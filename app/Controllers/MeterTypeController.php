<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MeterType;
use App\Models\GeminiPricing;

class MeterTypeController extends Controller
{
    private $meterModel;

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $this->meterModel = new MeterType();
    }

    public function index()
    {
        $meters = $this->meterModel->all();
        $pricingModel = new GeminiPricing();
        $llmModels = $pricingModel->all();
        $this->view('meters/index', ['meters' => $meters, 'llmModels' => $llmModels]);
    }

    public function save()
    {
        $id = $_POST['id'] ?? null;
        $currentUser = $_SESSION['username'] ?? 'system';

        // Convert empty strings to null for optional fields
        $opt = fn($k) => isset($_POST[$k]) && $_POST[$k] !== '' ? $_POST[$k] : null;

        $data = [
            'model_dong_ho' => $opt('model_dong_ho'),
            'loai_hien_thi' => $_POST['loai_hien_thi'] ?? '',
            'vung_hien_thi' => $opt('vung_hien_thi'),
            'phan_nguyen_digits' => (int) ($_POST['phan_nguyen_digits'] ?? 0),
            'phan_nguyen_color' => $opt('phan_nguyen_color'),
            'phan_nguyen_background' => $opt('phan_nguyen_background'),
            'phan_thap_phan_digits' => (int) ($_POST['phan_thap_phan_digits'] ?? 0),
            'phan_thap_phan_color' => $opt('phan_thap_phan_color'),
            'phan_thap_phan_background' => $opt('phan_thap_phan_background'),
            'quy_tac_lam_tron' => $opt('quy_tac_lam_tron'),
            'quy_tac_bo_sung' => $opt('quy_tac_bo_sung'),
            'la_mac_dinh' => isset($_POST['la_mac_dinh']) ? 1 : 0,
            'last_prompt_version' => $opt('last_prompt_version'),
            'last_prompt_txt' => $opt('last_prompt_txt'),
            // '' → null: prevents CHECK json_valid('') constraint violation
            'last_llm_models' => $opt('last_llm_models'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'edit_user' => $currentUser,
        ];

        // Validate JSON if provided
        if ($data['last_llm_models'] !== null) {
            json_decode($data['last_llm_models']);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['error' => 'last_llm_models phải là JSON hợp lệ'], 400);
            }
        }

        if ($id) {
            $this->meterModel->update($id, $data);
            return $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
        } else {
            $data['create_user'] = $currentUser;
            $this->meterModel->create($data);
            return $this->json(['success' => true, 'message' => 'Thêm mới thành công']);
        }
    }

    public function delete()
    {
        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->meterModel->delete((int) $id);
            return $this->json(['success' => true]);
        }
        return $this->json(['error' => 'Invalid ID'], 400);
    }
}
