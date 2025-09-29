<?php
require_once __DIR__ . '/../backend/controllers/AuthController.php';
require_once __DIR__ . '/../backend/helpers/session.php';

session_boot();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Ingresa usuario y contraseña.';
    } else {
        $auth = new AuthController();
        if ($auth->login($username, $password)) {
            $user = current_user();
            redirect_by_role((int)$user['role_id']);
        } else {
            $error = 'Credenciales inválidas o usuario inactivo.';
        }
    }
}
?>
<!doctype html>
<html lang="es">

    <head>

        <meta charset="utf-8" />
        <title>Login | Clínica Moya</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    </head>

    <body>

        <!-- <body data-layout="horizontal"> -->
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
                                            <h5 class="mb-0">Bienvenido de nuevo !</h5>
                                            <p class="text-muted mt-2">Inicia Sesión para continuar.</p>
                                        </div>
                                        <?php if ($error): ?>
                                            <div class="alert alert-danger" role="alert">
                                                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        <?php endif; ?>
                                        <form class="mt-4 pt-2" method="post" action="">
                                            <div class="mb-3">
                                                <label class="form-label">Nombre de Usuario</label>
                                                <input type="text" name="username" class="form-control" id="username" placeholder="Ingresa usuario o email" required>
                                            </div>
                                            <div class="mb-3">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-grow-1">
                                                        <label class="form-label">Contraseña</label>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <div class="">
                                                            <a href="../usuarios/recuperar-contrasena.html" class="text-muted">Has olvidado tu contraseña?</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="input-group auth-pass-inputgroup">
                                                    <input type="password" name="password" class="form-control" placeholder="Ingresa contraseña" aria-label="Password" aria-describedby="password-addon" required>
                                                    <button class="btn btn-light shadow-none ms-0" type="button" id="password-addon"><i class="mdi mdi-eye-outline"></i></button>
                                                </div>
                                            </div>
                                            <div class="row mb-4">
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="remember-check">
                                                        <label class="form-check-label" for="remember-check">
                                                            Acuerdame
                                                        </label>
                                                    </div>  
                                                </div>
                                                <div class="col-auto">
                                                    <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">Acceso</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="mt-4 mt-md-5 text-center">
                                        <p class="mb-0">© <script>document.write(new Date().getFullYear())</script> Clínica Moya</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end auth full page content -->
                    </div>
                    <!-- end col -->
                    <div class="col-xxl-9 col-lg-8 col-md-7">
                        <div class="auth-bg pt-md-5 p-4 d-flex">
                            <div class="bg-overlay bg-primary"></div>
                            <ul class="bg-bubbles">
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                                <li></li>
                            </ul>
                            <!-- end bubble effect -->
                            <div class="row justify-content-center align-items-center">
                                <div class="col-xl-7">
                                    <div class="p-0 p-sm-4 px-xl-0">
                                        <div id="reviewcarouselIndicators" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                <div class="carousel-item active">
                                                    <div class="testi-contain text-white">
                                                        <i class="bx bxs-quote-alt-left text-success display-6"></i>

                                                        <h4 class="mt-4 fw-medium lh-base text-white">La salud es el regalo más valioso. 
                                                            En Clínica Moya, lo protegemos juntos
                                                        </h4>
                                                        <div class="mt-4 pt-3 pb-5">
                                                            <div class="d-flex align-items-start">
                                                                <div class="flex-grow-1 ms-3 mb-4">
                                                                    <h5 class="font-size-18 text-white">Clínica Moya
                                                                    </h5>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- end carousel-inner -->
                                        </div>
                                        <!-- end review carousel -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container fluid -->
        </div>

        <!-- JAVASCRIPT -->
        <script src="../assets/libs/jquery/jquery.min.js"></script>
        <script src="../assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="../assets/libs/metismenu/metisMenu.min.js"></script>
        <script src="../assets/libs/simplebar/simplebar.min.js"></script>
        <script src="../assets/libs/node-waves/waves.min.js"></script>
        <script src="../assets/libs/feather-icons/feather.min.js"></script>
        <!-- pace js -->
        <script src="../assets/libs/pace-js/pace.min.js"></script>
        <!-- password addon init -->
        <script src="../assets/js/pages/pass-addon.init.js"></script>

    </body>

</html>