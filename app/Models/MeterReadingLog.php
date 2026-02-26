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

    /**
     * Build WHERE clause from filters.
     */
    private static function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['model_name'])) {
            $where[] = "l.model_name = ?";
            $params[] = $filters['model_name'];
        }
        if (!empty($filters['trang_thai_api'])) {
            $where[] = "l.trang_thai_api = ?";
            $params[] = $filters['trang_thai_api'];
        }
        if (isset($filters['is_exact_match']) && $filters['is_exact_match'] !== '') {
            $where[] = "l.is_exact_match = ?";
            $params[] = (int) $filters['is_exact_match'];
        }
        if (isset($filters['is_rationality']) && $filters['is_rationality'] !== '') {
            $where[] = "l.is_rationality = ?";
            $params[] = (int) $filters['is_rationality'];
        }
        if (!empty($filters['muc_do_poc'])) {
            $where[] = "l.muc_do_poc = ?";
            $params[] = $filters['muc_do_poc'];
        }
        if (!empty($filters['muc_do_thuc_te'])) {
            $where[] = "l.muc_do_thuc_te = ?";
            $params[] = $filters['muc_do_thuc_te'];
        }
        if (!empty($filters['id_data'])) {
            $where[] = "l.id_data = ?";
            $params[] = (int) $filters['id_data'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "l.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = "l.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        return [$sql, $params];
    }

    /**
     * Get all logs with filters + pagination.
     */
    public static function all(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $db = Database::getInstance();
        [$whereSql, $params] = self::buildWhere($filters);

        $sql = "SELECT l.* FROM tn_meter_reading_log l{$whereSql} ORDER BY l.created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count logs matching filters.
     */
    public static function count(array $filters = []): int
    {
        $db = Database::getInstance();
        [$whereSql, $params] = self::buildWhere($filters);

        $sql = "SELECT COUNT(*) FROM tn_meter_reading_log l{$whereSql}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get distinct model_name values for filter dropdown.
     */
    public static function distinctModels(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT DISTINCT model_name FROM tn_meter_reading_log WHERE model_name IS NOT NULL ORDER BY model_name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
