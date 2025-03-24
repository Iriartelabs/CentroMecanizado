<?php
/**
 * Página de inicio
 * Punto de entrada principal a la aplicación
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Si el usuario ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . 'dashboard.php');
}

// Establecer el título de la página
$page_title = 'Inicio';

// Incluir el encabezado
include_once 'includes/header.php';
?>

<!-- Contenido de la página de inicio -->
<div class="px-4 py-5 my-5 text-center">
    <img class="d-block mx-auto mb-4" src="<?= BASE_URL . $GLOBALS['app_settings']['logo']; ?>" alt="Logo" height="80">
    <h1 class="display-5 fw-bold"><?= $GLOBALS['app_settings']['name']; ?></h1>
    <div class="col-lg-6 mx-auto">
        <p class="lead mb-4">
            Plataforma de gestión para centro de mecanizado dental. 
            Sube tus archivos y realiza un seguimiento de tus pedidos de forma sencilla y eficiente.
        </p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="<?= BASE_URL; ?>login.php" class="btn btn-primary btn-lg px-4 gap-3">Iniciar Sesión</a>
            <a href="<?= BASE_URL; ?>register.php" class="btn btn-outline-secondary btn-lg px-4">Registrarse</a>
        </div>
    </div>
</div>

<!-- Características principales -->
<div class="container px-4 py-5">
    <h2 class="pb-2 border-bottom">Características principales</h2>
    <div class="row g-4 py-5 row-cols-1 row-cols-lg-3">
        <div class="col d-flex align-items-start">
            <div class="icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3">
                <i class="fas fa-upload"></i>
            </div>
            <div>
                <h3 class="fs-2">Subida de archivos</h3>
                <p>Sube tus archivos STL, OBJ y DCM de forma rápida y segura para su procesamiento.</p>
            </div>
        </div>
        <div class="col d-flex align-items-start">
            <div class="icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3">
                <i class="fas fa-cogs"></i>
            </div>
            <div>
                <h3 class="fs-2">Múltiples procesos</h3>
                <p>Fresado, sinterizado e impresión 3D para satisfacer todas tus necesidades de producción dental.</p>
            </div>
        </div>
        <div class="col d-flex align-items-start">
            <div class="icon-square text-bg-light d-inline-flex align-items-center justify-content-center fs-4 flex-shrink-0 me-3">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h3 class="fs-2">Seguimiento en tiempo real</h3>
                <p>Monitoriza el estado de tus pedidos en cualquier momento y desde cualquier dispositivo.</p>
            </div>
        </div>
    </div>
</div>

<!-- Procesos disponibles -->
<div class="container px-4 py-5 bg-light">
    <h2 class="pb-2 border-bottom">Nuestros servicios</h2>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-cog fa-3x mb-3 text-primary"></i>
                    <h3 class="card-title">Fresado</h3>
                    <p class="card-text">
                        Fresado de alta precisión para coronas, puentes, inlays y onlays.
                        Compatible con materiales como zirconio, disilicato de litio, PMMA y más.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-fire fa-3x mb-3 text-danger"></i>
                    <h3 class="card-title">Sinterizado</h3>
                    <p class="card-text">
                        Sinterizado de alta temperatura para estructuras metálicas y zirconio.
                        Resultados de alta calidad con equipos de última generación.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-print fa-3x mb-3 text-success"></i>
                    <h3 class="card-title">Impresión 3D</h3>
                    <p class="card-text">
                        Impresión 3D para modelos, guías quirúrgicas, férulas y más.
                        Alta precisión y acabados de calidad.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonios -->
<div class="container px-4 py-5">
    <h2 class="pb-2 border-bottom">Testimonios de clientes</h2>
    <div class="row">
        <div class="col-lg-4">
            <div class="d-flex flex-column h-100 p-4 bg-light border rounded-3">
                <p class="mb-4">"El servicio de fresado es excelente. Los tiempos de entrega son inmejorables y la calidad del trabajo es excepcional. Totalmente recomendado."</p>
                <div class="d-flex align-items-center mt-auto">
                    <strong>Dr. Carlos Ramírez</strong>
                    <small class="text-muted ms-2">Clínica Dental Sonrisa</small>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-column h-100 p-4 bg-light border rounded-3">
                <p class="mb-4">"La plataforma es muy intuitiva y fácil de usar. Puedo hacer seguimiento de mis pedidos en todo momento y la comunicación con el equipo técnico es muy fluida."</p>
                <div class="d-flex align-items-center mt-auto">
                    <strong>Dra. Laura Martínez</strong>
                    <small class="text-muted ms-2">Centro Odontológico Avanzado</small>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="d-flex flex-column h-100 p-4 bg-light border rounded-3">
                <p class="mb-4">"Los trabajos de impresión 3D son perfectos. Los modelos tienen un acabado impecable y la precisión es exactamente lo que necesitamos para nuestras planificaciones."</p>
                <div class="d-flex align-items-center mt-auto">
                    <strong>Dr. Manuel Sánchez</strong>
                    <small class="text-muted ms-2">Implantología Avanzada</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<div class="container px-4 py-5 text-center">
    <div class="py-5 bg-primary text-white rounded-3">
        <h2 class="display-6 fw-bold mb-3">¿Listo para empezar?</h2>
        <p class="fs-5 mb-4">Regístrate ahora y comienza a disfrutar de nuestros servicios de mecanizado dental.</p>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <a href="<?= BASE_URL; ?>register.php" class="btn btn-light btn-lg px-4 me-sm-3">Registrarse</a>
            <a href="<?= BASE_URL; ?>contact.php" class="btn btn-outline-light btn-lg px-4">Contactar</a>
        </div>
    </div>
</div>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>
