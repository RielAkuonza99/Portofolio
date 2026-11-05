<?php
declare(strict_types=1);
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/totp.php';

const LOGIN_MAX = 5; const LOGIN_WINDOW = 900; const PRE_AUTH_TTL = 300;
function admin_row(): ?array { return q_one('SELECT * FROM admins WHERE id=1'); }
function admin_active(): bool { $a = admin_row(); return $a && (int)$a['totp_enabled'] === 1; }
function new_recovery_codes(int $n = 10): array {
    $codes = []; $alpha = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    for ($i = 0; $i < $n; $i++) { $c = ''; for ($j = 0; $j < 12; $j++) $c .= $alpha[random_int(0, 31)]; $codes[] = substr($c, 0, 4) . '-' . substr($c, 4, 4) . '-' . substr($c, 8, 4); }
    return $codes;
}
function store_recovery_codes(array $codes): void {
    q('DELETE FROM recovery_codes WHERE admin_id=1');
    $ins = db()->prepare('INSERT INTO recovery_codes (admin_id, code_hash, created_at) VALUES (1,?,?)');
    foreach ($codes as $c) $ins->execute([password_hash(strtoupper($c), PASSWORD_DEFAULT), now()]);
}
function password_ok(string $pw): bool { return strlen($pw) >= 12 && strlen($pw) <= 200 && preg_match('/[A-Za-z]/', $pw) && preg_match('/\d/', $pw); }

/** Step 1. Returns null on success (pre-auth state set) or a generic error string. */
function login_step1(string $user, string $pass): ?string {
    session_boot();
    if (rate_blocked('login', LOGIN_MAX, LOGIN_WINDOW) || (int)q_val("SELECT COUNT(*) FROM audit_log WHERE action='login_fail' AND created_at > ?", [gmdate('Y-m-d H:i:s', time() - 3600)]) >= 30) {
        audit('login_blocked'); return 'Terlalu banyak percobaan. Coba lagi nanti.';
    }
    $a = admin_row(); $dummy = '$2y$10$usesomesillystringforsaltuQ1Zk3yH5mKfWl3dC9mQb1b9u1G8e2';
    $validUser = strlen($user) >= 3 && strlen($user) <= 64 && preg_match('/^[A-Za-z0-9_.-]+$/', $user);
    $validPass = $pass !== '' && strlen($pass) <= 200;
    $okUser = $validUser && $a && (int)$a['totp_enabled'] === 1 && hash_equals(strtolower($a['username']), strtolower($user));
    $okPass = password_verify($validPass ? $pass : '', $okUser ? $a['password_hash'] : $dummy);
    if (!($okUser && $okPass)) { rate_hit('login'); audit('login_fail'); return 'Username atau password salah.'; }
    if (!session_regenerate_id(true)) { audit('session_regenerate_fail', 'pre-auth'); return 'Tidak dapat membuat sesi login. Coba lagi.'; }
    $_SESSION = ['pre' => ['t' => time()], 'csrf' => bin2hex(random_bytes(32))];
    return null;
}
/** Step 2: TOTP or recovery code. Returns null on success, error string otherwise. */
function login_step2(string $code, bool $recovery): ?string {
    session_boot();
    if (empty($_SESSION['pre']) || time() - (int)$_SESSION['pre']['t'] > PRE_AUTH_TTL) { unset($_SESSION['pre']); return 'Sesi login kedaluwarsa. Mulai ulang.'; }
    if (rate_blocked('totp', LOGIN_MAX, LOGIN_WINDOW)) { audit('totp_blocked'); return 'Terlalu banyak percobaan. Coba lagi nanti.'; }
    if (strlen($code) > 20) { rate_hit('totp'); audit($recovery ? 'recovery_fail' : 'totp_fail'); return 'Kode tidak valid.'; }
    $a = admin_row(); if (!$a) return 'Sesi login tidak valid.';
    $ok = false;
    if ($recovery) {
        $c = strtoupper(trim($code));
        if (strlen($c) !== 14 || !preg_match('/^[A-HJ-NP-Z2-9]{4}(?:-[A-HJ-NP-Z2-9]{4}){2}$/', $c)) {
            rate_hit('totp'); audit('recovery_fail'); return 'Kode tidak valid.';
        }
        foreach (q_all('SELECT id, code_hash FROM recovery_codes WHERE admin_id=1 AND used_at IS NULL') as $r) {
            if (password_verify($c, $r['code_hash'])) { $ok = q('UPDATE recovery_codes SET used_at=? WHERE id=? AND used_at IS NULL', [now(), $r['id']])->rowCount() === 1; if ($ok) audit('recovery_code_used'); break; }
        }
    } else {
        $sec = dec((string)$a['totp_secret_enc']);
        $step = $sec ? totp_verify($sec, $code, (int)$a['totp_last_step']) : 0;
        if ($step > 0) $ok = q('UPDATE admins SET totp_last_step=? WHERE id=1 AND totp_last_step < ?', [$step, $step])->rowCount() === 1; // atomic anti-replay
    }
    if (!$ok) { rate_hit('totp'); audit($recovery ? 'recovery_fail' : 'totp_fail'); return 'Kode tidak valid.'; }
    rate_clear('login'); rate_clear('totp');
    if (!session_regenerate_id(true)) { audit('session_regenerate_fail', 'authenticated'); return 'Tidak dapat membuat sesi login. Coba lagi.'; }
    $_SESSION = ['uid' => 1, 'created' => time(), 'last' => time(), 'ua' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 'csrf' => bin2hex(random_bytes(32)), 'login_id' => bin2hex(random_bytes(16))];
    q('UPDATE admins SET last_login_at=? WHERE id=1', [now()]);
    audit('login_ok');
    return null;
}
function admin_logged_in(): bool {
    session_boot();
    if (empty($_SESSION['uid'])) return false;
    if (!isset($_SESSION['last'], $_SESSION['created'], $_SESSION['ua'], $_SESSION['csrf'], $_SESSION['login_id'])) { admin_logout(false); return false; }
    $idle = (int)cfg('SESSION_IDLE_MIN', '30') * 60; $abs = (int)cfg('SESSION_MAX_HOURS', '8') * 3600;
    if (time() - (int)$_SESSION['last'] > $idle || time() - (int)$_SESSION['created'] > $abs || !hash_equals((string)$_SESSION['ua'], hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''))) { admin_logout(false); return false; }
    $_SESSION['last'] = time();
    return true;
}
function require_admin(): void { if (!admin_logged_in()) redirect('login.php'); send_security_headers(true); }
function require_admin_api(): void { if (!admin_logged_in()) json_out(['error' => 'unauthorized'], 401); }
function admin_logout(bool $log = true): void {
    session_boot(); if ($log && !empty($_SESSION['uid'])) audit('logout');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']); }
    if (!session_destroy()) throw new RuntimeException('Unable to destroy the current session.');
}
