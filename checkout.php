<?php
// ============================================================
// QuickOrder — checkout.php
// Checkout corregido: null-safe + estilo tipo mockup + creación de pedido
// ============================================================

require_once 'includes/config.php';
require_once 'includes/functions.php';

require_login();
ensure_cart_exists();

if (!has_cart_items()) {
    set_alert('error', 'Tu carrito está vacío. Añade productos antes de finalizar el pedido.');
    redirect('cart.php');
}

$page_title = 'Checkout';
$user_id    = get_user_id();
$errors     = [];

// ============================================================
// DATOS DEL USUARIO
// ============================================================
$user = db_one("SELECT id, name, email, phone, address, city, postal_code FROM users WHERE id = " . (int)$user_id . " LIMIT 1");
if (!$user) {
    $user = [
        'name' => get_user_name(),
        'email' => get_user_email(),
        'phone' => '',
        'address' => '',
        'city' => '',
        'postal_code' => '',
    ];
}

// ============================================================
// CÁLCULOS DEL PEDIDO
// ============================================================
$cart_items    = get_cart_items();
$subtotal      = get_cart_subtotal();
$delivery_fee  = get_cart_delivery_fee();
$tax_amount    = get_cart_tax_amount(true);
$total         = get_cart_grand_total(true);
$min_order     = get_min_order_amount();
$currency      = (string)get_setting('currency_symbol', '€');

if ($subtotal < $min_order) {
    set_alert('error', 'El pedido mínimo es de ' . format_price($min_order) . '.');
    redirect('cart.php');
}

// ============================================================
// PROCESAR PEDIDO
// ============================================================
if (request_method_is('POST')) {
    $customer_name   = clean_input($_POST['customer_name'] ?? '');
    $customer_email  = clean_input($_POST['customer_email'] ?? '');
    $customer_phone  = clean_input($_POST['customer_phone'] ?? '');
    $delivery_type   = clean_input($_POST['delivery_type'] ?? 'delivery');
    $delivery_addr   = clean_input($_POST['delivery_address'] ?? '');
    $delivery_city   = clean_input($_POST['delivery_city'] ?? '');
    $postal_code     = clean_input($_POST['postal_code'] ?? '');
    $payment_method  = clean_input($_POST['payment_method'] ?? 'cash');
    $notes           = clean_input($_POST['notes'] ?? '');

    if (!validate_required($customer_name)) {
        $errors['customer_name'] = 'El nombre es obligatorio.';
    }
    if (!validate_email($customer_email)) {
        $errors['customer_email'] = 'Introduce un email válido.';
    }
    if (!validate_phone($customer_phone)) {
        $errors['customer_phone'] = 'Introduce un teléfono válido.';
    }
    if (!in_array($delivery_type, ['delivery', 'pickup'], true)) {
        $errors['delivery_type'] = 'Tipo de entrega no válido.';
    }
    if (!in_array($payment_method, ['cash', 'card', 'online'], true)) {
        $errors['payment_method'] = 'Método de pago no válido.';
    }

    if ($delivery_type === 'delivery') {
        if (!validate_required($delivery_addr)) {
            $errors['delivery_address'] = 'La dirección de entrega es obligatoria.';
        }
        if (!validate_required($delivery_city)) {
            $errors['delivery_city'] = 'La ciudad es obligatoria.';
        }
    }

    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        try {
            $order_number = generate_order_number();
            $payment_status = $payment_method === 'cash' ? 'pending' : 'paid';

            $name_sql   = db_escape($customer_name);
            $email_sql  = db_escape($customer_email);
            $phone_sql  = db_escape($customer_phone);
            $dtype_sql  = db_escape($delivery_type);
            $addr_sql   = db_escape($delivery_addr);
            $city_sql   = db_escape($delivery_city);
            $postal_sql = db_escape($postal_code);
            $notes_sql  = db_escape($notes);
            $pay_sql    = db_escape($payment_method);
            $order_sqln = db_escape($order_number);

            $insert_order = "INSERT INTO orders (
                user_id,
                order_number,
                total_amount,
                subtotal,
                tax_amount,
                delivery_fee,
                status,
                payment_method,
                payment_status,
                delivery_type,
                customer_name,
                customer_email,
                customer_phone,
                delivery_address,
                delivery_city,
                notes,
                created_at,
                updated_at
            ) VALUES (
                " . (int)$user_id . ",
                '$order_sqln',
                " . (float)$total . ",
                " . (float)$subtotal . ",
                " . (float)$tax_amount . ",
                " . (float)$delivery_fee . ",
                'pending',
                '$pay_sql',
                '$payment_status',
                '$dtype_sql',
                '$name_sql',
                '$email_sql',
                '$phone_sql',
                '$addr_sql',
                '$city_sql',
                '$notes_sql',
                NOW(),
                NOW()
            )";

            if (!mysqli_query($conn, $insert_order)) {
                throw new Exception('No se pudo crear el pedido: ' . mysqli_error($conn));
            }

            $order_id = (int)mysqli_insert_id($conn);

            foreach ($cart_items as $item) {
                $product_id = (int)($item['id'] ?? 0);
                $name       = db_escape((string)($item['name'] ?? 'Producto'));
                $price      = (float)($item['price'] ?? 0);
                $quantity   = (int)($item['quantity'] ?? 0);
                $line_total = $price * $quantity;

                if ($product_id <= 0 || $quantity <= 0) {
                    continue;
                }

                $insert_item = "INSERT INTO order_items (
                    order_id,
                    product_id,
                    name,
                    price,
                    quantity,
                    subtotal
                ) VALUES (
                    $order_id,
                    $product_id,
                    '$name',
                    $price,
                    $quantity,
                    $line_total
                )";

                if (!mysqli_query($conn, $insert_item)) {
                    throw new Exception('No se pudo guardar una línea de pedido: ' . mysqli_error($conn));
                }
            }

            // Guardar datos principales en el perfil del usuario si estaban vacíos o han cambiado
            $update_user = "UPDATE users SET
                name = '" . db_escape($customer_name) . "',
                email = '" . db_escape($customer_email) . "',
                phone = '" . db_escape($customer_phone) . "',
                address = '" . db_escape($delivery_addr) . "',
                city = '" . db_escape($delivery_city) . "',
                postal_code = '" . db_escape($postal_code) . "',
                updated_at = NOW()
                WHERE id = " . (int)$user_id;
            @mysqli_query($conn, $update_user);

            mysqli_commit($conn);
            clear_cart();
            set_alert('success', 'Pedido confirmado correctamente. Nº ' . $order_number);
            redirect('my-orders.php');
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $errors['general'] = $e->getMessage();
        }
    }
}

require_once 'includes/header.php';
?>

<section class="hero" style="padding:3.5rem 0 2.5rem;">
    <div class="container">
        <div class="hero-content" style="max-width:800px; margin:0 auto; text-align:center;">
            <span class="badge" style="margin-bottom:1rem; display:inline-block;">🧾 Finalizar pedido</span>
            <h1 class="hero-title">Completa tu pedido</h1>
            <p class="hero-subtitle">Revisa tu carrito, indica la dirección de entrega y selecciona el método de pago para confirmar tu pedido.</p>
        </div>
    </div>
</section>

<section class="section" style="background: var(--bg-secondary);">
    <div class="container checkout-grid">

        <div class="checkout-main">
            <?php if (!empty($errors['general'])): ?>
                <div class="checkout-alert checkout-alert-error"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" class="checkout-card checkout-form-card">
                <div class="checkout-card-head">
                    <div>
                        <h2>Datos del pedido</h2>
                        <p>Introduce la información necesaria para procesar la compra.</p>
                    </div>
                    <div class="checkout-steps">
                        <span class="checkout-step active">1. Datos</span>
                        <span class="checkout-step active">2. Pago</span>
                        <span class="checkout-step">3. Confirmación</span>
                    </div>
                </div>

                <div class="checkout-section-block">
                    <h3>👤 Información de contacto</h3>
                    <div class="form-grid-2">
                        <div>
                            <label>Nombre completo</label>
                            <input type="text" name="customer_name" value="<?= old('customer_name', $user['name'] ?? '') ?>" class="form-control<?= isset($errors['customer_name']) ? ' is-invalid' : '' ?>">
                            <?php if (isset($errors['customer_name'])): ?><small class="field-error"><?= e($errors['customer_name']) ?></small><?php endif; ?>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="customer_email" value="<?= old('customer_email', $user['email'] ?? '') ?>" class="form-control<?= isset($errors['customer_email']) ? ' is-invalid' : '' ?>">
                            <?php if (isset($errors['customer_email'])): ?><small class="field-error"><?= e($errors['customer_email']) ?></small><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label>Teléfono</label>
                            <input type="text" name="customer_phone" value="<?= old('customer_phone', $user['phone'] ?? '') ?>" class="form-control<?= isset($errors['customer_phone']) ? ' is-invalid' : '' ?>">
                            <?php if (isset($errors['customer_phone'])): ?><small class="field-error"><?= e($errors['customer_phone']) ?></small><?php endif; ?>
                        </div>
                        <div>
                            <label>Tipo de entrega</label>
                            <?php $old_delivery_type = old('delivery_type', 'delivery'); ?>
                            <select name="delivery_type" id="delivery_type" class="form-control<?= isset($errors['delivery_type']) ? ' is-invalid' : '' ?>">
                                <option value="delivery" <?= $old_delivery_type === 'delivery' ? 'selected' : '' ?>>🚚 Envío a domicilio</option>
                                <option value="pickup" <?= $old_delivery_type === 'pickup' ? 'selected' : '' ?>>🏪 Recogida en local</option>
                            </select>
                            <?php if (isset($errors['delivery_type'])): ?><small class="field-error"><?= e($errors['delivery_type']) ?></small><?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="checkout-section-block" id="deliveryFields">
                    <h3>📍 Dirección de entrega</h3>
                    <div>
                        <label>Dirección</label>
                        <textarea name="delivery_address" rows="3" class="form-control<?= isset($errors['delivery_address']) ? ' is-invalid' : '' ?>"><?= old('delivery_address', $user['address'] ?? '') ?></textarea>
                        <?php if (isset($errors['delivery_address'])): ?><small class="field-error"><?= e($errors['delivery_address']) ?></small><?php endif; ?>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label>Ciudad</label>
                            <input type="text" name="delivery_city" value="<?= old('delivery_city', $user['city'] ?? '') ?>" class="form-control<?= isset($errors['delivery_city']) ? ' is-invalid' : '' ?>">
                            <?php if (isset($errors['delivery_city'])): ?><small class="field-error"><?= e($errors['delivery_city']) ?></small><?php endif; ?>
                        </div>
                        <div>
                            <label>Código postal</label>
                            <input type="text" name="postal_code" value="<?= old('postal_code', $user['postal_code'] ?? '') ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="checkout-section-block">
                    <h3>💳 Método de pago</h3>
                    <?php $old_payment = old('payment_method', 'cash'); ?>
                    <div class="payment-options">
                        <label class="pay-option <?= $old_payment === 'cash' ? 'selected' : '' ?>">
                            <input type="radio" name="payment_method" value="cash" <?= $old_payment === 'cash' ? 'checked' : '' ?>>
                            <span>💵 Efectivo</span>
                        </label>
                        <label class="pay-option <?= $old_payment === 'card' ? 'selected' : '' ?>">
                            <input type="radio" name="payment_method" value="card" <?= $old_payment === 'card' ? 'checked' : '' ?>>
                            <span>💳 Tarjeta</span>
                        </label>
                        <label class="pay-option <?= $old_payment === 'online' ? 'selected' : '' ?>">
                            <input type="radio" name="payment_method" value="online" <?= $old_payment === 'online' ? 'checked' : '' ?>>
                            <span>📱 Pago online</span>
                        </label>
                    </div>
                    <?php if (isset($errors['payment_method'])): ?><small class="field-error"><?= e($errors['payment_method']) ?></small><?php endif; ?>
                </div>

                <div class="checkout-section-block">
                    <h3>📝 Observaciones</h3>
                    <textarea name="notes" rows="4" class="form-control" placeholder="Ejemplo: sin cebolla, llamar al llegar, piso 2B..."><?= old('notes') ?></textarea>
                </div>

                <div class="checkout-actions">
                    <a href="<?= site_url('cart.php') ?>" class="btn btn-outline">← Volver al carrito</a>
                    <button type="submit" class="btn btn-primary btn-lg">Confirmar pedido</button>
                </div>
            </form>
        </div>

        <aside class="checkout-sidebar">
            <div class="checkout-card order-summary-card">
                <div class="checkout-card-head simple">
                    <div>
                        <h2>Resumen del pedido</h2>
                        <p><?= get_cart_count() ?> artículo<?= get_cart_count() !== 1 ? 's' : '' ?> en tu carrito</p>
                    </div>
                </div>

                <div class="summary-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="summary-item-row">
                            <div class="summary-item-info">
                                <strong><?= e($item['name'] ?? 'Producto') ?></strong>
                                <span>Cantidad: <?= (int)($item['quantity'] ?? 0) ?></span>
                            </div>
                            <div class="summary-item-price">
                                <?= format_price(((float)($item['price'] ?? 0)) * ((int)($item['quantity'] ?? 0))) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-totals">
                    <div><span>Subtotal</span><strong><?= format_price($subtotal) ?></strong></div>
                    <div><span>Envío</span><strong><?= $delivery_fee > 0 ? format_price($delivery_fee) : 'Gratis' ?></strong></div>
                    <div><span>IVA</span><strong><?= format_price($tax_amount) ?></strong></div>
                    <div class="grand-total"><span>Total</span><strong><?= format_price($total) ?></strong></div>
                </div>

                <div class="summary-note">
                    <div>✅ Pago seguro</div>
                    <div>🚚 Entrega estimada: <?= (int)get_setting('delivery_time', 30) ?> min</div>
                    <div>📞 Soporte: <?= e((string)get_setting('site_phone', '+34 900 123 456')) ?></div>
                </div>
            </div>
        </aside>
    </div>
</section>

<style>
.checkout-grid{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(320px,420px);gap:2rem;align-items:start}
.checkout-card{background:#fff;border-radius:22px;box-shadow:0 18px 50px rgba(15,23,42,.08);overflow:hidden}
.checkout-form-card{padding:0}
.checkout-card-head{padding:1.5rem 1.5rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;flex-wrap:wrap}
.checkout-card-head.simple{padding-bottom:1.25rem}
.checkout-card-head h2{margin:0 0 .25rem;font-size:1.4rem}
.checkout-card-head p{margin:0;color:#64748b}
.checkout-steps{display:flex;gap:.5rem;flex-wrap:wrap}
.checkout-step{padding:.45rem .8rem;border-radius:999px;background:#f8fafc;color:#64748b;font-size:.82rem;font-weight:700}
.checkout-step.active{background:linear-gradient(135deg,#FF6B35,#FFA726);color:#fff}
.checkout-section-block{padding:1.4rem 1.5rem;border-bottom:1px solid #f8fafc}
.checkout-section-block h3{margin:0 0 1rem;font-size:1.05rem}
.form-grid-2{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem}
.form-control{width:100%;padding:.9rem 1rem;border:1.5px solid #e2e8f0;border-radius:14px;background:#fff;outline:none;font:inherit;transition:.2s}
.form-control:focus{border-color:#FF6B35;box-shadow:0 0 0 4px rgba(255,107,53,.12)}
textarea.form-control{resize:vertical;min-height:110px}
.is-invalid{border-color:#ef4444!important;box-shadow:0 0 0 4px rgba(239,68,68,.08)!important}
.field-error{display:block;color:#dc2626;font-size:.82rem;margin-top:.35rem}
.payment-options{display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem}
.pay-option{position:relative;border:1.5px solid #e2e8f0;border-radius:16px;padding:1rem;background:#fff;cursor:pointer;font-weight:700;color:#334155;text-align:center}
.pay-option input{position:absolute;opacity:0;pointer-events:none}
.pay-option.selected,.pay-option:has(input:checked){border-color:#FF6B35;background:rgba(255,107,53,.06);color:#c2410c}
.checkout-actions{display:flex;justify-content:space-between;gap:1rem;padding:1.5rem;flex-wrap:wrap}
.order-summary-card{position:sticky;top:95px}
.summary-items{padding:0 1.5rem 1rem}
.summary-item-row{display:flex;justify-content:space-between;gap:1rem;padding:.9rem 0;border-bottom:1px solid #f1f5f9}
.summary-item-row:last-child{border-bottom:none}
.summary-item-info{display:flex;flex-direction:column;gap:.2rem}
.summary-item-info span{font-size:.84rem;color:#64748b}
.summary-item-price{font-weight:800;color:#0f172a;white-space:nowrap}
.summary-totals{padding:1rem 1.5rem;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;display:flex;flex-direction:column;gap:.75rem}
.summary-totals>div{display:flex;justify-content:space-between;align-items:center;color:#475569}
.summary-totals .grand-total{font-size:1.15rem;color:#0f172a;font-weight:800;padding-top:.5rem;border-top:1px dashed #cbd5e1}
.summary-note{padding:1rem 1.5rem 1.4rem;display:flex;flex-direction:column;gap:.6rem;color:#475569;font-size:.92rem}
.checkout-alert{padding:1rem 1.1rem;border-radius:14px;margin-bottom:1rem}
.checkout-alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
label{display:block;margin-bottom:.45rem;font-weight:700;color:#334155}
@media (max-width: 992px){.checkout-grid{grid-template-columns:1fr}.order-summary-card{position:static}}
@media (max-width: 768px){.form-grid-2,.payment-options{grid-template-columns:1fr}.checkout-actions{flex-direction:column}.checkout-actions .btn{width:100%;text-align:center}}
</style>

<script>
(function(){
    const deliveryType = document.getElementById('delivery_type');
    const deliveryFields = document.getElementById('deliveryFields');
    const paymentOptions = document.querySelectorAll('.pay-option');

    function toggleDeliveryFields(){
        if(!deliveryType || !deliveryFields) return;
        deliveryFields.style.display = deliveryType.value === 'pickup' ? 'none' : 'block';
    }

    function refreshPaymentSelected(){
        paymentOptions.forEach(opt => {
            const input = opt.querySelector('input');
            if(input && input.checked){
                opt.classList.add('selected');
            } else {
                opt.classList.remove('selected');
            }
        });
    }

    if(deliveryType){
        deliveryType.addEventListener('change', toggleDeliveryFields);
        toggleDeliveryFields();
    }

    paymentOptions.forEach(opt => {
        opt.addEventListener('click', refreshPaymentSelected);
    });
    refreshPaymentSelected();
})();
</script>

<?php require_once 'includes/footer.php'; ?>
