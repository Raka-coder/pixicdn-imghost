<?php
/**
 * ============================================================
 * File: auth/logout.php
 * Deskripsi: Logout, hapus session
 * Author: Raka Restu Saputra
 * Created: 2026-10-10
 * ============================================================
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config.example.php';
if (is_file(dirname(__DIR__) . '/config.php')) {
    require_once dirname(__DIR__) . '/config.php';
}
require_once dirname(__DIR__) . '/includes/auth.php';

start_secure_session();
$_SESSION = [];
session_destroy();
setcookie(SESSION_NAME, '', time() - 3600, '/');
redirect('/');
