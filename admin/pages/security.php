<?php
$act = post_action();
if ($act !== null && !in_array($act, ['change_password', 'regen_codes'], true)) back('security', 'err', 'Aksi tidak valid.');
if ($act === 'change_password') {
    $a = admin_row(); $cur = $_POST['current'] ?? null; $new = $_POST['new'] ?? null; $new2 = $_POST['new2'] ?? null; $code = $_POST['code'] ?? null;
    if (rate_blocked('pwchange', 5, 900)) back('security', 'err', 'Terlalu banyak percobaan.');
    if (!is_string($cur) || strlen($cur) > 200 || !is_string($new) || !is_string($new2) || strlen($new2) > 200 || !is_string($code) || strlen($code) > 20) back('security', 'err', 'Input tidak valid.');
    $sec = dec((string)$a['totp_secret_enc']); $step = $sec ? totp_verify($sec, $code, (int)$a['totp_last_step']) : 0;
    if (!password_verify($cur, $a['password_hash']) || !$step) { rate_hit('pwchange'); audit('password_change_fail'); back('security', 'err', 'Password lama atau kode TOTP salah.'); }
    if (!password_ok($new) || !hash_equals($new, $new2)) back('security', 'err', 'Password baru min. 12 karakter (huruf+angka) dan harus sama dengan konfirmasi.');
    q('UPDATE admins SET password_hash=?, totp_last_step=? WHERE id=1', [password_hash($new, PASSWORD_DEFAULT), $step]);
    audit('password_changed'); session_regenerate_id(true); back('security', 'ok', 'Password diganti.');
}
if ($act === 'regen_codes') {
    $a = admin_row(); $code = $_POST['code'] ?? null; $current = $_POST['current'] ?? null;
    if (rate_blocked('recovery_regen', 5, 900)) back('security', 'err', 'Terlalu banyak percobaan.');
    if (!is_string($code) || strlen($code) > 20 || !is_string($current) || strlen($current) > 200) { rate_hit('recovery_regen'); audit('recovery_regen_fail'); back('security', 'err', 'Input tidak valid.'); }
    $sec = dec((string)$a['totp_secret_enc']); $step = $sec ? totp_verify($sec, $code, (int)$a['totp_last_step']) : 0;
    if (!$step || !password_verify($current, $a['password_hash'])) { rate_hit('recovery_regen'); audit('recovery_regen_fail'); back('security', 'err', 'Password atau kode TOTP salah.'); }
    rate_clear('recovery_regen');
    q('UPDATE admins SET totp_last_step=? WHERE id=1', [$step]);
    $codes = new_recovery_codes(); store_recovery_codes($codes); audit('recovery_regenerated');
    layout_head('Recovery code baru', 'security');
    echo '<p class="msg ok">Simpan sekarang. Hanya ditampilkan sekali; kode lama tidak berlaku.</p><pre class="codes">' . e(implode("\n", $codes)) . '</pre>'; layout_foot(); exit;
}
layout_head('Security', 'security');
$left = (int)q_val('SELECT COUNT(*) FROM recovery_codes WHERE admin_id=1 AND used_at IS NULL');
echo '<div class="card"><h2>Ganti password</h2><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="change_password"><label>Password saat ini<input type="password" name="current" required maxlength="200" autocomplete="current-password"></label><label>Kode TOTP<input name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="off"></label><label>Password baru<input type="password" name="new" required minlength="12" maxlength="200" autocomplete="new-password"></label><label>Ulangi<input type="password" name="new2" required minlength="12" maxlength="200" autocomplete="new-password"></label><div class="full"><button class="btn" type="submit" data-confirm="Ganti password admin sekarang?">Ganti</button></div></form></div>';
echo '<div class="card"><h2>Recovery code (' . $left . ' tersisa)</h2><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="regen_codes"><label>Password<input type="password" name="current" required maxlength="200" autocomplete="current-password"></label><label>Kode TOTP<input name="code" required inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="off"></label><div class="full"><button class="btn danger" type="submit" data-confirm="Buat ulang? Kode lama akan hangus.">Buat ulang kode</button></div></form></div>';
echo '<div class="card"><h2>Audit log (100 terbaru)</h2><table><tr><th>Waktu (UTC)</th><th>Aksi</th><th>Detail</th></tr>';
foreach (q_all('SELECT action, detail, created_at FROM audit_log ORDER BY id DESC LIMIT 100') as $r) echo '<tr><td>' . e($r['created_at']) . '</td><td>' . e($r['action']) . '</td><td>' . e($r['detail']) . '</td></tr>';
echo '</table></div><div class="card"><h2>Notifikasi email (30 terbaru)</h2><table><tr><th>Waktu</th><th>Event</th><th>Status</th><th>Detail</th></tr>';
foreach (q_all('SELECT * FROM notification_log ORDER BY id DESC LIMIT 30') as $r) echo '<tr><td>' . e($r['created_at']) . '</td><td>' . e($r['event']) . '</td><td><span class="pill ' . e($r['status']) . '">' . e($r['status']) . '</span></td><td>' . e($r['detail']) . '</td></tr>';
echo '</table></div>'; layout_foot();
