<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\MeterType;

class MeterTypeController extends Controller
{
    private $meterModel;

    public function __construct()
    {
        // Fix Bug #1: was checking $_SESSION['user'], but AuthController sets $_SESSION['user_id']
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $this->meterModel = new MeterType();
    }

    public function index()
    {
        $meters = $this->meterModel->all();
        $this->view('meters/index', ['meters' => $meters]);
    }

    public function save()
    {
        $id = $_POST['id'] ?? null;

        // Fix Bug #2: was reading $_SESSION['user']['username'], correct key is $_SESSION['username']
        $currentUser = $_SESSION['username'] ?? 'system';

        $data = [
            'model_dong_ho' => $_POST['model_dong_ho'] ?: null,
            'loai_hien_thi' => $_POST['loai_hien_thi'] ?? '',
            'vung_hien_thi' => $_POST['vung_hien_thi'] ?? null,
            'phan_nguyen_digits' => (int) ($_POST['phan_nguyen_digits'] ?? 0),
            'phan_nguyen_color' => $_POST['phan_nguyen_color'] ?? null,
            'phan_nguyen_background' => $_POST['phan_nguyen_background'] ?? null,
            'phan_thap_phan_digits' => (int) ($_POST['phan_thap_phan_digits'] ?? 0),
            'phan_thap_phan_color' => $_POST['phan_thap_phan_color'] ?? null,
            'phan_thap_phan_background' => $_POST['phan_thap_phan_background'] ?? null,
            'quy_tac_lam_tron' => $_POST['quy_tac_lam_tron'] ?? null,
            'quy_tac_bo_sung' => $_POST['quy_tac_bo_sung'] ?? null,
            'la_mac_dinh' => isset($_POST['la_mac_dinh']) ? 1 : 0,
            'last_prompt_version' => $_POST['last_prompt_version'] ?? null,
            'last_prompt_txt' => $_POST['last_prompt_txt'] ?? null,
            'last_llm_models' => $_POST['last_llm_models'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'edit_user' => $currentUser,
        ];

        // Validate that last_llm_models is valid JSON if provided
        if (!empty($data['last_llm_models'])) {
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
