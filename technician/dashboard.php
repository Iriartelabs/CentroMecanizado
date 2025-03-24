<?php
/**
 * Dashboard del técnico
 * Página principal después de iniciar sesión para usuarios con rol 'technician'
 */

// Incluir el archivo de configuración
require_once '../config.php';

// Verificar si el usuario ha iniciado sesión y es técnico
if (!isLoggedIn() || $_SESSION['user_role'] !== 'technician') {
    $_SESSION['alert_message'] = "No tienes permiso para acceder a esta sección.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Obtener parámetros de filtrado
$status = isset($_GET['status']) ? clean($_GET['status']) : '';
$processType = isset($_GET['process_type']) ? clean($_GET['process_type']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$searchTerm = isset($_GET['search']) ? clean($_GET['search']) : '';
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Obtener pedidos pendientes del técnico
$orderModel = new Order();
$pendingOrders = $orderModel->getAllOrders($searchTerm, $status ?: ORDER_STATUS_PENDING, $processType, $itemsPerPage, $offset);

// Obtener contadores para el panel de estadísticas
$countNew = count($orderModel->getAllOrders('', ORDER_STATUS_NEW, '', 1000, 0));
$countPending = count($orderModel->getAllOrders('', ORDER_STATUS_PENDING, '', 1000, 0));
$countProcessing = count($orderModel->getAllOrders('', ORDER_STATUS_PROCESSING, '', 1000, 0));
$countCompleted = count($orderModel->getAllOrders('', ORDER_STATUS_COMPLETED, '', 1000, 0));

// Para la paginación, necesitamos el total de pedidos
$totalOrders = count($orderModel->getAllOrders($searchTerm, $status, $processType, 1000, 0));
$totalPages = ceil($totalOrders / $itemsPerPage);

// Establecer el título de la página
$page_title = 'Dashboard de Técnico';

// Incluir el encabezado específico para técnicos
include_once '../includes/header.php';
?>

<!-- Título de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Dashboard de Técnico</h1>
    <div>
        <a href="<?= BASE_URL; ?>technician/reports.php" class="btn btn-outline-primary me-2">
            <i class="fas fa-chart-bar me-2"></i> Informes
        </a>
        <a href="<?= BASE_URL; ?>technician/calendar.php" class="btn btn-outline-primary">
            <i class="fas fa-calendar-alt me-2"></i> Calendario
        </a>
    </div>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="<?= BASE_URL; ?>technician/dashboard.php?status=<?= ORDER_STATUS_NEW; ?>" class="text-decoration-none">
            <div class="card stat-card h-100 border-left-primary">
                <div class="card-body">
                    <div class="stat-title">Nuevos Pedidos</div>
                    <div class="stat-value"><?= $countNew; ?></div>
                    <div class="stat-info">Pendientes de revisión</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="<?= BASE_URL; ?>technician/dashboard.php?status=<?= ORDER_STATUS_PENDING; ?>" class="text-decoration-none">
            <div class="card stat-card h-100 border-left-warning">
                <div class="card-body">
                    <div class="stat-title">Pendientes</div>
                    <div class="stat-value"><?= $countPending; ?></div>
                    <div class="stat-info">Listos para procesar</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="<?= BASE_URL; ?>technician/dashboard.php?status=<?= ORDER_STATUS_PROCESSING; ?>" class="text-decoration-none">
            <div class="card stat-card h-100 border-left-info">
                <div class="card-body">
                    <div class="stat-title">En Proceso</div>
                    <div class="stat-value"><?= $countProcessing; ?></div>
                    <div class="stat-info">Actualmente en producción</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="<?= BASE_URL; ?>technician/dashboard.php?status=<?= ORDER_STATUS_COMPLETED; ?>" class="text-decoration-none">
            <div class="card stat-card h-100 border-left-success">
                <div class="card-body">
                    <div class="stat-title">Completados (Hoy)</div>
                    <div class="stat-value"><?= $countCompleted; ?></div>
                    <div class="stat-info">Total completados hoy</div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Filtros y búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Estado</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos los estados</option>
                    <option value="<?= ORDER_STATUS_NEW; ?>" <?= $status === ORDER_STATUS_NEW ? 'selected' : ''; ?>>Nuevos</option>
                    <option value="<?= ORDER_STATUS_PENDING; ?>" <?= $status === ORDER_STATUS_PENDING ? 'selected' : ''; ?>>Pendientes</option>
                    <option value="<?= ORDER_STATUS_PROCESSING; ?>" <?= $status === ORDER_STATUS_PROCESSING ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="<?= ORDER_STATUS_COMPLETED; ?>" <?= $status === ORDER_STATUS_COMPLETED ? 'selected' : ''; ?>>Completados</option>
                    <option value="<?= ORDER_STATUS_REJECTED; ?>" <?= $status === ORDER_STATUS_REJECTED ? 'selected' : ''; ?>>Rechazados</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="process_type" class="form-label">Tipo de Proceso</label>
                <select class="form-select" id="process_type" name="process_type">
                    <option value="">Todos los procesos</option>
                    <option value="<?= PROCESS_TYPE_MILLING; ?>" <?= $processType === PROCESS_TYPE_MILLING ? 'selected' : ''; ?>>Fresado</option>
                    <option value="<?= PROCESS_TYPE_SINTERING; ?>" <?= $processType === PROCESS_TYPE_SINTERING ? 'selected' : ''; ?>>Sinterizado</option>
                    <option value="<?= PROCESS_TYPE_PRINTING; ?>" <?= $processType === PROCESS_TYPE_PRINTING ? 'selected' : ''; ?>>Impresión 3D</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Nº de referencia, nombre cliente..." value="<?= $searchTerm; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <?php if (!empty($status) || !empty($processType) || !empty($searchTerm)): ?>
            <div class="col-12">
                <a href="<?= BASE_URL; ?>technician/dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i> Limpiar filtros
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista de pedidos pendientes -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Pedidos <?= !empty($status) ? getOrderStatusText($status) : 'Pendientes'; ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendingOrders)): ?>
        <div class="text-center p-4">
            <p class="mb-0">No hay pedidos <?= !empty($status) ? getOrderStatusText($status) : 'pendientes'; ?> en este momento.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Ref.</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Material</th>
                        <th>Archivos</th>
                        <th>Estado</th>
                        <th>Entrega Est.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOrders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['reference_number']; ?></strong></td>
                        <td><?= $order['user_name']; ?></td>
                        <td><?= formatDateTime($order['created_at'], 'd/m/Y'); ?></td>
                        <td><span class="badge process-type-<?= $order['process_type']; ?>"><?= getProcessTypeText($order['process_type']); ?></span></td>
                        <td><?= $order['material'] ?: '-'; ?></td>
                        <td>
                            <?php if ($order['file_count'] > 0): ?>
                            <span class="badge bg-info"><?= $order['file_count']; ?> <?= $order['file_count'] == 1 ? 'archivo' : 'archivos'; ?></span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Sin archivos</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?= getOrderStatusClass($order['status']); ?>"><?= getOrderStatusText($order['status']); ?></span></td>
                        <td><?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="<?= BASE_URL; ?>technician/order-details.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#changeStatusModal" data-order-id="<?= $order['id']; ?>" data-ref-number="<?= $order['reference_number']; ?>">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <?php if ($order['comment_count'] > 0): ?>
                                <a href="<?= BASE_URL; ?>technician/order-details.php?id=<?= $order['id']; ?>#comments" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="<?= $order['comment_count']; ?> comentarios">
                                    <i class="fas fa-comment"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= BASE_URL; ?>technician/dashboard.php?page=<?= $page - 1; ?><?= !empty($status) ? '&status=' . $status : ''; ?><?= !empty($processType) ? '&process_type=' . $processType : ''; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?= BASE_URL; ?>technician/dashboard.php?page=<?= $i; ?><?= !empty($status) ? '&status=' . $status : ''; ?><?= !empty($processType) ? '&process_type=' . $processType : ''; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>"><?= $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= BASE_URL; ?>technician/dashboard.php?page=<?= $page + 1; ?><?= !empty($status) ? '&status=' . $status : ''; ?><?= !empty($processType) ? '&process_type=' . $processType : ''; ?><?= !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Estadísticas por tipo de proceso -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Fresado</h5>
                <span class="badge bg-primary"><?= count($orderModel->getAllOrders('', '', PROCESS_TYPE_MILLING, 1000, 0)); ?> pedidos</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Nuevos:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_NEW, PROCESS_TYPE_MILLING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pendientes:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_PENDING, PROCESS_TYPE_MILLING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>En proceso:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_PROCESSING, PROCESS_TYPE_MILLING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Completados (7 días):</span>
                    <strong>12</strong>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= BASE_URL; ?>technician/dashboard.php?process_type=<?= PROCESS_TYPE_MILLING; ?>" class="btn btn-sm btn-outline-primary w-100">Ver todos</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sinterizado</h5>
                <span class="badge bg-danger"><?= count($orderModel->getAllOrders('', '', PROCESS_TYPE_SINTERING, 1000, 0)); ?> pedidos</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Nuevos:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_NEW, PROCESS_TYPE_SINTERING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pendientes:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_PENDING, PROCESS_TYPE_SINTERING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>En proceso:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_PROCESSING, PROCESS_TYPE_SINTERING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Completados (7 días):</span>
                    <strong>8</strong>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= BASE_URL; ?>technician/dashboard.php?process_type=<?= PROCESS_TYPE_SINTERING; ?>" class="btn btn-sm btn-outline-primary w-100">Ver todos</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Impresión 3D</h5>
                <span class="badge bg-success"><?= count($orderModel->getAllOrders('', '', PROCESS_TYPE_PRINTING, 1000, 0)); ?> pedidos</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Nuevos:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_NEW, PROCESS_TYPE_PRINTING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Pendientes:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_PENDING, PROCESS_TYPE_PRINTING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>En proceso:</span>
                    <strong><?= count($orderModel->getAllOrders('', ORDER_STATUS_PROCESSING, PROCESS_TYPE_PRINTING, 1000, 0)); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Completados (7 días):</span>
                    <strong>15</strong>
                </div>
            </div>
            <div class="card-footer bg-white">
                <a href="<?= BASE_URL; ?>technician/dashboard.php?process_type=<?= PROCESS_TYPE_PRINTING; ?>" class="btn btn-sm btn-outline-primary w-100">Ver todos</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar el estado del pedido -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL; ?>technician/update-status.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Cambiar Estado del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modal-order-id" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Pedido</label>
                        <div class="form-control-plaintext" id="modal-ref-number"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Nuevo Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="">Seleccionar estado...</option>
                            <option value="<?= ORDER_STATUS_PENDING; ?>">Pendiente</option>
                            <option value="<?= ORDER_STATUS_PROCESSING; ?>">En Proceso</option>
                            <option value="<?= ORDER_STATUS_COMPLETED; ?>">Completado</option>
                            <option value="<?= ORDER_STATUS_REJECTED; ?>">Rechazado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Añade notas o comentarios sobre este cambio de estado..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notify_client" name="notify_client" checked>
                        <label class="form-check-label" for="notify_client">
                            Notificar al cliente sobre este cambio
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script para manejar el modal de cambio de estado
    document.addEventListener('DOMContentLoaded', function() {
        const changeStatusModal = document.getElementById('changeStatusModal');
        if (changeStatusModal) {
            changeStatusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                const refNumber = button.getAttribute('data-ref-number');
                
                const modalOrderId = this.querySelector('#modal-order-id');
                const modalRefNumber = this.querySelector('#modal-ref-number');
                
                modalOrderId.value = orderId;
                modalRefNumber.textContent = '#' + refNumber;
            });
        }
    });
</script>

<?php
// Incluir el pie de página
include_once '../includes/footer.php';
?>