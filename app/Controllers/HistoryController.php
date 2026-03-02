<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\History;
use App\Models\MeterType;

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
            'loaiDongHo_new' => $_GET['loaiDongHo_new'] ?? '',
            'soDanhBo' => $_GET['soDanhBo'] ?? '',
            'coHinh' => isset($_GET['filter']) ? (isset($_GET['coHinh']) ? 1 : 0) : 1,
        ];

        $history = History::all($filters, $limit, $offset);
        $totalItems = History::count($filters);
        $totalPages = ceil($totalItems / $limit);

        // Pass meter types for the inline edit dropdown
        $meterModel = new \App\Models\MeterType();
        $meterTypes = $meterModel->all();

        // Pass LLM models for AI read selector
        $pricingModel = new \App\Models\GeminiPricing();
        $llmModels = $pricingModel->all();

        // Pass distinct types for the search filter
        $distinctLoaiDongHo = History::getDistinctLoaiDongHo();

        $this->view('history/index', [
            'history' => $history,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'meterTypes' => $meterTypes,
            'llmModels' => $llmModels,
            'distinctLoaiDongHo' => $distinctLoaiDongHo,
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

    /**
     * POST /history/update-meter-type
     * Update loaiDongHo_new for one record, optionally bulk by soDanhBo
     */
    public function updateMeterType()
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $loaiDongHo_new = isset($_POST['loaiDongHo_new']) && $_POST['loaiDongHo_new'] !== '' ? $_POST['loaiDongHo_new'] : null;
        $bulk = !empty($_POST['bulk']); // apply to all records with same soDanhBo

        if (!$id) {
            return $this->json(['error' => 'Missing ID'], 400);
        }

        if ($bulk) {
            $record = History::findById($id);
            if (!$record) {
                return $this->json(['error' => 'Not found'], 404);
            }
            $rows = History::bulkUpdateMeterType($record['soDanhBo'], $loaiDongHo_new);
            return $this->json(['success' => true, 'updated' => $rows, 'message' => "Đã cập nhật $rows bản ghi cùng sổ danh bộ"]);
        }

        History::updateMeterType($id, $loaiDongHo_new);
        return $this->json(['success' => true, 'message' => 'Đã cập nhật loại đồng hồ']);
    }

    /**
     * POST /history/save-prompt-info
     */
    public function savePromptInfo()
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : null;
        $promptTxt = $_POST['promptText'] ?? null;
        $modelName = $_POST['modelName'] ?? null;
        $promptVersion = $_POST['promptVersion'] ?? null;
        $applyScope = $_POST['applyScope'] ?? 'id'; // default to only this record

        $editUser = $_SESSION['username'] ?? 'User_' . ($_SESSION['user_id'] ?? 'Unknown');

        if (!$id) {
            return $this->json(['error' => 'Missing ID'], 400);
        }

        $count = History::updatePromptInfo($id, $promptTxt, $modelName, $promptVersion, $editUser, $applyScope);

        $msg = 'Đã lưu cấu hình prompt';
        if ($applyScope !== 'id' && $count > 1) {
            $msg = "Đã cập nhật cấu hình prompt cho $count bản ghi";
        }

        return $this->json(['success' => true, 'message' => $msg, 'updated_count' => $count]);
    }
}
