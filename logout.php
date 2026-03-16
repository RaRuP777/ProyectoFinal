<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Verificar que el usuario esté logueado
if (!is_logged_in()) {
    redirect(SITE_URL . '/login.php');
}

// Guardar nombre antes de destruir la sesión
$user_name = $_SESSION['name'] ?? 'Usuario';

// Destruir todas las variables de sesión
$_SESSION = array();

// Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Eliminar cookie de "recordarme"
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

// Destruir la sesión
session_destroy();

// Iniciar nueva sesión para mostrar mensaje
session_start();

// Mostrar mensaje de despedida
set_alert('success', '¡Hasta pronto, ' . $user_name . '!');

// Redirigir al inicio
redirect(SITE_URL . '/');
?>
