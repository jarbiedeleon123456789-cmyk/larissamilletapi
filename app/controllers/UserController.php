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

    public function update($id)
    {
        $this->api->require_method('PUT');
        $payload = $this->auth('read');
        $actor_id = (int) ($payload['sub'] ?? 0);
        $is_admin = ($payload['role'] ?? '') === 'admin';
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            $this->fail('Invalid user ID.', 422);
        }

        $existing = $this->db->raw(
            'SELECT id FROM users WHERE id = ? LIMIT 1',
            [$id]
        )->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $this->fail('User not found.', 404);
        }

        if (!$is_admin && $actor_id !== (int) $id) {
            $this->fail('You can only update your own account.', 403);
        }

        $in = $this->json_body();
        $fields = [];
        $values = [];
        $errors = [];

        if (array_key_exists('username', $in)) {
            if (!is_string($in['username'])) {
                $errors['username'] = 'Username must be a string.';
            } else {
                $username = trim($in['username']);
                if ($username === '' || $this->len($username) > 100) {
                    $errors['username'] = 'Username is required (max 100 characters).';
                } else {
                    $fields['username'] = $username;
                }
            }
        }

        if (array_key_exists('email', $in)) {
            if (!is_string($in['email'])) {
                $errors['email'] = 'Email must be a string.';
            } else {
                $email = trim($in['email']);
                if ($this->len($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'Enter a valid email (max 255 characters).';
                } else {
                    $fields['email'] = $email;
                }
            }
        }

        if (array_key_exists('role', $in)) {
            if (!$is_admin) {
                $errors['role'] = 'Only administrators can change user roles.';
            } elseif ((int) $id === $actor_id) {
                $errors['role'] = 'You cannot change your own role.';
            } elseif (!is_string($in['role']) || !in_array($in['role'], ['admin', 'moderator', 'user'], true)) {
                $errors['role'] = 'Role must be admin, moderator or user.';
            } else {
                $fields['role'] = $in['role'];
            }
        }

        if ($errors) {
            $this->fail('Please fix the highlighted fields.', 422, ['fields' => $errors]);
        }
        if (!$fields) {
            $this->fail('Provide a username, email, or role to update.', 422);
        }

        foreach (['username', 'email'] as $unique_field) {
            if (isset($fields[$unique_field])) {
                $conflict = $this->db->raw(
                    "SELECT id FROM users WHERE {$unique_field} = ? AND id <> ? LIMIT 1",
                    [$fields[$unique_field], $id]
                )->fetch(PDO::FETCH_ASSOC);
                if ($conflict) {
                    $this->fail("That {$unique_field} is already in use.", 409);
                }
            }
        }

        $sets = [];
        foreach ($fields as $column => $value) {
            $sets[] = "`{$column}` = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $this->db->raw(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $values
        );

        $user = $this->db->raw(
            'SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1',
            [$id]
        )->fetch(PDO::FETCH_ASSOC);
        $user['id'] = (int) $user['id'];
        $user['is_active'] = (int) $user['is_active'];

        $this->ok(['message' => 'User updated', 'user' => $user]);
    }

    public function destroy($id)
    {
        $this->api->require_method('DELETE');
        $payload = $this->auth('delete');
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            $this->fail('Invalid user ID.', 422);
        }
        if ((int) $id === (int) ($payload['sub'] ?? 0)) {
            $this->fail('You cannot delete your own account.', 403);
        }

        $exists = $this->db->raw(
            'SELECT id FROM users WHERE id = ? LIMIT 1',
            [$id]
        )->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            $this->fail('User not found.', 404);
        }

        $this->db->raw('DELETE FROM refresh_tokens WHERE user_id = ?', [$id]);
        $this->db->raw('DELETE FROM users WHERE id = ?', [$id]);
        $this->ok(['message' => 'User deleted']);
    }
}
