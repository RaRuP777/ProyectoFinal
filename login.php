<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Si ya está logueado, redirigir
if (is_logged_in()) {
    if (is_admin()) {
        redirect(SITE_URL . '/admin/');
    } else {
        redirect(SITE_URL . '/');
    }
}

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validaciones
    $errors = [];
    
    if (empty($email)) {
        $errors[] = 'El email es obligatorio';
    } elseif (!validate_email($email)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($password)) {
        $errors[] = 'La contraseña es obligatoria';
    }
    
    // Si no hay errores, verificar credenciales
    if (empty($errors)) {
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        // Verificar si existe el usuario y la contraseña coincide
        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Recordar usuario (opcional)
            if ($remember) {
                setcookie('user_email', $email, time() + (86400 * 30), '/'); // 30 días
            }
            
            // Redirigir según rol
            if ($user['role'] === 'admin') {
                set_alert('success', '¡Bienvenido de nuevo, ' . $user['name'] . '!');
                redirect(SITE_URL . '/admin/');
            } else {
                set_alert('success', '¡Bienvenido, ' . $user['name'] . '!');
                redirect(SITE_URL . '/');
            }
        } else {
            // Credenciales incorrectas
            $errors[] = 'Email o contraseña incorrectos';
        }
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errors)) {
        set_alert('error', implode(', ', $errors));
    }
}

$page_title = 'Iniciar Sesión - ' . SITE_NAME;
include 'includes/header.php';
?>

<div class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-sign-in-alt"></i>
                <h1>Iniciar Sesión</h1>
                <p>Accede a tu cuenta de <?= SITE_NAME ?></p>
            </div>

            <form method="POST" action="" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        placeholder="cliente@example.com"
                        value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            placeholder="Tu contraseña"
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-row">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Recordarme</span>
                    </label>
                    <a href="#" class="forgot-password">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <div class="auth-footer">
                <p>¿No tienes una cuenta? <a href="<?= SITE_URL ?>/register.php">Regístrate aquí</a></p>
            </div>

            <!-- Credenciales de prueba -->
            <div class="test-credentials">
                <h3><i class="fas fa-info-circle"></i> Credenciales de Prueba</h3>
                <div class="credentials-grid">
                    <div class="credential-item">
                        <strong>👨‍💼 Administrador:</strong><br>
                        <code>admin@quickorder.com</code><br>
                        <code>admin123</code>
                        <button class="btn-copy" onclick="fillCredentials('admin@quickorder.com', 'admin123')">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                    <div class="credential-item">
                        <strong>👤 Cliente:</strong><br>
                        <code>cliente@example.com</code><br>
                        <code>cliente123</code>
                        <button class="btn-copy" onclick="fillCredentials('cliente@example.com', 'cliente123')">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Rellenar credenciales automáticamente
function fillCredentials(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    showAlert('info', 'Credenciales copiadas. Haz clic en "Iniciar Sesión"');
}

// Toggle mostrar/ocultar contraseña
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Validación del formulario
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        e.preventDefault();
        showAlert('error', 'Por favor, completa todos los campos');
    }
});

// Función para mostrar alertas
function showAlert(type, message) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible`;
    alert.innerHTML = `
        <div class="container">
            <span class="alert-message">${message}</span>
            <button class="alert-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.insertBefore(alert, document.body.firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Auto-rellenar si hay cookie
<?php if (isset($_COOKIE['user_email'])): ?>
document.getElementById('email').value = '<?= htmlspecialchars($_COOKIE['user_email']) ?>';
document.getElementById('remember').checked = true;
<?php endif; ?>
</script>

<style>
.auth-page {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 40px 20px;
}

.auth-container {
    width: 100%;
    max-width: 500px;
}

.auth-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.auth-header {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: white;
    padding: 40px;
    text-align: center;
}

.auth-header i {
    font-size: 48px;
    margin-bottom: 15px;
}

.auth-header h1 {
    margin: 0 0 10px 0;
    font-size: 28px;
}

.auth-header p {
    margin: 0;
    opacity: 0.9;
}

.auth-form {
    padding: 40px;
}

.password-input {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #999;
    cursor: pointer;
    font-size: 18px;
}

.toggle-password:hover {
    color: var(--primary-color);
}

.form-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.forgot-password {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 14px;
}

.forgot-password:hover {
    text-decoration: underline;
}

.auth-footer {
    text-align: center;
    padding: 20px 40px 40px;
    border-top: 1px solid #eee;
}

.auth-footer p {
    margin: 0;
    color: #666;
}

.auth-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}

.auth-footer a:hover {
    text-decoration: underline;
}

/* Credenciales de prueba */
.test-credentials {
    background: #f8f9fa;
    padding: 20px;
    margin: 0 40px 40px;
    border-radius: 10px;
    border: 2px dashed #ddd;
}

.test-credentials h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

.credentials-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.credential-item {
    background: white;
    padding: 15px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.8;
}

.credential-item strong {
    display: block;
    margin-bottom: 8px;
    color: #333;
}

.credential-item code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    color: var(--primary-color);
}

.btn-copy {
    margin-top: 10px;
    width: 100%;
    padding: 6px;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}

.btn-copy:hover {
    background: var(--secondary-color);
}

@media (max-width: 768px) {
    .credentials-grid {
        grid-template-columns: 1fr;
    }
    
    .auth-form,
    .auth-footer,
    .test-credentials {
        padding-left: 20px;
        padding-right: 20px;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
