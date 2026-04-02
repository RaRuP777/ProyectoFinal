<?php
// ============================================================
// QuickOrder — includes/functions.php
// Versión completa de helpers para evitar errores de funciones
// faltantes en login, logout, contact, reservations, cart,
// my-orders, admin y otras páginas del proyecto.
// Compatible con PHP 8+
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// HELPERS BÁSICOS
// ============================================================

if (!function_exists('site_url')) {
    function site_url(string $path = ''): string {
        $base = defined('SITE_URL') ? rtrim((string)SITE_URL, '/') : '';
        $path = trim($path);

        if ($path === '') {
            return $base !== '' ? $base : '/';
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $base . $path;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path = ''): void {
        header('Location: ' . site_url($path));
        exit;
    }
}

if (!function_exists('sanitize')) {
    function sanitize(?string $input): string {
        return htmlspecialchars(trim((string)($input ?? '')), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('request_method_is')) {
    function request_method_is(string $method): bool {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '') {
        if (isset($_POST[$key])) {
            if (is_array($_POST[$key])) {
                return $_POST[$key];
            }
            return htmlspecialchars((string)$_POST[$key], ENT_QUOTES, 'UTF-8');
        }
        return $default;
    }
}

// ============================================================
// VALIDACIÓN
// ============================================================

if (!function_exists('validate_required')) {
    function validate_required($value): bool {
        if (is_array($value)) {
            return !empty($value);
        }
        return trim((string)($value ?? '')) !== '';
    }
}

if (!function_exists('validate_email')) {
    function validate_email(?string $email): bool {
        if ($email === null) {
            return false;
        }
        $email = trim($email);
        if ($email === '') {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('validate_min_length')) {
    function validate_min_length(?string $value, int $min = 1): bool {
        return mb_strlen(trim((string)($value ?? ''))) >= $min;
    }
}

if (!function_exists('validate_max_length')) {
    function validate_max_length(?string $value, int $max = 255): bool {
        return mb_strlen(trim((string)($value ?? ''))) <= $max;
    }
}

if (!function_exists('validate_numeric')) {
    function validate_numeric($value): bool {
        return is_numeric($value);
    }
}

if (!function_exists('validate_phone')) {
    function validate_phone(?string $phone): bool {
        if ($phone === null) {
            return false;
        }
        $phone = trim($phone);
        if ($phone === '') {
            return false;
        }
        return preg_match('/^[0-9+\-\s()]{6,20}$/', $phone) === 1;
    }
}

if (!function_exists('validate_password')) {
    function validate_password(?string $password, int $min = 4): bool {
        return validate_min_length($password, $min);
    }
}

// ============================================================
// AUTENTICACIÓN / SESIÓN
// ============================================================

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('get_user_id')) {
    function get_user_id(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }
}

if (!function_exists('get_user_name')) {
    function get_user_name(): string {
        return (string)($_SESSION['user_name'] ?? '');
    }
}

if (!function_exists('get_user_email')) {
    function get_user_email(): string {
        return (string)($_SESSION['user_email'] ?? '');
    }
}

if (!function_exists('require_login')) {
    function require_login(string $redirectTo = 'login.php'): void {
        if (!is_logged_in()) {
            set_alert('error', 'Debes iniciar sesión para continuar.');
            redirect($redirectTo);
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void {
        if (!is_admin()) {
            set_alert('error', 'No tienes permisos para acceder a esta página.');
            redirect('index.php');
        }
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

// ============================================================
// ALERTAS FLASH
// ============================================================

if (!function_exists('set_alert')) {
    function set_alert(string $type, string $message): void {
        $_SESSION['alert'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('get_alert')) {
    function get_alert(): ?array {
        if (!isset($_SESSION['alert']) || !is_array($_SESSION['alert'])) {
            return null;
        }

        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
}

// ============================================================
// SETTINGS / CONFIGURACIÓN
// ============================================================

if (!function_exists('get_setting')) {
    function get_setting(string $key, $default = null) {
        global $conn;

        if (!isset($conn) || !($conn instanceof mysqli)) {
            return $default;
        }

        $safeKey = mysqli_real_escape_string($conn, $key);
        $sql = "SELECT setting_value FROM settings WHERE setting_key = '$safeKey' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['setting_value'];
        }

        return $default;
    }
}

if (!function_exists('setting')) {
    function setting(string $key, $default = null) {
        return get_setting($key, $default);
    }
}

// ============================================================
// FORMATEO
// ============================================================

if (!function_exists('format_amount')) {
    function format_amount($amount, int $decimals = 2): string {
        return number_format((float)($amount ?? 0), $decimals, ',', '.');
    }
}

if (!function_exists('format_price')) {
    function format_price($amount, int $decimals = 2, ?string $symbol = null): string {
        $symbol = $symbol ?? (string)get_setting('currency_symbol', '€');
        return $symbol . format_amount($amount, $decimals);
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y'): string {
        if (empty($date)) {
            return '-';
        }
        $time = strtotime($date);
        if ($time === false) {
            return '-';
        }
        return date($format, $time);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $date, string $format = 'd/m/Y H:i'): string {
        return format_date($date, $format);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $datetime): string {
        if (empty($datetime)) {
            return '-';
        }

        try {
            $now = new DateTime();
            $then = new DateTime($datetime);
            $diff = $now->diff($then);

            if ($diff->days > 30) {
                return $then->format('d/m/Y');
            }
            if ($diff->days >= 1) {
                return 'hace ' . $diff->days . ' día' . ($diff->days > 1 ? 's' : '');
            }
            if ($diff->h >= 1) {
                return 'hace ' . $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
            }
            if ($diff->i >= 1) {
                return 'hace ' . $diff->i . ' min';
            }
            return 'ahora mismo';
        } catch (Exception $e) {
            return '-';
        }
    }
}

// ============================================================
// CARRITO
// ============================================================

if (!function_exists('ensure_cart_exists')) {
    function ensure_cart_exists(): void {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
}

if (!function_exists('get_cart')) {
    function get_cart(): array {
        ensure_cart_exists();
        return $_SESSION['cart'];
    }
}

if (!function_exists('get_cart_count')) {
    function get_cart_count(): int {
        ensure_cart_exists();
        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += (int)($item['quantity'] ?? 0);
        }
        return $count;
    }
}

if (!function_exists('get_cart_subtotal')) {
    function get_cart_subtotal(): float {
        ensure_cart_exists();
        $subtotal = 0.0;
        foreach ($_SESSION['cart'] as $item) {
            $price = (float)($item['price'] ?? 0);
            $qty   = (int)($item['quantity'] ?? 0);
            $subtotal += $price * $qty;
        }
        return $subtotal;
    }
}

if (!function_exists('get_cart_total')) {
    function get_cart_total(): float {
        // Compatibilidad con páginas antiguas como checkout.php
        // En esta base del proyecto, el total del carrito equivale
        // al subtotal de líneas; envío/impuestos se calculan aparte.
        return get_cart_subtotal();
    }
}

if (!function_exists('get_cart_items')) {
    function get_cart_items(): array {
        return get_cart();
    }
}

if (!function_exists('has_cart_items')) {
    function has_cart_items(): bool {
        return get_cart_count() > 0;
    }
}

if (!function_exists('get_delivery_fee')) {
    function get_delivery_fee(): float {
        return (float)get_setting('delivery_fee', 2.50);
    }
}

if (!function_exists('get_free_delivery_from')) {
    function get_free_delivery_from(): float {
        return (float)get_setting('free_delivery_from', 25.00);
    }
}

if (!function_exists('get_min_order_amount')) {
    function get_min_order_amount(): float {
        return (float)get_setting('min_order_amount', 10.00);
    }
}

if (!function_exists('get_cart_delivery_fee')) {
    function get_cart_delivery_fee(): float {
        $subtotal = get_cart_subtotal();
        if ($subtotal <= 0) {
            return 0.0;
        }
        $freeFrom = get_free_delivery_from();
        return $subtotal >= $freeFrom ? 0.0 : get_delivery_fee();
    }
}

if (!function_exists('get_tax_rate')) {
    function get_tax_rate(): float {
        return ((float)get_setting('tax_rate', 10)) / 100;
    }
}

if (!function_exists('get_cart_tax_amount')) {
    function get_cart_tax_amount(bool $includeDelivery = true): float {
        $base = get_cart_subtotal();
        if ($includeDelivery) {
            $base += get_cart_delivery_fee();
        }
        return $base * get_tax_rate();
    }
}

if (!function_exists('get_cart_grand_total')) {
    function get_cart_grand_total(bool $includeTax = false): float {
        $total = get_cart_subtotal() + get_cart_delivery_fee();
        if ($includeTax) {
            $total += get_cart_tax_amount(true);
        }
        return $total;
    }
}

if (!function_exists('clear_cart')) {
    function clear_cart(): void {
        $_SESSION['cart'] = [];
    }
}

// ============================================================
// PEDIDOS / RESERVAS
// ============================================================

if (!function_exists('generate_order_number')) {
    function generate_order_number(): string {
        return 'ORD-' . date('Ymd') . '-' . str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generate_reservation_number')) {
    function generate_reservation_number(): string {
        return 'RES-' . date('Ymd') . '-' . str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('order_status_badge')) {
    function order_status_badge(string $status): string {
        $map = [
            'pending'    => ['label' => 'Pendiente',   'color' => '#FFC107', 'bg' => '#fffbeb'],
            'confirmed'  => ['label' => 'Confirmado',  'color' => '#2196F3', 'bg' => '#eff6ff'],
            'preparing'  => ['label' => 'Preparando',  'color' => '#FF9800', 'bg' => '#fff7ed'],
            'ready'      => ['label' => 'Listo',       'color' => '#8B5CF6', 'bg' => '#f5f3ff'],
            'delivering' => ['label' => 'En camino',   'color' => '#3B82F6', 'bg' => '#eff6ff'],
            'delivered'  => ['label' => 'Entregado',   'color' => '#16a34a', 'bg' => '#f0fdf4'],
            'cancelled'  => ['label' => 'Cancelado',   'color' => '#dc2626', 'bg' => '#fef2f2'],
        ];

        $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f3f4f6'];

        return '<span style="display:inline-block;padding:.2rem .75rem;border-radius:99px;font-size:.8rem;font-weight:700;background:'
            . $s['bg'] . ';color:' . $s['color'] . ';border:1px solid ' . $s['color'] . '33;">'
            . $s['label'] . '</span>';
    }
}

if (!function_exists('payment_status_badge')) {
    function payment_status_badge(string $status): string {
        $map = [
            'pending'  => ['label' => 'Pendiente', 'color' => '#d97706', 'bg' => '#fffbeb'],
            'paid'     => ['label' => 'Pagado',    'color' => '#16a34a', 'bg' => '#f0fdf4'],
            'failed'   => ['label' => 'Fallido',   'color' => '#dc2626', 'bg' => '#fef2f2'],
            'refunded' => ['label' => 'Reembolsado', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        ];

        $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f3f4f6'];

        return '<span style="display:inline-block;padding:.2rem .75rem;border-radius:99px;font-size:.8rem;font-weight:700;background:'
            . $s['bg'] . ';color:' . $s['color'] . ';border:1px solid ' . $s['color'] . '33;">'
            . $s['label'] . '</span>';
    }
}

if (!function_exists('reservation_status_badge')) {
    function reservation_status_badge(string $status): string {
        $map = [
            'pending'   => ['label' => 'Pendiente',  'color' => '#d97706', 'bg' => '#fffbeb'],
            'confirmed' => ['label' => 'Confirmada', 'color' => '#16a34a', 'bg' => '#f0fdf4'],
            'cancelled' => ['label' => 'Cancelada',  'color' => '#dc2626', 'bg' => '#fef2f2'],
            'completed' => ['label' => 'Completada', 'color' => '#2563eb', 'bg' => '#eff6ff'],
        ];

        $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f3f4f6'];

        return '<span style="display:inline-block;padding:.2rem .75rem;border-radius:99px;font-size:.8rem;font-weight:700;background:'
            . $s['bg'] . ';color:' . $s['color'] . ';border:1px solid ' . $s['color'] . '33;">'
            . $s['label'] . '</span>';
    }
}

// ============================================================
// BASE DE DATOS — HELPERS ÚTILES
// ============================================================

if (!function_exists('db_escape')) {
    function db_escape(string $value): string {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            return addslashes($value);
        }
        return mysqli_real_escape_string($conn, $value);
    }
}

if (!function_exists('db_query')) {
    function db_query(string $sql) {
        global $conn;
        if (!isset($conn) || !($conn instanceof mysqli)) {
            return false;
        }
        return mysqli_query($conn, $sql);
    }
}

if (!function_exists('db_one')) {
    function db_one(string $sql): ?array {
        $result = db_query($sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        return null;
    }
}

if (!function_exists('db_all')) {
    function db_all(string $sql): array {
        $result = db_query($sql);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}

if (!function_exists('db_value')) {
    function db_value(string $sql, $default = null) {
        $result = db_query($sql);
        if ($result) {
            $row = mysqli_fetch_row($result);
            return $row[0] ?? $default;
        }
        return $default;
    }
}

// ============================================================
// SUBIDA DE IMÁGENES
// ============================================================

if (!function_exists('upload_image')) {
    function upload_image(array $file, string $folder = 'products') {
        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return false;
        }

        if (!in_array($file['type'] ?? '', $allowed, true)) {
            return false;
        }

        if (($file['size'] ?? 0) > $maxSize) {
            return false;
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === '') {
            $ext = 'jpg';
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $dir = __DIR__ . '/../uploads/' . trim($folder, '/') . '/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $target = $dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $filename;
        }

        return false;
    }
}

// ============================================================
// PAGINACIÓN
// ============================================================

if (!function_exists('paginate')) {
    function paginate(int $total, int $perPage, int $currentPage): array {
        $perPage = max(1, $perPage);
        $pages = max(1, (int)ceil($total / $perPage));
        $currentPage = max(1, min($currentPage, $pages));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'total' => $total,
            'per_page' => $perPage,
            'current' => $currentPage,
            'pages' => $pages,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $pages,
            'prev' => max(1, $currentPage - 1),
            'next' => min($pages, $currentPage + 1),
        ];
    }
}

// ============================================================
// CSRF BÁSICO (OPCIONAL)
// ============================================================

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool {
        return isset($_SESSION['_csrf_token']) && is_string($token)
            && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

// ============================================================
// DEBUG (solo desarrollo)
// ============================================================

if (!function_exists('dd')) {
    function dd(...$vars): void {
        echo '<pre style="background:#111;color:#0f0;padding:1rem;border-radius:8px;overflow:auto;">';
        foreach ($vars as $var) {
            var_dump($var);
            echo "\n";
        }
        echo '</pre>';
        exit;
    }
}
