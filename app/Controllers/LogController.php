<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MeterReadingLog;

class LogController extends Controller
{
    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    public function index()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'model_name' => $_GET['model_name'] ?? '',
            'trang_thai_api' => $_GET['trang_thai_api'] ?? '',
            'is_exact_match' => $_GET['is_exact_match'] ?? '',
            'is_rationality' => $_GET['is_rationality'] ?? '',
            'muc_do_poc' => $_GET['muc_do_poc'] ?? '',
            'muc_do_thuc_te' => $_GET['muc_do_thuc_te'] ?? '',
            'id_data' => $_GET['id_data'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $logs = MeterReadingLog::all($filters, $limit, $offset);
        $totalItems = MeterReadingLog::count($filters);
        $totalPages = ceil($totalItems / $limit);
        $distinctModels = MeterReadingLog::distinctModels();

        $this->view('logs/index', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'filters' => $filters,
            'distinctModels' => $distinctModels,
        ]);
    }

    /**
     * GET /logs/detail?id=...
     * Return full log record as JSON.
     */
    public function detail()
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            return $this->json(['error' => 'Missing ID'], 400);
        }
        $log = MeterReadingLog::findById($id);
        if (!$log) {
            return $this->json(['error' => 'Not found'], 404);
        }
        return $this->json($log);
    }
}
