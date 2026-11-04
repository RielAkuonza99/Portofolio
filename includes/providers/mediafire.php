<?php
declare(strict_types=1);
require_once __DIR__ . '/../http.php';
// MediaFire Core API (https://www.mediafire.com/developers/core_api/1.1/). Endpoints used: user/get_session_token, upload/simple, upload/poll_upload, file/get_links, file/delete, file/update.
// STATUS: session-token auth follows the official docs; upload/poll/links parameter details follow the official endpoint list + public SDKs and are UNTESTED with owner credentials.
// Manual fallback (paste a public MediaFire link) is always available.

const DOC_MIMES = ['application/pdf' => 'pdf', 'application/zip' => 'zip', 'text/plain' => 'txt',
    'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.ms-powerpoint' => 'ppt', 'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'];
function mediafire_configured(): bool { foreach (['MEDIAFIRE_EMAIL', 'MEDIAFIRE_PASSWORD', 'MEDIAFIRE_APP_ID', 'MEDIAFIRE_API_KEY'] as $k) if (cfg($k) === '') return false; return true; }

function mf_call(string $path, array $params): array {
    $params['response_format'] = 'json';
    $r = http_request('POST', 'https://www.mediafire.com/api/1.1/' . $path . '.php', ['Content-Type: application/x-www-form-urlencoded'], http_build_query($params), 25);
    if ($r['error'] !== '') throw new RuntimeException('MediaFire tidak dapat dihubungi.');
    $j = json_decode($r['body'], true);
    if (!is_array($j) || !isset($j['response'])) throw new RuntimeException('Respons MediaFire tidak valid (HTTP ' . $r['status'] . ').');
    if (($j['response']['result'] ?? '') !== 'Success') throw new RuntimeException('MediaFire error ' . (string)($j['response']['error'] ?? '?') . ': ' . mb_substr((string)($j['response']['message'] ?? ''), 0, 100));
    return $j['response'];
}
function mf_session(): string {
    $e = cfg('MEDIAFIRE_EMAIL'); $p = cfg('MEDIAFIRE_PASSWORD'); $a = cfg('MEDIAFIRE_APP_ID');
    $res = mf_call('user/get_session_token', ['email' => $e, 'password' => $p, 'application_id' => $a, 'signature' => sha1($e . $p . $a . cfg('MEDIAFIRE_API_KEY')), 'token_version' => 1]);
    if (empty($res['session_token'])) throw new RuntimeException('MediaFire tidak mengembalikan session token.');
    return (string)$res['session_token'];
}
function mediafire_test(): array {
    if (!mediafire_configured()) return ['manual', 'Kredensial belum diisi: mode URL manual'];
    try { mf_session(); return ['success', 'Autentikasi sesi berhasil (upload belum diuji)']; } catch (RuntimeException $e) { return ['failed', $e->getMessage()]; }
}
function mediafire_upload(array $file, string $alt = ''): int {
    if (!mediafire_configured()) throw new RuntimeException('MediaFire belum dikonfigurasi. Gunakan tautan manual.');
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload gagal (kode ' . (int)($file['error'] ?? -1) . ').');
    $tmp = $file['tmp_name'] ?? null; $name = $file['name'] ?? null;
    if (!is_string($tmp) || $tmp === '' || !is_file($tmp) || (!is_uploaded_file($tmp) && !defined('TEST_ALLOW_LOCAL_UPLOAD')) || !is_string($name)) throw new RuntimeException('File tidak valid.');
    $size = (int)filesize($tmp);
    if ($size <= 0 || $size > max_upload_bytes('MAX_DOC_MB', 10)) throw new RuntimeException('Ukuran dokumen maksimum ' . cfg('MAX_DOC_MB', '10') . ' MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset(DOC_MIMES[$mime])) throw new RuntimeException('Tipe dokumen tidak diizinkan (' . e($mime) . ').');
    $orig = mb_substr(preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name)), 0, 120);
    if (strtolower(pathinfo($orig, PATHINFO_EXTENSION)) !== DOC_MIMES[$mime]) $orig = pathinfo($orig, PATHINFO_FILENAME) . '.' . DOC_MIMES[$mime];
    $tok = mf_session();
    $r = http_request('POST', 'https://www.mediafire.com/api/1.1/upload/simple.php?session_token=' . rawurlencode($tok) . '&response_format=json',
        ['X-Filesize: ' . $size], ['file' => new CURLFile($tmp, $mime, $orig)], 90);
    $j = json_decode($r['body'], true);
    $key = $j['response']['doupload']['key'] ?? '';
    if ($r['error'] !== '' || $key === '') throw new RuntimeException('Upload MediaFire gagal atau respons tidak dikenali.');
    $quick = '';
    for ($i = 0; $i < 10 && $quick === ''; $i++) {
        usleep(700000);
        $p = mf_call('upload/poll_upload', ['key' => $key]);
        $quick = (string)($p['doupload']['quickkey'] ?? '');
        if (($p['doupload']['result'] ?? '0') !== '0' && $quick === '') throw new RuntimeException('MediaFire menolak berkas (kode ' . e((string)$p['doupload']['result']) . ').');
    }
    if ($quick === '') throw new RuntimeException('MediaFire belum selesai memproses berkas; coba lagi.');
    $l = mf_call('file/get_links', ['session_token' => $tok, 'quick_key' => $quick, 'link_type' => 'normal_download']);
    $url = v_url($l['links'][0]['normal_download'] ?? '', ['https']);
    if (!$url) throw new RuntimeException('MediaFire tidak mengembalikan tautan unduhan.');
    q('INSERT INTO media (provider,kind,url,provider_ref,filename,mime_type,size_bytes,alt_text) VALUES (?,?,?,?,?,?,?,?)', ['mediafire', 'document', $url, $quick, $orig, $mime, $size, mb_substr($alt, 0, 255)]);
    return (int)db()->lastInsertId();
}
function mediafire_add_link(string $url, string $filename, string $alt = ''): int {
    $u = v_url($url, ['https']);
    if (!$u || !preg_match('#^https://(www\.)?mediafire\.com/#i', $u)) throw new RuntimeException('Tautan harus berupa URL https://www.mediafire.com/...');
    $fn = mb_substr(preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename), 0, 200);
    q('INSERT INTO media (provider,kind,url,filename,alt_text) VALUES (?,?,?,?,?)', ['mediafire', 'document', $u, $fn, mb_substr($alt, 0, 255)]);
    return (int)db()->lastInsertId();
}
/** Best-effort remote delete; returns note for the admin UI. DB soft-delete happens regardless. */
function mediafire_remote_delete(string $quick): string {
    if ($quick === '' || !mediafire_configured()) return 'Berkas MediaFire tidak dihapus dari akun (tautan manual / belum dikonfigurasi).';
    try { mf_call('file/delete', ['session_token' => mf_session(), 'quick_key' => $quick]); return 'Berkas juga dihapus dari MediaFire.'; } catch (RuntimeException $e) { return 'Gagal menghapus di MediaFire: ' . $e->getMessage(); }
}
