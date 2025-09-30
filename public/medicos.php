<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';

session_boot();
require_role(3); // paciente

$user = current_user();
$app = new AppointmentController();

$specialties = $app->getSpecialties();
$selected = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;

$doctorsBySpec = [];
if ($selected > 0) {
  // Solo la especialidad seleccionada
  $doctorsBySpec[$selected] = $app->getDoctorsBySpecialty($selected);
} else {
  // Todas las especialidades
  foreach ($specialties as $s) {
    $doctorsBySpec[(int)$s['id']] = $app->getDoctorsBySpecialty((int)$s['id']);
  }
}

// Utilidad: normalizar nombre (minúsculas, sin acentos, sin espacios)
function norm_name(string $s): string {
  $s = mb_strtolower($s, 'UTF-8');
  $replacements = [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'
  ];
  $s = strtr($s, $replacements);
  $s = preg_replace('/\s+/', '', $s);
  return $s;
}

function slug_hyphen(string $s): string {
  $s = mb_strtolower($s, 'UTF-8');
  $replacements = [
    'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'
  ];
  $s = strtr($s, $replacements);
  $s = preg_replace('/\s+/', '-', $s);
  return $s;
}

// Mapa por nombre normalizado -> archivo PHP de especialidad
$specFileMap = [
  'anestesiologia'   => 'anestesiologia.php',
  'cardiologia'      => 'cardiologia.php',
  'dermatologia'     => 'dermatologia.php',
  'ginecologia'      => 'ginecologia.php',
  'medicinainterna'  => 'medicina-interna.php',
  'oftalmologia'     => 'oftalmologia.php',
  'psiquiatria'      => 'psiquiatria.php',
];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Médicos y Especialidades | Clínica Moya</title>
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
        <a href="mis-citas.php" class="btn btn-outline-secondary btn-sm">Mis Citas</a>
        <a href="mis-fichas.php" class="btn btn-outline-secondary btn-sm">Mis Fichas</a>
        <span class="text-muted d-none d-sm-inline"><?php echo htmlspecialchars($user['full_name'] ?? $user['username'], ENT_QUOTES, 'UTF-8'); ?></span>
        <a class="btn btn-outline-danger btn-sm" href="logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row">
      <div class="col-12 col-lg-3">
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white"><strong>Especialidades</strong></div>
          <div class="list-group list-group-flush">
            <a class="list-group-item list-group-item-action <?php echo $selected===0?'active':''; ?>" href="medicos.php">Todas</a>
            <?php foreach ($specialties as $s): ?>
              <a class="list-group-item list-group-item-action <?php echo $selected===(int)$s['id']?'active':''; ?>" href="medicos.php?sid=<?php echo (int)$s['id']; ?>">
                <?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-9">
        <?php if ($selected > 0): ?>
          <?php
            // Obtener nombre y archivo de la especialidad seleccionada
            $selName = '';
            foreach ($specialties as $s) { if ((int)$s['id'] === $selected) { $selName = $s['name']; break; } }
            $key = norm_name($selName);
            $specFile = $specFileMap[$key] ?? null;
            // Fallbacks si no hay mapeo exacto
            if (!$specFile) {
              $candidate1 = norm_name($selName) . '.php';           // ej: medicinainterna.php
              $candidate2 = slug_hyphen($selName) . '.php';         // ej: medicina-interna.php
              $baseDir = dirname(__DIR__) . '/especialidades/';     // ruta server-side
              if (is_file($baseDir . $candidate1)) { $specFile = $candidate1; }
              elseif (is_file($baseDir . $candidate2)) { $specFile = $candidate2; }
            }
          ?>
          <?php if ($specFile): ?>
            <div class="mb-3 d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Especialidad: <?php echo htmlspecialchars($selName, ENT_QUOTES, 'UTF-8'); ?></h5>
              <a class="btn btn-sm btn-outline-primary" href="../especialidades/<?php echo rawurlencode($specFile); ?>" target="_blank" rel="noopener">Abrir en nueva pestaña</a>
            </div>
            <div class="border rounded overflow-hidden" style="height: 900px;">
              <iframe src="../especialidades/<?php echo rawurlencode($specFile); ?>?sid=<?php echo (int)$selected; ?>" title="<?php echo htmlspecialchars($selName, ENT_QUOTES, 'UTF-8'); ?>" style="border:0; width:100%; height:100%;"></iframe>
            </div>
          <?php else: ?>
            <div class="alert alert-info">No se encontró la página de la especialidad seleccionada.</div>
          <?php endif; ?>
        <?php else: ?>
          <?php if (empty($doctorsBySpec)): ?>
            <div class="alert alert-info">No hay especialidades registradas.</div>
          <?php else: ?>
            <?php foreach ($doctorsBySpec as $specId => $docs): ?>
              <?php
                $specName = '';
                foreach ($specialties as $s) { if ((int)$s['id']===$specId) { $specName = $s['name']; break; } }
              ?>
              <div class="card shadow-sm mb-4">
                <div class="card-header bg-light"><strong><?php echo htmlspecialchars($specName, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="card-body">
                  <?php if (!$docs): ?>
                    <div class="text-muted">No hay médicos activos en esta especialidad.</div>
                  <?php else: ?>
                    <div class="row g-3">
                      <?php foreach ($docs as $d): ?>
                        <div class="col-12 col-md-6">
                          <div class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="fw-bold mb-1"><?php echo htmlspecialchars($d['full_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-muted small mb-3">Especialidad: <?php echo htmlspecialchars($specName, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="mt-auto">
                              <a class="btn btn-sm btn-primary" href="reservar-cita.php?doctor=<?php echo (int)$d['id']; ?>&sid=<?php echo (int)$specId; ?>">Reservar</a>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endif; ?>
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
