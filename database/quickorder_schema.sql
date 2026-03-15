-- ============================================================
--  QuickOrder — Esquema completo de base de datos
--  Compatible con MySQL 5.7+ / MariaDB 10.3+
--  Ejecución segura: usa IF NOT EXISTS / IF EXISTS
--  Generado para XAMPP + PHP 8.x
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 0. CREAR BASE DE DATOS (si no existe)
-- ------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `quickorder`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `quickorder`;

-- ============================================================
-- 1. TABLA: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(100) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(20)  DEFAULT NULL,
  `address`       TEXT         DEFAULT NULL,
  `city`          VARCHAR(80)  DEFAULT NULL,
  `postal_code`   VARCHAR(10)  DEFAULT NULL,
  `role`          ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  `avatar`        VARCHAR(255) DEFAULT NULL,
  `is_verified`   TINYINT(1)   DEFAULT 0,
  `reset_token`   VARCHAR(100) DEFAULT NULL,
  `last_login`    DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. TABLA: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)  NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `sort_order`  INT(11)      DEFAULT 0,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. TABLA: products
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
  `id`             INT(11)        NOT NULL AUTO_INCREMENT,
  `category_id`    INT(11)        DEFAULT NULL,
  `name`           VARCHAR(150)   NOT NULL,
  `description`    TEXT           DEFAULT NULL,
  `price`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `discount_price` DECIMAL(10,2)  DEFAULT NULL,
  `image`          VARCHAR(255)   DEFAULT NULL,
  `stock`          INT(11)        DEFAULT 100,
  `is_featured`    TINYINT(1)     DEFAULT 0,
  `allergens`      VARCHAR(255)   DEFAULT NULL,
  `calories`       INT(11)        DEFAULT NULL,
  `sort_order`     INT(11)        DEFAULT 0,
  `created_at`     DATETIME       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_price`    (`price`),
  CONSTRAINT `fk_product_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABLA: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               INT(11)        NOT NULL AUTO_INCREMENT,
  `user_id`          INT(11)        DEFAULT NULL,
  `order_number`     VARCHAR(50)    NOT NULL,
  `total_amount`     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `subtotal`         DECIMAL(10,2)  DEFAULT 0.00,
  `tax_amount`       DECIMAL(10,2)  DEFAULT 0.00,
  `delivery_fee`     DECIMAL(10,2)  DEFAULT 0.00,
  `discount_amount`  DECIMAL(10,2)  DEFAULT 0.00,
  `status`           ENUM('pending','confirmed','preparing','ready','delivering','delivered','cancelled')
                       NOT NULL DEFAULT 'pending',
  `payment_method`   ENUM('cash','card','online') DEFAULT 'cash',
  `payment_status`   ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
  `delivery_type`    ENUM('delivery','pickup') DEFAULT 'delivery',
  `delivery_address` TEXT           DEFAULT NULL,
  `delivery_city`    VARCHAR(80)    DEFAULT NULL,
  `customer_name`    VARCHAR(100)   DEFAULT NULL,
  `customer_email`   VARCHAR(100)   DEFAULT NULL,
  `customer_phone`   VARCHAR(20)    DEFAULT NULL,
  `notes`            TEXT           DEFAULT NULL,
  `estimated_time`   INT(11)        DEFAULT 30,
  `created_at`       DATETIME       DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_number` (`order_number`),
  KEY `idx_user`   (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_date`   (`created_at`),
  CONSTRAINT `fk_order_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABLA: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`   INT(11)       NOT NULL,
  `product_id` INT(11)       DEFAULT NULL,
  `name`       VARCHAR(150)  NOT NULL,
  `price`      DECIMAL(10,2) NOT NULL,
  `quantity`   INT(11)       NOT NULL DEFAULT 1,
  `subtotal`   DECIMAL(10,2) NOT NULL,
  `notes`      VARCHAR(255)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order`   (`order_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_item_order`
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_item_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. TABLA: reservations
-- ============================================================
CREATE TABLE IF NOT EXISTS `reservations` (
  `id`                INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`           INT(11)     DEFAULT NULL,
  `reservation_number` VARCHAR(50) NOT NULL,
  `name`              VARCHAR(100) NOT NULL,
  `email`             VARCHAR(100) NOT NULL,
  `phone`             VARCHAR(20)  NOT NULL,
  `reservation_date`  DATE         NOT NULL,
  `reservation_time`  TIME         NOT NULL,
  `guests`            INT(11)      NOT NULL DEFAULT 2,
  `table_preference`  VARCHAR(50)  DEFAULT NULL,
  `special_requests`  TEXT         DEFAULT NULL,
  `status`            ENUM('pending','confirmed','cancelled','completed')
                        DEFAULT 'pending',
  `notes`             TEXT         DEFAULT NULL,
  `created_at`        DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reservation_number` (`reservation_number`),
  KEY `idx_date`    (`reservation_date`),
  KEY `idx_user`    (`user_id`),
  KEY `idx_status`  (`status`),
  CONSTRAINT `fk_reservation_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. TABLA: contact_messages
-- ============================================================
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `phone`      VARCHAR(20)  DEFAULT NULL,
  `subject`    VARCHAR(150) NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   DEFAULT 0,
  `is_replied` TINYINT(1)   DEFAULT 0,
  `reply_text` TEXT         DEFAULT NULL,
  `replied_at` DATETIME     DEFAULT NULL,
  `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_read`  (`is_read`),
  KEY `idx_date`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. TABLA: cart (carrito persistente por sesión/usuario)
-- ============================================================
CREATE TABLE IF NOT EXISTS `cart` (
  `id`         INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)       DEFAULT NULL,
  `session_id` VARCHAR(100)  DEFAULT NULL,
  `product_id` INT(11)       NOT NULL,
  `quantity`   INT(11)       NOT NULL DEFAULT 1,
  `added_at`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`    (`user_id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_cart_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. TABLA: settings (configuración del sitio)
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT       DEFAULT NULL,
  `updated_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECCIÓN B — ACTUALIZAR TABLAS EXISTENTES
-- Ejecuta estos ALTER solo si la tabla ya existía antes.
-- Cada comando usa IF NOT EXISTS para no romper nada.
-- ============================================================

-- B1. categories: añadir sort_order si no existe
ALTER TABLE `categories`
  MODIFY COLUMN `name`        VARCHAR(80)  NOT NULL,
  MODIFY COLUMN `description` TEXT         DEFAULT NULL,
  MODIFY COLUMN `image`       VARCHAR(255) DEFAULT NULL;

-- Añadir sort_order solo si no existe (procedimiento seguro)
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'categories'
    AND COLUMN_NAME  = 'sort_order'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `categories` ADD COLUMN `sort_order` INT(11) DEFAULT 0 AFTER `image`',
  'SELECT "sort_order ya existe en categories"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- B2. products: añadir columnas opcionales si no existen
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='discount_price');
SET @sql = IF(@col=0,
  'ALTER TABLE `products` ADD COLUMN `discount_price` DECIMAL(10,2) DEFAULT NULL AFTER `price`',
  'SELECT "discount_price ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='is_featured');
SET @sql = IF(@col=0,
  'ALTER TABLE `products` ADD COLUMN `is_featured` TINYINT(1) DEFAULT 0 AFTER `stock`',
  'SELECT "is_featured ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='sort_order');
SET @sql = IF(@col=0,
  'ALTER TABLE `products` ADD COLUMN `sort_order` INT(11) DEFAULT 0',
  'SELECT "sort_order ya existe en products"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- B3. orders: añadir columnas si no existen
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='order_number');
SET @sql = IF(@col=0,
  'ALTER TABLE `orders` ADD COLUMN `order_number` VARCHAR(50) NOT NULL DEFAULT "" AFTER `id`',
  'SELECT "order_number ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='delivery_fee');
SET @sql = IF(@col=0,
  'ALTER TABLE `orders` ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0.00',
  'SELECT "delivery_fee ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='payment_status');
SET @sql = IF(@col=0,
  'ALTER TABLE `orders` ADD COLUMN `payment_status` ENUM(''pending'',''paid'',''failed'',''refunded'') DEFAULT ''pending''',
  'SELECT "payment_status ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- B4. users: añadir columnas si no existen
SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='last_login');
SET @sql = IF(@col=0,
  'ALTER TABLE `users` ADD COLUMN `last_login` DATETIME DEFAULT NULL',
  'SELECT "last_login ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='avatar');
SET @sql = IF(@col=0,
  'ALTER TABLE `users` ADD COLUMN `avatar` VARCHAR(255) DEFAULT NULL AFTER `role`',
  'SELECT "avatar ya existe"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- SECCIÓN C — DATOS DE EJEMPLO (datos semilla)
-- Solo se insertan si las tablas están vacías.
-- ============================================================

-- C1. Usuario administrador (password: admin123)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`)
SELECT 'Administrador', 'admin@quickorder.com',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       '+34 600 000 000', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'admin@quickorder.com');

-- C2. Usuario cliente de prueba (password: cliente123)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`)
SELECT 'María López', 'cliente@example.com',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       '+34 611 222 333', 'customer'
WHERE NOT EXISTS (SELECT 1 FROM `users` WHERE `email` = 'cliente@example.com');

-- C3. Categorías de ejemplo
INSERT INTO `categories` (`name`, `description`, `sort_order`)
SELECT 'Pizzas', 'Pizzas artesanales elaboradas en horno de leña', 1
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Pizzas');

INSERT INTO `categories` (`name`, `description`, `sort_order`)
SELECT 'Ensaladas', 'Ensaladas frescas con ingredientes de temporada', 2
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Ensaladas');

INSERT INTO `categories` (`name`, `description`, `sort_order`)
SELECT 'Hamburguesas', 'Hamburguesas premium con pan artesano', 3
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Hamburguesas');

INSERT INTO `categories` (`name`, `description`, `sort_order`)
SELECT 'Postres', 'Postres caseros y tartas del día', 4
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Postres');

INSERT INTO `categories` (`name`, `description`, `sort_order`)
SELECT 'Bebidas', 'Refrescos, zumos naturales y agua', 5
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Bebidas');

INSERT INTO `categories` (`name`, `description`, `sort_order`)
SELECT 'Pastas', 'Pastas frescas elaboradas cada día', 6
WHERE NOT EXISTS (SELECT 1 FROM `categories` WHERE `name` = 'Pastas');

-- C4. Productos de ejemplo
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Pizzas' LIMIT 1),
       'Pizza Margarita',
       'Pizza clásica con salsa de tomate, mozzarella fresca y albahaca',
       12.99, NULL, 1, 50
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Pizza Margarita');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Pizzas' LIMIT 1),
       'Pizza Pepperoni',
       'Pizza con salsa de tomate, mozzarella y abundante pepperoni',
       14.99, 12.99, 1, 50
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Pizza Pepperoni');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Pizzas' LIMIT 1),
       'Pizza Cuatro Quesos',
       'Mozzarella, gorgonzola, parmesano y queso de cabra',
       15.99, NULL, 0, 50
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Pizza Cuatro Quesos');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Ensaladas' LIMIT 1),
       'Ensalada César',
       'Lechuga romana, pollo a la parrilla, parmesano, crutones y salsa César',
       9.99, NULL, 1, 30
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Ensalada César');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Ensaladas' LIMIT 1),
       'Ensalada Griega',
       'Tomate, pepino, aceitunas negras, cebolla morada y queso feta',
       8.99, NULL, 0, 30
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Ensalada Griega');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Hamburguesas' LIMIT 1),
       'Burger Clásica',
       'Carne de vacuno 200g, lechuga, tomate, cebolla y salsa especial',
       11.99, NULL, 1, 40
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Burger Clásica');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Hamburguesas' LIMIT 1),
       'Burger BBQ Bacon',
       'Doble carne, bacon crujiente, queso cheddar y salsa barbacoa',
       13.99, 11.99, 1, 40
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Burger BBQ Bacon');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Postres' LIMIT 1),
       'Tarta de Chocolate',
       'Tarta húmeda de chocolate con cobertura de ganache',
       5.99, NULL, 0, 20
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Tarta de Chocolate');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Postres' LIMIT 1),
       'Tiramisú Casero',
       'Tiramisú tradicional con café espresso y mascarpone',
       5.49, NULL, 1, 20
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Tiramisú Casero');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Bebidas' LIMIT 1),
       'Refresco (33cl)',
       'Coca-Cola, Fanta naranja, Fanta limón o agua con gas',
       2.50, NULL, 0, 100
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Refresco (33cl)');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Bebidas' LIMIT 1),
       'Zumo Natural',
       'Zumo recién exprimido de naranja, limón o mixto',
       3.50, NULL, 0, 50
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Zumo Natural');

INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `discount_price`, `is_featured`, `stock`)
SELECT (SELECT id FROM categories WHERE name='Pastas' LIMIT 1),
       'Spaghetti Carbonara',
       'Spaghetti con panceta, huevo, parmesano y pimienta negra',
       10.99, NULL, 1, 30
WHERE NOT EXISTS (SELECT 1 FROM `products` WHERE `name` = 'Spaghetti Carbonara');

-- C5. Configuración del sitio
INSERT INTO `settings` (`setting_key`, `setting_value`)
VALUES
  ('site_name',         'QuickOrder'),
  ('site_email',        'info@quickorder.com'),
  ('site_phone',        '+34 900 123 456'),
  ('site_address',      'Calle Gran Vía, 1, 28013 Madrid'),
  ('currency_symbol',   '€'),
  ('delivery_fee',      '2.50'),
  ('min_order_amount',  '10.00'),
  ('delivery_time',     '30'),
  ('tax_rate',          '10'),
  ('opening_hours',     'Lun-Vie: 12:00-23:00 | Sáb-Dom: 11:00-00:00')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);


-- ============================================================
-- SECCIÓN D — VISTAS ÚTILES (opcionales)
-- ============================================================

-- Vista: resumen de pedidos con datos del usuario
CREATE OR REPLACE VIEW `v_orders_summary` AS
SELECT
  o.id,
  o.order_number,
  o.status,
  o.payment_method,
  o.payment_status,
  o.total_amount,
  o.created_at,
  u.name   AS customer_name,
  u.email  AS customer_email,
  u.phone  AS customer_phone
FROM `orders` o
LEFT JOIN `users` u ON o.user_id = u.id;

-- Vista: productos con nombre de categoría
CREATE OR REPLACE VIEW `v_products_full` AS
SELECT
  p.*,
  c.name AS category_name
FROM `products` p
LEFT JOIN `categories` c ON p.category_id = c.id;

-- Vista: estadísticas del dashboard admin
CREATE OR REPLACE VIEW `v_dashboard_stats` AS
SELECT
  (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE())          AS orders_today,
  (SELECT COUNT(*) FROM orders WHERE status = 'pending')                     AS orders_pending,
  (SELECT COUNT(*) FROM orders WHERE status = 'delivering')                  AS orders_delivering,
  (SELECT COALESCE(SUM(total_amount),0) FROM orders
   WHERE payment_status='paid' AND DATE(created_at)=CURDATE())               AS revenue_today,
  (SELECT COUNT(*) FROM users WHERE role='customer')                          AS total_customers,
  (SELECT COUNT(*) FROM reservations WHERE reservation_date = CURDATE())     AS reservations_today,
  (SELECT COUNT(*) FROM contact_messages WHERE is_read=0)                    AS unread_messages;


-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
-- Credenciales de acceso de prueba:
--   Admin   → admin@quickorder.com   / admin123
--   Cliente → cliente@example.com    / cliente123
-- (hash bcrypt de "password" de Laravel/PHP por defecto)
-- IMPORTANTE: Cambia las contraseñas en producción.
-- ============================================================
