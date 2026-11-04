<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/view.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $confirm = $_POST['confirm'] ?? null;
    if ($confirm !== 'yes') redirect('index.php');
    admin_logout();
    redirect('login.php');
}
auth_head('Konfirmasi keluar');
echo '<p>Anda yakin ingin keluar dari dashboard admin?</p><form method="post">' . csrf_field() . '<button class="btn danger" type="submit" name="confirm" value="yes">Ya, keluar</button> <a class="btn sec" href="index.php">Batal</a></form>';
auth_foot();
