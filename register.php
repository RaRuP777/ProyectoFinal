<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$errors = [];
$name = '';
$email = '';
$phone = '';
$terms = false;

if (request_method_is('POST')) {
    $csrf_ok = true;
    if (function_exists('verify_csrf_token')) {
        $csrf_ok = verify_csrf_token($_POST['_token'] ?? '');
    }

    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    $terms = isset($_POST['terms']);

    if (!$csrf_ok) {
        $errors['general'] = 'La sesión del formulario ha caducado. Recarga la página e inténtalo de nuevo.';
    }
    if (!validate_required($name)) {
        $errors['name'] = 'El nombre completo es obligatorio.';
    } elseif (!validate_min_length($name, 3)) {
        $errors['name'] = 'El nombre debe tener al menos 3 caracteres.';
    }

    if (!validate_email($email)) {
        $errors['email'] = 'Introduce un correo electrónico válido.';
    }

    if ($phone !== '' && !validate_phone($phone)) {
        $errors['phone'] = 'Introduce un teléfono válido.';
    }

    if (!validate_required($password)) {
        $errors['password'] = 'La contraseña es obligatoria.';
    } elseif (!validate_min_length($password, 6)) {
        $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    if ($confirm_password !== $password) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden.';
    }

    if (!$terms) {
        $errors['terms'] = 'Debes aceptar los términos y la política de privacidad.';
    }

    if (empty($errors)) {
        $existing_user = db_one("SELECT id FROM users WHERE email = '" . db_escape($email) . "' LIMIT 1");
        if ($existing_user) {
            $errors['email'] = 'Ya existe una cuenta registrada con ese correo electrónico.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password, phone, role, is_verified, created_at, updated_at)
                    VALUES ('" . db_escape($name) . "', '" . db_escape($email) . "', '" . db_escape($password_hash) . "', '" . db_escape($phone) . "', 'customer', 1, NOW(), NOW())";

            if (db_query($sql)) {
                set_alert('success', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
                redirect('login.php');
            } else {
                $errors['general'] = 'No se ha podido crear la cuenta en este momento. Inténtalo de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - QuickOrder</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.auth-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #B8421C 0%, #FF6B35 48%, #7A2D14 100%);
            font-family: var(--font-primary);
        }
        .back-home {
            position: fixed;
            top: 1.5rem;
            left: 1.5rem;
            z-index: 30;
            background: rgba(255,255,255,.92);
            color: #B8421C;
            border: 1px solid rgba(255,255,255,.92);
            box-shadow: 0 12px 28px rgba(0,0,0,.12);
        }
        .auth-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            width: 100%;
            max-width: 520px;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 22px 60px rgba(15, 23, 42, .22);
            border: 1px solid rgba(255,255,255,.7);
        }
        .auth-header {
            background: linear-gradient(135deg, #FF6B35, #C84E21);
            color: #fff;
            padding: 2rem;
            text-align: center;
        }
        .auth-logo-badge {
            width: 84px;
            height: 84px;
            margin: 0 auto 1rem;
            border-radius: 22px;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
            overflow: hidden;
        }
        .auth-logo-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #fff;
        }
        .auth-logo-fallback {
            font-size: 2.5rem;
        }
        .auth-header h1 {
            margin-bottom: .5rem;
            color: #fff;
            font-size: 1.9rem;
        }
        .auth-header p {
            margin: 0;
            color: rgba(255,255,255,.9);
        }
        .auth-body {
            padding: 2rem;
        }
        .auth-tabs {
            display: flex;
            gap: .5rem;
            margin-bottom: 1.5rem;
            background: #F5F7FA;
            padding: .45rem;
            border-radius: 14px;
        }
        .auth-tab {
            flex: 1;
            padding: .85rem 1rem;
            border-radius: 12px;
            text-align: center;
            font-weight: 700;
            color: #64748B;
            background: transparent;
        }
        .auth-tab.active {
            background: #FF6B35;
            color: #fff;
            box-shadow: 0 10px 22px rgba(255,107,53,.22);
        }
        .auth-tab:not(.active):hover {
            background: #fff;
            color: #0F172A;
        }
        .helper-box {
            background: #EFF6FF;
            border-left: 4px solid #2563EB;
            padding: 1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
        }
        .helper-box h4 {
            font-size: .95rem;
            margin-bottom: .35rem;
            color: #1D4ED8;
        }
        .helper-box p {
            margin: .2rem 0;
            color: #334155;
            font-size: .92rem;
        }
        .field {
            margin-bottom: 1.15rem;
        }
        .field label {
            display: block;
            font-weight: 700;
            margin-bottom: .45rem;
            color: #334155;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 1rem;
            pointer-events: none;
        }
        .input-wrap input {
            width: 100%;
            padding: .95rem 1rem .95rem 3rem;
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            background: #fff;
            font: inherit;
            transition: .2s;
        }
        .input-wrap input:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 4px rgba(255,107,53,.12);
        }
        .input-wrap.has-toggle input {
            padding-right: 3rem;
        }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            cursor: pointer;
        }
        .password-toggle:hover {
            color: #FF6B35;
        }
        .field-error {
            display: block;
            color: #DC2626;
            font-size: .84rem;
            margin-top: .35rem;
        }
        .field input.invalid {
            border-color: #EF4444;
            box-shadow: 0 0 0 4px rgba(239,68,68,.08);
        }
        .terms-box {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            margin: 1.2rem 0 1.5rem;
        }
        .terms-box input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: .1rem;
            accent-color: #FF6B35;
        }
        .terms-box label {
            margin: 0;
            font-weight: 500;
            color: #475569;
            line-height: 1.55;
        }
        .terms-box a {
            color: #C84E21;
            font-weight: 700;
        }
        .auth-submit {
            width: 100%;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            font-weight: 700;
        }
        .auth-footer {
            text-align: center;
            padding: 1.35rem 2rem 1.8rem;
            background: #F8FAFC;
            border-top: 1px solid #EEF2F7;
        }
        .auth-footer p {
            margin: 0;
            color: #64748B;
        }
        .auth-footer a {
            color: #C84E21;
            font-weight: 700;
        }
        .general-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
            border-radius: 14px;
            padding: .95rem 1rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .back-home {
                top: 1rem;
                left: 1rem;
            }
            .auth-wrap {
                padding: 1rem;
            }
            .auth-header, .auth-body {
                padding: 1.5rem;
            }
            .auth-footer {
                padding: 1.2rem 1.5rem 1.5rem;
            }
        }
    </style>
</head>
<body class="auth-page">
    <a href="/index.php" class="btn btn-secondary back-home">
        <i class="fas fa-arrow-left"></i>
        Volver al inicio
    </a>

    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo-badge">
                    <img src="/assets/img/logo-quickorder.png" alt="QuickOrder" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="auth-logo-fallback" style="display:none;">🍕</div>
                </div>
                <h1>Crea tu cuenta</h1>
                <p>Regístrate para realizar pedidos, gestionar tus reservas y consultar tu historial.</p>
            </div>

            <div class="auth-body">
                <div class="auth-tabs">
                    <a href="/login.php" class="auth-tab">
                        <i class="fas fa-sign-in-alt"></i> Iniciar sesión
                    </a>
                    <div class="auth-tab active">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </div>
                </div>

                <div class="helper-box">
                    <h4><i class="fas fa-shield-alt"></i> Registro rápido y seguro</h4>
                    <p>Completa el formulario con tus datos reales. Después podrás iniciar sesión y continuar con tus pedidos.</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="general-error"><?php echo e($errors['general']); ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <?php if (function_exists('csrf_field')) echo csrf_field(); ?>

                    <div class="field">
                        <label for="name">Nombre completo</label>
                        <div class="input-wrap">
                            <i class="input-icon fas fa-user"></i>
                            <input id="name" type="text" name="name" value="<?php echo e($name); ?>" class="<?php echo isset($errors['name']) ? 'invalid' : ''; ?>" placeholder="Tu nombre y apellidos">
                        </div>
                        <?php if (isset($errors['name'])): ?><small class="field-error"><?php echo e($errors['name']); ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="email">Correo electrónico</label>
                        <div class="input-wrap">
                            <i class="input-icon fas fa-envelope"></i>
                            <input id="email" type="email" name="email" value="<?php echo e($email); ?>" class="<?php echo isset($errors['email']) ? 'invalid' : ''; ?>" placeholder="tu@email.com">
                        </div>
                        <?php if (isset($errors['email'])): ?><small class="field-error"><?php echo e($errors['email']); ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="phone">Teléfono</label>
                        <div class="input-wrap">
                            <i class="input-icon fas fa-phone"></i>
                            <input id="phone" type="tel" name="phone" value="<?php echo e($phone); ?>" class="<?php echo isset($errors['phone']) ? 'invalid' : ''; ?>" placeholder="+34 600 123 456">
                        </div>
                        <?php if (isset($errors['phone'])): ?><small class="field-error"><?php echo e($errors['phone']); ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="password">Contraseña</label>
                        <div class="input-wrap has-toggle">
                            <i class="input-icon fas fa-lock"></i>
                            <input id="password" type="password" name="password" class="<?php echo isset($errors['password']) ? 'invalid' : ''; ?>" placeholder="Mínimo 6 caracteres">
                            <i class="password-toggle fas fa-eye" data-target="password"></i>
                        </div>
                        <?php if (isset($errors['password'])): ?><small class="field-error"><?php echo e($errors['password']); ?></small><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirmar contraseña</label>
                        <div class="input-wrap has-toggle">
                            <i class="input-icon fas fa-lock"></i>
                            <input id="confirm_password" type="password" name="confirm_password" class="<?php echo isset($errors['confirm_password']) ? 'invalid' : ''; ?>" placeholder="Repite la contraseña">
                            <i class="password-toggle fas fa-eye" data-target="confirm_password"></i>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?><small class="field-error"><?php echo e($errors['confirm_password']); ?></small><?php endif; ?>
                    </div>

                    <div class="terms-box">
                        <input id="terms" type="checkbox" name="terms" value="1" <?php echo $terms ? 'checked' : ''; ?>>
                        <label for="terms">
                            Acepto los <a href="#">Términos y Condiciones</a> y la <a href="#">Política de Privacidad</a>.
                            <?php if (isset($errors['terms'])): ?><small class="field-error" style="margin-top:.35rem;"><?php echo e($errors['terms']); ?></small><?php endif; ?>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        <i class="fas fa-user-plus"></i>
                        Crear cuenta
                    </button>
                </form>
            </div>

            <div class="auth-footer">
                <p>¿Ya tienes una cuenta? <a href="/login.php">Inicia sesión aquí</a></p>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.password-toggle').forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const target = document.getElementById(this.getAttribute('data-target'));
                if (!target) return;
                const isPassword = target.getAttribute('type') === 'password';
                target.setAttribute('type', isPassword ? 'text' : 'password');
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
