<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class MeterReadingLog
{
    /**
     * Insert a reading log record into tn_meter_reading_log.
     *
     * @param array $data  Associative array of column => value
     * @return int          Last insert ID
     */
    public static function create(array $data): int
    {
        $db = Database::getInstance();

        // Only keep columns that exist in the table
        $allowed = [
            'id_data',
            'model_name',
            'prompt_version',
            'prompt_text',
            'ai_chi_so',
            'ai_chi_so_parse',
            'co_ky_tu_x',
            'so_ky_tu_x',
            'raw_response',
            'prompt_tokens',
            'output_tokens',
            'thinking_tokens',
            'chi_phi_usd',
            'chi_phi_vnd',
            'thoi_gian_xu_ly',
            'api_started_at',
            'api_completed_at',
            'retry_count',
            'trang_thai_api',
            'thong_bao_loi',
            'human_chi_so',
            'is_exact_match',
            'sai_so',
            'sai_so_tuyet_doi',
            'loai_sai_so',
            'char_match_count',
            'char_total_count',
            'char_accuracy_rate',
            'is_rationality',
            'luong_tieu_thu_ai',
            'nguong_hop_ly_min',
            'nguong_hop_ly_max',
            'ly_do_bat_hop_ly',
            'image_type',
            'is_accept',
            'is_accept_for_billing',
            'last_reviewer',
            'last_reviewed_at',
            'last_review_note',
            // Score POC (Giai đoạn 1)
            'score_so_sat',
            'score_ky_tu_poc',
            'score_poc',
            'muc_do_poc',
            // Score Thực tế (Giai đoạn 2)
            'score_hop_ly',
            'score_do_lech_tb',
            'score_doc_duoc',
            'score_thuc_te',
            'muc_do_thuc_te',
        ];

        $filtered = array_intersect_key($data, array_flip($allowed));
        $cols = array_keys($filtered);
        $placeholders = array_fill(0, count($cols), '?');

        $sql = "INSERT INTO tn_meter_reading_log (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($filtered));
        return (int) $db->lastInsertId();
    }

    /**
     * Find a log record by ID.
     */
    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tn_meter_reading_log WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find logs by data ID (chisodhn.id).
     */
    public static function findByDataId(int $idData): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tn_meter_reading_log WHERE id_data = ? ORDER BY created_at DESC");
        $stmt->execute([$idData]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
