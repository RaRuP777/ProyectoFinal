<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page_title = 'Mis Pedidos';

// Verificar que el usuario esté logueado
if (!is_logged_in()) {
    set_flash('error', 'Debes iniciar sesión para ver tus pedidos');
    redirect('login.php?redirect=my-orders');
}

$user_id = $_SESSION['user_id'];

// Obtener filtros de búsqueda
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';
$search_query = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Paginación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construir consulta SQL con filtros
$where_conditions = ["user_id = ?"];
$params = [$user_id];
$param_types = "i";

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($search_query)) {
    $where_conditions[] = "order_number LIKE ?";
    $params[] = "%{$search_query}%";
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Contar total de pedidos (para paginación)
$count_sql = "SELECT COUNT(*) as total FROM orders WHERE {$where_clause}";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($param_types, ...$params);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_orders / $per_page);

// Obtener pedidos del usuario con paginación
$sql = "SELECT * FROM orders WHERE {$where_clause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$param_types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// Función para obtener el badge del estado
function get_status_badge($status) {
    $badges = [
        'pending' => ['class' => 'badge-warning', 'icon' => 'fa-clock', 'text' => 'Pendiente'],
        'confirmed' => ['class' => 'badge-info', 'icon' => 'fa-check-circle', 'text' => 'Confirmado'],
        'preparing' => ['class' => 'badge-primary', 'icon' => 'fa-utensils', 'text' => 'Preparando'],
        'ready' => ['class' => 'badge-success', 'icon' => 'fa-motorcycle', 'text' => 'Listo'],
        'delivered' => ['class' => 'badge-success', 'icon' => 'fa-check-double', 'text' => 'Entregado'],
        'cancelled' => ['class' => 'badge-danger', 'icon' => 'fa-times-circle', 'text' => 'Cancelado']
    ];
    
    return $badges[$status] ?? $badges['pending'];
}

include 'includes/header.php';
?>

<section class="orders-section">
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-box"></i> Mis Pedidos</h1>
            <p>Historial completo de tus pedidos</p>
        </div>
        
        <!-- Filtros y búsqueda -->
        <div class="orders-filters">
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Filtrar por estado:</label>
                <select id="statusFilter" class="form-control" onchange="applyFilters()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Todos los pedidos</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendientes</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmados</option>
                    <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>En preparación</option>
                    <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Listos</option>
                    <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Entregados</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelados</option>
                </select>
            </div>
            
            <div class="search-group">
                <label><i class="fas fa-search"></i> Buscar pedido:</label>
                <input type="text" 
                       id="searchInput" 
                       class="form-control" 
                       placeholder="Número de pedido..."
                       value="<?php echo htmlspecialchars($search_query); ?>"
                       onkeyup="handleSearch(event)">
            </div>
        </div>
        
        <!-- Estadísticas rápidas -->
        <div class="orders-stats">
            <?php
            // Obtener estadísticas
            $stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status IN ('pending', 'confirmed', 'preparing', 'ready') THEN 1 ELSE 0 END) as active,
                SUM(total_amount) as total_spent
                FROM orders WHERE user_id = ?";
            $stats_stmt = $conn->prepare($stats_sql);
            $stats_stmt->bind_param("i", $user_id);
            $stats_stmt->execute();
            $stats = $stats_stmt->get_result()->fetch_assoc();
            $stats_stmt->close();
            ?>
            
            <div class="stat-card">
                <i class="fas fa-shopping-bag"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['total']; ?></span>
                    <span class="stat-label">Pedidos Totales</span>
                </div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['delivered']; ?></span>
                    <span class="stat-label">Entregados</span>
                </div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo $stats['active']; ?></span>
                    <span class="stat-label">En Proceso</span>
                </div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-euro-sign"></i>
                <div class="stat-info">
                    <span class="stat-value"><?php echo format_price($stats['total_spent']); ?></span>
                    <span class="stat-label">Total Gastado</span>
                </div>
            </div>
        </div>
        
        <!-- Lista de pedidos -->
        <div class="orders-list">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <?php
                    $status_badge = get_status_badge($order['status']);
                    
                    // Obtener número de items del pedido
                    $items_stmt = $conn->prepare("SELECT COUNT(*) as items_count, SUM(quantity) as total_items FROM order_items WHERE order_id = ?");
                    $items_stmt->bind_param("i", $order['id']);
                    $items_stmt->execute();
                    $items_info = $items_stmt->get_result()->fetch_assoc();
                    $items_stmt->close();
                    ?>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">
                                <i class="fas fa-receipt"></i>
                                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                            </div>
                            
                            <span class="order-badge <?php echo $status_badge['class']; ?>">
                                <i class="fas <?php echo $status_badge['icon']; ?>"></i>
                                <?php echo $status_badge['text']; ?>
                            </span>
                        </div>
                        
                        <div class="order-body">
                            <div class="order-info-grid">
                                <div class="info-item">
                                    <i class="fas fa-calendar"></i>
                                    <div>
                                        <small>Fecha</small>
                                        <strong><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-shopping-bag"></i>
                                    <div>
                                        <small>Productos</small>
                                        <strong><?php echo $items_info['total_items']; ?> artículo(s)</strong>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-euro-sign"></i>
                                    <div>
                                        <small>Total</small>
                                        <strong><?php echo format_price($order['total_amount']); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <small>Dirección</small>
                                        <strong><?php echo htmlspecialchars(substr($order['delivery_address'], 0, 30)) . '...'; ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($order['notes']): ?>
                                <div class="order-notes">
                                    <i class="fas fa-comment"></i>
                                    <em><?php echo htmlspecialchars($order['notes']); ?></em>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-footer">
                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </a>
                            
                            <?php if ($order['status'] === 'delivered'): ?>
                                <button onclick="reorderItems(<?php echo $order['id']; ?>)" class="btn btn-primary btn-sm">
                                    <i class="fas fa-redo"></i> Repetir Pedido
                                </button>
                            <?php endif; ?>
                            
                            <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="page-link active"><?php echo $i; ?></span>
                            <?php elseif ($i == 1 || $i == $total_pages || abs($i - $page) <= 2): ?>
                                <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="page-link">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif (abs($i - $page) == 3): ?>
                                <span class="page-link">...</span>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="page-link">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Sin pedidos -->
                <div class="no-orders">
                    <i class="fas fa-box-open"></i>
                    <h2>No tienes pedidos
                    
                    <?php if ($status_filter !== 'all'): ?>
                        con este estado
                    <?php elseif (!empty($search_query)): ?>
                        que coincidan con tu búsqueda
                    <?php endif; ?>
                    </h2>
                    <p>¿Qué tal si haces tu primer pedido?</p>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-utensils"></i> Ver Carta
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script>
// Aplicar filtros
function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    let url = 'my-orders.php?';
    if (status !== 'all') url += 'status=' + status + '&';
    if (search) url += 'search=' + encodeURIComponent(search);
    
    window.location.href = url;
}

// Búsqueda con Enter
function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

// Repetir pedido
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
                
                // Redirigir al carrito después de 1.5 segundos
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
                
                // Recargar página después de 1.5 segundos
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
</script>
