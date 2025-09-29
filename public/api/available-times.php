<?php
// public/api/available-times.php
header('Content-Type: application/json');

require_once __DIR__ . '/../../backend/helpers/session.php';
require_once __DIR__ . '/../../backend/controllers/AppointmentController.php';

session_boot();
require_role(3); // solo pacientes consultan

$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$date     = isset($_GET['date']) ? $_GET['date'] : '';

if ($doctorId <= 0 || !$date) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

try {
    $app = new AppointmentController();
    $slots = $app->getAvailableTimes($doctorId, $date);
    echo json_encode(['slots' => $slots]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
