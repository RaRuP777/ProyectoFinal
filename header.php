<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - QuickOrder' : 'QuickOrder - Ordena tu comida favorita'; ?></title>

    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="/assets/img/logo-quickorder.png">
</head>
<body>
<?php
if (function_exists('is_logged_in') && is_logged_in() && function_exists('db_value')) {
    $sessionUserId = (int)(function_exists('get_user_id') ? get_user_id() : ($_SESSION['user_id'] ?? 0));
    if ($sessionUserId > 0) {
        $_SESSION['user_role'] = (string)db_value("SELECT role FROM users WHERE id = " . $sessionUserId . " LIMIT 1", $_SESSION['user_role'] ?? 'customer');
    }
}
?>
    <header class="header">
        <nav class="navbar container">
            <a href="/" class="logo" aria-label="QuickOrder - Inicio">
                <img src="/assets/img/logo-quickorder.png" alt="Logo de QuickOrder" class="logo-image">
                <span class="logo-text">QuickOrder</span>
            </a>

            <ul class="nav-menu" id="navMenu">
                <li><a href="/" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Inicio</a></li>
                <li><a href="/#menu" class="nav-link">Menú</a></li>
                <li><a href="/reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">Reservas</a></li>
                <li><a href="/contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contacto</a></li>

                <?php if (is_logged_in()): ?>
                    <?php if (function_exists('is_admin') && is_admin()): ?>
                        <li>
                            <a href="/admin/index.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-user-shield"></i>
                                Admin
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="cart-badge">
                        <a href="/cart.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <?php $cart_count = get_cart_count(); ?>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li>
                        <a href="/my-orders.php" class="nav-link">
                            <i class="fas fa-receipt"></i>
                            Mis Pedidos
                        </a>
                    </li>

                    <li>
                        <a href="/logout.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            Cerrar Sesión
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="/login.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </a></li>
                <?php endif; ?>
            </ul>

            <button class="mobile-menu-toggle" id="mobileMenuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </nav>
    </header>

    <?php $alert = get_alert(); ?>
    <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?>" style="margin: 1rem auto; max-width: 1200px;">
            <i class="fas fa-<?php echo $alert['type'] == 'success' ? 'check-circle' : ($alert['type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $alert['message']; ?>
        </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const navMenu = document.getElementById('navMenu');

            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    navMenu.classList.toggle('active');
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-bars');
                    icon.classList.toggle('fa-times');
                });
            }

            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#' && href.length > 1) {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            navMenu.classList.remove('active');
                        }
                    }
                });
            });
        });
    </script>
