<?php
// backend/controllers/AuthController.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/session.php';

class AuthController {
    public function login(string $usernameOrEmail, string $password): bool {
        $pdo = db();

        // Buscar por username o email y activo
        $sql = "SELECT id, role_id, username, email, password_hash, full_name, is_active
                FROM users
                WHERE (username = :u OR email = :e) AND is_active = 1 AND deleted_at IS NULL
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':u' => $usernameOrEmail, ':e' => $usernameOrEmail]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_login($user);
        return true;
    }

    public function logout(): void {
        session_logout();
    }
}
