<?php
// Incluir el archivo de configuración si aún no se ha incluido
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/config.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' . $GLOBALS['app_settings']['name'] : $GLOBALS['app_settings']['name']; ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= BASE_URL; ?>assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>assets/css/style.css">
    
    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($extra_css)): ?>
        <?= $extra_css; ?>
    <?php endif; ?>
    
    <?php if (isset($extra_js_head)): ?>
        <?= $extra_js_head; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header Principal -->
    <header class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL; ?>">
                <img src="<?= BASE_URL; ?>assets/images/logo.png" alt="<?= $GLOBALS['app_settings']['name']; ?>" height="40">
                <span class="ms-2 fw-bold text-primary"><?= $GLOBALS['app_settings']['name']; ?></span>
            </a>
            
            <?php if (isLoggedIn()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto">
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL; ?>admin/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Panel Admin
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= BASE_URL; ?>notifications.php">
                            <i class="fas fa-bell me-1"></i> Notificaciones
                            <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $_SESSION['unread_notifications']; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= BASE_URL; ?>profile.php"><i class="fas fa-user me-2"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL; ?>settings.php"><i class="fas fa-cog me-2"></i> Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL; ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </header>
    
    <?php if (isLoggedIn()): ?>
    <!-- Barra de navegación lateral (solo para usuarios logueados) -->
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false ? 'active' : ''; ?>" href="<?= BASE_URL; ?>dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'orders.php') !== false ? 'active' : ''; ?>" href="<?= BASE_URL; ?>orders.php">
                                <i class="fas fa-clipboard-list me-2"></i> Mis Pedidos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'new-order.php') !== false ? 'active' : ''; ?>" href="<?= BASE_URL; ?>new-order.php">
                                <i class="fas fa-plus-circle me-2"></i> Nuevo Pedido
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'help.php') !== false ? 'active' : ''; ?>" href="<?= BASE_URL; ?>help.php">
                                <i class="fas fa-question-circle me-2"></i> Ayuda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'contact.php') !== false ? 'active' : ''; ?>" href="<?= BASE_URL; ?>contact.php">
                                <i class="fas fa-envelope me-2"></i> Contacto
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <?php if (isset($_SESSION['alert_message'])): ?>
                <div class="alert alert-<?= $_SESSION['alert_type']; ?> alert-dismissible fade show">
                    <?= $_SESSION['alert_message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php
                    unset($_SESSION['alert_message']);
                    unset($_SESSION['alert_type']);
                endif;
                ?>
    <?php else: ?>
        <!-- Contenido para usuarios no logueados -->
        <main class="container py-4">
            <?php if (isset($_SESSION['alert_message'])): ?>
            <div class="alert alert-<?= $_SESSION['alert_type']; ?> alert-dismissible fade show">
                <?= $_SESSION['alert_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
                unset($_SESSION['alert_message']);
                unset($_SESSION['alert_type']);
            endif;
            ?>
    <?php endif; ?>