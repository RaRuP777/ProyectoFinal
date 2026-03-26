<?php
// ============================================================
//  QuickOrder — includes/functions.php
//  CORREGIDO: number_format() con null — PHP 8.1+ compatible
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Formatear precio (nunca recibe null) ─────────────────────
function format_price($amount, int $decimals = 2, string $symbol = '€'): string {
    return $symbol . number_format((float)($amount ?? 0), $decimals, ',', '.');
}

// ── Formatear precio sin símbolo ─────────────────────────────
function format_amount($amount, int $decimals = 2): string {
    return number_format((float)($amount ?? 0), $decimals, ',', '.');
}

// ── Verificar si el usuario ha iniciado sesión ───────────────
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ── Verificar si es administrador ───────────────────────────
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role'])
           && $_SESSION['user_role'] === 'admin';
}

// ── Obtener ID del usuario actual ────────────────────────────
function get_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

// ── Redirigir a login si no autenticado ──────────────────────
function require_login(string $redirect = ''): void {
    if (!is_logged_in()) {
        $back = $redirect ?: $_SERVER['REQUEST_URI'] ?? '';
        $qs   = $back ? '?redirect=' . urlencode($back) : '';
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/login.php' . $qs);
        exit;
    }
}

// ── Redirigir si NO es admin ──────────────────────────────────
function require_admin(): void {
    if (!is_admin()) {
        header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/index.php');
        exit;
    }
}

// ── Número de items en el carrito ────────────────────────────
function get_cart_count(): int {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
    return (int)array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// ── Guardar alerta en sesión ─────────────────────────────────
function set_alert(string $type, string $message): void {
    $_SESSION['alert'] = ['type' => $type, 'message' => $message];
}

// ── Obtener y limpiar alerta ─────────────────────────────────
function get_alert(): ?array {
    if (!isset($_SESSION['alert'])) return null;
    $alert = $_SESSION['alert'];
    unset($_SESSION['alert']);
    return $alert;
}

// ── Sanitizar entrada de usuario ─────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ── Generar número de pedido único ───────────────────────────
function generate_order_number(): string {
    return 'ORD-' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

// ── Generar número de reserva único ─────────────────────────
function generate_reservation_number(): string {
    return 'RES-' . date('Ymd') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

// ── Etiqueta de estado de pedido ─────────────────────────────
function order_status_badge(string $status): string {
    $map = [
        'pending'    => ['label' => 'Pendiente',   'color' => '#FFC107', 'bg' => '#fffbeb'],
        'confirmed'  => ['label' => 'Confirmado',  'color' => '#2196F3', 'bg' => '#eff6ff'],
        'preparing'  => ['label' => 'Preparando',  'color' => '#FF9800', 'bg' => '#fff7ed'],
        'ready'      => ['label' => 'Listo',        'color' => '#8B5CF6', 'bg' => '#f5f3ff'],
        'delivering' => ['label' => 'En camino',   'color' => '#3B82F6', 'bg' => '#eff6ff'],
        'delivered'  => ['label' => 'Entregado',   'color' => '#16a34a', 'bg' => '#f0fdf4'],
        'cancelled'  => ['label' => 'Cancelado',   'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f3f4f6'];
    return "<span style=\"display:inline-block;padding:.2rem .75rem;border-radius:99px;
            font-size:.8rem;font-weight:700;background:{$s['bg']};color:{$s['color']};
            border:1px solid {$s['color']}33;\">{$s['label']}</span>";
}

// ── Etiqueta de estado de pago ────────────────────────────────
function payment_status_badge(string $status): string {
    $map = [
        'pending'  => ['label' => 'Pendiente', 'color' => '#d97706', 'bg' => '#fffbeb'],
        'paid'     => ['label' => 'Pagado',    'color' => '#16a34a', 'bg' => '#f0fdf4'],
        'failed'   => ['label' => 'Fallido',   'color' => '#dc2626', 'bg' => '#fef2f2'],
        'refunded' => ['label' => 'Reembolso', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f3f4f6'];
    return "<span style=\"display:inline-block;padding:.2rem .75rem;border-radius:99px;
            font-size:.8rem;font-weight:700;background:{$s['bg']};color:{$s['color']};
            border:1px solid {$s['color']}33;\">{$s['label']}</span>";
}

// ── Tiempo relativo (hace X minutos/horas) ───────────────────
function time_ago(string $datetime): string {
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->days > 30)  return $then->format('d/m/Y');
    if ($diff->days >= 1)  return "hace {$diff->days} día" . ($diff->days > 1 ? 's' : '');
    if ($diff->h >= 1)     return "hace {$diff->h} hora"  . ($diff->h > 1 ? 's' : '');
    if ($diff->i >= 1)     return "hace {$diff->i} min";
    return "ahora mismo";
}

// ── Subir imagen (productos / categorías) ────────────────────
function upload_image(array $file, string $folder = 'products'): string|false {
    $allowed   = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    $max_size  = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK)       return false;
    if (!in_array($file['type'], $allowed))       return false;
    if ($file['size'] > $max_size)                return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . strtolower($ext);
    $dir      = __DIR__ . '/../uploads/' . $folder . '/';

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    return move_uploaded_file($file['tmp_name'], $dir . $filename) ? $filename : false;
}

// ── Paginar resultados ────────────────────────────────────────
function paginate(int $total, int $per_page, int $current): array {
    $pages      = (int)ceil($total / max(1, $per_page));
    $current    = max(1, min($current, $pages));
    $offset     = ($current - 1) * $per_page;
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'current'     => $current,
        'pages'       => $pages,
        'offset'      => $offset,
        'has_prev'    => $current > 1,
        'has_next'    => $current < $pages,
        'prev'        => $current - 1,
        'next'        => $current + 1,
    ];
}

function get_setting(string $key, $default = null) {
    global $conn;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        return $default;
    }

    $key_escaped = mysqli_real_escape_string($conn, $key);
    $sql = "SELECT setting_value FROM settings WHERE setting_key = '$key_escaped' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }

    return $default;
}