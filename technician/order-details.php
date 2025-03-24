<?php
/**
 * Página de detalles de pedido para técnicos
 */

// Incluir el archivo de configuración
require_once '../config.php';

// Verificar si el usuario ha iniciado sesión y es técnico
if (!isLoggedIn() || $_SESSION['user_role'] !== 'technician') {
    $_SESSION['alert_message'] = "No tienes permiso para acceder a esta sección.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Verificar si se ha proporcionado un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert_message'] = "ID de pedido no válido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/dashboard.php');
}

$orderId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Obtener los detalles del pedido
$orderModel = new Order();
$orderDetails = $orderModel->getOrderDetails($orderId);

// Verificar si el pedido existe
if (!$orderDetails) {
    $_SESSION['alert_message'] = "El pedido no existe o ha sido eliminado.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/dashboard.php');
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
        redirect(BASE_URL . 'technician/order-details.php?id=' . $orderId);
    }
}

// Obtener datos actualizados después de procesar formularios
$orderDetails = $orderModel->getOrderDetails($orderId);
$order = $orderDetails['order'];
$files = $orderDetails['files'];
$statusHistory = $orderDetails['status_history'];
$comments = $orderDetails['comments'];

// Obtener datos del cliente
$userModel = new User();
$client = $userModel->findUserById($order['user_id']);

// Establecer el título de la página
$page_title = 'Detalles del Pedido #' . $order['reference_number'];

// Incluir el encabezado
include_once '../includes/header.php';
?>

<!-- Título de la página y acciones -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
        <h1 class="mb-0">Pedido #<?= htmlspecialchars($order['reference_number']); ?></h1>
        <span class="status-badge ms-3 <?= getOrderStatusClass($order['status']); ?>"><?= getOrderStatusText($order['status']); ?></span>
    </div>
    <div class="btn-group">
        <a href="<?= BASE_URL; ?>technician/dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> Volver
        </a>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
            <i class="fas fa-exchange-alt me-2"></i> Cambiar Estado
        </button>
    </div>
</div>

<!-- Información general -->
<div class="row mb-4">
    <!-- Información del pedido -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Información del Pedido</h5>
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
                                <span class="text-muted">Fecha estimada:</span>
                                <span class="fw-medium"><?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Prioridad:</span>
                                <span class="badge bg-warning">Normal</span>
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
                    <h6 class="text-muted">Observaciones del cliente</h6>
                    <div class="border rounded p-3 bg-light">
                        <?= nl2br(htmlspecialchars($order['observations'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Botones de acción específicos para técnicos -->
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="fas fa-sticky-note me-1"></i> Añadir Nota Técnica
                    </button>
                    <button class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#adjustDateModal">
                        <i class="fas fa-calendar-alt me-1"></i> Ajustar Fecha
                    </button>
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#changePriorityModal">
                        <i class="fas fa-flag me-1"></i> Cambiar Prioridad
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Notas técnicas -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Notas Técnicas</h5>
            </div>
            <div class="card-body">
                <?php if (empty($orderDetails['notes'])): ?>
                <p class="text-muted">No hay notas técnicas para este pedido.</p>
                <?php else: ?>
                <div class="timeline">
                    <!-- Simulación de notas técnicas (en producción se obtendría de la base de datos) -->
                    <div class="timeline-item">
                        <div class="timeline-dot bg-info"></div>
                        <div class="timeline-date">22/03/2025 14:30</div>
                        <div class="timeline-title">Verificación de archivo STL</div>
                        <div class="timeline-description">Archivo revisado. Se detectan paredes demasiado finas en la zona posterior. Se recomienda reforzar.</div>
                        <div class="timeline-info">Por: Técnico Carlos</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Archivos del pedido -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Archivos</h5>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                    <i class="fas fa-upload me-1"></i> Subir Archivo
                </button>
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
                                <td><?= htmlspecialchars($file['original_name']); ?></td>
                                <td><?= strtoupper($file['file_type']); ?></td>
                                <td><?= formatSize($file['file_size']); ?></td>
                                <td><?= formatDateTime($file['uploaded_at']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= BASE_URL; ?>download-file.php?id=<?= $file['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Previsualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
                <h5 class="mb-0">Historial de Estados</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($statusHistory as $status): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot bg-<?= getStatusBadgeClass($status['status']); ?>"></div>
                        <div class="timeline-date"><?= formatDateTime($status['created_at']); ?></div>
                        <div class="timeline-title"><?= getOrderStatusText($status['status']); ?></div>
                        <?php if (!empty($status['notes'])): ?>
                        <div class="timeline-description"><?= htmlspecialchars($status['notes']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($status['user_name'])): ?>
                        <div class="timeline-info">Por: <?= htmlspecialchars($status['user_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar con información adicional -->
    <div class="col-md-4">
        <!-- Información del cliente -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Información del Cliente</h5>
            </div>
            <div class="card-body">
                <?php if ($client): ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="client-avatar rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <span><?= strtoupper(substr($client['name'], 0, 1)); ?></span>
                    </div>
                    <div>
                        <h6 class="mb-0"><?= htmlspecialchars($client['name']); ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($client['email']); ?></small>
                    </div>
                </div>
                
                <ul class="list-group list-group-flush">
                    <?php if (!empty($client['company'])): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Empresa:</span>
                        <span><?= htmlspecialchars($client['company']); ?></span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($client['phone'])): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Teléfono:</span>
                        <span><?= htmlspecialchars($client['phone']); ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Cliente desde:</span>
                        <span><?= formatDateTime($client['created_at'], 'd/m/Y'); ?></span>
                    </li>
                </ul>
                
                <div class="d-grid gap-2 mt-3">
                    <a href="<?= BASE_URL; ?>technician/client-orders.php?user_id=<?= $client['id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-clipboard-list me-1"></i> Ver todos los pedidos
                    </a>
                    <a href="mailto:<?= htmlspecialchars($client['email']); ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-envelope me-1"></i> Enviar correo
                    </a>
                </div>
                <?php else: ?>
                <p class="text-muted">No se encontró información del cliente.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Comentarios -->
        <div class="card mb-4" id="comments">
            <div class="card-header bg-white">
                <h5 class="mb-0">Comunicación con el Cliente</h5>
            </div>
            <div class="card-body">
                <div class="comments-container mb-3" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($comments)): ?>
                    <p class="text-muted">No hay comentarios para este pedido.</p>
                    <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment <?= $comment['user_id'] == $userId ? 'own-comment' : ''; ?> mb-3">
                        <div class="comment-header">
                            <div class="comment-user"><?= htmlspecialchars($comment['user_name']); ?></div>
                            <div class="comment-date"><?= formatDateTime($comment['created_at']); ?></div>
                        </div>
                        <div class="comment-content">
                            <?= nl2br(htmlspecialchars($comment['comment'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="" id="commentForm">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Añadir comentario</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <div class="d-flex justify-content-between">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="send_email" name="send_email">
                            <label class="form-check-label" for="send_email">
                                Notificar por email
                            </label>
                        </div>
                        <button type="submit" name="add_comment" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Pedidos recientes del cliente -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Otros pedidos del cliente</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <!-- En producción se obtendrían de la base de datos -->
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">#20250322-0001</h6>
                            <small class="text-muted">22/03/2025</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0 text-muted">Fresado - Zirconio</p>
                            <span class="status-badge status-completed">Completado</span>
                        </div>
                    </a>
                    <a href="#" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">#20250315-0023</h6>
                            <small class="text-muted">15/03/2025</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0 text-muted">Impresión 3D - Resina</p>
                            <span class="status-badge status-completed">Completado</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar estado -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>technician/update-status.php">
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
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Descripción del cambio de estado..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_client" name="notify_client" checked>
                        <label class="form-check-label" for="notify_client">
                            Notificar al cliente sobre este cambio
                        </label>
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

<!-- Modal para subir archivo -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>technician/upload-file.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadFileModalLabel">Subir Archivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Seleccionar Archivo</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                        <div class="form-text">Formatos aceptados: <?= implode(', ', ALLOWED_FILE_TYPES); ?> (máximo <?= formatSize(MAX_FILE_SIZE); ?>)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="file_description" class="form-label">Descripción</label>
                        <textarea class="form-control" id="file_description" name="file_description" rows="2" placeholder="Describe el propósito de este archivo..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_client_file" name="notify_client_file" checked>
                        <label class="form-check-label" for="notify_client_file">
                            Notificar al cliente sobre este archivo
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Subir Archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para añadir nota técnica -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>technician/add-note.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">Añadir Nota Técnica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                    
                    <div class="mb-3">
                        <label for="note_title" class="form-label">Título</label>
                        <input type="text" class="form-control" id="note_title" name="note_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="note_content" class="form-label">Contenido</label>
                        <textarea class="form-control" id="note_content" name="note_content" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="visible_to_client" name="visible_to_client">
                        <label class="form-check-label" for="visible_to_client">
                            Visible para el cliente
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Nota</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ajustar fecha -->
<div class="modal fade" id="adjustDateModal" tabindex="-1" aria-labelledby="adjustDateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>technician/adjust-date.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="adjustDateModalLabel">Ajustar Fecha de Entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                    
                    <div class="mb-3">
                        <label for="new_date" class="form-label">Nueva fecha estimada</label>
                        <input type="date" class="form-control" id="new_date" name="new_date" value="<?= date('Y-m-d', strtotime($order['estimated_completion_date'])); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="date_reason" class="form-label">Motivo del ajuste</label>
                        <textarea class="form-control" id="date_reason" name="date_reason" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_client_date" name="notify_client_date" checked>
                        <label class="form-check-label" for="notify_client_date">
                            Notificar al cliente sobre el cambio de fecha
                        </label>
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

<!-- Modal para cambiar prioridad -->
<div class="modal fade" id="changePriorityModal" tabindex="-1" aria-labelledby="changePriorityModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>technician/change-priority.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePriorityModalLabel">Cambiar Prioridad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Prioridad</label>
                        <select class="form-select" id="priority" name="priority" required>
                            <option value="low">Baja</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority_reason" class="form-label">Motivo del cambio</label>
                        <textarea class="form-control" id="priority_reason" name="priority_reason" rows="3" required></textarea>
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

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
?>
muted">Creado el: <?= formatDateTime($order['created_at']); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Tipo de proceso:</span>
                                <span class="badge process-type-<?= $order['process_type']; ?>"><?= getProcessTypeText($order['process_type']); ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-