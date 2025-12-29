<?php
// api/hostinger.php
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
    // SAVE API KEY
    if ($action === 'save_key') {
        require_admin();
        $key = $_POST['api_key'] ?? '';

        $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('hostinger_api_key', ?)");
        $stmt->execute([$key]);

        json_response(['status' => 'success', 'message' => 'API anahtarı kaydedildi']);
    }

    // SYNC (Fetch from Hostinger)
    if ($action === 'sync') {
        // Get API Key
        $stmt = $pdo->query("SELECT value FROM settings WHERE key = 'hostinger_api_key'");
        $apiKey = $stmt->fetchColumn();

        if (!$apiKey) {
            json_response(['status' => 'error', 'message' => 'API anahtarı bulunamadı'], 400);
        }

        // Call Hostinger API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://developers.hostinger.com/api/domains/v1/portfolio");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Fix for SSL certificate error in local dev environments
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Curl Error: ' . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            // Log error but simpler response
            json_response(['status' => 'error', 'message' => 'Hostinger API hatası: ' . $httpCode, 'details' => $response], 500);
        }

        $data = json_decode($response, true);

        // Handle different response structures (Direct array or wrapped in data)
        $domains = [];
        if (isset($data['data']) && is_array($data['data'])) {
            $domains = $data['data'];
        } elseif (is_array($data)) {
            $domains = $data;
        } else {
            // Debugging: Show raw response
            $debugInfo = strip_tags(substr($response, 0, 500));
            json_response(['status' => 'error', 'message' => 'Geçersiz API yanıt yapısı. Dönen veri: ' . $debugInfo], 500);
        }

        // Get Default Customer ID
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE full_name = 'Otomatik İçe Aktarılan' LIMIT 1");
        $stmt->execute();
        $defaultCustId = $stmt->fetchColumn() ?: 1; // Fallback to 1

        $addedSites = [];
        $updatedSites = [];

        foreach ($domains as $domainItem) {
            // Field mapping: Verify if it's 'name' or 'domain'
            $domainName = $domainItem['name'] ?? $domainItem['domain'] ?? '';

            if (empty($domainName))
                continue;

            $expiresAt = !empty($domainItem['expires_at']) ? date('Y-m-d', strtotime($domainItem['expires_at'])) : null;
            $createdAt = !empty($domainItem['created_at']) ? date('Y-m-d', strtotime($domainItem['created_at'])) : null;

            // Skip if no expiration date (e.g. free_domain type sometimes)
            if (!$expiresAt)
                continue;

            // Check if site exists
            $stmt = $pdo->prepare("SELECT id, renewal_date, start_date FROM sites WHERE domain = ?");
            $stmt->execute([$domainName]);
            $site = $stmt->fetch();

            if ($site) {
                // Update
                $updateFields = [];
                $params = [];

                $updateFields[] = "api_expires_at = ?";
                $params[] = $expiresAt;

                if ($createdAt) {
                    $updateFields[] = "start_date = ?";
                    $params[] = $createdAt;
                }

                $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
                $params[] = $site['id'];

                $sql = "UPDATE sites SET " . implode(', ', $updateFields) . " WHERE id = ?";
                $updateStmt = $pdo->prepare($sql);
                $updateStmt->execute($params);

                if ($site['renewal_date'] != $expiresAt) {
                    $updatedSites[] = $domainName;
                }
            } else {
                // Insert new site
                $insStmt = $pdo->prepare("INSERT INTO sites (customer_id, domain, renewal_date, api_expires_at, start_date, package_type, price, status) VALUES (?, ?, ?, ?, ?, 'Paket Seç', 3000, 'active')");
                $insStmt->execute([$defaultCustId, $domainName, $expiresAt, $expiresAt, $createdAt]);
                $addedSites[] = $domainName;
            }
        }

        // Update Last Sync Time
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('hostinger_last_sync', ?)")->execute([date('Y-m-d H:i:s')]);

        $message = "Senkronizasyon tamamlandı.";
        if (count($addedSites) > 0)
            $message .= " " . count($addedSites) . " yeni site eklendi.";
        if (count($updatedSites) > 0)
            $message .= " " . count($updatedSites) . " site güncellendi.";

        if (count($addedSites) > 0 || count($updatedSites) > 0) {
            syncToGoogleSheets($pdo);
        }

        json_response([
            'status' => 'success',
            'message' => $message,
            'added' => $addedSites,
            'updated_count' => count($updatedSites)
        ]);
    }

    // Accept Renewal (Sync API Date to System Date)
    if ($action === 'accept_renewal') {
        require_admin();
        $siteId = $_POST['id'] ?? 0;

        $stmt = $pdo->prepare("SELECT id, domain, renewal_date, last_renewed_at, status, api_expires_at FROM sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch();

        if (!$site || !$site['api_expires_at']) {
            json_response(['status' => 'error', 'message' => 'API verisi bulunamadı'], 400);
        }

        $newDate = $site['api_expires_at'];

        // Prepare previous data for Undo
        $previousData = [
            'type' => 'update',
            'table' => 'sites',
            'data' => [
                'renewal_date' => $site['renewal_date'],
                'last_renewed_at' => $site['last_renewed_at'],
                'status' => $site['status']
            ]
        ];

        // Update
        $stmt = $pdo->prepare("UPDATE sites SET renewal_date = ?, last_renewed_at = CURRENT_TIMESTAMP, status = 'active' WHERE id = ?");
        $stmt->execute([$newDate, $siteId]);

        log_activity($pdo, 'Site Süresi Eşitlendi (API)', "Site: " . $site['domain'] . " (ID: $siteId), Yeni Tarih: $newDate", $previousData);
        syncToGoogleSheets($pdo);

        json_response(['status' => 'success', 'message' => 'Site süresi güncellendi', 'new_date' => $newDate]);
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
?>