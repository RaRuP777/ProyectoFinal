<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - QuickOrder' : 'QuickOrder - Ordena tu comida favorita'; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🍕</text></svg>">
</head>
<body>
    <!-- HEADER/NAVEGACIÓN -->
    <header class="header">
        <nav class="navbar container">
            <a href="<?php echo SITE_URL; ?>/" class="logo">
                <span class="logo-icon">🍕</span>
                <span>QuickOrder</span>
            </a>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo SITE_URL; ?>/" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Inicio</a></li>
                <li><a href="<?php echo SITE_URL; ?>/#menu" class="nav-link">Menú</a></li>
                <li><a href="<?php echo SITE_URL; ?>/reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">Reservas</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contacto</a></li>
                
                <?php if (is_logged_in()): ?>
                    <li class="cart-badge">
                        <a href="<?php echo SITE_URL; ?>/cart.php" class="nav-link">
                            <i class="fas fa-shopping-cart"></i>
                            <?php 
                            $cart_count = get_cart_count();
                            if ($cart_count > 0): 
                            ?>
                                <span class="badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo SITE_URL; ?>/my-orders.php" class="nav-link">
                            <i class="fas fa-receipt"></i>
                            Mis Pedidos
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            Cerrar Sesión
                        </a>
                    </li>
                <?php else: ?>
                    <li><a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-primary btn-sm">
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
    
    <!-- Mostrar alertas si existen -->
    <?php
    $alert = get_alert();
    if ($alert):
    ?>
        <div class="alert alert-<?php echo $alert['type']; ?>" style="margin: 1rem auto; max-width: 1200px;">
            <i class="fas fa-<?php echo $alert['type'] == 'success' ? 'check-circle' : ($alert['type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo $alert['message']; ?>
        </div>
    <?php endif; ?>
    
    <script>
        // Menú móvil
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
            
            // Smooth scroll para enlaces internos
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
