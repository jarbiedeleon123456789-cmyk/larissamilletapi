<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

class UserController extends BaseApiController
{
    public function index()
    {
        $this->api->require_method('GET');
        $payload = $this->auth('read');
        if (($payload['role'] ?? '') !== 'admin') {
            $this->fail('Only administrators can list users.', 403);
        }

        $rows = $this->db->raw(
            'SELECT id, username, email, role, is_active, created_at FROM users ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (int) $row['is_active'];
        }

        $this->ok(['data' => $rows, 'count' => count($rows)]);
    }

    public function store()
    {
        $this->api->require_method('POST');
        $this->limit('user_create', 5, 300);

        $in = $this->json_body();
        $username_input = $in['username'] ?? '';
        $email_input = $in['email'] ?? '';
        $password_input = $in['password'] ?? '';
        $username = is_string($username_input) ? trim($username_input) : '';
        $email = is_string($email_input) ? trim($email_input) : '';
        $password = is_string($password_input) ? $password_input : '';

        $errors = [];
        if ($username === '' || $this->len($username) > 100) {
            $errors['username'] = 'Username is required (max 100 characters).';
        }
        if ($this->len($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email (max 255 characters).';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if ($errors) {
            $this->fail('Please fix the highlighted fields.', 422, ['fields' => $errors]);
        }

        $exists = $this->db->raw(
            'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$username, $email]
        )->fetch(PDO::FETCH_ASSOC);
        if ($exists) {
            $this->fail('That username or email is already registered.', 409);
        }

        $this->db->raw(
            'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)',
            [$username, $email, password_hash($password, PASSWORD_DEFAULT), 'user']
        );

        $id = (int) $this->db->last_id();
        $user = $this->db->raw(
            'SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1',
            [$id]
        )->fetch(PDO::FETCH_ASSOC);
        $user['id'] = (int) $user['id'];
        $user['is_active'] = (int) $user['is_active'];

        $this->ok(['message' => 'User created', 'user' => $user], 201);
    }
}
