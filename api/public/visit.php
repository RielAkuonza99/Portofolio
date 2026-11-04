<?php
declare(strict_types=1);
define('API_JSON', true);
require __DIR__ . '/../../includes/security.php';
require __DIR__ . '/../../includes/bootstrap.php';
send_security_headers();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') json_out(['error' => 'method_not_allowed'], 405);
$input = json_decode((string)file_get_contents('php://input'), true);
$path = is_array($input) && isset($input['path']) ? trim((string)$input['path']) : '';
if (!preg_match('#^#[/][a-z0-9_-]+$#i', $path) && $path !== '/') $path = '/';
try {
    q('CREATE TABLE IF NOT EXISTS site_visits (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, path VARCHAR(120) NOT NULL, ip_hash CHAR(64) NOT NULL, user_agent VARCHAR(300) NOT NULL DEFAULT \'\', visited_at DATETIME NOT NULL, KEY idx_visit_time (visited_at), KEY idx_visit_path (path, visited_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    q('INSERT INTO site_visits (path,ip_hash,user_agent,visited_at) VALUES (?,?,?,?)', [substr($path, 0, 120), ip_hash(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300), now()]);
    json_out(['ok' => true]);
} catch (Throwable $e) { json_out(['ok' => false], 503); }
