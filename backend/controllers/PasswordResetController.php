<?php
// backend/controllers/PasswordResetController.php

require_once __DIR__ . '/../config/db.php';

class PasswordResetController {
    
    /**
     * Crea un token de recuperación y lo guarda en la BD
     * Retorna el token generado (para enviarlo por email)
     */
    public function createResetToken(string $email): ?string {
        $pdo = db();
        
        // Verificar que el email existe y está activo
        $st = $pdo->prepare('SELECT id, full_name FROM users WHERE email = :e AND is_active = 1 AND deleted_at IS NULL');
        $st->execute([':e' => $email]);
        $user = $st->fetch();
        
        if (!$user) {
            return null; // Email no encontrado o usuario inactivo
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32)); // 64 caracteres
        
        // Expiración: 1 hora desde ahora
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Guardar token
        $ins = $pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:e, :t, :exp)');
        $ins->execute([':e' => $email, ':t' => $token, ':exp' => $expiresAt]);
        
        return $token;
    }
    
    /**
     * Valida un token de recuperación
     * Retorna el email asociado si es válido, null si no
     */
    public function validateToken(string $token): ?string {
        $pdo = db();
        
        $st = $pdo->prepare('SELECT email, expires_at, used FROM password_resets WHERE token = :t');
        $st->execute([':t' => $token]);
        $reset = $st->fetch();
        
        if (!$reset) {
            return null; // Token no existe
        }
        
        if ($reset['used'] == 1) {
            return null; // Token ya usado
        }
        
        if (strtotime($reset['expires_at']) < time()) {
            return null; // Token expirado
        }
        
        return $reset['email'];
    }
    
    /**
     * Cambia la contraseña usando un token válido
     */
    public function resetPassword(string $token, string $newPassword): bool {
        $email = $this->validateToken($token);
        
        if (!$email) {
            throw new InvalidArgumentException('Token inválido o expirado');
        }
        
        // Validar contraseña
        if (strlen($newPassword) < 8 || strlen($newPassword) > 255) {
            throw new InvalidArgumentException('La contraseña debe tener entre 8 y 255 caracteres');
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*\d).+$/', $newPassword)) {
            throw new InvalidArgumentException('La contraseña debe incluir mayúscula, minúscula, letras y números');
        }
        
        $pdo = db();
        $pdo->beginTransaction();
        
        try {
            // Actualizar contraseña
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE users SET password_hash = :ph WHERE email = :e');
            $upd->execute([':ph' => $hash, ':e' => $email]);
            
            // Marcar token como usado
            $mark = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE token = :t');
            $mark->execute([':t' => $token]);
            
            $pdo->commit();
            return true;
        } catch (Throwable $t) {
            $pdo->rollBack();
            throw $t;
        }
    }
    
    /**
     * Limpia tokens expirados (mantenimiento)
     */
    public function cleanExpiredTokens(): int {
        $pdo = db();
        $del = $pdo->prepare('DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1');
        $del->execute();
        return $del->rowCount();
    }
}
