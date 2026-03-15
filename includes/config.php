<?php
/**
 * QUICKORDER - Archivo de Configuración
 * Conexión a base de datos y constantes del sistema
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =============================================
// CONFIGURACIÓN DE BASE DE DATOS
// =============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quickorder');

// =============================================
// CONFIGURACIÓN DEL SITIO
// =============================================
define('SITE_NAME', 'QuickOrder');
define('SITE_URL', 'http://localhost/QuickOrder');
define('SITE_EMAIL', 'info@quickorder.com');
define('SITE_PHONE', '+34 900 000 000');

// =============================================
// CONEXIÓN A LA BASE DE DATOS
// =============================================
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if (!$conn) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Configurar charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// =============================================
// CONFIGURACIÓN DE ERRORES (DESARROLLO)
// =============================================
// Descomentar en producción:
// error_reporting(0);
// ini_set('display_errors', 0);

// Para desarrollo (mostrar errores):
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// ZONA HORARIA
// =============================================
date_default_timezone_set('Europe/Madrid');
?>
