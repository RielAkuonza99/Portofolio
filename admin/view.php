<?php
declare(strict_types=1);
function auth_head(string $title): void {
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' · Personal HUB</title><link rel="stylesheet" href="assets/admin.css"></head><body class="auth-page'.($title === 'Masuk' ? ' auth-login-page' : '').'">';
    if ($title === 'Masuk') {
        echo '<main class="auth auth-login"><aside class="auth-brand"><a class="auth-logo" href="../" aria-label="Kembali ke website"><span class="auth-logo-mark" aria-hidden="true">PH</span><span>Personal HUB</span></a><div class="auth-brand-copy"><p>Dasbor administrasi</p><h2>Kelola portfolio dan konten dari satu tempat.</h2></div><div class="auth-trust"><span class="auth-trust-line" aria-hidden="true"></span><span>Sesi terenkripsi dan terlindungi</span></div></aside><section class="auth-panel"><a class="auth-back" href="../">Kembali ke website</a><header><p class="auth-kicker">Admin access</p><h1>Masuk</h1><p class="muted">Masukkan kredensial untuk membuka dashboard.</p></header>';
    } else {
        echo '<main class="auth"><h1>' . e($title) . '</h1>';
    }
}
function auth_foot(): void {
    if (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/login.php')) echo '</section>';
    echo '</main><script src="assets/admin.js"></script></body></html>';
}
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function flash_show(): void { if (!empty($_SESSION['flash'])) { list($t, $m) = $_SESSION['flash']; unset($_SESSION['flash']); echo '<p class="msg ' . ($t === 'ok' ? 'ok' : 'err') . '">' . e($m) . '</p>'; } }
function layout_head(string $title, string $page): void {
    $groups = [
        'Ringkasan' => ['overview' => 'Overview'],
        'Konten' => ['profile'=>'Profile','about'=>'About','skills'=>'Skills','projects'=>'Projects','journey'=>'Journey','contact'=>'Contact'],
        'Analitik & Sistem' => ['settings'=>'Settings','security'=>'Security','tools'=>'Tools'],
    ];
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>' . e($title) . ' · Dashboard</title><link rel="stylesheet" href="assets/admin.css"></head><body><div class="wrap"><nav class="side" aria-label="Menu dashboard"><div class="side-brand"><strong>Personal HUB</strong><span class="muted">Dashboard</span></div>';
    foreach ($groups as $group => $nav) { echo '<div class="nav-group"><p>' . e($group) . '</p>'; foreach ($nav as $k => $l) echo '<a class="' . ($k === $page ? 'on' : '') . '" href="index.php?p=' . $k . '">' . e($l) . '</a>'; echo '</div>'; }
    echo '<form class="logout-form" method="post" action="logout.php">' . csrf_field() . '<input type="hidden" name="confirm" value="yes"><button class="btn sec sm" type="submit" data-confirm="Keluar dari dashboard?">Keluar</button></form></nav><main><h1>' . e($title) . '</h1>';
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
