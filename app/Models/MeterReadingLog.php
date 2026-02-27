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
            'img_dhn',
            'linkHinhDongHo',
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
        $stmt = $db->prepare("SELECT l.*, c.soDanhBo, c.loaiDongHo_new FROM tn_meter_reading_log l LEFT JOIN chisodhn c ON l.id_data = c.id WHERE l.id = ?");
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
        if (!empty($filters['prompt_version'])) {
            $where[] = "l.prompt_version = ?";
            $params[] = $filters['prompt_version'];
        }
        if (!empty($filters['image_type'])) {
            $where[] = "l.image_type = ?";
            $params[] = $filters['image_type'];
        }
        if (!empty($filters['soDanhBo'])) {
            $where[] = "c.soDanhBo LIKE ?";
            $params[] = '%' . trim($filters['soDanhBo']) . '%';
        }
        if (!empty($filters['loaiDongHo_new'])) {
            $where[] = "c.loaiDongHo_new = ?";
            $params[] = $filters['loaiDongHo_new'];
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
     * Get all logs with filters + pagination + sorting.
     */
    public static function all(array $filters = [], int $limit = 20, int $offset = 0, string $sortBy = 'created_at', string $sortDir = 'DESC'): array
    {
        $db = Database::getInstance();
        [$whereSql, $params] = self::buildWhere($filters);

        // Whitelist sortable columns
        $allowedSort = ['id', 'id_data', 'model_name', 'ai_chi_so_parse', 'human_chi_so', 'is_exact_match', 'is_rationality', 'sai_so', 'score_poc', 'score_thuc_te', 'chi_phi_vnd', 'thoi_gian_xu_ly', 'trang_thai_api', 'created_at'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        // Add l. prefix to sort column if not prefixed
        $sortCol = strpos($sortBy, '.') !== false ? $sortBy : "l.{$sortBy}";

        $sql = "SELECT l.*, c.soDanhBo, c.loaiDongHo_new FROM tn_meter_reading_log l LEFT JOIN chisodhn c ON l.id_data = c.id {$whereSql} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}";
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

        $sql = "SELECT COUNT(*) FROM tn_meter_reading_log l LEFT JOIN chisodhn c ON l.id_data = c.id {$whereSql}";
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

    /**
     * Get distinct loaiDongHo_new from chisodhn for filter dropdown
     */
    public static function distinctMeterTypes(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT DISTINCT loaiDongHo_new FROM chisodhn WHERE loaiDongHo_new IS NOT NULL AND loaiDongHo_new != '' ORDER BY loaiDongHo_new");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function distinctPromptVersions(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT DISTINCT prompt_version FROM tn_meter_reading_log WHERE prompt_version IS NOT NULL AND prompt_version != '' ORDER BY prompt_version");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function distinctImageTypes(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT DISTINCT image_type FROM tn_meter_reading_log WHERE image_type IS NOT NULL AND image_type != '' ORDER BY image_type");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get aggregated metrics for the AI dashboard.
     */
    public static function getDashboardMetrics(array $filters = [], string $groupBy = ''): array
    {
        $db = Database::getInstance();
        [$whereSql, $params] = self::buildWhere($filters);

        $groupClause = '';
        $selectGroup = '';

        if ($groupBy === 'model_name') {
            $selectGroup = 'l.model_name as group_name, ';
            $groupClause = ' GROUP BY l.model_name ORDER BY total_requests DESC';
        } elseif ($groupBy === 'loaiDongHo_new') {
            $selectGroup = 'c.loaiDongHo_new as group_name, ';
            $groupClause = ' GROUP BY c.loaiDongHo_new ORDER BY total_requests DESC';
        }

        $sql = "SELECT 
            {$selectGroup}
            COUNT(l.id) as total_requests,
            SUM(CASE WHEN l.trang_thai_api = 'loi_api' THEN 1 ELSE 0 END) as total_errors,
            SUM(CASE WHEN l.is_exact_match = 1 THEN 1 ELSE 0 END) as exact_matches,
            SUM(CASE WHEN l.is_rationality = 1 THEN 1 ELSE 0 END) as rational_reads,
            COUNT(l.score_poc) as count_poc,
            SUM(l.score_poc) as sum_poc,
            COUNT(l.score_thuc_te) as count_tt,
            SUM(l.score_thuc_te) as sum_tt,
            SUM(l.chi_phi_vnd) as total_cost_vnd,
            SUM(l.thoi_gian_xu_ly) as total_time_ms
            FROM tn_meter_reading_log l 
            LEFT JOIN chisodhn c ON l.id_data = c.id
            {$whereSql}
            {$groupClause}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate rates and averages for each row
        foreach ($results as &$row) {
            $total = (int) $row['total_requests'];
            $row['api_error_rate'] = $total > 0 ? round(($row['total_errors'] / $total) * 100, 2) : 0;
            $row['exact_match_rate'] = $total > 0 ? round(($row['exact_matches'] / $total) * 100, 2) : 0;
            $row['rationality_rate'] = $total > 0 ? round(($row['rational_reads'] / $total) * 100, 2) : 0;

            $countPoc = (int) $row['count_poc'];
            $row['avg_score_poc'] = $countPoc > 0 ? round($row['sum_poc'] / $countPoc, 2) : 0;

            $countTt = (int) $row['count_tt'];
            $row['avg_score_thuc_te'] = $countTt > 0 ? round($row['sum_tt'] / $countTt, 2) : 0;
        }

        return $results;
    }
}
