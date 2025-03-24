<?php
/**
 * Página de inicio de sesión
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Si el usuario ya está logueado, redirigir según su rol
if (isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        redirect(BASE_URL . 'admin/dashboard.php');
    } elseif ($_SESSION['user_role'] === 'technician') {
        redirect(BASE_URL . 'technician/dashboard.php');
    } else { // cliente por defecto
        redirect(BASE_URL . 'dashboard.php');
    }
}

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validar datos
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'El correo electrónico es obligatorio';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El formato del correo electrónico no es válido';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    }
    
    // Si no hay errores, intentar iniciar sesión
    if (empty($errors)) {
        // Obtener la instancia de la base de datos
        $db = Database::getInstance();
        
        // Buscar el usuario por correo electrónico
        $db->query("SELECT * FROM users WHERE email = :email");
        $db->bind(':email', $email);
        $user = $db->single();
        
        // Verificar si el usuario existe y la contraseña es correcta
        if ($user && password_verify($password, $user['password'])) {
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Si se ha marcado "Recordarme", crear una cookie
            if ($remember) {
                $token = generateRandomString(32);
                
                // Guardar el token en la base de datos
                $db->query("UPDATE users SET remember_token = :token WHERE id = :id");
                $db->bind(':token', $token);
                $db->bind(':id', $user['id']);
                $db->execute();
                
                // Crear la cookie (válida por 30 días)
                setcookie('remember_token', $token, time() + (86400 * 30), '/');
            }
            
            // Registrar el inicio de sesión
            logMessage("Usuario {$user['name']} (ID: {$user['id']}) ha iniciado sesión", 'info');
            
            // Redirigir según el rol del usuario
            $_SESSION['alert_message'] = "Bienvenido/a, {$user['name']}!";
            $_SESSION['alert_type'] = 'success';
            
            if ($user['role'] === 'admin') {
                redirect(BASE_URL . 'admin/dashboard.php');
            } elseif ($user['role'] === 'technician') {
                redirect(BASE_URL . 'technician/dashboard.php');
            } else { // cliente por defecto
                redirect(BASE_URL . 'dashboard.php');
            }
        } else {
            $errors[] = 'Correo electrónico o contraseña incorrectos';
            
            // Registrar el intento fallido
            logMessage("Intento de inicio de sesión fallido para el correo: {$email}", 'warning');
        }
    }
}

// Establecer el título de la página
$page_title = 'Iniciar Sesión';

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
                        <h2 class="mt-3">Iniciar Sesión</h2>
                        <p class="text-muted">Accede a tu cuenta para gestionar tus pedidos</p>
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
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= isset($email) ? $email : ''; ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p><a href="<?= BASE_URL; ?>forgot-password.php">¿Olvidaste tu contraseña?</a></p>
                        <p class="mb-0">¿No tienes una cuenta? <a href="<?= BASE_URL; ?>register.php">Regístrate aquí</a></p>
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