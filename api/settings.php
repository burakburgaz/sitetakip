<?php
// Start output buffering and clean everything
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
// api/settings.php - Sistem ayarları API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_security.php';

require_login();

try {
    $action = $_REQUEST['action'] ?? ''; // Get action from GET or POST

    // --- IP ACCESS MANAGEMENT ---

    // Get IP Restriction Status
    if ($action === 'get_ip_restriction') {
        require_admin();
        $stmt = $pdo->query("SELECT value FROM settings WHERE key = 'system_api_ip_restriction'");
        $val = $stmt->fetchColumn();
        json_response(['status' => 'success', 'enabled' => ($val === '1')]);
    }

    // Toggle IP Restriction
    if ($action === 'toggle_ip_restriction') {
        require_admin();
        $val = ($_POST['value'] === '1') ? '1' : '0';
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('system_api_ip_restriction', ?)")->execute([$val]);
        log_activity($pdo, 'IP Kısıtlaması Değiştirildi', "Durum: " . ($val === '1' ? 'Aktif' : 'Pasif'));
        json_response(['status' => 'success']);
    }

    // List IPs
    if ($action === 'list_ips') {
        require_admin();
        $stmt = $pdo->query("SELECT * FROM ip_whitelist ORDER BY created_at DESC");
        json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
    }

    // Add IP
    if ($action === 'add_ip') {
        require_admin();
        $ip = trim($_POST['ip_address'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (!$ip) {
            json_response(['status' => 'error', 'message' => 'IP adresi gerekli'], 400);
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            json_response(['status' => 'error', 'message' => 'Geçersiz IP adresi formatı'], 400);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO ip_whitelist (ip_address, description, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$ip, $desc, $_SESSION['user_id'] ?? null]);
            log_activity($pdo, 'IP Whitelist Eklendi', "IP: $ip");
            json_response(['status' => 'success', 'message' => 'IP adresi eklendi']);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                json_response(['status' => 'error', 'message' => 'Bu IP adresi zaten listede'], 400);
            }
            json_response(['status' => 'error', 'message' => 'Veritabanı hatası'], 500);
        }
    }

    // Delete IP
    if ($action === 'delete_ip') {
        require_admin();
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM ip_whitelist WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, 'IP Whitelist Silindi', "ID: $id");
        json_response(['status' => 'success', 'message' => 'IP adresi silindi']);
    }

    // Get API Logs
    if ($action === 'get_api_logs') {
        require_admin();
        $limit = 100;
        $status = $_REQUEST['status'] ?? '';

        $sql = "SELECT * FROM api_access_logs";
        $params = [];

        if ($status === 'denied') {
            $sql .= " WHERE status = 'denied'";
        } elseif ($status === 'allowed') {
            $sql .= " WHERE status = 'allowed'";
        }

        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
    }

    // Clear API Logs (Manual)
    if ($action === 'clear_api_logs') {
        require_admin();
        $pdo->exec("DELETE FROM api_access_logs");
        // Reset auto increment? Optional.
        log_activity($pdo, 'API Logları Temizlendi (Manuel)', '');
        json_response(['status' => 'success', 'message' => 'Tüm erişim logları temizlendi.']);
    }

    if ($action === 'save_admin_wa') {
        require_admin(); // Sadece admin değiştirebilir
        $phone = $_POST['daily_whatsapp_phone'] ?? '';
        $time = $_POST['daily_whatsapp_time'] ?? '';

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('daily_whatsapp_phone', ?)");
        $stmt->execute([$phone]);
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('daily_whatsapp_time', ?)");
        $stmt->execute([$time]);

        log_activity($pdo, 'Admin WhatsApp Ayarları Güncellendi', '');
        json_response(['status' => 'success', 'message' => 'Ayarlar kaydedildi']);
    }

    // Get Queue
    if ($action === 'get_wa_queue') {
        $stmt = $pdo->query("SELECT q.*, s.domain, strftime('%d.%m.%Y %H:%M', q.scheduled_at) as scheduled_at_formatted 
                             FROM whatsapp_queue q 
                             LEFT JOIN sites s ON q.site_id = s.id 
                             WHERE q.status = 'pending' 
                             ORDER BY q.scheduled_at ASC");
        $data = $stmt->fetchAll();
        json_response(['status' => 'success', 'data' => $data]);
    }

    // Delete Queue Item
    if ($action === 'delete_wa_queue') {
        require_admin(); // Sadece admin değiştirebilir
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("UPDATE whatsapp_queue SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$id]);
        log_activity($pdo, 'WhatsApp Kuyruk Öğesi İptal Edildi', 'ID: ' . $id);
        json_response(['status' => 'success', 'message' => 'Silindi']);
    }

    // Edit Queue Item
    if ($action === 'edit_wa_queue') {
        require_admin();
        $id = $_POST['id'] ?? 0;
        $message = $_POST['message'] ?? '';
        $scheduled_at = $_POST['scheduled_at'] ?? '';
        $scheduled_at = str_replace('T', ' ', $scheduled_at);

        if (!$id || !$message || !$scheduled_at) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        $stmt = $pdo->prepare("UPDATE whatsapp_queue SET message = ?, scheduled_at = ? WHERE id = ?");
        $stmt->execute([$message, $scheduled_at, $id]);

        log_activity($pdo, 'WhatsApp Kuyruk Öğesi Düzenlendi', 'ID: ' . $id);
        json_response(['status' => 'success', 'message' => 'Güncellendi']);
    }

    // Send Queue Item Now
    if ($action === 'send_wa_queue_now') {
        require_admin();
        $id = $_POST['id'] ?? 0;

        // Fetch Item
        $stmt = $pdo->prepare("SELECT q.*, s.domain FROM whatsapp_queue q LEFT JOIN sites s ON q.site_id = s.id WHERE q.id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();

        if (!$item) {
            json_response(['status' => 'error', 'message' => 'Bulunamadı'], 404);
        }

        // Get Credentials
        $evo_url = $pdo->query("SELECT value FROM settings WHERE key = 'evolution_api_url'")->fetchColumn();
        $evo_instance = $pdo->query("SELECT value FROM settings WHERE key = 'evolution_instance_name'")->fetchColumn();
        $evo_key = $pdo->query("SELECT value FROM settings WHERE key = 'evolution_api_key'")->fetchColumn();

        if (!$evo_url || !$evo_instance || !$evo_key) {
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        // Prepare API
        $apiUrl = rtrim($evo_url, '/');
        $endpoint = "$apiUrl/message/sendText/$evo_instance";
        $data = [
            "number" => $item['phone'],
            "text" => $item['message'],
            "delay" => 1,
            "linkPreview" => false
        ];

        // CURL
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "apikey: $evo_key"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $pdo->prepare("UPDATE whatsapp_queue SET status = 'sent' WHERE id = ?")->execute([$id]);

            if ($item['site_id']) {
                $pdo->prepare("UPDATE sites SET whatsapp_sent = 1, whatsapp_sent_at = CURRENT_TIMESTAMP, status = 'requested', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$item['site_id']]);
                log_activity($pdo, 'Zamanlı Mesaj Gönderildi (Manuel)', "Site: {$item['domain']} | Telefon: {$item['phone']}");
            }
            json_response(['status' => 'success', 'message' => 'Gönderildi']);
        } else {
            json_response(['status' => 'error', 'message' => 'API Hatası: ' . $response], 400);
        }
    }

    // Send Admin WA Now
    if ($action === 'send_admin_wa_now') {
        require_admin();

        $dailyWaPhone = $pdo->query("SELECT value FROM settings WHERE key = 'daily_whatsapp_phone'")->fetchColumn();
        // Fallback to post if not saved yet? For now assume saved. 
        // Or if user just typed it, we might want to capture it. But let's stick to DB to force save.

        if (!$dailyWaPhone) {
            json_response(['status' => 'error', 'message' => 'Önce telefon numarasını kaydedin.'], 400);
        }

        $evo_url = $pdo->query("SELECT value FROM settings WHERE key = 'evolution_api_url'")->fetchColumn();
        $evo_instance = $pdo->query("SELECT value FROM settings WHERE key = 'evolution_instance_name'")->fetchColumn();
        $evo_key = $pdo->query("SELECT value FROM settings WHERE key = 'evolution_api_key'")->fetchColumn();

        if (!$evo_url || !$evo_instance || !$evo_key) {
            json_response(['status' => 'error', 'message' => 'Evolution API ayarları eksik.'], 400);
        }

        // Clean Phone
        $phone = preg_replace('/\D/', '', $dailyWaPhone);
        if (substr($phone, 0, 1) === '0')
            $phone = substr($phone, 1);
        if (substr($phone, 0, 2) !== '90')
            $phone = '90' . $phone;

        // Fetch Data
        $sql = "SELECT s.domain, s.renewal_date, s.status, c.full_name 
                FROM sites s 
                JOIN customers c ON s.customer_id = c.id 
                WHERE s.status IN ('requested', 'accepted', 'waiting') 
                AND DATE(s.renewal_date) BETWEEN DATE('now') AND DATE('now', '+30 days') 
                ORDER BY s.renewal_date ASC";
        $upcoming = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (empty($upcoming)) {
            json_response(['status' => 'info', 'message' => 'Yaklaşan (30 gün) site bulunamadı.'], 200);
        }

        $msg = "*Yönetici Raporu - " . date('d.m.Y H:i') . "*\n\n";
        $msg .= "Yaklaşan " . count($upcoming) . " site kaydı:\n\n";

        foreach ($upcoming as $site) {
            $days = days_until_renewal($site['renewal_date']);
            $statusLabel = get_status_label($site['status']); // Using the helper from functions.php

            $msg .= "🌐 *{$site['domain']}*\n";
            $msg .= "👤 {$site['full_name']}\n";
            $msg .= "📊 Durum: {$statusLabel}\n";
            $msg .= "📅 " . format_date($site['renewal_date']) . " (Kalan: $days gün)\n";
            $msg .= "-------------------\n";
        }

        // Send
        $apiUrl = rtrim($evo_url, '/');
        $endpoint = "$apiUrl/message/sendText/$evo_instance";
        $data = [
            "number" => $phone,
            "text" => $msg,
            "delay" => 1,
            "linkPreview" => false
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "apikey: $evo_key"]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            json_response(['status' => 'success', 'message' => 'Rapor gönderildi']);
        } else {
            json_response(['status' => 'error', 'message' => 'API Hatası: ' . $response], 400);
        }
    }

    // LIST (GET)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Tüm ayarları getir
        $stmt = $pdo->query("SELECT key, value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }

        // Hide sensitive data for non-admins
        if (!is_admin()) {
            $sensitive_keys = [
                'evolution_api_key',
                'hostinger_api_key',
                'smtp_password',
                'google_sheets_client_secret',
                'google_sheets_access_token'
            ];
            foreach ($sensitive_keys as $key) {
                if (!empty($settings[$key])) {
                    $settings[$key] = '********'; // Mask but keep truthy
                }
            }
        }

        json_response($settings);
    }

    // SAVE API Keys (Evolution, Hostinger, GS)
    if ($action === 'save_all_api') {
        require_admin(); // Sadece admin değiştirebilir

        $hostinger_api_key = $_POST['hostinger_api_key'] ?? '';
        $webhook_url = $_POST['google_sheets_webhook_url'] ?? '';
        $sync_enabled = $_POST['google_sheets_sync_enabled'] ?? '0';

        // Evolution
        $evo_url = $_POST['evolution_api_url'] ?? '';
        $evo_instance = $_POST['evolution_instance_name'] ?? '';
        $evo_key = $_POST['evolution_api_key'] ?? '';

        $params = [
            'hostinger_api_key' => $hostinger_api_key,
            'google_sheets_webhook_url' => $webhook_url,
            'google_sheets_sync_enabled' => $sync_enabled,
            'evolution_api_url' => $evo_url,
            'evolution_instance_name' => $evo_instance,
            'evolution_api_key' => $evo_key
        ];

        foreach ($params as $key => $value) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        log_activity($pdo, 'API Ayarları Güncellendi', '');
        json_response(['status' => 'success', 'message' => 'Ayarlar kaydedildi']);
    }

    // If it's a POST request but no specific action was handled,
    // it means it's the generic settings update.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
        require_admin(); // Sadece admin değiştirebilir

        foreach ($_POST as $key => $value) {
            // Skip 'action' if it somehow made it here, or other non-setting fields
            if ($key === 'action')
                continue;

            $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
            $stmt->execute([$key, $value]);
        }

        // Recreate daily recurring jobs if their times changed
        $dailyMailTime = $_POST['daily_reminder_time'] ?? null;
        $dailyMailEmail = $_POST['daily_reminder_email'] ?? null;
        $dailyWaTime = $_POST['daily_whatsapp_time'] ?? null;
        $dailyWaPhone = $_POST['daily_whatsapp_phone'] ?? null;

        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        // Daily mail reminder
        if ($dailyMailTime && $dailyMailEmail) {
            // Delete existing pending jobs
            $pdo->exec("DELETE FROM cron_jobs WHERE job_type = 'daily_mail_reminder' AND status = 'pending'");

            // Create new job
            $stmt = $pdo->prepare("
                INSERT INTO cron_jobs (job_type, job_name, job_data, scheduled_time, scheduled_date, status, is_recurring, next_run_at) 
                VALUES ('daily_mail_reminder', 'Günlük Mail Hatırlatma', ?, ?, ?, 'pending', 1, ?)
            ");
            $jobData = json_encode(['email' => $dailyMailEmail]);
            $nextRun = "$tomorrow $dailyMailTime:00";
            $stmt->execute([$jobData, $dailyMailTime, $tomorrow, $nextRun]);
        }

        // Daily WhatsApp reminder  
        if ($dailyWaTime && $dailyWaPhone) {
            // Delete existing pending jobs
            $pdo->exec("DELETE FROM cron_jobs WHERE job_type = 'daily_whatsapp_reminder' AND status = 'pending'");

            // Create new job
            $stmt = $pdo->prepare("
                INSERT INTO cron_jobs (job_type, job_name, job_data, scheduled_time, scheduled_date, status, is_recurring, next_run_at) 
                VALUES ('daily_whatsapp_reminder', 'Yönetici Günlük WhatsApp Hatırlatma', ?, ?, ?, 'pending', 1, ?)
            ");
            $jobData = json_encode(['phone' => $dailyWaPhone]);
            $nextRun = "$tomorrow $dailyWaTime:00";
            $stmt->execute([$jobData, $dailyWaTime, $tomorrow, $nextRun]);
        }

        log_activity($pdo, 'Ayarlar Güncellendi', '');
        json_response(['status' => 'success', 'message' => 'Ayarlar kaydedildi']);
    }


} catch (Exception $e) {
    log_error('Settings API Error', ['error' => $e->getMessage()]);
    json_response(['status' => 'error', 'message' => 'Bir hata oluştu'], 500);
}
