<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

/**
 * Product (menu item) CRUD. Every route needs a valid Bearer token.
 */
class ProductController extends BaseApiController
{
    private function validate(array $in, bool $partial = false): array
    {
        $errors = [];
        $out = [];

        if (!$partial || array_key_exists('product_name', $in)) {
            $name = trim((string) ($in['product_name'] ?? ''));
            if ($name === '' || $this->len($name) > 100) {
                $errors['product_name'] = 'Name is required (max 100 characters).';
            }
            $out['product_name'] = $name;
        }
        if (!$partial || array_key_exists('description', $in)) {
            $out['description'] = trim((string) ($in['description'] ?? ''));
        }
        if (!$partial || array_key_exists('price', $in)) {
            $price = $in['price'] ?? null;
            if (!is_numeric($price) || $price < 0 || $price > 99999999.99) {
                $errors['price'] = 'Price must be a number from 0 up.';
            }
            $out['price'] = is_numeric($price) ? round((float) $price, 2) : 0;
        }
        if (!$partial || array_key_exists('quantity', $in)) {
            $qty = $in['quantity'] ?? null;
            if (!is_numeric($qty) || (int) $qty < 0 || (int) $qty != $qty) {
                $errors['quantity'] = 'Quantity must be a whole number from 0 up.';
            }
            $out['quantity'] = is_numeric($qty) ? (int) $qty : 0;
        }
        if (!$partial || array_key_exists('category', $in)) {
            $cat = trim((string) ($in['category'] ?? 'Mains'));
            if ($cat === '' || $this->len($cat) > 50) {
                $errors['category'] = 'Category is required (max 50 characters).';
            }
            $out['category'] = $cat;
        }

        if ($errors) {
            $this->fail('Please fix the highlighted fields.', 422, ['fields' => $errors]);
        }
        return $out;
    }

    private function find(int $id): ?array
    {
        $row = $this->db->raw(
            'SELECT id, product_name, description, price, quantity, category, created_at FROM products WHERE id = ? LIMIT 1',
            [$id]
        )->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['id'] = (int) $row['id'];
        $row['price'] = (float) $row['price'];
        $row['quantity'] = (int) $row['quantity'];
        return $row;
    }

    public function index()
    {
        $this->api->require_method('GET');
        $this->auth('read');
        $rows = $this->db->raw(
            'SELECT id, product_name, description, price, quantity, category, created_at FROM products ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['id'] = (int) $r['id'];
            $r['price'] = (float) $r['price'];
            $r['quantity'] = (int) $r['quantity'];
        }
        $this->ok(['data' => $rows, 'count' => count($rows)]);
    }

    public function show($id)
    {
        $this->api->require_method('GET');
        $this->auth('read');
        $row = $this->find((int) $id);
        if (!$row) {
            $this->fail('Dish not found.', 404);
        }
        $this->ok(['data' => $row]);
    }

    public function store()
    {
        $this->api->require_method('POST');
        $this->auth('write');
        $d = $this->validate($this->json_body());
        $this->db->raw(
            'INSERT INTO products (product_name, description, price, quantity, category) VALUES (?, ?, ?, ?, ?)',
            [$d['product_name'], $d['description'], $d['price'], $d['quantity'], $d['category']]
        );
        $id = (int) $this->db->last_id();
        $this->ok(['message' => 'Dish added', 'data' => $this->find($id)], 201);
    }

    public function update($id)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'PUT';
        $this->auth('write');
        $id = (int) $id;
        if (!$this->find($id)) {
            $this->fail('Dish not found.', 404);
        }
        $d = $this->validate($this->json_body(), $method === 'PATCH');
        if (!$d) {
            $this->fail('Nothing to update.', 422);
        }
        $sets = [];
        $vals = [];
        foreach ($d as $col => $val) {
            $sets[] = "`$col` = ?";
            $vals[] = $val;
        }
        $vals[] = $id;
        $this->db->raw('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = ?', $vals);
        $this->ok(['message' => 'Dish updated', 'data' => $this->find($id)]);
    }

    public function destroy($id)
    {
        $this->api->require_method('DELETE');
        $this->auth('delete');
        $id = (int) $id;
        if (!$this->find($id)) {
            $this->fail('Dish not found.', 404);
        }
        $this->db->raw('DELETE FROM products WHERE id = ?', [$id]);
        $this->ok(['message' => 'Dish deleted']);
    }
}
