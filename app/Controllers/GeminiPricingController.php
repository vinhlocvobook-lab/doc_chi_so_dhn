<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\GeminiPricing;

class GeminiPricingController extends Controller
{
    private $model;

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $this->model = new GeminiPricing();
    }

    public function index()
    {
        $pricings = $this->model->all();
        $this->view('pricing/index', ['pricings' => $pricings]);
    }

    public function save()
    {
        $id = $_POST['id'] ?? null;
        $data = [
            'model_name' => trim($_POST['model_name'] ?? ''),
            'input_price_low_context' => $_POST['input_price_low_context'] ?? null,
            'input_price_high_context' => $_POST['input_price_high_context'] ?? null,
            'output_price_low_context' => $_POST['output_price_low_context'] ?? null,
            'output_price_high_context' => $_POST['output_price_high_context'] ?? null,
            'context_threshold' => $_POST['context_threshold'] ?? 128000,
            'unit_amount' => $_POST['unit_amount'] ?? 1000000,
            'currency' => $_POST['currency'] ?? 'USD',
        ];

        if (empty($data['model_name'])) {
            return $this->json(['error' => 'Tên model không được để trống'], 400);
        }

        if ($id) {
            $this->model->update($id, $data);
            return $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
        } else {
            $this->model->create($data);
            return $this->json(['success' => true, 'message' => 'Thêm mới thành công']);
        }
    }

    public function delete()
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->model->delete($id);
            return $this->json(['success' => true]);
        }
        return $this->json(['error' => 'Invalid ID'], 400);
    }
}
