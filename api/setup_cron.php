<?php
// Create or Update Recurring Cron Jobs
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_admin();

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    // Create/Update Daily Mail Reminder Job
    if ($action === 'setup_daily_mail') {
        $time = $_POST['time'] ?? '09:00';
        $email = $_POST['email'] ?? '';

        if (!$time || !$email) {
            json_response(['status' => 'error', 'message' => 'Saat ve email gerekli'], 400);
        }

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Delete existing daily mail jobs
        $pdo->exec("DELETE FROM cron_jobs WHERE job_type = 'daily_mail_reminder'");

        // Create new recurring job
        $stmt = $pdo->prepare("
            INSERT INTO cron_jobs (job_type, job_name, job_data, scheduled_time, scheduled_date, status, is_recurring, next_run_at) 
            VALUES ('daily_mail_reminder', 'Günlük Mail Hatırlatma', ?, ?, ?, 'pending', 1, ?)
        ");
        $jobData = json_encode(['email' => $email]);
        $nextRun = "$tomorrow $time:00";
        $stmt->execute([$jobData, $time, $tomorrow, $nextRun]);

        json_response(['status' => 'success', 'message' => 'Günlük mail görevi oluşturuldu']);
    }

    // Create/Update Daily WhatsApp Reminder Job
    if ($action === 'setup_daily_whatsapp') {
        $time = $_POST['time'] ?? '09:00';
        $phone = $_POST['phone'] ?? '';

        if (!$time || !$phone) {
            json_response(['status' => 'error', 'message' => 'Saat ve telefon gerekli'], 400);
        }

        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Delete existing daily whatsapp jobs
        $pdo->exec("DELETE FROM cron_jobs WHERE job_type = 'daily_whatsapp_reminder'");

        // Create new recurring job
        $stmt = $pdo->prepare("
            INSERT INTO cron_jobs (job_type, job_name, job_data, scheduled_time, scheduled_date, status, is_recurring, next_run_at) 
            VALUES ('daily_whatsapp_reminder', 'Günlük WhatsApp Hatırlatma', ?, ?, ?, 'pending', 1, ?)
        ");
        $jobData = json_encode(['phone' => $phone]);
        $nextRun = "$tomorrow $time:00";
        $stmt->execute([$jobData, $time, $tomorrow, $nextRun]);

        json_response(['status' => 'success', 'message' => 'Günlük WhatsApp görevi oluşturuldu']);
    }

    json_response(['status' => 'error', 'message' => 'Geçersiz action'], 400);

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
