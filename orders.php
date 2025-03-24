<?php
/**
 * Página de listado de pedidos
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (!isLoggedIn()) {
    $_SESSION['alert_message'] = "Debes iniciar sesión para ver tus pedidos.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Obtener el ID del usuario actual
$userId = $_SESSION['user_id'];

// Configuración de filtros y paginación
$status = isset($_GET['status']) ? clean($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Obtener los pedidos del usuario
$orderModel = new Order();
$orders = $orderModel->getUserOrders($userId, $status, $itemsPerPage, $offset);

// Para la paginación, necesitamos el total de pedidos
// Este es un ejemplo simple, en producción deberías implementar una función específica
$totalOrders = count($orderModel->getUserOrders($userId, $status, 1000, 0)); // Límite alto para obtener todos
$totalPages = ceil($totalOrders / $itemsPerPage);

// Establecer el título de la página
$page_title = 'Mis Pedidos';

// Incluir el encabezado
include_once 'includes/header.php';
?>

<!-- Título de la página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Mis Pedidos</h1>
    <a href="<?= BASE_URL; ?>new-order.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Nuevo Pedido
    </a>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Filtrar por estado</label>
                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="<?= ORDER_STATUS_NEW; ?>" <?= $status === ORDER_STATUS_NEW ? 'selected' : ''; ?>>Nuevos</option>
                    <option value="<?= ORDER_STATUS_PENDING; ?>" <?= $status === ORDER_STATUS_PENDING ? 'selected' : ''; ?>>Pendientes</option>
                    <option value="<?= ORDER_STATUS_PROCESSING; ?>" <?= $status === ORDER_STATUS_PROCESSING ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="<?= ORDER_STATUS_COMPLETED; ?>" <?= $status === ORDER_STATUS_COMPLETED ? 'selected' : ''; ?>>Completados</option>
                    <option value="<?= ORDER_STATUS_REJECTED; ?>" <?= $status === ORDER_STATUS_REJECTED ? 'selected' : ''; ?>>Rechazados</option>
                </select>
            </div>
            <?php if (!empty($status)): ?>
            <div class="col-md-2 d-flex align-items-end">
                <a href="<?= BASE_URL; ?>orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i> Limpiar filtros
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista de pedidos -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
        <div class="p-4 text-center">
            <p class="mb-0">No se encontraron pedidos<?= !empty($status) ? ' con el estado seleccionado' : ''; ?>.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Material</th>
                        <th>Archivos</th>
                        <th>Estado</th>
                        <th>Fecha Est.</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><a href="<?= BASE_URL; ?>order-details.php?id=<?= $order['id']; ?>" class="fw-bold">#<?= $order['reference_number']; ?></a></td>
                        <td><?= formatDateTime($order['created_at'], 'd/m/Y'); ?></td>
                        <td><?= getProcessTypeText($order['process_type']); ?></td>
                        <td><?= $order['material'] ?: '-'; ?></td>
                        <td><?= $order['file_count']; ?></td>
                        <td><span class="status-badge <?= getOrderStatusClass($order['status']); ?>"><?= getOrderStatusText($order['status']); ?></span></td>
                        <td><?= formatDateTime($order['estimated_completion_date'], 'd/m/Y'); ?></td>
                        <td>
                            <a href="<?= BASE_URL; ?>order-details.php?id=<?= $order['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($order['comment_count'] > 0): ?>
                            <span class="badge bg-info ms-1" data-bs-toggle="tooltip" title="<?= $order['comment_count']; ?> comentarios">
                                <i class="fas fa-comment"></i> <?= $order['comment_count']; ?>
                            </span>
                            <?php endif; ?>
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
                    <a class="page-link" href="<?= BASE_URL; ?>orders.php?page=<?= $page - 1; ?><?= !empty($status) ? '&status=' . $status : ''; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="<?= BASE_URL; ?>orders.php?page=<?= $i; ?><?= !empty($status) ? '&status=' . $status : ''; ?>"><?= $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= BASE_URL; ?>orders.php?page=<?= $page + 1; ?><?= !empty($status) ? '&status=' . $status : ''; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php
// Incluir el pie de página
include_once 'includes/footer.php';
?>