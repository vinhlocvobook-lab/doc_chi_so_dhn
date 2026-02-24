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
        $sql = "SELECT * FROM chisodhn WHERE 1=1";
        $params = [];
        self::applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
        $stmt = $db->prepare("SELECT * FROM chisodhn WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
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
}
