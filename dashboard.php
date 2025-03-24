<?php
/**
 * Dashboard del cliente
 * Página principal después de iniciar sesión
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (!isLoggedIn()) {
    $_SESSION['alert_message'] = "Debes iniciar sesión para acceder al dashboard.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Obtener el ID del usuario actual
$userId = $_SESSION['user_id'];

// Obtener datos reales para el dashboard
$orderModel = new Order();

// Obtener estadísticas de pedidos del usuario
$stats = $orderModel->getUserOrderStats($userId);

// Obtener pedidos recientes del usuario (limitados a 4)
$recent_orders = $orderModel->getUserOrders($userId, '', 4, 0);

// Establecer el título de la página
$page_title = 'Dashboard';

// Incluir el encabezado
include_once 'includes/header.php';
?>

<!-- Título de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Dashboard</h1>
    <a href="<?= BASE_URL; ?>new-order.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Nuevo Pedido
    </a>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-title">Pedidos Activos</div>
                <div class="stat-value"><?= $stats['active_orders']; ?></div>
                <div class="stat-info"><?= $stats['pending_orders']; ?> pendientes, <?= $stats['processing_orders']; ?> en proceso</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-title">Completados (Mes)</div>
                <div class="stat-value"><?= $stats['monthly_orders']; ?></div>
                <div class="stat-info">Total completados este mes</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-title">Tiempo Promedio</div>
                <div class="stat-value"><?= $stats['average_time']; ?>d</div>
                <div class="stat-info">De procesamiento</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="stat-title">Próxima Entrega</div>
                <?php if ($stats['next_delivery']): ?>
                <div class="stat-value"><?= formatDateTime($stats['next_delivery']['estimated_completion_date'], 'd/m/Y'); ?></div>
                <div class="stat-info">Pedido #<?= $stats['next_delivery']['reference_number']; ?></div>
                <?php else: ?>
                <div class="stat-value">-</div>
                <div class="stat-info">No hay entregas pendientes</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pedidos recientes -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pedidos Recientes</h5>
            <a href="<?= BASE_URL; ?>orders.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Archivo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-3">No hay pedidos recientes. <a href="<?= BASE_URL; ?>new-order.php">Crear nuevo pedido</a></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?= $order['reference_number']; ?></td>
                            <td><?= formatDateTime($order['created_at']); ?></td>
                            <td><?= getProcessTypeText($order['process_type']); ?></td>
                            <td><?= $order['file_count']; ?> <?= $order['file_count'] == 1 ? 'archivo' : 'archivos'; ?></td>
                            <td><span class="status-badge <?= getOrderStatusClass($order['status']); ?>"><?= getOrderStatusText($order['status']); ?></span></td>
                            <td>
                                <a href="<?= BASE_URL; ?>order-details.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Comentarios">
                                    <i class="fas fa-comment"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Actividad reciente -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Actividad Reciente</h5>
            </div>
            <div class="card-body">
                <?php
                // Obtener el historial de estados de los pedidos recientes
                $activityItems = [];
                
                foreach ($recent_orders as $order) {
                    $orderDetails = $orderModel->getOrderDetails($order['id']);
                    if (!empty($orderDetails['status_history'])) {
                        foreach ($orderDetails['status_history'] as $status) {
                            $activityItems[] = [
                                'date' => $status['created_at'],
                                'title' => 'Pedido #' . $order['reference_number'] . ' - ' . getOrderStatusText($status['status']),
                                'description' => $status['notes'] ?: 'El estado de tu pedido ha sido actualizado.',
                                'order_id' => $order['id']
                            ];
                        }
                    }
                    
                    if (!empty($orderDetails['comments'])) {
                        foreach ($orderDetails['comments'] as $comment) {
                            if ($comment['user_id'] != $userId) {
                                $activityItems[] = [
                                    'date' => $comment['created_at'],
                                    'title' => 'Nuevo comentario en Pedido #' . $order['reference_number'],
                                    'description' => $comment['user_name'] . ' ha añadido un comentario.',
                                    'order_id' => $order['id']
                                ];
                            }
                        }
                    }
                }
                
                // Ordenar por fecha (más recientes primero)
                usort($activityItems, function($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });
                
                // Limitar a 4 items
                $activityItems = array_slice($activityItems, 0, 4);
                ?>
                
                <?php if (empty($activityItems)): ?>
                <p class="text-muted">No hay actividad reciente para mostrar.</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($activityItems as $item): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-date"><?= formatDateTime($item['date']); ?></div>
                        <div class="timeline-title"><?= $item['title']; ?></div>
                        <div class="timeline-description"><?= $item['description']; ?></div>
                        <div class="timeline-actions mt-2">
                            <a href="<?= BASE_URL; ?>order-details.php?id=<?= $item['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                                Ver detalles
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Próximas Entregas</h5>
            </div>
            <div class="card-body">
                <?php
                // Obtener los pedidos pendientes o en proceso
                $pendingOrders = $orderModel->getUserOrders($userId, ORDER_STATUS_PENDING, 5, 0);
                $processingOrders = $orderModel->getUserOrders($userId, ORDER_STATUS_PROCESSING, 5, 0);
                
                // Combinar los pedidos
                $upcomingOrders = array_merge($pendingOrders, $processingOrders);
                
                // Ordenar por fecha estimada
                usort($upcomingOrders, function($a, $b) {
                    return strtotime($a['estimated_completion_date']) - strtotime($b['estimated_completion_date']);
                });
                
                // Limitar a 3 pedidos
                $upcomingOrders = array_slice($upcomingOrders, 0, 3);
                ?>
                
                <?php if (empty($upcomingOrders)): ?>
                <p class="text-muted">No hay entregas próximas programadas.</p>
                <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($upcomingOrders as $order): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Pedido #<?= $order['reference_number']; ?></strong>
                            <p class="mb-0 text-muted"><?= getProcessTypeText($order['process_type']); ?> - <?= $order['material'] ?: 'Material no especificado'; ?></p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary rounded-pill"><?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></span>
                            <p class="mb-0 small text-muted">
                                <?php
                                $days = ceil((strtotime($order['estimated_completion_date']) - time()) / (60 * 60 * 24));
                                echo $days <= 0 ? 'Hoy' : "En {$days} día" . ($days == 1 ? '' : 's');
                                ?>
                            </p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resumen por tipo de proceso -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0">Resumen por Tipo de Proceso</h5>
    </div>
    <div class="card-body">
        <?php
        // Obtener estadísticas por tipo de proceso
        $millingCount = count($orderModel->getUserOrders($userId, '', 1000, 0, PROCESS_TYPE_MILLING));
        $millingActive = count($orderModel->getUserOrders($userId, ORDER_STATUS_PROCESSING, 1000, 0, PROCESS_TYPE_MILLING));
        
        $sinteringCount = count($orderModel->getUserOrders($userId, '', 1000, 0, PROCESS_TYPE_SINTERING));
        $sinteringActive = count($orderModel->getUserOrders($userId, ORDER_STATUS_PROCESSING, 1000, 0, PROCESS_TYPE_SINTERING));
        
        $printingCount = count($orderModel->getUserOrders($userId, '', 1000, 0, PROCESS_TYPE_PRINTING));
        $printingActive = count($orderModel->getUserOrders($userId, ORDER_STATUS_PROCESSING, 1000, 0, PROCESS_TYPE_PRINTING));
        ?>
        
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card border-primary h-100">
                    <div class="card-body text-center">
                        <div class="selection-icon">
                            <i class="fas fa-cog fa-2x text-primary"></i>
                        </div>
                        <h5 class="mt-3">Fresado</h5>
                        <p class="mb-0"><?= $millingCount; ?> pedidos en total</p>
                        <p class="mb-0"><?= $millingActive; ?> en proceso</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card border-danger h-100">
                    <div class="card-body text-center">
                        <div class="selection-icon">
                            <i class="fas fa-fire fa-2x text-danger"></i>
                        </div>
                        <h5 class="mt-3">Sinterizado</h5>
                        <p class="mb-0"><?= $sinteringCount; ?> pedidos en total</p>
                        <p class="mb-0"><?= $sinteringActive; ?> en proceso</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success h-100">
                    <div class="card-body text-center">
                        <div class="selection-icon">
                            <i class="fas fa-print fa-2x text-success"></i>
                        </div>
                        <h5 class="mt-3">Impresión 3D</h5>
                        <p class="mb-0"><?= $printingCount; ?> pedidos en total</p>
                        <p class="mb-0"><?= $printingActive; ?> en proceso</p>
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