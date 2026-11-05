<?php
declare(strict_types=1);
// RFC 6238 TOTP (SHA-1, 6 digits, 30s) - compatible with Google Authenticator. No external dependency.
const B32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
function b32_encode(string $bin): string {
    $bits = ''; foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    $out = ''; foreach (str_split($bits, 5) as $chunk) $out .= B32[bindec(str_pad($chunk, 5, '0'))];
    return $out;
}
function b32_decode(string $s): string {
    $s = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $s)); $bits = '';
    foreach (str_split($s) as $c) { $p = strpos(B32, $c); if ($p === false) continue; $bits .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT); }
    $out = ''; foreach (str_split($bits, 8) as $b) if (strlen($b) === 8) $out .= chr(bindec($b));
    return $out;
}
function totp_new_secret(): string { return b32_encode(random_bytes(20)); }
function totp_code(string $secretB32, int $step): string {
    $bin = pack('N2', 0, $step);
    $h = hash_hmac('sha1', $bin, b32_decode($secretB32), true);
    $o = ord($h[19]) & 0x0F;
    $n = ((ord($h[$o]) & 0x7F) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
}
/** Returns matched step (>0) or 0. Rejects steps <= $lastStep (replay). Window +-1 step. */
function totp_verify(string $secretB32, string $code, int $lastStep, ?int $atTime = null): int {
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) return 0;
    $cur = intdiv($atTime ?? time(), 30);
    for ($d = -1; $d <= 1; $d++) {
        $step = $cur + $d;
        if ($step > $lastStep && hash_equals(totp_code($secretB32, $step), $code)) return $step;
    }
    return 0;
}
function totp_uri(string $secretB32, string $account, string $issuer): string {
    return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . $secretB32 . '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
}
