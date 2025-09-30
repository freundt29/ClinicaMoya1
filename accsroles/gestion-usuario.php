<?php
require_once __DIR__ . '/../backend/helpers/session.php';
require_once __DIR__ . '/../backend/controllers/UserController.php';

session_boot();
require_role(1); // solo admin

$usersCtl = new UserController();

$success = flash_get('success');
$generalFlash = flash_get('error');
$errors = [];

// Filtros simples
$q = isset($_GET['q']) ? trim($_GET['q']) : null;
$roleFilter = isset($_GET['role']) && $_GET['role'] !== '' ? (int)$_GET['role'] : null; // 1,2,3
$activeFilter = isset($_GET['active']) && $_GET['active'] !== '' ? (int)$_GET['active'] : null; // 1 o 0

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    verify_csrf_or_die();
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
      $full = trim($_POST['full_name'] ?? '');
      $username = trim($_POST['username'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $password = $_POST['password'] ?? '';
      $roleId = (int)($_POST['role_id'] ?? 0);

      if ($full === '') { $errors['full_name'] = 'Ingresa el nombre completo'; }
      if ($username === '') { $errors['username'] = 'Ingresa el usuario'; }
      if ($email === '') { $errors['email'] = 'Ingresa el email'; }
      if ($password === '') { $errors['password'] = 'Ingresa la contraseña'; }
      if ($password !== '' && strlen($password) < 8) { $errors['password'] = 'Mínimo 8 caracteres'; }
      if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'Email inválido'; }
      if (!in_array($roleId, [1,3], true)) { $errors['role_id'] = 'Solo Admin o Paciente'; }

      if (empty($errors)) {
        $id = $usersCtl->createUser($full, $username, $email, $password, $roleId);
        flash_set('success', 'Usuario creado (ID ' . (int)$id . ')');
        header('Location: gestion-usuario.php');
        exit;
      }
    } elseif ($action === 'toggle') {
      $uid = (int)($_POST['user_id'] ?? 0);
      $active = (int)($_POST['active'] ?? 1) === 1;
      $usersCtl->toggleUserActive($uid, $active);
      flash_set('success', 'Estado actualizado');
      header('Location: gestion-usuario.php');
      exit;
    } elseif ($action === 'delete') {
      $uid = (int)($_POST['user_id'] ?? 0);
      if ($uid > 0) {
        $usersCtl->deleteUser($uid);
        flash_set('success', 'Usuario eliminado correctamente');
      } else {
        flash_set('error', 'ID de usuario inválido');
      }
      header('Location: gestion-usuario.php');
      exit;
    } elseif ($action === 'resetpwd') {
      $uid = (int)($_POST['user_id'] ?? 0);
      $new = $_POST['new_password'] ?? '';
      if (strlen($new) < 8) { $errors['new_password_'.$uid] = 'Mín. 8 caracteres'; }
      if (empty($errors)) {
        $usersCtl->resetPassword($uid, $new);
        flash_set('success', 'Contraseña actualizada');
        header('Location: gestion-usuario.php');
        exit;
      }
    }
  } catch (Throwable $t) {
    $msg = $t->getMessage();
    if (stripos($msg, 'usuario ya existe') !== false) { $errors['username'] = 'El usuario ya existe'; }
    elseif (stripos($msg, 'email ya está registrado') !== false) { $errors['email'] = 'El email ya está registrado'; }
    else { $generalFlash = $msg; }
  }
}

$list = $usersCtl->listUsers($q ?: null, $roleFilter, $activeFilter);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Gestión de Usuarios | Clínica Moya</title>
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
      <a class="navbar-brand d-flex align-items-center" href="../admin/dashboard-admin.php">
      <img src="../assets/images/logoMoya.png" alt="" height="40" class="me-2"> 
      </a>
      <div class="d-flex align-items-center gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="../admin/doctores.php">Gestionar doctores</a>
        <a class="btn btn-outline-danger btn-sm" href="../public/logout.php">Salir</a>
      </div>
    </div>
  </nav>

  <div class="container my-5">
    <div class="row g-4">
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <strong>Usuarios</strong>
            <form class="d-flex gap-2" method="get">
              <input type="text" class="form-control form-control-sm" name="q" placeholder="Buscar (nombre, usuario, email)" value="<?php echo htmlspecialchars($q ?? '', ENT_QUOTES, 'UTF-8'); ?>">
              <select name="role" class="form-select form-select-sm" style="max-width:140px;">
                <option value="">Rol</option>
                <option value="1" <?php echo ($roleFilter===1)?'selected':''; ?>>Admin</option>
                <option value="2" <?php echo ($roleFilter===2)?'selected':''; ?>>Doctor</option>
                <option value="3" <?php echo ($roleFilter===3)?'selected':''; ?>>Paciente</option>
              </select>
              <select name="active" class="form-select form-select-sm" style="max-width:140px;">
                <option value="">Estado</option>
                <option value="1" <?php echo ($activeFilter===1)?'selected':''; ?>>Activo</option>
                <option value="0" <?php echo ($activeFilter===0)?'selected':''; ?>>Inactivo</option>
              </select>
              <button class="btn btn-sm btn-outline-light">Filtrar</button>
            </form>
          </div>
          <div class="card-body">
            <?php if ($success && !$generalFlash): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($generalFlash): ?><div class="alert alert-danger"><?php echo htmlspecialchars($generalFlash, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
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
                    <td><?php echo (int)$row['role_id']===1?'Admin':((int)$row['role_id']===2?'Doctor':'Paciente'); ?></td>
                    <td><span class="badge bg-<?php echo ((int)$row['is_active']===1)?'success':'secondary'; ?>"><?php echo ((int)$row['is_active']===1)?'Activo':'Inactivo'; ?></span></td>
                    <td class="text-end">
                      <div class="d-flex flex-column gap-2">
                        <div>
                          <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar estado de este usuario?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="active" value="<?php echo ((int)$row['is_active']===1)?'0':'1'; ?>">
                            <button class="btn btn-sm btn-outline-<?php echo ((int)$row['is_active']===1)?'secondary':'success'; ?>"><?php echo ((int)$row['is_active']===1)?'Desactivar':'Activar'; ?></button>
                          </form>
                          <form method="post" class="d-inline ms-1" onsubmit="return confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="mdi mdi-delete"></i> Eliminar</button>
                          </form>
                        </div>
                        <?php // Reset password form ?>
                        <form method="post" class="d-flex align-items-center gap-2">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="action" value="resetpwd">
                          <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                          <input type="text" name="new_password" class="form-control form-control-sm <?php echo isset($errors['new_password_'.$row['id']])?'is-invalid':''; ?>" placeholder="Nueva contraseña" minlength="8" style="max-width:200px;">
                          <button class="btn btn-sm btn-outline-primary">Reset</button>
                          <?php if (isset($errors['new_password_'.$row['id']])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['new_password_'.$row['id']], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                        </form>
                      </div>
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
