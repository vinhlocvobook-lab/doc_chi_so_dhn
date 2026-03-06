<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Gemini;
use App\Models\History;
use App\Models\MeterReadingLog;
use App\Models\GeminiPricing;
use App\Services\WaterMeterRationalityChecker;

class AiReadController extends Controller
{
    private const LOG_BASE = __DIR__ . '/../../log_doc_chi_so';

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    /**
     * GET /history/ai-read?id=...&model_names=...&prompt_text=...
     * Server-Sent Events endpoint for real-time progress.
     */
    public function stream()
    {
        // ── Setup SSE headers ──
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        // Increase execution time for long AI tasks
        set_time_limit(600); // 10 minutes max for multiple models
        ignore_user_abort(true);

        // Disable output buffering
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        @ini_set('zlib.output_compression', '0');
        while (ob_get_level())
            ob_end_flush();

        // ── Parse inputs ──
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $modelNamesRaw = $_GET['model_names'] ?? $_GET['model_name'] ?? ''; // Support both single and multiple
        $promptText = $_GET['prompt_text'] ?? '';

        if (!$id || !$modelNamesRaw || !$promptText) {
            $this->sse('error_event', ['message' => 'Thiếu tham số: id, model_names, prompt_text']);
            return;
        }

        $models = [];
        try {
            $decoded = json_decode($modelNamesRaw, true);
            if (is_array($decoded)) {
                $models = $decoded;
            } else {
                $models = [$modelNamesRaw];
            }
        } catch (\Exception $e) {
            $models = [$modelNamesRaw];
        }

        if (empty($models)) {
            $this->sse('error_event', ['message' => 'Danh sách mô hình trống']);
            return;
        }

        // ── Step 1: Fetch record ──
        $this->sse('progress', ['step' => 'fetch_record', 'label' => '📋 Đang lấy dữ liệu bản ghi...']);

        $record = History::findById($id);
        if (!$record) {
            $this->sse('error_event', ['message' => "Không tìm thấy bản ghi ID #{$id}"]);
            return;
        }
        if (empty($record['linkHinhDongHo'])) {
            $this->sse('error_event', ['message' => "Bản ghi ID #{$id} không có hình ảnh"]);
            return;
        }

        $this->sse('progress', [
            'step' => 'fetch_record_done',
            'label' => "✅ Bản ghi #{$id} — Danh bộ: {$record['soDanhBo']}",
            'data' => [
                'soDanhBo' => $record['soDanhBo'],
                'chiSoNuoc' => $record['chiSoNuoc'],
                'imageUrl' => $record['linkHinhDongHo'],
            ]
        ]);

        // ── Step 2: Download image ──
        $this->sse('progress', ['step' => 'downloading_image', 'label' => '⬇️ Đang tải hình ảnh đồng hồ...']);

        $imageUrl = $record['linkHinhDongHo'];
        $dateObj = new \DateTime();
        $datePath = $dateObj->format('Y/m/d');
        $imgDir = __DIR__ . '/../../img_dhn/' . $datePath;

        if (!is_dir($imgDir)) {
            mkdir($imgDir, 0777, true);
        }

        $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'meter_' . $id . '_' . time() . '.' . $ext;
        $targetPath = $imgDir . '/' . $filename;
        $relativeImgPath = 'img_dhn/' . $datePath . '/' . $filename;

        $imgData = @file_get_contents($imageUrl);
        if ($imgData === false) {
            $this->sse('error_event', ['message' => "Không thể tải hình ảnh từ: {$imageUrl}"]);
            return;
        }
        file_put_contents($targetPath, $imgData);

        $this->sse('progress', [
            'step' => 'image_downloaded',
            'label' => '✅ Đã tải hình ảnh (' . round(strlen($imgData) / 1024) . ' KB)'
        ]);

        // ── Step 3: Loop through models ──
        $finalSuccess = false;
        $lastResult = null;
        $lastModelUsed = '';
        $attemptsTotal = count($models);

        foreach ($models as $index => $currentModel) {
            $attemptNum = $index + 1;
            $this->sse('progress', [
                'step' => 'calling_api',
                'label' => "🤖 [Lượt {$attemptNum}/{$attemptsTotal}] Đang thử đọc bằng: {$currentModel}..."
            ]);

            $apiStartedAt = date('Y-m-d H:i:s');
            $startTime = microtime(true);
            $trangThai = 'thanh_cong';
            $thongBaoLoi = null;
            $result = null;

            try {
                $gemini = new Gemini();
                $result = $gemini->prompt_image($targetPath, $promptText, $currentModel);
            } catch (\Exception $e) {
                $trangThai = 'loi_api';
                $thongBaoLoi = $e->getMessage();
            }

            $apiCompletedAt = date('Y-m-d H:i:s');
            $thoiGianXuLy = (int) ((microtime(true) - $startTime) * 1000); // ms

            // Check if successful
            if ($result && empty($result['error'])) {
                // Potential success, check content
                $content = $result['data']['content'] ?? null;
                if ($content) {
                    // We found a result!
                    $finalSuccess = true;
                    $lastResult = $result;
                    $lastModelUsed = $currentModel;

                    // Log success and break loop
                    $this->processAndSaveResult(
                        $id,
                        $record,
                        $currentModel,
                        $promptText,
                        $result,
                        $thoiGianXuLy,
                        $apiStartedAt,
                        $apiCompletedAt,
                        $relativeImgPath,
                        $imageUrl
                    );
                    break;
                } else {
                    $trangThai = 'loi_parse';
                    $thongBaoLoi = 'AI trả về kết quả rỗng (không parse được JSON/nội dung)';
                }
            }

            // If we are here, this model failed
            $this->sse('progress', [
                'step' => 'model_failed',
                'label' => "⚠️ Mô hình {$currentModel} thất bại: " . ($thongBaoLoi ?: 'Lỗi không xác định')
            ]);

            // Always log attempt
            $logData = [
                'id_data' => $id,
                'model_name' => $currentModel,
                'prompt_text' => $promptText,
                'trang_thai_api' => $trangThai ?: 'loi_api',
                'thong_bao_loi' => $thongBaoLoi ?: ($result['message'] ?? 'Unknown error'),
                'api_started_at' => $apiStartedAt,
                'api_completed_at' => $apiCompletedAt,
                'thoi_gian_xu_ly' => $thoiGianXuLy,
                'img_dhn' => $relativeImgPath,
                'linkHinhDongHo' => $imageUrl,
                'image_type' => $record['image_type'] ?? null,
                'retry_count' => $index,
            ];
            try {
                MeterReadingLog::create($logData);
            } catch (\Exception $e) { /* ignore */
            }
            $this->writeLogFile($id, $record, $currentModel, $trangThai, $logData, null);

            // Continue to next model
        }

        if (!$finalSuccess) {
            $this->sse('error_event', ['message' => 'Tất cả các mô hình AI đều không đọc được hoặc gặp lỗi.']);
        }
    }

    /**
     * Parse result, evaluate, calculate scores, save to DB and send SSE done.
     */
    private function processAndSaveResult(
        int $id,
        array $record,
        string $modelName,
        string $promptText,
        array $result,
        int $thoiGianXuLy,
        string $apiStartedAt,
        string $apiCompletedAt,
        string $relativeImgPath,
        string $imageUrl
    ): void {
        $this->sse('progress', ['step' => 'parsing', 'label' => '📊 Đang phân tích kết quả...']);

        $data = $result['data'] ?? [];
        $content = $data['content'] ?? null;

        // Extract chi_so
        $aiChiSo = null;
        $aiChiSoParse = null;
        $coKyTuX = 0;
        $soKyTuX = 0;

        if ($content) {
            $aiChiSo = $content['chi_so'] ?? $content['chiSo'] ?? null;
            $phanNguyen = $content['chi_so_phan_nguyen'] ?? $content['phan_nguyen'] ?? $content['phanNguyen'] ?? null;

            if ($phanNguyen !== null && trim((string) $phanNguyen) !== '' && trim((string) $phanNguyen) !== 'N/A') {
                $cleaned = str_replace([' ', ','], '', (string) $phanNguyen);
                $numericOnly = preg_replace('/[^0-9]/', '', $cleaned);
                $aiChiSoParse = ($numericOnly !== '') ? (int) $numericOnly : null;
            } elseif ($aiChiSo !== null && trim((string) $aiChiSo) !== '' && trim((string) $aiChiSo) !== 'N/A') {
                $rawVal = (string) $aiChiSo;
                if (stripos($rawVal, 'x') !== false) {
                    $coKyTuX = 1;
                    $soKyTuX = substr_count(strtolower($rawVal), 'x');
                }
                $normalized = str_replace([' ', ','], ['', '.'], $rawVal);
                $parts = explode('.', $normalized);
                $integerStr = $parts[0];
                $numericOnly = preg_replace('/[^0-9]/', '', $integerStr);
                $aiChiSoParse = ($numericOnly !== '') ? (int) $numericOnly : null;
            }
        }

        // Cost
        $chiPhiUSD = $data['cost_usd'] ?? 0;
        $chiPhiVND = $data['cost_vnd'] ?? 0;

        // Compare with human_chi_so
        $humanChiSo = $record['chiSoNuoc'] ?? null;
        $isExactMatch = null;
        $saiSo = null;
        $saiSoTuyetDoi = null;
        if ($humanChiSo !== null && $aiChiSoParse !== null) {
            $isExactMatch = ($aiChiSoParse == $humanChiSo) ? 1 : 0;
            $saiSo = $aiChiSoParse - (int) $humanChiSo;
            $saiSoTuyetDoi = abs($saiSo);
        }

        // Char accuracy
        $charAccuracy = $this->tinhCharAccuracy($aiChiSoParse, $humanChiSo !== null ? (int) $humanChiSo : null);
        $charMatchCount = null;
        $charTotalCount = null;
        if ($aiChiSoParse !== null && $humanChiSo !== null) {
            $aiStr = (string) $aiChiSoParse;
            $humStr = (string) (int) $humanChiSo;
            $charTotalCount = max(strlen($aiStr), strlen($humStr));
            $charMatchCount = $charAccuracy !== null ? (int) round($charAccuracy * $charTotalCount) : null;
        }

        // Rationality + Scoring
        $chiSoTN = (float) ($record['chiSoNuocTN'] ?? 0);
        $luongTT = isset($record['luongNuocTieuThuThangTruoc']) && $record['luongNuocTieuThuThangTruoc'] !== null
            ? (float) $record['luongNuocTieuThuThangTruoc'] : null;
        $luongTB3T = isset($record['luongNuocTieuThuTrungBinh3ThangTruoc'])
            && $record['luongNuocTieuThuTrungBinh3ThangTruoc'] !== null
            && $record['luongNuocTieuThuTrungBinh3ThangTruoc'] !== ''
            ? (float) $record['luongNuocTieuThuTrungBinh3ThangTruoc'] : null;

        $danhGia = WaterMeterRationalityChecker::danhGia(
            $aiChiSoParse !== null ? (float) $aiChiSoParse : null,
            $chiSoTN,
            $luongTT,
            $luongTB3T
        );

        $scorePoc = WaterMeterRationalityChecker::tinhScorePoc(
            $aiChiSoParse !== null ? (float) $aiChiSoParse : null,
            $humanChiSo !== null ? (float) $humanChiSo : null,
            $charAccuracy
        );

        $aiDocDuoc = ($aiChiSoParse !== null);
        $scoreTT = WaterMeterRationalityChecker::tinhScoreThucTe(
            $danhGia,
            $luongTB3T,
            $soKyTuX,
            $aiDocDuoc
        );

        $this->sse('progress', [
            'step' => 'parsed',
            'label' => '✅ Đã phân tích: chỉ số = ' . ($aiChiSo ?? 'N/A')
                . ' | Score POC: ' . $scorePoc['score_poc'] . '/100'
                . ' | Score TT: ' . $scoreTT['score_thuc_te'] . '/100',
            'data' => [
                'ai_chi_so' => $aiChiSo,
                'ai_chi_so_parse' => $aiChiSoParse,
                'human_chi_so' => $humanChiSo,
                'is_exact_match' => $isExactMatch,
                'is_rationality' => $danhGia['is_rationality'],
                'score_poc' => $scorePoc['score_poc'],
                'muc_do_poc' => $scorePoc['muc_do_poc'],
                'score_thuc_te' => $scoreTT['score_thuc_te'],
                'muc_do_thuc_te' => $scoreTT['muc_do_thuc_te'],
                'content' => $content,
            ]
        ]);

        // Save to DB
        $this->sse('progress', ['step' => 'saving', 'label' => '💾 Đang lưu kết quả...']);

        $logData = [
            'id_data' => $id,
            'model_name' => $modelName,
            'prompt_version' => '1.0',
            'prompt_text' => $promptText,
            'ai_chi_so' => $aiChiSo,
            'ai_chi_so_parse' => $aiChiSoParse,
            'co_ky_tu_x' => $coKyTuX,
            'so_ky_tu_x' => $soKyTuX,
            'raw_response' => $data['raw_response'] ?? null,
            'prompt_tokens' => $data['prompt_tokens'] ?? 0,
            'output_tokens' => $data['output_tokens'] ?? 0,
            'thinking_tokens' => $data['thinking_tokens'] ?? 0,
            'chi_phi_usd' => $chiPhiUSD,
            'chi_phi_vnd' => $chiPhiVND,
            'thoi_gian_xu_ly' => $thoiGianXuLy,
            'api_started_at' => $apiStartedAt,
            'api_completed_at' => $apiCompletedAt,
            'trang_thai_api' => 'thanh_cong',
            'human_chi_so' => $humanChiSo,
            'is_exact_match' => $isExactMatch,
            'sai_so' => $saiSo,
            'sai_so_tuyet_doi' => $saiSoTuyetDoi,
            'char_match_count' => $charMatchCount,
            'char_total_count' => $charTotalCount,
            'char_accuracy_rate' => $charAccuracy,
            'is_rationality' => $danhGia['is_rationality'] !== null ? ($danhGia['is_rationality'] ? 1 : 0) : null,
            'luong_tieu_thu_ai' => $danhGia['luong_tieu_thu'],
            'nguong_hop_ly_min' => $danhGia['nguong_min'],
            'nguong_hop_ly_max' => $danhGia['nguong_max'],
            'ly_do_bat_hop_ly' => $danhGia['is_rationality'] === false ? $danhGia['ly_do'] : null,
            'image_type' => $record['image_type'] ?? null,
            'score_so_sat' => $scorePoc['score_so_sat'],
            'score_ky_tu_poc' => $scorePoc['score_ky_tu_poc'],
            'score_poc' => $scorePoc['score_poc'],
            'muc_do_poc' => $scorePoc['muc_do_poc'],
            'score_hop_ly' => $scoreTT['score_hop_ly'],
            'score_do_lech_tb' => $scoreTT['score_do_lech_tb'],
            'score_doc_duoc' => $scoreTT['score_doc_duoc'],
            'score_thuc_te' => $scoreTT['score_thuc_te'],
            'muc_do_thuc_te' => $scoreTT['muc_do_thuc_te'],
            'img_dhn' => $relativeImgPath,
            'linkHinhDongHo' => $imageUrl,
        ];

        $logId = 0;
        try {
            $logId = MeterReadingLog::create($logData);
        } catch (\Exception $e) {
            $this->sse('progress', ['step' => 'save_warning', 'label' => '⚠️ Lỗi DB: ' . $e->getMessage()]);
        }

        $this->writeLogFile($id, $record, $modelName, 'thanh_cong', $logData, $content, $scorePoc, $scoreTT);

        $this->sse('done', [
            'log_id' => $logId,
            'ai_chi_so' => $aiChiSo,
            'ai_chi_so_parse' => $aiChiSoParse,
            'human_chi_so' => $humanChiSo,
            'is_exact_match' => $isExactMatch,
            'sai_so' => $saiSo,
            'is_rationality' => $danhGia['is_rationality'],
            'ly_do_hop_ly' => $danhGia['ly_do'],
            'score_poc' => $scorePoc['score_poc'],
            'muc_do_poc' => $scorePoc['muc_do_poc'],
            'score_thuc_te' => $scoreTT['score_thuc_te'],
            'muc_do_thuc_te' => $scoreTT['muc_do_thuc_te'],
            'score_poc_details' => $scorePoc['chi_tiet'] ?? [],
            'score_thuc_te_details' => $scoreTT['chi_tiet'] ?? [],
            'content' => $content,
            'tokens' => [
                'prompt' => $data['prompt_tokens'] ?? 0,
                'output' => $data['output_tokens'] ?? 0,
                'thinking' => $data['thinking_tokens'] ?? 0,
            ],
            'cost' => [
                'usd' => round($chiPhiUSD, 8),
                'vnd' => round($chiPhiVND, 2),
            ],
            'thoi_gian_ms' => $thoiGianXuLy,
            'model_version' => $data['modelVersion'] ?? $modelName,
        ]);
    }

    public function logs()
    {
        $idData = isset($_GET['id_data']) ? (int) $_GET['id_data'] : 0;
        if (!$idData) {
            return $this->json(['error' => 'Missing id_data'], 400);
        }
        $logs = MeterReadingLog::findByDataId($idData);
        return $this->json($logs);
    }

    private function sse(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level())
            ob_flush();
        flush();
    }

    private function tinhCharAccuracy(?int $aiVal, ?int $humanVal): ?float
    {
        if ($aiVal === null || $humanVal === null)
            return null;
        $aiStr = (string) $aiVal;
        $humanStr = (string) $humanVal;
        $maxLen = max(strlen($aiStr), strlen($humanStr));
        if ($maxLen === 0)
            return 1.0;

        $aiStr = str_pad($aiStr, $maxLen, '0', STR_PAD_LEFT);
        $humanStr = str_pad($humanStr, $maxLen, '0', STR_PAD_LEFT);

        $match = 0;
        for ($i = 0; $i < $maxLen; $i++) {
            if ($aiStr[$i] === $humanStr[$i])
                $match++;
        }
        return (float) round($match / $maxLen, 4);
    }

    private function writeLogFile(
        int $id,
        array $record,
        string $model,
        string $status,
        array $logData,
        ?array $content,
        array $scorePoc = [],
        array $scoreTT = []
    ): void {
        try {
            $date = new \DateTime();
            $dir = self::LOG_BASE . '/' . $date->format('Y/m/d');
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $logFile = $dir . '/log.txt';
            $line = str_repeat('=', 60) . "\n";
            $entry = $line;
            $entry .= "[{$date->format('Y-m-d H:i:s')}] ID: {$id} | SDB: {$record['soDanhBo']} | Model: {$model} | Type: " . ($record['image_type'] ?? 'N/A') . "\n";
            $entry .= "Status: {$status} | Time: {$logData['thoi_gian_xu_ly']}ms\n";
            if ($content) {
                $entry .= "AI Chi So: " . ($logData['ai_chi_so'] ?? 'N/A') . " | Parse: " . ($logData['ai_chi_so_parse'] ?? 'N/A') . "\n";
                $entry .= "Human Chi So: " . ($logData['human_chi_so'] ?? 'N/A') . " | Match: " . ($logData['is_exact_match'] ?? '?') . "\n";
                if (!empty($scorePoc))
                    $entry .= "Score POC: {$scorePoc['score_poc']}/100 [{$scorePoc['muc_do_poc']}]\n";
                if (!empty($scoreTT))
                    $entry .= "Score TT : {$scoreTT['score_thuc_te']}/100 [{$scoreTT['muc_do_thuc_te']}]\n";
                $entry .= "Tokens: P={$logData['prompt_tokens']} O={$logData['output_tokens']} T={$logData['thinking_tokens']}\n";
                $entry .= "Cost: " . number_format($logData['chi_phi_vnd'] ?? 0, 6) . " VND\n";
                if (isset($content['giai_thich_chi_so']))
                    $entry .= "Giải thích: {$content['giai_thich_chi_so']}\n";
            } else {
                $entry .= "Error: " . ($logData['thong_bao_loi'] ?? 'Unknown') . "\n";
            }
            $entry .= $line . "\n";
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
        }
    }
}
