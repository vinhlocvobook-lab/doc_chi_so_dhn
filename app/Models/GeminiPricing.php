<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class GeminiPricing
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all()
    {
        $stmt = $this->db->query("SELECT * FROM gemini_pricing ORDER BY model_name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM gemini_pricing WHERE id = ?");
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByModel($modelName)
    {
        $stmt = $this->db->prepare("SELECT * FROM gemini_pricing WHERE model_name = ?");
        $stmt->execute([$modelName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO gemini_pricing 
            (model_name, input_price_low_context, input_price_high_context,
             output_price_low_context, output_price_high_context,
             context_threshold, unit_amount, currency)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['model_name'],
            $data['input_price_low_context'] ?: null,
            $data['input_price_high_context'] ?: null,
            $data['output_price_low_context'] ?: null,
            $data['output_price_high_context'] ?: null,
            (int) ($data['context_threshold'] ?? 128000),
            (int) ($data['unit_amount'] ?? 1000000),
            $data['currency'] ?? 'USD',
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $sql = "UPDATE gemini_pricing SET
            model_name = ?,
            input_price_low_context = ?,
            input_price_high_context = ?,
            output_price_low_context = ?,
            output_price_high_context = ?,
            context_threshold = ?,
            unit_amount = ?,
            currency = ?
            WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['model_name'],
            $data['input_price_low_context'] ?: null,
            $data['input_price_high_context'] ?: null,
            $data['output_price_low_context'] ?: null,
            $data['output_price_high_context'] ?: null,
            (int) ($data['context_threshold'] ?? 128000),
            (int) ($data['unit_amount'] ?? 1000000),
            $data['currency'] ?? 'USD',
            (int) $id,
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM gemini_pricing WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }
}
