<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
send_security_headers(true); session_boot();
if (!admin_active()) { redirect('setup.php'); }
if (admin_logged_in()) redirect('index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $user = $_POST['username'] ?? null;
    $pass = $_POST['password'] ?? null;
    if (is_string($user) && is_string($pass)) $err = login_step1($user, $pass);
    else { rate_hit('login'); audit('login_fail'); $err = 'Username atau password salah.'; }
    if ($err === null) redirect('totp.php');
}
auth_head('Masuk');
?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<?php if ($err) echo '<p class="msg err">' . e($err) . '</p>'; ?>
<label>Username<input name="username" required minlength="3" maxlength="64" pattern="[A-Za-z0-9_.\-]+" title="Gunakan 3-64 huruf, angka, titik, garis bawah, atau tanda hubung." autocomplete="username" autofocus></label>
<label>Password<span class="password-field"><input name="password" type="password" required maxlength="200" autocomplete="current-password"><button class="password-toggle" type="button" aria-label="Tampilkan password">Tampilkan</button></span></label>
<button class="btn auth-submit" type="submit" data-loading-label="Memverifikasi..."><span>Lanjut ke verifikasi</span></button><p class="auth-security-note">Lanjutkan dengan kata sandi dan kode verifikasi.</p></form>
<?php auth_foot();
