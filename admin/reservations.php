<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/_layout.php';

require_login();
admin_sync_user_role();
require_admin();

$page_title = 'Reservas';
$statusFilter = trim((string)($_GET['status'] ?? ''));

$where = '';

$reservations = db_all("SELECT r.*, u.name AS user_name, u.email AS user_email
                        FROM reservations r
                        LEFT JOIN users u ON u.id = r.user_id
                        $where
                        ORDER BY r.reservation_date DESC, r.reservation_time DESC");

admin_render_start($page_title, 'reservations');
?>

<div class="admin-page-header">
    <div>
        <h1>Visualización de reservas</h1>
        <p>En esta sección el administrador solo consulta las reservas registradas por los clientes.</p>
    </div>
    <div class="admin-pill"><i class="fas fa-calendar-days"></i> Reservas: <?php echo count($reservations); ?></div>
</div>

<section class="admin-card">
    <div class="admin-search-row">
        <div>
            <h2 style="margin:0;">Reservas registradas</h2>
            <p class="admin-note" style="margin:.35rem 0 0;">Consulta rápida de fechas, turnos y observaciones.</p>
        </div>
    </div>

    <?php if (empty($reservations)): ?>
        <div class="admin-empty">No se han encontrado reservas con ese criterio.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Fecha y hora</th>
                        <th>Comensales</th>
                        <th>Preferencia</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td>
                            <strong><?php echo e($reservation['reservation_number']); ?></strong><br>
                            <span class="admin-note">Creada: <?php echo e(format_datetime($reservation['created_at'])); ?></span>
                        </td>
                        <td>
                            <strong><?php echo e($reservation['name']); ?></strong><br>
                            <span class="admin-note"><?php echo e($reservation['email']); ?></span><br>
                            <span class="admin-note"><?php echo e($reservation['phone']); ?></span>
                        </td>
                        <td>
                            <strong><?php echo e(format_date($reservation['reservation_date'])); ?></strong><br>
                            <span class="admin-note"><?php echo e(substr((string)$reservation['reservation_time'], 0, 5)); ?></span>
                        </td>
                        <td><?php echo (int)$reservation['guests']; ?></td>
                        <td><?php echo e($reservation['table_preference'] ?: 'Sin preferencia'); ?></td>
                        <td>
                            <span class="admin-note"><?php echo e($reservation['special_requests'] ?: $reservation['notes'] ?: 'Sin observaciones'); ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php admin_render_end(); ?>
