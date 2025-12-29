<?php
// Tasks API - Cron Jobs Management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
// require_admin(); // Removed global restriction

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    // LIST CRON JOBS
    if ($action === 'list') {
        // Allow all logged in users to see tasks
        $stmt = $pdo->query("SELECT * FROM cron_jobs ORDER BY scheduled_date DESC, scheduled_time DESC LIMIT 100");
        $jobs = $stmt->fetchAll();

        $stats = [
            'pending' => 0,
            'completed' => 0,
            'failed' => 0,
            'recurring' => 0
        ];

        foreach ($jobs as $job) {
            if ($job['status'] === 'pending')
                $stats['pending']++;
            if ($job['status'] === 'completed')
                $stats['completed']++;
            if ($job['status'] === 'failed')
                $stats['failed']++;
            if ($job['is_recurring'])
                $stats['recurring']++;
        }

        json_response(['status' => 'success', 'jobs' => $jobs, 'stats' => $stats]);
    }

    // GET WHATSAPP QUEUE
    if ($action === 'queue') {
        // Allow if user has WA permission or admin
        // But for simplicity let's allow read for all logged in
        $stmt = $pdo->query("SELECT q.*, s.domain FROM whatsapp_queue q LEFT JOIN sites s ON q.site_id = s.id ORDER BY q.scheduled_at DESC LIMIT 50");
        $queue = $stmt->fetchAll();
        json_response(['status' => 'success', 'queue' => $queue]);
    }

    // CANCEL JOB
    if ($action === 'cancel') {
        require_admin();
        $id = $_POST['id'] ?? 0;
        if (!$id)
            json_response(['status' => 'error', 'message' => 'ID gerekli'], 400);

        $stmt = $pdo->prepare("UPDATE cron_jobs SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Görev iptal edildi']);
    }

    // DELETE JOB
    if ($action === 'delete') {
        require_admin();
        $id = $_POST['id'] ?? 0;
        if (!$id)
            json_response(['status' => 'error', 'message' => 'ID gerekli'], 400);

        $stmt = $pdo->prepare("DELETE FROM cron_jobs WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Görev silindi']);
    }

    // DELETE QUEUE ITEM
    if ($action === 'delete_queue') {
        require_admin();
        $id = $_POST['id'] ?? 0;
        if (!$id)
            json_response(['status' => 'error', 'message' => 'ID gerekli'], 400);

        $stmt = $pdo->prepare("DELETE FROM whatsapp_queue WHERE id = ?");
        $stmt->execute([$id]);
        json_response(['status' => 'success', 'message' => 'Mesaj silindi']);
    }

    // CLEAR WHATSAPP LOGS
    if ($action === 'clear_logs') {
        require_admin();
        // Count first
        $count = $pdo->query("SELECT COUNT(*) FROM whatsapp_queue WHERE status IN ('sent', 'failed', 'cancelled')")->fetchColumn();

        // Delete sent, failed and cancelled messages
        $pdo->exec("DELETE FROM whatsapp_queue WHERE status IN ('sent', 'failed', 'cancelled')");

        json_response(['status' => 'success', 'message' => "$count mesaj temizlendi"]);
    }

    // RUN JOB NOW
    if ($action === 'run_now') {
        require_admin();
        $id = $_POST['id'] ?? 0;
        if (!$id)
            json_response(['status' => 'error', 'message' => 'ID gerekli'], 400);

        // Get job
        $stmt = $pdo->prepare("SELECT * FROM cron_jobs WHERE id = ?");
        $stmt->execute([$id]);
        $job = $stmt->fetch();

        if (!$job)
            json_response(['status' => 'error', 'message' => 'Görev bulunamadı'], 404);

        // Just mark it as ready to run
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $currentTime = date('H:i');

        $pdo->prepare("UPDATE cron_jobs SET scheduled_date = ?, scheduled_time = ? WHERE id = ?")
            ->execute([$today, $currentTime, $id]);

        json_response(['status' => 'success', 'message' => 'Görev çalıştırılmak üzere hazırlandı. Cron bir sonraki çalışmada işleyecek.']);
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
