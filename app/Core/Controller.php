<?php

namespace App\Core;

class Controller
{
    protected function view($name, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . "/../Views/$name.php";
        require_once __DIR__ . "/../Views/layout/main.php";
    }

    protected function json($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect($path)
    {
        header("Location: $path");
        exit;
    }
}
