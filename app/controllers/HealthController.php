<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class HealthController extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');
        echo json_encode(['name' => 'Marrows API', 'status' => 'ok']);
    }
}
