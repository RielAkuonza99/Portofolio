<?php
declare(strict_types=1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/content.php';
require __DIR__ . '/../includes/providers/brevo.php';
require __DIR__ . '/../includes/providers/imgbb.php';
require __DIR__ . '/../includes/providers/streamable.php';
require __DIR__ . '/../includes/providers/mediafire.php';
require __DIR__ . '/../includes/uploads.php';
require __DIR__ . '/view.php';
require __DIR__ . '/crud.php';
if (!admin_active()) redirect('setup.php');
require_admin();
$pages = ['overview', 'profile', 'about', 'skills', 'projects', 'journey', 'contact', 'settings', 'security', 'tools'];
$p = (string)($_GET['p'] ?? 'overview');
if (!in_array($p, $pages, true)) $p = 'overview';
require __DIR__ . '/pages/' . $p . '.php';
