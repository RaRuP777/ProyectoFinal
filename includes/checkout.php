<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Finalizar Pedido';

// Verificar que el usuario esté logueado
if (!is_logged_in()) {
    set_flash('error', 'Debes iniciar sesión para realizar un pedido');
    redirect('login.php?redirect=checkout');
}

// Verificar que el carrito no esté vacío
if (empty($_SESSION['cart'])) {
    set_flash('error', 'Tu carrito está vacío');
    redirect('cart.php');
}

// Obtener datos del usuario
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Variables del formulario
$errors = array();
$delivery_address = $user['address'];
$phone = $user['phone'];
$notes = '';
$payment_method = 'cash';

// Calcular totales
$cart_items = $_SESSION['cart'];
$subtotal = get_cart_total();
$delivery_fee = 0; // Envío gratis
$total = $subtotal + $delivery_fee;

// Procesar el pedido cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener datos del formulario
    $delivery_address = clean_input($_POST['delivery_address']);
    $phone = clean_input($_POST['phone']);
    $notes = clean_input($_POST['notes']);
    $payment_method = clean_input($_POST['payment_method']);
    
    // VALIDACIONES
    
    // Validar dirección
    if (empty($delivery_address)) {
        $errors['delivery_address'] = 'La dirección de entrega es obligatoria';
    } elseif (strlen($delivery_address) < 10) {
        $errors['delivery_address'] = 'La dirección debe tener al menos 10 caracteres';
    }
    
    // Validar teléfono
    if (empty($phone)) {
        $errors['phone'] = 'El teléfono es obligatorio';
    } elseif (!preg_match("/^[0-9]{9}$/", $phone)) {
        $errors['phone'] = 'El teléfono debe tener 9 dígitos';
    }
    
    // Validar método de pago
    $valid_payment_methods = array('cash', 'card', 'transfer');
    if (!in_array($payment_method, $valid_payment_methods)) {
        $errors['payment_method'] = 'Método de pago no válido';
    }
    
    // Validar que el carrito no esté vacío (por seguridad)
    if (empty($_SESSION['cart'])) {
        $errors['cart'] = 'El carrito está vacío';
    }
    
    // Si no hay errores, procesar el pedido
    if (empty($errors)) {
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Generar número de pedido único
            $order_number = 'QO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Insertar pedido en la base de datos
            $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, total_amount, status, delivery_address, phone, notes) VALUES (?, ?, ?, 'pending', ?, ?, ?)");
            $stmt->bind_param("isdsss", $user_id, $order_number, $total, $delivery_address, $phone, $notes);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();
            
            // Insertar items del pedido
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            
            foreach ($cart_items as $item) {
                $subtotal_item = $item['price'] * $item['quantity'];
                $stmt->bind_param("iisidd", 
                    $order_id, 
                    $item['id'], 
                    $item['name'], 
                    $item['quantity'], 
                    $item['price'], 
                    $subtotal_item
                );
                $stmt->execute();
            }
            $stmt->close();
            
            // Confirmar transacción
            $conn->commit();
            
            // Vaciar el carrito
            clear_cart();
            
            // Guardar datos del pedido en sesión para mostrar confirmación
            $_SESSION['last_order'] = array(
                'order_id' => $order_id,
                'order_number' => $order_number,
                'total' => $total,
                'items_count' => count($cart_items)
            );
            
            // Enviar email de confirmación (opcional - simulado)
            // send_order_confirmation_email($user['email'], $order_number, $total);
            
            // Redirigir a página de confirmación
            redirect('order-confirmation.php');
            
        } catch (Exception $e) {
            // Si hay error, revertir transacción
            $conn->rollback();
            $errors['general'] = 'Error al procesar el pedido. Por favor, inténtalo de nuevo.';
            error_log('Error en checkout: ' . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<section class="checkout-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-credit-card"></i> Finalizar Pedido</h1>
            <div class="breadcrumb">
                <a href="index.php">Inicio</a>
                <i class="fas fa-chevron-right"></i>
                <a href="cart.php">Carrito</a>
                <i class="fas fa-chevron-right"></i>
                <span>Checkout</span>
            </div>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $errors['general']; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-wrapper">
            <!-- Formulario de checkout -->
            <div class="checkout-form-section">
                <form action="checkout.php" method="POST" id="checkoutForm" novalidate>
                    
                    <!-- Información de entrega -->
                    <div class="checkout-card">
                        <h2><i class="fas fa-shipping-fast"></i> Información de Entrega</h2>
                        
                        <div class="form-group <?php echo isset($errors['delivery_address']) ? 'has-error' : ''; ?>">
                            <label for="delivery_address">
                                <i class="fas fa-map-marker-alt"></i> Dirección de Entrega <span class="required">*</span>
                            </label>
                            <textarea id="delivery_address" 
                                      name="delivery_address" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Calle, número, piso, código postal, ciudad"
                                      required
                                      minlength="10"
                                      maxlength="250"><?php echo htmlspecialchars($delivery_address); ?></textarea>
                            <?php if (isset($errors['delivery_address'])): ?>
                                <span class="error-message"><?php echo $errors['delivery_address']; ?></span>
                            <?php endif; ?>
                            <small class="form-text">Asegúrate de que la dirección sea correcta</small>
                        </div>
                        
                        <div class="form-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Teléfono de Contacto <span class="required">*</span>
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control" 
                                   placeholder="612345678"
                                   value="<?php echo htmlspecialchars($phone); ?>"
                                   required
                                   pattern="[0-9]{9}"
                                   maxlength="9">
                            <?php if (isset($errors['phone'])): ?>
                                <span class="error-message"><?php echo $errors['phone']; ?></span>
                            <?php endif; ?>
                            <small class="form-text">Te llamaremos si hay algún problema</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-comment"></i> Notas del Pedido (Opcional)
                            </label>
                            <textarea id="notes" 
                                      name="notes" 
                                      class="form-control" 
                                      rows="3"
                                      placeholder="Ej: Sin cebolla, timbre no funciona, etc."
                                      maxlength="500"><?php echo htmlspecialchars($notes); ?></textarea>
                            <small class="form-text">Máximo 500 caracteres</small>
                        </div>
                    </div>
                    
                    <!-- Método de pago -->
                    <div class="checkout-card">
                        <h2><i class="fas fa-wallet"></i> Método de Pago</h2>
                        
                        <div class="payment-methods">
                            <label class="payment-option <?php echo $payment_method === 'cash' ? 'active' : ''; ?>">
                                <input type="radio" 
                                       name="payment_method" 
                                       value="cash" 
                                       <?php echo $payment_method === 'cash' ? 'checked' : ''; ?>>
                                <div class="payment-content">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div>
                                        <strong>Efectivo</strong>
                                        <p>Paga al recibir tu pedido</p>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option <?php echo $payment_method === 'card' ? 'active' : ''; ?>">
                                <input type="radio" 
                                       name="payment_method" 
                                       value="card"
                                       <?php echo $payment_method === 'card' ? 'checked' : ''; ?>>
                                <div class="payment-content">
                                    <i class="fas fa-credit-card"></i>
                                    <div>
                                        <strong>Tarjeta</strong>
                                        <p>Visa, Mastercard, American Express</p>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option <?php echo $payment_method === 'transfer' ? 'active' : ''; ?>">
                                <input type="radio" 
                                       name="payment_method" 
                                       value="transfer"
                                       <?php echo $payment_method === 'transfer' ? 'checked' : ''; ?>>
                                <div class="payment-content">
                                    <i class="fas fa-university"></i>
                                    <div>
                                        <strong>Transferencia</strong>
                                        <p>Pago online inmediato</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                        
                        <?php if (isset($errors['payment_method'])): ?>
                            <span class="error-message"><?php echo $errors['payment_method']; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tiempo estimado de entrega -->
                    <div class="delivery-estimate">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Tiempo estimado de entrega</strong>
                            <p>30-45 minutos</p>
                        </div>
                    </div>
                    
                    <!-- Términos y condiciones -->
                    <div class="checkout-terms">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" id="terms" required>
                            <span>He leído y acepto los <a href="terms.php" target="_blank">términos y condiciones</a></span>
                        </label>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="checkout-actions">
                        <a href="cart.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Volver al Carrito
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Confirmar Pedido
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Resumen del pedido -->
            <div class="checkout-summary-section">
                <div class="checkout-summary">
                    <h2>Resumen del Pedido</h2>
                    
                    <!-- Items del pedido -->
                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item">
                                <img src="<?php echo $item['image'] ? UPLOAD_URL . $item['image'] : SITE_URL . '/assets/img/default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Cantidad: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="item-price">
                                    <?php echo format_price($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Totales -->
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo format_price($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Gastos de envío</span>
                            <span class="text-success">GRATIS</span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row summary-total">
                            <span>Total a Pagar</span>
                            <span><?php echo format_price($total); ?></span>
                        </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="summary-features">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Pago 100% seguro</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-truck"></i>
                            <span>Entrega rápida</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-headset"></i>
                            <span>Soporte 24/7</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Activar/desactivar métodos de pago visualmente
document.querySelectorAll('.payment-option input').forEach(input => {
    input.addEventListener('change', function() {
        document.querySelectorAll('.payment-option').forEach(option => {
            option.classList.remove('active');
        });
        this.closest('.payment-option').classList.add('active');
    });
});

// Validación del formulario
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const terms = document.getElementById('terms');
    
    if (!terms.checked) {
        e.preventDefault();
        alert('Debes aceptar los términos y condiciones');
        terms.focus();
        return false;
    }
});
</script>
