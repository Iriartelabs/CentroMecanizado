<?php
/**
 * Script para actualizar el estado de un pedido
 * Usado por los técnicos para cambiar el estado de procesamiento
 */

// Incluir el archivo de configuración
require_once '../config.php';

// Verificar si el usuario ha iniciado sesión y es técnico
if (!isLoggedIn() || $_SESSION['user_role'] !== 'technician') {
    $_SESSION['alert_message'] = "No tienes permiso para realizar esta acción.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Verificar si se ha enviado el formulario mediante POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['alert_message'] = "Método no permitido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/dashboard.php');
}

// Obtener y validar datos del formulario
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? clean($_POST['status']) : '';
$notes = isset($_POST['notes']) ? clean($_POST['notes']) : '';
$notifyClient = isset($_POST['notify_client']);

// Validar los datos
if (!$orderId || empty($status)) {
    $_SESSION['alert_message'] = "Datos incompletos para actualizar el estado.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/dashboard.php');
}

// Verificar si el estado es válido
$validStatus = [
    ORDER_STATUS_PENDING,
    ORDER_STATUS_PROCESSING,
    ORDER_STATUS_COMPLETED,
    ORDER_STATUS_REJECTED
];

if (!in_array($status, $validStatus)) {
    $_SESSION['alert_message'] = "Estado no válido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/dashboard.php');
}

// Obtener el modelo de pedidos y el ID del usuario actual
$orderModel = new Order();
$userId = $_SESSION['user_id'];

// Obtener información del pedido actual para verificación
$currentOrder = $orderModel->getOrderById($orderId);

if (!$currentOrder) {
    $_SESSION['alert_message'] = "El pedido no existe o ha sido eliminado.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/dashboard.php');
}

// Si el estado es "Rechazado", verificar que se proporcionó un motivo
if ($status === ORDER_STATUS_REJECTED && empty($notes)) {
    $_SESSION['alert_message'] = "Debes proporcionar un motivo para rechazar el pedido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/order-details.php?id=' . $orderId);
}

// Verificar si ya está en ese estado (para evitar duplicados en el historial)
if ($currentOrder['status'] === $status) {
    $_SESSION['alert_message'] = "El pedido ya se encuentra en el estado " . getOrderStatusText($status) . ".";
    $_SESSION['alert_type'] = 'info';
    redirect(BASE_URL . 'technician/order-details.php?id=' . $orderId);
}

// Actualizar el estado del pedido
if ($orderModel->updateStatus($orderId, $status, $notes, $userId)) {
    // Registro de actividad para los técnicos
    logMessage("Técnico {$_SESSION['user_name']} (ID: {$userId}) cambió el estado del pedido #{$currentOrder['reference_number']} a " . getOrderStatusText($status), 'info');
    
    // Si se solicitó notificar al cliente, crear una notificación
    if ($notifyClient) {
        // Obtener datos del cliente
        $userModel = new User();
        $client = $userModel->findUserById($currentOrder['user_id']);
        
        if ($client) {
            // Crear una notificación en el sistema
            $title = "Actualización de estado - Pedido #{$currentOrder['reference_number']}";
            $message = "Tu pedido ha sido actualizado a estado: " . getOrderStatusText($status);
            
            if (!empty($notes)) {
                $message .= ". Notas: " . $notes;
            }
            
            // Insertar notificación en la base de datos
            $db = Database::getInstance();
            $db->query("INSERT INTO notifications (user_id, title, message, related_to, related_id) 
                         VALUES (:user_id, :title, :message, :related_to, :related_id)");
            
            $db->bind(':user_id', $client['id']);
            $db->bind(':title', $title);
            $db->bind(':message', $message);
            $db->bind(':related_to', 'order');
            $db->bind(':related_id', $orderId);
            $db->execute();
            
            // En un entorno de producción, aquí se enviaría el correo electrónico
            // Verificar si el cliente tiene habilitadas las notificaciones por email
            $userSettings = $userModel->getUserSettings($client['id']);
            
            if ($userSettings && $userSettings['email_notifications']) {
                // Enviar correo electrónico (simulado en este prototipo)
                logMessage("Se enviaría un correo electrónico a {$client['email']} sobre el cambio de estado del pedido #{$currentOrder['reference_number']}", 'info');
            }
        }
    }
    
    $_SESSION['alert_message'] = "Estado del pedido actualizado correctamente a " . getOrderStatusText($status) . ".";
    $_SESSION['alert_type'] = 'success';
    
    // Redirigir a la página de detalles del pedido
    redirect(BASE_URL . 'technician/order-details.php?id=' . $orderId);
} else {
    $_SESSION['alert_message'] = "Error al actualizar el estado del pedido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'technician/order-details.php?id=' . $orderId);
}
?>