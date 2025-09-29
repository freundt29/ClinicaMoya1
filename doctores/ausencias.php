<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/config/db.php';

session_boot();
require_role(2); // Doctor

$pdo = db();
$user = current_user();
$success = flash_get('success');
$error = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
      $start = trim($_POST['start_date'] ?? '');
      $end = trim($_POST['end_date'] ?? '');
      $reason = trim($_POST['reason'] ?? '');
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
        throw new InvalidArgumentException('Fechas inválidas');
      }
      if ($end < $start) { throw new InvalidArgumentException('El fin debe ser mayor o igual al inicio'); }
      $st = $pdo->prepare('INSERT INTO doctor_absences(doctor_id, start_date, end_date, reason) VALUES (:d, :s, :e, :r)');
      $st->execute([':d'=>(int)$user['id'], ':s'=>$start, ':e'=>$end, ':r'=>$reason]);
      flash_set('success','Ausencia registrada');
      header('Location: ausencias.php');
      exit;
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM doctor_absences WHERE id = :id AND doctor_id = :d')->execute([':id'=>$id, ':d'=>(int)$user['id']]);
      flash_set('success','Ausencia eliminada');
      header('Location: ausencias.php');
      exit;
    }
  } catch (Throwable $t) {
    flash_set('error', $t->getMessage());
    header('Location: ausencias.php');
    exit;
  }
}

// Listar ausencias del doctor
$rows = [];
try {
  $st = $pdo->prepare('SELECT id, start_date, end_date, reason FROM doctor_absences WHERE doctor_id = :d ORDER BY start_date DESC');
  $st->execute([':d'=>(int)$user['id']]);
  $rows = $st->fetchAll();
} catch (Throwable $t) {
  $error = $error ?: 'Aún no has creado la tabla doctor_absences (ver instrucciones en conversación).';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Ausencias | Clínica Moya</title>
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
      <a class="navbar-brand d-flex align-items-center" href="citas.php">
        <img src="../assets/images/logo-sm.svg" alt="" height="24" class="me-2"> <span>Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="citas.php">Mis Citas</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row g-4">
      <div class="col-12 col-lg-5 order-lg-2">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Registrar ausencia</strong></div>
          <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="action" value="create">
              <div class="mb-3">
                <label class="form-label">Inicio</label>
                <input type="date" name="start_date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Fin</label>
                <input type="date" name="end_date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Motivo</label>
                <input type="text" name="reason" class="form-control" maxlength="200" placeholder="Opcional">
              </div>
              <div class="text-end"><button class="btn btn-primary">Registrar</button></div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Mis Ausencias</strong></div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Motivo</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['start_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['end_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end">
                      <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar ausencia?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                      </form>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
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
