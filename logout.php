<?php
/**
 * Página de cierre de sesión
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (isLoggedIn()) {
    // Registrar el cierre de sesión
    logMessage("Usuario {$_SESSION['user_name']} (ID: {$_SESSION['user_id']}) ha cerrado sesión", 'info');
    
    // Destruir la sesión
    session_unset();
    session_destroy();
    
    // Eliminar la cookie de "recordarme" si existe
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Redirigir a la página de inicio de sesión
$_SESSION['alert_message'] = "Has cerrado sesión correctamente.";
$_SESSION['alert_type'] = 'success';
redirect(BASE_URL . 'login.php');
?>