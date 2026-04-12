<?php
// ============================================================
// QuickOrder — order-detail.php
// Detalle de pedido robusto, null-safe y con estilo tipo mockup
// ============================================================

require_once 'includes/config.php';
require_once 'includes/functions.php';

require_login();

$page_title = 'Detalle del pedido - QuickOrder';
$user_id = get_user_id();
$order_id = (int)($_GET['id'] ?? 0);

if ($order_id <= 0) {
    set_alert('error', 'Pedido no válido.');
    redirect('my-orders.php');
}

$order_sql = "SELECT
    o.id,
    o.user_id,
    o.order_number,
    o.total_amount,
    o.subtotal,
    o.tax_amount,
    o.delivery_fee,
    o.discount_amount,
    o.status,
    o.payment_method,
    o.payment_status,
    o.delivery_type,
    o.delivery_address,
    o.delivery_city,
    o.customer_name,
    o.customer_email,
    o.customer_phone,
    o.notes,
    o.estimated_time,
    o.created_at,
    o.updated_at,
    COUNT(oi.id) AS item_count,
    SUM(COALESCE(oi.quantity, 0)) AS total_qty
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = o.id
WHERE o.id = {$order_id} AND o.user_id = " . (int)$user_id . "
GROUP BY o.id
LIMIT 1";

$order = db_one($order_sql);

if (!$order) {
    require_once 'includes/header.php';
    ?>
    <section class="section" style="min-height:60vh;display:flex;align-items:center;">
        <div class="container" style="max-width:760px;">
            <div class="card" style="padding:2.5rem;text-align:center;border-radius:24px;">
                <div style="font-size:4rem;margin-bottom:1rem;">📭</div>
                <h1 style="margin-bottom:.75rem;">No se ha encontrado el pedido</h1>
                <p style="color:var(--text-secondary);margin-bottom:1.5rem;">
                    El pedido no existe o no tienes permisos para acceder a él.
                </p>
                <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
                    <a href="<?= site_url('my-orders.php') ?>" class="btn btn-primary">Volver a mis pedidos</a>
                    <a href="<?= site_url('index.php#menu') ?>" class="btn btn-outline">Ir al menú</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    require_once 'includes/footer.php';
    exit;
}

$order['total_amount'] = (float)($order['total_amount'] ?? 0);
$order['subtotal'] = (float)($order['subtotal'] ?? 0);
$order['tax_amount'] = (float)($order['tax_amount'] ?? 0);
$order['delivery_fee'] = (float)($order['delivery_fee'] ?? 0);
$order['discount_amount'] = (float)($order['discount_amount'] ?? 0);
$order['estimated_time'] = (int)($order['estimated_time'] ?? 30);
$order['total_qty'] = (int)($order['total_qty'] ?? 0);
$order['item_count'] = (int)($order['item_count'] ?? 0);

$item_sql = "SELECT
    oi.id,
    oi.product_id,
    COALESCE(oi.name, p.name, 'Producto') AS item_name,
    COALESCE(oi.price, 0) AS price,
    COALESCE(oi.quantity, 0) AS quantity,
    COALESCE(oi.subtotal, 0) AS subtotal,
    oi.notes,
    p.image,
    c.name AS category_name
FROM order_items oi
LEFT JOIN products p ON p.id = oi.product_id
LEFT JOIN categories c ON c.id = p.category_id
WHERE oi.order_id = {$order_id}
ORDER BY oi.id ASC";

$order_items = db_all($item_sql);
foreach ($order_items as &$item) {
    $item['price'] = (float)($item['price'] ?? 0);
    $item['quantity'] = (int)($item['quantity'] ?? 0);
    $item['subtotal'] = (float)($item['subtotal'] ?? 0);
}
unset($item);

$delivery_type = (string)($order['delivery_type'] ?? 'delivery');
$delivery_icon = $delivery_type === 'pickup' ? '🏪' : '🚚';
$delivery_label = $delivery_type === 'pickup' ? 'Recogida en local' : 'Entrega a domicilio';

$payment_method = (string)($order['payment_method'] ?? 'cash');
$payment_labels = [
    'cash' => 'Efectivo',
    'card' => 'Tarjeta',
    'online' => 'Pago online',
];
$payment_icons = [
    'cash' => '💵',
    'card' => '💳',
    'online' => '📱',
];

$status_steps = ['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered'];
$status_labels = [
    'pending' => 'Recibido',
    'confirmed' => 'Confirmado',
    'preparing' => 'Preparando',
    'ready' => 'Listo',
    'delivering' => 'En camino',
    'delivered' => 'Entregado',
    'cancelled' => 'Cancelado',
];
$current_step = array_search((string)$order['status'], $status_steps, true);
$progress = ($current_step !== false && (string)$order['status'] !== 'cancelled')
    ? (int)round(($current_step / (count($status_steps) - 1)) * 100)
    : 0;

$can_repeat = in_array((string)$order['status'], ['delivered', 'ready'], true);

require_once 'includes/header.php';
?>

<section class="hero" style="padding:3.25rem 0 2.5rem;background:linear-gradient(135deg,#FF6B35 0%,#ff8a3d 48%,#c0392b 100%);color:#fff;">
    <div class="container">
        <div style="display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <span class="badge" style="background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.24);margin-bottom:1rem;display:inline-block;">📦 Detalle del pedido</span>
                <h1 class="hero-title" style="color:#fff;margin-bottom:.35rem;">Pedido <?= e((string)($order['order_number'] ?? ('#' . $order['id']))) ?></h1>
                <p class="hero-subtitle" style="color:rgba(255,255,255,.88);max-width:760px;">
                    Consulta los productos, el estado actual, la dirección de entrega y el resumen económico de tu pedido.
                </p>
            </div>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <a href="<?= site_url('my-orders.php') ?>" class="btn btn-outline" style="background:#fff;color:#FF6B35;border-color:#fff;">← Mis pedidos</a>
                <a href="<?= site_url('index.php#menu') ?>" class="btn btn-outline" style="border-color:rgba(255,255,255,.45);color:#fff;">Ver menú</a>
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:var(--bg-secondary);">
    <div class="container order-detail-layout">
        <div class="order-main-column">
            <div class="order-card order-overview-card">
                <div class="order-card-head">
                    <div>
                        <h2>Resumen general</h2>
                        <p>Creado el <?= format_datetime((string)($order['created_at'] ?? ''), 'd/m/Y H:i') ?> · <?= e(time_ago((string)($order['created_at'] ?? ''))) ?></p>
                    </div>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                        <?= order_status_badge((string)($order['status'] ?? 'pending')) ?>
                        <?= payment_status_badge((string)($order['payment_status'] ?? 'pending')) ?>
                    </div>
                </div>

                <div class="order-kpis-grid">
                    <div class="mini-kpi">
                        <div class="mini-kpi-icon">🧾</div>
                        <div class="mini-kpi-value"><?= e((string)($order['order_number'] ?? ('#' . $order['id']))) ?></div>
                        <div class="mini-kpi-label">Número de pedido</div>
                    </div>
                    <div class="mini-kpi">
                        <div class="mini-kpi-icon"><?= $delivery_icon ?></div>
                        <div class="mini-kpi-value"><?= e($delivery_label) ?></div>
                        <div class="mini-kpi-label">Tipo de entrega</div>
                    </div>
                    <div class="mini-kpi">
                        <div class="mini-kpi-icon">🛍️</div>
                        <div class="mini-kpi-value"><?= (int)$order['total_qty'] ?></div>
                        <div class="mini-kpi-label">Unidades</div>
                    </div>
                    <div class="mini-kpi">
                        <div class="mini-kpi-icon">💶</div>
                        <div class="mini-kpi-value"><?= format_price($order['total_amount']) ?></div>
                        <div class="mini-kpi-label">Importe total</div>
                    </div>
                </div>

                <?php if ((string)$order['status'] !== 'cancelled' && $current_step !== false): ?>
                    <div class="timeline-block">
                        <div class="timeline-head">
                            <strong>Estado del pedido</strong>
                            <span><?= $progress ?>% completado</span>
                        </div>
                        <div class="timeline-bar"><div class="timeline-fill" style="width:<?= $progress ?>%;"></div></div>
                        <div class="timeline-steps">
                            <?php foreach ($status_steps as $step): ?>
                                <?php
                                $step_index = array_search($step, $status_steps, true);
                                $is_done = ($current_step !== false && $step_index <= $current_step);
                                $is_current = ((string)$order['status'] === $step);
                                ?>
                                <div class="timeline-step <?= $is_done ? 'done' : '' ?> <?= $is_current ? 'current' : '' ?>">
                                    <span><?= $is_done ? '●' : '○' ?></span>
                                    <small><?= e($status_labels[$step] ?? ucfirst($step)) ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ((string)$order['status'] === 'cancelled'): ?>
                    <div class="cancelled-box">❌ Este pedido fue cancelado.</div>
                <?php endif; ?>
            </div>

            <div class="order-card">
                <div class="order-card-head">
                    <div>
                        <h2>Productos del pedido</h2>
                        <p><?= count($order_items) ?> línea<?= count($order_items) !== 1 ? 's' : '' ?> en este pedido</p>
                    </div>
                </div>

                <?php if (empty($order_items)): ?>
                    <div style="padding:1.5rem;">
                        <div class="empty-soft">No hay líneas de pedido registradas.</div>
                    </div>
                <?php else: ?>
                    <div class="order-items-list">
                        <?php foreach ($order_items as $item): ?>
                            <?php
                            $image = trim((string)($item['image'] ?? ''));
                            $image_url = $image !== '' ? site_url('uploads/products/' . ltrim($image, '/')) : '';
                            ?>
                            <div class="order-item-row">
                                <div class="order-item-media">
                                    <?php if ($image !== ''): ?>
                                        <img src="<?= e($image_url) ?>" alt="<?= e((string)($item['item_name'] ?? 'Producto')) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="order-item-fallback" style="display:none;">🍽️</div>
                                    <?php else: ?>
                                        <div class="order-item-fallback">🍽️</div>
                                    <?php endif; ?>
                                </div>
                                <div class="order-item-info">
                                    <div class="order-item-top">
                                        <div>
                                            <h3><?= e((string)($item['item_name'] ?? 'Producto')) ?></h3>
                                            <p>
                                                <?= !empty($item['category_name']) ? e((string)$item['category_name']) . ' · ' : '' ?>
                                                <?= format_price($item['price']) ?> / unidad
                                            </p>
                                        </div>
                                        <div class="order-item-subtotal"><?= format_price($item['subtotal']) ?></div>
                                    </div>
                                    <div class="order-item-bottom">
                                        <span class="qty-pill">Cantidad: <?= (int)($item['quantity'] ?? 0) ?></span>
                                        <?php if (!empty($item['notes'])): ?>
                                            <span class="note-pill">📝 <?= e((string)$item['notes']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <aside class="order-side-column">
            <div class="order-card sticky-card">
                <div class="order-card-head simple">
                    <div>
                        <h2>Resumen económico</h2>
                        <p>Desglose del importe del pedido</p>
                    </div>
                </div>

                <div class="summary-lines">
                    <div><span>Subtotal</span><strong><?= format_price($order['subtotal']) ?></strong></div>
                    <div><span>Envío</span><strong><?= $order['delivery_fee'] > 0 ? format_price($order['delivery_fee']) : 'Gratis' ?></strong></div>
                    <div><span>IVA</span><strong><?= format_price($order['tax_amount']) ?></strong></div>
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div><span>Descuento</span><strong style="color:#16a34a;">-<?= format_price($order['discount_amount']) ?></strong></div>
                    <?php endif; ?>
                    <div class="total-line"><span>Total</span><strong><?= format_price($order['total_amount']) ?></strong></div>
                </div>

                <div class="side-box-group">
                    <div class="info-box soft-orange">
                        <h3>Pago</h3>
                        <p><?= e($payment_icons[$payment_method] ?? '💳') ?> <?= e($payment_labels[$payment_method] ?? ucfirst($payment_method)) ?></p>
                        <div style="margin-top:.4rem;"><?= payment_status_badge((string)($order['payment_status'] ?? 'pending')) ?></div>
                    </div>

                    <div class="info-box soft-slate">
                        <h3>Cliente</h3>
                        <p><strong><?= e((string)($order['customer_name'] ?? get_user_name())) ?></strong></p>
                        <p><?= e((string)($order['customer_email'] ?? get_user_email())) ?></p>
                        <?php if (!empty($order['customer_phone'])): ?><p><?= e((string)$order['customer_phone']) ?></p><?php endif; ?>
                    </div>

                    <div class="info-box soft-blue">
                        <h3>Entrega</h3>
                        <p><?= $delivery_icon ?> <?= e($delivery_label) ?></p>
                        <?php if ($delivery_type === 'delivery'): ?>
                            <p><?= e(trim((string)($order['delivery_address'] ?? ''))) !== '' ? e((string)$order['delivery_address']) : 'Dirección no disponible' ?></p>
                            <?php if (!empty($order['delivery_city'])): ?><p><?= e((string)$order['delivery_city']) ?></p><?php endif; ?>
                        <?php else: ?>
                            <p>Recogerás el pedido en el local.</p>
                        <?php endif; ?>
                        <p>Tiempo estimado: <?= (int)$order['estimated_time'] ?> min</p>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div class="info-box soft-yellow">
                            <h3>Observaciones</h3>
                            <p><?= nl2br(e((string)$order['notes'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="side-actions">
                    <a href="<?= site_url('my-orders.php') ?>" class="btn btn-outline">← Volver</a>
                    <?php if ($can_repeat): ?>
                        <a href="<?= site_url('index.php#menu') ?>" class="btn btn-primary">🔄 Pedir de nuevo</a>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</section>

<style>
.order-detail-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(320px,420px);gap:2rem;align-items:start}
.order-main-column,.order-side-column{display:flex;flex-direction:column;gap:1.5rem}
.order-card{background:#fff;border-radius:24px;box-shadow:0 18px 48px rgba(15,23,42,.08);overflow:hidden;border:1px solid rgba(255,255,255,.6)}
.order-card-head{padding:1.4rem 1.5rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}
.order-card-head.simple{padding-bottom:1.1rem}
.order-card-head h2{margin:0 0 .25rem;font-size:1.3rem;color:#0f172a}
.order-card-head p{margin:0;color:#64748b}
.order-kpis-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;padding:1.5rem}
.mini-kpi{border:1px solid #eef2f7;border-radius:18px;padding:1rem;background:linear-gradient(180deg,#fff,#fcfcfd);text-align:center}
.mini-kpi-icon{font-size:1.7rem;margin-bottom:.45rem}
.mini-kpi-value{font-size:1rem;font-weight:800;color:#0f172a;word-break:break-word}
.mini-kpi-label{font-size:.82rem;color:#64748b;margin-top:.2rem}
.timeline-block{padding:0 1.5rem 1.5rem}
.timeline-head{display:flex;justify-content:space-between;gap:1rem;align-items:center;margin-bottom:.7rem;font-size:.92rem;color:#475569}
.timeline-bar{height:8px;border-radius:999px;background:#e2e8f0;overflow:hidden}
.timeline-fill{height:100%;border-radius:999px;background:linear-gradient(135deg,#FF6B35,#FFA726)}
.timeline-steps{display:grid;grid-template-columns:repeat(6,1fr);gap:.5rem;margin-top:.85rem}
.timeline-step{display:flex;flex-direction:column;align-items:center;gap:.2rem;text-align:center;color:#94a3b8;font-size:.78rem}
.timeline-step.done{color:#ea580c}
.timeline-step.current{font-weight:800;color:#c2410c}
.cancelled-box{margin:0 1.5rem 1.5rem;padding:1rem 1.1rem;border-radius:16px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-weight:700}
.order-items-list{padding:0 1.25rem 1.25rem}
.order-item-row{display:flex;gap:1rem;padding:1rem 0;border-bottom:1px solid #f1f5f9}
.order-item-row:last-child{border-bottom:none}
.order-item-media{width:88px;height:88px;flex-shrink:0}
.order-item-media img,.order-item-fallback{width:88px;height:88px;border-radius:18px;object-fit:cover;background:linear-gradient(135deg,#fff1eb,#ffe4d6);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#FF6B35;border:1px solid #fde7dc}
.order-item-info{flex:1;display:flex;flex-direction:column;justify-content:center;gap:.75rem;min-width:0}
.order-item-top{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}
.order-item-top h3{margin:0 0 .25rem;font-size:1.04rem;color:#0f172a}
.order-item-top p{margin:0;color:#64748b;font-size:.9rem}
.order-item-subtotal{font-size:1.05rem;font-weight:800;color:#ea580c;white-space:nowrap}
.order-item-bottom{display:flex;gap:.5rem;flex-wrap:wrap}
.qty-pill,.note-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.45rem .8rem;border-radius:999px;font-size:.82rem;font-weight:700}
.qty-pill{background:#fff7ed;color:#c2410c}
.note-pill{background:#f8fafc;color:#475569}
.summary-lines{padding:0 1.5rem 1.25rem;display:flex;flex-direction:column;gap:.85rem}
.summary-lines>div{display:flex;justify-content:space-between;gap:1rem;align-items:center;color:#475569}
.summary-lines .total-line{padding-top:.85rem;border-top:1px dashed #cbd5e1;font-size:1.12rem;font-weight:800;color:#0f172a}
.side-box-group{display:flex;flex-direction:column;gap:.85rem;padding:0 1.5rem 1.4rem}
.info-box{border-radius:18px;padding:1rem 1.05rem}
.info-box h3{margin:0 0 .45rem;font-size:.96rem;color:#0f172a}
.info-box p{margin:.2rem 0;color:#475569;font-size:.92rem}
.soft-orange{background:#fff7ed;border:1px solid #fed7aa}
.soft-slate{background:#f8fafc;border:1px solid #e2e8f0}
.soft-blue{background:#eff6ff;border:1px solid #bfdbfe}
.soft-yellow{background:#fefce8;border:1px solid #fde68a}
.side-actions{display:flex;gap:.75rem;flex-wrap:wrap;padding:0 1.5rem 1.5rem}
.sticky-card{position:sticky;top:92px}
.empty-soft{padding:1rem;border-radius:16px;background:#f8fafc;color:#64748b}
@media (max-width: 1100px){.order-detail-layout{grid-template-columns:1fr}.sticky-card{position:static}}
@media (max-width: 900px){.order-kpis-grid{grid-template-columns:repeat(2,1fr)}.timeline-steps{grid-template-columns:repeat(3,1fr)}}
@media (max-width: 640px){.order-kpis-grid{grid-template-columns:1fr}.order-item-row{flex-direction:column}.order-item-media,.order-item-media img,.order-item-fallback{width:100%;height:180px}.order-item-top{flex-direction:column}.timeline-head{flex-direction:column;align-items:flex-start}.timeline-steps{grid-template-columns:repeat(2,1fr)}.side-actions .btn{width:100%;text-align:center}}
</style>

<?php require_once 'includes/footer.php'; ?>
