<?php
// ★ここを変更してください / Change this password before deploying
define('ADMIN_PASSWORD', 'ar-admin-change-me');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

if ($data['password'] !== ADMIN_PASSWORD) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Sanitize and validate
$rf_keys     = ['minCF', 'beta', 'miss', 'warmup'];
$adjust_tabs = ['file', 'url', 'demo'];
$clean       = ['rf' => [], 'adjust' => []];

$rf = $data['rf'] ?? [];
foreach ($rf_keys as $k) {
    if (isset($rf[$k]) && is_numeric($rf[$k])) {
        $clean['rf'][$k] = (float)$rf[$k];
    }
}

$adj = $data['adjust'] ?? [];
foreach ($adjust_tabs as $tab) {
    $t = $adj[$tab] ?? null;
    if (is_array($t) && isset($t['h'], $t['y']) && is_numeric($t['h']) && is_numeric($t['y'])) {
        $clean['adjust'][$tab] = ['h' => (float)$t['h'], 'y' => (float)$t['y']];
    } else {
        $clean['adjust'][$tab] = null;
    }
}

$file = __DIR__ . '/settings.json';
if (file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write settings.json']);
    exit;
}

echo json_encode(['ok' => true]);
