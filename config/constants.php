<?php
/**
 * Constantes de la aplicación
 */

// Rutas de la aplicación
define('BASE_URL', 'http://localhost/camteks/');
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/camteks/');

// Rutas para archivos de cliente
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('TEMP_UPLOAD_PATH', UPLOAD_PATH . 'temp/');

// Ruta al servidor de archivos externo
define('FILE_SERVER_PATH', 'C:/servidor_archivos/'); // Ajustar a la ruta real del servidor de archivos

// Estados de pedidos
define('ORDER_STATUS_NEW', 'new');
define('ORDER_STATUS_PENDING', 'pending');
define('ORDER_STATUS_PROCESSING', 'processing');
define('ORDER_STATUS_COMPLETED', 'completed');
define('ORDER_STATUS_REJECTED', 'rejected');

// Tipos de procesos
define('PROCESS_TYPE_MILLING', 'milling');     // Fresado
define('PROCESS_TYPE_SINTERING', 'sintering'); // Sinterizado
define('PROCESS_TYPE_PRINTING', 'printing');   // Impresión 3D

// Límites de archivos
define('MAX_FILE_SIZE', 200 * 1024 * 1024); // 200MB en bytes
define('ALLOWED_FILE_TYPES', ['stl', 'obj', 'dcm', 'constructioninfo', 'zip', 'rar']); // Extensiones permitidas
