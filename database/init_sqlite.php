<?php
declare(strict_types=1);

/**
 * CLI script to initialize the SQLite database for SouqLink.
 * Usage: php database/init_sqlite.php [--force]
 */

require_once dirname(__DIR__) . '/src/Database.php';

use SouqLink\Database;

$force = in_array('--force', $argv, true);
$dbPath = Database::getDatabasePath();

echo "== SouqLink SQLite Database Initializer ==" . PHP_EOL;
echo "Target path: {$dbPath}" . PHP_EOL;

if ($force && is_file($dbPath)) {
    echo "Force flag detected. Removing existing database..." . PHP_EOL;
    unlink($dbPath);
    if (is_file($dbPath . '-wal')) @unlink($dbPath . '-wal');
    if (is_file($dbPath . '-shm')) @unlink($dbPath . '-shm');
}

$db = Database::connection();
$tables = (int)$db->query("SELECT count(*) FROM sqlite_master WHERE type='table'")->fetchColumn();
$cities = (int)$db->query("SELECT count(*) FROM marketplace_locations WHERE location_type='city'")->fetchColumn();

echo "Database initialized successfully!" . PHP_EOL;
echo "- Tables count: {$tables}" . PHP_EOL;
echo "- Seeded cities: {$cities}" . PHP_EOL;
