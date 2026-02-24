<?php
namespace App\Models;

require_once __DIR__ . '/../Core/Database.php';
use App\Core\Database;
use PDO;

class MeterType
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all($activeOnly = false)
    {
        $sql = "SELECT * FROM loai_dhn";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY la_mac_dinh DESC, model_dong_ho ASC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM loai_dhn WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        // If setting as default, unset other defaults
        if (isset($data['la_mac_dinh']) && $data['la_mac_dinh']) {
            $this->db->exec("UPDATE loai_dhn SET la_mac_dinh = 0");
        }

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO loai_dhn (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        if (isset($data['la_mac_dinh']) && $data['la_mac_dinh']) {
            $this->db->exec("UPDATE loai_dhn SET la_mac_dinh = 0 WHERE id != " . (int) $id);
        }

        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
        }

        $sql = "UPDATE loai_dhn SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $params = array_values($data);
        $params[] = $id;

        return $stmt->execute($params);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM loai_dhn WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
