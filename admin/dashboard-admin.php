<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/config/db.php';

session_boot();
require_role(1); // Admin

$pdo = db();
$today = date('Y-m-d');
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));

function kpi($pdo, $dateFrom, $dateTo, $statusName = null) {
  $sql = "SELECT COUNT(*)
          FROM appointments a
          JOIN appointment_status st ON st.id = a.status_id
          WHERE a.scheduled_date BETWEEN :d1 AND :d2";
  $params = [':d1'=>$dateFrom, ':d2'=>$dateTo];
  if ($statusName) { $sql .= " AND st.name = :st"; $params[':st'] = $statusName; }
  $st = $pdo->prepare($sql);
  $st->execute($params);
  return (int)$st->fetchColumn();
}

$kpi_today_total = kpi($pdo, $today, $today);
$kpi_today_conf  = kpi($pdo, $today, $today, 'confirmada');
$kpi_today_canc  = kpi($pdo, $today, $today, 'cancelada');
$kpi_today_atend = kpi($pdo, $today, $today, 'atendida');

$kpi_week_total = kpi($pdo, $monday, $sunday);
$kpi_week_conf  = kpi($pdo, $monday, $sunday, 'confirmada');
$kpi_week_canc  = kpi($pdo, $monday, $sunday, 'cancelada');
$kpi_week_atend = kpi($pdo, $monday, $sunday, 'atendida');

// Próximas citas (hoy y futuras cercanas)
$upcoming = $pdo->prepare("SELECT a.id, a.scheduled_date, a.scheduled_time, st.name AS status, u.full_name AS doctor, p.full_name AS patient, s.name AS specialty
                           FROM appointments a
                           JOIN appointment_status st ON st.id = a.status_id
                           JOIN users u ON u.id = a.doctor_id
                           JOIN users p ON p.id = a.patient_id
                           JOIN specialties s ON s.id = a.specialty_id
                           WHERE a.scheduled_date >= :today
                           ORDER BY a.scheduled_date ASC, a.scheduled_time ASC
                           LIMIT 10");
$upcoming->execute([':today'=>$today]);
$nextRows = $upcoming->fetchAll();

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin | Clínica Moya</title>
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
      <a class="navbar-brand d-flex align-items-center" href="#">
        <img src="../assets/images/logo-sm.svg" alt="" height="24" class="me-2"> <span>Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-primary btn-sm" href="doctores.php">Doctores</a>
        <a class="btn btn-outline-secondary btn-sm" href="feriados.php">Feriados</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row g-4">
      <div class="col-12">
        <h4 class="mb-3">Hoy (<?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?>)</h4>
        <div class="row g-3">
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Citas</div><div class="fs-3 fw-bold"><?php echo $kpi_today_total; ?></div></div></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Confirmadas</div><div class="fs-3 fw-bold text-info"><?php echo $kpi_today_conf; ?></div></div></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Atendidas</div><div class="fs-3 fw-bold text-success"><?php echo $kpi_today_atend; ?></div></div></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Canceladas</div><div class="fs-3 fw-bold text-secondary"><?php echo $kpi_today_canc; ?></div></div></div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <h4 class="mb-3 mt-4">Semana (<?php echo htmlspecialchars($monday, ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($sunday, ENT_QUOTES, 'UTF-8'); ?>)</h4>
        <div class="row g-3">
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Citas</div><div class="fs-3 fw-bold"><?php echo $kpi_week_total; ?></div></div></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Confirmadas</div><div class="fs-3 fw-bold text-info"><?php echo $kpi_week_conf; ?></div></div></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Atendidas</div><div class="fs-3 fw-bold text-success"><?php echo $kpi_week_atend; ?></div></div></div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card shadow-sm"><div class="card-body"><div class="text-muted">Canceladas</div><div class="fs-3 fw-bold text-secondary"><?php echo $kpi_week_canc; ?></div></div></div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card shadow-sm mt-4">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <strong>Próximas citas</strong>
            <div>
              <a class="btn btn-sm btn-outline-light" href="doctores.php">Gestionar doctores</a>
              <a class="btn btn-sm btn-outline-light" href="feriados.php">Feriados</a>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Fecha</th><th>Hora</th><th>Estado</th><th>Doctor</th><th>Paciente</th><th>Especialidad</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($nextRows as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['scheduled_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars(substr($r['scheduled_time'],0,5), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="badge bg-<?php echo $r['status']==='reservada'?'warning':($r['status']==='confirmada'?'info':($r['status']==='atendida'?'success':($r['status']==='cancelada'?'secondary':'light'))); ?>"><?php echo htmlspecialchars(ucfirst($r['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo htmlspecialchars($r['doctor'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['patient'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($r['specialty'], ENT_QUOTES, 'UTF-8'); ?></td>
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
