<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/config.php';

// Cargar functions.php solo si existe
$functionsPath = dirname(__DIR__) . '/includes/functions.php';
if (file_exists($functionsPath)) {
    require_once $functionsPath;
}

// Comprobación básica de conexión
if (!isset($conn) || !($conn instanceof mysqli)) {
    die('Error: no se ha podido establecer la conexión con la base de datos.');
}

// Función para escapar salida HTML
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Comprobación flexible de administrador
$isAdmin = false;

if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $isAdmin = true;
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
} elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $isAdmin = true;
}

if (!$isAdmin) {
    header('Location: ../login.php');
    exit;
}

// Token CSRF simple
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$successMessage = '';
$errorMessage = '';

// Comprobar que exista la tabla contact_messages
$tableExists = false;
$tableCheck = $conn->query("SHOW TABLES LIKE 'contact_messages'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $tableExists = true;
}

if (!$tableExists) {
    die('Error: la tabla "contact_messages" no existe en la base de datos.');
}

// Procesar borrado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errorMessage = 'Token de seguridad no válido.';
    } else {
        $deleteId = (int)$_POST['delete_id'];

        if ($deleteId > 0) {
            $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $deleteId);

                if ($stmt->execute()) {
                    header('Location: messages.php?deleted=1');
                    exit;
                } else {
                    $errorMessage = 'No se ha podido eliminar el mensaje.';
                }

                $stmt->close();
            } else {
                $errorMessage = 'Error al preparar la consulta de borrado.';
            }
        } else {
            $errorMessage = 'ID de mensaje no válido.';
        }
    }
}

if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $successMessage = 'Mensaje eliminado correctamente.';
}

// Detectar columnas existentes en contact_messages
$existingColumns = [];
$columnsResult = $conn->query("SHOW COLUMNS FROM contact_messages");

if ($columnsResult) {
    while ($col = $columnsResult->fetch_assoc()) {
        $existingColumns[] = $col['Field'];
    }
} else {
    die('Error al obtener la estructura de la tabla contact_messages.');
}

// Selección dinámica de columnas
$selectFields = ['id'];

$optionalFields = ['name', 'email', 'subject', 'message', 'created_at'];

foreach ($optionalFields as $field) {
    if (in_array($field, $existingColumns, true)) {
        $selectFields[] = $field;
    }
}

// Si no existe "message" pero sí otro nombre alternativo, puedes añadirlo aquí
if (!in_array('message', $existingColumns, true)) {
    if (in_array('mensaje', $existingColumns, true)) {
        $selectFields[] = 'mensaje';
    }
}

// Ordenación
$orderBy = in_array('created_at', $existingColumns, true) ? 'created_at DESC' : 'id DESC';

$sql = "SELECT " . implode(', ', $selectFields) . " FROM contact_messages ORDER BY $orderBy";
$result = $conn->query($sql);

if (!$result) {
    die('Error al obtener los mensajes: ' . e($conn->error));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes de contacto - Panel de administración</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .topbar {
            background: #111827;
            color: #fff;
            padding: 16px 24px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 22px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .actions {
            margin-bottom: 20px;
        }

        .btn-back {
            display: inline-block;
            text-decoration: none;
            background: #2563eb;
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 14px;
        }

        .btn-back:hover {
            background: #1d4ed8;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 14px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
            color: #111827;
        }

        tr:hover td {
            background: #fafafa;
        }

        .message-box {
            white-space: pre-wrap;
            word-break: break-word;
            max-width: 420px;
            line-height: 1.5;
        }

        .empty {
            padding: 30px 20px;
            text-align: center;
            color: #6b7280;
        }

        .btn-delete {
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <h1>Panel de administración - Mensajes de contacto</h1>
    </div>

    <div class="container">
        <div class="actions">
            <a href="index.php" class="btn-back">← Volver al panel</a>
        </div>

        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?php echo e($successMessage); ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Listado de mensajes recibidos</h2>
            </div>

            <div class="table-wrapper">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <?php if (in_array('name', $existingColumns, true)): ?>
                                    <th>Nombre</th>
                                <?php endif; ?>

                                <?php if (in_array('email', $existingColumns, true)): ?>
                                    <th>Email</th>
                                <?php endif; ?>

                                <?php if (in_array('subject', $existingColumns, true)): ?>
                                    <th>Asunto</th>
                                <?php endif; ?>

                                <?php if (in_array('message', $existingColumns, true) || in_array('mensaje', $existingColumns, true)): ?>
                                    <th>Mensaje</th>
                                <?php endif; ?>

                                <?php if (in_array('created_at', $existingColumns, true)): ?>
                                    <th>Fecha</th>
                                <?php endif; ?>

                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (int)$row['id']; ?></td>

                                    <?php if (in_array('name', $existingColumns, true)): ?>
                                        <td><?php echo e($row['name'] ?? ''); ?></td>
                                    <?php endif; ?>

                                    <?php if (in_array('email', $existingColumns, true)): ?>
                                        <td><?php echo e($row['email'] ?? ''); ?></td>
                                    <?php endif; ?>

                                    <?php if (in_array('subject', $existingColumns, true)): ?>
                                        <td><?php echo e($row['subject'] ?? ''); ?></td>
                                    <?php endif; ?>

                                    <?php if (in_array('message', $existingColumns, true) || in_array('mensaje', $existingColumns, true)): ?>
                                        <td>
                                            <div class="message-box">
                                                <?php
                                                if (isset($row['message'])) {
                                                    echo nl2br(e($row['message']));
                                                } elseif (isset($row['mensaje'])) {
                                                    echo nl2br(e($row['mensaje']));
                                                } else {
                                                    echo '<span class="muted">Sin contenido</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>

                                    <?php if (in_array('created_at', $existingColumns, true)): ?>
                                        <td><?php echo e($row['created_at'] ?? ''); ?></td>
                                    <?php endif; ?>

                                    <td>
                                        <form method="POST" onsubmit="return confirm('¿Seguro que quieres borrar este mensaje?');">
                                            <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                            <button type="submit" class="btn-delete">Borrar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty">
                        No hay mensajes de contacto registrados.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>
