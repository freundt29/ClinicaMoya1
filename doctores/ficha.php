<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/MedicalRecordController.php';

session_boot();
require_role(2); // doctor

$user = current_user();
$mr = new MedicalRecordController();

$success = flash_get('success');
$error = flash_get('error');

// Pacientes con los que este doctor tiene citas
$patients = $mr->getDoctorPatients((int)$user['id']);
$prefPatient = isset($_GET['patient']) ? (int)$_GET['patient'] : 0;

// Historial de fichas del doctor
$records = $mr->getDoctorRecords((int)$user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    verify_csrf_or_die();
    $patientId = (int)($_POST['patient_id'] ?? 0);
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $treatment = trim($_POST['treatment'] ?? '');
    if ($patientId <= 0) { throw new InvalidArgumentException('Selecciona un paciente'); }
    $recId = $mr->createRecord((int)$user['id'], $patientId, $diagnosis, $treatment, null);
    flash_set('success', 'Ficha enviada al paciente (ID ' . (int)$recId . ').');
    header('Location: ficha.php');
    exit;
  } catch (Throwable $t) {
    flash_set('error', $t->getMessage());
    header('Location: ficha.php');
    exit;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Enviar Ficha | Clínica Moya</title>
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
        <a class="btn btn-outline-secondary btn-sm" href="citas.php">Mis Citas</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Enviar diagnóstico y tratamiento</strong></div>
          <div class="card-body">
            <?php if ($success): ?>
              <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <form method="post">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <div class="mb-3">
                <label class="form-label">Paciente</label>
                <select name="patient_id" class="form-select" required>
                  <option value="">Seleccione paciente</option>
                  <?php foreach ($patients as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" <?php echo ($prefPatient && $prefPatient==(int)$p['id'])?'selected':''; ?>>
                      <?php echo htmlspecialchars($p['full_name'] ?? $p['username'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Solo aparecen pacientes con los que tiene citas registradas.</div>
              </div>
              <div class="mb-3">
                <label class="form-label">Diagnóstico</label>
                <textarea name="diagnosis" class="form-control" rows="4" maxlength="2000" required></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Tratamiento</label>
                <textarea name="treatment" class="form-control" rows="4" maxlength="2000" required></textarea>
              </div>
              <div class="text-end">
                <button class="btn btn-primary">Enviar ficha</button>
              </div>
            </form>
            <hr class="my-4">
            <h5 class="mb-3">Historial de fichas enviadas</h5>
            <?php if (!$records): ?>
              <div class="alert alert-info">Aún no has enviado fichas clínicas.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Paciente</th>
                      <th>Diagnóstico</th>
                      <th>Tratamiento</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($r['patient_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td style="white-space:pre-wrap; max-width:360px;">&ZeroWidthSpace;<?php echo nl2br(htmlspecialchars($r['diagnosis'], ENT_QUOTES, 'UTF-8')); ?></td>
                      <td style="white-space:pre-wrap; max-width:360px;">&ZeroWidthSpace;<?php echo nl2br(htmlspecialchars($r['treatment'], ENT_QUOTES, 'UTF-8')); ?></td>
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
