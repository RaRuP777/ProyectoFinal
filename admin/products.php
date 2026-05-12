<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';

require_login();
admin_sync_user_role();
require_admin();

$page_title = 'Productos';
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editProduct = null;

if (request_method_is('POST') && (string)($_POST['action'] ?? '') === 'save_product') {
    if (!verify_csrf_token($_POST['_token'] ?? null)) {
        $errors[] = 'La sesión del formulario ha caducado. Vuelve a intentarlo.';
    }

    $id = (int)($_POST['id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $price = (float)($_POST['price'] ?? 0);
    $discountPriceRaw = trim((string)($_POST['discount_price'] ?? ''));
    $discountPrice = $discountPriceRaw !== '' ? (float)$discountPriceRaw : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $allergens = trim((string)($_POST['allergens'] ?? ''));
    $caloriesRaw = trim((string)($_POST['calories'] ?? ''));
    $calories = $caloriesRaw !== '' ? (int)$caloriesRaw : null;
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $currentImage = trim((string)($_POST['current_image'] ?? ''));

    if ($name === '') {
        $errors[] = 'El nombre del producto es obligatorio.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Debes seleccionar una categoría.';
    }
    if ($price <= 0) {
        $errors[] = 'El precio debe ser mayor que 0.';
    }
    if ($discountPrice !== null && $discountPrice <= 0) {
        $errors[] = 'El precio rebajado debe ser mayor que 0.';
    }

    $imageName = $currentImage;
    if (!empty($_FILES['image']['name'] ?? '')) {
        $uploaded = upload_image($_FILES['image'], 'products');
        if ($uploaded === false) {
            $errors[] = 'La imagen no se pudo subir. Usa JPG, PNG, WEBP o GIF y un máximo de 5MB.';
        } else {
            $imageName = $uploaded;
        }
    }

    if (empty($errors)) {
        $safeName = db_escape($name);
        $safeDescription = db_escape($description);
        $safeAllergens = db_escape($allergens);
        $safeImage = $imageName !== '' ? "'" . db_escape($imageName) . "'" : 'NULL';
        $discountSql = $discountPrice !== null ? number_format($discountPrice, 2, '.', '') : 'NULL';
        $caloriesSql = $calories !== null ? (string)$calories : 'NULL';
        $priceSql = number_format($price, 2, '.', '');

        if ($id > 0) {
            $sql = "UPDATE products
                    SET category_id=$categoryId,
                        name='$safeName',
                        description='$safeDescription',
                        price=$priceSql,
                        discount_price=$discountSql,
                        image=$safeImage,
                        stock=$stock,
                        is_featured=$isFeatured,
                        allergens='$safeAllergens',
                        calories=$caloriesSql,
                        sort_order=$sortOrder,
                        updated_at=NOW()
                    WHERE id=$id
                    LIMIT 1";
            $ok = db_query($sql);
            if ($ok) {
                set_alert('success', 'Producto actualizado correctamente.');
                redirect('admin/products.php');
            }
            $errors[] = 'No se pudo actualizar el producto.';
        } else {
            $sql = "INSERT INTO products (category_id, name, description, price, discount_price, image, stock, is_featured, allergens, calories, sort_order, created_at, updated_at)
                    VALUES ($categoryId, '$safeName', '$safeDescription', $priceSql, $discountSql, $safeImage, $stock, $isFeatured, '$safeAllergens', $caloriesSql, $sortOrder, NOW(), NOW())";
            $ok = db_query($sql);
            if ($ok) {
                set_alert('success', 'Producto creado correctamente.');
                redirect('admin/products.php');
            }
            $errors[] = 'No se pudo crear el producto.';
        }
    }

    $editId = $id;
}

if ($editId > 0) {
    $editProduct = db_one("SELECT * FROM products WHERE id = $editId LIMIT 1");
}

$categories = db_all("SELECT id, name FROM categories ORDER BY sort_order ASC, name ASC");
$products = db_all("SELECT p.*, c.name AS category_name
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    ORDER BY p.sort_order ASC, p.created_at DESC");

admin_render_start($page_title, 'products');
?>

<div class="admin-page-header">
    <div>
        <h1>Gestión de productos</h1>
        <p>Alta y modificación de productos del catálogo. No se incluyen otras operaciones administrativas fuera del alcance definido.</p>
    </div>
    <div class="admin-pill"><i class="fas fa-utensils"></i> Total: <?php echo count($products); ?></div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
        <i class="fas fa-circle-exclamation"></i>
        <?php echo e(implode(' ', $errors)); ?>
    </div>
<?php endif; ?>

<div class="admin-grid cols-2">
    <section class="admin-card">
        <h2><?php echo $editProduct ? 'Modificar producto' : 'Nuevo producto'; ?></h2>
        <p class="admin-note">Sube una fotografía real si quieres que la web la muestre en lugar del recurso de respaldo.</p>

        <form method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="save_product">
            <input type="hidden" name="id" value="<?php echo (int)($editProduct['id'] ?? 0); ?>">
            <input type="hidden" name="current_image" value="<?php echo e((string)($editProduct['image'] ?? '')); ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="category_id">Categoría</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['id']; ?>" <?php echo (string)old('category_id', (string)($editProduct['category_id'] ?? '')) === (string)$category['id'] ? 'selected' : ''; ?>><?php echo e($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-form-group">
                    <label for="name">Nombre</label>
                    <input type="text" id="name" name="name" value="<?php echo e(old('name', (string)($editProduct['name'] ?? ''))); ?>" required>
                </div>
                <div class="admin-form-group full">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description"><?php echo e(old('description', (string)($editProduct['description'] ?? ''))); ?></textarea>
                </div>
                <div class="admin-form-group">
                    <label for="price">Precio</label>
                    <input type="number" step="0.01" min="0" id="price" name="price" value="<?php echo e((string)old('price', (string)($editProduct['price'] ?? ''))); ?>" required>
                </div>
                <div class="admin-form-group">
                    <label for="discount_price">Precio rebajado</label>
                    <input type="number" step="0.01" min="0" id="discount_price" name="discount_price" value="<?php echo e((string)old('discount_price', (string)($editProduct['discount_price'] ?? ''))); ?>">
                </div>
                <div class="admin-form-group">
                    <label for="stock">Stock</label>
                    <input type="number" min="0" id="stock" name="stock" value="<?php echo e((string)old('stock', (string)($editProduct['stock'] ?? '100'))); ?>">
                </div>
                <div class="admin-form-group">
                    <label for="sort_order">Orden</label>
                    <input type="number" min="0" id="sort_order" name="sort_order" value="<?php echo e((string)old('sort_order', (string)($editProduct['sort_order'] ?? '0'))); ?>">
                </div>
                <div class="admin-form-group">
                    <label for="allergens">Alérgenos</label>
                    <input type="text" id="allergens" name="allergens" value="<?php echo e(old('allergens', (string)($editProduct['allergens'] ?? ''))); ?>">
                </div>
                <div class="admin-form-group">
                    <label for="calories">Calorías</label>
                    <input type="number" min="0" id="calories" name="calories" value="<?php echo e((string)old('calories', (string)($editProduct['calories'] ?? ''))); ?>">
                </div>
                <div class="admin-form-group full">
                    <label for="image">Imagen</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($editProduct['image'])): ?>
                        <span class="admin-note">Actual: <?php echo e($editProduct['image']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="admin-form-group full" style="flex-direction:row;align-items:center;gap:.75rem;">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo old('is_featured', (string)($editProduct['is_featured'] ?? '0')) ? 'checked' : ''; ?> style="width:auto;">
                    <label for="is_featured" style="margin:0;">Marcar como destacado</label>
                </div>
            </div>

            <div class="admin-form-actions">
                <button class="admin-btn primary" type="submit"><i class="fas fa-save"></i> <?php echo $editProduct ? 'Guardar cambios' : 'Crear producto'; ?></button>
                <?php if ($editProduct): ?>
                    <a class="admin-btn light" href="<?php echo SITE_URL; ?>/admin/products.php"><i class="fas fa-rotate-left"></i> Cancelar edición</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Productos registrados</h2>
        <p class="admin-note">Inventario actual del catálogo.</p>

        <?php if (empty($products)): ?>
            <div class="admin-empty">No hay productos creados todavía.</div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img class="admin-thumb" src="<?php echo SITE_URL; ?>/uploads/products/<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                                <?php else: ?>
                                    <div class="admin-thumb" style="display:grid;place-items:center;font-weight:800;color:#ff6b35;">PROD</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo e($product['name']); ?></strong><br>
                                <span class="admin-note"><?php echo e(mb_strimwidth((string)($product['description'] ?? ''), 0, 70, '…')); ?></span>
                                <?php if ((int)$product['is_featured'] === 1): ?>
                                    <div style="margin-top:.35rem;"><span class="admin-pill"><i class="fas fa-star"></i> Destacado</span></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($product['category_name'] ?: 'Sin categoría'); ?></td>
                            <td>
                                <strong><?php echo e(format_price($product['price'])); ?></strong>
                                <?php if (!empty($product['discount_price'])): ?>
                                    <br><span class="admin-note">Oferta: <?php echo e(format_price($product['discount_price'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int)$product['stock']; ?></td>
                            <td>
                                <a class="admin-btn secondary" href="<?php echo SITE_URL; ?>/admin/products.php?edit=<?php echo (int)$product['id']; ?>"><i class="fas fa-pen"></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php admin_render_end(); ?>
