<?php
// scripts/db_cli.php - Database connection for CLI scripts (No die())

try {
    $db_file = __DIR__ . '/../database.sqlite';
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 10); // Wait 10s for lock
} catch (PDOException $e) {
    // Log error to file instead of dying to stdout
    if (function_exists('log_sync')) {
        log_sync("DB Connect Error: " . $e->getMessage());
    } else {
        file_put_contents(__DIR__ . '/../sync_debug.log', date('Y-m-d H:i:s') . " - DB Connect Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
    exit;
}
?>