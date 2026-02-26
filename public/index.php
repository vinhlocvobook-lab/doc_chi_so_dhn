<?php

require_once __DIR__ . '/../app/Core/DotEnv.php';
(new \App\Core\DotEnv(__DIR__ . '/../.env'))->load();

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

session_start();

$router = new \App\Core\Router();

// Define routes
$router->add('GET', '/', 'HistoryController@index');
$router->add('GET', '/login', 'AuthController@showLogin');
$router->add('POST', '/login', 'AuthController@login');
$router->add('GET', '/logout', 'AuthController@logout');
$router->add('GET', '/users', 'UserController@index');
$router->add('GET', '/profile', 'UserController@profile');
$router->add('GET', '/meters', 'MeterTypeController@index');
$router->add('POST', '/meters/save', 'MeterTypeController@save');
$router->add('POST', '/meters/delete', 'MeterTypeController@delete');
$router->add('GET', '/pricing', 'GeminiPricingController@index');
$router->add('POST', '/pricing/save', 'GeminiPricingController@save');
$router->add('POST', '/pricing/delete', 'GeminiPricingController@delete');
$router->add('GET', '/history/detail', 'HistoryController@detail');
$router->add('POST', '/history/update-meter-type', 'HistoryController@updateMeterType');
$router->add('GET', '/history/ai-read', 'AiReadController@stream');
$router->add('GET', '/history/ai-read-logs', 'AiReadController@logs');
$router->add('GET', '/logs', 'LogController@index');
$router->add('GET', '/logs/detail', 'LogController@detail');


$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->dispatch($method, $uri);
