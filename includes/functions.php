<?php
/**
 * Funciones auxiliares para la aplicación
 */

/**
 * Redirecciona a una URL específica
 * @param string $url URL a la que redireccionar
 * @return void
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Limpia datos de entrada para prevenir XSS
 * @param string $data Datos a limpiar
 * @return string Datos limpios
 */
function clean($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Genera una cadena aleatoria
 * @param int $length Longitud de la cadena
 * @return string Cadena aleatoria
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Verifica si el usuario ha iniciado sesión
 * @return bool True si el usuario ha iniciado sesión
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica si el usuario es administrador
 * @return bool True si el usuario es administrador
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Muestra un mensaje de alerta
 * @param string $message Mensaje a mostrar
 * @param string $type Tipo de alerta (success, danger, warning, info)
 * @return string HTML de la alerta
 */
function showAlert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '">' . $message . '</div>';
}

/**
 * Formatea la fecha y hora
 * @param string $datetime Fecha y hora a formatear
 * @param string $format Formato deseado
 * @return string Fecha y hora formateada
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    $date = new DateTime($datetime);
    return $date->format($format);
}

/**
 * Formatea un número
 * @param float $number Número a formatear
 * @param int $decimals Número de decimales
 * @return string Número formateado
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Obtiene el estado de un pedido en formato legible
 * @param string $status Código de estado
 * @return string Estado en formato legible
 */
function getOrderStatusText($status) {
    $statusTexts = [
        ORDER_STATUS_NEW => 'Nuevo',
        ORDER_STATUS_PENDING => 'Pendiente',
        ORDER_STATUS_PROCESSING => 'En proceso',
        ORDER_STATUS_COMPLETED => 'Completado',
        ORDER_STATUS_REJECTED => 'Rechazado'
    ];
    
    return isset($statusTexts[$status]) ? $statusTexts[$status] : 'Desconocido';
}

/**
 * Obtiene la clase CSS para un estado de pedido
 * @param string $status Código de estado
 * @return string Clase CSS
 */
function getOrderStatusClass($status) {
    $statusClasses = [
        ORDER_STATUS_NEW => 'status-new',
        ORDER_STATUS_PENDING => 'status-pending',
        ORDER_STATUS_PROCESSING => 'status-processing',
        ORDER_STATUS_COMPLETED => 'status-completed',
        ORDER_STATUS_REJECTED => 'status-rejected'
    ];
    
    return isset($statusClasses[$status]) ? $statusClasses[$status] : '';
}

/**
 * Obtiene el tipo de proceso en formato legible
 * @param string $type Código de tipo de proceso
 * @return string Tipo de proceso en formato legible
 */
function getProcessTypeText($type) {
    $typeTexts = [
        PROCESS_TYPE_MILLING => 'Fresado',
        PROCESS_TYPE_SINTERING => 'Sinterizado',
        PROCESS_TYPE_PRINTING => 'Impresión 3D'
    ];
    
    return isset($typeTexts[$type]) ? $typeTexts[$type] : 'Desconocido';
}

/**
 * Verifica si una extensión de archivo está permitida
 * @param string $extension Extensión del archivo
 * @return bool True si la extensión está permitida
 */
function isAllowedFileType($extension) {
    return in_array(strtolower($extension), ALLOWED_FILE_TYPES);
}

/**
 * Registra un mensaje en el archivo de log
 * @param string $message Mensaje a registrar
 * @param string $type Tipo de mensaje (error, info, warning)
 * @return void
 */
function logMessage($message, $type = 'info') {
    $logFile = ROOT_PATH . 'logs/' . $type . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[$timestamp] $message" . PHP_EOL;
    
    // Crear directorio si no existe
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}
