<?php
/**
 * Configuraciones generales de la aplicación
 */

// Configuración de la aplicación
$app_settings = [
    'name' => 'DentalMEC',
    'company' => 'Centro de Mecanizado Dental',
    'email' => 'contacto@example.com',
    'phone' => '+34 123 456 789',
    'address' => 'Calle Principal, 123, 28001 Madrid',
    'logo' => 'assets/images/logo.png',
    'favicon' => 'assets/images/favicon.ico',
    'version' => '1.0.0',
];

// Configuración de correo electrónico
$mail_settings = [
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'notificaciones@example.com',
    'password' => 'tu_contraseña',
    'encryption' => 'tls',
    'from_name' => 'DentalMEC',
    'from_email' => 'notificaciones@example.com',
];

// Configuración de notificaciones
$notification_settings = [
    'order_created' => true,      // Notificar cuando se crea un nuevo pedido
    'order_status_changed' => true, // Notificar cuando cambia el estado de un pedido
    'order_completed' => true,    // Notificar cuando se completa un pedido
    'order_rejected' => true,     // Notificar cuando se rechaza un pedido
    'comment_added' => true,      // Notificar cuando se añade un comentario a un pedido
];

// Tiempos de procesamiento estimados (en horas)
$processing_times = [
    PROCESS_TYPE_MILLING => 24,     // Tiempo estimado para fresado
    PROCESS_TYPE_SINTERING => 48,   // Tiempo estimado para sinterizado
    PROCESS_TYPE_PRINTING => 12,    // Tiempo estimado para impresión 3D
];

// Hacer que las configuraciones sean accesibles globalmente
$GLOBALS['app_settings'] = $app_settings;
$GLOBALS['mail_settings'] = $mail_settings;
$GLOBALS['notification_settings'] = $notification_settings;
$GLOBALS['processing_times'] = $processing_times;
