<?php
// backend/controllers/UserController.php

require_once __DIR__ . '/../config/db.php';

class UserController {
    public function listDoctors(): array {
        $pdo = db();
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.is_active,
                       d.license_number,
                       GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ', ') AS specialties
                FROM users u
                LEFT JOIN doctors d ON d.user_id = u.id
                LEFT JOIN doctor_specialty ds ON ds.doctor_id = u.id
                LEFT JOIN specialties s ON s.id = ds.specialty_id
                WHERE u.role_id = 2
                GROUP BY u.id, u.username, u.email, u.full_name, u.is_active, d.license_number
                ORDER BY u.full_name";
        $st = $pdo->query($sql);
        return $st->fetchAll();
    }

    public function createDoctor(string $fullName, string $username, string $email, string $password, ?string $licenseNumber, array $specialtyIds): int {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Crear usuario doctor
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insUser = $pdo->prepare("INSERT INTO users (role_id, username, email, password_hash, full_name, is_active)
                                      VALUES (2, :un, :em, :ph, :fn, 1)");
            $insUser->execute([
                ':un' => $username,
                ':em' => $email,
                ':ph' => $hash,
                ':fn' => $fullName,
            ]);
            $userId = (int)$pdo->lastInsertId();

            // Crear perfil doctor
            $insDoc = $pdo->prepare("INSERT INTO doctors (user_id, license_number) VALUES (:id, :lic)");
            $insDoc->execute([':id' => $userId, ':lic' => $licenseNumber]);

            // Especialidades
            if (!empty($specialtyIds)) {
                $insDS = $pdo->prepare("INSERT INTO doctor_specialty (doctor_id, specialty_id) VALUES (:d, :s)");
                foreach ($specialtyIds as $sid) {
                    $sid = (int)$sid;
                    if ($sid > 0) {
                        $insDS->execute([':d' => $userId, ':s' => $sid]);
                    }
                }
            }

            $pdo->commit();
            return $userId;
        } catch (Throwable $t) {
            $pdo->rollBack();
            throw $t;
        }
    }

    public function toggleUserActive(int $userId, bool $active): bool {
        $pdo = db();
        $st = $pdo->prepare("UPDATE users SET is_active = :a WHERE id = :id");
        return $st->execute([':a' => $active ? 1 : 0, ':id' => $userId]);
    }

    public function listUsers(?string $q = null, ?int $roleId = null, ?int $active = null): array {
        $pdo = db();
        $where = [];
        $params = [];
        if ($q) { $where[] = '(u.username LIKE :q OR u.email LIKE :q OR u.full_name LIKE :q)'; $params[':q'] = "%$q%"; }
        if ($roleId) { $where[] = 'u.role_id = :r'; $params[':r'] = $roleId; }
        if ($active !== null) { $where[] = 'u.is_active = :a'; $params[':a'] = $active; }
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.role_id, u.is_active
                FROM users u";
        if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
        $sql .= ' ORDER BY u.full_name';
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function createUser(string $fullName, string $username, string $email, string $password, int $roleId): int {
        if (!in_array($roleId, [1,3], true)) {
            throw new InvalidArgumentException('Este formulario solo crea Admins o Pacientes');
        }
        if (strlen($password) < 8) { throw new InvalidArgumentException('Contraseña mínima 8 caracteres'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new InvalidArgumentException('Email inválido'); }
        $pdo = db();
        // duplicados
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
        $st->execute([':u'=>$username]);
        if ((int)$st->fetchColumn() > 0) { throw new RuntimeException('El usuario ya existe'); }
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
        $st->execute([':e'=>$email]);
        if ((int)$st->fetchColumn() > 0) { throw new RuntimeException('El email ya está registrado'); }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (role_id, username, email, password_hash, full_name, is_active) VALUES (:r, :u, :e, :ph, :f, 1)');
        $ins->execute([':r'=>$roleId, ':u'=>$username, ':e'=>$email, ':ph'=>$hash, ':f'=>$fullName]);
        $id = (int)$pdo->lastInsertId();
        if ($roleId === 3) {
            // crear perfil paciente si aplica
            $pdo->prepare('INSERT IGNORE INTO patients (user_id) VALUES (:id)')->execute([':id'=>$id]);
        }
        return $id;
    }

    public function resetPassword(int $userId, string $newPassword): bool {
        if (strlen($newPassword) < 8) { throw new InvalidArgumentException('Contraseña mínima 8 caracteres'); }
        $pdo = db();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $st = $pdo->prepare('UPDATE users SET password_hash = :ph WHERE id = :id');
        return $st->execute([':ph'=>$hash, ':id'=>$userId]);
    }

    /**
     * Reemplaza disponibilidad del doctor: elimina y vuelve a insertar rangos activos.
     * $ranges: array de ['weekday'=>1..7, 'start'=>'HH:MM', 'end'=>'HH:MM', 'active'=>bool]
     */
    public function setDoctorAvailability(int $doctorUserId, array $ranges): void {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM doctor_availability WHERE doctor_id = :d");
            $del->execute([':d' => $doctorUserId]);

            $ins = $pdo->prepare("INSERT INTO doctor_availability (doctor_id, weekday, start_time, end_time, is_active)
                                   VALUES (:d, :w, :s, :e, :a)");
            foreach ($ranges as $r) {
                $w = (int)($r['weekday'] ?? 0);
                $s = trim($r['start'] ?? '');
                $e = trim($r['end'] ?? '');
                $a = !empty($r['active']) ? 1 : 0;
                if ($a !== 1) { continue; }
                if ($w < 1 || $w > 7) { continue; }
                if ($s === '' || $e === '') { continue; }
                // Normalizar a HH:MM:SS
                if (strlen($s) === 5) { $s .= ':00'; }
                if (strlen($e) === 5) { $e .= ':00'; }
                if (strtotime('1970-01-01 '.$s) === false || strtotime('1970-01-01 '.$e) === false) { continue; }
                if ($s >= $e) { continue; }
                $ins->execute([':d' => $doctorUserId, ':w' => $w, ':s' => $s, ':e' => $e, ':a' => 1]);
            }

            $pdo->commit();
        } catch (Throwable $t) {
            $pdo->rollBack();
            throw $t;
        }
    }

    public function getDoctorAvailability(int $doctorUserId): array {
        $pdo = db();
        $st = $pdo->prepare("SELECT weekday, start_time, end_time, is_active FROM doctor_availability WHERE doctor_id = :d ORDER BY weekday, start_time");
        $st->execute([':d' => $doctorUserId]);
        return $st->fetchAll();
    }
}
