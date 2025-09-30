<?php
require_once __DIR__ . '/../../backend/helpers/session.php';

session_boot();
require_role(3); // paciente

$user = current_user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Panel del Paciente | Clínica Moya</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="../../assets/images/favicon.ico">
  <link rel="stylesheet" href="../../assets/css/preloader.min.css" type="text/css" />
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
  <link href="../../assets/css/app.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center fw-bold" href="#">
        <img src="../assets/images/logoMoya.png" alt="" height="40" class="me-2"> 
        <span class="text-primary">Clínica Moya</span>
      </a>
      <div class="d-flex align-items-center gap-2">
        <a href="../reservar-cita.php" class="btn btn-outline-primary btn-sm">Reservar cita</a>
        <a href="../mis-citas.php" class="btn btn-outline-secondary btn-sm">Mis citas</a>
        <span class="text-muted d-none d-sm-inline"><?php echo htmlspecialchars($user['full_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="btn btn-outline-danger btn-sm" href="../logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row g-4 justify-content-center">
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Reservar Cita</h5>
            <p class="card-text text-muted">Agenda una nueva cita eligiendo especialidad, médico, fecha y hora disponible.</p>
            <div class="mt-auto"><a href="../reservar-cita.php" class="btn btn-primary">Ir a reservar</a></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Mis Citas</h5>
            <p class="card-text text-muted">Consulta el historial y estado de tus citas. Cancela futuras si lo necesitas.</p>
            <div class="mt-auto"><a href="../mis-citas.php" class="btn btn-secondary">Ver mis citas</a></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Médicos</h5>
            <p class="card-text text-muted">Explora especialidades y encuentra médicos disponibles.</p>
            <div class="mt-auto"><a href="../medicos.php" class="btn btn-outline-primary">Ver médicos</a></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title">Mis Fichas</h5>
            <p class="card-text text-muted">Revisa diagnósticos y tratamientos que te envió tu doctor.</p>
            <div class="mt-auto"><a href="../mis-fichas.php" class="btn btn-outline-secondary">Ver mis fichas</a></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/libs/jquery/jquery.min.js"></script>
  <script src="../../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../../assets/libs/metismenu/metisMenu.min.js"></script>
  <script src="../../assets/libs/simplebar/simplebar.min.js"></script>
  <script src="../../assets/libs/node-waves/waves.min.js"></script>
  <script src="../../assets/libs/feather-icons/feather.min.js"></script>
  <script src="../../assets/libs/pace-js/pace.min.js"></script>
</body>
</html>
