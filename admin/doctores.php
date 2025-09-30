<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/UserController.php';
require_once __DIR__ . '/../backend/config/db.php';
require_once __DIR__ . '/../backend/controllers/AppointmentController.php';

session_boot();
require_role(1); // solo admin

$users = new UserController();
$app = new AppointmentController();
$success = flash_get('success');
$errors = [];
$generalFlash = flash_get('error');

// Cargar especialidades para el formulario de alta
$specialties = $app->getSpecialties();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_or_die();
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $full = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $yearsExp = isset($_POST['years_experience']) ? (int)$_POST['years_experience'] : 0;
            $bio = trim($_POST['bio'] ?? '');
            $specs = $_POST['specialties'] ?? [];
            if ($full === '') { $errors['full_name'] = 'Ingresa el nombre completo'; }
            if ($username === '') { $errors['username'] = 'Ingresa el usuario'; }
            if ($email === '') { $errors['email'] = 'Ingresa el email'; }
            if ($password === '') { $errors['password'] = 'Ingresa la contraseña'; }
            if ($password !== '' && strlen($password) < 8) { $errors['password'] = 'La contraseña debe tener al menos 8 caracteres'; }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Email inválido'; }
            if ($email !== '' && !preg_match('/^[^@\s]+@[^@\s]+\.com$/i', $email)) { $errors['email'] = 'El email debe terminar en .com'; }
            if ($yearsExp < 0 || $yearsExp > 50) { $errors['years_experience'] = 'Los años de experiencia deben estar entre 0 y 50'; }
            if ($bio === '') { $errors['bio'] = 'Ingresa una descripción profesional'; }
            if ($bio !== '' && strlen($bio) > 500) { $errors['bio'] = 'La descripción no puede exceder 500 caracteres'; }

            // Validación de duplicados (username/email)
            $pdo = db();
            if (empty($errors)) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
                $stmt->execute([':u' => $username]);
                if ((int)$stmt->fetchColumn() > 0) { $errors['username'] = 'El usuario ya existe'; }

                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :e');
                $stmt->execute([':e' => $email]);
                if ((int)$stmt->fetchColumn() > 0) { $errors['email'] = 'El email ya está registrado'; }
            }

            // Disponibilidad (schedule)
            $ranges = [];
            $schedule = $_POST['schedule'] ?? [];
            for ($w=1; $w<=6; $w++) {
                $row = $schedule[$w] ?? [];
                $active = !empty($row['active']);
                $start = trim($row['start'] ?? '');
                $end   = trim($row['end'] ?? '');
                if ($active) {
                    if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
                        $errors["schedule_$w"] = 'Use formato HH:MM';
                    } else {
                        list($sh, $sm) = array_map('intval', explode(':', $start));
                        list($eh, $em) = array_map('intval', explode(':', $end));
                        if ($sm % 20 !== 0 || $em % 20 !== 0) {
                            $errors["schedule_$w"] = 'Minutos válidos: 00, 20 o 40';
                        } elseif (sprintf('%02d:%02d', $sh, $sm) >= sprintf('%02d:%02d', $eh, $em)) {
                            $errors["schedule_$w"] = 'Inicio debe ser menor que fin';
                        }
                    }
                }
                $ranges[] = [
                    'weekday' => $w,
                    'start'   => $start,
                    'end'     => $end,
                    'active'  => $active,
                ];
            }

            if (empty($errors)) {
                $uid = $users->createDoctor($full, $username, $email, $password, null, $specs, $yearsExp, $bio);
                if (!empty($ranges)) { $users->setDoctorAvailability($uid, $ranges); }
                flash_set('success', 'Doctor creado (ID ' . (int)$uid . ')');
                header('Location: doctores.php');
                exit;
            }
        }
        if (isset($_POST['action']) && $_POST['action'] === 'toggle' && isset($_POST['user_id'])) {
            $uid = (int)$_POST['user_id'];
            $active = (int)($_POST['active'] ?? 1) === 1;
            $users->toggleUserActive($uid, $active);
            flash_set('success', 'Estado actualizado');
            header('Location: doctores.php');
            exit;
        }
    } catch (Throwable $t) {
        if ($t instanceof PDOException && $t->getCode() === '23000') {
            $msg = $t->getMessage();
            if (stripos($msg, 'username') !== false) { $errors['username'] = 'El usuario ya existe'; }
            if (stripos($msg, 'email') !== false) { $errors['email'] = 'El email ya está registrado'; }
            if (empty($errors['username']) && empty($errors['email'])) {
                $errors['general'] = 'Violación de restricción de unicidad';
            }
        } else {
            $errors['general'] = $t->getMessage();
        }
    }
}

$list = $users->listDoctors();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Gestión de Doctores | Clínica Moya</title>
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
      <img src="../assets/images/logoMoya.png" alt="" height="40" class="me-2"> 
      </a>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="dashboard-admin.php">Dashboard</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row">
      <div class="col-12 col-xl-5 order-xl-2 mb-4">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Nuevo Doctor</strong></div>
          <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($generalFlash): ?><div class="alert alert-danger"><?php echo htmlspecialchars($generalFlash, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if (!empty($errors['general'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="action" value="create">
              <div class="mb-3">
                <label class="form-label">Nombre completo</label>
                <input type="text" name="full_name" class="form-control <?php echo isset($errors['full_name'])?'is-invalid':''; ?>" value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['full_name'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
              </div>
              <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="username" class="form-control <?php echo isset($errors['username'])?'is-invalid':''; ?>" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                <?php if (isset($errors['username'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control <?php echo isset($errors['email'])?'is-invalid':''; ?>" required pattern="^[^@\s]+@[^@\s]+\.com$" title="Debe ser un correo válido que termine en .com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
              </div>
              <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="text" name="password" class="form-control <?php echo isset($errors['password'])?'is-invalid':''; ?>" required minlength="8" title="Mínimo 8 caracteres" value="<?php echo htmlspecialchars($_POST['password'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Mínimo 8 caracteres. Compártela al doctor para su primer acceso.</div>
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Años de experiencia</label>
                <input type="number" name="years_experience" class="form-control <?php echo isset($errors['years_experience'])?'is-invalid':''; ?>" min="0" max="50" value="<?php echo htmlspecialchars($_POST['years_experience'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-text">Años de experiencia profesional del doctor (0-50 años)</div>
                <?php if (isset($errors['years_experience'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['years_experience'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Descripción profesional</label>
                <textarea name="bio" class="form-control <?php echo isset($errors['bio'])?'is-invalid':''; ?>" rows="3" required maxlength="500" placeholder="Ej: Especialista en cirugía refractiva y córnea. Experto en procedimientos LASIK, PRK y trasplante de córnea."><?php echo htmlspecialchars($_POST['bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text">Breve descripción del área de especialización del doctor (máximo 500 caracteres)</div>
                <?php if (isset($errors['bio'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['bio'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
              </div>
              
              <div class="mb-3">
                <label class="form-label d-block">Especialidades</label>
                <div class="row row-cols-1 row-cols-md-2 g-2">
                  <?php foreach ($specialties as $s): ?>
                    <div class="col">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sp<?php echo (int)$s['id']; ?>" name="specialties[]" value="<?php echo (int)$s['id']; ?>">
                        <label class="form-check-label" for="sp<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8'); ?></label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="form-text">Marca una o varias especialidades.</div>
              </div>
              <hr>
              <div class="mb-2">
                <strong>Disponibilidad semanal</strong>
                <div class="form-text">La clínica no trabaja los domingos. Los horarios se usan en slots de 20 minutos y cada cita bloquea 1 hora.</div>
              </div>
              <?php
                $days = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado'];
                foreach ($days as $w => $label): ?>
                <div class="row align-items-center g-2 mb-2">
                  <div class="col-5">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="d<?php echo $w; ?>" name="schedule[<?php echo $w; ?>][active]" checked>
                      <label class="form-check-label" for="d<?php echo $w; ?>"><?php echo $label; ?></label>
                    </div>
                  </div>
                  <div class="col-3">
                    <input type="time" class="form-control <?php echo isset($errors['schedule_'.$w])?'is-invalid':''; ?>" name="schedule[<?php echo $w; ?>][start]" value="<?php echo htmlspecialchars($_POST['schedule'][$w]['start'] ?? '09:00', ENT_QUOTES, 'UTF-8'); ?>" step="1200" title="Minutos en 00, 20 o 40">
                  </div>
                  <div class="col-3">
                    <input type="time" class="form-control <?php echo isset($errors['schedule_'.$w])?'is-invalid':''; ?>" name="schedule[<?php echo $w; ?>][end]" value="<?php echo htmlspecialchars($_POST['schedule'][$w]['end'] ?? '18:00', ENT_QUOTES, 'UTF-8'); ?>" step="1200" title="Minutos en 00, 20 o 40">
                  </div>
                  <?php if (isset($errors['schedule_'.$w])): ?><div class="col-12"><div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['schedule_'.$w], ENT_QUOTES, 'UTF-8'); ?></div></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
              <div class="text-end">
                <button class="btn btn-primary">Crear Doctor</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-12 col-xl-7 order-xl-1">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white"><strong>Doctores</strong></div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    
                    <th>Especialidades</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($list as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    
                    <td><?php echo htmlspecialchars($row['specialties'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                      <span class="badge bg-<?php echo ((int)$row['is_active']===1)?'success':'secondary'; ?>"><?php echo ((int)$row['is_active']===1)?'Activo':'Inactivo'; ?></span>
                    </td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary me-1" href="disponibilidad.php?doctor=<?php echo (int)$row['id']; ?>">Editar disp.</a>
                      <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar estado de este usuario?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                        <input type="hidden" name="active" value="<?php echo ((int)$row['is_active']===1)?'0':'1'; ?>">
                        <button class="btn btn-sm btn-outline-<?php echo ((int)$row['is_active']===1)?'secondary':'success'; ?>">
                          <?php echo ((int)$row['is_active']===1)?'Desactivar':'Activar'; ?>
                        </button>
                      </form>
                    </td>
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
