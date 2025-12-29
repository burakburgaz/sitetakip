<?php
// api/evolution.php - Evolution API Integration Handler
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login(); // Ensure user is logged in

header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_POST['action'] ?? '';

    // TEST SEND
    if ($action === 'test_send') {
        $apiUrl = $_POST['api_url'] ?? '';
        $instance = $_POST['instance'] ?? '';
        $apiKey = $_POST['api_key'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $message = $_POST['message'] ?? '';

        if (!$apiUrl || !$instance || !$apiKey || !$phone || !$message) {
            json_response(['status' => 'error', 'message' => 'Lütfen tüm alanları doldurun.'], 400);
        }

        // Clean URL
        $apiUrl = rtrim($apiUrl, '/');

        // Construct Endpoint
        // Common Evolution API Endpoint: /message/sendText/{instance}
        $endpoint = "$apiUrl/message/sendText/$instance";

        // Prepare Data
        // Evolution API v2 / v1.6+ Payload Structure for /message/sendText
        $data = [
            "number" => $phone,
            "text" => $message,
            "delay" => 1200,
            "linkPreview" => false
        ];

        // Send CURL Request
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
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            json_response(['status' => 'error', 'message' => 'CURL Hatası', 'detail' => $error], 500);
        }

        $respData = json_decode($response, true);

        // Check success
        // Evolution usually returns 201 Created on success
        if ($httpCode >= 200 && $httpCode < 300) {
            json_response(['status' => 'success', 'message' => 'Mesaj gönderildi', 'response' => $respData]);
        } else {
            json_response([
                'status' => 'error',
                'message' => 'API Hatası (' . $httpCode . ')',
                'detail' => $response
            ], 400);
        }
    }

    // CHECK STATUS
    if ($action === 'check_status') {
        $apiUrl = $_POST['api_url'] ?? '';
        $instance = $_POST['instance'] ?? '';
        $apiKey = $_POST['api_key'] ?? '';

        if (!$apiUrl || !$instance || !$apiKey) {
            json_response(['status' => 'error', 'message' => 'Ayarlar eksik'], 400);
        }

        $apiUrl = rtrim($apiUrl, '/');
        // Endpoint: /instance/connectionState/{instance}
        $endpoint = "$apiUrl/instance/connectionState/$instance";

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "apikey: $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            json_response(['status' => 'success', 'data' => $data]);
        } else {
            // Try to be helpful with error
            $msg = $data['message'] ?? $data['response']['message'] ?? 'Bilinmeyen Hata';
            json_response(['status' => 'error', 'message' => 'API Hatası: ' . $msg, 'detail' => $response], 400);
        }
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
