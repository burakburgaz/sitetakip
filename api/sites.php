<?php
// Start output buffering and clean everything
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/logger.php';
require_login();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$user_id = get_current_user_id();
try {
    if ($action === 'list') {
        $filter = $_GET['filter'] ?? 'all';
        $search = $_GET['search'] ?? '';
        $sql = "SELECT s.*, c.full_name as customer_name, c.phone as customer_phone, c.email as customer_email FROM sites s LEFT JOIN customers c ON s.customer_id = c.id WHERE 1=1";
        $params = [];
        if ($filter === 'active') {
            // User requested: "iptal - transfer dışında kalan durumlar aktif... gözükmeye devam edilmeli"
            // Usually 'expired' is separate, but let's include 'requested', 'accepted', 'active'.
            // If the user considers 'expired' as NOT active, I excludes it.
            // But usually "Active Filter" implies "Current Portfolio".
            // Let's rely on status NOT IN ('cancelled', 'transferred') AND renewal_date > NOW (not expired)
            // Or just status IN ('active', 'requested', 'accepted').
            $sql .= " AND s.status IN ('active', 'requested', 'accepted')";
        } elseif ($filter === 'expired') {
            // Expired is based on Date primarily, but status shouldn't be cancelled/transferred
            $sql .= " AND s.status NOT IN ('cancelled', 'transferred') AND DATE(s.renewal_date) < DATE('now')";
        } elseif ($filter === 'upcoming') {
            // "iptal - transfer dışında kalan durumlar... yaklaşanlarda gözükmeli"
            $sql .= " AND s.status NOT IN ('cancelled', 'transferred') AND DATE(s.renewal_date) BETWEEN DATE('now') AND DATE('now', '+30 days')";
        } elseif ($filter === 'cancelled') {
            $sql .= " AND s.status = 'cancelled'";
        } elseif ($filter === 'transferred') {
            $sql .= " AND s.status = 'transferred'";
        }
        if ($search) {
            $sql .= " AND (s.domain LIKE ? OR c.full_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY s.renewal_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sites = $stmt->fetchAll();
        foreach ($sites as &$site) {
            $site['days_until'] = days_until_renewal($site['renewal_date']);
            $site['status_class'] = get_renewal_status($site['days_until']);
            $site['status_color'] = get_status_color($site['status_class']);
        }
        json_response(['status' => 'success', 'data' => $sites]);
    }

    // List all sites (for dropdown selection)
    if ($action === 'list_all') {
        $stmt = $pdo->query("SELECT id, domain, customer_id FROM sites WHERE status = 'active' ORDER BY domain ASC");
        $sites = $stmt->fetchAll();
        json_response(['status' => 'success', 'data' => $sites]);
    }
    if ($action === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT s.*, c.full_name as customer_name FROM sites s LEFT JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
        $stmt->execute([$id]);
        $site = $stmt->fetch();
        if ($site) {
            json_response(['status' => 'success', 'data' => $site]);
        } else {
            json_response(['status' => 'error', 'message' => 'Site bulunamadı'], 404);
        }
    }
    if ($action === 'create') {
        $customer_id = $_POST['customer_id'] ?? 0;
        $domain = sanitize_input($_POST['domain'] ?? '');
        $renewal_date = $_POST['renewal_date'] ?? '';
        $package_type = $_POST['package_type'] ?? 'BASIC';
        $price = floatval($_POST['price'] ?? 0);
        $notes = sanitize_input($_POST['notes'] ?? '');
        if (!$customer_id || !$domain || !$renewal_date) {
            json_response(['status' => 'error', 'message' => 'Gerekli alanları doldurun'], 400);
        }
        $stmt = $pdo->prepare("INSERT INTO sites (customer_id, domain, renewal_date, package_type, price, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer_id, $domain, $renewal_date, $package_type, $price, $notes]);
        $site_id = $pdo->lastInsertId();
        log_activity($pdo, 'Site Eklendi', "Site: $domain | Paket: $package_type | Tarih: $renewal_date");
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => 'Site eklendi', 'id' => $site_id]);
    }
    // UPDATE
    if ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $customer_id = $_POST['customer_id'] ?? 0;
        $domain = sanitize_input($_POST['domain'] ?? '');
        $renewal_date = $_POST['renewal_date'] ?? '';
        $package_type = $_POST['package_type'] ?? 'BASIC';
        $price = floatval($_POST['price'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $notes = sanitize_input($_POST['notes'] ?? '');

        if (!$id || !$customer_id || !$domain || !$renewal_date) {
            json_response(['status' => 'error', 'message' => 'Gerekli alanları doldurun'], 400);
        }

        // Get previous state
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$id]);
        $old_data = $stmt->fetch();

        $stmt = $pdo->prepare("UPDATE sites SET customer_id = ?, domain = ?, renewal_date = ?, package_type = ?, price = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$customer_id, $domain, $renewal_date, $package_type, $price, $status, $notes, $id]);

        log_activity($pdo, 'Site Güncellendi', "Site: $domain | Eski Fiyat: {$old_data['price']} -> $price", ['table' => 'sites', 'data' => $old_data]);
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => 'Site güncellendi']);
    }

    if ($action === 'renew') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$id]);
        $site = $stmt->fetch();

        if (!$site) {
            json_response(['status' => 'error', 'message' => 'Site bulunamadı'], 404);
        }

        $new_date = date('Y-m-d', strtotime($site['renewal_date'] . ' +1 year'));
        $stmt = $pdo->prepare("UPDATE sites SET renewal_date = ?, last_renewed_at = CURRENT_TIMESTAMP, status = 'active' WHERE id = ?");
        $stmt->execute([$new_date, $id]);

        log_activity($pdo, 'Site Yenilendi', "Site: {$site['domain']} | Tarih: {$site['renewal_date']} -> $new_date", ['table' => 'sites', 'data' => $site]);
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => 'Site yenilendi', 'new_date' => $new_date]);
    }

    // UPDATE STATUS
    if ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['active', 'cancelled', 'transferred', 'expired', 'requested', 'accepted'])) {
            json_response(['status' => 'error', 'message' => 'Geçersiz durum'], 400);
        }

        // Get previous state
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$id]);
        $old_data = $stmt->fetch();

        $stmt = $pdo->prepare("UPDATE sites SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $id]);

        log_activity($pdo, 'Durum Değişti', "Site: {$old_data['domain']} | Durum: " . get_status_label($old_data['status']) . " -> " . get_status_label($status), ['table' => 'sites', 'data' => $old_data]);
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => 'Durum güncellendi']);
    }

    // BULK RENEW
    if ($action === 'bulk_renew') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            json_response(['status' => 'error', 'message' => 'Site seçilmedi'], 400);
        }

        // Get previous states for all
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $old_data_list = $stmt->fetchAll();

        $stmt = $pdo->prepare("UPDATE sites SET renewal_date = DATE(renewal_date, '+1 year'), last_renewed_at = CURRENT_TIMESTAMP, status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        $count = $stmt->rowCount();
        log_activity($pdo, 'Toplu Site Yenileme', "$count site yenilendi", ['table' => 'sites', 'data_list' => $old_data_list, 'type' => 'bulk_update']);
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => "$count site başarıyla yenilendi"]);
    }

    // BULK STATUS CHANGE
    if ($action === 'bulk_status') {
        $ids = $_POST['ids'] ?? [];
        $status = $_POST['status'] ?? '';
        if (empty($ids) || !$status) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        // Get previous states
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $old_data_list = $stmt->fetchAll();

        $stmt = $pdo->prepare("UPDATE sites SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
        $params = array_merge([$status], $ids);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        log_activity($pdo, 'Toplu Durum Değişikliği', "$count site " . get_status_label($status) . " yapıldı", ['table' => 'sites', 'data_list' => $old_data_list, 'type' => 'bulk_update']);
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => "$count site başarıyla güncellendi"]);
    }

    // BULK DELETE
    if ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            json_response(['status' => 'error', 'message' => 'Site seçilmedi'], 400);
        }

        // Get previous data
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $old_data_list = $stmt->fetchAll();

        $stmt = $pdo->prepare("DELETE FROM sites WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        $count = $stmt->rowCount();
        log_activity($pdo, 'Toplu Site Silme', "$count site silindi", ['table' => 'sites', 'data_list' => $old_data_list, 'type' => 'bulk_delete']);
        syncToGoogleSheets($pdo);
        json_response(['status' => 'success', 'message' => "$count site başarıyla silindi"]);
    }

    // ADD REMINDER
    if ($action === 'add_reminder') {
        $site_id = $_POST['site_id'] ?? 0;
        $title = sanitize_input($_POST['title'] ?? 'Yenileme Hatırlatması');
        $reminder_date = $_POST['reminder_date'] ?? '';
        $reminder_time = $_POST['reminder_time'] ?? '09:00';
        $note = sanitize_input($_POST['note'] ?? '');
        $alarm_enabled = isset($_POST['alarm_enabled']) ? (int) $_POST['alarm_enabled'] : 0;

        if (!$site_id || !$reminder_date) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        // Get current user ID
        $user_id = $_SESSION['user_id'] ?? 1;

        // Try inserting with alarm_enabled, if column doesn't exist, try without
        try {
            $stmt = $pdo->prepare("
                INSERT INTO reminders (site_id, title, description, reminder_date, reminder_time, status, created_by, created_at, is_notified, alarm_enabled) 
                VALUES (?, ?, ?, ?, ?, 'pending', ?, CURRENT_TIMESTAMP, 0, ?)
            ");
            $stmt->execute([$site_id, $title, $note, $reminder_date, $reminder_time, $user_id, $alarm_enabled]);
        } catch (PDOException $e) {
            // If alarm_enabled column doesn't exist, try without it
            $stmt = $pdo->prepare("
                INSERT INTO reminders (site_id, title, description, reminder_date, reminder_time, status, created_by, created_at, is_notified) 
                VALUES (?, ?, ?, ?, ?, 'pending', ?, CURRENT_TIMESTAMP, 0)
            ");
            $stmt->execute([$site_id, $title, $note, $reminder_date, $reminder_time, $user_id]);
            $alarm_enabled = 0; // Force disable if column doesn't exist
        }
        $id = $pdo->lastInsertId();

        // Create Cron Job if alarm is enabled
        if ($alarm_enabled) {
            $siteDomain = $pdo->query("SELECT domain FROM sites WHERE id = $site_id")->fetchColumn();
            $jobData = json_encode([
                'reminder_id' => $id,
                'site_id' => $site_id,
                'site_domain' => $siteDomain,
                'title' => $title,
                'note' => $note
            ]);

            $cronStmt = $pdo->prepare("
                INSERT INTO cron_jobs (job_type, job_name, job_data, scheduled_time, scheduled_date, status, next_run_at) 
                VALUES ('reminder_alarm', ?, ?, ?, ?, 'pending', ?)
            ");
            $nextRun = "$reminder_date $reminder_time:00";
            $cronStmt->execute([
                "Hatırlatma: $title",
                $jobData,
                $reminder_time,
                $reminder_date,
                $nextRun
            ]);
        }

        log_activity($pdo, 'Hatırlatma Eklendi', "Site ID: $site_id, Başlık: $title, Tarih: $reminder_date $reminder_time, Alarm: " . ($alarm_enabled ? 'Açık' : 'Kapalı'), ['table' => 'reminders', 'id' => $id, 'type' => 'insert']);

        // Send WhatsApp to Admin (Immediate)
        $adminPhone = $pdo->query("SELECT value FROM settings WHERE key = 'daily_reminder_whatsapp_phone'")->fetchColumn();
        if ($adminPhone) {
            // Get Site Domain
            $siteDomain = $pdo->query("SELECT domain FROM sites WHERE id = $site_id")->fetchColumn();

            // Get Settings
            $stmt = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%'");
            $settings = [];
            while ($row = $stmt->fetch())
                $settings[$row['key']] = $row['value'];

            if (!empty($settings['evolution_api_key'])) {
                $phone = preg_replace('/\D/', '', $adminPhone);
                if (substr($phone, 0, 1) === '0')
                    $phone = substr($phone, 1);
                if (substr($phone, 0, 2) !== '90')
                    $phone = '90' . $phone;

                $msg = "*🔔 Yeni Hatırlatma Eklendi*\n\n";
                $msg .= "*Site:* $siteDomain\n";
                $msg .= "*Başlık:* $title\n";
                $msg .= "*Tarih:* " . date('d.m.Y', strtotime($reminder_date)) . " $reminder_time\n";
                if ($note)
                    $msg .= "*Not:* $note\n";
                if ($alarm_enabled)
                    $msg .= "\n🔔 *Alarm Kuruldu*";

                $apiUrl = rtrim($settings['evolution_api_url'] ?? '', '/');
                $instance = $settings['evolution_instance_name'] ?? '';
                $apiKey = $settings['evolution_api_key'] ?? '';
                $endpoint = "$apiUrl/message/sendText/$instance";

                $data = ["number" => $phone, "text" => $msg, "delay" => 1200, "linkPreview" => false];

                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "apikey: $apiKey"]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Fast timeout
                curl_exec($ch); // Fire and forget mostly
                curl_close($ch);
            }
        }

        json_response(['status' => 'success', 'message' => 'Hatırlatma eklendi']);
    }

    // DELETE
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$id]);
        $site = $stmt->fetch();
        if ($site) {
            $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            log_activity($pdo, 'Site Silindi', "Site: {$site['domain']} | Müşteri ID: {$site['customer_id']}", ['table' => 'sites', 'data' => $site]);
            syncToGoogleSheets($pdo);
            json_response(['status' => 'success', 'message' => 'Site silindi']);
        } else {
            json_response(['status' => 'error', 'message' => 'Site bulunamadı'], 404);
        }
    }

    // LOG WHATSAPP (Legacy/Manual)
    if ($action === 'log_whatsapp') {
        // PERMISSION CHECK (DB Source for immediate effect)
        if (!is_admin()) {
            $perm = $pdo->query("SELECT can_send_whatsapp FROM users WHERE id = " . get_current_user_id())->fetchColumn();
            if (!$perm) {
                json_response(['status' => 'error', 'message' => 'WhatsApp gönderme yetkiniz yok.'], 403);
            }
        }

        $id = $_POST['site_id'] ?? 0;
        if ($id) {
            // Get previous state
            $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $old_data = $stmt->fetch();

            // Automatic 'requested' status
            $stmt = $pdo->prepare("UPDATE sites SET whatsapp_sent = 1, whatsapp_sent_at = CURRENT_TIMESTAMP, status = 'requested', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);

            log_activity($pdo, 'WhatsApp Mesajı (Web)', "Site: {$old_data['domain']} | Durum: " . get_status_label($old_data['status']) . " -> " . get_status_label('requested'), ['table' => 'sites', 'data' => $old_data]);
            syncToGoogleSheets($pdo);
            json_response(['status' => 'success', 'message' => 'WhatsApp loglandı']);
        }
    }

    // SEND WHATSAPP API
    if ($action === 'send_whatsapp_api') {
        // PERMISSION CHECK
        if (!is_admin()) {
            $perm = $pdo->query("SELECT can_send_whatsapp FROM users WHERE id = " . get_current_user_id())->fetchColumn();
            if (!$perm) {
                json_response(['status' => 'error', 'message' => 'WhatsApp gönderme yetkiniz yok.'], 403);
            }
        }

        $id = $_POST['site_id'] ?? 0;
        $phone = $_POST['phone'] ?? '';
        $message = $_POST['message'] ?? '';

        if (!$id || !$phone || !$message) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        // Get Settings
        $stmt = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%'");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }

        $apiUrl = $settings['evolution_api_url'] ?? '';
        $instance = $settings['evolution_instance_name'] ?? '';
        $apiKey = $settings['evolution_api_key'] ?? '';

        if (!$apiUrl || !$instance || !$apiKey) {
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        // Send via Evolution API
        $apiUrl = rtrim($apiUrl, '/');
        $endpoint = "$apiUrl/message/sendText/$instance";

        $data = [
            "number" => $phone,
            "text" => $message,
            "delay" => 1200,
            "linkPreview" => false
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "apikey: $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            json_response(['status' => 'error', 'message' => 'CURL Hatası: ' . $err], 500);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            // Success - Update Site Status
            $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$id]);
            $old_data = $stmt->fetch();

            $stmt = $pdo->prepare("UPDATE sites SET whatsapp_sent = 1, whatsapp_sent_at = CURRENT_TIMESTAMP, status = 'requested', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$id]);

            log_activity($pdo, 'WhatsApp Mesajı (API)', "Site: {$old_data['domain']} | Telefon: $phone | Durum: " . get_status_label('requested'), ['table' => 'sites', 'data' => $old_data]);
            syncToGoogleSheets($pdo);
            json_response(['status' => 'success', 'message' => 'Mesaj gönderildi']);
        } else {
            json_response(['status' => 'error', 'message' => 'API Hatası: ' . $response], 400);
        }
    }

    // SCHEDULE WHATSAPP
    if ($action === 'schedule_whatsapp') {
        // PERMISSION CHECK
        if (!is_admin()) {
            $perm = $pdo->query("SELECT can_send_whatsapp FROM users WHERE id = " . get_current_user_id())->fetchColumn();
            if (!$perm) {
                json_response(['status' => 'error', 'message' => 'WhatsApp gönderme yetkiniz yok.'], 403);
            }
        }

        $id = $_POST['site_id'] ?? 0;
        $phone = $_POST['phone'] ?? '';
        $message = $_POST['message'] ?? '';
        $scheduled_at = $_POST['scheduled_at'] ?? '';
        $scheduled_at = str_replace('T', ' ', $scheduled_at); // Fix datetime-local format

        if (!$id || !$phone || !$message || !$scheduled_at) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO whatsapp_queue (site_id, phone, message, scheduled_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $phone, $message, $scheduled_at]);

        // Log
        $siteStmt = $pdo->prepare("SELECT domain FROM sites WHERE id = ?");
        $siteStmt->execute([$id]);
        $domain = $siteStmt->fetchColumn();

        log_activity($pdo, 'WhatsApp Mesajı Planlandı', "Site: $domain | Tarih: $scheduled_at | Telefon: $phone");
        json_response(['status' => 'success', 'message' => 'Planlandı']);
    }

    // BULK WHATSAPP SCHEDULE
    if ($action === 'bulk_whatsapp_schedule') {
        // PERMISSION CHECK
        if (!is_admin()) {
            $perm = $pdo->query("SELECT can_send_whatsapp FROM users WHERE id = " . get_current_user_id())->fetchColumn();
            if (!$perm) {
                json_response(['status' => 'error', 'message' => 'WhatsApp gönderme yetkiniz yok.'], 403);
            }
        }

        $ids = $_POST['ids'] ?? [];
        $template = $_POST['message_template'] ?? '';
        $scheduled_at = $_POST['scheduled_at'] ?? '';

        if (empty($ids) || !$template) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        // If no schedule provided, set to NOW so cron picks it up immediately
        if (!$scheduled_at) {
            $scheduled_at = date('Y-m-d H:i:s');
        } else {
            $scheduled_at = str_replace('T', ' ', $scheduled_at);
        }

        // Fetch details for all selected sites
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "
            SELECT s.id as site_id, s.domain, s.renewal_date, s.package_type, 
                   c.full_name, c.phone 
            FROM sites s 
            JOIN customers c ON s.customer_id = c.id 
            WHERE s.id IN ($placeholders)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);
        $sites = $stmt->fetchAll();

        $queuedCount = 0;

        foreach ($sites as $site) {
            if (!$site['phone'])
                continue;

            // Normalize Phone
            $phone = preg_replace('/\D/', '', $site['phone']);
            if (substr($phone, 0, 1) === '0')
                $phone = substr($phone, 1);
            if (substr($phone, 0, 2) !== '90')
                $phone = '90' . $phone;

            // Parse Template
            $msg = $template;
            $msg = str_replace('[ADI SOYADI]', $site['full_name'], $msg);
            $msg = str_replace('[SITE]', $site['domain'], $msg);
            $msg = str_replace('[TARIH]', date('d.m.Y', strtotime($site['renewal_date'])), $msg);
            $msg = str_replace('[PAKET]', $site['package_type'], $msg);

            // Add to Queue
            $qStmt = $pdo->prepare("INSERT INTO whatsapp_queue (site_id, phone, message, scheduled_at) VALUES (?, ?, ?, ?)");
            $qStmt->execute([$site['site_id'], $phone, $msg, $scheduled_at]);
            $queuedCount++;
        }

        log_activity($pdo, 'Toplu WhatsApp Planlandı', "$queuedCount mesaj kuyruğa eklendi. Tarih: $scheduled_at");
        json_response(['status' => 'success', 'message' => "$queuedCount mesaj gönderim kuyruğuna eklendi."]);
    }

} catch (PDOException $e) {
    log_error('Sites API Error', ['error' => $e->getMessage()]);
    json_response(['status' => 'error', 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
