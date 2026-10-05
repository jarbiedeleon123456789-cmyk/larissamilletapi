-- Marrows API database setup for MySQL / Navicat.
-- Select the target database in Navicat before running this script.
-- This creates the schema and opening menu; it does not create a staff account.

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` INT NOT NULL,
    `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `migration_unique` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin','moderator','user') NOT NULL DEFAULT 'user',
    `is_active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username_unique` (`username`),
    KEY `email_idx` (`email`),
    KEY `role_idx` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `refresh_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token` TEXT NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `jti` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id_idx` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `product_name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `quantity` INT(11) NOT NULL DEFAULT 0,
    `category` VARCHAR(50) NOT NULL DEFAULT 'Mains',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `category_idx` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reservations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `guest_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `reserve_date` DATE NOT NULL,
    `reserve_time` TIME NOT NULL,
    `party_size` INT(11) NOT NULL DEFAULT 2,
    `note` TEXT DEFAULT NULL,
    `status` ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `reserve_date_idx` (`reserve_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed the opening menu only when products is empty; safe to re-run.
INSERT INTO `products` (`product_name`, `description`, `price`, `quantity`, `category`)
SELECT menu.`product_name`, menu.`description`, menu.`price`, menu.`quantity`, menu.`category`
FROM (
    SELECT 'Roasted Bone Marrow' AS `product_name`, 'Split canoe bones, parsley and caper salad, grilled sourdough, flaked salt.' AS `description`, 520.00 AS `price`, 30 AS `quantity`, 'To begin' AS `category`
    UNION ALL SELECT 'Scallop Crudo', 'Hokkaido scallop, calamansi, pickled shallot, gold leaf.', 640.00, 24, 'To begin'
    UNION ALL SELECT 'Foie Gras Torchon', 'Pandan brioche, mango preserve, smoked sea salt.', 780.00, 18, 'To begin'
    UNION ALL SELECT 'Beef Tartare', 'Hand-cut tenderloin, egg yolk, black garlic, crisp potato.', 560.00, 22, 'To begin'
    UNION ALL SELECT 'Dry-Aged Ribeye', '45-day aged, bone marrow butter, charred onion, jus.', 2450.00, 14, 'Mains'
    UNION ALL SELECT 'Braised Short Rib', 'Eight hours in red wine, celeriac puree, glazed carrot.', 1480.00, 16, 'Mains'
    UNION ALL SELECT 'Pan-Seared Sea Bass', 'Saffron beurre blanc, fennel, crisp skin.', 1320.00, 15, 'Mains'
    UNION ALL SELECT 'Truffle Tagliolini', 'Fresh egg pasta, aged parmesan, shaved black truffle.', 1180.00, 20, 'Mains'
    UNION ALL SELECT 'Dark Chocolate Marquise', 'Valrhona 70%, sea salt, espresso cream.', 420.00, 25, 'Sweet'
    UNION ALL SELECT 'Burnt Honey Panna Cotta', 'Roasted fig, thyme, candied walnut.', 380.00, 25, 'Sweet'
    UNION ALL SELECT 'Gold Leaf Old Fashioned', 'Bourbon, demerara, orange bitters, 24k gold flake.', 540.00, 40, 'Drinks'
    UNION ALL SELECT 'Marrow Martini', 'Gin, dry vermouth, olive brine, bone-marrow fat wash.', 520.00, 40, 'Drinks'
) AS menu
WHERE NOT EXISTS (SELECT 1 FROM `products` LIMIT 1);
