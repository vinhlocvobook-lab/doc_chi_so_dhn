<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\History;

class HistoryController extends Controller
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
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'nam' => $_GET['nam'] ?? '',
            'thang' => $_GET['thang'] ?? '',
            'loaiDongHo' => $_GET['loaiDongHo'] ?? '',
            'soDanhBo' => $_GET['soDanhBo'] ?? '',
            'coHinh' => isset($_GET['filter']) ? (isset($_GET['coHinh']) ? 1 : 0) : 1
        ];

        $history = History::all($filters, $limit, $offset);
        $totalItems = History::count($filters);
        $totalPages = ceil($totalItems / $limit);

        $this->view('history/index', [
            'history' => $history,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters
        ]);
    }

    public function detail()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->json(['error' => 'Missing ID']);
            return;
        }

        $detail = History::findById($id);
        if (!$detail) {
            $this->json(['error' => 'Not found']);
            return;
        }

        $this->json($detail);
    }
}
