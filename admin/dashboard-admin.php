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

// Estadísticas generales
$totalDoctors = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 2 AND is_active = 1 AND deleted_at IS NULL")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id = 3 AND is_active = 1 AND deleted_at IS NULL")->fetchColumn();
$totalSpecialties = $pdo->query("SELECT COUNT(*) FROM specialties")->fetchColumn();

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

$user = current_user();

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
  <style>
    .stat-card {
      border-left: 4px solid;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }
    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }
    .welcome-card {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 15px;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top shadow-sm">
    <div class="container-fluid px-4">
      <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
        <img src="../assets/images/logoMoya.png" alt="" height="40" class="me-2"> 
        <span class="text-primary">Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted me-2 d-none d-md-inline">
          <i class="mdi mdi-account-circle"></i> <?php echo htmlspecialchars($user['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <a class="btn btn-outline-primary btn-sm" href="doctores.php"><i class="mdi mdi-doctor"></i> Doctores</a>
        <a class="btn btn-outline-info btn-sm" href="../accsroles/gestion-usuario.php"><i class="mdi mdi-account-group"></i> Usuarios</a>
        <a class="btn btn-outline-secondary btn-sm" href="feriados.php"><i class="mdi mdi-calendar"></i> Feriados</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php"><i class="mdi mdi-logout"></i> Salir</a>
      </div>
    </div>
  </nav>

  <div class="container-fluid px-4 py-4" style="background-color: #f8f9fa;">
    <div class="row g-4">
      <!-- Welcome Card -->
      <div class="col-12">
        <div class="welcome-card p-4 shadow">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h3 class="mb-2"><i class="mdi mdi-hand-wave"></i> ¡Bienvenido, <?php echo htmlspecialchars($user['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>!</h3>
              <p class="mb-0 opacity-75">Panel de administración de Clínica Moya</p>
              <p class="mb-0 mt-2"><i class="mdi mdi-calendar-today"></i> <?php echo date('l, d \d\e F \d\e Y'); ?></p>
            </div>
            <div class="col-md-4 text-end d-none d-md-block">
              <i class="mdi mdi-hospital-building" style="font-size: 80px; opacity: 0.3;"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas Generales -->
      <div class="col-12">
        <h5 class="mb-3"><i class="mdi mdi-chart-box"></i> Estadísticas Generales</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="card stat-card border-primary shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="text-muted mb-1">Doctores Activos</p>
                    <h2 class="mb-0 fw-bold text-primary"><?php echo $totalDoctors; ?></h2>
                  </div>
                  <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="mdi mdi-doctor"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card stat-card border-success shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="text-muted mb-1">Pacientes Registrados</p>
                    <h2 class="mb-0 fw-bold text-success"><?php echo $totalPatients; ?></h2>
                  </div>
                  <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="mdi mdi-account-multiple"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card stat-card border-info shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="text-muted mb-1">Especialidades</p>
                    <h2 class="mb-0 fw-bold text-info"><?php echo $totalSpecialties; ?></h2>
                  </div>
                  <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="mdi mdi-medical-bag"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Citas de Hoy -->
      <div class="col-12">
        <h5 class="mb-3"><i class="mdi mdi-calendar-today"></i> Citas de Hoy <span class="badge bg-primary"><?php echo htmlspecialchars($today, ENT_QUOTES, 'UTF-8'); ?></span></h5>
        <div class="row g-3">
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-dark shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-dark bg-opacity-10 text-dark mx-auto mb-2">
                  <i class="mdi mdi-calendar-check"></i>
                </div>
                <p class="text-muted mb-1">Total Citas</p>
                <h3 class="mb-0 fw-bold"><?php echo $kpi_today_total; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-info shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-2">
                  <i class="mdi mdi-check-circle"></i>
                </div>
                <p class="text-muted mb-1">Confirmadas</p>
                <h3 class="mb-0 fw-bold text-info"><?php echo $kpi_today_conf; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-success shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                  <i class="mdi mdi-check-all"></i>
                </div>
                <p class="text-muted mb-1">Atendidas</p>
                <h3 class="mb-0 fw-bold text-success"><?php echo $kpi_today_atend; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-secondary shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-2">
                  <i class="mdi mdi-close-circle"></i>
                </div>
                <p class="text-muted mb-1">Canceladas</p>
                <h3 class="mb-0 fw-bold text-secondary"><?php echo $kpi_today_canc; ?></h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Citas de la Semana -->
      <div class="col-12">
        <h5 class="mb-3"><i class="mdi mdi-calendar-week"></i> Citas de la Semana <span class="badge bg-secondary"><?php echo htmlspecialchars($monday, ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($sunday, ENT_QUOTES, 'UTF-8'); ?></span></h5>
        <div class="row g-3">
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-dark shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-dark bg-opacity-10 text-dark mx-auto mb-2">
                  <i class="mdi mdi-calendar-multiple"></i>
                </div>
                <p class="text-muted mb-1">Total Citas</p>
                <h3 class="mb-0 fw-bold"><?php echo $kpi_week_total; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-info shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info mx-auto mb-2">
                  <i class="mdi mdi-check-circle"></i>
                </div>
                <p class="text-muted mb-1">Confirmadas</p>
                <h3 class="mb-0 fw-bold text-info"><?php echo $kpi_week_conf; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-success shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                  <i class="mdi mdi-check-all"></i>
                </div>
                <p class="text-muted mb-1">Atendidas</p>
                <h3 class="mb-0 fw-bold text-success"><?php echo $kpi_week_atend; ?></h3>
              </div>
            </div>
          </div>
          <div class="col-6 col-lg-3">
            <div class="card stat-card border-secondary shadow-sm">
              <div class="card-body text-center">
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary mx-auto mb-2">
                  <i class="mdi mdi-close-circle"></i>
                </div>
                <p class="text-muted mb-1">Canceladas</p>
                <h3 class="mb-0 fw-bold text-secondary"><?php echo $kpi_week_canc; ?></h3>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Próximas Citas -->
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-gradient text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h5 class="mb-0"><i class="mdi mdi-calendar-clock"></i> Próximas Citas</h5>
            <div>
              <a class="btn btn-sm btn-light" href="doctores.php"><i class="mdi mdi-doctor"></i> Gestionar Doctores</a>
              <a class="btn btn-sm btn-light" href="feriados.php"><i class="mdi mdi-calendar"></i> Feriados</a>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($nextRows)): ?>
              <div class="text-center py-5">
                <i class="mdi mdi-calendar-blank" style="font-size: 64px; color: #ccc;"></i>
                <p class="text-muted mt-3">No hay citas programadas próximamente</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th><i class="mdi mdi-calendar"></i> Fecha</th>
                      <th><i class="mdi mdi-clock"></i> Hora</th>
                      <th><i class="mdi mdi-information"></i> Estado</th>
                      <th><i class="mdi mdi-doctor"></i> Doctor</th>
                      <th><i class="mdi mdi-account"></i> Paciente</th>
                      <th><i class="mdi mdi-medical-bag"></i> Especialidad</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($nextRows as $r): ?>
                    <tr>
                      <td><strong><?php echo date('d/m/Y', strtotime($r['scheduled_date'])); ?></strong></td>
                      <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars(substr($r['scheduled_time'],0,5), ENT_QUOTES, 'UTF-8'); ?></span></td>
                      <td>
                        <span class="badge bg-<?php echo $r['status']==='reservada'?'warning':($r['status']==='confirmada'?'info':($r['status']==='atendida'?'success':($r['status']==='cancelada'?'secondary':'light'))); ?>">
                          <?php echo htmlspecialchars(ucfirst($r['status']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                      </td>
                      <td><?php echo htmlspecialchars($r['doctor'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($r['patient'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($r['specialty'], ENT_QUOTES, 'UTF-8'); ?></span></td>
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

  <footer class="text-center py-3 mt-4 border-top">
    <p class="text-muted mb-0">© <?php echo date('Y'); ?> Clínica Moya. Todos los derechos reservados.</p>
  </footer>

  <script src="../assets/libs/jquery/jquery.min.js"></script>
  <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
  <script src="../assets/libs/simplebar/simplebar.min.js"></script>
  <script src="../assets/libs/node-waves/waves.min.js"></script>
  <script src="../assets/libs/feather-icons/feather.min.js"></script>
  <script src="../assets/libs/pace-js/pace.min.js"></script>
</body>
</html>
