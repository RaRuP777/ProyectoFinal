<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';

require_login();
admin_sync_user_role();
require_admin();

$page_title = 'Resumen de administración';

$totalCategories = (int)db_value("SELECT COUNT(*) FROM categories", 0);
$totalProducts = (int)db_value("SELECT COUNT(*) FROM products", 0);
$pendingOrders = (int)db_value("SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirmed','preparing','ready','delivering')", 0);
$todayReservations = (int)db_value("SELECT COUNT(*) FROM reservations WHERE reservation_date = CURDATE()", 0);

$recentOrders = db_all("SELECT id, order_number, customer_name, total_amount, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5");
$upcomingReservations = db_all("SELECT reservation_number, name, reservation_date, reservation_time, guests, status FROM reservations WHERE reservation_date >= CURDATE() ORDER BY reservation_date ASC, reservation_time ASC LIMIT 5");

admin_render_start($page_title, 'dashboard');
?>

<div class="admin-page-header">
    <div>
        <h1>Panel de administración</h1>
</div>
    <div class="admin-pill"><i class="fas fa-shield-halved"></i> Acceso restringido</div>
</div>

<div class="admin-grid cols-3" style="margin-bottom:1rem;">
    <div class="admin-stat">
        <div class="icon"><i class="fas fa-layer-group"></i></div>
        <div class="value"><?php echo $totalCategories; ?></div>
        <div class="label">Categorías registradas</div>
    </div>
    <div class="admin-stat">
        <div class="icon"><i class="fas fa-burger"></i></div>
        <div class="value"><?php echo $totalProducts; ?></div>
        <div class="label">Productos en catálogo</div>
    </div>
    <div class="admin-stat">
        <div class="icon"><i class="fas fa-receipt"></i></div>
        <div class="value"><?php echo $pendingOrders; ?></div>
        <div class="label">Pedidos activos</div>
    </div>
</div>

<div class="admin-grid cols-2" style="margin-bottom:1rem;">
    <div class="admin-card admin-highlight">
        <h2 style="margin-bottom:.5rem;">Gestión del catálogo</h2>
        <p class="admin-note" style="margin-bottom:1rem;">Añade nuevas categorías, modifica las existentes y administra el catálogo de productos con imagen, precio, stock y destacados.</p>
        <div class="admin-form-actions">
            <a class="admin-btn primary" href="/admin/categories.php"><i class="fas fa-layer-group"></i> Gestionar categorías</a>
            <a class="admin-btn light" href="/admin/products.php"><i class="fas fa-burger"></i> Gestionar productos</a>
        </div>
    </div>
    <div class="admin-card admin-highlight">
        <h2 style="margin-bottom:.5rem;">Operaciones del día</h2>
        <p class="admin-note" style="margin-bottom:1rem;">Desde aquí puedes actualizar el estado de los pedidos y revisar rápidamente las reservas previstas para hoy.</p>
        <div class="admin-kpi-list">
            <div>
                <strong style="display:block;font-size:1.4rem;"><?php echo $todayReservations; ?></strong>
                <span class="admin-note">Reservas hoy</span>
            </div>
            <div>
                <strong style="display:block;font-size:1.4rem;"><?php echo (int)db_value("SELECT COUNT(*) FROM orders WHERE status='delivered' AND DATE(updated_at)=CURDATE()", 0); ?></strong>
                <span class="admin-note">Pedidos entregados hoy</span>
            </div>
            <div>
                <strong style="display:block;font-size:1.4rem;"><?php echo (int)db_value("SELECT COUNT(*) FROM products WHERE stock <= 5", 0); ?></strong>
                <span class="admin-note">Productos con stock bajo</span>
            </div>
        </div>
    </div>
</div>

<div class="admin-grid cols-2">
    <section class="admin-card">
        <div class="admin-page-header" style="margin-bottom:1rem;">
            <div>
                <h2 style="margin:0;">Últimos pedidos</h2>
                <p>Acceso rápido para cambiar estados.</p>
            </div>
            <a class="admin-btn secondary" href="/admin/orders.php"><i class="fas fa-arrow-right"></i> Ver todos</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <div class="admin-empty">Todavía no hay pedidos registrados.</div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table" style="min-width:0;">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($order['order_number']); ?></strong><br>
                                <span class="admin-note"><?php echo e(format_datetime($order['created_at'])); ?></span>
                            </td>
                            <td><?php echo e($order['customer_name'] ?: 'Cliente'); ?></td>
                            <td><?php echo e(format_price($order['total_amount'])); ?></td>
                            <td><?php echo order_status_badge((string)$order['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card">
        <div class="admin-page-header" style="margin-bottom:1rem;">
            <div>
                <h2 style="margin:0;">Próximas reservas</h2>
                <p>Solo visualización, sin editar.</p>
            </div>
            <a class="admin-btn secondary" href="/admin/reservations.php"><i class="fas fa-arrow-right"></i> Ver todas</a>
        </div>

        <?php if (empty($upcomingReservations)): ?>
            <div class="admin-empty">No hay reservas próximas en este momento.</div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table" style="min-width:0;">
                    <thead>
                        <tr>
                            <th>Reserva</th>
                            <th>Cliente</th>
                            <th>Comensales</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($upcomingReservations as $reservation): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($reservation['reservation_number']); ?></strong><br>
                                <span class="admin-note"><?php echo e(format_date($reservation['reservation_date']) . ' · ' . substr((string)$reservation['reservation_time'], 0, 5)); ?></span>
                            </td>
                            <td><?php echo e($reservation['name']); ?></td>
                            <td><?php echo (int)$reservation['guests']; ?></td>
                            <td><?php echo reservation_status_badge((string)$reservation['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php admin_render_end(); ?>
