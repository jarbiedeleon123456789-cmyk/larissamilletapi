<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

class AuthController extends BaseApiController
{
    public function login()
    {
        $this->api->require_method('POST');
        $this->limit('login', 10, 60);

        $in = $this->json_body();
        $login = trim((string) ($in['login'] ?? $in['email'] ?? $in['username'] ?? ''));
        $password = (string) ($in['password'] ?? '');

        if ($login === '' || $password === '') {
            $this->fail('Enter your username or email and your password.', 422);
        }

        $stmt = $this->db->raw(
            'SELECT id, username, email, password, role, is_active FROM users WHERE email = ? OR username = ? LIMIT 1',
            [$login, $login]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->fail('Wrong username/email or password.', 401);
        }
        if ((int) $user['is_active'] !== 1) {
            $this->fail('This account is switched off. Ask the owner to reactivate it.', 403);
        }

        $tokens = $this->api->issue_tokens([
            'id'     => (int) $user['id'],
            'role'   => $user['role'],
            'scopes' => $this->role_scopes[$user['role']] ?? ['read'],
        ]);

        $this->ok([
            'message' => 'Signed in',
            'user'    => [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
            'tokens'  => $tokens,
        ]);
    }

    public function refresh()
    {
        $this->api->require_method('POST');
        $in = $this->json_body();
        $token = (string) ($in['refresh_token'] ?? '');
        if ($token === '') {
            $this->fail('refresh_token is required.', 422);
        }
        $this->api->refresh_access_token($token); // responds and exits
    }

    public function logout()
    {
        $this->api->require_method('POST');
        $in = $this->json_body();
        $token = (string) ($in['refresh_token'] ?? '');
        if ($token !== '') {
            $this->api->revoke_refresh_token($token);
        }
        $this->ok(['message' => 'Signed out']);
    }

    public function me()
    {
        $this->api->require_method('GET');
        $payload = $this->auth();
        $stmt = $this->db->raw('SELECT id, username, email, role FROM users WHERE id = ? LIMIT 1', [$payload['sub']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->ok(['user' => $user, 'scopes' => $payload['scopes'] ?? []]);
    }
}
