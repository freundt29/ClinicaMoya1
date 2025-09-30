<?php
/**
 * Registro público de PACIENTES
 * - Los doctores solo pueden ser creados por el administrador desde el panel admin
 * - Solo existe un administrador en el sistema
 * - Este formulario solo crea cuentas de pacientes (role_id = 3)
 */
require_once __DIR__ . '/../backend/controllers/UserController.php';
require_once __DIR__ . '/../backend/helpers/session.php';

session_boot();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_or_die();
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $birthDate = trim($_POST['birth_date'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if ($fullName === '' || $username === '' || $email === '' || $birthDate === '' || $password === '' || $confirm === '') {
            throw new InvalidArgumentException('Completa todos los campos.');
        }
        if ($password !== $confirm) {
            throw new InvalidArgumentException('Las contraseñas no coinciden.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email inválido.');
        }

        $uc = new UserController();
        // 3 = paciente
        $uc->createUser($fullName, $username, $email, $password, 3, $birthDate);

        flash_set('success', 'Registro completado. Ahora puedes iniciar sesión.');
        header('Location: /proyecto1moya/public/login.php');
        exit;
    } catch (Throwable $t) {
        $error = $t->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">

    <head>
        <meta charset="utf-8" />
        <title>Registro | Clínica Moya</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta content="Clínica Moya" name="author" />
        <link rel="shortcut icon" href="../assets/images/favicon.ico">
        <link rel="stylesheet" href="../assets/css/preloader.min.css" type="text/css" />
        <link href="../assets/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <link href="../assets/css/icons.min.css" rel="stylesheet" type="text/css" />
        <link href="../assets/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    </head>

    <body>
        <div class="auth-page">
            <div class="container-fluid p-0">
                <div class="row g-0">
                    <div class="col-xxl-3 col-lg-4 col-md-5">
                        <div class="auth-full-page-content d-flex p-sm-5 p-4">
                            <div class="w-100">
                                <div class="d-flex flex-column h-100">
                                    <div class="mb-4 mb-md-5 text-center">
                                        <a href="../index.html" class="d-block auth-logo">
                                            <img src="../assets/images/logo-sm.svg" alt="" height="28"> <span class="logo-txt">Clínica Moya</span>
                                        </a>
                                    </div>
                                    <div class="auth-content my-auto">
                                        <div class="text-center">
                                            <h5 class="mb-0">Crear cuenta</h5>
                                            <p class="text-muted mt-2">Regístrate para reservar tus citas.</p>
                                        </div>
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger" role="alert">
                                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <form class="mt-4 pt-2" method="post" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                                            <div class="mb-3">
                                                <label class="form-label">Nombre completo</label>
                                                <input type="text" name="full_name" class="form-control" 
                                                       placeholder="Ingresa tu nombre completo" 
                                                       minlength="3" maxlength="100" 
                                                       pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+" 
                                                       title="Solo letras y espacios, máximo 100 caracteres"
                                                       required>
                                                <small class="text-muted">Máximo 100 caracteres</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Nombre de usuario</label>
                                                <input type="text" name="username" class="form-control" 
                                                       placeholder="Elige un usuario" 
                                                       minlength="4" maxlength="50" 
                                                       pattern="[a-zA-Z0-9_]+" 
                                                       title="Solo letras, números y guión bajo. Entre 4 y 50 caracteres"
                                                       required>
                                                <small class="text-muted">4-50 caracteres (letras, números, _)</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" 
                                                       placeholder="ejemplo@correo.com" 
                                                       maxlength="120"
                                                       pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
                                                       title="Ingresa un email válido (debe contener @ y dominio)"
                                                       required>
                                                <small class="text-muted">Debe contener @ y dominio válido</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Fecha de nacimiento</label>
                                                <input type="date" name="birth_date" class="form-control" 
                                                       max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>" 
                                                       min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>"
                                                       required>
                                                <small class="text-muted">Debes ser mayor de 18 años</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Contraseña</label>
                                                <input type="password" name="password" class="form-control" 
                                                       placeholder="Mínimo 8 caracteres" 
                                                       minlength="8" maxlength="255"
                                                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*\d).{8,}"
                                                       title="Debe incluir mayúscula, minúscula, letras y números (mínimo 8 caracteres)"
                                                       required>
                                                <small class="text-muted">Debe incluir mayúscula, minúscula, letras y números (mínimo 8 caracteres)</small>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Confirmar contraseña</label>
                                                <input type="password" name="password_confirm" class="form-control" 
                                                       placeholder="Repite tu contraseña" 
                                                       minlength="8" maxlength="255"
                                                       required>
                                            </div>

                                            <div class="d-grid">
                                                <button class="btn btn-primary waves-effect waves-light" type="submit">Registrarme</button>
                                            </div>

                                            <div class="mt-4 text-center">
                                                <p class="text-muted mb-0">¿Ya tienes cuenta? <a href="login.php" class="text-primary fw-semibold">Inicia sesión</a></p>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="mt-4 mt-md-5 text-center">
                                        <p class="mb-0">© <script>document.write(new Date().getFullYear())</script> Clínica Moya</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-9 col-lg-8 col-md-7">
                        <div class="auth-bg pt-md-5 p-4 d-flex">
                            <div class="bg-overlay bg-primary"></div>
                            <ul class="bg-bubbles">
                                <li></li><li></li><li></li><li></li><li></li>
                                <li></li><li></li><li></li><li></li><li></li>
                            </ul>
                            <div class="row justify-content-center align-items-center">
                                <div class="col-xl-7">
                                    <div class="p-0 p-sm-4 px-xl-0">
                                        <div id="reviewcarouselIndicators" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                <div class="carousel-item active">
                                                    <div class="testi-contain text-white">
                                                        <i class="bx bxs-quote-alt-left text-success display-6"></i>
                                                        <h4 class="mt-4 fw-medium lh-base text-white">La salud es el regalo más valioso. En Clínica Moya, lo protegemos juntos.</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
