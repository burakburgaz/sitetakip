<?php
// Migration script - Add is_read column to whatsapp_messages
require_once __DIR__ . '/../includes/db.php';

try {
    // Check if column exists
    $checkSql = "PRAGMA table_info(whatsapp_messages)";
    $stmt = $pdo->query($checkSql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasIsRead = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'is_read') {
            $hasIsRead = true;
            break;
        }
    }

    if (!$hasIsRead) {
        echo "Adding is_read column to whatsapp_messages...\n";
        $pdo->exec("ALTER TABLE whatsapp_messages ADD COLUMN is_read INTEGER DEFAULT 0");

        // Mark all existing messages from me as read
        $pdo->exec("UPDATE whatsapp_messages SET is_read = 1 WHERE from_me = 1");

        echo "✅ Migration completed successfully!\n";
        echo "- Added is_read column\n";
        echo "- Marked all outgoing messages as read\n";
    } else {
        echo "ℹ️ Column is_read already exists. No migration needed.\n";
    }

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
