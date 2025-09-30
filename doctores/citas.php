<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';

session_boot();
require_role(2); // solo doctores

$user = current_user();
$app = new AppointmentController();
$success = flash_get('success');
$error = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    try {
        verify_csrf_or_die();
        if ($action === 'confirm') {
            $app->doctorConfirmAppointment($id, (int)$user['id'], (int)$user['id']);
            flash_set('success', 'Cita confirmada');
            header('Location: citas.php');
            exit;
        } elseif ($action === 'cancel') {
            $reason = trim($_POST['reason'] ?? '');
            $app->doctorCancelAppointment($id, (int)$user['id'], (int)$user['id'], $reason);
            flash_set('success', 'Cita cancelada');
            header('Location: citas.php');
            exit;
        } elseif ($action === 'complete') {
            $app->doctorCompleteAppointment($id, (int)$user['id'], (int)$user['id']);
            flash_set('success', 'Cita marcada como atendida');
            header('Location: citas.php');
            exit;
        }
    } catch (Throwable $t) {
        flash_set('error', $t->getMessage());
        header('Location: citas.php');
        exit;
    }
}

$list = $app->getDoctorAppointments((int)$user['id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Citas (Doctor) | Clínica Moya</title>
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
        <img src="../assets/images/logoMoya.png" alt="" height="34" class="me-2"> <span>Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted d-none d-sm-inline"><?php echo htmlspecialchars($user['full_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Citas asignadas</h5>
          </div>
          <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <?php if (!$list): ?>
              <div class="alert alert-info">No tienes citas registradas.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Hora</th>
                      <th>Paciente</th>
                      <th>Especialidad</th>
                      <th>Estado</th>
                      <th class="text-end">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $today = date('Y-m-d');
                      $now = date('H:i:s');
                      foreach ($list as $row):
                        $isFuture = ($row['scheduled_date'] > $today) || ($row['scheduled_date'] === $today && $row['scheduled_time'] > $now);
                        $isPastOrNow = ($row['scheduled_date'] < $today) || ($row['scheduled_date'] === $today && $row['scheduled_time'] <= $now);
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['scheduled_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars(substr($row['scheduled_time'],0,5), ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($row['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($row['specialty'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td>
                        <span class="badge bg-<?php echo $row['status']==='reservada'?'warning':($row['status']==='confirmada'?'info':($row['status']==='atendida'?'success':($row['status']==='cancelada'?'secondary':'light'))); ?>">
                          <?php echo htmlspecialchars(ucfirst($row['status']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                          <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="action" value="confirm">
                            <button class="btn btn-sm btn-outline-info" <?php echo ($row['status']==='reservada' && $isFuture)?'':'disabled'; ?>>Confirmar</button>
                          </form>
                          <form method="post" class="d-inline d-flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="action" value="cancel">
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Motivo" maxlength="255" style="max-width:220px;" <?php echo ($isFuture && $row['status']!=='cancelada')?'required':''; ?>>
                            <button class="btn btn-sm btn-outline-danger" <?php echo ($isFuture && $row['status']!=='cancelada')?'':'disabled'; ?>>Cancelar</button>
                          </form>
                          <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="action" value="complete">
                            <button class="btn btn-sm btn-outline-success" <?php echo ($isPastOrNow && in_array($row['status'], ['reservada','confirmada']))?'':'disabled'; ?>>Atendida</button>
                          </form>
                          <a class="btn btn-sm btn-primary" href="ficha.php?patient=<?php echo (int)$row['patient_id']; ?>">Enviar ficha</a>
                        </div>
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
