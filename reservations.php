<?php
/**
 * Página de Reservas - QuickOrder
 */

// Incluir archivos necesarios ANTES del header
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Configurar título de la página
$page_title = 'Reservar Mesa';

// Verificar si el usuario está logueado (opcional, puedes permitir reservas sin login)
// if (!is_logged_in()) {
//     set_alert('warning', 'Debes iniciar sesión para hacer una reserva');
//     redirect('/login.php');
// }

// Procesar formulario de reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $reservation_date = clean_input($_POST['reservation_date'] ?? '');
    $reservation_time = clean_input($_POST['reservation_time'] ?? '');
    $guests = intval($_POST['guests'] ?? 0);
    $table_preference = clean_input($_POST['table_preference'] ?? '');
    $special_requests = clean_input($_POST['special_requests'] ?? '');
    
    $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
    
    // Validaciones
    $errors = [];
    
    if (strlen($name) < 3) {
        $errors[] = 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (!validate_email($email)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($phone)) {
        $errors[] = 'El teléfono es obligatorio';
    }
    
    if (empty($reservation_date) || strtotime($reservation_date) < strtotime('today')) {
        $errors[] = 'Fecha de reserva inválida';
    }
    
    if (empty($reservation_time)) {
        $errors[] = 'Debes seleccionar una hora';
    }
    
    if ($guests < 1 || $guests > 20) {
        $errors[] = 'Número de comensales inválido (1-20)';
    }
    
    // Verificar disponibilidad (máximo 10 reservas por hora)
    if (empty($errors)) {
        $datetime = $reservation_date . ' ' . $reservation_time;
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservations WHERE reservation_date = ? AND reservation_time = ? AND status != 'cancelled'");
        $stmt->bind_param("ss", $reservation_date, $reservation_time);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] >= 10) {
            $errors[] = 'No hay disponibilidad para esa fecha y hora. Por favor, selecciona otro horario.';
        }
        $stmt->close();
    }
    
    if (empty($errors)) {
        // Generar número de reserva
        $reservation_number = generate_reservation_number();
        
        // Insertar reserva
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, name, email, phone, reservation_date, reservation_time, guests, table_preference, special_requests, reservation_number, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("isssssisss", $user_id, $name, $email, $phone, $reservation_date, $reservation_time, $guests, $table_preference, $special_requests, $reservation_number);
        
        if ($stmt->execute()) {
            set_alert('success', "¡Reserva confirmada! Tu número de reserva es: $reservation_number");
            
            // Opcional: Enviar email de confirmación
            // mail($email, "Confirmación de Reserva - $reservation_number", "...", "From: " . SITE_EMAIL);
            
            redirect('/reservations.php?success=1&reservation_number=' . $reservation_number);
        } else {
            set_alert('error', 'Error al crear la reserva. Inténtalo de nuevo.');
        }
        
        $stmt->close();
    } else {
        foreach ($errors as $error) {
            set_alert('error', $error);
        }
    }
}

// Incluir header
require_once 'includes/header.php';

// Verificar si viene de confirmación
$success = isset($_GET['success']) && $_GET['success'] == 1;
$reservation_number = $_GET['reservation_number'] ?? '';
?>

<style>
    .reservation-hero {
        background: linear-gradient(135deg, rgba(255, 107, 53, 0.95), rgba(255, 167, 38, 0.95));
        padding: 4rem 0;
        color: white;
        text-align: center;
    }

    .reservation-hero h1 {
        color: white;
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .reservation-content {
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 2rem;
        margin-top: -3rem;
        position: relative;
        z-index: 10;
    }

    .reservation-form-card {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        padding: 2.5rem;
    }

    .time-slots {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .time-slot {
        padding: 0.75rem;
        border: 2px solid #e0e0e0;
        border-radius: var(--radius-md);
        text-align: center;
        cursor: pointer;
        transition: var(--transition-fast);
        font-weight: 600;
        background: white;
    }

    .time-slot:hover:not(.unavailable) {
        border-color: var(--primary-color);
        background: rgba(255, 107, 53, 0.05);
    }

    .time-slot.selected {
        border-color: var(--primary-color);
        background: var(--primary-color);
        color: white;
    }

    .time-slot.unavailable {
        opacity: 0.4;
        cursor: not-allowed;
        background: #f5f5f5;
    }

    .guests-selector {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .guest-option {
        padding: 1rem;
        border: 2px solid #e0e0e0;
        border-radius: var(--radius-md);
        text-align: center;
        cursor: pointer;
        transition: var(--transition-fast);
        font-weight: 600;
        background: white;
    }

    .guest-option:hover {
        border-color: var(--primary-color);
        background: rgba(255, 107, 53, 0.05);
    }

    .guest-option.selected {
        border-color: var(--primary-color);
        background: var(--primary-color);
        color: white;
    }

    .reservation-summary {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        padding: 2rem;
        height: fit-content;
        position: sticky;
        top: 100px;
    }

    .summary-header {
        text-align: center;
        padding-bottom: 1.5rem;
        border-bottom: 2px dashed #e0e0e0;
        margin-bottom: 1.5rem;
    }

    .summary-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .summary-label {
        color: var(--text-secondary);
    }

    .summary-value {
        font-weight: 700;
        color: var(--text-primary);
    }

    .benefits-list {
        background: rgba(76, 175, 80, 0.1);
        padding: 1.5rem;
        border-radius: var(--radius-md);
        margin-top: 1.5rem;
    }

    .benefit-item {
        display: flex;
        align-items: start;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .benefit-item i {
        color: var(--success-color);
        margin-top: 0.25rem;
    }

    .confirmation-card {
        text-align: center;
        padding: 2rem;
    }

    .confirmation-icon {
        width: 100px;
        height: 100px;
        margin: 0 auto 1.5rem;
        background: var(--success-color);
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
        animation: scaleUp 0.5s ease-out;
    }

    @keyframes scaleUp {
        0% { transform: scale(0); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .reservation-number {
        display: inline-block;
        background: var(--bg-secondary);
        padding: 1rem 2rem;
        border-radius: var(--radius-md);
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 1rem 0;
    }

    @media (max-width: 968px) {
        .reservation-content {
            grid-template-columns: 1fr;
        }
        .reservation-summary {
            position: static;
        }
        .time-slots {
            grid-template-columns: repeat(3, 1fr);
        }
    }
</style>

<!-- HERO -->
<section class="reservation-hero">
    <div class="container">
        <h1 class="fade-in">Reserva tu Mesa</h1>
        <p class="fade-in" style="animation-delay: 0.1s;">Asegura tu lugar en QuickOrder y disfruta de una experiencia única</p>
    </div>
</section>

<!-- CONTENIDO PRINCIPAL -->
<section class="section">
    <div class="container">
        <?php if ($success): ?>
            <!-- Pantalla de Confirmación -->
            <div class="reservation-form-card fade-in" style="max-width: 600px; margin: 0 auto;">
                <div class="confirmation-card">
                    <div class="confirmation-icon">✓</div>
                    <h2 style="color: var(--success-color);">¡Reserva Confirmada!</h2>
                    <p style="font-size: 1.1rem; margin: 1rem 0;">Tu mesa ha sido reservada exitosamente</p>
                    
                    <div class="reservation-number">
                        <?php echo htmlspecialchars($reservation_number); ?>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Hemos enviado un correo de confirmación con todos los detalles
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                        <a href="<?php echo SITE_URL; ?>/" class="btn btn-outline" style="flex: 1;">
                            <i class="fas fa-home"></i>
                            Volver al Inicio
                        </a>
                        <a href="<?php echo SITE_URL; ?>/#menu" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-utensils"></i>
                            Ver Menú
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Formulario de Reserva -->
            <div class="reservation-content">
                <div>
                    <div class="reservation-form-card fade-in">
                        <h2>Completa tu Reserva</h2>
                        <p>Rellena el formulario para reservar tu mesa</p>

                        <form method="POST" action="" id="reservationForm">
                            <input type="hidden" name="create_reservation" value="1">
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    Selecciona la Fecha *
                                </label>
                                <input type="date" name="reservation_date" class="form-control" id="reservationDate" required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-clock"></i>
                                    Selecciona la Hora *
                                </label>
                                <div class="time-slots" id="timeSlots">
                                    <?php
                                    $times = ['12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00', '22:30'];
                                    foreach ($times as $time):
                                    ?>
                                        <div class="time-slot" data-time="<?php echo $time; ?>">
                                            <?php echo $time; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="reservation_time" id="selectedTime" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-users"></i>
                                    ¿Cuántas personas sois? *
                                </label>
                                <div class="guests-selector" id="guestsSelector">
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <div class="guest-option" data-guests="<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="guests" id="selectedGuests" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-chair"></i>
                                    Preferencia de Mesa (Opcional)
                                </label>
                                <select name="table_preference" class="form-control">
                                    <option value="">Sin preferencia</option>
                                    <option value="ventana">Junto a la ventana</option>
                                    <option value="terraza">Terraza</option>
                                    <option value="interior">Interior</option>
                                    <option value="privado">Zona privada</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Nombre Completo *
                                </label>
                                <input type="text" name="name" class="form-control" placeholder="Tu nombre" required minlength="3">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-envelope"></i>
                                        Correo Electrónico *
                                    </label>
                                    <input type="email" name="email" class="form-control" placeholder="tu@email.com" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Teléfono *
                                    </label>
                                    <input type="tel" name="phone" class="form-control" placeholder="+34 XXX XXX XXX" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-comment-alt"></i>
                                    Comentarios o Solicitudes Especiales
                                </label>
                                <textarea name="special_requests" class="form-control" placeholder="Alergias, celebración especial, silla para niños..." rows="4" maxlength="250"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fas fa-check-circle"></i>
                                Confirmar Reserva
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Resumen -->
                <div>
                    <div class="reservation-summary fade-in" style="animation-delay: 0.1s;">
                        <div class="summary-header">
                            <div class="summary-icon">📅</div>
                            <h3>Resumen de Reserva</h3>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">📅 Fecha</span>
                            <span class="summary-value" id="summaryDate">-</span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">🕐 Hora</span>
                            <span class="summary-value" id="summaryTime">-</span>
                        </div>

                        <div class="summary-item">
                            <span class="summary-label">👥 Comensales</span>
                            <span class="summary-value" id="summaryGuests">-</span>
                        </div>

                        <div class="benefits-list">
                            <h4 style="color: var(--success-color); margin-bottom: 1rem;">
                                🎁 Beneficios de Reservar
                            </h4>
                            <div class="benefit-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Mesa garantizada sin esperas</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Postre de cortesía incluido</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Cancelación gratuita hasta 6h antes</span>
                            </div>
                            <div class="benefit-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Atención personalizada</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
    // Selección de hora
    document.querySelectorAll('.time-slot:not(.unavailable)').forEach(slot => {
        slot.addEventListener('click', function() {
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('selectedTime').value = this.dataset.time;
            updateSummary();
        });
    });

    // Selección de comensales
    document.querySelectorAll('.guest-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.guest-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            document.getElementById('selectedGuests').value = this.dataset.guests;
            updateSummary();
        });
    });

    // Cambio de fecha
    document.getElementById('reservationDate').addEventListener('change', function() {
        updateSummary();
    });

    function updateSummary() {
        const date = document.getElementById('reservationDate').value;
        const time = document.getElementById('selectedTime').value;
        const guests = document.getElementById('selectedGuests').value;
        
        document.getElementById('summaryDate').textContent = date ? 
            new Date(date + 'T00:00:00').toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric', month: 'long' }) : '-';
        document.getElementById('summaryTime').textContent = time || '-';
        document.getElementById('summaryGuests').textContent = guests ? `${guests} ${guests === '1' ? 'persona' : 'personas'}` : '-';
    }

    // Validación del formulario
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        const time = document.getElementById('selectedTime').value;
        const guests = document.getElementById('selectedGuests').value;
        
        if (!time) {
            e.preventDefault();
            alert('Por favor selecciona una hora');
            return false;
        }
        
        if (!guests) {
            e.preventDefault();
            alert('Por favor selecciona el número de comensales');
            return false;
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
