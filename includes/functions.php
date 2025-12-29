<?php
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/smtp.php';

// includes/functions.php - Yardımcı fonksiyonlar

function json_response($data, $status = 200)
{
    // Clean all output buffers to remove BOM
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize_input($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function format_phone($phone)
{
    return preg_replace('/[^0-9]/', '', $phone);
}

function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function days_until_renewal($renewal_date)
{
    $now = new DateTime();
    $renewal = new DateTime($renewal_date);
    $diff = $now->diff($renewal);
    return $diff->invert ? -$diff->days : $diff->days;
}

function get_renewal_status($days)
{
    if ($days < 0)
        return 'expired';
    if ($days <= 7)
        return 'critical';
    if ($days <= 30)
        return 'warning';
    return 'ok';
}

function get_status_color($status)
{
    switch ($status) {
        case 'expired':
            return '#ef4444'; // Kırmızı
        case 'critical':
            return '#f59e0b'; // Turuncu
        case 'warning':
            return '#eab308'; // Sarı
        default:
            return '#10b981'; // Yeşil
    }
}

function get_status_label($status)
{
    $labels = [
        'active' => 'Aktif',
        'requested' => 'İstendi',
        'accepted' => 'Kabul Etti',
        'cancelled' => 'İptal Edildi',
        'transferred' => 'Transfer Edildi',
        'expired' => 'Süresi Doldu',
        'pending' => 'Bekliyor'
    ];
    return $labels[$status] ?? $status;
}

function log_activity($pdo, $action, $details = '', $previous_data = null)
{
    try {
        $user_id = get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        // Encode previous_data as JSON if it's an array/object
        $prev_data_json = null;
        if ($previous_data !== null) {
            $prev_data_json = is_string($previous_data) ? $previous_data : json_encode($previous_data, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, previous_data, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $prev_data_json, $ip]);
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

function format_currency($amount)
{
    return number_format($amount, 2, ',', '.') . ' ₺';
}

function format_date($date, $format = 'd.m.Y')
{
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

function syncToGoogleSheets($pdo)
{
    // Lazy Sync: Update the 'google_sheets_last_change' timestamp.
    // The actual sync will be handled by a client-side triggered cron (api/cron.php)
    // to prevent process exhaustion and system locking.
    try {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_last_change', ?)");
        $stmt->execute([time()]);
    } catch (Exception $e) {
        // Fail silently or log, but don't break the flow
        error_log("Sync flag update failed: " . $e->getMessage());
    }
}

function getEvolutionConfig($pdo)
{
    $stmt = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'evolution_%'");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }

    if (empty($settings['evolution_api_url']) || empty($settings['evolution_api_key'])) {
        return false;
    }
    return $settings;
}

function callEvolutionApi($endpoint, $method = 'GET', $data = [], $config)
{
    $apiUrl = rtrim($config['evolution_api_url'], '/');
    $instance = $config['evolution_instance_name'];
    $apiKey = $config['evolution_api_key'];

    // Build URL: apiUrl/endpoint/instance
    $url = "$apiUrl/$endpoint/$instance";

    // Handle v1 vs v2 endpoints or GET params
    if ($method === 'GET' && !empty($data)) {
        $url .= '?' . http_build_query($data);
    }

    // Log API request
    if (class_exists('Logger')) {
        Logger::logInfo("🔵 Evolution API Request: $method $endpoint", [
            'url' => $url,
            'data' => $data
        ]);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "apikey: $apiKey"
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $responseBody = json_decode($response, true);

    // Detailed logging
    if (class_exists('Logger')) {
        Logger::logEvolutionAPI(
            $endpoint,
            $method,
            $data,
            $httpCode,
            $responseBody ?: $response,
            $err
        );
    }

    if ($err) {
        if (class_exists('Logger')) {
            Logger::logError("CURL Error in Evolution API", [
                'endpoint' => $endpoint,
                'error' => $err,
                'url' => $url
            ]);
        }
        return ['error' => $err];
    }

    return ['code' => $httpCode, 'body' => $responseBody];
}

/**
 * Sends an email using SMTP settings from database
 */
function send_email_notification($pdo, $to, $subject, $body)
{
    try {
        // Get SMTP Settings
        $stmt = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'smtp_%'");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['key']] = $row['value'];
        }

        if (empty($config['smtp_host'])) {
            Logger::logError("SMTP Not Configured");
            return false;
        }

        // Initialize SMTP
        $smtp = new SMTP(
            $config['smtp_host'],
            $config['smtp_port'],
            $config['smtp_user'],
            $config['smtp_pass'],
            $config['smtp_security']
        );

        $fromEmail = $config['smtp_from_email'];
        $fromName = $config['smtp_from_name'];
        $htmlBody = "<html><body>" . nl2br($body) . "</body></html>";

        if ($smtp->send($to, $subject, $htmlBody, $fromEmail, $fromName)) {
            Logger::logInfo("📧 Email Sent to $to: $subject");
            return true;
        } else {
            $logs = $smtp->getLogs();
            Logger::logError("Email Failed to $to", $logs);
            return false;
        }
    } catch (Exception $e) {
        Logger::logError("Email Error: " . $e->getMessage());
        return false;
    }
}

function log_error($message, $context = [])
{
    if (class_exists('Logger')) {
        Logger::logError($message, $context);
    } else {
        error_log($message . ' ' . json_encode($context));
    }
}
