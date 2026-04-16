<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Inicio - QuickOrder';

// --- Obtener categorías con conteo de productos ---
$categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id
                     GROUP BY c.id
                     ORDER BY c.name ASC
                     LIMIT 8";
$categories_result = mysqli_query($conn, $categories_query);
$categories = [];
if ($categories_result) {
    while ($row = mysqli_fetch_assoc($categories_result)) {
        $categories[] = $row;
    }
}

// --- Categoría seleccionada desde la propia portada ---
$selected_category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$selected_category_name = '';
if ($selected_category_id > 0) {
    foreach ($categories as $cat_row) {
        if ((int)$cat_row['id'] === $selected_category_id) {
            $selected_category_name = $cat_row['name'];
            break;
        }
    }
}

// --- Obtener productos para la sección menú ---
$featured_products = [];

if ($selected_category_id > 0) {
    $products_query = "SELECT p.*, c.name as category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.category_id = {$selected_category_id}
                       ORDER BY p.is_featured DESC, p.created_at DESC
                       LIMIT 12";
} else {
    $products_query = "SELECT p.*, c.name as category_name 
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.is_featured = 1
                       ORDER BY p.created_at DESC
                       LIMIT 6";
}

$products_result = mysqli_query($conn, $products_query);
if ($products_result) {
    while ($row = mysqli_fetch_assoc($products_result)) {
        $featured_products[] = $row;
    }
}

// Si no hay productos destacados y no hay filtro, traer los más recientes
if (empty($featured_products) && $selected_category_id === 0) {
    $recent_query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     LEFT JOIN categories c ON p.category_id = c.id
                     ORDER BY p.created_at DESC
                     LIMIT 6";
    $recent_result = mysqli_query($conn, $recent_query);
    if ($recent_result) {
        while ($row = mysqli_fetch_assoc($recent_result)) {
            $featured_products[] = $row;
        }
    }
}

// --- Mapa de emojis e ícono según nombre de categoría ---
function getCategoryEmoji($name) {
    $name_lower = strtolower($name);
    $map = [
        'pizza'       => ['emoji' => '🍕', 'gradient' => 'linear-gradient(135deg, #FF6B35, #FFA726)'],
        'ensalada'    => ['emoji' => '🥗', 'gradient' => 'linear-gradient(135deg, #4CAF50, #8BC34A)'],
        'hamburguesa' => ['emoji' => '🍔', 'gradient' => 'linear-gradient(135deg, #FF6B35, #FF8A50)'],
        'burger'      => ['emoji' => '🍔', 'gradient' => 'linear-gradient(135deg, #FF6B35, #FF8A50)'],
        'postre'      => ['emoji' => '🍰', 'gradient' => 'linear-gradient(135deg, #AB47BC, #EC407A)'],
        'bebida'      => ['emoji' => '🥤', 'gradient' => 'linear-gradient(135deg, #2196F3, #42A5F5)'],
        'pasta'       => ['emoji' => '🍝', 'gradient' => 'linear-gradient(135deg, #FF8F00, #FFA000)'],
        'sushi'       => ['emoji' => '🍱', 'gradient' => 'linear-gradient(135deg, #E91E63, #F06292)'],
        'tacos'       => ['emoji' => '🌮', 'gradient' => 'linear-gradient(135deg, #FF5722, #FF7043)'],
        'mariscos'    => ['emoji' => '🦐', 'gradient' => 'linear-gradient(135deg, #00BCD4, #4DD0E1)'],
        'sopa'        => ['emoji' => '🍲', 'gradient' => 'linear-gradient(135deg, #795548, #A1887F)'],
        'sandwich'    => ['emoji' => '🥪', 'gradient' => 'linear-gradient(135deg, #FF9800, #FFB74D)'],
        'pollo'       => ['emoji' => '🍗', 'gradient' => 'linear-gradient(135deg, #8D6E63, #BCAAA4)'],
        'carne'       => ['emoji' => '🥩', 'gradient' => 'linear-gradient(135deg, #B71C1C, #E53935)'],
        'vegano'      => ['emoji' => '🌿', 'gradient' => 'linear-gradient(135deg, #388E3C, #66BB6A)'],
        'desayuno'    => ['emoji' => '🥐', 'gradient' => 'linear-gradient(135deg, #F57F17, #F9A825)'],
        'especial'    => ['emoji' => '⭐', 'gradient' => 'linear-gradient(135deg, #9C27B0, #BA68C8)'],
        'oferta'      => ['emoji' => '🏷️', 'gradient' => 'linear-gradient(135deg, #E91E63, #EC407A)'],
    ];
    foreach ($map as $key => $val) {
        if (strpos($name_lower, $key) !== false) return $val;
    }
    // default
    $defaults = [
        ['emoji' => '🍽️', 'gradient' => 'linear-gradient(135deg, #FF6B35, #C84E21)'],
        ['emoji' => '🥘', 'gradient' => 'linear-gradient(135deg, #4CAF50, #8BC34A)'],
        ['emoji' => '🫕', 'gradient' => 'linear-gradient(135deg, #9C27B0, #BA68C8)'],
        ['emoji' => '🍜', 'gradient' => 'linear-gradient(135deg, #2196F3, #42A5F5)'],
    ];
    return $defaults[crc32($name) % count($defaults)];
}

require_once 'includes/header.php';
?>

<!-- HERO SECTION -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <span class="badge hero-badge">🔥 ¡Pedidos en 30 minutos o menos!</span>
            <h1 class="hero-title">La mejor comida,<br><span class="hero-accent-text">entregada en tu puerta</span></h1>
            <p class="hero-subtitle">Descubre nuestros platos elaborados con ingredientes frescos y de temporada. Desde pizzas artesanales hasta ensaladas gourmet.</p>
            <div class="hero-actions" style="display:flex; gap:1rem; flex-wrap:wrap;">
                <a href="#menu" class="btn btn-secondary btn-lg">
                    <i class="fas fa-utensils"></i> Ver Menú
                </a>
                <a href="reservations.php" class="btn btn-outline-white btn-lg">
                    <i class="fas fa-calendar-alt"></i> Reservar Mesa
                </a>
            </div>
            <!-- Stats -->
            <div class="hero-stats" style="display:flex; gap:2rem; margin-top:2.5rem; flex-wrap:wrap;">
                <div class="hero-stat">
                    <span class="hero-stat-value">500+</span>
                    <span class="hero-stat-label">Clientes felices</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-value">30min</span>
                    <span class="hero-stat-label">Tiempo de entrega</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-value">4.9⭐</span>
                    <span class="hero-stat-label">Valoración media</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CARACTERÍSTICAS -->
<section class="section" style="background: var(--bg-secondary); padding: 3rem 0;">
    <div class="container">
        <div class="grid grid-4" style="gap: 1.5rem;">
            <div class="feature-item" style="text-align:center; padding:1.5rem;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">🚚</div>
                <h4 style="margin-bottom:0.5rem; color: var(--primary-color);">Entrega Rápida</h4>
                <p style="color: var(--text-secondary); font-size:0.9rem;">En 30 minutos en tu domicilio</p>
            </div>
            <div class="feature-item" style="text-align:center; padding:1.5rem;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">🌿</div>
                <h4 style="margin-bottom:0.5rem; color: var(--primary-color);">Ingredientes Frescos</h4>
                <p style="color: var(--text-secondary); font-size:0.9rem;">Productos de temporada y locales</p>
            </div>
            <div class="feature-item" style="text-align:center; padding:1.5rem;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">💳</div>
                <h4 style="margin-bottom:0.5rem; color: var(--primary-color);">Pago Seguro</h4>
                <p style="color: var(--text-secondary); font-size:0.9rem;">Tarjeta, efectivo o en línea</p>
            </div>
            <div class="feature-item" style="text-align:center; padding:1.5rem;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">⭐</div>
                <h4 style="margin-bottom:0.5rem; color: var(--primary-color);">Calidad Garantizada</h4>
                <p style="color: var(--text-secondary); font-size:0.9rem;">Más de 4.9/5 en valoraciones</p>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORÍAS -->
<section class="section" id="categorias">
    <div class="container">
        <h2 class="section-title">Categorías Populares</h2>
        <p class="section-subtitle">Explora nuestras deliciosas opciones organizadas por categorías</p>

        <?php if (!empty($categories)): ?>
        <div class="grid grid-4" style="gap: 1.5rem;">
            <?php foreach ($categories as $i => $cat):
                $cat_style = getCategoryEmoji($cat['name']);
                $delay = $i * 0.1;
            ?>
            <div class="category-card card fade-in" style="animation-delay: <?= $delay ?>s; cursor: pointer;"
                 onclick="window.location.href='<?= SITE_URL ?>/index.php?category=<?= $cat['id'] ?>#menu'">
                <!-- Imagen / Gradiente con emoji -->
                <?php if (!empty($cat['image']) && file_exists('uploads/categories/' . $cat['image'])): ?>
                    <div class="category-card-img" style="height:150px; overflow:hidden; position:relative;">
                        <img src="<?= SITE_URL ?>/uploads/categories/<?= htmlspecialchars($cat['image']) ?>"
                             alt="<?= htmlspecialchars($cat['name']) ?>"
                             style="width:100%; height:100%; object-fit:cover;">
                        <div style="position:absolute; inset:0; background: rgba(0,0,0,0.25); display:flex; align-items:center; justify-content:center; font-size:3rem;">
                        </div>
                    </div>
                <?php else: ?>
                    <div class="category-card-img" style="background: <?= $cat_style['gradient'] ?>; height:150px; display:flex; align-items:center; justify-content:center; font-size:4rem;">
                        <?= $cat_style['emoji'] ?>
                    </div>
                <?php endif; ?>

                <!-- Cuerpo de la tarjeta -->
                <div class="card-body text-center">
                    <h3 class="card-title" style="font-size:1.1rem; margin-bottom:0.35rem;">
                        <?= htmlspecialchars($cat['name']) ?>
                    </h3>
                    <p class="card-text" style="font-size:0.9rem; margin-bottom:0.75rem;">
                        <?= (int)$cat['product_count'] ?> producto<?= $cat['product_count'] != 1 ? 's' : '' ?>
                    </p>
                    <a href="<?= SITE_URL ?>/index.php?category=<?= $cat['id'] ?>#menu" class="btn btn-outline btn-sm"
                       onclick="event.stopPropagation();">
                        Ver más
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Categorías de ejemplo si la BD está vacía -->
        <div class="grid grid-4" style="gap: 1.5rem;">
            <?php
            $sample_cats = [
                ['emoji' => '🍕', 'name' => 'Pizzas',       'count' => 15, 'gradient' => 'linear-gradient(135deg, #FF6B35, #C84E21)', 'id' => 1],
                ['emoji' => '🥗', 'name' => 'Ensaladas',    'count' => 8,  'gradient' => 'linear-gradient(135deg, #4CAF50, #2E7D32)', 'id' => 2],
                ['emoji' => '🍔', 'name' => 'Hamburguesas', 'count' => 12, 'gradient' => 'linear-gradient(135deg, #FF6B35, #D65A31)', 'id' => 3],
                ['emoji' => '🍰', 'name' => 'Postres',      'count' => 10, 'gradient' => 'linear-gradient(135deg, #AB47BC, #EC407A)', 'id' => 4],
            ];
            foreach ($sample_cats as $i => $cat):
            ?>
            <div class="category-card card fade-in" style="animation-delay: <?= $i * 0.1 ?>s; cursor:pointer;"
                 onclick="window.location.href='<?= SITE_URL ?>/index.php?category=<?= $cat['id'] ?>#menu'">
                <div class="category-card-img" style="background: <?= $cat['gradient'] ?>; height:150px; display:flex; align-items:center; justify-content:center; font-size:4rem;">
                    <?= $cat['emoji'] ?>
                </div>
                <div class="card-body text-center">
                    <h3 class="card-title" style="font-size:1.1rem; margin-bottom:0.35rem;"><?= $cat['name'] ?></h3>
                    <p class="card-text" style="font-size:0.9rem; margin-bottom:0.75rem;"><?= $cat['count'] ?> productos</p>
                    <a href="<?= SITE_URL ?>/index.php?category=<?= $cat['id'] ?>#menu" class="btn btn-outline btn-sm">Ver más</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- PRODUCTOS DESTACADOS -->
<section class="section" id="menu" style="background-color: var(--bg-secondary);">
    <div class="container">
        <h2 class="section-title"><?= $selected_category_name ? 'Menú: ' . htmlspecialchars($selected_category_name) : 'Productos Destacados' ?></h2>
        <p class="section-subtitle">
            <?= $selected_category_name
                ? 'Mostrando productos de la categoría seleccionada.'
                : 'Los favoritos de nuestros clientes, elaborados con los mejores ingredientes' ?>
        </p>

        <?php if ($selected_category_name): ?>
        <div style="text-align:center; margin-bottom:1.5rem;">
            <a href="<?= SITE_URL ?>/index.php#menu" class="btn btn-outline btn-sm">Ver todas las categorías</a>
        </div>
        <?php endif; ?>

        <?php if (!empty($featured_products)): ?>
        <div class="grid grid-3" style="gap: 1.5rem;">
            <?php foreach ($featured_products as $product): ?>
            <div class="card product-card">
                <div style="position: relative;">
                    <?php if (!empty($product['image']) && file_exists('uploads/products/' . $product['image'])): ?>
                        <img src="<?= SITE_URL ?>/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="card-img">
                    <?php else: ?>
                        <?php $ps = getCategoryEmoji($product['category_name'] ?? ''); ?>
                        <div style="background: <?= $ps['gradient'] ?>; height: 200px; display:flex; align-items:center; justify-content:center; font-size:4rem;">
                            <?= $ps['emoji'] ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($product['is_featured'])): ?>
                        <span class="badge" style="position:absolute; top:10px; right:10px;">⭐ Destacado</span>
                    <?php endif; ?>
                    <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                        <span class="badge badge-success" style="position:absolute; top:10px; left:10px;">Oferta</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($product['category_name'])): ?>
                        <small style="color: var(--primary-color); font-weight:600; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.5px;">
                            <?= htmlspecialchars($product['category_name']) ?>
                        </small>
                    <?php endif; ?>
                    <h3 class="card-title" style="margin-top:0.25rem;"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="card-text" style="font-size:0.9rem; -webkit-line-clamp:2; overflow:hidden; display:-webkit-box; -webkit-box-orient:vertical;">
                        <?= htmlspecialchars($product['description'] ?? '') ?>
                    </p>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                        <div>
                            <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                <span style="text-decoration:line-through; color: var(--text-secondary); font-size:0.9rem;">
                                    €<?= number_format($product['price'], 2) ?>
                                </span>
                                <span style="font-size:1.4rem; font-weight:700; color: var(--primary-color); margin-left:0.5rem;">
                                    €<?= number_format($product['discount_price'], 2) ?>
                                </span>
                            <?php else: ?>
                                <span style="font-size:1.4rem; font-weight:700; color: var(--primary-color);">
                                    €<?= number_format($product['price'], 2) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <form action="<?= SITE_URL ?>/cart.php" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-cart-plus"></i> Añadir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align:center; margin-top:2.5rem;">
            <a href="<?= SITE_URL ?>/index.php#menu" class="btn btn-primary btn-lg">
                <i class="fas fa-utensils"></i> Ver Menú Completo
            </a>
        </div>

        <?php else: ?>
        <div style="text-align:center; padding:3rem;">
            <div style="font-size:4rem; margin-bottom:1rem;">🍽️</div>
            <h3>¡Próximamente nuevos platos!</h3>
            <p style="color: var(--text-secondary);">Estamos preparando el menú. Vuelve pronto.</p>
            <a href="contact.php" class="btn btn-primary" style="margin-top:1rem;">Contáctanos</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- BANNER PROMOCIONAL -->
<section style="background: linear-gradient(135deg, var(--primary-color), #c0392b); color:#fff; padding:4rem 0; text-align:center;">
    <div class="container">
        <h2 style="font-size:2rem; margin-bottom:1rem; color:#fff;">🎉 ¡Primera entrega GRATIS!</h2>
        <p style="font-size:1.1rem; opacity:0.9; max-width:500px; margin:0 auto 2rem;">
            Regístrate ahora y disfruta de tu primer pedido sin costes de envío. Oferta válida hasta fin de mes.
        </p>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-lg" style="background:#fff; color: var(--primary-color); font-weight:700;">
            <i class="fas fa-user-plus"></i> Crear Cuenta Gratis
        </a>
    </div>
</section>

<!-- POR QUÉ ELEGIRNOS -->
<section class="section">
    <div class="container">
        <h2 class="section-title">¿Por qué elegir QuickOrder?</h2>
        <p class="section-subtitle">Miles de clientes confían en nosotros cada día</p>
        <div class="grid grid-3" style="gap:2rem; margin-top:1rem;">
            <div class="card" style="padding:2rem; text-align:center; border-top:4px solid var(--primary-color);">
                <div style="font-size:3rem; margin-bottom:1rem;">👨‍🍳</div>
                <h3 style="margin-bottom:0.75rem;">Chefs Profesionales</h3>
                <p style="color: var(--text-secondary);">Nuestro equipo de chefs tiene más de 10 años de experiencia en cocina mediterránea y fusion.</p>
            </div>
            <div class="card" style="padding:2rem; text-align:center; border-top:4px solid var(--secondary-color);">
                <div style="font-size:3rem; margin-bottom:1rem;">📦</div>
                <h3 style="margin-bottom:0.75rem;">Embalaje Ecológico</h3>
                <p style="color: var(--text-secondary);">Usamos envases 100% biodegradables y reciclables porque nos importa el planeta.</p>
            </div>
            <div class="card" style="padding:2rem; text-align:center; border-top:4px solid #4CAF50;">
                <div style="font-size:3rem; margin-bottom:1rem;">💬</div>
                <h3 style="margin-bottom:0.75rem;">Soporte 24/7</h3>
                <p style="color: var(--text-secondary);">¿Algún problema con tu pedido? Nuestro equipo está disponible las 24 horas para ayudarte.</p>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIOS -->
<section class="section" style="background: var(--bg-secondary);">
    <div class="container">
        <h2 class="section-title">Lo que dicen nuestros clientes</h2>
        <div class="grid grid-3" style="gap:1.5rem;">
            <div class="card" style="padding:1.75rem; border-left:4px solid var(--primary-color);">
                <div style="color:#FFC107; margin-bottom:0.75rem; font-size:1.2rem;">★★★★★</div>
                <p style="color: var(--text-secondary); font-style:italic; margin-bottom:1rem;">
                    "La pizza margarita es la mejor que he probado en la ciudad. Llegó caliente y perfectamente embalada. ¡100% recomendado!"
                </p>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:40px; height:40px; border-radius:50%; background: linear-gradient(135deg, #FF6B35, #FFA726); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">A</div>
                    <div>
                        <strong>Ana García</strong>
                        <small style="display:block; color: var(--text-secondary);">Cliente desde 2023</small>
                    </div>
                </div>
            </div>
            <div class="card" style="padding:1.75rem; border-left:4px solid var(--secondary-color);">
                <div style="color:#FFC107; margin-bottom:0.75rem; font-size:1.2rem;">★★★★★</div>
                <p style="color: var(--text-secondary); font-style:italic; margin-bottom:1rem;">
                    "Pedido en 5 minutos, entrega en 25. El sistema de seguimiento en tiempo real es fantástico. ¡Mis hijos ya no quieren otro restaurante!"
                </p>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:40px; height:40px; border-radius:50%; background: linear-gradient(135deg, #4CAF50, #8BC34A); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">C</div>
                    <div>
                        <strong>Carlos Martínez</strong>
                        <small style="display:block; color: var(--text-secondary);">Cliente desde 2022</small>
                    </div>
                </div>
            </div>
            <div class="card" style="padding:1.75rem; border-left:4px solid #4CAF50;">
                <div style="color:#FFC107; margin-bottom:0.75rem; font-size:1.2rem;">★★★★☆</div>
                <p style="color: var(--text-secondary); font-style:italic; margin-bottom:1rem;">
                    "Las ensaladas son frescas y abundantes. Me encanta que puedo personalizar mi pedido sin complicaciones. ¡La app es muy intuitiva!"
                </p>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div style="width:40px; height:40px; border-radius:50%; background: linear-gradient(135deg, #2196F3, #42A5F5); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700;">L</div>
                    <div>
                        <strong>Laura Sánchez</strong>
                        <small style="display:block; color: var(--text-secondary);">Cliente desde 2024</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA - RESERVAS -->
<section style="background: linear-gradient(135deg, #1a1a2e, #16213e); color:#fff; padding:5rem 0; text-align:center;">
    <div class="container">
        <div style="font-size:3.5rem; margin-bottom:1rem;">🍽️</div>
        <h2 style="font-size:2.2rem; margin-bottom:1rem; color:#fff;">¿Prefieres comer en el local?</h2>
        <p style="font-size:1.1rem; opacity:0.85; max-width:550px; margin:0 auto 2rem;">
            Reserva tu mesa ahora y disfruta de una experiencia gastronómica inigualable con nuestros chefs en directo.
        </p>
        <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
            <a href="reservations.php" class="btn btn-primary btn-lg">
                <i class="fas fa-calendar-alt"></i> Reservar Mesa
            </a>
            <a href="contact.php" class="btn btn-lg" style="background:transparent; border:2px solid #fff; color:#fff;">
                <i class="fas fa-phone"></i> Llamar Ahora
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
