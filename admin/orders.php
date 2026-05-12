<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';

require_login();
admin_sync_user_role();
require_admin();

$page_title = 'Pedidos';
$errors = [];
$statusFilter = trim((string)($_GET['status'] ?? ''));
$validStatuses = [
    'pending' => 'Pendiente',
    'confirmed' => 'Confirmado',
    'preparing' => 'Preparando',
    'ready' => 'Listo',
    'delivering' => 'En camino',
    'delivered' => 'Entregado',
    'cancelled' => 'Cancelado',
];

if (request_method_is('POST') && (string)($_POST['action'] ?? '') === 'update_order_status') {
    if (!verify_csrf_token($_POST['_token'] ?? null)) {
        $errors[] = 'La sesión del formulario ha caducado. Vuelve a intentarlo.';
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim((string)($_POST['status'] ?? ''));

    if ($orderId <= 0 || !isset($validStatuses[$newStatus])) {
        $errors[] = 'Los datos del pedido no son válidos.';
    }

    if (empty($errors)) {
        $sql = "UPDATE orders SET status='" . db_escape($newStatus) . "', updated_at=NOW() WHERE id=$orderId LIMIT 1";
        if (db_query($sql)) {
            set_alert('success', 'Estado del pedido actualizado correctamente.');
            $redirect = 'admin/orders.php';
            if ($statusFilter !== '' && isset($validStatuses[$statusFilter])) {
                $redirect .= '?status=' . urlencode($statusFilter);
            }
            redirect($redirect);
        }
        $errors[] = 'No se pudo actualizar el estado del pedido.';
    }
}

$where = '';
if ($statusFilter !== '' && isset($validStatuses[$statusFilter])) {
    $where = "WHERE o.status='" . db_escape($statusFilter) . "'";
}

$orders = db_all("SELECT o.*, COUNT(oi.id) AS items_count
                  FROM orders o
                  LEFT JOIN order_items oi ON oi.order_id = o.id
                  $where
                  GROUP BY o.id
                  ORDER BY o.created_at DESC");

admin_render_start($page_title, 'orders');
?>

<div class="admin-page-header">
    <div>
        <h1>Gestión de pedidos</h1>
        <p>La única operación administrativa permitida aquí es cambiar el estado de los pedidos.</p>
    </div>
    <div class="admin-pill"><i class="fas fa-truck"></i> Pedidos: <?php echo count($orders); ?></div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
        <i class="fas fa-circle-exclamation"></i>
        <?php echo e(implode(' ', $errors)); ?>
    </div>
<?php endif; ?>

<section class="admin-card">
    <div class="admin-search-row">
        <div>
            <h2 style="margin:0;">Listado de pedidos</h2>
            <p class="admin-note" style="margin:.35rem 0 0;">Filtra por estado para centrarte en la operativa del día.</p>
        </div>
        <form class="admin-inline-form" method="get">
            <select name="status">
                <option value="">Todos los estados</option>
                <?php foreach ($validStatuses as $value => $label): ?>
                    <option value="<?php echo e($value); ?>" <?php echo $statusFilter === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="admin-btn secondary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            <?php if ($statusFilter !== ''): ?>
                <a class="admin-btn light" href="<?php echo SITE_URL; ?>/admin/orders.php"><i class="fas fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($orders)): ?>
        <div class="admin-empty">No se han encontrado pedidos con ese criterio.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Entrega</th>
                        <th>Pago</th>
                        <th>Estado actual</th>
                        <th>Cambiar estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($order['order_number']); ?></strong><br>
                            <span class="admin-note"><?php echo e(format_datetime($order['created_at'])); ?></span><br>
                            <span class="admin-note"><?php echo (int)$order['items_count']; ?> artículo(s)</span>
                        </td>
                        <td>
                            <strong><?php echo e($order['customer_name'] ?: 'Cliente'); ?></strong><br>
                            <span class="admin-note"><?php echo e($order['customer_email'] ?: '-'); ?></span><br>
                            <span class="admin-note"><?php echo e($order['customer_phone'] ?: '-'); ?></span>
                        </td>
                        <td><?php echo e(format_price($order['total_amount'])); ?></td>
                        <td>
                            <span class="admin-note"><?php echo $order['delivery_type'] === 'pickup' ? 'Recogida en local' : 'Entrega a domicilio'; ?></span>
                        </td>
                        <td><?php echo payment_status_badge((string)$order['payment_status']); ?></td>
                        <td><?php echo order_status_badge((string)$order['status']); ?></td>
                        <td>
                            <form method="post" class="admin-inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_order_status">
                                <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                <select name="status">
                                    <?php foreach ($validStatuses as $value => $label): ?>
                                        <option value="<?php echo e($value); ?>" <?php echo (string)$order['status'] === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="admin-btn primary" type="submit"><i class="fas fa-floppy-disk"></i> Actualizar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_render_end(); ?>
