<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';

require_login();
admin_sync_user_role();
require_admin();

$page_title = 'Categorías';
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$editCategory = null;

if (request_method_is('POST') && (string)($_POST['action'] ?? '') === 'save_category') {
    if (!verify_csrf_token($_POST['_token'] ?? null)) {
        $errors[] = 'La sesión del formulario ha caducado. Vuelve a intentarlo.';
    }

    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $currentImage = trim((string)($_POST['current_image'] ?? ''));

    if ($name === '') {
        $errors[] = 'El nombre de la categoría es obligatorio.';
    }

    $duplicateSql = "SELECT id FROM categories WHERE name='" . db_escape($name) . "'" . ($id > 0 ? " AND id <> $id" : '') . " LIMIT 1";
    if ($name !== '' && db_one($duplicateSql)) {
        $errors[] = 'Ya existe otra categoría con ese nombre.';
    }

    $imageName = $currentImage;
    if (!empty($_FILES['image']['name'] ?? '')) {
        $uploaded = upload_image($_FILES['image'], 'categories');
        if ($uploaded === false) {
            $errors[] = 'La imagen no se pudo subir. Usa JPG, PNG, WEBP o GIF y un máximo de 5MB.';
        } else {
            $imageName = $uploaded;
        }
    }

    if (empty($errors)) {
        $safeName = db_escape($name);
        $safeDescription = db_escape($description);
        $safeImage = $imageName !== '' ? "'" . db_escape($imageName) . "'" : 'NULL';

        if ($id > 0) {
            $sql = "UPDATE categories
                    SET name='$safeName',
                        description='${safeDescription}',
                        image=$safeImage,
                        sort_order=$sortOrder,
                        updated_at=NOW()
                    WHERE id=$id
                    LIMIT 1";
            $ok = db_query($sql);
            if ($ok) {
                set_alert('success', 'Categoría actualizada correctamente.');
                redirect('admin/categories.php');
            }
            $errors[] = 'No se pudo actualizar la categoría.';
        } else {
            $sql = "INSERT INTO categories (name, description, image, sort_order, created_at, updated_at)
                    VALUES ('$safeName', '$safeDescription', $safeImage, $sortOrder, NOW(), NOW())";
            $ok = db_query($sql);
            if ($ok) {
                set_alert('success', 'Categoría creada correctamente.');
                redirect('admin/categories.php');
            }
            $errors[] = 'No se pudo crear la categoría.';
        }
    }

    $editId = $id;
}

if ($editId > 0) {
    $editCategory = db_one("SELECT * FROM categories WHERE id = $editId LIMIT 1");
}

$categories = db_all("SELECT c.*, COUNT(p.id) AS product_count
                      FROM categories c
                      LEFT JOIN products p ON p.category_id = c.id
                      GROUP BY c.id
                      ORDER BY c.sort_order ASC, c.name ASC");

admin_render_start($page_title, 'categories');
?>

<div class="admin-page-header">
    <div>
        <h1>Gestión de categorías</h1>
        <p>Aquí el administrador solo puede crear nuevas categorías y modificar las ya existentes.</p>
    </div>
    <div class="admin-pill"><i class="fas fa-tags"></i> Total: <?php echo count($categories); ?></div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:1rem;">
        <i class="fas fa-circle-exclamation"></i>
        <?php echo e(implode(' ', $errors)); ?>
    </div>
<?php endif; ?>

<div class="admin-grid cols-2">
    <section class="admin-card">
        <h2><?php echo $editCategory ? 'Modificar categoría' : 'Nueva categoría'; ?></h2>
        <p class="admin-note">Puedes subir una imagen real para reemplazar el bloque gráfico del front.</p>

        <form method="post" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="save_category">
            <input type="hidden" name="id" value="<?php echo (int)($editCategory['id'] ?? 0); ?>">
            <input type="hidden" name="current_image" value="<?php echo e((string)($editCategory['image'] ?? '')); ?>">

            <div class="admin-form-grid">
                <div class="admin-form-group">
                    <label for="name">Nombre</label>
                    <input type="text" id="name" name="name" value="<?php echo e(old('name', (string)($editCategory['name'] ?? ''))); ?>" required>
                </div>
                <div class="admin-form-group">
                    <label for="sort_order">Orden</label>
                    <input type="number" id="sort_order" name="sort_order" value="<?php echo e((string)old('sort_order', (string)($editCategory['sort_order'] ?? '0'))); ?>" min="0">
                </div>
                <div class="admin-form-group full">
                    <label for="description">Descripción</label>
                    <textarea id="description" name="description"><?php echo e(old('description', (string)($editCategory['description'] ?? ''))); ?></textarea>
                </div>
                <div class="admin-form-group full">
                    <label for="image">Imagen</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    <?php if (!empty($editCategory['image'])): ?>
                        <span class="admin-note">Actual: <?php echo e($editCategory['image']); ?></span>
                    <?php else: ?>
                        <span class="admin-note">Opcional. Si no subes una, la web mostrará el recurso de reserva.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="admin-form-actions">
                <button class="admin-btn primary" type="submit"><i class="fas fa-save"></i> <?php echo $editCategory ? 'Guardar cambios' : 'Crear categoría'; ?></button>
                <?php if ($editCategory): ?>
                    <a class="admin-btn light" href="<?php echo SITE_URL; ?>/admin/categories.php"><i class="fas fa-rotate-left"></i> Cancelar edición</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Categorías registradas</h2>
        <p class="admin-note">Listado rápido del catálogo actual.</p>

        <?php if (empty($categories)): ?>
            <div class="admin-empty">No hay categorías creadas todavía.</div>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Productos</th>
                            <th>Orden</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <?php if (!empty($category['image'])): ?>
                                    <img class="admin-thumb" src="<?php echo SITE_URL; ?>/uploads/categories/<?php echo e($category['image']); ?>" alt="<?php echo e($category['name']); ?>">
                                <?php else: ?>
                                    <div class="admin-thumb" style="display:grid;place-items:center;font-weight:800;color:#ff6b35;">CAT</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo e($category['name']); ?></strong><br>
                                <span class="admin-note"><?php echo e(mb_strimwidth((string)($category['description'] ?? ''), 0, 70, '…')); ?></span>
                            </td>
                            <td><?php echo (int)$category['product_count']; ?></td>
                            <td><?php echo (int)$category['sort_order']; ?></td>
                            <td>
                                <a class="admin-btn secondary" href="<?php echo SITE_URL; ?>/admin/categories.php?edit=<?php echo (int)$category['id']; ?>">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
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
