<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow cron/cli access or logged in user
if (php_sapi_name() !== 'cli') {
    require_login();
}

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';

try {
    // SAVE WEBHOOK URL
    if ($action === 'save_url') {
        require_admin();
        $url = $_POST['webhook_url'] ?? '';

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('google_sheets_webhook_url', ?)");
        $stmt->execute([$url]);

        json_response(['status' => 'success', 'message' => 'Webhook URL kaydedildi']);
    }

    // EXPORT TO GOOGLE SHEETS
    if ($action === 'export') {
        // Get Webhook URL
        $stmt = $pdo->query("SELECT value FROM settings WHERE key = 'google_sheets_webhook_url'");
        $webhookUrl = $stmt->fetchColumn();

        if (!$webhookUrl) {
            json_response(['status' => 'error', 'message' => 'Lütfen önce Google Sheets Webhook URL adresini kaydedin.'], 400);
        }

        // Fetch All Sites Data
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

        // Prepare data payload
        $payload = [
            'action' => 'update_sheet',
            'data' => $sites,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Send POST request to Google Apps Script
        if (strpos($webhookUrl, '/exec') === false) {
            json_response(['status' => 'error', 'message' => 'Hatalı URL: Google Apps Script "Web App" URL\'si "/exec" ile bitmelidir. Lütfen "Dağıt (Deploy)" adımlarını kontrol edin.'], 400);
        }

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        // Do NOT follow redirects automatically to avoid method confusion
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // Disable SSL for local dev if needed
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

        if (curl_errno($ch)) {
            throw new Exception('Curl Hatası: ' . curl_error($ch));
        }
        curl_close($ch);

        // Handle Google's 302 Redirect explicitly
        if ($httpCode == 302 && $redirectUrl) {
            // Check if redirect is valid (basic safety)
            // Perform GET to the redirect URL to fetch the response
            $ch = curl_init($redirectUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        // Check response
        $respData = json_decode($response, true);

        if ($httpCode == 200) {
            json_response(['status' => 'success', 'message' => 'Veriler Google Sheets\'e aktarıldı.', 'details' => $respData]);
        } else {
            // If we got here, it's an error. 
            // If 302 persisted, we didn't follow?
            json_response(['status' => 'error', 'message' => 'Google Sheets Hatası: ' . $httpCode . ' (URL: ' . $webhookUrl . ') Response: ' . substr(strip_tags($response), 0, 200)], 500);
        }
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
?>