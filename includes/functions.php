<?php
/**
 * QUICKORDER - Funciones Auxiliares
 * Funciones helper para uso en todo el sistema
 */

// Prevenir acceso directo
if (!defined('DB_HOST')) {
    die('Acceso directo no permitido');
}

/**
 * Formatear precio con símbolo de moneda
 */
function format_price($price) {
    return number_format($price, 2, ',', '.') . ' €';
}

/**
 * Sanitizar entrada de texto
 */
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Verificar si el usuario está logueado
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Verificar si el usuario es administrador
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redireccionar a una URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Obtener categorías activas
 * CORREGIDO: Usar is_active en lugar de active
 */
function get_active_categories() {
    global $conn;
    $query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order ASC, name ASC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Obtener categorías destacadas
 * CORREGIDO: Usar is_active e is_featured
 */
function get_featured_categories() {
    global $conn;
    $query = "SELECT * FROM categories WHERE is_active = 1 AND is_featured = 1 ORDER BY display_order ASC LIMIT 6";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Obtener productos por categoría
 * CORREGIDO: Usar is_available
 */
function get_products_by_category($category_id = null) {
    global $conn;
    
    if ($category_id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = ? AND p.is_available = 1 
                  ORDER BY p.name ASC";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $category_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.is_available = 1 
                  ORDER BY c.display_order ASC, p.name ASC";
        $result = mysqli_query($conn, $query);
    }
    
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Obtener productos destacados
 * CORREGIDO: Usar is_available e is_featured
 */
function get_featured_products($limit = 8) {
    global $conn;
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.is_available = 1 AND p.is_featured = 1 
              ORDER BY p.created_at DESC 
              LIMIT $limit";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Obtener producto por ID
 * CORREGIDO: Usar is_available
 */
function get_product_by_id($id) {
    global $conn;
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Obtener todos los productos
 */
function get_all_products() {
    global $conn;
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY c.display_order ASC, p.name ASC";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Buscar productos
 * CORREGIDO: Usar is_available
 */
function search_products($search_term) {
    global $conn;
    $search_term = '%' . $search_term . '%';
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE (p.name LIKE ? OR p.description LIKE ?) AND p.is_available = 1 
              ORDER BY p.name ASC";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

/**
 * Obtener cantidad de items en el carrito
 */
function get_cart_count() {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
    return 0;
}

/**
 * Calcular total del carrito
 */
function get_cart_total() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Obtener items del carrito con información completa
 */
function get_cart_items() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    global $conn;
    $cart_items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // Obtener información actualizada del producto
        $product = get_product_by_id($product_id);
        
        if ($product) {
            $cart_items[] = [
                'id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $item['quantity'],
                'subtotal' => $product['price'] * $item['quantity']
            ];
        }
    }
    
    return $cart_items;
}

/**
 * Generar número de pedido único
 */
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Generar número de reserva único
 */
function generate_reservation_number() {
    return 'RES-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Calcular impuesto (IVA)
 */
function calculate_tax($subtotal) {
    global $conn;
    $query = "SELECT setting_value FROM settings WHERE setting_key = 'tax_rate'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $tax_rate = $row ? floatval($row['setting_value']) : 21;
    return round($subtotal * ($tax_rate / 100), 2);
}

/**
 * Calcular tarifa de entrega
 */
function calculate_delivery_fee($subtotal) {
    global $conn;
    
    // Obtener configuración
    $query = "SELECT setting_key, setting_value FROM settings 
              WHERE setting_key IN ('delivery_fee', 'free_delivery_min')";
    $result = mysqli_query($conn, $query);
    
    $settings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $delivery_fee = floatval($settings['delivery_fee'] ?? 3.50);
    $free_delivery_min = floatval($settings['free_delivery_min'] ?? 20.00);
    
    // Si el subtotal es mayor que el mínimo para envío gratis
    if ($subtotal >= $free_delivery_min) {
        return 0;
    }
    
    return $delivery_fee;
}

/**
 * Obtener configuración del sitio
 */
function get_setting($key, $default = '') {
    global $conn;
    $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ? $row['setting_value'] : $default;
}

/**
 * Formatear fecha en español
 */
function format_date_es($date) {
    $timestamp = strtotime($date);
    $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
               'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    
    $day_name = $days[date('w', $timestamp)];
    $day = date('d', $timestamp);
    $month_name = $months[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    
    return "$day_name, $day de $month_name de $year";
}

/**
 * Validar email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validar teléfono
 */
function validate_phone($phone) {
    return preg_match('/^[\+]?[0-9\s\-]{9,15}$/', $phone);
}

/**
 * Encriptar contraseña
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar contraseña
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Subir imagen
 */
function upload_image($file, $upload_dir = 'uploads/products/') {
    // Verificar errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir el archivo'];
    }
    
    // Verificar tipo de archivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }
    
    // Verificar tamaño (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande (máx. 5MB)'];
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Mover archivo
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Error al guardar el archivo'];
    }
}

/**
 * Eliminar imagen
 */
function delete_image($filename, $upload_dir = 'uploads/products/') {
    $filepath = $upload_dir . $filename;
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Truncar texto
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

/**
 * Tiempo transcurrido en formato legible
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Hace unos segundos';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "Hace $minutes " . ($minutes == 1 ? 'minuto' : 'minutos');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "Hace $hours " . ($hours == 1 ? 'hora' : 'horas');
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "Hace $days " . ($days == 1 ? 'día' : 'días');
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Debug helper (solo en desarrollo)
 */
function debug($data, $die = false) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    if ($die) die();
}

/**
 * Alias de set_alert para compatibilidad
 */
function set_flash($type, $message) {
    set_alert($type, $message);
}

/**
 * Alias de get_alert para compatibilidad
 */
function get_flash() {
    return get_alert();
}

/**
 * Mostrar mensaje de alerta directamente
 */
function show_alert($type, $message) {
    echo '<div class="alert alert-' . $type . ' alert-dismissible">';
    echo '<div class="container">';
    echo '<span class="alert-message">' . htmlspecialchars($message) . '</span>';
    echo '<button class="alert-close" onclick="this.parentElement.parentElement.remove()">';
    echo '<i class="fas fa-times"></i>';
    echo '</button>';
    echo '</div>';
    echo '</div>';
}

/**
 * Mostrar alerta en sesión
 */
function set_alert($type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
}

/**
 * Obtener y limpiar alerta de sesión
 */
function get_alert() {
    if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
        $alert = [
            'type' => $_SESSION['alert_type'],
            'message' => $_SESSION['alert_message']
        ];
        unset($_SESSION['alert_type']);
        unset($_SESSION['alert_message']);
        return $alert;
    }
    return null;
}

?>
