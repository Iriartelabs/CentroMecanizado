<?php
/**
 * Archivo de configuración principal
 * Carga las configuraciones necesarias para la aplicación
 */

// Sesión
session_start();

// Configuración de errores (ajustar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivos de configuración
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/settings.php';

// Incluir funciones auxiliares
require_once 'includes/functions.php';

// Incluir clases principales
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Order.php';
require_once 'classes/File.php';

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');