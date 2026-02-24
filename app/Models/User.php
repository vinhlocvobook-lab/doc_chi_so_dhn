<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByUsername($username)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public static function findById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function all()
    {
        $db = Database::getInstance();
        return $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
    }
}
