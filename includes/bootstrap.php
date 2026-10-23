<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$configFile = __DIR__ . '/../config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration error: config.php is missing.');
}

require_once $configFile;
