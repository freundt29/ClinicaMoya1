<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/config/db.php';

session_boot();
require_role(1); // Admin

$pdo = db();
$success = flash_get('success');
$error = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
      $date = trim($_POST['holiday_date'] ?? '');
      $name = trim($_POST['name'] ?? '');
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { throw new InvalidArgumentException('Fecha inválida (use YYYY-MM-DD)'); }
      if ($name === '') { throw new InvalidArgumentException('Nombre del feriado requerido'); }
      $st = $pdo->prepare('INSERT INTO clinic_holidays(holiday_date, name) VALUES (:d, :n)');
      $st->execute([':d'=>$date, ':n'=>$name]);
      flash_set('success','Feriado agregado');
      header('Location: feriados.php');
      exit;
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $pdo->prepare('DELETE FROM clinic_holidays WHERE id = :id')->execute([':id'=>$id]);
      flash_set('success','Feriado eliminado');
      header('Location: feriados.php');
      exit;
    }
  } catch (Throwable $t) {
    flash_set('error', $t->getMessage());
    header('Location: feriados.php');
    exit;
  }
}

// Listar feriados
$rows = [];
try {
  $rows = $pdo->query('SELECT id, holiday_date, name FROM clinic_holidays ORDER BY holiday_date DESC')->fetchAll();
} catch (Throwable $t) {
  $error = $error ?: 'Aún no has creado la tabla clinic_holidays (ver instrucciones en conversación).';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Feriados | Clínica Moya</title>
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
      <a class="navbar-brand d-flex align-items-center" href="dashboard-admin.php">
        <img src="../assets/images/logoMoya.png" alt="" height="40" class="me-2"> 
      </a>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="doctores.php">Doctores</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>
  <div class="container my-5">
    <div class="row g-4">
      <div class="col-12 col-lg-5 order-lg-2">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Agregar feriado</strong></div>
          <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="action" value="create">
              <div class="mb-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="holiday_date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" name="name" class="form-control" maxlength="120" required>
              </div>
              <div class="text-end"><button class="btn btn-primary">Agregar</button></div>
            </form>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-7">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Feriados</strong></div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['holiday_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end">
                      <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar feriado?');">
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
