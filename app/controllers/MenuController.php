<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

/**
 * Public, read-only menu for the website.
 */
class MenuController extends BaseApiController
{
    public function index()
    {
        $this->api->require_method('GET');
        $rows = $this->db->raw(
            'SELECT id, product_name, description, price, quantity, category FROM products ORDER BY category, product_name'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['price'] = (float) $r['price'];
            $r['quantity'] = (int) $r['quantity'];
        }
        $this->ok(['data' => $rows]);
    }
}
