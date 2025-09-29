<?php
require __DIR__ . '/../backend/config/db.php';

try {
    $pdo = db();
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM specialties');
    $row = $stmt->fetch();
    echo 'Conexión OK. Especialidades: ' . ($row['total'] ?? 0);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error de conexión: ' . $e->getMessage();
}
echo 'Sirviendo desde: ' . realpath(__DIR__);