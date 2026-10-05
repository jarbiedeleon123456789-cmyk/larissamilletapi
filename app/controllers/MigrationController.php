<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class MigrationController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        // Migrations run from the CLI (Docker entrypoint) or, over HTTP,
        // only when ?key= matches the MIGRATION_KEY environment variable.
        if (!(defined('IS_CLI') && IS_CLI)) {
            $key = (string) getenv('MIGRATION_KEY');
            $given = (string) ($_GET['key'] ?? '');
            if ($key === '' || !hash_equals($key, $given)) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden']);
                exit;
            }
            // Allow the library to run for this request.
            putenv('MIGRATION_ENABLED=true');
        }
        $this->call->library('migration');
    }

    public function create_migration($migration_class)
    {
        $this->migration->create_migration($migration_class);
    }

    public function migrate()
    {
        $this->migration->migrate();
    }

    public function rollback()
    {
        $this->migration->rollback();
    }

    public function rollback_all()
    {
        $this->migration->rollback_all();
    }

    public function refresh()
    {
        $this->migration->refresh();
    }

    public function status()
    {
        $this->migration->status();
    }
}
