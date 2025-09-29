<?php
// backend/helpers/session.php

// Arranque seguro de sesión
function session_boot(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Opciones seguras razonables (ajusta según necesidad y entorno HTTPS)
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// =====================
// CSRF & Flash helpers
// =====================

function csrf_token(): string {
    session_boot();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_or_die(): void {
    session_boot();
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        echo 'CSRF token inválido';
        exit;
    }
}

function flash_set(string $type, string $message): void {
    session_boot();
    $_SESSION['flash'][$type] = $message;
}

function flash_get(string $type): ?string {
    session_boot();
    if (!empty($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $msg;
    }
    return null;
}

function session_login(array $user): void {
    session_boot();
    // Regenerar ID para mitigar fijación de sesión
    if (function_exists('session_regenerate_id')) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = [
        'id' => $user['id'],
        'role_id' => $user['role_id'],
        'username' => $user['username'],
        'full_name' => $user['full_name'],
        'email' => $user['email'] ?? null,
    ];
}

function session_logout(): void {
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], ($params['secure'] ?? false), true);
    }
    session_destroy();
}

function current_user(): ?array {
    session_boot();
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    session_boot();
    if (!isset($_SESSION['user'])) {
        header('Location: /proyecto1moya/public/login.php');
        exit;
    }
}

function require_role(int $roleId): void {
    require_login();
    $user = current_user();
    if (!$user || (int)$user['role_id'] !== $roleId) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
}

function redirect_by_role(int $roleId): void {
    // 1=admin, 2=doctor, 3=paciente
    switch ($roleId) {
        case 1:
            header('Location: /proyecto1moya/admin/dashboard-admin.html');
            break;
        case 2:
            header('Location: /proyecto1moya/doctores/citas.php');
            break;
        case 3:
        default:
            header('Location: /proyecto1moya/public/paciente/dashboard.php');
            break;
    }
    exit;
}
