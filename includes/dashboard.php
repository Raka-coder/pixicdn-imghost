<?php

/**
 * ============================================================
 * File: includes/dashboard.php
 * Deskripsi: Agregasi metrik dashboard user — SATU sumber kebenaran.
 *   Dipakai dashboard/index.php (render awal) dan dashboard/stats.php
 *   (refresh realtime via fetch). Semua angka dari database, bukan dummy.
 * ============================================================
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Hitung metrik dashboard untuk satu user.
 *
 * @param int $user_id ID user
 * @return array{total: int, views: int, storage_used: int, storage_quota: int, uploads7: array{labels: list<string>, data: list<int>}, views30: array{labels: list<string>, data: list<int>}, top: list<array<string, mixed>>} Metrik
 * @throws PDOException Jika query gagal (caller yang menangani fallback)
 */
function get_dashboard_stats(int $user_id): array
{
    $stats = ['total' => 0, 'views' => 0];
    $stmt = getDB()->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(views),0) AS views FROM images WHERE user_id = ? AND is_deleted = FALSE');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $stats = ['total' => (int) $row['total'], 'views' => (int) $row['views']];
    }

    $stmt = getDB()->prepare('SELECT DATE(uploaded_at) AS d, COUNT(*) AS c FROM images WHERE user_id = ? AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(uploaded_at) ORDER BY d');
    $stmt->execute([$user_id]);
    $map = [];
    foreach ($stmt->fetchAll() as $r) {
        $map[$r['d']] = (int) $r['c'];
    }
    $chart_labels = [];
    $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = $d;
        $chart_data[] = $map[$d] ?? 0;
    }

    $stmt = getDB()->prepare('SELECT DATE(viewed_at) AS d, COUNT(*) AS c FROM view_logs v JOIN images i ON i.id = v.image_id WHERE i.user_id = ? AND v.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(viewed_at) ORDER BY d');
    $stmt->execute([$user_id]);
    $vmap = [];
    foreach ($stmt->fetchAll() as $r) {
        $vmap[$r['d']] = (int) $r['c'];
    }
    $views30_labels = [];
    $views30_data = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $views30_labels[] = substr($d, 5);
        $views30_data[] = $vmap[$d] ?? 0;
    }

    $stmt = getDB()->prepare('SELECT filename, public_url, thumbnail_url, views FROM images WHERE user_id = ? AND is_deleted = FALSE ORDER BY views DESC LIMIT 10');
    $stmt->execute([$user_id]);
    $top = $stmt->fetchAll();

    $stmt = getDB()->prepare('SELECT storage_used, storage_quota FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $quota = $stmt->fetch();

    return [
        'total' => $stats['total'],
        'views' => $stats['views'],
        'storage_used' => (int) ($quota['storage_used'] ?? 0),
        'storage_quota' => (int) ($quota['storage_quota'] ?? 0),
        'uploads7' => ['labels' => $chart_labels, 'data' => $chart_data],
        'views30' => ['labels' => $views30_labels, 'data' => $views30_data],
        'top' => $top,
    ];
}
