<?php
if (!function_exists('admin_sync_user_role')) {
    function admin_sync_user_role(): void {
        if (!is_logged_in()) {
            return;
        }

        $userId = get_user_id();
        if ($userId <= 0) {
            return;
        }

        $role = db_value("SELECT role FROM users WHERE id = " . (int)$userId . " LIMIT 1", $_SESSION['user_role'] ?? 'customer');
        $_SESSION['user_role'] = (string)($role ?: 'customer');
    }
}

if (!function_exists('admin_status_options')) {
    function admin_status_options(array $options, string $selected = ''): string {
        $html = '';
        foreach ($options as $value => $label) {
            $html .= '<option value="' . e((string)$value) . '"' . ((string)$value === (string)$selected ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        return $html;
    }
}

if (!function_exists('admin_sidebar_link')) {
    function admin_sidebar_link(string $href, string $icon, string $label, string $activeKey, string $currentKey): string {
        $activeClass = $activeKey === $currentKey ? 'active' : '';
        return '<a class="admin-side-link ' . $activeClass . '" href="' . e($href) . '"><i class="fas ' . e($icon) . '"></i><span>' . e($label) . '</span></a>';
    }
}

if (!function_exists('admin_render_start')) {
    function admin_render_start(string $pageTitle = 'Panel de administración', string $activeKey = 'dashboard'): void {
        $siteName = (string)get_setting('site_name', 'QuickOrder');
        $userName = get_user_name() ?: 'Administrador';
        $alert = get_alert();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle . ' - ' . $siteName); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/img/logo-quickorder.png">
    <style>
        :root {
            --admin-bg: #fff7f0;
            --admin-surface: #ffffff;
            --admin-border: #ffd9c7;
            --admin-text: #2b2b2b;
            --admin-muted: #6b7280;
            --admin-primary: #ff6b35;
            --admin-primary-dark: #e55a27;
            --admin-secondary: #fff1e8;
            --admin-success: #16a34a;
            --admin-warning: #d97706;
            --admin-danger: #dc2626;
            --admin-info: #2563eb;
            --admin-shadow: 0 18px 40px rgba(255, 107, 53, .10);
        }
        body.admin-body { background: linear-gradient(180deg, #fff8f2 0%, #fff 100%); color: var(--admin-text); }
        .admin-topbar {
            position: sticky; top: 0; z-index: 50; backdrop-filter: blur(14px);
            background: rgba(255,255,255,.92); border-bottom: 1px solid rgba(255,107,53,.12);
        }
        .admin-topbar-inner {
            max-width: 1400px; margin: 0 auto; padding: 1rem 1.5rem; display: flex;
            align-items: center; justify-content: space-between; gap: 1rem;
        }
        .admin-brand { display:flex; align-items:center; gap:.9rem; text-decoration:none; color:inherit; }
        .admin-brand img { width:48px; height:48px; object-fit:contain; border-radius:14px; background:#fff; box-shadow:0 10px 24px rgba(255,107,53,.16); }
        .admin-brand small { display:block; color:var(--admin-muted); font-size:.82rem; }
        .admin-brand strong { display:block; font-size:1.05rem; }
        .admin-top-actions { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
        .admin-user-chip {
            display:flex; align-items:center; gap:.6rem; padding:.7rem 1rem; border-radius:999px;
            background:var(--admin-secondary); color:#8a3c1f; font-weight:700; border:1px solid #ffd8c7;
        }
        .admin-shell {
            max-width: 1400px; margin: 0 auto; padding: 1.5rem; display:grid; grid-template-columns: 280px 1fr; gap:1.5rem;
        }
        .admin-sidebar {
            background: var(--admin-surface); border:1px solid var(--admin-border); box-shadow: var(--admin-shadow);
            border-radius: 24px; padding: 1rem; height: fit-content; position: sticky; top: 96px;
        }
        .admin-sidebar-title { font-size:.9rem; text-transform:uppercase; letter-spacing:.08em; color:var(--admin-muted); margin:.4rem .75rem 1rem; }
        .admin-side-link {
            display:flex; align-items:center; gap:.85rem; padding:.95rem 1rem; border-radius:16px;
            text-decoration:none; color:#374151; font-weight:700; transition:all .25s ease; margin-bottom:.45rem;
        }
        .admin-side-link i { width:20px; text-align:center; color:var(--admin-primary); }
        .admin-side-link:hover { background:#fff4ed; transform: translateX(4px); }
        .admin-side-link.active { background: linear-gradient(135deg, var(--admin-primary), #ff8b61); color:#fff; box-shadow:0 12px 26px rgba(255,107,53,.24); }
        .admin-side-link.active i { color:#fff; }
        .admin-main { min-width:0; }
        .admin-page-header {
            display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;
            margin-bottom: 1.25rem;
        }
        .admin-page-header h1 { margin:0; font-size: clamp(1.7rem, 2vw, 2.4rem); }
        .admin-page-header p { margin:.35rem 0 0; color:var(--admin-muted); }
        .admin-grid { display:grid; gap:1rem; }
        .admin-grid.cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .admin-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .admin-card {
            background:var(--admin-surface); border:1px solid var(--admin-border); border-radius:24px;
            padding:1.25rem; box-shadow:var(--admin-shadow);
        }
        .admin-card h2, .admin-card h3 { margin-top:0; }
        .admin-stat {
            background: linear-gradient(135deg, #fff 0%, #fff6f1 100%); border:1px solid var(--admin-border);
            border-radius:22px; padding:1.25rem; box-shadow:var(--admin-shadow);
        }
        .admin-stat .value { font-size:2rem; font-weight:800; color:#111827; }
        .admin-stat .label { color:var(--admin-muted); font-weight:600; }
        .admin-stat .icon {
            width:52px; height:52px; border-radius:16px; display:grid; place-items:center; font-size:1.2rem;
            background:#fff1e8; color:var(--admin-primary); margin-bottom: .9rem;
        }
        .admin-form-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:1rem; }
        .admin-form-group { display:flex; flex-direction:column; gap:.45rem; }
        .admin-form-group.full { grid-column:1 / -1; }
        .admin-form-group label { font-weight:700; color:#374151; }
        .admin-form-group input,
        .admin-form-group select,
        .admin-form-group textarea {
            width:100%; border:1px solid #ffd8c7; border-radius:14px; padding:.9rem 1rem; background:#fff;
            font:inherit; color:#111827;
        }
        .admin-form-group textarea { min-height:120px; resize:vertical; }
        .admin-form-actions { display:flex; gap:.75rem; flex-wrap:wrap; margin-top:1rem; }
        .admin-btn {
            display:inline-flex; align-items:center; justify-content:center; gap:.55rem; text-decoration:none;
            border:none; border-radius:14px; padding:.9rem 1.15rem; font-weight:700; cursor:pointer;
        }
        .admin-btn.primary { background:var(--admin-primary); color:#fff; }
        .admin-btn.primary:hover { background:var(--admin-primary-dark); }
        .admin-btn.secondary { background:#fff3eb; color:#8a3c1f; border:1px solid #ffd6c4; }
        .admin-btn.success { background:var(--admin-success); color:#fff; }
        .admin-btn.warning { background:var(--admin-warning); color:#fff; }
        .admin-btn.danger { background:var(--admin-danger); color:#fff; }
        .admin-btn.light { background:#fff; color:#374151; border:1px solid #e5e7eb; }
        .admin-table-wrap { overflow:auto; }
        .admin-table { width:100%; border-collapse:collapse; min-width: 760px; }
        .admin-table th, .admin-table td { padding:1rem .85rem; border-bottom:1px solid #f3e0d6; vertical-align:top; }
        .admin-table th { text-align:left; font-size:.88rem; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; }
        .admin-table tr:hover td { background:#fffaf7; }
        .admin-thumb { width:68px; height:68px; border-radius:16px; object-fit:cover; background:#fff3eb; border:1px solid #ffe0d1; }
        .admin-empty { padding:2rem; text-align:center; color:var(--admin-muted); }
        .admin-inline-form { display:flex; gap:.5rem; align-items:center; flex-wrap:wrap; }
        .admin-inline-form select { min-width: 170px; }
        .admin-note { color:var(--admin-muted); font-size:.92rem; }
        .admin-kpi-list { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:1rem; }
        .admin-highlight {
            background: linear-gradient(135deg, rgba(255,107,53,.12), rgba(255,171,79,.12));
            border:1px solid rgba(255,107,53,.18); border-radius:20px; padding:1rem;
        }
        .admin-pill {
            display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .75rem; border-radius:999px;
            background:#fff3eb; color:#9a3412; font-weight:700; font-size:.85rem;
        }
        .admin-search-row {
            display:flex; gap:.75rem; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:1rem;
        }
        @media (max-width: 1100px) {
            .admin-shell { grid-template-columns: 1fr; }
            .admin-sidebar { position: static; }
            .admin-grid.cols-2, .admin-grid.cols-3, .admin-kpi-list, .admin-form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .admin-topbar-inner, .admin-shell { padding: 1rem; }
            .admin-page-header { align-items:stretch; }
            .admin-inline-form { flex-direction:column; align-items:stretch; }
        }
    </style>
</head>
<body class="admin-body">
    <header class="admin-topbar">
        <div class="admin-topbar-inner">
            <a class="admin-brand" href="/admin/index.php">
                <img src="/assets/img/logo-quickorder.png" alt="Logo QuickOrder">
                <div>
                    <strong>Panel Admin · QuickOrder</strong>
                    <small>Gestión restringida a catálogo, pedidos y reservas</small>
                </div>
            </a>
            <div class="admin-top-actions">
                <span class="admin-user-chip"><i class="fas fa-user-shield"></i> <?php echo e($userName); ?></span>
                <a class="admin-btn light" href="/index.php"><i class="fas fa-arrow-left"></i> Volver a la web</a>
                <a class="admin-btn primary" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar sesión</a>
            </div>
        </div>
    </header>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <div class="admin-sidebar-title">Administración</div>
            <?php echo admin_sidebar_link('/admin/index.php', 'fa-gauge-high', 'Resumen', 'dashboard', $activeKey); ?>
            <?php echo admin_sidebar_link('/admin/categories.php', 'fa-layer-group', 'Categorías', 'categories', $activeKey); ?>
            <?php echo admin_sidebar_link('/admin/products.php', 'fa-burger', 'Productos', 'products', $activeKey); ?>
            <?php echo admin_sidebar_link('/admin/orders.php', 'fa-receipt', 'Pedidos', 'orders', $activeKey); ?>
            <?php echo admin_sidebar_link('/admin/reservations.php', 'fa-calendar-check', 'Reservas', 'reservations', $activeKey); ?>
            <?php echo admin_sidebar_link('/admin/messages.php', 'fa-calendar-check', 'Mensajes', 'messages', $activeKey); ?>            
        </aside>
        <main class="admin-main">
            <?php if ($alert): ?>
                <div class="alert alert-<?php echo e($alert['type']); ?>" style="margin-bottom:1rem;">
                    <i class="fas fa-<?php echo $alert['type'] === 'success' ? 'check-circle' : ($alert['type'] === 'error' ? 'triangle-exclamation' : 'circle-info'); ?>"></i>
                    <?php echo e($alert['message']); ?>
                </div>
            <?php endif; ?>
        <?php
    }
}

if (!function_exists('admin_render_end')) {
    function admin_render_end(): void {
        ?>
        </main>
    </div>
</body>
</html>
        <?php
    }
}
