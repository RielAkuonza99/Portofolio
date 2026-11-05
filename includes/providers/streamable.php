<?php
declare(strict_types=1);
require_once __DIR__ . '/../http.php';
require_once __DIR__ . '/../content.php';
// Streamable's official API is read-only (oEmbed / GET videos). Upload is NOT supported by the API: videos are uploaded manually at streamable.com.

/** Registers a Streamable URL in the media library. Optional oEmbed check; network failure does not block (reported as unverified). */
function streamable_add(string $url, string $alt = ''): array {
    $id = streamable_id($url);
    if (!$id) throw new RuntimeException('URL Streamable tidak valid. Contoh: https://streamable.com/abc123');
    $canon = 'https://streamable.com/' . $id; $verified = null; $title = '';
    if (cfg_bool('STREAMABLE_VALIDATE', true)) {
        $r = http_request('GET', 'https://api.streamable.com/oembed.json?url=' . rawurlencode($canon), ['Accept: application/json'], null, 8);
        if ($r['error'] === '' && $r['status'] === 200) { $j = json_decode($r['body'], true); $verified = is_array($j) && ($j['type'] ?? '') === 'video'; $title = is_array($j) ? (string)($j['title'] ?? '') : ''; }
        elseif ($r['error'] === '' && in_array($r['status'], [404, 400], true)) throw new RuntimeException('Video tidak ditemukan di Streamable (HTTP ' . $r['status'] . ').');
    }
    q('INSERT INTO media (provider,kind,url,provider_ref,filename,alt_text) VALUES (?,?,?,?,?,?)', ['streamable', 'video', $canon, $id, mb_substr($title, 0, 250), mb_substr($alt, 0, 255)]);
    return ['id' => (int)db()->lastInsertId(), 'verified' => $verified];
}
function streamable_test(): array {
    $r = http_request('GET', 'https://api.streamable.com/oembed.json?url=' . rawurlencode('https://streamable.com/hn8hq'), ['Accept: application/json'], null, 10);
    if ($r['error'] !== '') return ['failed', 'Jaringan: ' . mb_substr($r['error'], 0, 120)];
    return $r['status'] === 200 ? ['success', 'oEmbed dapat dijangkau (upload via API tidak didukung Streamable)'] : ['failed', 'HTTP ' . $r['status']];
}
