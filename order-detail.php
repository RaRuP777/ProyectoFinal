<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Detalle del Pedido';

// Verificar que el usuario esté logueado
if (!is_logged_in()) {
    set_flash('error', 'Debes iniciar sesión para ver este pedido');
    redirect('login.php');
}

// Obtener ID del pedido
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    set_flash('error', 'Pedido no válido');
    redirect('my-orders.php');
}

$user_id = $_SESSION['user_id'];

// Obtener información del pedido
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Verificar que el pedido existe y pertenece al usuario
if (!$order) {
    set_flash('error', 'Pedido no encontrado');
    redirect('my-orders.php');
}

// Obtener items del pedido
$stmt = $conn->prepare("SELECT oi.*, p.image FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();
$stmt->close();

// Función para obtener el badge del estado
function get_status_badge($status) {
    $badges = [
        'pending' => ['class' => 'badge-warning', 'icon' => 'fa-clock', 'text' => 'Pendiente'],
        'confirmed' => ['class' => 'badge-info', 'icon' => 'fa-check-circle', 'text' => 'Confirmado'],
        'preparing' => ['class' => 'badge-primary', 'icon' => 'fa-utensils', 'text' => 'Preparando'],
        'ready' => ['class' => 'badge-success', 'icon' => 'fa-motorcycle', 'text' => 'Listo para Envío'],
        'delivered' => ['class' => 'badge-success', 'icon' => 'fa-check-double', 'text' => 'Entregado'],
        'cancelled' => ['class' => 'badge-danger', 'icon' => 'fa-times-circle', 'text' => 'Cancelado']
    ];
    
    return $badges[$status] ?? $badges['pending'];
}

// Función para obtener el progreso del pedido
function get_order_progress($status) {
    $progress = [
        'pending' => 25,
        'confirmed' => 50,
        'preparing' => 75,
        'ready' => 90,
        'delivered' => 100,
        'cancelled' => 0
    ];
    
    return $progress[$status] ?? 0;
}

$status_badge = get_status_badge($order['status']);
$progress = get_order_progress($order['status']);

include 'includes/header.php';
?>

<section class="order-detail-section">
    <div class="container">
        <!-- Header del pedido -->
        <div class="detail-header">
            <div>
                <a href="my-orders.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Volver a Mis Pedidos
                </a>
                <h1>Pedido #<?php echo htmlspecialchars($order['order_number']); ?></h1>
                <p class="order-date">
                    <i class="fas fa-calendar"></i> 
                    Realizado el <?php echo date('d/m/Y', strtotime($order['created_at'])); ?> 
                    a las <?php echo date('H:i', strtotime($order['created_at'])); ?>
                </p>
            </div>
            
            <div class="header-actions">
                <span class="order-status-badge <?php echo $status_badge['class']; ?>">
                    <i class="fas <?php echo $status_badge['icon']; ?>"></i>
                    <?php echo $status_badge['text']; ?>
                </span>
            </div>
        </div>
        
        <!-- Tracking del pedido -->
        <?php if ($order['status'] !== 'cancelled'): ?>
        <div class="order-tracking-detail">
            <h2><i class="fas fa-route"></i> Estado de tu Pedido</h2>
            
            <div class="progress-bar-container">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                </div>
                <span class="progress-text"><?php echo $progress; ?>%</span>
            </div>
            
            <div class="tracking-timeline">
                <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'confirmed', 'preparing', 'ready', 'delivered']) ? 'completed' : ''; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="timeline-content">
                        <strong>Pedido Recibido</strong>
                        <small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                        <p>Tu pedido ha sido recibido correctamente</p>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'preparing', 'ready', 'delivered']) ? 'completed' : ($order['status'] === 'pending' ? 'active' : ''); ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="timeline-content">
                        <strong>Confirmado</strong>
                        <small><?php echo $order['status'] !== 'pending' ? date('H:i', strtotime($order['updated_at'])) : 'Pendiente'; ?></small>
                        <p>El restaurante ha confirmado tu pedido</p>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['preparing', 'ready', 'delivered']) ? 'completed' : (in_array($order['status'], ['pending', 'confirmed']) ? 'active' : ''); ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="timeline-content">
                        <strong>En Preparación</strong>
                        <small><?php echo in_array($order['status'], ['preparing', 'ready', 'delivered']) ? date('H:i', strtotime($order['updated_at'])) : 'Pendiente'; ?></small>
                        <p>Nuestros chefs están preparando tu pedido</p>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo in_array($order['status'], ['ready', 'delivered']) ? 'completed' : (in_array($order['status'], ['pending', 'confirmed', 'preparing']) ? 'active' : ''); ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                    <div class="timeline-content">
                        <strong>En Camino</strong>
                        <small><?php echo in_array($order['status'], ['ready', 'delivered']) ? date('H:i', strtotime($order['updated_at'])) : 'Pendiente'; ?></small>
                        <p>Tu pedido está de camino a tu dirección</p>
                    </div>
                </div>
                
                <div class="timeline-item <?php echo $order['status'] === 'delivered' ? 'completed' : 'active'; ?>">
                    <div class="timeline-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="timeline-content">
                        <strong>Entregado</strong>
                        <small><?php echo $order['status'] === 'delivered' ? date('H:i', strtotime($order['updated_at'])) : 'Pendiente'; ?></small>
                        <p>¡Disfruta de tu comida!</p>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="order-cancelled-alert">
            <i class="fas fa-times-circle"></i>
            <div>
                <strong>Pedido Cancelado</strong>
                <p>Este pedido fue cancelado el <?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Contenido principal -->
        <div class="detail-content">
            <!-- Productos del pedido -->
            <div class="detail-main">
                <div class="detail-card">
                    <h2><i class="fas fa-shopping-bag"></i> Productos del Pedido</h2>
                    
                    <div class="order-items-list">
                        <?php while ($item = $order_items->fetch_assoc()): ?>
                            <div class="order-item-detail">
                                <img src="<?php echo $item['image'] ? UPLOAD_URL . $item['image'] : SITE_URL . '/assets/img/default-product.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                
                                <div class="item-detail-info">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <p class="item-quantity">Cantidad: <?php echo $item['quantity']; ?></p>
                                    <p class="item-price">Precio unitario: <?php echo format_price($item['price']); ?></p>
                                    <?php if ($item['notes']): ?>
                                        <p class="item-notes">
                                            <i class="fas fa-comment"></i> 
                                            <em><?php echo htmlspecialchars($item['notes']); ?></em>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-subtotal">
                                    <?php echo format_price($item['subtotal']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Información de entrega -->
                <div class="detail-card">
                    <h2><i class="fas fa-map-marker-alt"></i> Información de Entrega</h2>
                    
                    <div class="delivery-info">
                        <div class="info-row">
                            <strong>Dirección:</strong>
                            <span><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                        </div>
                        
                        <div class="info-row">
                            <strong>Teléfono de contacto:</strong>
                            <span><?php echo htmlspecialchars($order['phone']); ?></span>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                            <div class="info-row">
                                <strong>Notas del pedido:</strong>
                                <span><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Resumen del pedido (sidebar) -->
            <div class="detail-sidebar">
                <div class="detail-card summary-card">
                    <h2>Resumen</h2>
                    
                    <div class="summary-rows">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo format_price($order['total_amount']); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Gastos de envío</span>
                            <span class="text-success">GRATIS</span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row summary-total">
                            <span>Total Pagado</span>
                            <span><?php echo format_price($order['total_amount']); ?></span>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <?php if ($order['status'] === 'delivered'): ?>
                            <button onclick="reorderItems(<?php echo $order['id']; ?>)" class="btn btn-primary btn-block">
                                <i class="fas fa-redo"></i> Repetir Pedido
                            </button>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                            <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn btn-danger btn-block">
                                <i class="fas fa-times"></i> Cancelar Pedido
                            </button>
                        <?php endif; ?>
                        
                        <button onclick="printOrder()" class="btn btn-outline btn-block">
                            <i class="fas fa-print"></i> Imprimir Pedido
                        </button>
                        
                        <a href="contact.php?order=<?php echo $order['order_number']; ?>" class="btn btn-outline btn-block">
                            <i class="fas fa-headset"></i> Soporte
                        </a>
                    </div>
                </div>
                
                <!-- Información adicional -->
                <div class="detail-card info-card">
                    <h3><i class="fas fa-info-circle"></i> Información</h3>
                    <ul class="info-list">
                        <li><i class="fas fa-clock"></i> Tiempo estimado: 30-45 min</li>
                        <li><i class="fas fa-shield-alt"></i> Pago seguro garantizado</li>
                        <li><i class="fas fa-phone-alt"></i> Soporte 24/7</li>
                        <li><i class="fas fa-undo"></i> Devoluciones fáciles</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Repetir pedido (reutilizar función del archivo anterior)
function reorderItems(orderId) {
    if (confirm('¿Deseas añadir todos los productos de este pedido al carrito?')) {
        showLoading();
        
        fetch('api/reorder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                showNotification('success', data.message);
                updateCartBadge(data.cart_count);
                
                setTimeout(() => {
                    window.location.href = 'cart.php';
                }, 1500);
            } else {
                showNotification('error', data.message || 'Error al repetir pedido');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('error', 'Error de conexión');
            console.error('Error:', error);
        });
    }
}

// Cancelar pedido
function cancelOrder(orderId) {
    if (confirm('¿Estás seguro de que deseas cancelar este pedido?')) {
        showLoading();
        
        fetch('api/cancel-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order_id=' + orderId
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                showNotification('success', 'Pedido cancelado correctamente');
                
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showNotification('error', data.message || 'Error al cancelar pedido');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('error', 'Error de conexión');
            console.error('Error:', error);
        });
    }
}

// Imprimir pedido
function printOrder() {
    window.print();
}
</script>
