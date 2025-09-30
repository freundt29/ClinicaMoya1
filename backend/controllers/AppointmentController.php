<?php
// backend/controllers/AppointmentController.php

require_once __DIR__ . '/../config/db.php';

class AppointmentController {
    public function getSpecialties(): array {
        $pdo = db();
        $stmt = $pdo->query('SELECT id, name FROM specialties ORDER BY name');
        return $stmt->fetchAll();
    }

    /**
     * Listado de citas por doctor (solo sus citas), ordenadas por fecha/hora desc.
     */
    public function getDoctorAppointments(int $doctorUserId): array {
        $pdo = db();
        $sql = "SELECT a.id,
                       a.scheduled_date,
                       a.scheduled_time,
                       a.reason,
                       a.patient_id,
                       s.name AS specialty,
                       u.full_name AS patient_name,
                       st.name AS status
                FROM appointments a
                JOIN specialties s ON s.id = a.specialty_id
                JOIN users u ON u.id = a.patient_id
                JOIN appointment_status st ON st.id = a.status_id
                WHERE a.doctor_id = :did
                ORDER BY a.scheduled_date DESC, a.scheduled_time DESC";
        $st = $pdo->prepare($sql);
        $st->execute([':did' => $doctorUserId]);
        return $st->fetchAll();
    }

    /** Confirmar cita (solo el doctor dueño, en futuro, si está 'reservada'). */
    public function doctorConfirmAppointment(int $appointmentId, int $doctorUserId, int $userId): bool {
        $pdo = db();
        $row = $this->loadAppointmentBasic($appointmentId);
        if (!$row) { throw new RuntimeException('Cita no encontrada'); }
        if ((int)$row['doctor_id'] !== $doctorUserId) { throw new RuntimeException('No autorizado'); }
        $today = date('Y-m-d');
        $now = date('H:i:s');
        if ($row['scheduled_date'] < $today || ($row['scheduled_date'] === $today && $row['scheduled_time'] <= $now)) {
            throw new RuntimeException('Solo puede confirmar citas futuras');
        }
        if ($row['status'] !== 'reservada') { throw new RuntimeException('Solo se puede confirmar citas reservadas'); }

        $statusId = $this->statusIdByName('confirmada');
        $st = $pdo->prepare("UPDATE appointments SET status_id = :st WHERE id = :id");
        return $st->execute([':st' => $statusId, ':id' => $appointmentId]);
    }

    /** Cancelar cita (solo el doctor dueño, futuro, con motivo obligatorio). */
    public function doctorCancelAppointment(int $appointmentId, int $doctorUserId, int $userId, string $reason): bool {
        $pdo = db();
        $row = $this->loadAppointmentBasic($appointmentId);
        if (!$row) { throw new RuntimeException('Cita no encontrada'); }
        if ((int)$row['doctor_id'] !== $doctorUserId) { throw new RuntimeException('No autorizado'); }
        $today = date('Y-m-d');
        $now = date('H:i:s');
        if ($row['scheduled_date'] < $today || ($row['scheduled_date'] === $today && $row['scheduled_time'] <= $now)) {
            throw new RuntimeException('Solo puede cancelar citas futuras');
        }
        if ($reason === '') { throw new RuntimeException('Indique un motivo de cancelación'); }

        $statusId = $this->statusIdByName('cancelada');
        $st = $pdo->prepare("UPDATE appointments
                              SET status_id = :st, canceled_by = :cb, cancellation_reason = :cr
                              WHERE id = :id");
        return $st->execute([':st' => $statusId, ':cb' => $userId, ':cr' => $reason, ':id' => $appointmentId]);
    }

    /** Marcar atendida (solo el doctor dueño, cuando ya pasó la hora o es el mismo día con hora alcanzada). */
    public function doctorCompleteAppointment(int $appointmentId, int $doctorUserId, int $userId): bool {
        $pdo = db();
        $row = $this->loadAppointmentBasic($appointmentId);
        if (!$row) { throw new RuntimeException('Cita no encontrada'); }
        if ((int)$row['doctor_id'] !== $doctorUserId) { throw new RuntimeException('No autorizado'); }
        $today = date('Y-m-d');
        $now = date('H:i:s');
        if ($row['scheduled_date'] > $today || ($row['scheduled_date'] === $today && $row['scheduled_time'] > $now)) {
            throw new RuntimeException('Solo puede marcar como atendida una cita en curso o pasada');
        }
        if (!in_array($row['status'], ['reservada','confirmada'], true)) {
            throw new RuntimeException('La cita debe estar reservada o confirmada'); }

        $statusId = $this->statusIdByName('atendida');
        $st = $pdo->prepare("UPDATE appointments SET status_id = :st WHERE id = :id");
        return $st->execute([':st' => $statusId, ':id' => $appointmentId]);
    }

    /** Utilidades privadas */
    private function statusIdByName(string $name): int {
        $pdo = db();
        $st = $pdo->prepare("SELECT id FROM appointment_status WHERE name = :n LIMIT 1");
        $st->execute([':n' => $name]);
        $id = (int)$st->fetchColumn();
        if ($id === 0) { throw new RuntimeException("Estado '$name' no definido"); }
        return $id;
    }

    private function loadAppointmentBasic(int $appointmentId): ?array {
        $pdo = db();
        $sql = "SELECT a.id, a.patient_id, a.doctor_id, a.scheduled_date, a.scheduled_time, st.name AS status
                FROM appointments a
                JOIN appointment_status st ON st.id = a.status_id
                WHERE a.id = :id";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $appointmentId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function getDoctorsBySpecialty(int $specialtyId): array {
        $pdo = db();
        $sql = "SELECT d.user_id AS id, u.full_name, u.email, d.years_experience, d.bio
                FROM doctor_specialty ds
                INNER JOIN doctors d ON d.user_id = ds.doctor_id
                INNER JOIN users u ON u.id = d.user_id
                WHERE ds.specialty_id = :sid AND u.is_active = 1 AND u.deleted_at IS NULL
                ORDER BY u.full_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':sid' => $specialtyId]);
        return $stmt->fetchAll();
    }

    public function createAppointment(int $patientId, int $doctorId, int $specialtyId, string $date, string $time, ?string $reason, int $createdBy): int {
        $pdo = db();
        // Validación: no trabajar domingos
        $ts = strtotime($date);
        if ($ts === false) {
            throw new InvalidArgumentException('Fecha inválida');
        }
        $today = date('Y-m-d');
        $max   = date('Y-m-d', strtotime('+2 months'));
        if ($date < $today) {
            throw new RuntimeException('No se pueden reservar fechas pasadas');
        }
        if ($date > $max) {
            throw new RuntimeException('La fecha excede el máximo permitido (2 meses)');
        }
        $weekday = (int)date('N', $ts); // 1..7 (7=domingo)
        if ($weekday === 7) {
            throw new RuntimeException('La clínica no atiende los domingos');
        }

        // Validación: el horario elegido debe estar disponible (slots de 20m)
        $available = $this->getAvailableTimes($doctorId, $date, 20);
        $timeNorm = substr($time, 0, 5); // HH:MM
        if (!in_array($timeNorm, $available, true)) {
            throw new RuntimeException('El horario seleccionado no está disponible');
        }
        // Obtener status_id para 'reservada'
        $statusSql = "SELECT id FROM appointment_status WHERE name = 'reservada' LIMIT 1";
        $statusId = (int)$pdo->query($statusSql)->fetchColumn();
        if ($statusId === 0) {
            throw new RuntimeException("No existe el estado 'reservada'");
        }

        // Insertar cita
        $sql = "INSERT INTO appointments (patient_id, doctor_id, specialty_id, status_id, scheduled_date, scheduled_time, reason, created_by)
                VALUES (:pid, :did, :sid, :st, :d, :t, :r, :cb)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':pid' => $patientId,
            ':did' => $doctorId,
            ':sid' => $specialtyId,
            ':st'  => $statusId,
            ':d'   => $date,
            ':t'   => $time,
            ':r'   => $reason,
            ':cb'  => $createdBy,
        ]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Retorna una lista de horas disponibles (HH:MM) para un doctor y fecha dados.
     * Basado en:
     *  - doctor_availability (weekday, start_time, end_time, is_active)
     *  - appointments existentes para ese doctor en ese día
     * Slot por defecto: 30 minutos.
     */
    public function getAvailableTimes(int $doctorId, string $date, int $slotMinutes = 20): array {
        $pdo = db();
        $ts = strtotime($date);
        if ($ts === false) { return []; }
        // Bloquear fechas pasadas (comparación por día, no por hora)
        $today = date('Y-m-d');
        $max   = date('Y-m-d', strtotime('+2 months'));
        if ($date < $today) { return []; }
        if ($date > $max) { return []; }
        // ISO 8601: 1 (lunes) ... 7 (domingo)
        $weekday = (int)date('N', $ts);
        // Bloquear domingos
        if ($weekday === 7) { return []; }

        // Feriados a nivel clínica
        try {
            $isHoliday = (int)$pdo->prepare("SELECT COUNT(*) FROM clinic_holidays WHERE holiday_date = :d")
                                  ->execute([':d' => $date]) === false ? 0 : 0; // placeholder
            $stHol = $pdo->prepare("SELECT COUNT(*) FROM clinic_holidays WHERE holiday_date = :d");
            $stHol->execute([':d' => $date]);
            if ((int)$stHol->fetchColumn() > 0) { return []; }
        } catch (Throwable $e) {
            // Si la tabla no existe aún, ignorar silenciosamente
        }

        // Ausencias del doctor por rango
        try {
            $stAbs = $pdo->prepare("SELECT COUNT(*) FROM doctor_absences WHERE doctor_id = :d AND :dt BETWEEN start_date AND end_date");
            $stAbs->execute([':d' => $doctorId, ':dt' => $date]);
            if ((int)$stAbs->fetchColumn() > 0) { return []; }
        } catch (Throwable $e) {
            // Si la tabla no existe aún, ignorar
        }

        // Traer disponibilidad activa para ese día
        $sqlAv = "SELECT start_time, end_time
                  FROM doctor_availability
                  WHERE doctor_id = :d AND weekday = :w AND is_active = 1";
        $stAv = $pdo->prepare($sqlAv);
        $stAv->execute([':d' => $doctorId, ':w' => $weekday]);
        $rows = $stAv->fetchAll();
        if (!$rows) { return []; }

        // Traer horas ya reservadas para ese doctor y fecha
        $sqlBusy = "SELECT scheduled_time FROM appointments
                    WHERE doctor_id = :d AND scheduled_date = :dt";
        $stBusy = $pdo->prepare($sqlBusy);
        $stBusy->execute([':d' => $doctorId, ':dt' => $date]);
        $busyTimes = array_column($stBusy->fetchAll(), 'scheduled_time'); // 'HH:MM:SS'
        // Expandir ocupación a 1 hora (3 slots): t, t+20, t+40
        $busySet = [];
        foreach ($busyTimes as $bt) {
            // Normalizar base
            $t0 = strtotime($date . ' ' . $bt);
            if ($t0 === false) { continue; }
            $b0 = date('H:i', $t0);
            $b1 = date('H:i', $t0 + $slotMinutes * 60);
            $b2 = date('H:i', $t0 + 2 * $slotMinutes * 60);
            $busySet[$b0] = true;
            $busySet[$b1] = true;
            $busySet[$b2] = true;
        }

        $available = [];
        foreach ($rows as $av) {
            $start = strtotime($date . ' ' . $av['start_time']);
            $end   = strtotime($date . ' ' . $av['end_time']);
            if ($start === false || $end === false || $start >= $end) { continue; }

            // Para citas de 1 hora (3 slots), el inicio debe caber: t + 60m <= end
            for ($t = $start; $t + 60 * 60 <= $end; $t += $slotMinutes * 60) {
                $hhmm0 = date('H:i', $t);
                $hhmm1 = date('H:i', $t + $slotMinutes * 60);
                $hhmm2 = date('H:i', $t + 2 * $slotMinutes * 60);
                // Excluir si cualquier slot está ocupado
                if (isset($busySet[$hhmm0]) || isset($busySet[$hhmm1]) || isset($busySet[$hhmm2])) {
                    continue;
                }
                $available[] = $hhmm0;
            }
        }

        // Ordenar y únicos por si hay múltiples rangos
        $available = array_values(array_unique($available));
        sort($available);
        return $available;
    }

    /**
     * Lista citas del paciente con datos de doctor, especialidad y estado.
     */
    public function getPatientAppointments(int $patientId): array {
        $pdo = db();
        $sql = "SELECT a.id,
                       a.scheduled_date,
                       a.scheduled_time,
                       a.reason,
                       s.name AS specialty,
                       u.full_name AS doctor_name,
                       st.name AS status
                FROM appointments a
                JOIN specialties s ON s.id = a.specialty_id
                JOIN users u ON u.id = a.doctor_id
                JOIN appointment_status st ON st.id = a.status_id
                WHERE a.patient_id = :pid
                ORDER BY a.scheduled_date DESC, a.scheduled_time DESC";
        $st = $pdo->prepare($sql);
        $st->execute([':pid' => $patientId]);
        return $st->fetchAll();
    }

    /**
     * Cancela una cita del paciente si es futura y le pertenece.
     */
    public function cancelAppointment(int $appointmentId, int $patientId, int $userId, ?string $reason = null): bool {
        $pdo = db();
        // Verificar pertenencia y que sea futura y no cancelada
        $sql = "SELECT a.id, a.patient_id, a.scheduled_date, a.scheduled_time, st.name AS status
                FROM appointments a
                JOIN appointment_status st ON st.id = a.status_id
                WHERE a.id = :id";
        $st = $pdo->prepare($sql);
        $st->execute([':id' => $appointmentId]);
        $row = $st->fetch();
        if (!$row) { throw new RuntimeException('Cita no encontrada'); }
        if ((int)$row['patient_id'] !== $patientId) { throw new RuntimeException('No autorizado'); }
        $today = date('Y-m-d');
        $nowTime = date('H:i:s');
        if ($row['scheduled_date'] < $today || ($row['scheduled_date'] === $today && $row['scheduled_time'] <= $nowTime)) {
            throw new RuntimeException('Solo se pueden cancelar citas futuras');
        }
        if ($row['status'] === 'cancelada') { return true; }

        // Obtener status cancelada
        $statusCancel = (int)$pdo->query("SELECT id FROM appointment_status WHERE name = 'cancelada' LIMIT 1")->fetchColumn();
        if (!$statusCancel) { throw new RuntimeException('Estado cancelada no definido'); }

        $upd = $pdo->prepare("UPDATE appointments
                               SET status_id = :st, canceled_by = :cb, cancellation_reason = :cr
                               WHERE id = :id");
        return $upd->execute([
            ':st' => $statusCancel,
            ':cb' => $userId,
            ':cr' => $reason,
            ':id' => $appointmentId,
        ]);
    }
}
