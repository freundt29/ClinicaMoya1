<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';

session_boot();
require_role(3); // solo pacientes

$app = new AppointmentController();
$success = flash_get('success');
$error = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    verify_csrf_or_die();
    $user = current_user();
    $apptId = (int)$_POST['cancel_id'];
    $reason = trim($_POST['cancel_reason'] ?? '');
    try {
        $app->cancelAppointment($apptId, (int)$user['id'], (int)$user['id'], $reason !== '' ? $reason : null);
        flash_set('success', 'Cita cancelada correctamente.');
        header('Location: mis-citas.php');
        exit;
    } catch (Throwable $t) {
        flash_set('error', $t->getMessage());
        header('Location: mis-citas.php');
        exit;
    }
}

$user = current_user();
$list = $app->getPatientAppointments((int)$user['id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Citas | Clínica Moya</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="../assets/images/favicon.ico">
  <link rel="stylesheet" href="../assets/css/preloader.min.css" type="text/css" />
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
  <link href="../assets/css/app.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container-fluid px-4">
      <a class="navbar-brand d-flex align-items-center" href="../index.html">
        <img src="../assets/images/logo-sm.svg" alt="" height="24" class="me-2"> <span>Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <a href="reservar-cita.php" class="btn btn-outline-primary btn-sm">Reservar cita</a>
        <a href="mis-fichas.php" class="btn btn-outline-secondary btn-sm">Mis Fichas</a>
        <span class="text-muted d-none d-sm-inline"><?php echo htmlspecialchars($user['full_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="btn btn-outline-danger btn-sm" href="logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Mis Citas</h5>
          </div>
          <div class="card-body">
            <?php if ($success): ?>
              <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
              <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (!$list): ?>
              <div class="alert alert-info">Aún no tienes citas registradas.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Hora</th>
                      <th>Especialidad</th>
                      <th>Médico</th>
                      <th>Estado</th>
                      <th>Motivo</th>
                      <th class="text-end">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $today = date('Y-m-d');
                    $now = date('H:i:s');
                    foreach ($list as $row):
                      $isFuture = ($row['scheduled_date'] > $today) || ($row['scheduled_date'] === $today && $row['scheduled_time'] > $now);
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['scheduled_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(substr($row['scheduled_time'],0,5), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($row['specialty'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($row['doctor_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <span class="badge bg-<?php echo $row['status']==='reservada'?'warning':($row['status']==='confirmada'?'info':($row['status']==='atendida'?'success':($row['status']==='cancelada'?'secondary':'light'))); ?>">
                          <?php echo htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td><?php echo htmlspecialchars($row['reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="text-end">
                        <?php if ($isFuture && $row['status'] !== 'cancelada'): ?>
                          <form method="post" class="d-inline-flex align-items-center gap-2" onsubmit="return confirm('¿Cancelar esta cita?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="cancel_id" value="<?php echo (int)$row['id']; ?>">
                            <input type="text" name="cancel_reason" class="form-control form-control-sm" placeholder="Motivo (opcional)" maxlength="255" style="max-width: 220px;">
                            <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                          </form>
                        <?php else: ?>
                          <button class="btn btn-sm btn-outline-secondary" disabled>No disponible</button>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/libs/jquery/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
  <script src="../assets/libs/simplebar/simplebar.min.js"></script>
  <script src="../assets/libs/node-waves/waves.min.js"></script>
  <script src="../assets/libs/feather-icons/feather.min.js"></script>
  <script src="../assets/libs/pace-js/pace.min.js"></script>
</body>
</html>
