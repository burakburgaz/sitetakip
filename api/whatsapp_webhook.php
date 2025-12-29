<?php
// api/whatsapp_webhook.php - Evolution API Webhook Handler
// This receives real-time WhatsApp messages from Evolution API

// Disable error display (production mode)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Set error handler to prevent HTML errors from breaking JSON
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("Webhook PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/api_security.php';

// Log webhook for debugging (Raw Payload) - keeping the old logs as well just in case for easy access
$rawPayload = file_get_contents('php://input');
file_put_contents(__DIR__ . '/../logs/webhook_' . date('Y-m-d') . '.log', date('[Y-m-d H:i:s] ') . $rawPayload . PHP_EOL, FILE_APPEND);

$webhook = json_decode($rawPayload, true);

if (!$webhook) {
    // If accessed via browser, this will happen. Normal behavior for GET requests.
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
    exit;
}

// Log webhook receipt via Logger
Logger::logInfo("🔔 WEBHOOK RECEIVED (API Endpoint)", [
    'timestamp' => date('Y-m-d H:i:s'),
    'event' => $webhook['event'] ?? 'unknown',
    'instance' => $webhook['instance'] ?? 'unknown'
]);

try {
    // Get event type
    $event = $webhook['event'] ?? '';
    $data = $webhook['data'] ?? [];

    // Handle different event types
    if ($event === 'messages.upsert') {
        Logger::logInfo("📨 Processing messages.upsert event");

        // Extract message info
        $key = $data['key'] ?? [];
        $message = $data['message'] ?? [];

        $remoteJid = $key['remoteJid'] ?? '';
        $fromMe = $key['fromMe'] ?? false;
        $messageId = $key['id'] ?? uniqid();

        if (!$remoteJid) {
            Logger::logError("No remoteJid in webhook");
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No remoteJid']);
            exit;
        }

        // Extract message content
        $content = '';
        $msgType = 'unknown';

        if (isset($message['conversation'])) {
            $content = $message['conversation'];
            $msgType = 'text';
        } elseif (isset($message['extendedTextMessage'])) {
            $content = $message['extendedTextMessage']['text'] ?? '';
            $msgType = 'text';
        } elseif (isset($message['imageMessage'])) {
            $content = '[Görsel] ' . ($message['imageMessage']['caption'] ?? '');
            $msgType = 'image';
        } elseif (isset($message['videoMessage'])) {
            $content = '[Video] ' . ($message['videoMessage']['caption'] ?? '');
            $msgType = 'video';
        } elseif (isset($message['documentMessage'])) {
            $content = '[Dosya] ' . ($message['documentMessage']['fileName'] ?? 'Belge');
            $msgType = 'document';
        } elseif (isset($message['audioMessage'])) {
            $content = '[Ses Kaydı]';
            $msgType = 'audio';
        } elseif (isset($message['ephemeralMessage'])) {
            $content = '[Süreli Mesaj]';
            $inner = $message['ephemeralMessage']['message'] ?? [];
            if (isset($inner['conversation'])) {
                $content = $inner['conversation'];
                $msgType = 'text';
            } elseif (isset($inner['extendedTextMessage'])) {
                $content = $inner['extendedTextMessage']['text'] ?? '';
                $msgType = 'text';
            }
        } elseif (isset($message['viewOnceMessage'])) {
            $content = '[Bir Kez Görüntülenen Mesaj]';
            $msgType = 'view_once';
        } else {
            $content = '[Diğer Mesaj]';
            $msgType = 'other';
        }

        // Get sender info
        $pushName = $data['pushName'] ?? '';
        $timestamp = $data['messageTimestamp'] ?? time();

        Logger::logInfo("Message extracted", [
            'remoteJid' => $remoteJid,
            'fromMe' => $fromMe ? 'YES' : 'NO',
            'content' => substr($content, 0, 50),
            'type' => $msgType
        ]);

        // Save to database
        $stmt = $pdo->prepare("
            INSERT OR IGNORE INTO whatsapp_messages 
            (remote_jid, message_id, from_me, push_name, content, message_type, timestamp, is_read, raw_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Incoming messages (from_me=0) are unread, outgoing (from_me=1) are read
        $isRead = $fromMe ? 1 : 0;

        $stmt->execute([
            $remoteJid,
            $messageId,
            $fromMe ? 1 : 0,
            $pushName,
            $content,
            $msgType,
            $timestamp,
            $isRead,  // New: is_read column
            json_encode($data)
        ]);

        Logger::logInfo("✅ Message saved to database", [
            'message_id' => $messageId,
            'remote_jid' => $remoteJid
        ]);

        // Update or create contact
        $isGroup = strpos($remoteJid, '@g.us') !== false;
        $type = $isGroup ? 'group' : 'individual';

        $name = $pushName;
        $groupName = '';
        $number = '';

        if ($isGroup) {
            $groupName = $pushName;
        } else {
            $number = explode('@', $remoteJid)[0];
        }

        // Only update name if it's an incoming message or contact doesn't exist
        // Use INSERT OR IGNORE then UPDATE if needed, or simple logic:
        // Try to get existing contact
        $existing = $pdo->query("SELECT name FROM whatsapp_contacts WHERE jid = '$remoteJid'")->fetch();

        if (!$fromMe || !$existing) {
            $contactStmt = $pdo->prepare("
                INSERT OR REPLACE INTO whatsapp_contacts 
                (jid, name, group_name, number, type, last_message_time, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $contactStmt->execute([
                $remoteJid,
                $name,
                $groupName,
                $number,
                $type,
                date('Y-m-d H:i:s', $timestamp)
            ]);
        } else {
            // Just update timestamp
            $contactStmt = $pdo->prepare("UPDATE whatsapp_contacts SET last_message_time = ? WHERE jid = ?");
            $contactStmt->execute([date('Y-m-d H:i:s', $timestamp), $remoteJid]);
        }

        Logger::logInfo("✅ Contact updated", ['jid' => $remoteJid]);

    } elseif ($event === 'messaging-history.set') {
        Logger::logInfo("📚 Processing messaging-history.set event");

        $messages = $data['messages'] ?? [];
        $count = 0;

        foreach ($messages as $m) {
            // Re-use logic for message extraction (simplified for loop)
            $key = $m['key'] ?? [];
            $remoteJid = $key['remoteJid'] ?? '';
            $fromMe = $key['fromMe'] ?? false;
            $messageId = $key['id'] ?? uniqid();

            if (!$remoteJid)
                continue;

            $msgContent = $m['message'] ?? [];
            $content = '';
            $msgType = 'unknown';

            if (isset($msgContent['conversation'])) {
                $content = $msgContent['conversation'];
                $msgType = 'text';
            } elseif (isset($msgContent['extendedTextMessage'])) {
                $content = $msgContent['extendedTextMessage']['text'] ?? '';
                $msgType = 'text';
            } elseif (isset($msgContent['ephemeralMessage'])) {
                $content = '[Süreli Mesaj]';
                $inner = $msgContent['ephemeralMessage']['message'] ?? [];
                if (isset($inner['conversation'])) {
                    $content = $inner['conversation'];
                    $msgType = 'text';
                } elseif (isset($inner['extendedTextMessage'])) {
                    $content = $inner['extendedTextMessage']['text'] ?? '';
                    $msgType = 'text';
                }
            } else {
                // Fallback
                $content = '[Diğer Mesaj]';
                $msgType = 'other';
            }

            $timestamp = $m['messageTimestamp'] ?? time();
            $pushName = $m['pushName'] ?? '';

            // Insert
            $stmt = $pdo->prepare("
                INSERT OR IGNORE INTO whatsapp_messages 
                (remote_jid, message_id, from_me, push_name, content, message_type, timestamp, raw_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $remoteJid,
                $messageId,
                $fromMe ? 1 : 0,
                $pushName,
                $content,
                $msgType,
                $timestamp,
                json_encode($m)
            ]);

            // Auto-save contact if incoming (Update if exists)
            // Fix: Check if exists to avoid overwriting with empty pushNames in history
            if (!$fromMe && $pushName) {
                $number = explode('@', $remoteJid)[0];
                $contactStmt = $pdo->prepare("
                    INSERT OR REPLACE INTO whatsapp_contacts 
                    (jid, name, number, type, last_message_time, updated_at)
                    VALUES (?, ?, ?, 'individual', ?, CURRENT_TIMESTAMP)
                 ");
                $contactStmt->execute([$remoteJid, $pushName, $number, date('Y-m-d H:i:s', $timestamp)]);
            }

            $count++;
        }
        Logger::logInfo("✅ History synced: $count messages saved");

    } else {
        Logger::logInfo("ℹ️ Unhandled event type: $event");
    }

    // Send success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Webhook processed',
        'event' => $event
    ]);

} catch (Exception $e) {
    Logger::logError("❌ Webhook processing error", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
