<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // 1. Add columns to users table
    $alterCommands = [
        "ALTER TABLE users ADD COLUMN wa_2fa_enabled INTEGER DEFAULT 0",
        "ALTER TABLE users ADD COLUMN wa_2fa_code TEXT",
        "ALTER TABLE users ADD COLUMN wa_2fa_expiry TEXT"
    ];

    foreach ($alterCommands as $cmd) {
        try {
            $pdo->exec($cmd);
            echo "Executed: $cmd\n";
        } catch (PDOException $e) {
            // Ignore if column exists
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "Column already exists: $cmd\n";
            } else {
                echo "Error executing $cmd: " . $e->getMessage() . "\n";
            }
        }
    }

    // 2. Create login_logs table
    $createTableCmd = "CREATE TABLE IF NOT EXISTS login_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        ip_address TEXT,
        status TEXT, -- success, failed, 2fa_sent, 2fa_verified
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    try {
        $pdo->exec($createTableCmd);
        echo "Executed: Create login_logs table\n";
    } catch (PDOException $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
    }

    echo "Database update completed successfully.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
