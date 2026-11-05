<?php
function media_usage(array $m): int {
    $u = $m['url']; $n = 0;
    $n += (int)q_val('SELECT COUNT(*) FROM site_profile WHERE photo=? OR cv_url=?', [$u, $u]);
    $n += (int)q_val('SELECT COUNT(*) FROM projects WHERE deleted_at IS NULL AND (image_url=? OR video_url=? OR demo_url=?)', [$u, $u, $u]);
    $n += (int)q_val("SELECT COUNT(*) FROM settings WHERE svalue=?", [$u]);
    if ($m['provider'] === 'streamable') foreach (q_all("SELECT video_url FROM projects WHERE deleted_at IS NULL AND video_url<>''") as $r) if (streamable_id($r['video_url']) === $m['provider_ref']) $n++;
    return $n;
}
$act = post_action();
if ($act !== null) {
    if (!in_array($act, ['upload_image', 'add_video', 'upload_doc', 'add_doc_link', 'rename', 'delete', 'show_delete'], true)) back('media', 'err', 'Aksi tidak valid.');
    try {
        $alt = v_str($_POST['alt'] ?? '', 0, 255);
        if ($alt === null && in_array($act, ['upload_image', 'add_video', 'upload_doc', 'add_doc_link'], true)) back('media', 'err', 'Keterangan tidak valid.');
        if ($act === 'upload_image') { $file = $_FILES['file'] ?? []; if (!is_array($file)) back('media', 'err', 'File tidak valid.'); $id = imgbb_upload($file, $alt ?? ''); audit('media_upload', 'imgbb #' . $id); back('media', 'ok', 'Gambar diunggah ke ImgBB. URL tersimpan di database.'); }
        elseif ($act === 'add_video') {
            $url = v_str($_POST['url'] ?? null, 1, 300);
            if (!$url || !streamable_id($url)) back('media', 'err', 'URL Streamable tidak valid.');
            $r = streamable_add($url, $alt ?? ''); audit('media_add', 'streamable #' . $r['id']); back('media', 'ok', 'Video Streamable ditambahkan' . ($r['verified'] === true ? ' dan terverifikasi oEmbed.' : ' (belum dapat diverifikasi oEmbed).'));
        }
        elseif ($act === 'upload_doc') { $file = $_FILES['file'] ?? []; if (!is_array($file)) back('media', 'err', 'File tidak valid.'); $id = mediafire_upload($file, $alt ?? ''); audit('media_upload', 'mediafire #' . $id); back('media', 'ok', 'Dokumen diunggah ke MediaFire.'); }
        elseif ($act === 'add_doc_link') {
            $url = v_str($_POST['url'] ?? null, 1, 500); $filename = v_str($_POST['filename'] ?? null, 1, 200);
            if (!$url || !$filename) back('media', 'err', 'URL atau nama dokumen tidak valid.');
            $id = mediafire_add_link($url, $filename, $alt ?? ''); audit('media_add', 'mediafire-link #' . $id); back('media', 'ok', 'Tautan dokumen ditambahkan.');
        }
        elseif ($act === 'rename') {
            $n = v_str($_POST['filename'] ?? null, 1, 200); $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$n || $id === false || $id < 1) back('media', 'err', 'Nama atau ID media tidak valid.');
            q('UPDATE media SET filename=? WHERE id=? AND deleted_at IS NULL', [$n, $id]); back('media', 'ok', 'Nama diperbarui (metadata di database).');
        }
        elseif ($act === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) back('media', 'err', 'ID media tidak valid.');
            $m = q_one('SELECT * FROM media WHERE id=? AND deleted_at IS NULL', [$id]);
            if (!$m) back('media', 'err', 'Media tidak ditemukan.');
            if (media_usage($m) > 0) back('media', 'err', 'Media sedang dipakai di konten. Lepaskan dulu dari profil/proyek/pengaturan.');
            $note = $m['provider'] === 'mediafire' ? mediafire_remote_delete($m['provider_ref']) : ($m['provider'] === 'imgbb' ? 'ImgBB tidak menyediakan API hapus terdokumentasi: buka delete URL dari daftar jika ingin menghapus permanen di ImgBB.' : '');
            q('UPDATE media SET deleted_at=? WHERE id=?', [now(), $m['id']]); audit('media_delete', '#' . $m['id']); back('media', 'ok', 'Media dihapus dari library. ' . $note);
        } elseif ($act === 'show_delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id < 1) back('media', 'err', 'ID media tidak valid.');
            $m = q_one("SELECT delete_url_enc FROM media WHERE id=? AND provider='imgbb' AND deleted_at IS NULL", [$id]);
                    $u = $m && $m['delete_url_enc'] ? dec($m['delete_url_enc']) : null; audit('media_delete_url_viewed', '#' . $id);
            back('media', $u ? 'ok' : 'err', $u ? 'Delete URL ImgBB (rahasia, jangan dibagikan): ' . $u : 'Delete URL tidak tersedia.');
        }
    } catch (RuntimeException $ex) { back('media', 'err', $ex->getMessage()); }
    catch (PDOException $ex) { error_log('[portfolio] media: ' . $ex->getMessage()); back('media', 'err', 'Operasi database gagal.'); }
    back('media');
}
layout_head('Media Library', 'media');
$cap = function ($ok, $manual = false) { return $ok ? 'configured' : ($manual ? 'mode manual' : 'belum dikonfigurasi'); };
echo '<div class="card"><h2>Upload gambar (ImgBB: ' . e($cap(imgbb_configured())) . ')</h2><form method="post" enctype="multipart/form-data" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="upload_image"><label>File (JPEG/PNG/GIF/WEBP, maks ' . e(cfg('MAX_IMAGE_MB', '5')) . ' MB)<input type="file" name="file" accept="image/jpeg,image/png,image/gif,image/webp" required></label><label>Alt text<input name="alt" maxlength="255"></label><div class="full"><button class="btn" type="submit" data-confirm="Unggah gambar ini ke ImgBB?">Upload</button></div></form></div>';
echo '<div class="card"><h2>Tambah video Streamable (hanya URL)</h2><p class="muted">API Streamable bersifat read-only: unggah video secara manual di streamable.com, lalu tempel URL-nya di sini.</p><form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="add_video"><label>URL<input type="url" name="url" placeholder="https://streamable.com/abc123" required maxlength="300"></label><label>Alt / caption<input name="alt" maxlength="255"></label><div class="full"><button class="btn" type="submit" data-confirm="Simpan tautan video Streamable ini?">Tambah</button></div></form></div>';
echo '<div class="card"><h2>Dokumen (MediaFire: ' . e($cap(mediafire_configured(), true)) . ')</h2>';
if (mediafire_configured()) echo '<form method="post" enctype="multipart/form-data" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="upload_doc"><label>File dokumen (maks ' . e(cfg('MAX_DOC_MB', '10')) . ' MB)<input type="file" name="file" required></label><label>Keterangan<input name="alt" maxlength="255"></label><div class="full"><button class="btn" type="submit" data-confirm="Unggah dokumen ini ke MediaFire?">Upload ke MediaFire</button> <span class="muted">Integrasi belum diuji dengan akun Anda.</span></div></form><hr>';
echo '<form method="post" class="fields">' . csrf_field() . '<input type="hidden" name="action" value="add_doc_link"><label>Tautan MediaFire publik<input type="url" name="url" placeholder="https://www.mediafire.com/file/..." required maxlength="500"></label><label>Nama dokumen<input name="filename" required maxlength="200"></label><div class="full"><button class="btn" type="submit" data-confirm="Simpan tautan dokumen ini?">Tambah tautan</button></div></form></div>';
$prov = $_GET['provider'] ?? ''; $kind = $_GET['kind'] ?? ''; $query = $_GET['q'] ?? '';
if (!is_string($prov) || ($prov !== '' && !in_array($prov, ['imgbb', 'streamable', 'mediafire', 'manual'], true))
    || !is_string($kind) || ($kind !== '' && !in_array($kind, ['image', 'video', 'document'], true))) {
    http_response_code(400); echo 'Filter tidak valid.'; exit;
}
$qs = v_str($query, 0, 120);
if ($qs === null) { http_response_code(400); echo 'Kata pencarian tidak valid.'; exit; }
$w = ['deleted_at IS NULL']; $par = [];
if ($prov) { $w[] = 'provider=?'; $par[] = $prov; } if ($kind) { $w[] = 'kind=?'; $par[] = $kind; }
if ($qs !== '') { $w[] = '(filename LIKE ? OR url LIKE ? OR alt_text LIKE ?)'; $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $qs) . '%'; array_push($par, $like, $like, $like); }
$pg = filter_var($_GET['pg'] ?? '1', FILTER_VALIDATE_INT);
if ($pg === false || $pg < 1 || $pg > 100000) { http_response_code(400); echo 'Nomor halaman tidak valid.'; exit; }
$per = 20; $wh = implode(' AND ', $w);
$total = (int)q_val("SELECT COUNT(*) FROM media WHERE $wh", $par);
$rows = q_all("SELECT * FROM media WHERE $wh ORDER BY id DESC LIMIT $per OFFSET " . (($pg - 1) * $per), $par);
echo '<div class="card"><h2>Library (' . $total . ')</h2><form method="get" class="fields"><input type="hidden" name="p" value="media"><label>Cari<input name="q" value="' . e($qs) . '"></label><label>Provider<select name="provider"><option value="">Semua</option>';
foreach (['imgbb', 'streamable', 'mediafire'] as $o) echo '<option' . ($prov === $o ? ' selected' : '') . '>' . $o . '</option>';
echo '</select></label><label>Jenis<select name="kind"><option value="">Semua</option>'; foreach (['image', 'video', 'document'] as $o) echo '<option' . ($kind === $o ? ' selected' : '') . '>' . $o . '</option>';
echo '</select></label><div class="full"><button class="btn sec" type="submit">Filter</button></div></form><table><tr><th>Pratinjau</th><th>Metadata</th><th>Dipakai</th><th>Aksi</th></tr>';
foreach ($rows as $m) {
    $u = media_usage($m);
    $pv = $m['kind'] === 'image' ? '<img class="thumb" src="' . e($m['url']) . '" alt="" loading="lazy" referrerpolicy="no-referrer">' : ($m['kind'] === 'video' ? '<span class="pill">video</span>' : '<span class="pill">dokumen</span>');
    $bt = function ($a, $l, $c = 'sec', $conf = '') use ($m) { return '<form method="post">' . csrf_field() . '<input type="hidden" name="id" value="' . (int)$m['id'] . '"><input type="hidden" name="action" value="' . e($a) . '"><button class="btn sm ' . e($c) . '" type="submit"' . ($conf ? ' data-confirm="' . e($conf) . '"' : '') . '>' . e($l) . '</button></form>'; };
    echo '<tr><td>' . $pv . '</td><td><a href="' . e($m['url']) . '" target="_blank" rel="noopener noreferrer">' . e($m['filename'] !== '' ? $m['filename'] : $m['url']) . '</a><br><span class="muted">' . e($m['provider']) . ' · ' . e($m['mime_type']) . ($m['size_bytes'] ? ' · ' . round($m['size_bytes'] / 1024) . ' KB' : '') . ' · ' . e($m['created_at']) . ' UTC<br>' . e($m['url']) . '</span></td><td>' . ($u ? '<span class="pill pub">' . $u . '×</span>' : '<span class="pill">tidak dipakai</span>') . '</td><td><div class="row-actions">'
        . ($m['provider'] === 'imgbb' ? $bt('show_delete', 'Delete URL', 'sec', 'Tampilkan tautan penghapusan rahasia?') : '') . $bt('delete', 'Hapus', 'danger', 'Hapus media dari library?') . '</div></td></tr>';
}
echo '</table>' . ($total > $per ? '<p class="muted"><a href="index.php?p=media&pg=' . ($pg + 1) . '">Berikutnya</a></p>' : '') . '</div>';
layout_foot();
