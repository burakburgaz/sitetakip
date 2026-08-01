<?php
// api/whatsapp.php - Evolution WhatsApp API Integration (CLEANED)

// Production mode - Disable error display (errors will be logged instead)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Set error handler to prevent HTML errors from breaking JSON
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Log error instead of displaying
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    // Don't execute PHP internal error handler
    return true;
});

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/api_security.php';

header('Content-Type: application/json; charset=utf-8');

require_login();

// Helper to get Evolution Config
// Helper functions moved to includes/functions.php

$action = $_REQUEST['action'] ?? '';

try {
    // 1. SYNC STEPS
    // Step 1: Check Connection
    if ($action === 'check_connection') {
        Logger::logInfo("🔵 Connection Check Started");
        $config = getEvolutionConfig($pdo);
        if (!$config) {
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        // Simple ping or state check
        $res = callEvolutionApi('instance/connectionState', 'GET', [], $config);

        if ($res['code'] === 200) {
            $state = $res['body']['instance']['state'] ?? 'UNKNOWN';
            if ($state === 'open') {
                json_response(['status' => 'success', 'message' => 'API ve Telefon Bağlı']);
            } else {
                json_response(['status' => 'error', 'message' => "Telefon bağlı değil (Durum: $state)"], 400);
            }
        } else {
            json_response(['status' => 'error', 'message' => 'API Erişim Hatası'], 500);
        }
    }

    // Step 2: Fetch Remote Chats (WITH PAGINATION \u0026 PROGRESSIVE LOGGING)
    if ($action === 'fetch_remote_chats') {
        Logger::logInfo("🔵 Remote Chat Fetch Started");
        $config = getEvolutionConfig($pdo);
        if (!$config) {
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        // Initialize session for progress tracking
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['sync_progress'] = [];
        $_SESSION['sync_logs'] = [];

        $addLog = function ($message) {
            if (!isset($_SESSION['sync_logs']))
                $_SESSION['sync_logs'] = [];
            $_SESSION['sync_logs'][] = [
                'time' => date('H:i:s'),
                'message' => $message
            ];
            session_write_close();
            session_start();
        };

        $addLog("🚀 Senkronizasyon başlatıldı");
        $addLog("📡 Evolution API'ye bağlanılıyor...");

        $totalChats = 0;
        $totalContacts = 0;
        $page = 1;
        $limit = 100; // Per page
        $hasMore = true;
        $processedNames = [];

        $stmt = $pdo->prepare("
            INSERT OR REPLACE INTO whatsapp_contacts 
            (jid, name, group_name, number, type, last_message_time, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");

        while ($hasMore) {
            $addLog("📄 Sayfa $page çekiliyor...");

            // Fetch Chats with pagination
            $payload = [
                "where" => [],
                "limit" => $limit,
                "page" => $page
            ];

            $res = callEvolutionApi('chat/findChats', 'POST', $payload, $config);

            if ($res['code'] !== 200 && $res['code'] !== 201) {
                $addLog("⚠️ API Hatası: HTTP {$res['code']}");

                // Try GET fallback for first page only
                if ($page === 1) {
                    $addLog("🔄 GET yöntemi deneniyor...");
                    $res = callEvolutionApi('chat/findChats', 'GET', [], $config);
                    if ($res['code'] !== 200) {
                        $addLog("❌ Sohbetler alınamadı");
                        json_response(['status' => 'error', 'message' => 'Sohbetler alınamadı (API Error)', 'debug' => $res, 'logs' => $_SESSION['sync_logs']], 500);
                    }
                }
            }

            $chats = [];
            if (isset($res['body']['records'])) {
                $chats = $res['body']['records'];
            } elseif (isset($res['body']) && is_array($res['body'])) {
                $chats = $res['body'];
            }

            $chatCount = count($chats);
            $addLog("✅ Sayfa $page: $chatCount sohbet bulundu");

            if (empty($chats)) {
                $hasMore = false;
                $addLog("ℹ️ Daha fazla sohbet yok");
                break;
            }

            // Process each chat
            foreach ($chats as $chat) {
                $jid = $chat['id'] ?? $chat['remoteJid'] ?? '';
                if (!$jid)
                    continue;

                $name = $chat['pushName'] ?? $chat['name'] ?? 'Bilinmiyor';
                $isGroup = strpos($jid, '@g.us') !== false;
                $type = $isGroup ? 'group' : 'individual';

                $number = explode('@', $jid)[0];
                $groupName = $isGroup ? $name : '';

                // Last message timestamp
                $lastTs = $chat['messageTimestamp'] ?? $chat['conversationTimestamp'] ?? time();
                if (is_numeric($lastTs)) {
                    if (strlen((string) $lastTs) > 10)
                        $lastTs = $lastTs / 1000;
                    $lastTime = date('Y-m-d H:i:s', $lastTs);
                } else {
                    $lastTime = date('Y-m-d H:i:s');
                }

                $stmt->execute([$jid, $name, $groupName, $number, $type, $lastTime]);
                $processedNames[] = $isGroup ? "$name (Grup)" : $name;
                $totalContacts++;
            }

            $totalChats += $chatCount;
            $addLog("💾 Sayfa $page işlendi: $totalContacts toplam kişi/grup");

            // Check if there are more pages
            if ($chatCount < $limit) {
                $hasMore = false;
                $addLog("ℹ️ Son sayfa işlendi");
            } else {
                $page++;
                $addLog("➡️ Bir sonraki sayfaya geçiliyor...");
            }

            // Safety limit: max 20 pages (2000 chats)
            if ($page > 20) {
                $addLog("⚠️ Güvenlik limiti: 20 sayfa işlendi, durduruluyor");
                $hasMore = false;
            }
        }

        $addLog("✅ Senkronizasyon başarıyla tamamlandı!");
        $addLog("📊 Toplam: $totalContacts kişi/grup güncellendi");

        Logger::logInfo("✅ Remote chat fetch completed", [
            'total_pages' => $page - 1,
            'total_chats' => $totalChats,
            'total_contacts' => $totalContacts
        ]);

        session_write_close();

        json_response([
            'status' => 'success',
            'message' => 'Sohbetler başarıyla getirildi',
            'count' => $totalContacts,
            'pages' => $page - 1,
            'names' => array_slice($processedNames, 0, 10), // First 10 for preview
            'logs' => $_SESSION['sync_logs'] ?? []
        ]);
    }


    // 2. LIST CONTACTS (Local DB)
    if ($action === 'list_contacts') {
        $type = $_GET['type'] ?? 'all';

        // Join with messages to get unread count
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN m.from_me = 0 AND m.is_read = 0 THEN 1 END) as unread_count
                FROM whatsapp_contacts c
                LEFT JOIN whatsapp_messages m ON c.jid = m.remote_jid
                WHERE 1=1";

        if ($type === 'group')
            $sql .= " AND c.type = 'group'";
        if ($type === 'individual')
            $sql .= " AND c.type = 'individual'";

        $sql .= " GROUP BY c.jid
                  ORDER BY c.last_message_time DESC
                  LIMIT 1000";

        $contacts = $pdo->query($sql)->fetchAll();
        json_response(['status' => 'success', 'data' => $contacts]);
    }

    // 3. FETCH MESSAGES
    if ($action === 'fetch_messages') {
        $jid = $_POST['jid'] ?? '';
        if (!$jid) {
            json_response(['status' => 'error', 'message' => 'JID gerekli'], 400);
        }

        // Try database first for recent messages
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_messages WHERE remote_jid = ? ORDER BY timestamp ASC LIMIT 100");
        $stmt->execute([$jid]);
        $dbMessages = $stmt->fetchAll();

        $forceRefresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] == 1;

        // Use database cache ONLY if we have enough messages AND force_refresh is not set
        if (!$forceRefresh && !empty($dbMessages) && count($dbMessages) >= 5) {
            $simplified = [];
            foreach ($dbMessages as $m) {
                $simplified[] = [
                    'id' => $m['message_id'],
                    'fromMe' => (bool) $m['from_me'],
                    'content' => $m['content'],
                    'timestamp' => $m['timestamp'],
                    'sender' => $m['push_name'],
                    'type' => $m['message_type']
                ];
            }
            json_response(['status' => 'success', 'data' => $simplified, 'source' => 'database']);
        }

        // Fetch from API
        $config = getEvolutionConfig($pdo);
        if (!$config) {
            // Return database messages as fallback
            if (!empty($dbMessages)) {
                $simplified = [];
                foreach ($dbMessages as $m) {
                    $simplified[] = [
                        'id' => $m['message_id'],
                        'fromMe' => (bool) $m['from_me'],
                        'content' => $m['content'],
                        'timestamp' => $m['timestamp'],
                        'sender' => $m['push_name'],
                        'type' => $m['message_type']
                    ];
                }
                json_response(['status' => 'success', 'data' => $simplified, 'source' => 'database_fallback']);
            }
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        // Increase limit to 500 to fetch more history
        // STRATEGY: Fetch Page 1 with high limit.
        // Experience shows Page 1 contains newest messages (DESC default).
        // Use 'chat/findMessages' (POST)
        // Evolution API v2 requires nested key structure for proper filtering
        $payload = [
            "where" => [
                "key" => [
                    "remoteJid" => $jid
                ]
            ],
            "limit" => 100,
            "page" => 1,
            "order" => [
                "messageTimestamp" => "DESC"
            ]
        ];

        // Use 'chat/findMessages' (POST) which is verified working
        $res = callEvolutionApi('chat/findMessages', 'POST', $payload, $config);

        // FALLBACK: If payload structure was the issue, try the old one if empty?
        // Or if API fails.
        // But let's assume this structure is better for "all messages".

        // LOGGING
        Logger::logEvolutionAPI('chat/findMessages', 'POST', $payload, $res['code'] ?? 0, $res['body'] ?? [], $res['error'] ?? null);

        $body = $res['body'] ?? [];
        $messages = [];

        if (isset($body['messages']['records'])) {
            $messages = $body['messages']['records'];
        } elseif (isset($body['messages']) && is_array($body['messages'])) {
            $messages = $body['messages'];
        } elseif (isset($body['data']) && is_array($body['data'])) {
            $messages = $body['data'];
        } elseif (is_array($body) && isset($body[0])) {
            $messages = $body;
        }

        // Store in database for future use
        $storeStmt = $pdo->prepare("
            INSERT OR IGNORE INTO whatsapp_messages 
            (remote_jid, message_id, from_me, push_name, content, message_type, timestamp, raw_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Prepare Contact Update Stmt (Auto-save)
        $contactStmt = $pdo->prepare("
            INSERT OR IGNORE INTO whatsapp_contacts (jid, name, number, type, updated_at) 
            VALUES (?, ?, ?, 'individual', CURRENT_TIMESTAMP)
        ");
        $contactUpdateStmt = $pdo->prepare("UPDATE whatsapp_contacts SET name = ? WHERE jid = ? AND (name IS NULL OR name = '' OR name = 'Bilinmiyor')");

        $simplified = [];
        foreach ($messages as $m) {
            if (!is_array($m))
                continue;

            $key = $m['key'] ?? [];

            // STRICT FILTER: Prevent showing messages from other chats
            // Even if API returns them, we must ensure they belong to the requested JID.
            $msgJid = $key['remoteJid'] ?? '';
            if ($msgJid) {
                $msgNum = explode('@', $msgJid)[0];
                $targetNum = explode('@', $jid)[0];
                if ($msgNum !== $targetNum) {
                    continue;
                }
            }

            $fromMe = $key['fromMe'] ?? false;
            $pushName = $m['pushName'] ?? '';

            // Auto-Add to Contacts if incoming
            if (!$fromMe && $pushName) {
                // Try to insert first
                $num = explode('@', $jid)[0];
                $contactStmt->execute([$jid, $pushName, $num]);
                // Or update if name was missing
                $contactUpdateStmt->execute([$pushName, $jid]);
            }

            $msg = $m['message'] ?? [];

            $content = '';
            $msgType = 'unknown';

            if (isset($msg['conversation'])) {
                $content = $msg['conversation'];
                $msgType = 'text';
            } elseif (isset($msg['extendedTextMessage'])) {
                $content = $msg['extendedTextMessage']['text'] ?? '';
                $msgType = 'text';
            } elseif (isset($msg['imageMessage'])) {
                $content = '[Görsel] ' . ($msg['imageMessage']['caption'] ?? '');
                $msgType = 'image';
            } elseif (isset($msg['videoMessage'])) {
                $content = '[Video] ' . ($msg['videoMessage']['caption'] ?? '');
                $msgType = 'video';
            } elseif (isset($msg['documentMessage'])) {
                $content = '[Dosya] ' . ($msg['documentMessage']['fileName'] ?? 'Belge');
                $msgType = 'document';
            } elseif (isset($msg['audioMessage'])) {
                $content = '[Ses Kaydı]';
                $msgType = 'audio';
            } elseif (isset($msg['stickerMessage'])) {
                $content = '[Çıkartma]';
                $msgType = 'sticker';
            } elseif (isset($msg['protocolMessage'])) {
                $content = '[Sistem Mesajı]';
                $msgType = 'system';
            } elseif (isset($msg['reactionMessage'])) {
                $content = 'Reaksiyon: ' . ($msg['reactionMessage']['text'] ?? '');
                $msgType = 'system';
            } elseif (isset($msg['ephemeralMessage'])) {
                $content = '[Süreli Mesaj]';
                $inner = $msg['ephemeralMessage']['message'] ?? [];
                if (isset($inner['conversation'])) {
                    $content = $inner['conversation'];
                    $msgType = 'text';
                } elseif (isset($inner['extendedTextMessage'])) {
                    $content = $inner['extendedTextMessage']['text'] ?? '';
                    $msgType = 'text';
                }
            } elseif (isset($msg['viewOnceMessage'])) {
                $content = '[Bir Kez Görüntülenen Medya]';
                $msgType = 'image';
            } else {
                // Fallback for unknown types
                $keys = array_keys($msg);
                $firstKey = !empty($keys) ? $keys[0] : 'Bilinmeyen';
                $content = "[Desteklenmeyen Mesaj Tipi: $firstKey]";
                $msgType = 'unknown';
            }

            $messageId = $key['id'] ?? uniqid();
            $timestamp = $m['messageTimestamp'] ?? time();

            // Store in DB
            $storeStmt->execute([
                $jid,
                $messageId,
                $fromMe ? 1 : 0,
                $pushName,
                $content,
                $msgType,
                $timestamp,
                json_encode($m)
            ]);

            $simplified[] = [
                'id' => $messageId,
                'fromMe' => $fromMe,
                'content' => $content,
                'timestamp' => $timestamp,
                'sender' => $pushName,
                'type' => $msgType
            ];
        }

        usort($simplified, function ($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        json_response(['status' => 'success', 'data' => $simplified, 'source' => 'api']);
    }

    // 4. GET MESSAGES BY CUSTOMER ID
    if ($action === 'get_messages_by_customer') {
        $customerId = $_GET['customer_id'] ?? 0;

        Logger::logInfo("🔵 GET_MESSAGES_BY_CUSTOMER Action Started", [
            'customer_id' => $customerId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        $phone = $pdo->query("SELECT phone FROM customers WHERE id = $customerId")->fetchColumn();

        Logger::logInfo("Customer phone lookup", [
            'customer_id' => $customerId,
            'phone_found' => $phone ? 'YES' : 'NO',
            'phone' => $phone ?: 'NULL'
        ]);

        if (!$phone) {
            Logger::logError("Customer phone not found", ['customer_id' => $customerId]);
            json_response(['status' => 'error', 'message' => 'Müşteri telefonu bulunamadı'], 404);
        }

        // Format JID
        $clean = preg_replace('/\D/', '', $phone);
        if (substr($clean, 0, 1) === '0')
            $clean = substr($clean, 1);
        if (substr($clean, 0, 2) !== '90')
            $clean = '90' . $clean;
        $jid = $clean . "@s.whatsapp.net";

        Logger::logInfo("JID formatted", [
            'original_phone' => $phone,
            'cleaned_phone' => $clean,
            'jid' => $jid
        ]);

        // Get from database
        $stmt = $pdo->prepare("
            SELECT * FROM whatsapp_messages 
            WHERE remote_jid = ? 
            ORDER BY timestamp ASC 
            LIMIT 100
        ");
        $stmt->execute([$jid]);
        $dbMessages = $stmt->fetchAll();

        Logger::logInfo("Database query completed", [
            'jid' => $jid,
            'message_count' => count($dbMessages),
            'has_messages' => !empty($dbMessages)
        ]);

        if (empty($dbMessages) || count($dbMessages) < 10) {
            // Trigger API fetch if we have very little or no data
            // We can reuse the fetch_messages logic by internal redirection or just calling the API here.

            Logger::logInfo("🔄 DB has minimal data, triggering API sync...", ['jid' => $jid]);

            $config = getEvolutionConfig($pdo);
            if ($config) {
                $payload = ["remoteJid" => $jid, "limit" => 500];
                // Use user-suggested endpoint message/list (GET)
                $res = callEvolutionApi('message/list', 'GET', $payload, $config);
                $body = $res['body'] ?? [];

                $apiMessages = [];
                if (isset($body['messages']['records'])) {
                    $apiMessages = $body['messages']['records'];
                } elseif (isset($body['messages']) && is_array($body['messages'])) {
                    $apiMessages = $body['messages'];
                } elseif (is_array($body) && isset($body[0])) {
                    $apiMessages = $body;
                }

                if (!empty($apiMessages)) {
                    // Save to DB
                    $storeStmt = $pdo->prepare("
                        INSERT OR IGNORE INTO whatsapp_messages 
                        (remote_jid, message_id, from_me, push_name, content, message_type, timestamp, raw_data)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    foreach ($apiMessages as $m) {
                        if (!is_array($m))
                            continue;
                        $key = $m['key'] ?? [];
                        $msgJid = $key['remoteJid'] ?? '';

                        // JID check removed
                        /*
                        if ($msgJid && $msgJid !== $jid) {
                            $check1 = explode('@', $msgJid)[0];
                            $check2 = explode('@', $jid)[0];
                            if ($check1 !== $check2)
                                continue;
                        }
                        */

                        $fromMe = $key['fromMe'] ?? false;
                        $pushName = $m['pushName'] ?? '';
                        $msgContent = $m['message'] ?? [];

                        $content = '';
                        $msgType = 'unknown';

                        if (isset($msgContent['conversation'])) {
                            $content = $msgContent['conversation'];
                            $msgType = 'text';
                        } elseif (isset($msgContent['extendedTextMessage'])) {
                            $content = $msgContent['extendedTextMessage']['text'] ?? '';
                            $msgType = 'text';
                        } elseif (isset($msgContent['imageMessage'])) {
                            $content = '[Görsel]';
                            $msgType = 'image';
                        } elseif (isset($msgContent['reactionMessage'])) {
                            $content = 'Reaksiyon: ' . ($msgContent['reactionMessage']['text'] ?? '');
                            $msgType = 'system';
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
                        } elseif (isset($msgContent['viewOnceMessage'])) {
                            $content = '[Bir Kez Görüntülenen Medya]';
                            $msgType = 'image';
                        } else {
                            // Fallback for unknown types
                            $keys = array_keys($msgContent);
                            $firstKey = !empty($keys) ? $keys[0] : 'Bilinmeyen';
                            $content = "[Desteklenmeyen Mesaj Tipi: $firstKey]";
                            $msgType = 'unknown';
                        }

                        $timestamp = $m['messageTimestamp'] ?? time();
                        $messageId = $key['id'] ?? uniqid();

                        $storeStmt->execute([
                            $jid,
                            $messageId,
                            $fromMe ? 1 : 0,
                            $pushName,
                            $content,
                            $msgType,
                            $timestamp,
                            json_encode($m)
                        ]);
                    }

                    // Re-query DB after sync to get sorted full list
                    $stmt = $pdo->prepare("
                        SELECT * FROM whatsapp_messages 
                        WHERE remote_jid = ? 
                        ORDER BY timestamp ASC 
                        LIMIT 500
                    ");
                    $stmt->execute([$jid]);
                    $dbMessages = $stmt->fetchAll();
                }
            }
        }

        $simplified = [];
        foreach ($dbMessages as $m) {
            $simplified[] = [
                'id' => $m['message_id'],
                'fromMe' => (bool) $m['from_me'],
                'content' => $m['content'],
                'timestamp' => $m['timestamp'],
                'sender' => $m['push_name'],
                'type' => $m['message_type']
            ];
        }

        Logger::logInfo("✅ Returning messages", [
            'customer_id' => $customerId,
            'jid' => $jid,
            'message_count' => count($simplified),
            'source' => 'database'
        ]);

        json_response(['status' => 'success', 'data' => $simplified, 'jid' => $jid, 'source' => 'database']);
    }

    // 5. SEND MESSAGE
    if ($action === 'send_message') {
        $jid = $_POST['jid'] ?? '';
        $message = $_POST['message'] ?? '';

        if (!$jid || !$message) {
            json_response(['status' => 'error', 'message' => 'Eksik bilgi'], 400);
        }

        $config = getEvolutionConfig($pdo);
        if (!$config) {
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        $target = explode('@', $jid)[0];
        if (strpos($jid, '@g.us') !== false) {
            $target = $jid;
        }

        $payload = [
            "number" => $target,
            "text" => $message,
            "delay" => 1200,
            "linkPreview" => false
        ];

        $res = callEvolutionApi('message/sendText', 'POST', $payload, $config);

        if (isset($res['error']) || ($res['code'] !== 200 && $res['code'] !== 201)) {
            $errMsg = isset($res['body']) ? json_encode($res['body']) : ($res['error'] ?? 'Unknown Error');
            json_response(['status' => 'error', 'message' => "Gönderim başarısız ({$res['code']}): $errMsg"], 500);
        }

        json_response(['status' => 'success', 'message' => 'Mesaj gönderildi']);
    }

    // 6. IMPORT TO CUSTOMERS
    if ($action === 'import_to_customers') {
        $jids = $_POST['jids'] ?? [];
        if (empty($jids)) {
            json_response(['status' => 'error', 'message' => 'Seçim yapılmadı'], 400);
        }

        $count = 0;
        foreach ($jids as $jid) {
            $stmt = $pdo->prepare("SELECT * FROM whatsapp_contacts WHERE jid = ?");
            $stmt->execute([$jid]);
            $contact = $stmt->fetch();

            if ($contact) {
                $phone = $contact['number'];
                $name = $contact['name'];

                if ($contact['type'] === 'group') {
                    $name = $contact['group_name'];
                    $phone = null;
                }

                $exists = false;
                if ($phone) {
                    $exists = $pdo->query("SELECT id FROM customers WHERE phone LIKE '%$phone%'")->fetchColumn();
                } else {
                    $exists = $pdo->query("SELECT id FROM customers WHERE full_name = '$name'")->fetchColumn();
                }

                if (!$exists) {
                    $ins = $pdo->prepare("INSERT INTO customers (full_name, phone, email, notes) VALUES (?, ?, '', 'WhatsApp İçe Aktarma')");
                    $ins->execute([$name, $phone]);
                    $count++;

                    $pdo->prepare("UPDATE whatsapp_contacts SET is_imported = 1 WHERE jid = ?")->execute([$jid]);
                }
            }
        }
        json_response(['status' => 'success', 'message' => "$count kişi müşteri olarak eklendi."]);
    }

    // 7. DELETE CONTACTS (New)
    if ($action === 'delete_contacts') {
        $jids = $_POST['jids'] ?? [];
        if (empty($jids)) {
            json_response(['status' => 'error', 'message' => 'Seçim yapılmadı'], 400);
        }

        $count = 0;
        foreach ($jids as $jid) {
            // Delete messages for this JID
            $pdo->prepare("DELETE FROM whatsapp_messages WHERE remote_jid = ?")->execute([$jid]);

            // Delete contact
            $stmt = $pdo->prepare("DELETE FROM whatsapp_contacts WHERE jid = ?");
            $stmt->execute([$jid]);

            if ($stmt->rowCount() > 0) {
                $count++;
            }
        }

        log_activity($pdo, 'WhatsApp Kişi Silindi', "$count kişi ve mesaj geçmişi silindi");
        json_response(['status' => 'success', 'message' => "$count kişi ve tüm mesaj geçmişleri silindi."]);
    }

    // 8. EXPORT EXCEL
    if ($action === 'export_excel') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=whatsapp_rehber.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Isim / Grup Ismi', 'Numara / ID', 'Tur']);

        $rows = $pdo->query("SELECT * FROM whatsapp_contacts ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $displayName = $row['type'] === 'group' ? $row['group_name'] : $row['name'];
            $displayNumber = $row['type'] === 'group' ? $row['jid'] : $row['number'];
            fputcsv($output, [$displayName, $displayNumber, $row['type']]);
        }
        fclose($output);
        exit;
    }

    // 8. SET WEBHOOK
    if ($action === 'set_webhook') {
        Logger::logInfo("🔵 SET WEBHOOK REQUEST STARTED", $_POST);

        $webhookUrl = $_POST['webhook_url'] ?? '';
        $events = $_POST['events'] ?? ['MESSAGES_UPSERT', 'MESSAGES_UPDATE', 'SEND_MESSAGE'];

        $config = getEvolutionConfig($pdo);
        if (!$config) {
            Logger::logError("SET WEBHOOK FAILED: API ayarları eksik", ['config' => $config]);
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        Logger::logInfo("Evolution Config Loaded", [
            'api_url' => $config['evolution_api_url'] ?? 'NOT SET',
            'instance' => $config['evolution_instance_name'] ?? 'NOT SET',
            'api_key' => substr($config['evolution_api_key'] ?? '', 0, 10) . '...'
        ]);

        if (!$webhookUrl) {
            Logger::logError("SET WEBHOOK FAILED: Webhook URL eksik");
            json_response(['status' => 'error', 'message' => 'Webhook URL gerekli'], 400);
        }

        // Evolution API: POST /webhook/set/{instance}
        // Evolution API expects nested format: { "webhook": { "enabled": true, ... } }
        $payload = [
            "webhook" => [
                "enabled" => true,
                "url" => $webhookUrl,
                "events" => $events,
                "webhookByEvents" => false
            ]
        ];

        Logger::logInfo("Sending webhook to Evolution API", [
            'webhook_url' => $webhookUrl,
            'payload' => $payload
        ]);

        $res = callEvolutionApi('webhook/set', 'POST', $payload, $config);

        Logger::logInfo("Webhook API Response Received", [
            'code' => $res['code'] ?? 'NULL',
            'has_error' => isset($res['error']),
            'error' => $res['error'] ?? null,
            'body' => $res['body'] ?? null
        ]);

        if (isset($res['error'])) {
            Logger::logError("CURL Error in SET WEBHOOK", [
                'curl_error' => $res['error'],
                'config' => [
                    'url' => $config['evolution_api_url'],
                    'instance' => $config['evolution_instance_name']
                ]
            ]);
            json_response(['status' => 'error', 'message' => "cURL Hatası: " . $res['error'], 'debug' => $res], 500);
        }

        if ($res['code'] !== 200 && $res['code'] !== 201) {
            $errMsg = isset($res['body']) ? json_encode($res['body']) : 'Bilinmeyen hata';
            Logger::logError("Webhook HTTP Error", [
                'http_code' => $res['code'],
                'response_body' => $res['body'],
                'error_message' => $errMsg
            ]);
            json_response(['status' => 'error', 'message' => "Webhook kaydedilemedi (HTTP {$res['code']}): $errMsg", 'debug' => $res], 500);
        }

        Logger::logInfo("✅ WEBHOOK SET SUCCESS", $res['body']);
        json_response(['status' => 'success', 'message' => 'Webhook başarıyla kaydedildi', 'data' => $res['body']]);
    }

    // 9. GET WEBHOOK STATUS
    if ($action === 'get_webhook') {
        $config = getEvolutionConfig($pdo);
        if (!$config) {
            json_response(['status' => 'error', 'message' => 'API ayarları eksik'], 400);
        }

        // Evolution API: GET /webhook/find/{instance}
        $res = callEvolutionApi('webhook/find', 'GET', [], $config);

        if ($res['code'] === 200) {
            json_response(['status' => 'success', 'data' => $res['body']]);
        } else {
            json_response(['status' => 'error', 'message' => 'Webhook bilgisi alınamadı', 'debug' => $res], 500);
        }
    }

    // 10. GET DEBUG LOGS
    if ($action === 'get_debug_logs') {
        $logDir = __DIR__ . '/../logs';
        $logFiles = glob($logDir . '/evolution_api_*.log'); // Evolution logs
        $webhookFiles = glob($logDir . '/webhook_debug_*.log'); // Webhook logs

        $allFiles = array_merge($logFiles, $webhookFiles);

        // Sort by time desc
        usort($allFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        // Take latest 2 files
        $recentFiles = array_slice($allFiles, 0, 2);

        $content = '';
        foreach ($recentFiles as $file) {
            $content .= "=== FILE: " . basename($file) . " ===\n";
            // Read last 10KB
            // Use tail logic equivalent
            $fileSize = filesize($file);
            if ($fileSize > 20000) {
                $content .= "...(truncated)...\n" . file_get_contents($file, false, null, $fileSize - 20000);
            } else {
                $content .= file_get_contents($file);
            }
            $content .= "\n\n";
        }

        if (empty($content))
            $content = "Log dosyası bulunamadı. Lütfen işlem yapın (örn: Yenile butonu).";

        json_response(['status' => 'success', 'data' => $content]);
    }

    // 11. GET UNREAD CHATS (For floating widget)
    // 11. GET UNREAD CHATS (For floating widget)
    if ($action === 'get_unread_chats') {
        // Prevent session locking for long polling
        session_write_close();

        // 1. Actively poll API for unread chats to ensure real-time data
        $config = getEvolutionConfig($pdo);
        if ($config) {
            // Find chats with unread messages
            // Using 'where' filter for unreadCount > 0 is not always reliable in Evolution v2 depending on the adapter,
            // so we fetch recent chats and filter by unreadCount in PHP.
            // Dynamic limit: polling (10) vs full check (15)
            // We use 15 for 'check_all' because we will make aggressive API calls for each (N+1).
            // 100 would be too slow. 15 covers the widget view.
            $limit = isset($_REQUEST['check_all']) && $_REQUEST['check_all'] == 1 ? 15 : 10;
            $isForceCheck = isset($_REQUEST['check_all']) && $_REQUEST['check_all'] == 1;

            $payload = [
                "limit" => $limit,
                "page" => 1
            ];

            $res = callEvolutionApi('chat/findChats', 'POST', $payload, $config);

            if (($res['code'] === 200 || $res['code'] === 201) && isset($res['body'])) {
                $data = $res['body'];
                $records = $data['records'] ?? ($data['data'] ?? $data);

                if (is_array($records)) {
                    // Optimization: Batch fetch local timestamps for found JIDs
                    $jids = [];
                    foreach ($records as $r) {
                        $jid = $r['id'] ?? $r['remoteJid'] ?? '';
                        if ($jid)
                            $jids[] = $jid;
                    }

                    $dbTimestamps = [];
                    if (!empty($jids)) {
                        $placeholders = implode(',', array_fill(0, count($jids), '?'));
                        $stmtTs = $pdo->prepare("SELECT jid, last_message_time FROM whatsapp_contacts WHERE jid IN ($placeholders)");
                        $stmtTs->execute($jids);
                        while ($row = $stmtTs->fetch(PDO::FETCH_ASSOC)) {
                            $dbTimestamps[$row['jid']] = strtotime($row['last_message_time']);
                        }
                    }

                    foreach ($records as $chat) {
                        // Normalize IDs
                        $jid = $chat['id'] ?? $chat['remoteJid'] ?? '';
                        if (!$jid)
                            continue;

                        $apiTs = $chat['messageTimestamp'] ?? $chat['conversationTimestamp'] ?? 0;
                        if ($apiTs > 2000000000)
                            $apiTs = $apiTs / 1000; // Handle conversions if needed

                        $dbTs = $dbTimestamps[$jid] ?? 0;

                        // If API has newer activity OR unread count > 0, fetch messages
                        $unread = $chat['unreadCount'] ?? 0;

                        // SYNC LOGIC:
                        // 1. If Force Check (Widget Open) -> ALWAYS Sync top chats
                        // 2. If Polling -> Only Sync if API is newer or Unread > 0

                        if ($isForceCheck || $apiTs > $dbTs || $unread > 0) {

                            // 1. Update Contact Info
                            $name = $chat['pushName'] ?? $chat['name'] ?? 'Bilinmiyor';
                            $isGroup = strpos($jid, '@g.us') !== false;
                            $type = 'individual';
                            if ($isGroup) {
                                $type = 'group';
                            } else if (strpos($jid, '@s.whatsapp.net') !== false) {
                                $type = 'status';
                            }
                            $number = explode('@', $jid)[0];
                            $groupName = $isGroup ? $name : '';

                            $upsert = $pdo->prepare("
                                INSERT OR REPLACE INTO whatsapp_contacts
                                (jid, name, group_name, number, type, last_message_time, updated_at)
                                VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                            ");
                            $upsert->execute([$jid, $name, $groupName, $number, $type, date('Y-m-d H:i:s', $apiTs)]);

                            // 2. Fetch Messages (Robust Sync)
                            // We fetch the last 3 messages to sure we have the latest one for preview.
                            $msgRes = callEvolutionApi('chat/findMessages', 'POST', [
                                "where" => [
                                    "key" => ["remoteJid" => $jid]
                                ],
                                "limit" => 3,
                                "page" => 1
                            ], $config);

                            if (isset($msgRes['body']['messages']['records'])) {
                                $msgs = $msgRes['body']['messages']['records'];
                                foreach ($msgs as $m) {
                                    $keyData = $m['key'] ?? [];
                                    $msgId = $keyData['id'] ?? uniqid();
                                    $fromMe = $keyData['fromMe'] ?? false;

                                    $msgContent = $m['message'] ?? [];

                                    // Content Extraction logic
                                    $content = '';
                                    $msgType = 'unknown';

                                    if (isset($msgContent['conversation'])) {
                                        $content = $msgContent['conversation'];
                                        $msgType = 'text';
                                    } elseif (isset($msgContent['extendedTextMessage'])) {
                                        $content = $msgContent['extendedTextMessage']['text'] ?? '';
                                        $msgType = 'text';
                                    } elseif (isset($msgContent['imageMessage'])) {
                                        $content = '[Görsel]';
                                        $msgType = 'image';
                                    } elseif (isset($msgContent['videoMessage'])) {
                                        $content = '[Video]';
                                        $msgType = 'video';
                                    } elseif (isset($msgContent['audioMessage'])) {
                                        $content = '[Ses]';
                                        $msgType = 'audio';
                                    } else {
                                        $content = '[Medya/Diğer]';
                                        $msgType = 'other';
                                    }

                                    // Fix timestamp if ms
                                    $timestamp = $m['messageTimestamp'] ?? time();
                                    if ($timestamp > 2000000000)
                                        $timestamp /= 1000;

                                    $stmt = $pdo->prepare("INSERT OR IGNORE INTO whatsapp_messages (remote_jid, message_id, from_me, push_name, content, message_type, timestamp, is_read, raw_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                    $stmt->execute([
                                        $jid,
                                        $msgId,
                                        $fromMe ? 1 : 0,
                                        $m['pushName'] ?? '',
                                        $content,
                                        $msgType,
                                        $timestamp,
                                        0, // is_read = 0 (logic handles mark as read separately)
                                        json_encode($m)
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        $sql = "SELECT 
                    m.remote_jid as jid,
                    c.name,
                    c.group_name,
                    c.type,
                    datetime(MAX(m.timestamp), 'unixepoch', 'localtime') as last_message_time,
                    SUM(CASE WHEN m.from_me = 0 AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count,
                    (SELECT content FROM whatsapp_messages m2 
                     WHERE m2.remote_jid = m.remote_jid 
                     ORDER BY m2.timestamp DESC LIMIT 1) as last_message
                FROM whatsapp_messages m
                LEFT JOIN whatsapp_contacts c ON m.remote_jid = c.jid
                GROUP BY m.remote_jid
                ORDER BY last_message_time DESC
                LIMIT $limit";

        $stmt = $pdo->query($sql);
        $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total unread (global count)
        $totalUnread = $pdo->query("SELECT COUNT(*) FROM whatsapp_messages WHERE from_me = 0 AND is_read = 0")->fetchColumn();

        foreach ($chats as &$chat) {
            $chat['name'] = $chat['type'] === 'group' ? ($chat['group_name'] ?: $chat['name']) : $chat['name'];
        }

        json_response([
            'status' => 'success',
            'chats' => $chats,
            'total_unread' => $totalUnread
        ]);
    }

    // 12. MARK CHAT AS READ
    if ($action === 'mark_as_read') {
        $jid = $_POST['jid'] ?? '';

        if (!$jid) {
            json_response(['status' => 'error', 'message' => 'JID gerekli'], 400);
        }

        // Mark all messages from this JID as read
        $stmt = $pdo->prepare("UPDATE whatsapp_messages SET is_read = 1 WHERE remote_jid = ? AND from_me = 0");
        $stmt->execute([$jid]);

        $affected = $stmt->rowCount();

        json_response([
            'status' => 'success',
            'message' => "$affected mesaj okundu olarak işaretlendi"
        ]);
    }

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
}
