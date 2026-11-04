<?php
declare(strict_types=1);
// One-time enrollment. Disabled (404) once the admin account is activated. Requires SETUP_KEY from the private config.
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
send_security_headers(true); session_boot();
if (admin_active()) { http_response_code(404); echo 'Not found.'; exit; }
$setupKey = (string)cfg('SETUP_KEY');
if (strlen($setupKey) < 24) { http_response_code(503); echo 'SETUP_KEY belum dikonfigurasi (minimal 24 karakter) di file konfigurasi privat.'; exit; }
$err = ''; $codes = null; $step = !empty($_SESSION['setup_ok']) && admin_row() ? 2 : 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (rate_blocked('setup', 5, 900)) { $err = 'Terlalu banyak percobaan. Coba lagi nanti.'; }
    elseif ($step === 1) {
        $user = v_str($_POST['username'] ?? null, 3, 64);
        $pw = $_POST['password'] ?? null; $pw2 = $_POST['password2'] ?? null; $submittedKey = $_POST['setup_key'] ?? null;
        if (!is_string($submittedKey) || strlen($submittedKey) > 256 || !hash_equals($setupKey, $submittedKey)) { rate_hit('setup'); audit('setup_key_fail'); $err = 'Setup key salah.'; }
        elseif (!$user || !preg_match('/^[A-Za-z0-9_.-]{3,64}$/', $user)) $err = 'Username 3-64 karakter (huruf, angka, _ . -).';
        elseif (!is_string($pw) || !password_ok($pw)) $err = 'Password minimal 12 karakter dan mengandung huruf dan angka.';
        elseif (!is_string($pw2) || !hash_equals($pw, $pw2)) $err = 'Konfirmasi password tidak sama.';
        else {
            $secret = totp_new_secret();
            q('REPLACE INTO admins (id, username, password_hash, totp_secret_enc, totp_enabled, totp_last_step, created_at) VALUES (1,?,?,?,0,0,?)', [$user, password_hash($pw, PASSWORD_DEFAULT), enc($secret), now()]);
            session_regenerate_id(true); $_SESSION['setup_ok'] = true; $_SESSION['csrf'] = bin2hex(random_bytes(32));
            audit('setup_started'); redirect('setup.php');
        }
    } else {
        $a = admin_row(); $sec = $a ? dec((string)$a['totp_secret_enc']) : null;
        $code = $_POST['code'] ?? null;
        $st = $sec && is_string($code) ? totp_verify($sec, $code, 0) : 0;
        if (!$st) { rate_hit('setup'); $err = 'Kode salah. Pastikan jam perangkat akurat.'; }
        else {
            q('UPDATE admins SET totp_enabled=1, totp_last_step=? WHERE id=1', [$st]);
            $codes = new_recovery_codes(); store_recovery_codes($codes);
            unset($_SESSION['setup_ok']); audit('setup_completed');
        }
    }
}
if ($codes !== null) {
    auth_head('Setup selesai');
    echo '<p class="msg ok">Authenticator aktif. Simpan recovery code berikut sekarang: <strong>hanya ditampilkan sekali</strong>, masing-masing sekali pakai.</p><pre class="codes">' . e(implode("\n", $codes)) . '</pre><p><a class="btn" href="login.php">Ke halaman login</a></p>';
    auth_foot(); exit;
}
auth_head('Setup admin');
if ($err) echo '<p class="msg err">' . e($err) . '</p>';
if ($step === 1): ?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<label>Setup key (dari file konfigurasi)<input name="setup_key" type="password" required autocomplete="off"></label>
<label>Username baru<input name="username" required minlength="3" maxlength="64" pattern="[A-Za-z0-9_.\-]+" title="Gunakan 3-64 huruf, angka, titik, garis bawah, atau tanda hubung." autocomplete="off"></label>
<label>Password baru (min. 12 karakter)<input name="password" type="password" required minlength="12" maxlength="200" autocomplete="new-password"></label>
<label>Ulangi password<input name="password2" type="password" required minlength="12" maxlength="200" autocomplete="new-password"></label>
<button class="btn" type="submit" data-confirm="Buat akun admin dengan data ini?">Buat akun & lanjut</button></form>
<?php else:
    $a = admin_row(); $sec = dec((string)$a['totp_secret_enc']); $uri = totp_uri((string)$sec, $a['username'], (string)cfg('APP_NAME', 'Portfolio Admin')); ?>
<p>1. Buka Google Authenticator &rarr; tambah akun &rarr; pindai QR ini (dibuat lokal di browser, tidak dikirim ke layanan pihak ketiga).</p>
<div id="qr" class="qr" data-uri="<?= e($uri) ?>"></div>
<p class="muted">Tidak bisa memindai? Entri manual (hanya tampil saat setup): <code><?= e(trim(chunk_split((string)$sec, 4, ' '))) ?></code></p>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<label>2. Masukkan kode 6 digit untuk mengaktifkan<input name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code"></label>
<button class="btn" type="submit" data-confirm="Aktifkan verifikasi 2 langkah untuk akun admin ini?">Aktifkan</button></form>
<script src="assets/qrcode.js"></script>
<?php endif; auth_foot();
