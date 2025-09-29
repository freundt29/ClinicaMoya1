<?php
// backend/controllers/MedicalRecordController.php
require_once __DIR__ . '/../config/db.php';

class MedicalRecordController {
    public function getDoctorPatients(int $doctorId): array {
        $pdo = db();
        $sql = "SELECT DISTINCT u.id, u.full_name, u.username
                FROM appointments a
                JOIN users u ON u.id = a.patient_id
                WHERE a.doctor_id = :d
                ORDER BY u.full_name";
        $st = $pdo->prepare($sql);
        $st->execute([':d' => $doctorId]);
        return $st->fetchAll();
    }

    public function createRecord(int $doctorId, int $patientId, string $diagnosis, string $treatment, ?int $appointmentId = null): int {
        if ($diagnosis === '' || $treatment === '') {
            throw new InvalidArgumentException('Diagnóstico y tratamiento son obligatorios');
        }
        $pdo = db();
        // Opcional: validar que el paciente tenga relación con el doctor vía citas
        $chk = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = :d AND patient_id = :p');
        $chk->execute([':d'=>$doctorId, ':p'=>$patientId]);
        if ((int)$chk->fetchColumn() === 0) {
            throw new RuntimeException('No existe relación de citas con este paciente');
        }
        $ins = $pdo->prepare('INSERT INTO medical_records (doctor_id, patient_id, appointment_id, diagnosis, treatment, created_at) VALUES (:d, :p, :a, :dx, :tx, NOW())');
        $ins->execute([
            ':d' => $doctorId,
            ':p' => $patientId,
            ':a' => $appointmentId,
            ':dx'=> $diagnosis,
            ':tx'=> $treatment,
        ]);
        return (int)$pdo->lastInsertId();
    }

    public function getPatientRecords(int $patientId): array {
        $pdo = db();
        $sql = "SELECT mr.id, mr.created_at, mr.diagnosis, mr.treatment,
                       u.full_name AS doctor_name
                FROM medical_records mr
                JOIN users u ON u.id = mr.doctor_id
                WHERE mr.patient_id = :p
                ORDER BY mr.created_at DESC";
        $st = $pdo->prepare($sql);
        $st->execute([':p' => $patientId]);
        return $st->fetchAll();
    }

    public function getDoctorRecords(int $doctorId): array {
        $pdo = db();
        $sql = "SELECT mr.id, mr.created_at, mr.diagnosis, mr.treatment,
                       u.full_name AS patient_name
                FROM medical_records mr
                JOIN users u ON u.id = mr.patient_id
                WHERE mr.doctor_id = :d
                ORDER BY mr.created_at DESC";
        $st = $pdo->prepare($sql);
        $st->execute([':d' => $doctorId]);
        return $st->fetchAll();
    }
}
