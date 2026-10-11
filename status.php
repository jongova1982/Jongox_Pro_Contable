<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
require __DIR__ . '/config.php';

try {
    db()->query('SELECT 1');
    http_response_code(200);
    echo json_encode(['connected' => true, 'time' => date(DATE_ATOM)]);
} catch (Throwable) {
    http_response_code(503);
    echo json_encode(['connected' => false]);
}
