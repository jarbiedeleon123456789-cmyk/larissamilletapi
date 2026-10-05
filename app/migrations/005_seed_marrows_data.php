<?php

/**
 * Seeds the first staff account and the opening menu.
 * Staff login comes from ADMIN_USERNAME / ADMIN_EMAIL / ADMIN_PASSWORD.
 * Nothing is seeded for the account when ADMIN_PASSWORD is empty.
 */
class Seed_marrows_data {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
    }

    public function up()
    {
        $db = $this->_lava->db;

        // ---- Staff account
        $password = (string) getenv('ADMIN_PASSWORD');
        if ($password === '') {
            echo "ADMIN_PASSWORD is not set: staff account was NOT created." . PHP_EOL;
        } else {
            $username = trim((string) getenv('ADMIN_USERNAME')) ?: 'marrows';
            $email    = trim((string) getenv('ADMIN_EMAIL')) ?: 'staff@marrows.com';

            $exists = $db->raw('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1', [$username, $email])
                         ->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                $db->raw(
                    'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)',
                    [$username, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']
                );
                echo "Staff account '{$username}' created." . PHP_EOL;
            }
        }

        // ---- Opening menu (only when the table is empty)
        $count = (int) $db->raw('SELECT COUNT(*) FROM products')->fetchColumn();
        if ($count > 0) {
            return;
        }

        $menu = [
            ['Roasted Bone Marrow', 'Split canoe bones, parsley and caper salad, grilled sourdough, flaked salt.', 520.00, 30, 'To begin'],
            ['Scallop Crudo', 'Hokkaido scallop, calamansi, pickled shallot, gold leaf.', 640.00, 24, 'To begin'],
            ['Foie Gras Torchon', 'Pandan brioche, mango preserve, smoked sea salt.', 780.00, 18, 'To begin'],
            ['Beef Tartare', 'Hand-cut tenderloin, egg yolk, black garlic, crisp potato.', 560.00, 22, 'To begin'],
            ['Dry-Aged Ribeye', '45-day aged, bone marrow butter, charred onion, jus.', 2450.00, 14, 'Mains'],
            ['Braised Short Rib', 'Eight hours in red wine, celeriac puree, glazed carrot.', 1480.00, 16, 'Mains'],
            ['Pan-Seared Sea Bass', 'Saffron beurre blanc, fennel, crisp skin.', 1320.00, 15, 'Mains'],
            ['Truffle Tagliolini', 'Fresh egg pasta, aged parmesan, shaved black truffle.', 1180.00, 20, 'Mains'],
            ['Dark Chocolate Marquise', 'Valrhona 70%, sea salt, espresso cream.', 420.00, 25, 'Sweet'],
            ['Burnt Honey Panna Cotta', 'Roasted fig, thyme, candied walnut.', 380.00, 25, 'Sweet'],
            ['Gold Leaf Old Fashioned', 'Bourbon, demerara, orange bitters, 24k gold flake.', 540.00, 40, 'Drinks'],
            ['Marrow Martini', 'Gin, dry vermouth, olive brine, bone-marrow fat wash.', 520.00, 40, 'Drinks'],
        ];

        foreach ($menu as $m) {
            $db->raw(
                'INSERT INTO products (product_name, description, price, quantity, category) VALUES (?, ?, ?, ?, ?)',
                [$m[0], $m[1], $m[2], $m[3], $m[4]]
            );
        }
        echo "Opening menu seeded (" . count($menu) . " dishes)." . PHP_EOL;
    }

    public function down()
    {
        // Seed data is intentionally left in place on rollback.
    }
}
