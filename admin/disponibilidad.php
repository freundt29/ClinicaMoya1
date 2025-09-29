<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/UserController.php';

session_boot();
require_role(1); // Admin

$users = new UserController();
$success = flash_get('success');
$error = flash_get('error');

$doctorId = isset($_GET['doctor']) ? (int)$_GET['doctor'] : 0;
if ($doctorId <= 0) {
  http_response_code(400);
  echo 'Falta parámetro doctor';
  exit;
}

$availability = $users->getDoctorAvailability($doctorId);
// Mapa weekday => [start,end]
$daysMap = [];
foreach ($availability as $row) {
  $w = (int)$row['weekday'];
  $daysMap[$w] = [
    'start' => substr($row['start_time'],0,5),
    'end'   => substr($row['end_time'],0,5),
    'active'=> ((int)$row['is_active']===1)
  ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    verify_csrf_or_die();
    $ranges = [];
    $schedule = $_POST['schedule'] ?? [];
    $errors = [];
    for ($w=1; $w<=6; $w++) {
      $row = $schedule[$w] ?? [];
      $active = !empty($row['active']);
      $start = trim($row['start'] ?? '');
      $end   = trim($row['end'] ?? '');
      if ($active) {
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
          $errors["w$w"] = 'Use formato HH:MM';
        } else {
          list($sh,$sm) = array_map('intval', explode(':',$start));
          list($eh,$em) = array_map('intval', explode(':',$end));
          if ($sm % 20 !== 0 || $em % 20 !== 0) {
            $errors["w$w"] = 'Minutos válidos: 00, 20 o 40';
          } elseif (sprintf('%02d:%02d',$sh,$sm) >= sprintf('%02d:%02d',$eh,$em)) {
            $errors["w$w"] = 'Inicio debe ser menor que fin';
          }
        }
      }
      $ranges[] = [
        'weekday'=>$w,
        'start'=>$start,
        'end'=>$end,
        'active'=>$active,
      ];
    }
    if (!empty($errors)) {
      $error = 'Corrige los errores marcados.';
    } else {
      $users->setDoctorAvailability($doctorId, $ranges);
      flash_set('success', 'Disponibilidad actualizada');
      header('Location: disponibilidad.php?doctor='.(int)$doctorId);
      exit;
    }
  } catch (Throwable $t) {
    flash_set('error', $t->getMessage());
    header('Location: disponibilidad.php?doctor='.(int)$doctorId);
    exit;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Editar disponibilidad | Clínica Moya</title>
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
      <a class="navbar-brand d-flex align-items-center" href="dashboard-admin.html">
        <img src="../assets/images/logo-sm.svg" alt="" height="24" class="me-2"> <span>Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="doctores.php">Volver a Doctores</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Editar disponibilidad</strong></div>
          <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <form method="post">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <?php $days=[1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
              foreach($days as $w=>$label):
                $row = $daysMap[$w] ?? ['start'=>'09:00','end'=>'18:00','active'=>true];
              ?>
              <div class="row align-items-center g-2 mb-2">
                <div class="col-5">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="dw<?php echo $w; ?>" name="schedule[<?php echo $w; ?>][active]" <?php echo $row['active']?'checked':''; ?>>
                    <label class="form-check-label" for="dw<?php echo $w; ?>"><?php echo $label; ?></label>
                  </div>
                </div>
                <div class="col-3">
                  <input type="time" class="form-control" name="schedule[<?php echo $w; ?>][start]" value="<?php echo htmlspecialchars($row['start'], ENT_QUOTES, 'UTF-8'); ?>" step="1200">
                </div>
                <div class="col-3">
                  <input type="time" class="form-control" name="schedule[<?php echo $w; ?>][end]" value="<?php echo htmlspecialchars($row['end'], ENT_QUOTES, 'UTF-8'); ?>" step="1200">
                </div>
              </div>
              <?php endforeach; ?>
              <div class="text-end mt-3">
                <button class="btn btn-primary">Guardar cambios</button>
              </div>
            </form>
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
