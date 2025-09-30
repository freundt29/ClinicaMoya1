<?php
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';
$app = new AppointmentController();
$specName = 'Psiquiatría';
$specId = 0;
foreach ($app->getSpecialties() as $s) {
  if (strcasecmp($s['name'], $specName) === 0) { $specId = (int)$s['id']; break; }
}
$doctors = $specId ? $app->getDoctorsBySpecialty($specId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Psiquiatría</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <link href="../assets/images/favicon.ico" rel="shortcut icon">

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
</head>
<body>
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
                             Servicios
                        </a>
                    </li>
                    <li class="mx-2 nav-item" role="presentation">
                        <a class="nav-link fw-bold text-clinic-blue" id="pills-medicos-tab" data-toggle="pill" href="#pills-medicos" role="tab" aria-controls="pills-medicos" aria-selected="false" style="background-color: #1034a6; color: white;">
                             Psiquiatras
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContent">

                    <div class="tab-pane fade show active" id="pills-intro" role="tabpanel" aria-labelledby="pills-intro-tab">
                        <div class="card shadow-lg p-4">
                            <h5 class="card-title texto-color fw-bold">Mente, Emoción y Bienestar Integral</h5>
                            <p class="card-text text-muted">
                                  La Psiquiatría es la rama de la medicina dedicada a la prevención, diagnóstico y tratamiento de los trastornos mentales que afectan el pensamiento, las emociones, el comportamiento y el funcionamiento diario. 
                                  Nuestros psiquiatras son médicos especialistas que pueden integrar la terapia psicológica con el tratamiento farmacológico (medicamentos) cuando es necesario.
                            </p>
                            <h5 class="card-title texto-color fw-bold mt-4">Señales para Buscar Ayuda Profesional</h5>
                            <p class="card-text text-muted ">
                                 Es recomendable buscar apoyo si experimenta cambios de humor severos o repentinos, ansiedad o preocupación excesiva y persistente, dificultad para dormir (insomnio), ataques de pánico o si las 
                                 emociones negativas están interfiriendo con su Trabajo, relaciones o calidad de vida. No está solo.
                            </p>
                            </div>
                    </div>

                    <div class="tab-pane fade" id="pills-servicios" role="tabpanel" aria-labelledby="pills-servicios-tab">
                        <div class="card shadow-lg p-4">
                             <h5 class="mb-3 texto-color fw-bold">Diagnóstico y Manejo Clínico de Trastornos</h5>
                             <p>Nuestros especialistas ofrecen evaluación y tratamiento para las siguientes condiciones:</p>
                             <ul class="card-text text-muted">
                                <li>Trastornos del Estado de Ánimo: Depresión mayor y Trastorno Bipolar.</li>
                                <li>Trastornos de Ansiedad: Pánico, Ansiedad Generalizada, Fobias y TOC.</li>
                                <li>Trastornos del Sueño: Insomnio crónico y Narcolepsia relacionados con causas psiquiátricas.</li>
                            </ul>
                             <h5 class="mb-3 texto-color fw-bold">Subespecialidades y Terapias</h5>
                             <p>Ofrecemos atención dirigida a poblaciones específicas y terapias avanzadas:</p>
                             <ul class="card-text text-muted list-unstyled">
                                <li>Psiquiatría Geriátrica: Evaluación y manejo de trastornos neurocognitivos en adultos mayores.</li>
                                <li>Psiquiatría de Enlace: Atención a pacientes con enfermedades médicas y trastornos mentales concurrentes.</li>
                                <li>Terapia Farmacológica: Prescripción, ajuste y seguimiento de medicamentos psiquiátricos.</li>
                             </ul>
                            </div>
                    </div>

                    <div class="tab-pane fade" id="pills-medicos" role="tabpanel" aria-labelledby="pills-medicos-tab">
                        <h5 class="mb-4 text-clinic-blue fw-bold">Conoce a Nuestros Psiquiatras Especializados</h5>
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
                                    <?php if (!empty($d['bio'])): ?>
                                      <p class="text-muted small mb-1"><?php echo htmlspecialchars($d['bio'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($d['years_experience']) && $d['years_experience'] > 0): ?>
                                      <p class="text-muted small mb-0"><i class="mdi mdi-briefcase-outline me-1"></i><?php echo (int)$d['years_experience']; ?>+ años de experiencia</p>
                                    <?php endif; ?>
                                  </div>
                                  <div class="col-md-4 text-md-end">
                                    <a class="btn btn-primary" href="../public/reservar-cita.php?doctor=<?php echo (int)$d['id']; ?>&sid=<?php echo (int)$specId; ?>">Reservar</a>
                                  </div>
                                </div>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="../assets/libs/metismenu/metisMenu.min.js"></script>
<script src="../assets/libs/simplebar/simplebar.min.js"></script>
<script src="../assets/libs/node-waves/waves.min.js"></script>
<script src="../assets/libs/feather-icons/feather.min.js"></script>
<script src="../assets/js/app.js"></script>