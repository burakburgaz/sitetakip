<?php
// includes/api_security.php
// API Erişim Kontrolü ve Loglama

require_once __DIR__ . '/db.php';

// IP Adresini Al
function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if (isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if (isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

$clientIp = get_client_ip();
$requestUri = $_SERVER['REQUEST_URI'] ?? 'CLI';
$method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// İzin verilen local IP'ler ve Sunucu IP'si
$allowedIps = ['127.0.0.1', '::1', 'localhost', '92.112.185.146'];

// Veritabanındaki izinli IP'leri getir
try {
    // EĞER tablo yoksa (migration henüz çalışmadıysa) hata vermemesi için kontrol edebiliriz
    // Ama db.php include edildiği için tablo oluşmuş olmalı.
    $stmt = $pdo->query("SELECT ip_address FROM ip_whitelist");
    $dbAllowed = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $allowedIps = array_merge($allowedIps, $dbAllowed);
} catch (Exception $e) {
    // Tablo henüz yoksa sadece local'e izin ver
}

// Check IP Restriction Setting
$ipRestrictionActive = false; // Default OFF for safety/recovery
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE key = 'system_api_ip_restriction'");
    $val = $stmt->fetchColumn();
    // Only active if explicitly set to '1'
    if ($val === '1') {
        $ipRestrictionActive = true;
    }
} catch (Exception $e) {
}

// Kontrol
$isAllowed = false;

if (!$ipRestrictionActive) {
    $isAllowed = true;
} else {
    // 1. IP Whitelist Kontrolü
    if (in_array($clientIp, $allowedIps)) {
        $isAllowed = true;
    }

    // 2. CLI Kontrolü
    if (php_sapi_name() === 'cli') {
        $isAllowed = true;
    }

    // 3. Oturum Kontrolü
    if (!$isAllowed) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            $isAllowed = true;
        }
    }
}

// Loglama
$status = $isAllowed ? 'allowed' : 'denied';

// Skip logging for localhost if allowed to prevent log bloat
if ($status === 'allowed' && in_array($clientIp, ['127.0.0.1', '::1', 'localhost', '92.112.185.146'])) {
    // Skip
} else {
    try {
        $stmt = $pdo->prepare("INSERT INTO api_access_logs (ip_address, endpoint, method, status, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$clientIp, $requestUri, $method, $status, $userAgent]);

    } catch (Exception $e) {
        // Log Hatası sistemin çalışmasını durdurmamalı
    }
}

if (!$isAllowed) {
    http_response_code(403); // Forbidden
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Erişim Engellendi (IP Kısıtlaması)', 'your_ip' => $clientIp]);
    exit;
}
