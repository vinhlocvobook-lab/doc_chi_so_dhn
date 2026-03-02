<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class History
{
    // Build shared WHERE clause from filters
    private static function applyFilters(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters['nam'])) {
            $sql .= " AND nam = ?";
            $params[] = $filters['nam'];
        }
        if (!empty($filters['thang'])) {
            $sql .= " AND thang = ?";
            $params[] = $filters['thang'];
        }
        if (!empty($filters['loaiDongHo'])) {
            $sql .= " AND loaiDongHo = ?";
            $params[] = $filters['loaiDongHo'];
        }
        if (!empty($filters['loaiDongHo_new'])) {
            if ($filters['loaiDongHo_new'] === '__NULL__') {
                $sql .= " AND (loaiDongHo_new IS NULL OR loaiDongHo_new = '')";
            } else {
                $sql .= " AND loaiDongHo_new = ?";
                $params[] = $filters['loaiDongHo_new'];
            }
        }
        if (!empty($filters['soDanhBo'])) {
            $sql .= " AND soDanhBo LIKE ?";
            $params[] = "%" . $filters['soDanhBo'] . "%";
        }
        if (isset($filters['coHinh']) && $filters['coHinh'] == 1) {
            $sql .= " AND linkHinhDongHo IS NOT NULL AND linkHinhDongHo != ''";
        }
    }

    public static function all($filters = [], $limit = 10, $offset = 0)
    {
        $db = Database::getInstance();
        $sql = "SELECT t.*, 
                m_new.last_prompt_txt as m_new_prompt, m_new.last_prompt_version as m_new_version, m_new.last_llm_models as m_new_llm,
                m_old.last_prompt_txt as m_old_prompt, m_old.last_prompt_version as m_old_version, m_old.last_llm_models as m_old_llm
                FROM chisodhn t
                LEFT JOIN loai_dhn m_new ON t.loaiDongHo_new = m_new.model_dong_ho
                LEFT JOIN loai_dhn m_old ON t.loaiDongHo = m_old.model_dong_ho
                WHERE 1=1";
        $params = [];
        self::applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        return array_map([self::class, 'processEffectiveConfigs'], $results);
    }

    public static function count($filters = [])
    {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*) FROM chisodhn WHERE 1=1";
        $params = [];
        self::applyFilters($sql, $params, $filters);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public static function findById($id)
    {
        $db = Database::getInstance();
        $sql = "SELECT t.*, 
                m_new.last_prompt_txt as m_new_prompt, m_new.last_prompt_version as m_new_version, m_new.last_llm_models as m_new_llm,
                m_old.last_prompt_txt as m_old_prompt, m_old.last_prompt_version as m_old_version, m_old.last_llm_models as m_old_llm
                FROM chisodhn t
                LEFT JOIN loai_dhn m_new ON t.loaiDongHo_new = m_new.model_dong_ho
                LEFT JOIN loai_dhn m_old ON t.loaiDongHo = m_old.model_dong_ho
                WHERE t.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ? self::processEffectiveConfigs($result) : null;
    }

    private static function processEffectiveConfigs(array $item): array
    {
        $item['eff_prompt_txt'] = $item['last_prompt_txt'] ?: ($item['m_new_prompt'] ?: $item['m_old_prompt']);
        $item['eff_prompt_version'] = $item['last_prompt_version'] ?: ($item['m_new_version'] ?: $item['m_old_version']);

        $llm = $item['last_llm_models'];
        if (empty($llm)) {
            $llmSource = $item['m_new_llm'] ?: $item['m_old_llm'];
            if (!empty($llmSource)) {
                $decoded = json_decode($llmSource, true);
                if (is_array($decoded) && !empty($decoded)) {
                    usort($decoded, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));
                    $llm = $decoded[0]['model_name'] ?? '';
                }
            }
        }
        $item['eff_llm_models'] = $llm;
        return $item;
    }

    /**
     * Update loaiDongHo_new for a single record
     */
    public static function updateMeterType(int $id, ?string $loaiDongHo_new): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE chisodhn SET loaiDongHo_new = ? WHERE id = ?");
        return $stmt->execute([$loaiDongHo_new ?: null, $id]);
    }

    /**
     * Bulk update loaiDongHo_new for all records matching a soDanhBo
     */
    public static function bulkUpdateMeterType(string $soDanhBo, ?string $loaiDongHo_new): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE chisodhn SET loaiDongHo_new = ? WHERE soDanhBo = ?");
        $stmt->execute([$loaiDongHo_new ?: null, $soDanhBo]);
        return $stmt->rowCount();
    }

    /**
     * Update prompt config info with support for multiple scopes
     */
    public static function updatePromptInfo(
        int $id,
        ?string $promptTxt,
        ?string $modelName,
        ?string $promptVersion,
        ?string $editUser,
        string $scope = 'id'
    ): int {
        $db = Database::getInstance();
        $record = self::findById($id);
        if (!$record)
            return 0;

        $sql = "UPDATE chisodhn SET last_prompt_txt = ?, last_llm_models = ?, last_prompt_version = ?, edit_user = ? WHERE ";
        $params = [$promptTxt, $modelName, $promptVersion, $editUser];

        switch ($scope) {
            case 'soDanhBo':
                $sql .= "soDanhBo = ?";
                $params[] = $record['soDanhBo'];
                break;
            case 'loaiDongHo':
                $sql .= "loaiDongHo = ?";
                $params[] = $record['loaiDongHo'];
                break;
            case 'loaiDongHo_new':
                $sql .= "loaiDongHo_new = ?";
                $params[] = $record['loaiDongHo_new'];
                break;
            default:
                $sql .= "id = ?";
                $params[] = $id;
                break;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function getDistinctLoaiDongHo(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT DISTINCT loaiDongHo FROM chisodhn WHERE loaiDongHo IS NOT NULL AND loaiDongHo != '' ORDER BY loaiDongHo ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
