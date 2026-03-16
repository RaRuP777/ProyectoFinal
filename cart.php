<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Carrito de Compra';

// Procesar acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Actualizar cantidad
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        update_cart_quantity($product_id, $quantity);
        set_flash('success', 'Cantidad actualizada correctamente');
        redirect('cart.php');
    }
    
    // Eliminar producto
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $product_id = intval($_POST['product_id']);
        remove_from_cart($product_id);
        set_flash('success', 'Producto eliminado del carrito');
        redirect('cart.php');
    }
    
    // Vaciar carrito
    if (isset($_POST['action']) && $_POST['action'] === 'clear') {
        clear_cart();
        set_flash('info', 'Carrito vaciado');
        redirect('cart.php');
    }
}

// Calcular totales
$cart_items = $_SESSION['cart'];
$cart_total = get_cart_total();
$cart_count = get_cart_count();

include 'includes/header.php';
?>

<section class="cart-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-shopping-cart"></i> Tu Carrito</h1>
            <p><?php echo $cart_count; ?> producto(s) en tu carrito</p>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <!-- Carrito vacío -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Tu carrito está vacío</h2>
                <p>¡Añade algunos deliciosos platos y empieza a disfrutar!</p>
                <a href="index.php" class="btn btn-primary">Ver Carta</a>
            </div>
            
        <?php else: ?>
            <!-- Carrito con productos -->
            <div class="cart-content">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Cantidad</th>
                                <th>Subtotal</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <?php $subtotal = $item['price'] * $item['quantity']; ?>
                                <tr data-product-id="<?php echo $item['id']; ?>">
                                    <td class="product-info-cell">
                                        <div class="product-info">
                                            <img src="<?php echo $item['image'] ? UPLOAD_URL . $item['image'] : SITE_URL . '/assets/img/default-product.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="price-cell"><?php echo format_price($item['price']); ?></td>
                                    <td class="quantity-cell">
                                        <div class="quantity-control">
                                            <button class="qty-btn qty-minus" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   class="qty-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   max="99"
                                                   onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                            <button class="qty-btn qty-plus" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="subtotal-cell"><strong><?php echo format_price($subtotal); ?></strong></td>
                                    <td class="action-cell">
                                        <button class="btn-remove" onclick="removeFromCart(<?php echo $item['id']; ?>)" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Botones de acción del carrito (móvil) -->
                    <div class="cart-actions-mobile">
                        <button onclick="clearCart()" class="btn btn-outline">
                            <i class="fas fa-trash"></i> Vaciar Carrito
                        </button>
                        <a href="index.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Seguir Comprando
                        </a>
                    </div>
                </div>
                
                <!-- Resumen del pedido -->
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Resumen del Pedido</h3>
                        
                        <div class="summary-row">
                            <span>Subtotal (<?php echo $cart_count; ?> productos)</span>
                            <span><?php echo format_price($cart_total); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Gastos de envío</span>
                            <span class="text-success">GRATIS</span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span><?php echo format_price($cart_total); ?></span>
                        </div>
                        
                        <div class="summary-actions">
                            <?php if (is_logged_in()): ?>
                                <a href="checkout.php" class="btn btn-primary btn-block">
                                    <i class="fas fa-credit-card"></i> Finalizar Pedido
                                </a>
                            <?php else: ?>
                                <a href="login.php?redirect=checkout" class="btn btn-primary btn-block">
                                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión para Pedir
                                </a>
                                <p class="text-center mt-2">
                                    <small>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></small>
                                </p>
                            <?php endif; ?>
                            
                            <a href="index.php" class="btn btn-outline btn-block">
                                <i class="fas fa-arrow-left"></i> Seguir Comprando
                            </a>
                            
                            <button onclick="clearCart()" class="btn btn-text btn-block">
                                <i class="fas fa-trash"></i> Vaciar Carrito
                            </button>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="summary-info">
                            <div class="info-item">
                                <i class="fas fa-truck"></i>
                                <span>Entrega en 30-45 min</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-shield-alt"></i>
                                <span>Pago seguro</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-phone-alt"></i>
                                <span>Soporte 24/7</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cupón de descuento (opcional) -->
                    <div class="coupon-card">
                        <h4><i class="fas fa-tag"></i> ¿Tienes un cupón?</h4>
                        <form class="coupon-form">
                            <input type="text" placeholder="Código de descuento" class="form-control">
                            <button type="submit" class="btn btn-secondary">Aplicar</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Formularios ocultos para acciones POST -->
<form id="updateCartForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="product_id" id="update_product_id">
    <input type="hidden" name="quantity" id="update_quantity">
</form>

<form id="removeCartForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="remove">
    <input type="hidden" name="product_id" id="remove_product_id">
</form>

<form id="clearCartForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="clear">
</form>

<?php include 'includes/footer.php'; ?>