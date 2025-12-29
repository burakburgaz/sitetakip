<?php
// Quick fix for reminders - add to sites.php after add_reminder

// UPDATE/DELETE/COMPLETE REMINDER actions
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

// GET REMINDER
if ($action === 'get') {
    $id = $_GET['id'] ?? 0;
    if (!$id)
        json_response(['status' => 'error'], 400);

    $stmt = $pdo->prepare("SELECT * FROM reminders WHERE id = ?");
    $stmt->execute([$id]);
    $reminder = $stmt->fetch();

    if (!$reminder)
        json_response(['status' => 'error'], 404);

    json_response(['status' => 'success', 'data' => $reminder]);
}

// UPDATE REMINDER
if ($action === 'update') {
    $id = $_POST['id'] ?? 0;
    $title = sanitize_input($_POST['title'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '09:00';
    $note = sanitize_input($_POST['note'] ?? '');

    if (!$id || !$title || !$date)
        json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);

    // Update reminder
    $pdo->prepare("UPDATE reminders SET title = ?, description = ?, reminder_date = ?, reminder_time = ?, snoozed_until = ? WHERE id = ?")
        ->execute([$title, $note, $date, $time, $date, $id]);

    // Update related cron job - find by parsing JSON
    $stmt = $pdo->query("SELECT id, job_data FROM cron_jobs WHERE job_type = 'reminder_alarm' AND status = 'pending'");
    while ($job = $stmt->fetch()) {
        $data = json_decode($job['job_data'], true);
        if (isset($data['reminder_id']) && $data['reminder_id'] == $id) {
            $pdo->prepare("UPDATE cron_jobs SET scheduled_date = ?, scheduled_time = ?, next_run_at = ? WHERE id = ?")
                ->execute([$date, $time, "$date $time:00", $job['id']]);
        }
    }

    json_response(['status' => 'success']);
}

// COMPLETE REMINDER
if ($action === 'complete') {
    $id = $_POST['id'] ?? 0;
    if (!$id)
        json_response(['status' => 'error'], 400);

    // Mark reminder as completed
    $pdo->prepare("UPDATE reminders SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id]);

    // Delete related cron job - find by parsing JSON
    $stmt = $pdo->query("SELECT id, job_data FROM cron_jobs WHERE job_type = 'reminder_alarm' AND status = 'pending'");
    while ($job = $stmt->fetch()) {
        $data = json_decode($job['job_data'], true);
        if (isset($data['reminder_id']) && $data['reminder_id'] == $id) {
            $pdo->prepare("DELETE FROM cron_jobs WHERE id = ?")->execute([$job['id']]);
        }
    }

    json_response(['status' => 'success']);
}

// ADD NOTE
if ($action === 'add_note') {
    $id = $_POST['id'] ?? 0;
    $note = sanitize_input($_POST['note'] ?? '');

    if (!$id)
        json_response(['status' => 'error'], 400);

    $pdo->prepare("UPDATE reminders SET description = ? WHERE id = ?")->execute([$note, $id]);
    json_response(['status' => 'success']);
}

// DELETE REMINDER
if ($action === 'delete_reminder') {
    $id = $_POST['id'] ?? 0;
    if (!$id)
        json_response(['status' => 'error'], 400);

    // Delete reminder
    $pdo->prepare("DELETE FROM reminders WHERE id = ?")->execute([$id]);

    // Delete related cron job - find by parsing JSON
    $stmt = $pdo->query("SELECT id, job_data FROM cron_jobs WHERE job_type = 'reminder_alarm' AND status = 'pending'");
    while ($job = $stmt->fetch()) {
        $data = json_decode($job['job_data'], true);
        if (isset($data['reminder_id']) && $data['reminder_id'] == $id) {
            $pdo->prepare("DELETE FROM cron_jobs WHERE id = ?")->execute([$job['id']]);
        }
    }

    json_response(['status' => 'success']);
}

// SNOOZE REMINDER
if ($action === 'snooze_reminder') {
    $id = $_POST['id'] ?? 0;
    $new_date = $_POST['snoozed_until'] ?? '';
    $new_time = $_POST['reminder_time'] ?? '09:00';

    if (!$id || !$new_date)
        json_response(['status' => 'error'], 400);

    // Update reminder
    $pdo->prepare("UPDATE reminders SET snoozed_until = ?, reminder_date = ?, reminder_time = ? WHERE id = ?")->execute([$new_date, $new_date, $new_time, $id]);

    // Update related cron job - find by parsing JSON
    $stmt = $pdo->query("SELECT id, job_data FROM cron_jobs WHERE job_type = 'reminder_alarm' AND status = 'pending'");
    while ($job = $stmt->fetch()) {
        $data = json_decode($job['job_data'], true);
        if (isset($data['reminder_id']) && $data['reminder_id'] == $id) {
            $pdo->prepare("UPDATE cron_jobs SET scheduled_date = ?, scheduled_time = ?, next_run_at = ? WHERE id = ?")
                ->execute([$new_date, $new_time, "$new_date $new_time:00", $job['id']]);
        }
    }

    json_response(['status' => 'success']);
}
