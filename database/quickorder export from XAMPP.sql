-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-05-2026 a las 20:52:55
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `quickorder`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `calculate_order_total` (IN `p_order_id` INT, OUT `p_subtotal` DECIMAL(10,2), OUT `p_tax` DECIMAL(10,2), OUT `p_total` DECIMAL(10,2))   BEGIN
    DECLARE v_tax_rate DECIMAL(5,2);
    DECLARE v_delivery_fee DECIMAL(10,2);
    
    -- Obtener tasa de impuesto
    SELECT CAST(setting_value AS DECIMAL(5,2)) INTO v_tax_rate
    FROM settings WHERE setting_key = 'tax_rate';
    
    -- Calcular subtotal
    SELECT SUM(subtotal) INTO p_subtotal
    FROM order_items
    WHERE order_id = p_order_id;
    
    -- Calcular impuesto
    SET p_tax = ROUND(p_subtotal * (v_tax_rate / 100), 2);
    
    -- Obtener tarifa de entrega
    SELECT delivery_fee INTO v_delivery_fee
    FROM orders
    WHERE id = p_order_id;
    
    -- Calcular total
    SET p_total = p_subtotal + p_tax + COALESCE(v_delivery_fee, 0);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_reservation_availability` (IN `p_date` DATE, IN `p_max_tables` INT)   BEGIN
    SELECT 
        TIME_FORMAT(t.time_slot, '%H:%i') as time_slot,
        COALESCE(r.reserved_tables, 0) as reserved_tables,
        (p_max_tables - COALESCE(r.reserved_tables, 0)) as available_tables
    FROM (
        SELECT '11:00:00' as time_slot UNION ALL
        SELECT '11:30:00' UNION ALL
        SELECT '12:00:00' UNION ALL
        SELECT '12:30:00' UNION ALL
        SELECT '13:00:00' UNION ALL
        SELECT '13:30:00' UNION ALL
        SELECT '14:00:00' UNION ALL
        SELECT '14:30:00' UNION ALL
        SELECT '15:00:00' UNION ALL
        SELECT '20:00:00' UNION ALL
        SELECT '20:30:00' UNION ALL
        SELECT '21:00:00' UNION ALL
        SELECT '21:30:00' UNION ALL
        SELECT '22:00:00' UNION ALL
        SELECT '22:30:00' UNION ALL
        SELECT '23:00:00'
    ) t
    LEFT JOIN (
        SELECT 
            reservation_time,
            COUNT(*) as reserved_tables
        FROM reservations
        WHERE reservation_date = p_date
        AND status IN ('pending', 'confirmed')
        GROUP BY reservation_time
    ) r ON t.time_slot = r.reservation_time
    WHERE (p_max_tables - COALESCE(r.reserved_tables, 0)) > 0
    ORDER BY t.time_slot;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Pizzas', 'Pizzas artesanales elaboradas en horno de leña', NULL, 1, '2026-03-15 21:52:39', '2026-03-15 21:52:39'),
(2, 'Ensaladas', 'Ensaladas frescas con ingredientes de temporada', NULL, 2, '2026-03-15 21:52:39', '2026-03-15 21:52:39'),
(3, 'Hamburguesas', 'Hamburguesas premium con pan artesano', NULL, 3, '2026-03-15 21:52:39', '2026-03-15 21:52:39'),
(4, 'Postres', 'Postres caseros y tartas del día', NULL, 4, '2026-03-15 21:52:39', '2026-03-15 21:52:39'),
(5, 'Bebidas', 'Refrescos, zumos naturales y agua', NULL, 5, '2026-03-15 21:52:39', '2026-03-15 21:52:39'),
(6, 'Pastas', 'Pastas frescas elaboradas cada día', NULL, 6, '2026-03-15 21:52:39', '2026-03-15 21:52:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_replied` tinyint(1) DEFAULT 0,
  `reply_text` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `is_read`, `is_replied`, `reply_text`, `replied_at`, `created_at`) VALUES
(1, 'Cliente Prueba', 'cliente@example.com', '+34600000000', 'reserva', 'Esta mi reserva correcta?', 0, 0, NULL, NULL, '2026-04-19 21:34:19'),
(2, 'Cliente Prueba', 'cliente@example.com', '+34600000000', 'pedido', 'adfadfaadfasff', 0, 0, NULL, NULL, '2026-05-17 23:46:01');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','confirmed','preparing','ready','delivering','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `delivery_type` enum('delivery','pickup') DEFAULT 'delivery',
  `delivery_address` text DEFAULT NULL,
  `delivery_city` varchar(80) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `estimated_time` int(11) DEFAULT 30,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `subtotal`, `tax_amount`, `delivery_fee`, `discount_amount`, `status`, `payment_method`, `payment_status`, `delivery_type`, `delivery_address`, `delivery_city`, `customer_name`, `customer_email`, `customer_phone`, `notes`, `estimated_time`, `created_at`, `updated_at`) VALUES
(1, 4, 'ORD-20260406-2067', 40.67, 36.97, 3.70, 0.00, 0.00, 'pending', 'cash', 'pending', 'delivery', 'Melendez Valdes, 37, 2A', 'Badajoz', 'Cliente Prueba', 'cliente@example.com', '+34 600 000 001', '', 30, '2026-04-06 23:39:42', '2026-04-06 23:39:42'),
(2, 4, 'ORD-20260412-6742', 39.57, 35.97, 3.60, 0.00, 0.00, 'pending', 'cash', 'pending', 'delivery', 'Melendez Valdes, 37, 2A', 'Badajoz', 'Cliente Prueba', 'cliente@example.com', '+34 600 000 001', '', 30, '2026-04-12 17:08:13', '2026-04-12 17:08:13'),
(3, 4, 'ORD-20260419-3772', 50.56, 45.96, 4.60, 0.00, 0.00, 'pending', 'cash', 'pending', 'delivery', 'Melendez Valdes, 37, 2A', 'Badajoz', 'Cliente Prueba', 'cliente@example.com', '+34 600 000 001', 'Sin cebolla', 30, '2026-04-19 21:35:28', '2026-04-19 21:35:28'),
(4, 4, 'ORD-20260510-9861', 39.57, 35.97, 3.60, 0.00, 0.00, 'pending', 'cash', 'pending', 'delivery', 'Melendez Valdes, 37, 2A', 'Badajoz', 'Cliente Prueba', 'cliente@example.com', '+34 600 000 001', '', 30, '2026-05-10 19:24:52', '2026-05-10 19:24:52'),
(5, 4, 'ORD-20260517-1714', 17.04, 12.99, 1.55, 2.50, 0.00, 'pending', 'cash', 'pending', 'delivery', 'Melendez Valdes, 37, 2A', 'Badajoz', 'Cliente Prueba', 'cliente@example.com', '+34 600 000 001', '', 30, '2026-05-17 23:38:06', '2026-05-17 23:38:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `name`, `price`, `quantity`, `subtotal`, `notes`) VALUES
(1, 1, 1, 'Pizza Margarita', 12.99, 1, 12.99, NULL),
(2, 1, 7, 'Burger BBQ Bacon', 11.99, 2, 23.98, NULL),
(3, 2, 6, 'Burger Clásica', 11.99, 1, 11.99, NULL),
(4, 2, 2, 'Pizza Pepperoni', 12.99, 1, 12.99, NULL),
(5, 2, 12, 'Spaghetti Carbonara', 10.99, 1, 10.99, NULL),
(6, 3, 12, 'Spaghetti Carbonara', 10.99, 2, 21.98, NULL),
(7, 3, 7, 'Burger BBQ Bacon', 11.99, 1, 11.99, NULL),
(8, 3, 6, 'Burger Clásica', 11.99, 1, 11.99, NULL),
(9, 4, 12, 'Spaghetti Carbonara', 10.99, 1, 10.99, NULL),
(10, 4, 2, 'Pizza Pepperoni', 12.99, 1, 12.99, NULL),
(11, 4, 6, 'Burger Clásica', 11.99, 1, 11.99, NULL),
(12, 5, 1, 'Pizza Margarita', 12.99, 1, 12.99, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 100,
  `is_featured` tinyint(1) DEFAULT 0,
  `allergens` varchar(255) DEFAULT NULL,
  `calories` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `discount_price`, `image`, `stock`, `is_featured`, `allergens`, `calories`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, 'Pizza Margarita', 'Pizza clásica con salsa de tomate, mozzarella fresca y albahaca', 12.99, NULL, NULL, 50, 1, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(2, 1, 'Pizza Pepperoni', 'Pizza con salsa de tomate, mozzarella y abundante pepperoni', 14.99, 12.99, NULL, 50, 1, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(3, 1, 'Pizza Cuatro Quesos', 'Mozzarella, gorgonzola, parmesano y queso de cabra', 15.99, NULL, NULL, 50, 0, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(4, 2, 'Ensalada César', 'Lechuga romana, pollo a la parrilla, parmesano, crutones y salsa César', 9.99, NULL, NULL, 30, 1, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(5, 2, 'Ensalada Griega', 'Tomate, pepino, aceitunas negras, cebolla morada y queso feta', 8.99, NULL, NULL, 30, 0, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(6, 3, 'Burger Clásica', 'Carne de vacuno 200g, lechuga, tomate, cebolla y salsa especial', 11.99, NULL, NULL, 40, 1, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(7, 3, 'Burger BBQ Bacon', 'Doble carne, bacon crujiente, queso cheddar y salsa barbacoa', 13.99, 11.99, NULL, 40, 1, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(8, 4, 'Tarta de Chocolate', 'Tarta húmeda de chocolate con cobertura de ganache', 5.99, NULL, NULL, 20, 0, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(9, 4, 'Tiramisú Casero', 'Tiramisú tradicional con café espresso y mascarpone', 5.49, NULL, NULL, 20, 1, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(10, 5, 'Refresco (33cl)', 'Coca-Cola, Fanta naranja, Fanta limón o agua con gas', 2.50, NULL, NULL, 100, 0, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(11, 5, 'Zumo Natural', 'Zumo recién exprimido de naranja, limón o mixto', 3.50, NULL, NULL, 50, 0, NULL, NULL, 0, '2026-03-15 21:53:09', '2026-03-15 21:53:09'),
(12, 6, 'Spaghetti Carbonara', 'Spaghetti con panceta, huevo, parmesano y pimienta negra', 10.99, NULL, NULL, 30, 1, NULL, NULL, 0, '2026-03-15 21:53:10', '2026-03-15 21:53:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reservation_number` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_time` time NOT NULL,
  `guests` int(11) NOT NULL DEFAULT 2,
  `table_preference` varchar(50) DEFAULT NULL,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `reservation_number`, `name`, `email`, `phone`, `reservation_date`, `reservation_time`, `guests`, `table_preference`, `special_requests`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 'RES-20260419-0281', 'Cliente Prueba', 'cliente@example.com', '+34600000000', '2026-04-24', '12:00:00', 2, 'ventana', '', 'pending', NULL, '2026-04-19 21:33:46', '2026-04-19 21:33:46'),
(2, 4, 'RES-20260517-3508', 'Cliente Prueba', 'cliente@example.com', '+34600000000', '2026-05-24', '19:30:00', 2, '', '', 'pending', NULL, '2026-05-17 23:42:46', '2026-05-17 23:42:46');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'site_name', 'QuickOrder', '2026-03-15 21:53:24'),
(2, 'site_email', 'info@quickorder.com', '2026-03-15 21:53:24'),
(3, 'site_phone', '+34 900 123 456', '2026-03-15 21:53:24'),
(4, 'site_address', 'Calle Gran Vía, 1, 28013 Madrid', '2026-03-15 21:53:24'),
(5, 'currency_symbol', '€', '2026-03-15 21:53:24'),
(6, 'delivery_fee', '2.50', '2026-03-15 21:53:24'),
(7, 'min_order_amount', '10.00', '2026-03-15 21:53:24'),
(8, 'delivery_time', '30', '2026-03-15 21:53:24'),
(9, 'tax_rate', '10', '2026-03-15 21:53:24'),
(10, 'opening_hours', 'Lun-Vie: 12:00-23:00 | Sáb-Dom: 11:00-00:00', '2026-03-15 21:53:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `avatar` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `reset_token` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `postal_code`, `role`, `avatar`, `is_verified`, `reset_token`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'Administrador', 'admin@quickorder.com', '$2y$12$96BFuy5SrzKke1y0LJNPQO.coeQTB1Qd1GKc45YtLRqjiMXqusaZm', '+34 600 000 000', NULL, NULL, NULL, 'admin', NULL, 1, NULL, NULL, '2026-03-15 21:52:09', '2026-05-12 23:46:10'),
(4, 'Cliente Prueba', 'cliente@example.com', '$2y$12$uvMIF9e3CXvTnprzVvV7muQuSYdwfJ8W7FtpOfTVSUZ4eDpDZeB/C', '+34 600 000 001', 'Melendez Valdes, 37, 2A', 'Badajoz', '06001', 'customer', NULL, 1, NULL, NULL, '2026-03-17 00:50:37', '2026-05-17 23:38:06');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indices de la tabla `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_name` (`name`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indices de la tabla `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_price` (`price`);

--
-- Indices de la tabla `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reservation_number` (`reservation_number`),
  ADD KEY `idx_date` (`reservation_date`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indices de la tabla `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_key` (`setting_key`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
