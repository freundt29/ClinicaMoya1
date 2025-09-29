<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';

session_boot();
// Solo pacientes
require_role(3);

$app = new AppointmentController();
$specialties = $app->getSpecialties();

$selectedSpecialty = isset($_GET['specialty']) ? (int)$_GET['specialty'] : 0;
$doctors = [];
if ($selectedSpecialty > 0) {
    $doctors = $app->getDoctorsBySpecialty($selectedSpecialty);
}

$success = flash_get('success');
$error = flash_get('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();
    $user = current_user();
    $patientId = (int)$user['id'];
    $specialtyId = (int)($_POST['specialty_id'] ?? 0);
    $doctorId    = (int)($_POST['doctor_id'] ?? 0);
    $date        = trim($_POST['scheduled_date'] ?? '');
    $time        = trim($_POST['scheduled_time'] ?? '');
    $reason      = trim($_POST['reason'] ?? '');

    if (!$specialtyId || !$doctorId || $date === '' || $time === '') {
        $error = 'Completa todos los campos obligatorios.';
    } else {
        try {
            $appointmentId = $app->createAppointment($patientId, $doctorId, $specialtyId, $date, $time, $reason !== '' ? $reason : null, $patientId);
            flash_set('success', 'Cita reservada con éxito. Nº ' . (int)$appointmentId);
            header('Location: reservar-cita.php?specialty=' . urlencode((string)$specialtyId));
            exit;
        } catch (Throwable $e) {
            flash_set('error', 'No se pudo reservar la cita: ' . $e->getMessage());
            header('Location: reservar-cita.php' . ($selectedSpecialty?('?specialty='.urlencode((string)$selectedSpecialty)) : ''));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar cita</title>
    <meta content="Clínica Moya" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="../assets/images/favicon.ico">

    <!-- preloader css -->
    <link rel="stylesheet" href="../assets/css/preloader.min.css" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="../assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="../assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <style>
        .text-clinic-blue{ color: #1034a6 !important; }
        .background-reserva{ color:#1034a6; }
    </style>
    <script>
    function onSpecialtyChange(sel){
        const sid = sel.value;
        const url = new URL(window.location.href);
        if (sid) { url.searchParams.set('specialty', sid); }
        else { url.searchParams.delete('specialty'); }
        window.location.href = url.toString();
    }
    </script>
    </head>
<body class="bg-white">

    <!-- Navbar simple -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
      <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="../index.html">
          <img src="../assets/images/logo-sm.svg" alt="" height="24" class="me-2"> <span>Clínica Moya</span>
        </a>
        <div class="d-flex align-items-center gap-2">
          <a class="btn btn-outline-primary btn-sm" href="mis-citas.php">Mis Citas</a>
          <a class="btn btn-outline-secondary btn-sm" href="mis-fichas.php">Mis Fichas</a>
          <span class="me-2 text-muted d-none d-sm-inline"><?php echo htmlspecialchars(current_user()['full_name'] ?? current_user()['username'], ENT_QUOTES, 'UTF-8'); ?></span>
          <a class="btn btn-outline-danger btn-sm" href="logout.php">Salir</a>
        </div>
      </div>
    </nav>

    <div class="container my-5">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Reservar Cita</h5>
            </div>
            <div class="card-body">
              <?php if ($success): ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>
              <?php if ($error): ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              <?php endif; ?>

              <div id="msgDomingo" class="alert alert-warning d-none" role="alert">
                Clínica Moya no trabaja los domingos. Por favor, elige otro día.
              </div>

              <form action="" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                  <label class="form-label">Paciente</label>
                  <input type="text" class="form-control" value="<?php echo htmlspecialchars(current_user()['full_name'] ?? current_user()['username'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>

                <div class="mb-3">
                  <label for="especialidad" class="form-label">Especialidad</label>
                  <select class="form-select" id="especialidad" name="specialty_id" onchange="onSpecialtyChange(this)" required>
                    <option value="">Seleccione una especialidad</option>
                    <?php foreach ($specialties as $sp): ?>
                      <option value="<?php echo (int)$sp['id']; ?>" <?php echo $selectedSpecialty == (int)$sp['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sp['name'], ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-3">
                  <label for="medico" class="form-label">Médico</label>
                  <select class="form-select" id="medico" name="doctor_id" required <?php echo $selectedSpecialty ? '' : 'disabled'; ?>>
                    <option value="">Seleccione un médico</option>
                    <?php foreach ($doctors as $doc): ?>
                      <option value="<?php echo (int)$doc['id']; ?>"><?php echo htmlspecialchars($doc['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="fecha" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="fecha" name="scheduled_date" required min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+2 months')); ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="hora" class="form-label">Hora</label>
                    <select class="form-select" id="hora" name="scheduled_time" required disabled>
                      <option value="">Seleccione hora</option>
                    </select>
                    <div id="msgNoSlots" class="form-text text-muted d-none">No hay horarios disponibles para este día.</div>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="motivo" class="form-label">Motivo (opcional)</label>
                  <input type="text" class="form-control" id="motivo" name="reason" maxlength="255" placeholder="Motivo de la consulta">
                </div>

                <div class="d-flex justify-content-between align-items-center gap-2">
                  <div class="d-flex gap-2">
                    <a href="mis-citas.php" class="btn btn-outline-primary">Mis Citas</a>
                    <a href="../index.html" class="btn btn-light">Cancelar</a>
                  </div>
                  <button type="submit" id="btnReservar" class="btn btn-success">Reservar</button>
                </div>
              </form>
            </div>
        </div>
      </div>
    </div>

    <!-- JS -->
    <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/libs/feather-icons/feather.min.js"></script>
    <script src="../assets/libs/pace-js/pace.min.js"></script>
    <script>
      (function(){
        const doctorSel = document.getElementById('medico');
        const dateInp   = document.getElementById('fecha');
        const timeSel   = document.getElementById('hora');
        const msgDom    = document.getElementById('msgDomingo');
        const btnRes    = document.getElementById('btnReservar');
        const msgNo     = document.getElementById('msgNoSlots');
        async function loadTimes(){
          const did = doctorSel.value;
          const dt  = dateInp.value;
          // Reset
          timeSel.innerHTML = '<option value="">Seleccione hora</option>';
          timeSel.disabled = true;
          msgNo && msgNo.classList.add('d-none');

          // Si no hay fecha, ocultar aviso y deshabilitar reservar
          if (!dt){
            msgDom && msgDom.classList.add('d-none');
            btnRes && btnRes.setAttribute('disabled', 'disabled');
            return;
          }

          // Validar domingo (0=domingo en Date JS) incluso si no hay doctor todavía
          const djs = new Date(dt + 'T00:00:00');
          const isSunday = djs.getDay() === 0;
          if (isSunday){
            msgDom && msgDom.classList.remove('d-none');
            btnRes && btnRes.setAttribute('disabled', 'disabled');
            return; // no cargues API
          } else {
            msgDom && msgDom.classList.add('d-none');
          }

          // Si no hay doctor aún, mantener botón deshabilitado
          if (!did){
            btnRes && btnRes.setAttribute('disabled', 'disabled');
            return;
          }

          try {
            const resp = await fetch('api/available-times.php?doctor_id=' + encodeURIComponent(did) + '&date=' + encodeURIComponent(dt), {credentials: 'same-origin'});
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();
            if (Array.isArray(data.slots)){
              for (const hhmm of data.slots){
                const opt = document.createElement('option');
                opt.value = hhmm + ':00';
                opt.textContent = hhmm;
                timeSel.appendChild(opt);
              }
              const empty = data.slots.length === 0;
              timeSel.disabled = empty;
              if (empty) {
                msgNo && msgNo.classList.remove('d-none');
                btnRes && btnRes.setAttribute('disabled', 'disabled');
              } else {
                msgNo && msgNo.classList.add('d-none');
                btnRes && btnRes.removeAttribute('disabled');
              }
            }
          } catch(e){
            console.error('Error cargando horarios', e);
          }
        }

        doctorSel && doctorSel.addEventListener('change', loadTimes);
        dateInp && dateInp.addEventListener('change', loadTimes);

        // Si ya hay doctor seleccionado (tras POST), intenta cargar horarios
        if (doctorSel && doctorSel.value && dateInp && dateInp.value){
          loadTimes();
        }
      })();
    </script>