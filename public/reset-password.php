<?php
/**
 * Cambio de contraseña con token
 * El usuario llega aquí desde el enlace del email
 */
require_once __DIR__ . '/../backend/controllers/PasswordResetController.php';
require_once __DIR__ . '/../backend/helpers/session.php';

session_boot();

$error = null;
$success = null;
$token = $_GET['token'] ?? '';

// Validar que el token existe
if (empty($token)) {
    $error = 'Token inválido o faltante';
}

// Validar token antes de mostrar el formulario
$controller = new PasswordResetController();
$email = null;
if (!$error) {
    $email = $controller->validateToken($token);
    if (!$email) {
        $error = 'El enlace de recuperación es inválido o ha expirado. Solicita uno nuevo.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    try {
        verify_csrf_or_die();
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';
        
        if (empty($password) || empty($confirm)) {
            throw new InvalidArgumentException('Completa todos los campos');
        }
        
        if ($password !== $confirm) {
            throw new InvalidArgumentException('Las contraseñas no coinciden');
        }
        
        $controller->resetPassword($token, $password);
        
        flash_set('success', 'Contraseña actualizada correctamente. Ahora puedes iniciar sesión.');
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
        <title>Restablecer Contraseña | Clínica Moya</title>
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
                                            <h5 class="mb-0">Nueva Contraseña</h5>
                                            <p class="text-muted mt-2">Ingresa tu nueva contraseña.</p>
                                        </div>
                                        
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger" role="alert">
                                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div class="text-center mt-3">
                                                <a href="forgot-password.php" class="btn btn-primary">Solicitar nuevo enlace</a>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info" role="alert">
                                                Restableciendo contraseña para: <strong><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong>
                                            </div>
                                            
                                            <form class="mt-4" method="post" action="">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Nueva Contraseña</label>
                                                    <input type="password" name="password" class="form-control" 
                                                           placeholder="Mínimo 8 caracteres" 
                                                           minlength="8" maxlength="255"
                                                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z])(?=.*\d).{8,}"
                                                           title="Debe incluir mayúscula, minúscula, letras y números"
                                                           required>
                                                    <small class="text-muted">Debe incluir mayúscula, minúscula, letras y números (mínimo 8 caracteres)</small>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Confirmar Contraseña</label>
                                                    <input type="password" name="password_confirm" class="form-control" 
                                                           placeholder="Repite tu contraseña" 
                                                           minlength="8" maxlength="255"
                                                           required>
                                                </div>
                                                
                                                <div class="mb-3 mt-4">
                                                    <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">Cambiar Contraseña</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>

                                        <div class="mt-5 text-center">
                                            <p class="text-muted mb-0"><a href="login.php" class="text-primary fw-semibold">Volver al inicio de sesión</a></p>
                                        </div>
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

        <script src="../assets/libs/jquery/jquery.min.js"></script>
        <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
        <script src="../assets/libs/simplebar/simplebar.min.js"></script>
        <script src="../assets/libs/node-waves/waves.min.js"></script>
        <script src="../assets/libs/feather-icons/feather.min.js"></script>
        <script src="../assets/libs/pace-js/pace.min.js"></script>
    </body>
</html>
