<?php
// ============================================================
//  QuickOrder — cart.php
//  Carrito de compra con manejo seguro de sesión
// ============================================================

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Inicializar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Inicializar carrito si no existe ─────────────────────────
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$page_title = 'Carrito de Compra - QuickOrder';
$alert      = null;

// ============================================================
// ACCIONES (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

    // ── AÑADIR AL CARRITO ─────────────────────────────────
    if ($action === 'add' && $product_id > 0) {
        // Verificar que el producto existe en BD
        $pid = mysqli_real_escape_string($conn, $product_id);
        $res = mysqli_query($conn,
            "SELECT id, name, price, discount_price, stock, image
             FROM products WHERE id = $pid LIMIT 1");

        if ($res && $row = mysqli_fetch_assoc($res)) {
            $stock = (int)($row['stock'] ?? 99);
            $price = !empty($row['discount_price']) && $row['discount_price'] < $row['price']
                     ? (float)$row['discount_price']
                     : (float)$row['price'];

            if (isset($_SESSION['cart'][$product_id])) {
                // Ya existe → sumar cantidad respetando stock
                $new_qty = $_SESSION['cart'][$product_id]['quantity'] + $quantity;
                $_SESSION['cart'][$product_id]['quantity'] = min($new_qty, $stock);
            } else {
                // Nuevo producto
                $_SESSION['cart'][$product_id] = [
                    'id'       => $row['id'],
                    'name'     => $row['name'],
                    'price'    => $price,
                    'image'    => $row['image'] ?? '',
                    'quantity' => min($quantity, $stock),
                    'stock'    => $stock,
                ];
            }
            $alert = ['type' => 'success',
                      'msg'  => '✅ <strong>' . htmlspecialchars($row['name']) . '</strong> añadido al carrito.'];
        } else {
            $alert = ['type' => 'error', 'msg' => '❌ Producto no encontrado.'];
        }
    }

    // ── ACTUALIZAR CANTIDAD ───────────────────────────────
    if ($action === 'update' && $product_id > 0) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            $alert = ['type' => 'info', 'msg' => 'Producto eliminado del carrito.'];
        } elseif (isset($_SESSION['cart'][$product_id])) {
            $stock = (int)($_SESSION['cart'][$product_id]['stock'] ?? 99);
            $_SESSION['cart'][$product_id]['quantity'] = min($quantity, $stock);
            $alert = ['type' => 'success', 'msg' => '✅ Cantidad actualizada.'];
        }
    }

    // ── ELIMINAR PRODUCTO ─────────────────────────────────
    if ($action === 'remove' && $product_id > 0) {
        $name = $_SESSION['cart'][$product_id]['name'] ?? 'Producto';
        unset($_SESSION['cart'][$product_id]);
        $alert = ['type' => 'info',
                  'msg'  => 'Se eliminó <strong>' . htmlspecialchars($name) . '</strong> del carrito.'];
    }

    // ── VACIAR CARRITO ────────────────────────────────────
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        $alert = ['type' => 'info', 'msg' => '🗑️ Carrito vaciado correctamente.'];
    }

    // Redirigir para evitar reenvío de formulario (PRG pattern)
    if ($action === 'add') {
        $back = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . '/index.php');
        header('Location: ' . $back);
        exit;
    }
    // Para otras acciones (update, remove, clear) recargamos cart.php
    if (in_array($action, ['update', 'remove', 'clear'])) {
        header('Location: cart.php');
        exit;
    }
}

// ============================================================
// CALCULAR TOTALES
// ============================================================
$cart_items    = $_SESSION['cart'] ?? [];   // ← nunca undefined
$subtotal      = 0.0;
$total_items   = 0;

foreach ($cart_items as $item) {
    // Protección: saltar entradas corruptas
    if (!isset($item['price'], $item['quantity'])) continue;
    $subtotal    += (float)$item['price'] * (int)$item['quantity'];
    $total_items += (int)$item['quantity'];
}

// Leer config de delivery y mínimo desde settings
$delivery_fee       = 2.50;
$min_order          = 10.00;
$free_delivery_from = 25.00;

$cfg = mysqli_query($conn,
    "SELECT setting_key, setting_value FROM settings
     WHERE setting_key IN ('delivery_fee','min_order_amount','free_delivery_from')");
if ($cfg) {
    while ($s = mysqli_fetch_assoc($cfg)) {
        if ($s['setting_key'] === 'delivery_fee')       $delivery_fee       = (float)$s['setting_value'];
        if ($s['setting_key'] === 'min_order_amount')   $min_order          = (float)$s['setting_value'];
        if ($s['setting_key'] === 'free_delivery_from') $free_delivery_from = (float)$s['setting_value'];
    }
}

$applies_delivery = $subtotal > 0 && $subtotal < $free_delivery_from;
$delivery_cost    = $applies_delivery ? $delivery_fee : 0.0;
$total            = $subtotal + $delivery_cost;
$tax_rate         = 0.10;
$tax_amount       = $total * $tax_rate;

// ============================================================
// CARGAR PRODUCTOS SUGERIDOS (los más recientes, excluyendo
// los que ya están en el carrito)
// ============================================================
$cart_ids   = empty($cart_items) ? '0' : implode(',', array_map('intval', array_keys($cart_items)));
$suggested  = [];
$sug_result = mysqli_query($conn,
    "SELECT p.id, p.name, p.price, p.discount_price, p.image, c.name AS cat_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     WHERE p.id NOT IN ($cart_ids)
     ORDER BY p.is_featured DESC, RAND()
     LIMIT 4");
if ($sug_result) {
    while ($s = mysqli_fetch_assoc($sug_result)) $suggested[] = $s;
}

// Función helper: emoji por categoría
function cart_emoji(string $cat): string {
    $map = ['pizza'=>'🍕','ensalada'=>'🥗','hamburguesa'=>'🍔','burger'=>'🍔',
            'postre'=>'🍰','bebida'=>'🥤','pasta'=>'🍝','pollo'=>'🍗','carne'=>'🥩'];
    $cat = strtolower($cat);
    foreach ($map as $k => $v) if (str_contains($cat, $k)) return $v;
    return '🍽️';
}

require_once 'includes/header.php';
?>

<!-- ─── HERO ─────────────────────────────────────────── -->
<section style="background:linear-gradient(135deg,#FF6B35,#c0392b);color:#fff;padding:2.5rem 0;">
  <div class="container">
    <h1 style="font-size:1.9rem;color:#fff;margin-bottom:.25rem;">
      🛒 Tu Carrito
    </h1>
    <p style="opacity:.85;">
      <?= $total_items ?> producto<?= $total_items != 1 ? 's' : '' ?> —
      Total: <strong>€<?= number_format($total, 2) ?></strong>
    </p>
  </div>
</section>

<!-- ─── ALERTA ───────────────────────────────────────── -->
<?php if ($alert): ?>
<div class="container" style="margin-top:1.25rem;">
  <div class="alert alert-<?= $alert['type'] ?>" style="
    padding:.9rem 1.1rem;border-radius:10px;font-size:.9rem;
    <?= $alert['type']==='success' ? 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;' : '' ?>
    <?= $alert['type']==='error'   ? 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;' : '' ?>
    <?= $alert['type']==='info'    ? 'background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;' : '' ?>
  ">
    <?= $alert['msg'] ?>
  </div>
</div>
<?php endif; ?>

<!-- ─── CONTENIDO ────────────────────────────────────── -->
<section class="section">
<div class="container">

<?php if (empty($cart_items)): ?>
  <!-- ── Carrito vacío ── -->
  <div style="text-align:center;padding:4rem 1rem;">
    <div style="font-size:5rem;margin-bottom:1rem;">🛒</div>
    <h2 style="margin-bottom:.75rem;">Tu carrito está vacío</h2>
    <p style="color:var(--text-secondary);margin-bottom:2rem;">
      Añade productos desde nuestro menú y aparecerán aquí.
    </p>
    <a href="<?= SITE_URL ?>/index.php#menu" class="btn btn-primary btn-lg">
      <i class="fas fa-utensils"></i> Ver Menú
    </a>
  </div>

<?php else: ?>
  <!-- ── Layout carrito ── -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start;">

    <!-- Columna izquierda: lista de productos -->
    <div>
      <!-- Cabecera -->
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
        <h2 style="font-size:1.2rem;margin:0;">
          Productos en el carrito
          <span style="background:#FF6B35;color:#fff;border-radius:99px;
                       padding:.15rem .6rem;font-size:.8rem;margin-left:.5rem;">
            <?= $total_items ?>
          </span>
        </h2>
        <form method="POST" onsubmit="return confirm('¿Vaciar todo el carrito?')">
          <input type="hidden" name="action" value="clear">
          <button type="submit" style="background:none;border:1px solid #fecaca;
                  color:#dc2626;padding:.4rem .9rem;border-radius:8px;
                  cursor:pointer;font-size:.85rem;">
            🗑️ Vaciar carrito
          </button>
        </form>
      </div>

      <!-- Items -->
      <?php foreach ($cart_items as $pid => $item):
        if (!isset($item['name'], $item['price'], $item['quantity'])) continue;
        $item_total = (float)$item['price'] * (int)$item['quantity'];
        $has_image  = !empty($item['image']) &&
                      file_exists(__DIR__ . '/uploads/products/' . $item['image']);
      ?>
      <div class="card" style="margin-bottom:1rem;padding:0;overflow:hidden;">
        <div style="display:flex;align-items:center;gap:1rem;padding:1rem;">

          <!-- Imagen / Emoji -->
          <div style="flex-shrink:0;width:80px;height:80px;border-radius:10px;overflow:hidden;">
            <?php if ($has_image): ?>
              <img src="<?= SITE_URL ?>/uploads/products/<?= htmlspecialchars($item['image']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <div style="width:100%;height:100%;background:linear-gradient(135deg,#FF6B35,#FFA726);
                          display:flex;align-items:center;justify-content:center;font-size:2rem;">
                🍽️
              </div>
            <?php endif; ?>
          </div>

          <!-- Info producto -->
          <div style="flex:1;min-width:0;">
            <h3 style="font-size:1rem;margin-bottom:.2rem;
                       white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= htmlspecialchars($item['name']) ?>
            </h3>
            <p style="color:var(--text-secondary);font-size:.85rem;margin-bottom:.5rem;">
              €<?= number_format((float)$item['price'], 2) ?> / unidad
            </p>

            <!-- Controles cantidad -->
            <form method="POST" style="display:inline-flex;align-items:center;gap:.5rem;">
              <input type="hidden" name="action"     value="update">
              <input type="hidden" name="product_id" value="<?= (int)$pid ?>">
              <button type="submit" name="quantity"
                      value="<?= (int)$item['quantity'] - 1 ?>"
                      style="width:30px;height:30px;border:1.5px solid #e5e7eb;background:#fff;
                             border-radius:6px;font-size:1.1rem;cursor:pointer;line-height:1;"
                      <?= $item['quantity'] <= 1 ? '' : '' ?>>−</button>
              <span style="min-width:28px;text-align:center;font-weight:700;font-size:.95rem;">
                <?= (int)$item['quantity'] ?>
              </span>
              <button type="submit" name="quantity"
                      value="<?= (int)$item['quantity'] + 1 ?>"
                      style="width:30px;height:30px;border:1.5px solid #e5e7eb;background:#fff;
                             border-radius:6px;font-size:1.1rem;cursor:pointer;line-height:1;"
                      <?= $item['quantity'] >= ($item['stock'] ?? 99) ? 'disabled title="Stock máximo"' : '' ?>>+</button>
            </form>
          </div>

          <!-- Subtotal + eliminar -->
          <div style="text-align:right;flex-shrink:0;">
            <div style="font-size:1.2rem;font-weight:800;color:var(--primary-color);">
              €<?= number_format($item_total, 2) ?>
            </div>
            <form method="POST" style="margin-top:.4rem;">
              <input type="hidden" name="action"     value="remove">
              <input type="hidden" name="product_id" value="<?= (int)$pid ?>">
              <button type="submit"
                      style="background:none;border:none;color:#9ca3af;cursor:pointer;
                             font-size:.8rem;padding:.2rem .4rem;"
                      title="Eliminar">✕ Quitar</button>
            </form>
          </div>

        </div>
      </div>
      <?php endforeach; ?>

      <a href="<?= SITE_URL ?>/index.php#menu"
         style="display:inline-flex;align-items:center;gap:.5rem;
                color:var(--primary-color);text-decoration:none;font-size:.9rem;
                margin-top:.5rem;">
        ← Seguir comprando
      </a>
    </div><!-- /col izq -->

    <!-- Columna derecha: resumen -->
    <div>
      <div class="card" style="padding:1.5rem;position:sticky;top:90px;">
        <h3 style="margin-bottom:1.25rem;padding-bottom:.75rem;
                   border-bottom:1px solid #f3f4f6;">Resumen del pedido</h3>

        <!-- Desglose -->
        <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.25rem;">
          <div style="display:flex;justify-content:space-between;font-size:.9rem;">
            <span style="color:var(--text-secondary);">Subtotal</span>
            <span>€<?= number_format($subtotal, 2) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:.9rem;">
            <span style="color:var(--text-secondary);">Envío</span>
            <?php if ($applies_delivery): ?>
              <span>€<?= number_format($delivery_cost, 2) ?></span>
            <?php else: ?>
              <span style="color:#16a34a;font-weight:600;">GRATIS 🎉</span>
            <?php endif; ?>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:.9rem;">
            <span style="color:var(--text-secondary);">IVA (10%)</span>
            <span>€<?= number_format($tax_amount, 2) ?></span>
          </div>
        </div>

        <!-- Banner envío gratis -->
        <?php if ($applies_delivery && $subtotal > 0):
          $remaining = $free_delivery_from - $subtotal;
          $pct = min(100, round(($subtotal / $free_delivery_from) * 100));
        ?>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;
                    padding:.75rem;margin-bottom:1rem;font-size:.82rem;color:#92400e;">
          Te faltan <strong>€<?= number_format($remaining, 2) ?></strong>
          para envío gratuito (desde €<?= number_format($free_delivery_from, 2) ?>)
          <div style="background:#e5e7eb;border-radius:99px;height:6px;margin-top:.5rem;overflow:hidden;">
            <div style="background:#FFC107;height:100%;width:<?= $pct ?>%;border-radius:99px;"></div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Total -->
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding-top:.75rem;border-top:2px solid #f3f4f6;margin-bottom:1.25rem;">
          <span style="font-size:1.1rem;font-weight:700;">Total</span>
          <span style="font-size:1.5rem;font-weight:800;color:var(--primary-color);">
            €<?= number_format($total, 2) ?>
          </span>
        </div>

        <!-- Botón checkout -->
        <?php if ($subtotal >= $min_order): ?>
          <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary"
             style="display:block;text-align:center;width:100%;padding:.85rem;">
            <i class="fas fa-credit-card"></i> Proceder al pago
          </a>
        <?php else: ?>
          <button disabled class="btn btn-primary"
                  style="width:100%;padding:.85rem;opacity:.6;cursor:not-allowed;">
            Pedido mínimo €<?= number_format($min_order, 2) ?>
          </button>
          <p style="text-align:center;font-size:.8rem;color:var(--text-secondary);margin-top:.5rem;">
            Añade €<?= number_format($min_order - $subtotal, 2) ?> más para continuar
          </p>
        <?php endif; ?>

        <!-- Métodos de pago -->
        <div style="display:flex;justify-content:center;gap:.75rem;margin-top:1rem;
                    color:#9ca3af;font-size:.78rem;">
          <span>💳 Tarjeta</span>
          <span>💵 Efectivo</span>
          <span>📱 Online</span>
        </div>
      </div>
    </div><!-- /col der -->

  </div><!-- /grid -->
<?php endif; ?>

<!-- ─── PRODUCTOS SUGERIDOS ──────────────────────── -->
<?php if (!empty($suggested)): ?>
<div style="margin-top:3rem;">
  <h2 class="section-title" style="text-align:left;">También te puede gustar</h2>
  <div class="grid grid-4" style="gap:1.25rem;margin-top:1rem;">
    <?php foreach ($suggested as $sg):
      $sg_price = !empty($sg['discount_price']) && $sg['discount_price'] < $sg['price']
                  ? (float)$sg['discount_price'] : (float)$sg['price'];
      $sg_has_img = !empty($sg['image']) &&
                    file_exists(__DIR__ . '/uploads/products/' . $sg['image']);
    ?>
    <div class="card product-card">
      <!-- Imagen -->
      <div style="height:130px;overflow:hidden;">
        <?php if ($sg_has_img): ?>
          <img src="<?= SITE_URL ?>/uploads/products/<?= htmlspecialchars($sg['image']) ?>"
               alt="<?= htmlspecialchars($sg['name']) ?>"
               style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <div style="height:100%;background:linear-gradient(135deg,#FF6B35,#FFA726);
                      display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
            <?= cart_emoji($sg['cat_name'] ?? '') ?>
          </div>
        <?php endif; ?>
      </div>
      <!-- Info -->
      <div class="card-body" style="padding:1rem;">
        <h4 style="font-size:.9rem;margin-bottom:.25rem;
                   white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?= htmlspecialchars($sg['name']) ?>
        </h4>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;">
          <span style="font-weight:700;color:var(--primary-color);">
            €<?= number_format($sg_price, 2) ?>
          </span>
          <form method="POST">
            <input type="hidden" name="action"     value="add">
            <input type="hidden" name="product_id" value="<?= (int)$sg['id'] ?>">
            <input type="hidden" name="quantity"   value="1">
            <button type="submit" class="btn btn-primary btn-sm"
                    style="padding:.3rem .7rem;font-size:.8rem;">
              + Añadir
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

</div><!-- /container -->
</section>

<!-- ─── Responsive: apilar columnas en móvil ──────── -->
<style>
@media (max-width: 768px) {
  .cart-layout { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once 'includes/footer.php'; ?>
