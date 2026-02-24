<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class History
{
    public static function all($filters = [], $limit = 10, $offset = 0)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM chisodhn WHERE 1=1";
        $params = [];

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
        if (!empty($filters['soDanhBo'])) {
            $sql .= " AND soDanhBo LIKE ?";
            $params[] = "%" . $filters['soDanhBo'] . "%";
        }
        if (isset($filters['coHinh']) && $filters['coHinh'] == 1) {
            $sql .= " AND linkHinhDongHo IS NOT NULL AND linkHinhDongHo != ''";
        }

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
        if (!empty($filters['soDanhBo'])) {
            $sql .= " AND soDanhBo LIKE ?";
            $params[] = "%" . $filters['soDanhBo'] . "%";
        }
        if (isset($filters['coHinh']) && $filters['coHinh'] == 1) {
            $sql .= " AND linkHinhDongHo IS NOT NULL AND linkHinhDongHo != ''";
        }

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
}
