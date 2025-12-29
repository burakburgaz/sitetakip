<?php
// api/logs.php - Aktivite Logları API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

while (ob_get_level())
    ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_login();
require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    if ($action === 'list') {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;

        $sql = "
            SELECT a.*, u.name_surname as user_name 
            FROM activity_logs a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
            LIMIT $limit
        ";

        $logs = $pdo->query($sql)->fetchAll();

        // Format dates
        foreach ($logs as &$log) {
            $log['date_formatted'] = date('d.m.Y H:i', strtotime($log['created_at']));
        }

        json_response(['status' => 'success', 'data' => $logs]);
    }

    // Undo Action
    if ($action === 'undo') {
        $id = $_POST['id'] ?? 0;

        $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE id = ?");
        $stmt->execute([$id]);
        $log = $stmt->fetch();

        if (!$log || empty($log['previous_data'])) {
            json_response(['status' => 'error', 'message' => 'Geri alınabilir veri bulunamadı'], 400);
        }

        $prev_data = json_decode($log['previous_data'], true);
        if (!$prev_data || empty($prev_data['table'])) {
            json_response(['status' => 'error', 'message' => 'Geçersiz geri alma verisi'], 400);
        }

        $table = $prev_data['table'];
        // Allowed tables check for security
        if (!in_array($table, ['sites', 'customers', 'reminders'])) {
            json_response(['status' => 'error', 'message' => 'Bu tablo için geri alma desteklenmiyor'], 400);
        }

        $pdo->beginTransaction();

        try {
            // Function to restore a single row
            $restoreRow = function ($table, $data) use ($pdo) {
                // Determine if we need to INSERT (if it was deleted) or UPDATE (if it was updated)
                // However, previous_data represents the state BEFORE the action.
                // If the action destroyed the row (DELETE), we need to INSERT it back using this data.
                // If the action modified the row (UPDATE), we need to UPDATE it back using this data.
                // We can try to check if record exists?
                // Or simply rely on "REPLACE INTO" or "INSERT OR REPLACE"? 
                // "INSERT OR REPLACE" is risky if we just want to update.
                // Better strategy: Check if ID exists.

                // If ID exists, we UPDATE. If not, we INSERT.
                // This covers: 
                // 1. Undo Delete -> ID is gone -> INSERT restored data.
                // 2. Undo Update -> ID is there -> UPDATE with old data.

                $id = $data['id'];
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE id = ?");
                $stmt->execute([$id]);
                $exists = $stmt->fetchColumn() > 0;

                $columns = array_keys($data);

                if ($exists) {
                    // Update
                    $setClause = implode(', ', array_map(fn($c) => "$c = ?", $columns));
                    $sql = "UPDATE $table SET $setClause WHERE id = ?";
                    $values = array_values($data);
                    $values[] = $id;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values);
                } else {
                    // Insert
                    $cols = implode(', ', $columns);
                    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                    $sql = "INSERT INTO $table ($cols) VALUES ($placeholders)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_values($data));
                }
            };

            if (isset($prev_data['data_list'])) {
                // Bulk operation
                foreach ($prev_data['data_list'] as $row) {
                    $restoreRow($table, $row);
                }
            } elseif (isset($prev_data['data'])) {
                // Single operation
                $restoreRow($table, $prev_data['data']);
            } elseif (isset($prev_data['type']) && $prev_data['type'] === 'insert' && isset($prev_data['id'])) {
                // Special case: Undo of an INSERT action (e.g. Add Site).
                // "undo" means DELETE the inserted row.
                // previous_data for 'insert' should ideally contain just ID or 'type'='insert'.
                // In api/sites.php add_reminder, we logged 'type'=>'insert', 'id'=>$id.
                $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
                $stmt->execute([$prev_data['id']]);
            } else {
                throw new Exception("Tanınmayan veri formatı");
            }

            // Remove the log after undoing? Or keep it?
            // Usually we keep it but mark as undone, OR we add a new log "Undo Action performed".
            // Let's Log the Undo.
            log_activity($pdo, 'Geri Alma İşlemi', "Log ID: $id geri alındı");

            $pdo->commit();
            json_response(['status' => 'success', 'message' => 'İşlem başarıyla geri alındı']);

        } catch (Exception $e) {
            $pdo->rollBack();
            json_response(['status' => 'error', 'message' => 'Geri alma hatası: ' . $e->getMessage()], 500);
        }
    }

    if ($action === 'clear') {
        $pdo->exec("DELETE FROM activity_logs");
        log_activity($pdo, 'Loglar Temizlendi', 'Tüm geçmiş silindi');
        json_response(['status' => 'success', 'message' => 'Geçmiş temizlendi']);
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
