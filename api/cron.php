<?php
// api/cron.php - Lightweight background task runner
// Called via AJAX from the dashboard/sidebar to process pending tasks.

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api_security.php';

header('Content-Type: application/json');

// Prevent direct access if needed, but usually harmless as it just syncs
// We can check if request is AJAX or limit frequency here if we want.

try {
    // 1. Check Google Sheets Sync
    // ===========================
    $lastChange = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_last_change'")->fetchColumn();
    $lastSync = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_last_sync'")->fetchColumn();
    $isSyncing = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_is_syncing'")->fetchColumn();
    $syncStartTime = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_sync_start_time'")->fetchColumn();
    $syncEnabled = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_sync_enabled'")->fetchColumn();

    // Default enabled if not set, or treat 0 as disabled
    // If key missing, assume enabled or disabled? Let's assume disabled if explicit control requested.
    // Better: if ($syncEnabled !== '0') => enabled by default. User asked for "off button".
    // Let's coerce to int.
    $isSyncEnabled = ($syncEnabled === '1');

    // Defaults
    if (!$lastChange)
        $lastChange = 0;
    if (!$lastSync)
        $lastSync = 0;

    // Check for stale lock (older than 2 minutes)
    if ($isSyncing && (time() - $syncStartTime > 120)) {
        $isSyncing = 0; // Break lock
    }

    if ($isSyncEnabled && $lastChange > $lastSync && !$isSyncing) {
        // LOCK
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_is_syncing', 1)")->execute();
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_sync_start_time', ?)")->execute([time()]);

        // RUN SYNC LOGIC
        // Get URL
        $stmt = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_webhook_url'");
        $url = $stmt->fetchColumn();

        if ($url && strpos($url, '/exec') !== false) {
            $sql = "
                SELECT 
                    s.domain, 
                    c.full_name as customer_name, 
                    c.phone as customer_phone, 
                    s.renewal_date, 
                    s.start_date, 
                    s.api_expires_at, 
                    s.package_type, 
                    s.price, 
                    s.status
                FROM sites s
                JOIN customers c ON s.customer_id = c.id
                ORDER BY s.renewal_date ASC
            ";
            $sites = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            $payload = [
                'action' => 'update_sheet',
                'data' => $sites,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cron_sync'
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Max 30s
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            curl_close($ch);
        }

        // UNLOCK & UPDATE TIMESTAMP
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_is_syncing', 0)")->execute();
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_last_sync', ?)")->execute([time()]);

        echo json_encode(['status' => 'success', 'message' => 'Sync completed']);
        exit;
    }

    // 1.5 Daily Backup (Midnight)
    // ===========================
    $lastBackup = $pdo->query("SELECT value FROM settings WHERE key = 'daily_backup_last_run'")->fetchColumn(); // Y-m-d
    $now = new DateTime();
    $today = $now->format('Y-m-d');

    // Check if backup ran today (or if never run)
    // Run if current time is after 00:00 (which is always true for 'today') and we haven't run for 'today'
    if ($lastBackup !== $today) {
        $dbFile = __DIR__ . '/../database.sqlite';
        $backupDir = __DIR__ . '/../backups';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Cleanup old backups (keep last 30 days)
        $files = glob("$backupDir/*.sqlite");
        $nowTs = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($nowTs - filemtime($file) > 30 * 24 * 60 * 60) {
                    unlink($file);
                }
            }
        }

        // Create Backup
        if (file_exists($dbFile)) {
            $backupFile = $backupDir . '/backup_' . date('Y-m-d') . '.sqlite';
            if (copy($dbFile, $backupFile)) {
                $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('daily_backup_last_run', ?)")->execute([$today]);
                log_activity($pdo, 'Otomatik Yedek', "Yedek alındı: " . basename($backupFile));
            }
        }

        // Auto Log Cleanup (Keep last 7 days)
        try {
            $pdo->exec("DELETE FROM api_access_logs WHERE created_at < date('now', '-7 days')");
            // Optional: log this action only if rows were deleted? 
            // SQLite exec returns rows affected but PDO::exec returns it.
            // Let's just log it.
            log_activity($pdo, 'Otomatik Log Temizliği', '7 günden eski loglar silindi.');
        } catch (Exception $e) {
            // Ignore error
        }
    }

    // 2. Daily Reminder Email
    // ===========================
    $dailyTime = $pdo->query("SELECT value FROM settings WHERE key = 'daily_reminder_time'")->fetchColumn();
    $dailyEmail = $pdo->query("SELECT value FROM settings WHERE key = 'daily_reminder_email'")->fetchColumn();
    $lastRun = $pdo->query("SELECT value FROM settings WHERE key = 'daily_reminder_last_run'")->fetchColumn(); // Stores date Y-m-d

    $dailyWaPhone = $pdo->query("SELECT value FROM settings WHERE key = 'daily_whatsapp_phone'")->fetchColumn();
    $dailyWaTime = $pdo->query("SELECT value FROM settings WHERE key = 'daily_whatsapp_time'")->fetchColumn();

    if ($dailyTime && $dailyEmail) {
        $now = new DateTime();
        $targetTime = DateTime::createFromFormat('H:i', $dailyTime);
        $today = $now->format('Y-m-d');

        // Check if we passed the time AND haven't run today
        // We compare H:i. If now >= target and lastRun != today
        if ($now->format('H:i') >= $dailyTime && $lastRun !== $today) {

            // Mark as running first to prevent double send
            $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('daily_reminder_last_run', ?)")->execute([$today]);

            // Fetch Upcoming Sites
            require_once __DIR__ . '/../includes/smtp.php';

            // Get SMTP Settings
            $stmt = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'smtp_%' OR key = 'company_name'");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['key']] = $row['value'];
            }

            if (!empty($settings['smtp_host'])) {
                $sql = "
                    SELECT s.domain, s.renewal_date, c.full_name, c.phone 
                    FROM sites s 
                    JOIN customers c ON s.customer_id = c.id
                    WHERE s.status IN ('active', 'requested', 'accepted')
                    AND DATE(s.renewal_date) BETWEEN DATE('now') AND DATE('now', '+30 days')
                    ORDER BY s.renewal_date ASC
                ";
                $upcoming = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($upcoming)) {
                    $body = "<h2>Merhaba,</h2>";
                    $body .= "<p>Aşağıdaki " . count($upcoming) . " sitenin yenileme süresi yaklaşıyor (30 gün içinde):</p>";
                    $body .= "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
                    $body .= "<tr style='background:#f3f4f6;'><th>Domain</th><th>Müşteri</th><th>Bitiş Tarihi</th><th>Kalan Gün</th></tr>";

                    foreach ($upcoming as $site) {
                        $days = days_until_renewal($site['renewal_date']);
                        $color = $days <= 7 ? '#fee2e2' : '#ffffff';
                        $body .= "<tr style='background:{$color}'>";
                        $body .= "<td>{$site['domain']}</td>";
                        $body .= "<td>{$site['full_name']} ({$site['phone']})</td>";
                        $body .= "<td>" . format_date($site['renewal_date']) . "</td>";
                        $body .= "<td><strong>{$days} gün</strong></td>";
                        $body .= "</tr>";
                    }
                    $body .= "</table>";
                    $body .= "<p><br>İyi çalışmalar,<br>{$settings['company_name']}</p>";

                    $smtp = new SMTP(
                        $settings['smtp_host'],
                        $settings['smtp_port'],
                        $settings['smtp_user'],
                        $settings['smtp_pass'],
                        $settings['smtp_security']
                    );

                    $sent = $smtp->send(
                        $dailyEmail,
                        'Günlük Site Hatırlatması - ' . date('d.m.Y'),
                        $body,
                        $settings['smtp_from_email'] ?? $settings['smtp_user'],
                        $settings['smtp_from_name'] ?? 'Site Takip'
                    );

                    if ($sent) {
                        log_activity($pdo, 'Günlük Mail Gönderildi', "Hedef: $dailyEmail, Site: " . count($upcoming));
                    } else {
                        error_log("Daily mail failed: " . json_encode($smtp->getLogs()));
                    }
                }
            }
        }
    }

    // 3. Admin Daily WhatsApp Reminder (Sites)
    $evoSettings = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%'")->fetchAll(PDO::FETCH_KEY_PAIR);

    if ($dailyWaPhone && $dailyWaTime && !empty($evoSettings['evolution_api_key'])) {
        $lastWaRun = $pdo->query("SELECT value FROM settings WHERE key = 'daily_whatsapp_last_run'")->fetchColumn();
        $now = new DateTime();
        $today = $now->format('Y-m-d');

        if ($now->format('H:i') >= $dailyWaTime && $lastWaRun !== $today) {

            // Atomic Claim (Race condition prevention)
            $claimed = false;
            
            // 1. Try Update if exists and not today
            $upd = $pdo->prepare("UPDATE settings SET value = ? WHERE key = 'daily_whatsapp_last_run' AND value != ?");
            try {
                $upd->execute([$today, $today]);
                if ($upd->rowCount() > 0) {
                    $claimed = true;
                } else {
                    // 2. If update affected 0 rows, either it's already today OR it doesn't exist.
                    $check = $pdo->prepare("SELECT count(*) FROM settings WHERE key = 'daily_whatsapp_last_run'");
                    $check->execute();
                    if ($check->fetchColumn() == 0) {
                        // Insert new
                        $pdo->prepare("INSERT INTO settings (key, value) VALUES ('daily_whatsapp_last_run', ?)")->execute([$today]);
                        $claimed = true;
                    }
                }
            } catch (Exception $e) {
                // If Locked, skip.
                $claimed = false; 
            }

            if ($claimed) {

            // Fetch Upcoming Sites (FILTERED: requested, accepted, waiting)
            $sql = "SELECT s.domain, s.renewal_date, s.status, c.full_name 
                    FROM sites s 
                    JOIN customers c ON s.customer_id = c.id 
                    WHERE s.status IN ('requested', 'accepted', 'waiting') 
                    AND DATE(s.renewal_date) BETWEEN DATE('now') AND DATE('now', '+30 days') 
                    ORDER BY s.renewal_date ASC";
            $upcoming = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($upcoming)) {

                $msg = "*Yönetici Raporu - " . date('d.m.Y H:i') . "*\n\n";
                $msg .= "Yaklaşan " . count($upcoming) . " site kaydı:\n\n";

                foreach ($upcoming as $site) {
                    $days = days_until_renewal($site['renewal_date']);
                    // Helper function available? get_status_label is in functions.php?
                    // functions.php is included.
                    $statusLabel = get_status_label($site['status']);

                    $msg .= "🌐 *{$site['domain']}*\n";
                    $msg .= "👤 {$site['full_name']}\n";
                    $msg .= "📊 Durum: {$statusLabel}\n";
                    $msg .= "📅 " . format_date($site['renewal_date']) . " (Kalan: $days gün)\n";
                    $msg .= "-------------------\n";
                }

                // Send
                $apiUrl = rtrim($evoSettings['evolution_api_url'] ?? '', '/');
                $instance = $evoSettings['evolution_instance_name'] ?? '';
                $endpoint = "$apiUrl/message/sendText/$instance";

                // PhoneNumber clean
                $phone = preg_replace('/\D/', '', $dailyWaPhone);
                if (substr($phone, 0, 1) === '0')
                    $phone = substr($phone, 1);
                if (substr($phone, 0, 2) !== '90')
                    $phone = '90' . $phone;

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
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "apikey: {$evoSettings['evolution_api_key']}"]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    log_activity($pdo, 'Günlük WhatsApp Raporu Gönderildi', "Hedef: $phone");
                } else {
                }
            }
        }
    }

    // 4. Admin WhatsApp Notification for Custom Reminders
    // ===================================================
    // Logic: reminders due today, time passed, not notified yet
    if ($dailyWaPhone && !empty($settings['evolution_api_key'])) {
        $now = new DateTime();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        // Fetch pending reminders due
        $sql = "
            SELECT r.*, s.domain 
            FROM reminders r 
            LEFT JOIN sites s ON r.site_id = s.id 
            WHERE r.status = 'pending' 
            AND r.reminder_date = ? 
            AND r.reminder_time <= ? 
            AND r.is_notified = 0
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$today, $currentTime]);
        $dueReminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($dueReminders)) {
            // Prepare clean phone
            $phone = preg_replace('/\D/', '', $dailyWaPhone);
            if (substr($phone, 0, 1) === '0')
                $phone = substr($phone, 1);
            if (substr($phone, 0, 2) !== '90')
                $phone = '90' . $phone;

            $apiUrl = rtrim($settings['evolution_api_url'] ?? '', '/');
            $instance = $settings['evolution_instance_name'] ?? '';
            $apiKey = $settings['evolution_api_key'] ?? '';
            $endpoint = "$apiUrl/message/sendText/$instance";

            foreach ($dueReminders as $rem) {
                // Compose Message
                $msg = "*🔔 Hatırlatma*\n\n";
                $msg .= "*Başlık:* {$rem['title']}\n";
                if ($rem['domain']) {
                    $msg .= "*Site:* {$rem['domain']}\n";
                }
                if ($rem['description']) {
                    $msg .= "*Not:* {$rem['description']}\n";
                }
                $msg .= "*Zaman:* {$rem['reminder_date']} {$rem['reminder_time']}";

                $data = [
                    "number" => $phone,
                    "text" => $msg,
                    "delay" => 1200,
                    "linkPreview" => false
                ];

                $ch = curl_init($endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "apikey: $apiKey"]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    // Mark as notified
                    $pdo->prepare("UPDATE reminders SET is_notified = 1 WHERE id = ?")->execute([$rem['id']]);
                    log_activity($pdo, 'Yönetici Hatırlatması Gönderildi', "Hatırlatma ID: {$rem['id']} | Giden: $phone");
                } else {
                    log_error("Admin Reminder Fail ID {$rem['id']}: $response");
                }
            }
        }
    }

    // 4.5 Process Cron Jobs Table
    // ============================
    $nowStr = date('Y-m-d H:i:s');
    $today = date('Y-m-d');
    $currentTime = date('H:i');

    // Get pending jobs that are due
    $stmt = $pdo->prepare("
        SELECT * FROM cron_jobs 
        WHERE status = 'pending' 
        AND scheduled_date <= ? 
        AND scheduled_time <= ? 
        ORDER BY scheduled_date, scheduled_time 
        LIMIT 50
    ");
    $stmt->execute([$today, $currentTime]);
    $pendingJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pendingJobs as $job) {
        // Get settings if not loaded
        if (empty($settings)) {
            $st = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%' OR key LIKE 'daily_%' OR key LIKE 'smtp_%'");
            while ($row = $st->fetch())
                $settings[$row['key']] = $row['value'];
        }

        $jobData = json_decode($job['job_data'], true);
        $success = false;
        $errorLog = '';

        // Process different job types
        switch ($job['job_type']) {
            case 'reminder_alarm':
                // Send WhatsApp for reminder alarm
                // Use admin phone from WhatsApp settings
                $adminPhone = $pdo->query("SELECT value FROM settings WHERE key = 'daily_whatsapp_phone'")->fetchColumn();

                if (!$adminPhone) {
                    $errorLog = 'Yönetici telefon numarası ayarlanmamış (Ayarlar > SMTP/Mail > Yönetici Günlük WhatsApp Hatırlatma)';
                    break;
                }

                if (empty($settings['evolution_api_key'])) {
                    $errorLog = 'Evolution API ayarları eksik';
                    break;
                }

                $phone = preg_replace('/\D/', '', $adminPhone);
                if (substr($phone, 0, 1) === '0')
                    $phone = substr($phone, 1);
                if (substr($phone, 0, 2) !== '90')
                    $phone = '90' . $phone;

                $msg = "*🔔 HATIRLATMA ALARMI*\n\n";
                $msg .= "*Site:* " . ($jobData['site_domain'] ?? 'Bilinmiyor') . "\n";
                $msg .= "*Başlık:* " . ($jobData['title'] ?? '') . "\n";
                if (!empty($jobData['note']))
                    $msg .= "*Not:* " . $jobData['note'] . "\n";
                $msg .= "\n⏰ Zamanı geldi!";

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
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                $success = ($httpCode >= 200 && $httpCode < 300);

                if (!$success) {
                    $errorLog = "WhatsApp API Hatası - HTTP $httpCode";
                    if ($curlError)
                        $errorLog .= " - $curlError";
                    if ($response)
                        $errorLog .= " - Response: " . substr($response, 0, 200);
                }

                // Mark reminder as notified
                if ($success && !empty($jobData['reminder_id'])) {
                    $pdo->prepare("UPDATE reminders SET is_notified = 1 WHERE id = ?")->execute([$jobData['reminder_id']]);
                }
                break;

            case 'daily_mail_reminder':
                // Send daily mail with upcoming sites
                if (empty($settings['smtp_host'])) {
                    $errorLog = 'SMTP ayarları eksik';
                    break;
                }

                $email = json_decode($job['job_data'], true)['email'] ?? '';
                if (!$email) {
                    $errorLog = 'Email adresi bulunamadı';
                    break;
                }

                // Get upcoming sites (next 30 days)
                $today = date('Y-m-d');
                $next30 = date('Y-m-d', strtotime('+30 days'));

                $stmt = $pdo->prepare("
                    SELECT s.*, c.full_name, c.phone 
                    FROM sites s 
                    LEFT JOIN customers c ON s.customer_id = c.id 
                    WHERE s.renewal_date BETWEEN ? AND ? 
                    AND s.status NOT IN ('iptal', 'pasif')
                    ORDER BY s.renewal_date ASC
                ");
                $stmt->execute([$today, $next30]);
                $upcoming = $stmt->fetchAll();

                if (count($upcoming) == 0) {
                    $success = true;
                    break;
                }

                // Build email
                require_once __DIR__ . '/../includes/smtp.php';

                $body = "<h2>Yaklaşan Site Yenilemeleri - " . date('d.m.Y') . "</h2>";
                $body .= "<p>Önümüzdeki 30 gün içinde yenilenecek siteler:</p>";
                $body .= "<table border='1' cellpadding='10' style='border-collapse:collapse;width:100%'>";
                $body .= "<tr style='background:#f3f4f6'><th>Site</th><th>Müşteri</th><th>Yenileme Tarihi</th><th>Durum</th><th>Kalan Gün</th></tr>";

                foreach ($upcoming as $site) {
                    $days = (strtotime($site['renewal_date']) - time()) / 86400;
                    $days = ceil($days);
                    $color = $days <= 7 ? '#fee2e2' : ($days <= 15 ? '#fef3c7' : '#fff');

                    // Status labels
                    $statusLabels = [
                        'aktif' => 'Aktif',
                        'istendi' => '📤 İstendi',
                        'kabul etti' => '✅ Kabul Etti',
                        'bekliyor' => '⏳ Bekliyor',
                        'iptal' => '❌ İptal',
                        'pasif' => 'Pasif'
                    ];
                    $statusText = $statusLabels[$site['status']] ?? $site['status'];

                    $body .= "<tr style='background:$color'>";
                    $body .= "<td>{$site['domain']}</td>";
                    $body .= "<td>{$site['full_name']} ({$site['phone']})</td>";
                    $body .= "<td>" . date('d.m.Y', strtotime($site['renewal_date'])) . "</td>";
                    $body .= "<td><strong>$statusText</strong></td>";
                    $body .= "<td><strong>$days gün</strong></td>";
                    $body .= "</tr>";
                }
                $body .= "</table>";
                $smtp = new SMTP(
                    $settings['smtp_host'],
                    $settings['smtp_port'],
                    $settings['smtp_user'],
                    $settings['smtp_pass'],
                    $settings['smtp_security']
                );

                $success = $smtp->send(
                    $email,
                    'Günlük Site Hatırlatması - ' . date('d.m.Y'),
                    $body,
                    $settings['smtp_from_email'] ?? $settings['smtp_user'],
                    $settings['smtp_from_name'] ?? 'Site Takip'
                );

                if (!$success) {
                    $errorLog = 'SMTP hatası: ' . implode(', ', $smtp->getLogs());
                }
                break;

            case 'daily_whatsapp_reminder':
                // Send daily WhatsApp with upcoming sites
                if (empty($settings['evolution_api_key'])) {
                    $errorLog = 'Evolution API ayarları eksik';
                    break;
                }

                $phone = json_decode($job['job_data'], true)['phone'] ?? '';
                if (!$phone) {
                    // Try from settings
                    $phone = $settings['daily_whatsapp_phone'] ?? '';
                }

                if (!$phone) {
                    $errorLog = 'Telefon numarası bulunamadı';
                    break;
                }

                // Format phone
                $phone = preg_replace('/\D/', '', $phone);
                if (substr($phone, 0, 1) === '0')
                    $phone = substr($phone, 1);
                if (substr($phone, 0, 2) !== '90')
                    $phone = '90' . $phone;

                // Get upcoming sites
                $today = date('Y-m-d');
                $next30 = date('Y-m-d', strtotime('+30 days'));

                $stmt = $pdo->prepare("
                    SELECT s.*, c.full_name 
                    FROM sites s 
                    LEFT JOIN customers c ON s.customer_id = c.id 
                    WHERE s.renewal_date BETWEEN ? AND ? 
                    AND s.status NOT IN ('iptal', 'pasif')
                    ORDER BY s.renewal_date ASC
                    LIMIT 20
                ");
                $stmt->execute([$today, $next30]);
                $upcoming = $stmt->fetchAll();

                if (count($upcoming) == 0) {
                    $success = true;
                    break;
                }

                // Build message
                $msg = "*📊 GÜNLÜK RAPOR - " . date('d.m.Y') . "*\n\n";
                $msg .= "*Yaklaşan Yenilemeler (30 gün)*\n\n";

                foreach ($upcoming as $site) {
                    $days = ceil((strtotime($site['renewal_date']) - time()) / 86400);
                    $icon = $days <= 7 ? '🔴' : ($days <= 15 ? '🟡' : '🟢');

                    // Status emojis
                    $statusIcons = [
                        'aktif' => '',
                        'istendi' => '(📤 İstendi)',
                        'kabul etti' => '(✅ Kabul Etti)',
                        'bekliyor' => '(⏳ Bekliyor)',
                    ];
                    $statusSuffix = $statusIcons[$site['status']] ?? '';

                    $msg .= "$icon *{$site['domain']}* $statusSuffix\n";
                    $msg .= "   Müşteri: {$site['full_name']}\n";
                    $msg .= "   Tarih: " . date('d.m.Y', strtotime($site['renewal_date'])) . " ($days gün)\n\n";
                }

                $msg .= "\n✅ Toplam: " . count($upcoming) . " site";

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
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                $success = ($httpCode >= 200 && $httpCode < 300);

                if (!$success) {
                    $errorLog = "WhatsApp API Hatası - HTTP $httpCode";
                    if ($curlError)
                        $errorLog .= " - $curlError";
                    if ($response)
                        $errorLog .= " - " . substr($response, 0, 200);
                }
                break;
        }

        // Update job status with error log
        $newStatus = $success ? 'completed' : 'failed';
        $updateStmt = $pdo->prepare("UPDATE cron_jobs SET status = ?, last_run_at = ?, error_log = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $nowStr, $errorLog, $job['id']]);

        // If recurring and successful, create next day's job
        if ($job['is_recurring'] && $success) {
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $nextRun = "$tomorrow {$job['scheduled_time']}:00";

            // Check if tomorrow's job already exists
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM cron_jobs WHERE job_type = ? AND scheduled_date = ? AND status = 'pending'");
            $checkStmt->execute([$job['job_type'], $tomorrow]);

            if ($checkStmt->fetchColumn() == 0) {
                // Create new job for tomorrow
                $insertStmt = $pdo->prepare("
                    INSERT INTO cron_jobs (job_type, job_name, job_data, scheduled_time, scheduled_date, status, is_recurring, next_run_at) 
                    VALUES (?, ?, ?, ?, ?, 'pending', 1, ?)
                ");
                $insertStmt->execute([
                    $job['job_type'],
                    $job['job_name'],
                    $job['job_data'],
                    $job['scheduled_time'],
                    $tomorrow,
                    $nextRun
                ]);
            }
        }
    }

    // 5. Process WhatsApp Queue (Scheduled Messages)
    // ===========================
    // Fix: Use PHP time to ensure timezone consistency match with DB setting
    $nowStr = date('Y-m-d H:i:s');

    // Select items where scheduled_at <= NOW and status = 'pending'
    $stmt = $pdo->prepare("SELECT q.*, s.domain FROM whatsapp_queue q LEFT JOIN sites s ON q.site_id = s.id WHERE q.status = 'pending' AND REPLACE(q.scheduled_at, 'T', ' ') <= ?");
    $stmt->execute([$nowStr]);
    $queueItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($queueItems)) {
        // Need settings if not loaded
        if (empty($settings)) {
            $st = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%'");
            while ($row = $st->fetch())
                $settings[$row['key']] = $row['value'];
        }

        $apiUrl = rtrim($settings['evolution_api_url'] ?? '', '/');
        $instance = $settings['evolution_instance_name'] ?? '';
        $apiKey = $settings['evolution_api_key'] ?? '';
        $endpoint = "$apiUrl/message/sendText/$instance";

        // Pre-init curl reused headers? Or new curl per item? New curl safer for loop
        $processedCount = 0;
        foreach ($queueItems as $item) {
            $processedCount++;
            $data = [
                "number" => $item['phone'],
                "text" => $item['message'],
                "delay" => 1000,
                "linkPreview" => false
            ];

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "apikey: $apiKey"]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                // Success
                $pdo->prepare("UPDATE whatsapp_queue SET status = 'sent' WHERE id = ?")->execute([$item['id']]);

                // Update Site Status only if not already updated recently or logic dependent?
                // Request said "Auto message should act like Send Whatsapp". So we update site status.
                if ($item['site_id']) {
                    $pdo->prepare("UPDATE sites SET whatsapp_sent = 1, whatsapp_sent_at = CURRENT_TIMESTAMP, status = 'requested', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$item['site_id']]);
                    log_activity($pdo, 'Zamanlı Mesaj Gönderildi', "Site: {$item['domain']} | Telefon: {$item['phone']}");
                }
            } else {
                // Fail
                $pdo->prepare("UPDATE whatsapp_queue SET status = 'failed' WHERE id = ?")->execute([$item['id']]);
                log_error("Queue Send Fail ID {$item['id']}: $response");
            }
        }
    }

    // 6. Background Message Sync (Localhost Fallback)
    // =================================================
    // OPTIMIZED: Close session to prevent lag
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Every 60 seconds (managed by scheduler frequency)
    // Fetch last 10 messages for top 5 active contacts to catch incoming messages
    $lastSync = $pdo->query("SELECT value FROM settings WHERE key = 'whatsapp_background_sync_last'")->fetchColumn();
    $nowTs = time();
    $syncInterval = 45; // seconds

    // Only run if interval passed or never ran
    if (!$lastSync || ($nowTs - $lastSync > $syncInterval)) {
        if (empty($settings)) {
            $st = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%'");
            while ($row = $st->fetch())
                $settings[$row['key']] = $row['value'];
        }

        if (!empty($settings['evolution_api_key'])) {
            $apiUrl = rtrim($settings['evolution_api_url'] ?? '', '/');
            $instance = $settings['evolution_instance_name'] ?? '';
            $apiKey = $settings['evolution_api_key'] ?? '';

            // Get top 1 recently active contacts (Reduced from 5)
            $activeContacts = $pdo->query("SELECT jid FROM whatsapp_contacts ORDER BY last_message_time DESC LIMIT 1")->fetchAll(PDO::FETCH_COLUMN);

            // Also check for unread chats if API supports it (chat/findChats)
            // For now, just sync active ones
            $storeStmt = $pdo->prepare("INSERT OR IGNORE INTO whatsapp_messages (remote_jid, message_id, from_me, push_name, content, message_type, timestamp, raw_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $updateContactStmt = $pdo->prepare("UPDATE whatsapp_contacts SET last_message_time = ? WHERE jid = ?");

            foreach ($activeContacts as $jid) {
                // Correct Payload for Evolution v2.3.1 (chat/findMessages)
                $payload = [
                    "where" => [
                        "key" => [
                            "remoteJid" => $jid
                        ]
                    ],
                    "limit" => 2000,
                    "page" => 1,
                    "order" => [
                        "messageTimestamp" => "DESC"
                    ]
                ];

                $ch = curl_init("$apiUrl/chat/findMessages/$instance");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "apikey: $apiKey",
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                $resp = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($code == 200) {
                    $body = json_decode($resp, true);
                    // Extract messages
                    $msgs = [];
                    if (isset($body['messages']['records']))
                        $msgs = $body['messages']['records'];
                    elseif (is_array($body))
                        $msgs = $body;

                    if (!empty($msgs)) {
                        $lastTime = 0;
                        foreach ($msgs as $m) {
                            if (!is_array($m))
                                continue;
                            $key = $m['key'] ?? [];
                            $id = $key['id'] ?? '';
                            if (!$id)
                                continue;

                            $fromMe = $key['fromMe'] ?? false;
                            $pushName = $m['pushName'] ?? '';
                            $ts = $m['messageTimestamp'] ?? time();
                            $lastTime = max($lastTime, $ts);

                            // Content extraction (simplified)
                            $content = '[Yeni Mesaj]'; // Default
                            $msgData = $m['message'] ?? [];
                            if (isset($msgData['conversation']))
                                $content = $msgData['conversation'];
                            elseif (isset($msgData['extendedTextMessage']))
                                $content = $msgData['extendedTextMessage']['text'] ?? '';

                            // Type
                            $type = 'text';

                            $storeStmt->execute([$jid, $id, $fromMe ? 1 : 0, $pushName, $content, $type, $ts, json_encode($m)]);
                        }

                        // Update contact time
                        if ($lastTime > 0) {
                            $updateContactStmt->execute([date('Y-m-d H:i:s', $lastTime), $jid]);
                        }
                    }
                }
            }

            // Update sync time
            $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('whatsapp_background_sync_last', ?)")->execute([$nowTs]);
        }
    }


    echo json_encode([
        'status' => 'success',
        'message' => 'Tasks completed',
        'debug' => [
            'server_time' => $nowStr,
            'queue_found' => count($queueItems),
            'processed_count' => $processedCount ?? 0
        ]
    ]);

} catch (Exception $e) {
    // Attempt to unlock if error
    $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_is_syncing', 0)")->execute();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
