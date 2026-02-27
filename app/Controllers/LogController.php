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
            'soDanhBo' => $_GET['soDanhBo'] ?? '',
            'loaiDongHo_new' => $_GET['loaiDongHo_new'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
        ];

        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortDir = $_GET['sort_dir'] ?? 'DESC';

        $logs = MeterReadingLog::all($filters, $limit, $offset, $sortBy, $sortDir);
        $totalItems = MeterReadingLog::count($filters);
        $totalPages = ceil($totalItems / $limit);
        $distinctModels = MeterReadingLog::distinctModels();
        $distinctMeterTypes = MeterReadingLog::distinctMeterTypes();

        $this->view('logs/index', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'filters' => $filters,
            'distinctModels' => $distinctModels,
            'distinctMeterTypes' => $distinctMeterTypes,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
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

    /**
     * GET /logs/image?path=...
     * Securely stream images from the private img_dhn directory.
     */
    public function image()
    {
        $path = $_GET['path'] ?? '';
        // Basic security: only allow paths starting with img_dhn/ and prevent directory traversal
        if (empty($path) || strpos($path, 'img_dhn/') !== 0 || strpos($path, '..') !== false) {
            http_response_code(403);
            exit('Forbidden');
        }

        $filepath = __DIR__ . '/../../' . $path;
        if (!file_exists($filepath)) {
            http_response_code(404);
            exit('Not Found');
        }

        $mime = mime_content_type($filepath) ?: 'image/jpeg';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($filepath));

        // Disable caching for sensitive data if needed, or allow it for performance
        header('Cache-Control: private, max-age=86400');

        readfile($filepath);
        exit;
    }

    /**
     * GET /logs/dashboard
     * View AI reading metrics and statistics.
     */
    public function dashboard()
    {
        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'model_name' => $_GET['model_name'] ?? '',
            'prompt_version' => $_GET['prompt_version'] ?? '',
            'image_type' => $_GET['image_type'] ?? '',
            'soDanhBo' => $_GET['soDanhBo'] ?? '',
            'loaiDongHo_new' => $_GET['loaiDongHo_new'] ?? '',
        ];

        // Overall metrics
        $overallMetrics = MeterReadingLog::getDashboardMetrics($filters)[0] ?? [];

        // Metrics by Model
        $modelMetrics = MeterReadingLog::getDashboardMetrics($filters, 'model_name');

        // Metrics by Meter Type
        $typeMetrics = MeterReadingLog::getDashboardMetrics($filters, 'loaiDongHo_new');

        // Distinct items for filters
        $distinctModels = MeterReadingLog::distinctModels();
        $distinctPromptVersions = MeterReadingLog::distinctPromptVersions();
        $distinctImageTypes = MeterReadingLog::distinctImageTypes();
        $distinctMeterTypes = MeterReadingLog::distinctMeterTypes();

        $this->view('logs/dashboard', [
            'filters' => $filters,
            'overall' => $overallMetrics,
            'byModel' => $modelMetrics,
            'byType' => $typeMetrics,
            'distinctModels' => $distinctModels,
            'distinctPromptVersions' => $distinctPromptVersions,
            'distinctImageTypes' => $distinctImageTypes,
            'distinctMeterTypes' => $distinctMeterTypes,
        ]);
    }
}
