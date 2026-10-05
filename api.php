<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
$from = (string)($_GET['from'] ?? '');
$to   = (string)($_GET['to'] ?? '');
$opts = routeOptions($from, $to);
if ($opts === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid district']);
    exit;
}
echo json_encode($opts, JSON_UNESCAPED_UNICODE);
