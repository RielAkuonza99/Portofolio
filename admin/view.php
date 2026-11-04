<?php
declare(strict_types=1);
function auth_head(string $title): void {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . '</title><link rel="stylesheet" href="assets/admin.css"></head><body><main class="auth"><h1>' . e($title) . '</h1>';
}
function auth_foot(): void { echo '</main><script src="assets/admin.js"></script></body></html>'; }
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function flash_show(): void { if (!empty($_SESSION['flash'])) { list($t, $m) = $_SESSION['flash']; unset($_SESSION['flash']); echo '<p class="msg ' . ($t === 'ok' ? 'ok' : 'err') . '">' . e($m) . '</p>'; } }
function layout_head(string $title, string $page): void {
    $nav = ['overview' => 'Overview', 'profile' => 'Profile', 'about' => 'About', 'skills' => 'Skills', 'projects' => 'Projects', 'journey' => 'Journey', 'contact' => 'Contact', 'settings' => 'Settings', 'media' => 'Media', 'security' => 'Security', 'tools' => 'Tools'];
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' · Dashboard</title><link rel="stylesheet" href="assets/admin.css"></head><body><div class="wrap"><nav class="side">';
    foreach ($nav as $k => $l) echo '<a class="' . ($k === $page ? 'on' : '') . '" href="index.php?p=' . $k . '">' . e($l) . '</a>';
    echo '<a class="btn sec sm" href="logout.php">Keluar</a></nav><main><h1>' . e($title) . '</h1>';
    flash_show();
}
function layout_foot(): void { echo '</main></div><script src="assets/admin.js"></script></body></html>'; }
function post_action(): ?string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return null;
    csrf_verify();
    $action = $_POST['action'] ?? null;
    if (!is_string($action) || !preg_match('/^[a-z][a-z0-9_]{0,39}$/D', $action)) {
        http_response_code(400); echo 'Aksi tidak valid.'; exit;
    }
    return $action;
}
function back(string $page, string $type = 'ok', string $msg = ''): void { if ($msg !== '') flash($type, $msg); redirect('index.php?p=' . $page); }
