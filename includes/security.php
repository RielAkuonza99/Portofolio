<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function send_security_headers(bool $admin = false): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: DENY');
    if ($admin) {
        header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; frame-src https://streamable.com; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
}
function session_boot(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('pf_sid');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure' => is_https() && cfg_bool('SESSION_SECURE', true),
        'httponly' => true, 'samesite' => (string)cfg('SESSION_SAME_SITE', 'Strict'),
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}
function csrf_token(): string {
    session_boot();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function same_origin(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $h) {
        if (!empty($_SERVER[$h])) { $p = parse_url($_SERVER[$h]); return isset($p['host']) && strcasecmp($p['host'] . (isset($p['port']) ? ':' . $p['port'] : ''), $host) === 0; }
    }
    return true; // no header sent (some privacy settings); CSRF token remains the primary control
}
function csrf_verify(): void {
    session_boot();
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($_SESSION['csrf']) || !is_string($t) || !hash_equals($_SESSION['csrf'], $t) || !same_origin()) {
        http_response_code(403); echo 'Invalid request (CSRF).'; exit;
    }
}
function redirect(string $to): void { header('Location: ' . $to); exit; }
function json_out($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
