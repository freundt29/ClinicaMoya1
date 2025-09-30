<?php
/**
 * Solicitud de recuperación de contraseña
 * El usuario ingresa su email y recibe un enlace con token
 */
require_once __DIR__ . '/../backend/controllers/PasswordResetController.php';
require_once __DIR__ . '/../backend/helpers/session.php';

session_boot();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf_or_die();
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            throw new InvalidArgumentException('Ingresa tu email');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email inválido');
        }
        
        $controller = new PasswordResetController();
        $token = $controller->createResetToken($email);
        
        if ($token) {
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/proyecto1moya/public/reset-password.php?token=" . $token;
            
            // MODO DESARROLLO: Mostrar enlace en pantalla
            $_SESSION['reset_link'] = $resetLink;
            $success = "Enlace de recuperación generado. Copia el enlace que aparece abajo.";
            
            // NOTA: Para enviar emails reales en producción, necesitarías:
            // 1. Configurar credenciales SMTP en EmailHelper.php
            // 2. Descomentar el código de envío de email
        } else {
            // Por seguridad, no revelamos si el email existe o no
            $success = "Si el email existe en nuestro sistema, recibirás instrucciones para recuperar tu contraseña.";
        }
        
    } catch (Throwable $t) {
        $error = $t->getMessage();
    }
}
?>
<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <title>Recuperar Contraseña | Clínica Moya</title>
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
                                            <h5 class="mb-0">Recuperar Contraseña</h5>
                                            <p class="text-muted mt-2">Ingresa tu email para recibir instrucciones.</p>
                                        </div>
                                        
                                        <?php if ($success): ?>
                                            <div class="alert alert-success" role="alert">
                                                <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if (!empty($_SESSION['reset_link'])): ?>
                                                    <hr>
                                                    <p class="mb-0"><strong>Link de prueba:</strong></p>
                                                    <a href="<?php echo htmlspecialchars($_SESSION['reset_link'], ENT_QUOTES, 'UTF-8'); ?>" class="text-break">
                                                        <?php echo htmlspecialchars($_SESSION['reset_link'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </a>
                                                    <?php unset($_SESSION['reset_link']); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger" role="alert">
                                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form class="mt-4" method="post" action="">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" placeholder="Ingresa tu email" required>
                                            </div>
                                            
                                            <div class="mb-3 mt-4">
                                                <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">Enviar instrucciones</button>
                                            </div>
                                        </form>

                                        <div class="mt-5 text-center">
                                            <p class="text-muted mb-0">¿Recuerdas tu contraseña? <a href="login.php" class="text-primary fw-semibold">Iniciar Sesión</a></p>
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
