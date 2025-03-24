<?php
/**
 * Página para descargar archivos
 */

// Incluir el archivo de configuración
require_once 'config.php';

// Verificar si el usuario ha iniciado sesión
if (!isLoggedIn()) {
    $_SESSION['alert_message'] = "Debes iniciar sesión para descargar archivos.";
    $_SESSION['alert_type'] = 'warning';
    redirect(BASE_URL . 'login.php');
}

// Verificar si se ha proporcionado un ID de archivo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert_message'] = "ID de archivo no válido.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'orders.php');
}

$fileId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();

// Obtener información del archivo
$fileModel = new File();
$file = $fileModel->getFileById($fileId);

if (!$file) {
    $_SESSION['alert_message'] = "El archivo no existe.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'orders.php');
}

// Obtener información del pedido para verificar permisos
$orderModel = new Order();
$order = $orderModel->getOrderById($file['order_id']);

// Verificar si el usuario tiene permiso para descargar el archivo
if (!$order || (!$isAdmin && $order['user_id'] != $userId)) {
    $_SESSION['alert_message'] = "No tienes permiso para descargar este archivo.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'orders.php');
}

// Verificar si el archivo existe físicamente
if (!file_exists($file['file_path'])) {
    $_SESSION['alert_message'] = "El archivo no se encuentra en el servidor.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'order-details.php?id=' . $file['order_id']);
}

// Crear una copia temporal del archivo para descargar
$tempFile = $fileModel->createTemporaryFile($fileId);

if (!$tempFile) {
    $_SESSION['alert_message'] = "Error al preparar el archivo para descarga.";
    $_SESSION['alert_type'] = 'danger';
    redirect(BASE_URL . 'order-details.php?id=' . $file['order_id']);
}

// Configurar encabezados para la descarga
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFile));

// Limpiar el búfer de salida
ob_clean();
flush();

// Enviar el archivo al navegador
readfile($tempFile);

// Eliminar el archivo temporal
unlink($tempFile);

// Terminar la ejecución
exit;
?>