-- ============================================================
--  QuickOrder — Datos de Ejemplo (SEED DATA)
--  Archivo: quickorder_seed.sql
--  Descripción: INSERTs para categorías, productos y usuario admin
--  Compatible: MySQL 5.7+ / MariaDB 10.3+ / phpMyAdmin
--  Ejecución segura: usa INSERT IGNORE / ON DUPLICATE KEY
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ============================================================
-- 1. USUARIO ADMINISTRADOR + USUARIOS DE PRUEBA
-- ============================================================
-- Contraseñas hasheadas con password_hash($pass, PASSWORD_DEFAULT)
-- admin123    → hash incluido abajo
-- cliente123  → hash incluido abajo
-- Para regenerar: echo password_hash('tupass', PASSWORD_DEFAULT);

INSERT INTO `users`
  (`name`, `email`, `password`, `phone`, `address`, `city`, `postal_code`, `role`, `is_verified`)
VALUES
  -- ► ADMINISTRADOR
  (
    'Admin QuickOrder',
    'admin@quickorder.com',
    '$2y$10$TKh8H1.PfbuNIAG1a5CDEO9c8rDqTjDHaksnvV10JzFf9oCkGnbS2',
    -- contraseña: admin123
    '+34 900 100 200',
    'Calle Gran Vía, 1',
    'Madrid',
    '28013',
    'admin',
    1
  ),
  -- ► CLIENTE 1
  (
    'María López García',
    'maria.lopez@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    -- contraseña: password
    '+34 611 222 333',
    'Calle Serrano, 45, 2º A',
    'Madrid',
    '28001',
    'customer',
    1
  ),
  -- ► CLIENTE 2
  (
    'Carlos Martínez Ruiz',
    'carlos.martinez@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '+34 622 333 444',
    'Avenida Diagonal, 200, 4º B',
    'Barcelona',
    '08013',
    'customer',
    1
  ),
  -- ► CLIENTE 3
  (
    'Laura Sánchez Moreno',
    'laura.sanchez@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '+34 633 444 555',
    'Calle Larios, 10, 3º C',
    'Málaga',
    '29015',
    'customer',
    1
  )
ON DUPLICATE KEY UPDATE
  `name`       = VALUES(`name`),
  `phone`      = VALUES(`phone`),
  `is_verified`= VALUES(`is_verified`);


-- ============================================================
-- 2. CATEGORÍAS (6 categorías principales)
-- ============================================================

INSERT INTO `categories`
  (`id`, `name`, `description`, `sort_order`)
VALUES
  (1, 'Pizzas',
      'Pizzas artesanales elaboradas en horno de leña con masa madre',          1),
  (2, 'Ensaladas',
      'Ensaladas frescas con ingredientes de temporada y aliños caseros',        2),
  (3, 'Hamburguesas',
      'Hamburguesas premium con carne 100% vacuno y pan artesano',               3),
  (4, 'Pastas',
      'Pastas frescas elaboradas cada mañana con recetas italianas originales',  4),
  (5, 'Postres',
      'Postres caseros, tartas del día y helados artesanos',                     5),
  (6, 'Bebidas',
      'Refrescos, zumos naturales, cervezas y aguas',                            6)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `sort_order`  = VALUES(`sort_order`);


-- ============================================================
-- 3. PRODUCTOS (30 productos repartidos en 6 categorías)
-- ============================================================

-- ─── 3.1 PIZZAS (category_id = 1) ───────────────────────────
INSERT INTO `products`
  (`id`, `category_id`, `name`, `description`,
   `price`, `discount_price`, `is_featured`, `stock`, `allergens`, `calories`)
VALUES
  ( 1, 1, 'Pizza Margarita',
    'La clásica italiana: salsa de tomate San Marzano, mozzarella fior di latte y albahaca fresca',
    12.99, NULL,  1, 80, 'Gluten, Lácteos', 820),

  ( 2, 1, 'Pizza Pepperoni',
    'Generosa cantidad de pepperoni artesano sobre base de tomate y mozzarella',
    14.99, 12.99, 1, 70, 'Gluten, Lácteos, Cerdo', 950),

  ( 3, 1, 'Pizza Cuatro Quesos',
    'Mozzarella, gorgonzola, parmesano añejo y queso de cabra sobre masa fina',
    15.99, NULL,  0, 60, 'Gluten, Lácteos', 1020),

  ( 4, 1, 'Pizza Vegetal',
    'Pimientos asados, champiñones, aceitunas, cebolla morada y rúcula fresca',
    13.49, NULL,  0, 60, 'Gluten, Lácteos', 750),

  ( 5, 1, 'Pizza BBQ Pollo',
    'Pollo a la parrilla, salsa barbacoa, cebolla caramelizada y mozzarella',
    15.49, 13.49, 1, 65, 'Gluten, Lácteos', 980),

-- ─── 3.2 ENSALADAS (category_id = 2) ────────────────────────
  ( 6, 2, 'Ensalada César',
    'Lechuga romana, pollo a la parrilla, parmesano laminado, crutones y nuestra salsa César casera',
    9.99, NULL, 1, 40, 'Gluten, Lácteos, Huevo, Pescado', 540),

  ( 7, 2, 'Ensalada Griega',
    'Tomate cherry, pepino, aceitunas kalamata, cebolla morada, pimiento y queso feta',
    8.99, NULL, 0, 40, 'Lácteos', 420),

  ( 8, 2, 'Ensalada de Quinoa',
    'Quinoa, aguacate, tomate seco, espinacas baby, semillas de sésamo y vinagreta de limón',
    10.49, NULL, 0, 35, 'Sésamo', 480),

  ( 9, 2, 'Ensalada Caprese',
    'Tomate de temporada, mozzarella fresca, albahaca y reducción de vinagre balsámico',
    8.49, NULL,  1, 30, 'Lácteos', 380),

  (10, 2, 'Ensalada de Pollo Tropical',
    'Pollo asado, mango fresco, piña, rúcula, nueces y aderezo de miel y mostaza',
    10.99, 9.49, 0, 30, 'Frutos secos, Mostaza', 490),

-- ─── 3.3 HAMBURGUESAS (category_id = 3) ─────────────────────
  (11, 3, 'Burger Clásica',
    'Carne de vacuno 200g madurada, lechuga, tomate, cebolla y nuestra salsa especial de la casa',
    11.99, NULL, 1, 50, 'Gluten, Lácteos, Huevo, Mostaza', 750),

  (12, 3, 'Burger BBQ Bacon',
    'Doble carne 2×150g, bacon crujiente, queso cheddar fundido y salsa barbacoa ahumada',
    13.99, 11.99, 1, 45, 'Gluten, Lácteos, Cerdo', 1050),

  (13, 3, 'Burger Vegana',
    'Medallón de garbanzos y remolacha, aguacate, tomate asado, lechuga y salsa tahini',
    12.49, NULL, 0, 35, 'Gluten, Sésamo', 620),

  (14, 3, 'Burger Mushroom Swiss',
    'Carne de vacuno 200g, champiñones salteados con ajo, queso suizo y alioli casero',
    13.49, NULL, 0, 40, 'Gluten, Lácteos, Huevo', 820),

  (15, 3, 'Burger Picante',
    'Carne 200g, jalapeños encurtidos, queso pepper jack, guacamole y salsa sriracha',
    12.99, NULL, 1, 40, 'Gluten, Lácteos', 870),

-- ─── 3.4 PASTAS (category_id = 4) ───────────────────────────
  (16, 4, 'Spaghetti Carbonara',
    'Spaghetti al dente con panceta curada, yema de huevo, parmesano y pimienta negra recién molida',
    10.99, NULL, 1, 40, 'Gluten, Lácteos, Huevo, Cerdo', 780),

  (17, 4, 'Penne Arrabiata',
    'Penne con salsa de tomate picante, ajo, peperoncino y albahaca fresca',
    9.49, NULL, 0, 40, 'Gluten', 620),

  (18, 4, 'Tagliatelle al Ragù',
    'Tagliatelle caseras con ragù de ternera cocinado a fuego lento durante 4 horas',
    12.49, 10.99, 1, 35, 'Gluten, Lácteos', 890),

  (19, 4, 'Risotto de Setas',
    'Arroz arborio cremoso con mezcla de setas silvestres, mantequilla y parmesano añejo',
    11.99, NULL, 0, 30, 'Lácteos', 720),

  (20, 4, 'Lasaña Clásica',
    'Capas de pasta fresca, ragù de ternera y cerdo, bechamel casera y parmesano gratinado',
    11.49, NULL, 0, 30, 'Gluten, Lácteos, Huevo', 950),

-- ─── 3.5 POSTRES (category_id = 5) ──────────────────────────
  (21, 5, 'Tiramisú Casero',
    'Receta tradicional italiana con bizcochos savoiardi, café espresso y mascarpone',
    5.49, NULL, 1, 25, 'Gluten, Lácteos, Huevo', 420),

  (22, 5, 'Tarta de Chocolate',
    'Bizcocho húmedo de cacao 70%, mousse de chocolate negro y cobertura de ganache',
    5.99, NULL, 0, 20, 'Gluten, Lácteos, Huevo', 510),

  (23, 5, 'Cheesecake de Frutos Rojos',
    'Base de galleta, crema de queso Philadelphia y coulis de fresas y frambuesas',
    6.49, 5.49, 1, 20, 'Gluten, Lácteos, Huevo', 460),

  (24, 5, 'Panna Cotta de Vainilla',
    'Panna cotta con vainilla de Madagascar y salsa de caramelo salado',
    4.99, NULL, 0, 25, 'Lácteos', 330),

  (25, 5, 'Helado Artesano (2 bolas)',
    'Elige dos sabores: vainilla, chocolate, fresa, pistacho o turrón',
    3.99, NULL, 0, 50, 'Lácteos, Frutos secos', 280),

-- ─── 3.6 BEBIDAS (category_id = 6) ──────────────────────────
  (26, 6, 'Refresco Lata (33cl)',
    'Coca-Cola, Coca-Cola Zero, Fanta Naranja, Fanta Limón o Sprite',
    2.50, NULL, 0, 200, NULL, 140),

  (27, 6, 'Agua Mineral (50cl)',
    'Agua mineral natural o con gas, botella de 50cl',
    1.80, NULL, 0, 200, NULL, 0),

  (28, 6, 'Zumo Natural (35cl)',
    'Recién exprimido al momento: naranja, limón, naranja-zanahoria o tropical',
    3.50, NULL, 0, 60, NULL, 160),

  (29, 6, 'Cerveza Artesana (33cl)',
    'Selección de cervezas locales: rubia, tostada o IPA. Pregunte disponibilidad',
    3.99, NULL, 0, 80, 'Gluten', 150),

  (30, 6, 'Batido Casero (40cl)',
    'Fresa, mango, plátano-vainilla o chocolate. Elaborado con leche entera o vegetal',
    4.49, NULL, 1, 40, 'Lácteos', 320)

ON DUPLICATE KEY UPDATE
  `name`           = VALUES(`name`),
  `description`    = VALUES(`description`),
  `price`          = VALUES(`price`),
  `discount_price` = VALUES(`discount_price`),
  `is_featured`    = VALUES(`is_featured`),
  `stock`          = VALUES(`stock`),
  `allergens`      = VALUES(`allergens`),
  `calories`       = VALUES(`calories`);


-- ============================================================
-- 4. CONFIGURACIÓN DEL SITIO (tabla settings)
-- ============================================================

INSERT INTO `settings` (`setting_key`, `setting_value`)
VALUES
  ('site_name',           'QuickOrder'),
  ('site_tagline',        'La mejor comida, entregada en tu puerta'),
  ('site_email',          'info@quickorder.com'),
  ('site_phone',          '+34 900 123 456'),
  ('site_whatsapp',       '+34 600 123 456'),
  ('site_address',        'Calle Gran Vía, 1, 28013 Madrid'),
  ('currency_symbol',     '€'),
  ('currency_code',       'EUR'),
  ('delivery_fee',        '2.50'),
  ('free_delivery_from',  '25.00'),
  ('min_order_amount',    '10.00'),
  ('delivery_time_min',   '25'),
  ('delivery_time_max',   '40'),
  ('tax_rate',            '10'),
  ('max_reservations_per_hour', '10'),
  ('opening_mon_fri',     '12:00 - 23:00'),
  ('opening_sat_sun',     '11:00 - 00:00'),
  ('facebook_url',        'https://facebook.com/quickorder'),
  ('instagram_url',       'https://instagram.com/quickorder'),
  ('twitter_url',         'https://twitter.com/quickorder'),
  ('google_maps_url',     'https://maps.google.com/?q=Gran+Via+1+Madrid'),
  ('meta_description',    'QuickOrder — Pizzas, hamburguesas, ensaladas y más. Pide online y recibe en 30 min.')
ON DUPLICATE KEY UPDATE
  `setting_value` = VALUES(`setting_value`);


-- ============================================================
-- 5. RESERVAS DE EJEMPLO
-- ============================================================

INSERT INTO `reservations`
  (`user_id`, `reservation_number`, `name`, `email`, `phone`,
   `reservation_date`, `reservation_time`, `guests`,
   `table_preference`, `special_requests`, `status`)
VALUES
  (2, 'RES-20260310-0001', 'María López García',   'maria.lopez@example.com',
   '+34 611 222 333', CURDATE() + INTERVAL 1 DAY, '14:00:00', 2,
   'interior', 'Sin gluten por favor',          'confirmed'),

  (3, 'RES-20260310-0002', 'Carlos Martínez Ruiz', 'carlos.martinez@example.com',
   '+34 622 333 444', CURDATE() + INTERVAL 2 DAY, '21:00:00', 4,
   'terraza', 'Cumpleaños, decoración especial', 'pending'),

  (4, 'RES-20260310-0003', 'Laura Sánchez Moreno', 'laura.sanchez@example.com',
   '+34 633 444 555', CURDATE() + INTERVAL 3 DAY, '20:30:00', 3,
   'privado', NULL,                              'confirmed')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);


-- ============================================================
-- 6. MENSAJES DE CONTACTO DE EJEMPLO
-- ============================================================

INSERT INTO `contact_messages`
  (`name`, `email`, `phone`, `subject`, `message`, `is_read`)
VALUES
  ('Pedro Fernández', 'pedro.f@example.com', '+34 644 111 222',
   'Consulta sobre alérgenos',
   'Hola, me gustaría saber qué platos son aptos para personas con intolerancia al gluten. Muchas gracias.',
   0),

  ('Ana Gómez', 'ana.g@example.com', NULL,
   'Pedido con error',
   'Realicé el pedido #ORD-20260308-0021 y me faltó la pizza pepperoni. ¿Pueden compensarme?',
   1),

  ('Roberto Díaz', 'roberto.d@example.com', '+34 655 333 444',
   'Reserva para evento corporativo',
   'Somos una empresa de 15 personas y queremos hacer una cena de equipo. ¿Tienen disponibilidad para el próximo viernes?',
   0)
ON DUPLICATE KEY UPDATE `is_read` = VALUES(`is_read`);


-- ============================================================
-- 7. PEDIDOS DE EJEMPLO (orders + order_items)
-- ============================================================

INSERT INTO `orders`
  (`id`, `user_id`, `order_number`, `total_amount`, `subtotal`,
   `tax_amount`, `delivery_fee`, `status`,
   `payment_method`, `payment_status`, `delivery_type`,
   `customer_name`, `customer_email`, `customer_phone`,
   `delivery_address`, `delivery_city`, `notes`)
VALUES
  (1, 2, 'ORD-20260308-0001', 31.47, 27.97, 2.80, 2.50,
   'delivered', 'card', 'paid', 'delivery',
   'María López García', 'maria.lopez@example.com', '+34 611 222 333',
   'Calle Serrano, 45, 2º A', 'Madrid', 'Sin cebolla en la pizza'),

  (2, 3, 'ORD-20260309-0002', 28.97, 26.47, 2.65, 2.50,
   'delivering', 'cash', 'pending', 'delivery',
   'Carlos Martínez Ruiz', 'carlos.martinez@example.com', '+34 622 333 444',
   'Avenida Diagonal, 200, 4º B', 'Barcelona', NULL),

  (3, 4, 'ORD-20260310-0003', 19.48, 16.98, 1.70, 2.50,
   'pending', 'online', 'paid', 'pickup',
   'Laura Sánchez Moreno', 'laura.sanchez@example.com', '+34 633 444 555',
   NULL, NULL, 'Recogeré en 20 minutos')
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

INSERT INTO `order_items`
  (`order_id`, `product_id`, `name`, `price`, `quantity`, `subtotal`)
VALUES
  -- Pedido 1: pizza + ensalada + bebida
  (1,  1, 'Pizza Margarita',       12.99, 1, 12.99),
  (1,  6, 'Ensalada César',         9.99, 1,  9.99),
  (1, 26, 'Refresco Lata (33cl)',   2.50, 2,  5.00),
  -- Pedido 2: burger + burger + batido
  (2, 12, 'Burger BBQ Bacon',      13.99, 1, 13.99),
  (2, 11, 'Burger Clásica',        11.99, 1, 11.99),
  (2, 30, 'Batido Casero (40cl)',   4.49, 1,  4.49),
  -- Pedido 3: pasta + postre
  (3, 16, 'Spaghetti Carbonara',   10.99, 1, 10.99),
  (3, 21, 'Tiramisú Casero',        5.49, 1,  5.49),
  (3, 28, 'Zumo Natural (35cl)',    3.50, 1,  3.50)
ON DUPLICATE KEY UPDATE `quantity` = VALUES(`quantity`);


SET foreign_key_checks = 1;

-- ============================================================
-- ✅ SEED COMPLETADO
-- ============================================================
-- Resumen de datos insertados:
--   · 4  usuarios  (1 admin + 3 clientes)
--   · 6  categorías
--   · 30 productos  (5 por categoría)
--   · 22 configuraciones del sitio
--   · 3  reservas de ejemplo
--   · 3  mensajes de contacto
--   · 3  pedidos con líneas de detalle
--
-- Credenciales de acceso:
--   Admin   → admin@quickorder.com   | admin123
--   Cliente → maria.lopez@example.com | password
-- ============================================================
