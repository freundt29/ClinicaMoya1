<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/MedicalRecordController.php';

session_boot();
require_role(3); // paciente

$user = current_user();
$mr = new MedicalRecordController();

$records = $mr->getPatientRecords((int)$user['id']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Mis Fichas | Clínica Moya</title>
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
      <a class="navbar-brand d-flex align-items-center" href="paciente/dashboard.php">
        <img src="../assets/images/logoMoya.png" alt="" height="34" class="me-2"> <span>Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <a href="reservar-cita.php" class="btn btn-outline-primary btn-sm">Reservar cita</a>
        <a href="mis-citas.php" class="btn btn-outline-secondary btn-sm">Mis citas</a>
        <span class="text-muted d-none d-sm-inline"><?php echo htmlspecialchars($user['full_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="btn btn-outline-danger btn-sm" href="logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-xl-10">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mis Fichas Clínicas</h5>
          </div>
          <div class="card-body">
            <?php if (!$records): ?>
              <div class="alert alert-info">Aún no tienes fichas clínicas registradas.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Doctor</th>
                      <th>Diagnóstico</th>
                      <th>Tratamiento</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($r['doctor_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td style="white-space:pre-wrap; max-width:400px;"><?php echo nl2br(htmlspecialchars($r['diagnosis'], ENT_QUOTES, 'UTF-8')); ?></td>
                      <td style="white-space:pre-wrap; max-width:400px;"><?php echo nl2br(htmlspecialchars($r['treatment'], ENT_QUOTES, 'UTF-8')); ?></td>
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
