<?php
// ============================================================
//  QuickOrder — my-orders.php
//  Historial de pedidos del cliente autenticado
// ============================================================

require_once 'includes/config.php';
require_once 'includes/functions.php';

require_login();    // redirige a login si no está autenticado

$page_title = 'Mis Pedidos - QuickOrder';
$user_id    = get_user_id();

// ── Paginación ────────────────────────────────────────────────
$per_page = 8;
$current  = max(1, (int)($_GET['page'] ?? 1));

// ── Filtro de estado ─────────────────────────────────────────
$allowed_status = ['','pending','confirmed','preparing','ready',
                   'delivering','delivered','cancelled'];
$filter_status  = in_array($_GET['status'] ?? '', $allowed_status)
                  ? ($_GET['status'] ?? '') : '';

$uid = (int)$user_id;
$where_status = $filter_status
    ? "AND o.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'"
    : '';

// ── Contar total ──────────────────────────────────────────────
$count_sql = "SELECT COUNT(*) FROM orders o
              WHERE o.user_id = $uid $where_status";
$total     = (int)(mysqli_fetch_row(mysqli_query($conn, $count_sql))[0] ?? 0);
$pager     = paginate($total, $per_page, $current);

// ── Obtener pedidos ───────────────────────────────────────────
$orders_sql = "SELECT
    o.id,
    o.order_number,
    o.status,
    o.payment_method,
    o.payment_status,
    COALESCE(o.total_amount,  0) AS total_amount,
    COALESCE(o.subtotal,      0) AS subtotal,
    COALESCE(o.delivery_fee,  0) AS delivery_fee,
    COALESCE(o.discount_amount,0) AS discount_amount,
    o.delivery_type,
    o.delivery_address,
    o.notes,
    o.created_at,
    o.updated_at,
    COUNT(oi.id)                 AS item_count,
    SUM(COALESCE(oi.quantity,0)) AS total_qty
FROM orders o
LEFT JOIN order_items oi ON oi.order_id = o.id
WHERE o.user_id = $uid $where_status
GROUP BY o.id
ORDER BY o.created_at DESC
LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";

$orders_result = mysqli_query($conn, $orders_sql);
$orders        = [];
if ($orders_result) {
    while ($row = mysqli_fetch_assoc($orders_result)) {
        // Protección extra: forzar float en columnas monetarias
        $row['total_amount']   = (float)($row['total_amount']   ?? 0);
        $row['subtotal']       = (float)($row['subtotal']       ?? 0);
        $row['delivery_fee']   = (float)($row['delivery_fee']   ?? 0);
        $row['discount_amount']= (float)($row['discount_amount']?? 0);
        $orders[] = $row;
    }
}

// ── Stats rápidas del usuario ─────────────────────────────────
$stats_sql = "SELECT
    COUNT(*)                                   AS total_orders,
    COALESCE(SUM(total_amount),  0)            AS total_spent,
    COALESCE(SUM(CASE WHEN status='delivered'
               THEN total_amount ELSE 0 END),0) AS spent_delivered,
    SUM(status = 'delivered')                  AS delivered_count,
    SUM(status = 'cancelled')                  AS cancelled_count
FROM orders WHERE user_id = $uid";
$stats_row = mysqli_fetch_assoc(mysqli_query($conn, $stats_sql));
$stats = [
    'total_orders'    => (int)  ($stats_row['total_orders']    ?? 0),
    'total_spent'     => (float)($stats_row['total_spent']     ?? 0),
    'delivered_count' => (int)  ($stats_row['delivered_count'] ?? 0),
    'cancelled_count' => (int)  ($stats_row['cancelled_count'] ?? 0),
];

require_once 'includes/header.php';
?>

<!-- HERO -->
<section style="background:linear-gradient(135deg,#FF6B35,#c0392b);
                color:#fff;padding:2.5rem 0;">
  <div class="container">
    <h1 style="font-size:1.9rem;color:#fff;margin-bottom:.3rem;">
      📦 Mis Pedidos
    </h1>
    <p style="opacity:.85;">
      Consulta el estado de tus pedidos y revisa tu historial de compras
    </p>
  </div>
</section>

<section class="section">
<div class="container">

  <!-- ── Stats rápidas ── -->
  <div class="grid grid-4" style="gap:1rem;margin-bottom:2rem;">
    <div class="card" style="padding:1.25rem;text-align:center;">
      <div style="font-size:2rem;margin-bottom:.3rem;">🛒</div>
      <div style="font-size:1.6rem;font-weight:800;color:var(--primary-color);">
        <?= $stats['total_orders'] ?>
      </div>
      <div style="font-size:.8rem;color:var(--text-secondary);">Pedidos totales</div>
    </div>
    <div class="card" style="padding:1.25rem;text-align:center;">
      <div style="font-size:2rem;margin-bottom:.3rem;">✅</div>
      <div style="font-size:1.6rem;font-weight:800;color:#16a34a;">
        <?= $stats['delivered_count'] ?>
      </div>
      <div style="font-size:.8rem;color:var(--text-secondary);">Entregados</div>
    </div>
    <div class="card" style="padding:1.25rem;text-align:center;">
      <div style="font-size:2rem;margin-bottom:.3rem;">💶</div>
      <div style="font-size:1.6rem;font-weight:800;color:var(--primary-color);">
        <?= format_price($stats['total_spent']) ?>
      </div>
      <div style="font-size:.8rem;color:var(--text-secondary);">Total gastado</div>
    </div>
    <div class="card" style="padding:1.25rem;text-align:center;">
      <div style="font-size:2rem;margin-bottom:.3rem;">❌</div>
      <div style="font-size:1.6rem;font-weight:800;color:#dc2626;">
        <?= $stats['cancelled_count'] ?>
      </div>
      <div style="font-size:.8rem;color:var(--text-secondary);">Cancelados</div>
    </div>
  </div>

  <!-- ── Filtros de estado ── -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center;">
    <span style="font-size:.875rem;color:var(--text-secondary);font-weight:600;">
      Filtrar:
    </span>
    <?php
    $filter_labels = [
        ''           => ['label' => 'Todos',      'emoji' => '📋'],
        'pending'    => ['label' => 'Pendientes', 'emoji' => '⏳'],
        'confirmed'  => ['label' => 'Confirmados','emoji' => '✔️'],
        'preparing'  => ['label' => 'Preparando', 'emoji' => '👨‍🍳'],
        'delivering' => ['label' => 'En camino',  'emoji' => '🚚'],
        'delivered'  => ['label' => 'Entregados', 'emoji' => '✅'],
        'cancelled'  => ['label' => 'Cancelados', 'emoji' => '❌'],
    ];
    foreach ($filter_labels as $val => $info):
        $active = ($filter_status === $val);
    ?>
    <a href="?status=<?= $val ?>"
       style="display:inline-flex;align-items:center;gap:.3rem;
              padding:.4rem .9rem;border-radius:99px;font-size:.82rem;
              font-weight:600;text-decoration:none;transition:all .2s;
              <?= $active
                ? 'background:var(--primary-color);color:#fff;'
                : 'background:#fff;color:var(--text-secondary);border:1.5px solid #e5e7eb;' ?>">
      <?= $info['emoji'] ?> <?= $info['label'] ?>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- ── Lista de pedidos ── -->
  <?php if (empty($orders)): ?>
  <div style="text-align:center;padding:4rem 1rem;">
    <div style="font-size:5rem;margin-bottom:1rem;">📭</div>
    <h2 style="margin-bottom:.75rem;">
      <?= $filter_status ? 'No tienes pedidos con este estado' : 'Aún no has hecho ningún pedido' ?>
    </h2>
    <p style="color:var(--text-secondary);margin-bottom:2rem;">
      <?= $filter_status
        ? 'Prueba a cambiar el filtro o ver todos los pedidos.'
        : 'Explora nuestro menú y haz tu primer pedido.' ?>
    </p>
    <?php if ($filter_status): ?>
      <a href="my-orders.php" class="btn btn-primary">Ver todos los pedidos</a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/index.php#menu" class="btn btn-primary btn-lg">
        <i class="fas fa-utensils"></i> Ver Menú
      </a>
    <?php endif; ?>
  </div>

  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:1rem;">
    <?php foreach ($orders as $order): ?>

    <?php
    // Icono según tipo de entrega
    $delivery_icon = $order['delivery_type'] === 'pickup' ? '🏪' : '🚚';
    $delivery_label = $order['delivery_type'] === 'pickup' ? 'Recogida en local' : 'A domicilio';

    // Progreso visual del pedido
    $steps  = ['pending','confirmed','preparing','ready','delivering','delivered'];
    $cur_step = array_search($order['status'], $steps);
    $pct_progress = ($cur_step !== false && $order['status'] !== 'cancelled')
                    ? round(($cur_step / (count($steps)-1)) * 100)
                    : 0;
    ?>

    <div class="card" style="overflow:hidden;">
      <!-- Cabecera del pedido -->
      <div style="display:flex;flex-wrap:wrap;align-items:center;gap:1rem;
                  padding:1rem 1.25rem;border-bottom:1px solid #f3f4f6;background:#fafafa;">
        <div style="flex:1;min-width:200px;">
          <div style="font-size:.8rem;color:var(--text-secondary);">Número de pedido</div>
          <div style="font-weight:800;font-size:1rem;color:var(--primary-color);">
            <?= htmlspecialchars($order['order_number'] ?? '#' . $order['id']) ?>
          </div>
        </div>
        <div style="flex:1;min-width:160px;">
          <div style="font-size:.8rem;color:var(--text-secondary);">Fecha</div>
          <div style="font-weight:600;font-size:.9rem;">
            <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
          </div>
          <div style="font-size:.78rem;color:var(--text-secondary);">
            <?= time_ago($order['created_at']) ?>
          </div>
        </div>
        <div style="flex:1;min-width:120px;">
          <div style="font-size:.8rem;color:var(--text-secondary);">Tipo</div>
          <div style="font-size:.9rem;"><?= $delivery_icon ?> <?= $delivery_label ?></div>
        </div>
        <div style="flex:1;min-width:120px;">
          <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.2rem;">Estado</div>
          <?= order_status_badge($order['status']) ?>
        </div>
        <div style="text-align:right;">
          <div style="font-size:.8rem;color:var(--text-secondary);">Total</div>
          <div style="font-size:1.3rem;font-weight:800;color:var(--primary-color);">
            <?= format_price($order['total_amount']) ?>
          </div>
          <div style="font-size:.78rem;color:var(--text-secondary);">
            <?= (int)($order['total_qty'] ?? 0) ?> artículos
          </div>
        </div>
      </div>

      <!-- Barra de progreso (solo pedidos no cancelados) -->
      <?php if ($order['status'] !== 'cancelled' && $cur_step !== false): ?>
      <div style="padding:.6rem 1.25rem;background:#f9fafb;border-bottom:1px solid #f3f4f6;">
        <div style="display:flex;justify-content:space-between;
                    font-size:.7rem;color:var(--text-secondary);margin-bottom:.35rem;">
          <?php foreach ($steps as $step):
            $step_labels = [
              'pending'=>'Recibido','confirmed'=>'Confirmado','preparing'=>'Preparando',
              'ready'=>'Listo','delivering'=>'En camino','delivered'=>'Entregado'
            ];
            $is_done  = array_search($step, $steps) <= $cur_step;
            $is_cur   = $step === $order['status'];
          ?>
          <span style="color:<?= $is_done ? 'var(--primary-color)' : 'inherit' ?>;
                       font-weight:<?= $is_cur ? '700' : '400' ?>;">
            <?= $is_done ? '●' : '○' ?> <?= $step_labels[$step] ?>
          </span>
          <?php endforeach; ?>
        </div>
        <div style="background:#e5e7eb;border-radius:99px;height:6px;overflow:hidden;">
          <div style="background:var(--primary-color);height:100%;
                      width:<?= $pct_progress ?>%;border-radius:99px;
                      transition:width .5s ease;"></div>
        </div>
      </div>
      <?php elseif ($order['status'] === 'cancelled'): ?>
      <div style="padding:.5rem 1.25rem;background:#fef2f2;
                  border-bottom:1px solid #fecaca;font-size:.82rem;color:#991b1b;">
        ❌ Este pedido fue cancelado
      </div>
      <?php endif; ?>

      <!-- Desglose de precios + info pago -->
      <div style="display:flex;flex-wrap:wrap;gap:1.5rem;padding:1rem 1.25rem;
                  align-items:flex-start;">

        <div style="flex:1;min-width:200px;">
          <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.5rem;
                      font-weight:600;text-transform:uppercase;letter-spacing:.5px;">
            Desglose
          </div>
          <div style="display:flex;flex-direction:column;gap:.25rem;font-size:.875rem;">
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-secondary);">Subtotal</span>
              <span><?= format_price($order['subtotal']) ?></span>
            </div>
            <?php if ((float)$order['delivery_fee'] > 0): ?>
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-secondary);">Envío</span>
              <span><?= format_price($order['delivery_fee']) ?></span>
            </div>
            <?php else: ?>
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-secondary);">Envío</span>
              <span style="color:#16a34a;font-weight:600;">GRATIS</span>
            </div>
            <?php endif; ?>
            <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?>
            <div style="display:flex;justify-content:space-between;">
              <span style="color:var(--text-secondary);">Descuento</span>
              <span style="color:#16a34a;">−<?= format_price($order['discount_amount']) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;
                        font-weight:700;border-top:1px solid #f3f4f6;padding-top:.25rem;">
              <span>Total</span>
              <span style="color:var(--primary-color);"><?= format_price($order['total_amount']) ?></span>
            </div>
          </div>
        </div>

        <div style="flex:1;min-width:180px;">
          <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.5rem;
                      font-weight:600;text-transform:uppercase;letter-spacing:.5px;">
            Pago
          </div>
          <div style="font-size:.875rem;display:flex;flex-direction:column;gap:.3rem;">
            <div>
              <?php
              $pay_icons = ['cash'=>'💵','card'=>'💳','online'=>'📱'];
              $pay_labels= ['cash'=>'Efectivo','card'=>'Tarjeta','online'=>'Online'];
              $pm = $order['payment_method'] ?? 'cash';
              echo ($pay_icons[$pm] ?? '💳') . ' ' . ($pay_labels[$pm] ?? ucfirst($pm));
              ?>
            </div>
            <div><?= payment_status_badge($order['payment_status'] ?? 'pending') ?></div>
          </div>
        </div>

        <?php if (!empty($order['delivery_address'])): ?>
        <div style="flex:2;min-width:200px;">
          <div style="font-size:.8rem;color:var(--text-secondary);margin-bottom:.5rem;
                      font-weight:600;text-transform:uppercase;letter-spacing:.5px;">
            Dirección de entrega
          </div>
          <div style="font-size:.875rem;color:var(--text-secondary);">
            📍 <?= htmlspecialchars($order['delivery_address']) ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Botones de acción -->
        <div style="display:flex;flex-direction:column;gap:.5rem;flex-shrink:0;">
          <a href="<?= SITE_URL ?>/order-detail.php?id=<?= $order['id'] ?>"
             class="btn btn-outline btn-sm">
            <i class="fas fa-eye"></i> Ver detalle
          </a>
          <?php if (in_array($order['status'], ['delivered'])): ?>
          <a href="<?= SITE_URL ?>/index.php#menu"
             class="btn btn-primary btn-sm">
            🔄 Repetir pedido
          </a>
          <?php endif; ?>
        </div>

      </div><!-- /desglose -->

      <?php if (!empty($order['notes'])): ?>
      <div style="padding:.6rem 1.25rem;background:#fffbeb;
                  border-top:1px solid #fde68a;font-size:.82rem;color:#92400e;">
        📝 Nota: <?= htmlspecialchars($order['notes']) ?>
      </div>
      <?php endif; ?>

    </div><!-- /card pedido -->
    <?php endforeach; ?>
  </div><!-- /lista pedidos -->

  <!-- ── Paginación ── -->
  <?php if ($pager['pages'] > 1): ?>
  <div style="display:flex;justify-content:center;align-items:center;
              gap:.5rem;margin-top:2rem;flex-wrap:wrap;">
    <?php if ($pager['has_prev']): ?>
      <a href="?page=<?= $pager['prev'] ?>&status=<?= $filter_status ?>"
         style="padding:.5rem 1rem;border:1.5px solid #e5e7eb;border-radius:8px;
                text-decoration:none;color:var(--text-primary);font-size:.875rem;">
        ← Anterior
      </a>
    <?php endif; ?>

    <?php for ($p = max(1, $pager['current']-2);
               $p <= min($pager['pages'], $pager['current']+2); $p++): ?>
      <a href="?page=<?= $p ?>&status=<?= $filter_status ?>"
         style="padding:.5rem .9rem;border-radius:8px;text-decoration:none;font-size:.875rem;
                font-weight:<?= $p === $pager['current'] ? '700' : '400' ?>;
                <?= $p === $pager['current']
                  ? 'background:var(--primary-color);color:#fff;border:none;'
                  : 'border:1.5px solid #e5e7eb;color:var(--text-primary);' ?>">
        <?= $p ?>
      </a>
    <?php endfor; ?>

    <?php if ($pager['has_next']): ?>
      <a href="?page=<?= $pager['next'] ?>&status=<?= $filter_status ?>"
         style="padding:.5rem 1rem;border:1.5px solid #e5e7eb;border-radius:8px;
                text-decoration:none;color:var(--text-primary);font-size:.875rem;">
        Siguiente →
      </a>
    <?php endif; ?>

    <span style="font-size:.8rem;color:var(--text-secondary);margin-left:.5rem;">
      Página <?= $pager['current'] ?> de <?= $pager['pages'] ?>
      (<?= $total ?> pedidos)
    </span>
  </div>
  <?php endif; ?>

  <?php endif; // fin empty($orders) ?>

</div>
</section>

<?php require_once 'includes/footer.php'; ?>
