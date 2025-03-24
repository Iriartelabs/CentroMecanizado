<?php
/**
 * Página de detalles de pedido
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (!isLoggedIn()) {
    $_SESSION['alert_message'] = "Debes iniciar sesión para ver los detalles del pedido.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Verificar si se ha proporcionado un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert_message'] = "ID de pedido no válido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'orders.php');
}

$orderId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();

// Obtener los detalles del pedido
$orderModel = new Order();
$orderDetails = $orderModel->getOrderDetails($orderId);

// Verificar si el pedido existe y pertenece al usuario actual (a menos que sea admin)
if (!$orderDetails || (!$isAdmin && $orderDetails['order']['user_id'] != $userId)) {
    $_SESSION['alert_message'] = "No tienes permiso para ver este pedido o el pedido no existe.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'orders.php');
}

// Procesar comentarios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment = clean($_POST['comment']);
    
    if (!empty($comment)) {
        if ($orderModel->addComment($orderId, $userId, $comment)) {
            $_SESSION['alert_message'] = "Comentario añadido con éxito.";
            $_SESSION['alert_type'] = 'success';
        } else {
            $_SESSION['alert_message'] = "Error al añadir el comentario.";
            $_SESSION['alert_type'] = 'danger';
        }
        
        // Redirigir para evitar reenvío del formulario
        redirect(BASE_URL . 'order-details.php?id=' . $orderId);
    }
}

// Obtener datos actualizados después de procesar formularios
$orderDetails = $orderModel->getOrderDetails($orderId);
$order = $orderDetails['order'];
$files = $orderDetails['files'];
$statusHistory = $orderDetails['status_history'];
$comments = $orderDetails['comments'];

// Establecer el título de la página
$page_title = 'Detalles del Pedido #' . $order['reference_number'];

// Incluir el encabezado
include_once 'includes/header.php';
?>

<!-- Título de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Pedido #<?= htmlspecialchars($order['reference_number']); ?></h1>
    <a href="<?= BASE_URL; ?>orders.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i> Volver a Pedidos
    </a>
</div>

<!-- Estado actual del pedido -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-0">Estado actual: <span class="badge bg-<?= getStatusBadgeClass($order['status']); ?>"><?= getOrderStatusText($order['status']); ?></span></h4>
                <?php if ($order['status'] === ORDER_STATUS_PROCESSING): ?>
                <p class="text-muted mb-0 mt-2">Fecha estimada de finalización: <?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></p>
                <?php elseif ($order['status'] === ORDER_STATUS_COMPLETED && $order['completion_date']): ?>
                <p class="text-muted mb-0 mt-2">Completado el: <?= formatDateTime($order['completion_date'], 'd/m/Y H:i'); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($isAdmin): ?>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                    <i class="fas fa-edit me-2"></i> Cambiar Estado
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detalles del pedido y comentarios -->
<div class="row">
    <div class="col-lg-8">
        <!-- Información del pedido -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h4 class="mb-0">Información del Pedido</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Tipo de proceso:</span>
                                <span class="fw-medium"><?= getProcessTypeText($order['process_type']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Material:</span>
                                <span class="fw-medium"><?= $order['material'] ?: 'No especificado'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Color:</span>
                                <span class="fw-medium"><?= $order['color'] ?: 'No especificado'; ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Fecha de creación:</span>
                                <span class="fw-medium"><?= formatDateTime($order['created_at']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Fecha estimada:</span>
                                <span class="fw-medium"><?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></span>
                            </li>
                            <?php if ($order['total_price']): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Precio total:</span>
                                <span class="fw-medium"><?= formatNumber($order['total_price']); ?> €</span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <?php if (!empty($order['observations'])): ?>
                <div class="mt-3">
                    <h5>Observaciones</h5>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br($order['observations']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Archivos del pedido -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h4 class="mb-0">Archivos</h4>
            </div>
            <div class="card-body">
                <?php if (empty($files)): ?>
                <p class="text-muted">No hay archivos asociados a este pedido.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?= $file['original_name']; ?></td>
                                <td><?= strtoupper($file['file_type']); ?></td>
                                <td><?= formatSize($file['file_size']); ?></td>
                                <td><?= formatDateTime($file['uploaded_at']); ?></td>
                                <td>
                                    <a href="<?= BASE_URL; ?>download-file.php?id=<?= $file['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Historial de estados -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h4 class="mb-0">Historial de Estados</h4>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($statusHistory as $status): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot bg-<?= getStatusBadgeClass($status['status']); ?>"></div>
                        <div class="timeline-date"><?= formatDateTime($status['created_at']); ?></div>
                        <div class="timeline-title"><?= getOrderStatusText($status['status']); ?></div>
                        <?php if (!empty($status['notes'])): ?>
                        <div class="timeline-description"><?= $status['notes']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($status['user_name'])): ?>
                        <div class="timeline-info">Por: <?= $status['user_name']; ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Comentarios -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h4 class="mb-0">Comentarios</h4>
            </div>
            <div class="card-body">
                <div class="comments-container mb-3" style="max-height: 500px; overflow-y: auto;">
                    <?php if (empty($comments)): ?>
                    <p class="text-muted">No hay comentarios para este pedido.</p>
                    <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment <?= $comment['user_id'] == $userId ? 'own-comment' : ''; ?> mb-3">
                        <div class="comment-header">
                            <div class="comment-user"><?= $comment['user_name']; ?></div>
                            <div class="comment-date"><?= formatDateTime($comment['created_at']); ?></div>
                        </div>
                        <div class="comment-content">
                            <?= nl2br($comment['comment']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Añadir comentario</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="add_comment" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Modal para cambiar estado -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>admin/update-status.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Cambiar Estado del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="">Seleccionar estado...</option>
                            <option value="<?= ORDER_STATUS_PENDING; ?>" <?= $order['status'] === ORDER_STATUS_PENDING ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="<?= ORDER_STATUS_PROCESSING; ?>" <?= $order['status'] === ORDER_STATUS_PROCESSING ? 'selected' : ''; ?>>En Proceso</option>
                            <option value="<?= ORDER_STATUS_COMPLETED; ?>" <?= $order['status'] === ORDER_STATUS_COMPLETED ? 'selected' : ''; ?>>Completado</option>
                            <option value="<?= ORDER_STATUS_REJECTED; ?>" <?= $order['status'] === ORDER_STATUS_REJECTED ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Función para obtener la clase CSS del badge de estado
function getStatusBadgeClass($status) {
    $statusClasses = [
        ORDER_STATUS_NEW => 'secondary',
        ORDER_STATUS_PENDING => 'warning',
        ORDER_STATUS_PROCESSING => 'info',
        ORDER_STATUS_COMPLETED => 'success',
        ORDER_STATUS_REJECTED => 'danger'
    ];
    
    return isset($statusClasses[$status]) ? $statusClasses[$status] : 'secondary';
}

// Función para formatear el tamaño de archivo
function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $size > 1024; $i++) {
        $size /= 1024;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

// Incluir el pie de página
include_once 'includes/footer.php';
?>