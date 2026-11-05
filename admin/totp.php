<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
send_security_headers(true); session_boot();
if (admin_logged_in()) redirect('index.php');
if (empty($_SESSION['pre'])) redirect('login.php');
$rec = isset($_GET['recovery']); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $code = $_POST['code'] ?? null;
    if (is_string($code)) $err = login_step2($code, $rec);
    else { rate_hit('totp'); audit($rec ? 'recovery_fail' : 'totp_fail'); $err = 'Kode tidak valid.'; }
    if ($err === null) {
        // Optional security notice (once per successful login, never on refresh).
        require_once __DIR__ . '/../includes/providers/brevo.php';
        if (cfg_bool('NOTIFY_ON_LOGIN', true)) brevo_send('login', 'Login dashboard portofolio', '<p>Login berhasil pada ' . e(now()) . ' UTC.</p><p>Jika bukan Anda, ganti password dan buat ulang recovery code.</p>');
        redirect('index.php');
    }
    if (empty($_SESSION['pre'])) redirect('login.php');
}
auth_head($rec ? 'Recovery code' : 'Verifikasi 2 langkah');
?>
<form method="post" autocomplete="off"><?= csrf_field() ?>
<?php if ($err) echo '<p class="msg err">' . e($err) . '</p>'; ?>
<?php if ($rec): ?>
<label>Recovery code<input name="code" required minlength="14" maxlength="14" pattern="[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}" placeholder="XXXX-XXXX-XXXX" autofocus></label>
<p class="muted"><a href="totp.php">Pakai kode Authenticator</a></p>
<?php else: ?>
<label>Kode 6 digit dari Google Authenticator<input name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" autofocus></label>
<p class="muted"><a href="totp.php?recovery=1">Pakai recovery code</a></p>
<?php endif; ?>
<button class="btn" type="submit">Masuk</button></form>
<?php auth_foot();
