<?php
$defs = [
  'seo_title' => ['label' => 'Website title', 'max' => 120, 'required' => true], 'seo_description' => ['label' => 'Meta description', 'type' => 'textarea', 'max' => 300, 'full' => true],
  'og_title' => ['label' => 'Open Graph title', 'max' => 120], 'og_description' => ['label' => 'Open Graph description', 'type' => 'textarea', 'max' => 300, 'full' => true],
  'favicon_url' => ['label' => 'Favicon (URL atau path)', 'type' => 'media', 'full' => true], 'theme_color' => ['label' => 'Theme color (#rrggbb)', 'max' => 7, 'pattern' => '#[0-9a-fA-F]{6}', 'pattern_title' => 'Gunakan format warna #rrggbb.'],
  'google_site_verification' => ['label' => 'Google Search Console verification token', 'max' => 100, 'pattern' => '[A-Za-z0-9_\-]{10,100}', 'pattern_title' => 'Token hanya boleh berisi huruf, angka, garis bawah, dan tanda hubung.'],
];
$act = post_action();
if ($act !== null && $act !== 'save') back('settings', 'err', 'Aksi tidak valid.');
if ($act === 'save') {
    $err = null; $vals = [];
    foreach ($defs as $k => $f) { $vals[$k] = fld_value($f, $_POST[$k] ?? '', $err); if ($err) break; }
    if (!$err && $vals['theme_color'] !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $vals['theme_color'])) $err = 'Theme color harus format #rrggbb';
    if (!$err && $vals['google_site_verification'] !== '' && !preg_match('/^[A-Za-z0-9_-]{10,100}$/', $vals['google_site_verification'])) $err = 'Token verifikasi tidak valid';
    if ($err) back('settings', 'err', $err);
    foreach ($vals as $k => $v) set_setting($k, (string)$v);
    audit('update_settings'); back('settings', 'ok', 'Pengaturan tersimpan.');
}
layout_head('Website Settings', 'settings');
echo '<div class="card"><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="save">';
foreach ($defs as $k => $f) echo fld_input($f, $k, setting($k));
echo '<div class="full"><button class="btn" type="submit">Simpan</button></div></form></div><p class="muted">Tautan sosial diatur di menu Contact.</p>'; media_datalist();
layout_foot();
