<?php
/**
 * Página de restablecimiento de contraseña
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

// Verificar si se ha proporcionado un token
if (!isset($_GET['token']) || empty($_GET['token'])) {
    $_SESSION['alert_message'] = "Token no válido o caducado.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'forgot-password.php');
}

$token = clean($_GET['token']);

// Verificar si el token es válido
$userModel = new User();
$user = $userModel->verifyResetToken($token);

if (!$user) {
    $_SESSION['alert_message'] = "Token no válido o caducado.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'forgot-password.php');
}

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validar datos
    $errors = [];
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    } elseif (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Las contraseñas no coinciden';
    }
    
    // Si no hay errores, restablecer la contraseña
    if (empty($errors)) {
        if ($userModel->resetPassword($token, $password)) {
            // Registrar en el log
            logMessage("Contraseña restablecida para el usuario: {$user['name']} (ID: {$user['id']})", 'info');
            
            // Mostrar mensaje de éxito y redirigir al login
            $_SESSION['alert_message'] = 'Contraseña restablecida con éxito. Ahora puedes iniciar sesión con tu nueva contraseña.';
            $_SESSION['alert_type'] = 'success';
            redirect(BASE_URL . 'login.php');
        } else {
            $errors[] = 'Ha ocurrido un error al restablecer la contraseña. Inténtalo de nuevo.';
        }
    }
}

// Establecer el título de la página
$page_title = 'Restablecer Contraseña';

// Incluir el encabezado
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL . $GLOBALS['app_settings']['logo']; ?>" alt="Logo" height="60">
                        <h2 class="mt-3">Restablecer Contraseña</h2>
                        <p class="text-muted">Establece una nueva contraseña para tu cuenta</p>
                    </div>
                    
                    <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                            <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Restablecer Contraseña</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0"><a href="<?= BASE_URL; ?>login.php">Volver al inicio de sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>