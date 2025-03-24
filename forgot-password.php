<?php
/**
 * Página de recuperación de contraseña
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener el correo electrónico del formulario
    $email = clean($_POST['email']);
    
    // Validar correo electrónico
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'El correo electrónico es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido';
    }
    
    // Si no hay errores, proceder con la recuperación
    if (empty($errors)) {
        // Crear instancia de la clase User
        $userModel = new User();
        
        // Generar token de recuperación
        $token = $userModel->generateResetToken($email);
        
        if ($token) {
            // En un entorno real, aquí enviarías un correo electrónico con el enlace de recuperación
            // Para el prototipo, mostraremos un mensaje con el enlace
            
            $resetLink = BASE_URL . 'reset-password.php?token=' . $token;
            
            $success = true;
            $resetLinkMessage = $resetLink; // En producción, esto no se mostraría al usuario
            
            // Registrar en el log
            logMessage("Solicitud de recuperación de contraseña para: {$email}", 'info');
        } else {
            $errors[] = 'No se encontró ninguna cuenta con ese correo electrónico o hubo un error al procesar la solicitud.';
        }
    }
}

// Establecer el título de la página
$page_title = 'Recuperar Contraseña';

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
                        <h2 class="mt-3">Recuperar Contraseña</h2>
                        <p class="text-muted">Ingresa tu correo electrónico para recibir instrucciones</p>
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
                    
                    <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success">
                        <p>Hemos enviado un correo electrónico con instrucciones para recuperar tu contraseña. Por favor, revisa tu bandeja de entrada.</p>
                        
                        <!-- SOLO PARA DEMOSTRACIÓN - Eliminar en producción -->
                        <div class="mt-3 p-3 border rounded bg-light">
                            <p class="mb-1"><strong>Enlace de recuperación (solo para demo):</strong></p>
                            <a href="<?= $resetLinkMessage; ?>" class="word-break-all"><?= $resetLinkMessage; ?></a>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?= BASE_URL; ?>login.php" class="btn btn-outline-primary">Volver al inicio de sesión</a>
                    </div>
                    <?php else: ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? $email : ''; ?>" required autofocus>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Enviar instrucciones</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="mb-0"><a href="<?= BASE_URL; ?>login.php">Volver al inicio de sesión</a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .word-break-all {
        word-break: break-all;
    }
</style>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>