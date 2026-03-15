<?php
/**
 * Página de Contacto - QuickOrder
 */

// Incluir archivos necesarios ANTES del header
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Configurar título de la página
$page_title = 'Contacto';

// Procesar formulario de contacto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $subject = clean_input($_POST['subject'] ?? '');
    $message = clean_input($_POST['message'] ?? '');
    $privacy = isset($_POST['privacy']) ? 1 : 0;
    
    // Validaciones
    $errors = [];
    
    if (strlen($name) < 3) {
        $errors[] = 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (!validate_email($email)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($subject)) {
        $errors[] = 'Debes seleccionar un asunto';
    }
    
    if (strlen($message) < 10 || strlen($message) > 500) {
        $errors[] = 'El mensaje debe tener entre 10 y 500 caracteres';
    }
    
    if (!$privacy) {
        $errors[] = 'Debes aceptar la política de privacidad';
    }
    
    if (empty($errors)) {
        // Guardar en la base de datos
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
        
        if ($stmt->execute()) {
            set_alert('success', '¡Mensaje enviado exitosamente! Te responderemos pronto.');
            
            // Opcional: Enviar email al admin (si tienes configurado mail)
            // mail(SITE_EMAIL, "Nuevo mensaje de contacto: $subject", $message, "From: $email");
            
            redirect('/contact.php');
        } else {
            set_alert('error', 'Error al enviar el mensaje. Inténtalo de nuevo.');
        }
        
        $stmt->close();
    } else {
        foreach ($errors as $error) {
            set_alert('error', $error);
        }
    }
}

// Obtener configuraciones del sitio
$site_name = get_setting('site_name', 'QuickOrder');
$site_email = get_setting('site_email', 'info@quickorder.es');
$site_phone = get_setting('site_phone', '+34 912 345 678');
$site_address = get_setting('site_address', 'Calle Gran Vía 123, 28013 Madrid, España');
$whatsapp_number = get_setting('whatsapp_number', '+34912345678');
$facebook_url = get_setting('facebook_url', 'https://facebook.com');
$instagram_url = get_setting('instagram_url', 'https://instagram.com');
$twitter_url = get_setting('twitter_url', 'https://twitter.com');
$opening_hours = json_decode(get_setting('opening_hours', '{}'), true);

// Horarios por defecto si no existen
if (empty($opening_hours)) {
    $opening_hours = [
        'monday' => ['open' => '11:00', 'close' => '23:00'],
        'tuesday' => ['open' => '11:00', 'close' => '23:00'],
        'wednesday' => ['open' => '11:00', 'close' => '23:00'],
        'thursday' => ['open' => '11:00', 'close' => '23:00'],
        'friday' => ['open' => '11:00', 'close' => '00:00'],
        'saturday' => ['open' => '11:00', 'close' => '00:00'],
        'sunday' => ['open' => '12:00', 'close' => '23:00']
    ];
}

// Incluir header
require_once 'includes/header.php';
?>

<style>
    .contact-hero {
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.95), rgba(255, 167, 38, 0.95)),
                    url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 400"><rect fill="%23f0f0f0" width="1200" height="400"/></svg>');
        background-size: cover;
        background-position: center;
        padding: 4rem 0;
        color: white;
        text-align: center;
    }

    .contact-hero h1 {
        color: white;
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .contact-hero p {
        font-size: 1.15rem;
        opacity: 0.95;
        color: white;
    }

    .contact-content {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 3rem;
        margin-top: -3rem;
        position: relative;
        z-index: 10;
    }

    .contact-info-card {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        padding: 2rem;
    }

    .info-item {
        display: flex;
        gap: 1rem;
        padding: 1.5rem;
        border-radius: var(--radius-md);
        transition: var(--transition-fast);
        margin-bottom: 1rem;
    }

    .info-item:hover {
        background: var(--bg-secondary);
        transform: translateX(5px);
    }

    .info-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .info-content h3 {
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .info-content p {
        color: var(--text-secondary);
        margin: 0;
    }

    .info-content a {
        color: var(--primary-color);
        font-weight: 600;
    }

    .map-container {
        margin-top: 2rem;
        border-radius: var(--radius-md);
        overflow: hidden;
        height: 250px;
        background: var(--bg-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #ddd;
    }

    .map-placeholder {
        text-align: center;
        color: var(--text-light);
    }

    .contact-form-card {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        padding: 2.5rem;
    }

    .char-counter {
        text-align: right;
        font-size: 0.875rem;
        color: var(--text-light);
        margin-top: 0.25rem;
    }

    .social-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px dashed #e0e0e0;
    }

    .social-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    .social-card {
        text-align: center;
        padding: 1.5rem 1rem;
        border-radius: var(--radius-md);
        border: 2px solid #e0e0e0;
        transition: var(--transition-fast);
        cursor: pointer;
    }

    .social-card:hover {
        border-color: var(--primary-color);
        background: rgba(255, 107, 53, 0.05);
        transform: translateY(-3px);
    }

    .social-card i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .social-card.facebook { color: #1877F2; }
    .social-card.instagram { color: #E4405F; }
    .social-card.twitter { color: #1DA1F2; }
    .social-card.whatsapp { color: #25D366; }

    .social-card p {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .opening-hours {
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(255, 167, 38, 0.1));
        padding: 1.5rem;
        border-radius: var(--radius-md);
        margin-top: 1.5rem;
    }

    .opening-hours h4 {
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .hours-item {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .hours-item:last-child {
        border-bottom: none;
    }

    .hours-day {
        font-weight: 600;
        color: var(--text-primary);
    }

    .hours-time {
        color: var(--text-secondary);
    }

    .hours-time.open {
        color: var(--success-color);
        font-weight: 600;
    }

    @media (max-width: 968px) {
        .contact-content {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .social-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<!-- HERO -->
<section class="contact-hero">
    <div class="container">
        <h1 class="fade-in">Contáctanos</h1>
        <p class="fade-in" style="animation-delay: 0.1s;">¿Tienes alguna pregunta? Estamos aquí para ayudarte</p>
    </div>
</section>

<!-- CONTENIDO PRINCIPAL -->
<section class="section">
    <div class="container contact-content">
        <!-- Información de Contacto -->
        <div>
            <div class="contact-info-card fade-in">
                <h2 style="margin-bottom: 1.5rem;">Información de Contacto</h2>
                
                <!-- Dirección -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="info-content">
                        <h3>Nuestra Ubicación</h3>
                        <p><?php echo htmlspecialchars($site_address); ?></p>
                        <a href="https://maps.google.com" target="_blank">Ver en Google Maps →</a>
                    </div>
                </div>

                <!-- Teléfono -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="info-content">
                        <h3>Teléfono</h3>
                        <p><a href="tel:<?php echo htmlspecialchars($site_phone); ?>"><?php echo htmlspecialchars($site_phone); ?></a></p>
                        <p style="font-size: 0.875rem; margin-top: 0.25rem;">Lun-Dom: 11:00 - 23:00</p>
                    </div>
                </div>

                <!-- Email -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-content">
                        <h3>Correo Electrónico</h3>
                        <p><a href="mailto:<?php echo htmlspecialchars($site_email); ?>"><?php echo htmlspecialchars($site_email); ?></a></p>
                        <p style="font-size: 0.875rem; margin-top: 0.25rem;">Respondemos en 24h</p>
                    </div>
                </div>

                <!-- WhatsApp -->
                <div class="info-item">
                    <div class="info-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="info-content">
                        <h3>WhatsApp</h3>
                        <p><a href="https://wa.me/<?php echo htmlspecialchars(str_replace(['+', ' '], '', $whatsapp_number)); ?>" target="_blank"><?php echo htmlspecialchars($whatsapp_number); ?></a></p>
                        <p style="font-size: 0.875rem; margin-top: 0.25rem;">Atención inmediata</p>
                    </div>
                </div>

                <!-- Horario -->
                <div class="opening-hours">
                    <h4>
                        <i class="fas fa-clock"></i>
                        Horario de Atención
                    </h4>
                    <div class="hours-item">
                        <span class="hours-day">Lunes - Jueves</span>
                        <span class="hours-time open">
                            <?php echo $opening_hours['monday']['open'] ?? '11:00'; ?> - 
                            <?php echo $opening_hours['thursday']['close'] ?? '23:00'; ?>
                        </span>
                    </div>
                    <div class="hours-item">
                        <span class="hours-day">Viernes - Sábado</span>
                        <span class="hours-time open">
                            <?php echo $opening_hours['friday']['open'] ?? '11:00'; ?> - 
                            <?php echo $opening_hours['saturday']['close'] ?? '00:00'; ?>
                        </span>
                    </div>
                    <div class="hours-item">
                        <span class="hours-day">Domingo</span>
                        <span class="hours-time open">
                            <?php echo $opening_hours['sunday']['open'] ?? '12:00'; ?> - 
                            <?php echo $opening_hours['sunday']['close'] ?? '23:00'; ?>
                        </span>
                    </div>
                </div>

                <!-- Mapa -->
                <div class="map-container">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt" style="font-size: 3rem; margin-bottom: 0.5rem;"></i>
                        <p><strong>Mapa de Google</strong></p>
                        <p><?php echo htmlspecialchars($site_address); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Contacto -->
        <div>
            <div class="contact-form-card fade-in" style="animation-delay: 0.1s;">
                <h2>Envíanos un Mensaje</h2>
                <p>Completa el formulario y te responderemos lo antes posible</p>

                <form method="POST" action="" id="contactForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="name" class="form-control" placeholder="Tu nombre" required minlength="3">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Correo Electrónico *</label>
                            <input type="email" name="email" class="form-control" placeholder="tu@email.com" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+34 XXX XXX XXX">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Asunto *</label>
                            <select name="subject" class="form-control" required>
                                <option value="">Selecciona un asunto</option>
                                <option value="pedido">Consulta sobre pedido</option>
                                <option value="reserva">Consulta sobre reserva</option>
                                <option value="menu">Información del menú</option>
                                <option value="delivery">Información de delivery</option>
                                <option value="sugerencia">Sugerencia o comentario</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mensaje *</label>
                        <textarea 
                            name="message"
                            class="form-control" 
                            placeholder="Escribe tu mensaje aquí..." 
                            rows="6" 
                            required 
                            minlength="10" 
                            maxlength="500"
                            id="messageInput"
                            oninput="updateCharCount()"
                        ></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span> / 500 caracteres
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: start; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="privacy" required style="width: auto; margin-top: 0.25rem;">
                            <span style="font-size: 0.9rem; color: var(--text-secondary);">
                                Acepto la <a href="#" style="color: var(--primary-color); font-weight: 600;">Política de Privacidad</a> 
                                y el tratamiento de mis datos personales
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-paper-plane"></i>
                        Enviar Mensaje
                    </button>
                </form>

                <!-- Redes Sociales -->
                <div class="social-section">
                    <h4>También puedes encontrarnos en:</h4>
                    <div class="social-grid">
                        <div class="social-card facebook" onclick="window.open('<?php echo htmlspecialchars($facebook_url); ?>', '_blank')">
                            <i class="fab fa-facebook-f"></i>
                            <p>Facebook</p>
                        </div>
                        <div class="social-card instagram" onclick="window.open('<?php echo htmlspecialchars($instagram_url); ?>', '_blank')">
                            <i class="fab fa-instagram"></i>
                            <p>Instagram</p>
                        </div>
                        <div class="social-card twitter" onclick="window.open('<?php echo htmlspecialchars($twitter_url); ?>', '_blank')">
                            <i class="fab fa-twitter"></i>
                            <p>Twitter</p>
                        </div>
                        <div class="social-card whatsapp" onclick="window.open('https://wa.me/<?php echo htmlspecialchars(str_replace(['+', ' '], '', $whatsapp_number)); ?>', '_blank')">
                            <i class="fab fa-whatsapp"></i>
                            <p>WhatsApp</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Contador de caracteres
    function updateCharCount() {
        const input = document.getElementById('messageInput');
        const counter = document.getElementById('charCount');
        counter.textContent = input.value.length;
        
        if (input.value.length > 450) {
            counter.style.color = 'var(--error-color)';
        } else {
            counter.style.color = 'var(--text-light)';
        }
    }
</script>

<?php require_once 'includes/footer.php'; ?>
