<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Shared helpers for every JSON endpoint.
 */
class BaseApiController extends Controller
{
    /** @var array<string,array> role => scopes (mirrors the Api library) */
    protected $role_scopes = [
        'admin'     => ['read', 'write', 'delete'],
        'moderator' => ['read'],
        'user'      => ['read'],
    ];

    public function __construct()
    {
        parent::__construct();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        // The Api library needs the database for token storage and user checks.
        $this->call->database();
        $this->call->library('api');
    }

    /**
     * Raw JSON body (not HTML-escaped; Vue escapes on output).
     */
    protected function json_body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode((string) $raw, true);
        if (is_array($data)) {
            return $data;
        }
        return is_array($_POST) ? $_POST : [];
    }

    /** Character length that works with or without mbstring. */
    protected function len(string $s): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($s, 'UTF-8');
        }
        $n = preg_match_all('/./us', $s);
        return $n === false ? strlen($s) : $n;
    }

    protected function client_ip(): string
    {
        $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($fwd !== '') {
            return trim(explode(',', $fwd)[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    protected function limit(string $bucket, int $requests, int $seconds): void
    {
        try {
            $this->api->rate_limit($bucket . '_' . $this->client_ip(), $requests, $seconds);
        } catch (Throwable $e) {
            // Rate limiting must never take the API down.
        }
    }

    /**
     * Require a valid Bearer token, optionally with a scope.
     */
    protected function auth(?string $scope = null): array
    {
        $payload = $this->api->require_jwt();
        if ($scope !== null && !in_array($scope, $payload['scopes'] ?? [], true)) {
            $this->api->respond_error('Forbidden: your role cannot do this', 403);
        }
        return $payload;
    }

    protected function ok($data = [], int $code = 200): void
    {
        $this->api->respond($data, $code);
    }

    protected function fail(string $message, int $code = 400, array $extra = []): void
    {
        $this->api->respond(array_merge(['error' => $message, 'status' => $code], $extra), $code);
    }
}
