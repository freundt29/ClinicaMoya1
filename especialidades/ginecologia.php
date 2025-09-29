<?php
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';
$app = new AppointmentController();
function _norm_gyn(string $s): string {
  $s = mb_strtolower($s, 'UTF-8');
  $rep = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u'];
  return preg_replace('/\s+/', '', strtr($s, $rep));
}
$specName = 'Ginecología'; // en BD sin tilde
$target = _norm_gyn($specName);
$specId = 0;
foreach ($app->getSpecialties() as $s) {
  if (_norm_gyn($s['name']) === $target) { $specId = (int)$s['id']; break; }
}
$doctors = $specId ? $app->getDoctorsBySpecialty($specId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ginecología</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <link rel="shortcut icon" href="../assets/images/favicon.ico">

    <!-- plugin css -->
    <link href="../assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />

    <!-- preloader css -->
    <link rel="stylesheet" href="../assets/css/preloader.min.css" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="../assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="../assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <style>
        .texto-color{
            color: #1034a6 !important;
        }
    </style>
<div class="main-content">
    <div class="container-fluid">
        <div class="row justify-content-center mt-4">
            <div class="col-lg-10 col-xl-8">
                
                <ul class="nav nav-pills nav-justified mb-4" id="pills-tab" role="tablist">
                    <li class="mx-2 nav-item" role="presentation">
                        <a class="nav-link active fw-bold" id="pills-intro-tab" data-toggle="pill" href="#pills-intro" role="tab" aria-controls="pills-intro" aria-selected="true" style="background-color: #1034a6; color: white;">
                            ¿Qué es?
                        </a>
                    </li>
                    <li class="mx-2 nav-item" role="presentation">
                        <a class="nav-link fw-bold" id="pills-servicios-tab" data-toggle="pill" href="#pills-servicios" role="tab" aria-controls="pills-servicios" aria-selected="false" style="background-color: #1034a6; color: white;"> 
                            Tratamientos
                        </a>
                    </li>
                    <li class="mx-2 nav-item" role="presentation">
                        <a class="nav-link fw-bold" id="pills-medicos-tab" data-toggle="pill" href="#pills-medicos" role="tab" aria-controls="pills-medicos" aria-selected="false" style="background-color: #1034a6; color: white;">
                            Doctores
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">
                <h5 class="card-title texto-color fw-bold">Cuidado Integral de la Salud Femenina</h5>
                    <p class="card-text text-muted">
                        La Ginecología es la especialidad médico-quirúrgica dedicada al estudio, diagnóstico, prevención y tratamiento de las enfermedades del aparato reproductor femenino. Incluye el seguimiento de la salud menstrual, sexual, hormonal, reproductiva y mamaria.
                    </p>
                    <h5 class="card-title texto-color fw-bold mt-4">Motivos para agendar una cita</h5>
                    <p class="card-text text-muted">
                        Agenda una consulta si presentas irregularidades menstruales, dolor pélvico, infecciones vaginales recurrentes, síntomas de menopausia, planificación familiar, o si necesitas chequeos preventivos como el Papanicolaou o ultrasonido ginecológico. La detección temprana es clave para tu bienestar.
                    </p>


                    <div class="tab-pane fade" id="pills-servicios" role="tabpanel" aria-labelledby="pills-servicios-tab">
                        <div class="card shadow-lg p-4">
                        <h5 class="mb-3 texto-color fw-bold">Ginecología Médica y Quirúrgica</h5>
                            <p>Diagnóstico y manejo de condiciones que afectan la salud reproductiva y hormonal de la mujer.</p>
                            <ul class="card-text text-muted">
                                <li>Control de Ciclo Menstrual: Tratamiento de irregularidades, dolor y síndrome premenstrual.</li>
                                <li>Infecciones Ginecológicas: Manejo de vaginitis, ITS y cistitis.</li>
                                <li>Salud Mamaria: Evaluación de nódulos, dolor o secreciones.</li>
                                <li>Cirugía Ginecológica: Miomectomías, laparoscopías, histerectomías y más.</li>
                            </ul>

                            <h5 class="mb-3 texto-color fw-bold">Ginecología Preventiva y Estética</h5>
                            <p>Servicios orientados al bienestar íntimo, la prevención y el rejuvenecimiento genital femenino.</p>
                            <ul class="card-text text-muted list-unstyled">
                                <li>Chequeos Preventivos: Papanicolaou, colposcopía y estudios hormonales.</li>
                                <li>Rejuvenecimiento Vaginal: Láser íntimo, radiofrecuencia y tratamientos estéticos.</li>
                                <li>Planificación Familiar: Métodos anticonceptivos, DIU, implantes y asesoría reproductiva.</li>
                            </ul>

                        </div>
                    </div>

                    <div class="tab-pane fade" id="pills-medicos" role="tabpanel" aria-labelledby="pills-medicos-tab">
                        <h5 class="mb-4 text-clinic-blue fw-bold">Conoce a Nuestro Equipo de Dermatólogos Certificados</h5>
                        <?php if (!$specId): ?>
                          <div class="alert alert-info">Especialidad no encontrada en el sistema.</div>
                        <?php else: ?>
                          <?php if (!$doctors): ?>
                            <div class="alert alert-warning">No hay médicos activos registrados para esta especialidad.</div>
                          <?php else: ?>
                            <?php foreach ($doctors as $d): ?>
                              <div class="card shadow-lg mb-4 p-3 border-start border-5 border-primary">
                                <div class="row align-items-center">
                                  <div class="col-md-2 text-center mb-3 mb-md-0">
                                    <div class="placeholder-img bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 2.5rem; font-weight: bold;">
                                      <?php echo htmlspecialchars(mb_strtoupper(mb_substr($d['full_name'],0,1), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                  </div>
                                  <div class="col-md-6 mb-3 mb-md-0">
                                    <h4 class="fw-bold text-clinic-blue mb-1"><?php echo htmlspecialchars($d['full_name'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="text-info fw-bold mb-1">Especialista en <?php echo htmlspecialchars($specName, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p class="text-muted small mb-0">Agenda una cita con el especialista.</p>
                                  </div>
                                  <div class="col-md-4 text-md-end">
                                    <a class="btn btn-primary" href="../public/reservar-cita.php?doctor=<?php echo (int)$d['id']; ?>&sid=<?php echo (int)$specId; ?>">Reservar</a>
                                  </div>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        <?php endif; ?>
                    </div>
<!-- JAVASCRIPT -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="../assets/libs/simplebar/simplebar.min.js"></script>
    <script src="../assets/libs/node-waves/waves.min.js"></script>
    <script src="../assets/libs/feather-icons/feather.min.js"></script>
    <script src="../assets/js/app.js"></script> 