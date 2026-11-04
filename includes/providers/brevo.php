<?php
declare(strict_types=1);
require_once __DIR__ . '/../http.php';

function brevo_configured(): bool { return cfg('BREVO_API_KEY') !== '' && cfg('BREVO_SENDER_EMAIL') !== '' && cfg('ADMIN_NOTIFICATION_EMAIL') !== ''; }

/** Sends via Brevo Transactional Email API (POST /v3/smtp/email). Success only when HTTP 201/202 and messageId present. */
function brevo_send(string $event, string $subject, string $html, ?string $replyTo = null): bool {
    if (!brevo_configured()) { notif_log($event, 'skipped', 'Brevo not configured'); return false; }
    $to = v_email(cfg('ADMIN_NOTIFICATION_EMAIL')); $from = v_email(cfg('BREVO_SENDER_EMAIL'));
    if (!$to || !$from) { notif_log($event, 'failed', 'Invalid sender/recipient email in config'); return false; }
    $body = ['sender' => ['name' => (string)cfg('BREVO_SENDER_NAME', 'Portfolio'), 'email' => $from], 'to' => [['email' => $to]], 'subject' => mb_substr($subject, 0, 200), 'htmlContent' => $html];
    if ($replyTo && v_email($replyTo)) $body['replyTo'] = ['email' => $replyTo];
    $r = http_request('POST', 'https://api.brevo.com/v3/smtp/email', ['api-key: ' . cfg('BREVO_API_KEY'), 'accept: application/json', 'content-type: application/json'], json_encode($body), 15);
    $j = json_decode($r['body'], true);
    if ($r['error'] === '' && in_array($r['status'], [201, 202], true) && is_array($j) && !empty($j['messageId'])) { notif_log($event, 'sent', 'HTTP ' . $r['status']); return true; }
    notif_log($event, 'failed', $r['error'] !== '' ? 'network: ' . mb_substr($r['error'], 0, 120) : 'HTTP ' . $r['status'] . ' ' . mb_substr((string)($j['message'] ?? ''), 0, 150));
    return false;
}
function notif_log(string $event, string $status, string $detail): void {
    try { q('INSERT INTO notification_log (event,status,detail,created_at) VALUES (?,?,?,?)', [$event, $status, mb_substr($detail, 0, 290), now()]); } catch (Throwable $e) {}
}
/** Connectivity/auth test: GET /v3/account (authenticated, sends no email). */
function brevo_test(): array {
    if (cfg('BREVO_API_KEY') === '') return ['unconfigured', 'BREVO_API_KEY belum diisi'];
    $r = http_request('GET', 'https://api.brevo.com/v3/account', ['api-key: ' . cfg('BREVO_API_KEY'), 'accept: application/json'], null, 12);
    if ($r['error'] !== '') return ['failed', 'Jaringan: ' . mb_substr($r['error'], 0, 120)];
    return $r['status'] === 200 ? ['success', 'API key diterima Brevo'] : ['failed', 'HTTP ' . $r['status']];
}
