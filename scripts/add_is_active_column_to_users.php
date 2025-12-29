<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // Check if is_active column exists
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasIsActive = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'is_active') {
            $hasIsActive = true;
            break;
        }
    }

    if (!$hasIsActive) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1");
        echo "Column 'is_active' added to 'users' table.\n";
    } else {
        echo "Column 'is_active' already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
