<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/logger.php';

// Auto-Migration
try {
    $pdo->query("SELECT wa_2fa_enabled FROM users LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN wa_2fa_enabled INTEGER DEFAULT 0");
        $pdo->exec("ALTER TABLE users ADD COLUMN wa_2fa_code TEXT");
        $pdo->exec("ALTER TABLE users ADD COLUMN wa_2fa_expiry TEXT");
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            ip_address TEXT,
            status TEXT,
            details TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } catch (Exception $ex) {
    }
}
//deneme
// Auto-Migration for active/passive users
try {
    $pdo->query("SELECT is_active FROM users LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1");
    } catch (Exception $ex) {
    }
}

// Redirect if logged in
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Auto-Migration for WhatsApp
try {
    $pdo->query("SELECT 1 FROM whatsapp_messages LIMIT 1");
} catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            remote_jid TEXT,
            message_id TEXT UNIQUE,
            from_me INTEGER DEFAULT 0,
            push_name TEXT,
            content TEXT,
            message_type TEXT,
            timestamp INTEGER,
            raw_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_wa_jid ON whatsapp_messages(remote_jid)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_wa_timestamp ON whatsapp_messages(timestamp)");

        $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_contacts (
            jid TEXT PRIMARY KEY,
            name TEXT,
            group_name TEXT,
            number TEXT,
            type TEXT DEFAULT 'individual',
            last_message_time DATETIME,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_imported INTEGER DEFAULT 0
        )");
    } catch (Exception $ex) {
    }
}

$error = '';
$success = '';

// CANCEL 2FA HANDLER (Before showing 2FA screen)
if (isset($_GET['action']) && $_GET['action'] === 'cancel_2fa') {
    // Destroy entire session to be safe and start fresh
    session_unset();
    session_destroy();

    // Redirect to clean index
    header("Location: index.php");
    exit;
}

$show_2fa = isset($_SESSION['2fa_pending_user_id']);
$masked_phone = '';

// Get User info for Masked Phone
if ($show_2fa) {
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['2fa_pending_user_id']]);
    $pUser = $stmt->fetch();
    if ($pUser && $pUser['phone']) {
        $raw = preg_replace('/\D/', '', $pUser['phone']);
        if (strlen($raw) > 4) {
            $masked_phone = '******' . substr($raw, -4);
        } else {
            $masked_phone = '******';
        }
    }
}

// RESEND CODE HANDLER
if (isset($_GET['action']) && $_GET['action'] === 'resend_2fa' && $show_2fa) {
    $pending_user_id = $_SESSION['2fa_pending_user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$pending_user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $last_expiry = strtotime($user['wa_2fa_expiry'] ?? 0);
        $last_sent = $last_expiry - 300;

        if (time() - $last_sent < 60) {
            $error = 'Lütfen yeni kod istemeden önce 1 dakika bekleyin.';
        } else {
            $code = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', time() + 300);
            $pdo->prepare("UPDATE users SET wa_2fa_code = ?, wa_2fa_expiry = ? WHERE id = ?")->execute([$code, $expiry, $user['id']]);

            // WhatsApp
            $whatsapp_sent = false;
            if ($user['phone']) {
                $phone = $user['phone'];
                $clean = preg_replace('/\D/', '', $phone);
                if (substr($clean, 0, 1) === '0')
                    $clean = substr($clean, 1);
                if (substr($clean, 0, 2) !== '90')
                    $clean = '90' . $clean;
                $jid = $clean . "@s.whatsapp.net";

                $config = getEvolutionConfig($pdo);
                if ($config) {
                    $message = "🔐 *Giriş Doğrulama Kodu*\n\nDReklam paneline giriş yapmak için YENİ kodunuz: *$code*\n\nBu kod 5 dakika geçerlidir.";
                    $payload = ["number" => $jid, "text" => $message, "delay" => 1200];
                    $evoRes = callEvolutionApi('message/sendText', 'POST', $payload, $config);
                    if (!isset($evoRes['error'])) {
                        $whatsapp_sent = true;
                    }
                }
            }

            // Email
            $email_sent = false;
            if ($user['email']) {
                $subject = "Giriş Doğrulama Kodunuz";
                $body = "<h2>Giriş Doğrulama Kodu</h2><p>Merhaba {$user['name_surname']},</p><p>Sisteme giriş yapmak için YENİ kodunuz: <strong>$code</strong></p><p>Bu kod 5 dakika boyunca geçerlidir.</p>";
                $email_sent = send_email_notification($pdo, $user['email'], $subject, $body);
            }

            $details = "Resent code.";
            if ($whatsapp_sent)
                $details .= " Sent to WP.";
            if ($email_sent)
                $details .= " Sent to Email.";

            $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, '2fa_resent', ?)");
            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $details]);

            if ($whatsapp_sent || $email_sent) {
                $success = 'Yeni kod gönderildi. ' . ($email_sent ? ' (Email ve WhatsApp)' : '(WhatsApp)');
            } else {
                $error = 'Kod gönderilemedi. İletişim bilgilerinizi kontrol edin.';
            }
        }
    }
}

// 2FA VERIFICATION HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['2fa_code'])) {
    $code = trim($_POST['2fa_code']);
    $pending_user_id = $_SESSION['2fa_pending_user_id'] ?? null;

    if ($pending_user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$pending_user_id]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['wa_2fa_code'] === $code && strtotime($user['wa_2fa_expiry']) > time()) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name_surname'] = $user['name_surname'];
                $_SESSION['can_send_whatsapp'] = ($user['role'] === 'admin') ? 1 : ($user['can_send_whatsapp'] ?? 0);
                $_SESSION['can_send_email'] = ($user['role'] === 'admin') ? 1 : ($user['can_send_email'] ?? 0);

                $pdo->prepare("UPDATE users SET wa_2fa_code = NULL, wa_2fa_expiry = NULL WHERE id = ?")->execute([$user['id']]);
                unset($_SESSION['2fa_pending_user_id']);

                $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, 'success', '2FA Success')");
                $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Geçersiz veya süresi dolmuş kod!';
                $show_2fa = true;
                $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, 'failed', 'Invalid 2FA Code')");
                $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
            }
        } else {
            $error = 'Kullanıcı bulunamadı.';
            unset($_SESSION['2fa_pending_user_id']);
            $show_2fa = false;
        }
    } else {
        $error = 'Oturum zaman aşımına uğradı, lütfen tekrar giriş yapın.';
        $show_2fa = false;
    }
}
// LOGIN HANDLER
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                // Check Active Status
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    $error = 'Hesabınız pasif durumdadır. Yönetici ile iletişime geçiniz.';
                    try {
                        $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, 'failed', 'Account Passive')");
                        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                    } catch (Exception $e) {
                    }
                } else {
                    if (!empty($user['wa_2fa_enabled']) && $user['wa_2fa_enabled'] == 1) {
                        $code = rand(100000, 999999);
                        $expiry = date('Y-m-d H:i:s', time() + 300);

                        $pdo->prepare("UPDATE users SET wa_2fa_code = ?, wa_2fa_expiry = ? WHERE id = ?")->execute([$code, $expiry, $user['id']]);

                        $whatsapp_sent = false;
                        if ($user['phone']) {
                            $phone = $user['phone'];
                            $clean = preg_replace('/\D/', '', $phone);
                            if (substr($clean, 0, 1) === '0')
                                $clean = substr($clean, 1);
                            if (substr($clean, 0, 2) !== '90')
                                $clean = '90' . $clean;
                            $jid = $clean . "@s.whatsapp.net";

                            $config = getEvolutionConfig($pdo);
                            if ($config) {
                                $message = "🔐 *Giriş Doğrulama Kodu*\n\nDReklam paneline giriş yapmak için kodunuz: *$code*\n\nBu kod 5 dakika geçerlidir.";
                                $payload = ["number" => $jid, "text" => $message, "delay" => 1200];
                                $evoRes = callEvolutionApi('message/sendText', 'POST', $payload, $config);
                                if (isset($evoRes['error'])) {
                                    Logger::logError("2FA Send Failed", $evoRes);
                                } else {
                                    $whatsapp_sent = true;
                                }
                            }
                        }

                        $email_sent = false;
                        if ($user['email']) {
                            $subject = "Giriş Doğrulama Kodunuz";
                            $body = "<h2>Giriş Doğrulama Kodu</h2><p>Merhaba {$user['name_surname']},</p><p>Sisteme giriş yapmak için kodunuz: <strong>$code</strong></p><p>Bu kod 5 dakika boyunca geçerlidir.</p>";
                            $email_sent = send_email_notification($pdo, $user['email'], $subject, $body);
                        }

                        $details = "Code generated.";
                        if ($whatsapp_sent)
                            $details .= " Sent to WP.";
                        if ($email_sent)
                            $details .= " Sent to Email.";

                        try {
                            $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, '2fa_sent', ?)");
                            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'], $details]);
                        } catch (Exception $e) {
                        }

                        $_SESSION['2fa_pending_user_id'] = $user['id'];
                        $show_2fa = true;

                        // Re-fetch masked phone for instant display
                        $raw = preg_replace('/\D/', '', $user['phone']);
                        $masked_phone = (strlen($raw) > 4) ? '******' . substr($raw, -4) : '******';

                        if (!$whatsapp_sent && !$email_sent) {
                            $error = 'Doğrulama kodu gönderilemedi.';
                        }

                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['name_surname'] = $user['name_surname'];
                        $_SESSION['can_send_whatsapp'] = ($user['role'] === 'admin') ? 1 : ($user['can_send_whatsapp'] ?? 0);
                        $_SESSION['can_send_email'] = ($user['role'] === 'admin') ? 1 : ($user['can_send_email'] ?? 0);

                        try {
                            $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, 'success', 'Direct Login')");
                            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                        } catch (Exception $e) {
                        }

                        header('Location: dashboard.php');
                        exit;
                    }
                } // End of active user check else
            } else {
                $error = 'Kullanıcı adı veya şifre hatalı!';
                if ($user) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, ip_address, status, details) VALUES (?, ?, 'failed', 'Wrong Password')");
                        $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);
                    } catch (Exception $e) {
                    }
                }
            }
        } catch (PDOException $e) {
            $errorMsg = 'Login DB Error: ' . $e->getMessage() . ' Code: ' . $e->getCode();
            Logger::logError($errorMsg, ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
            $error = 'Bir hata oluştu: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')';
        }
    } else {
        $error = 'Tüm alanları doldurun!';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - DReklam Site Takip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-2xl mb-4">
                <i class="fa-solid fa-globe text-indigo-600 text-4xl"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">DReklam</h1>
            <p class="text-lg text-indigo-100 font-medium">Site Takip Yönetimi</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded flex items-center gap-2">
                    <i class="fa-solid fa-exclamation-circle"></i> <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div
                    class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded flex items-center gap-2">
                    <i class="fa-solid fa-check-circle"></i> <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($show_2fa): ?>
                <form method="POST" action="" class="space-y-6">
                    <div class="text-center mb-4">
                        <div class="inline-flex items-center justify-center w-12 h-12 bg-green-100 rounded-full mb-3">
                            <i class="fa-brands fa-whatsapp text-green-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Doğrulama Kodu</h3>
                        <p class="text-sm text-gray-600">
                            <strong><?= $masked_phone ?></strong> numaralı WhatsApp ve kayıtlı E-posta adresinize gönderilen
                            kodu giriniz.
                        </p>
                    </div>

                    <div>
                        <input type="text" name="2fa_code" required autofocus autocomplete="off"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition text-center text-xl tracking-widest"
                            placeholder="******" maxlength="6">
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-green-600 to-teal-600 text-white font-bold py-3 px-6 rounded-lg hover:from-green-700 hover:to-teal-700 transform hover:scale-105 transition shadow-lg">
                        <i class="fa-solid fa-check-circle mr-2"></i>Doğrula
                    </button>

                    <div class="text-center mt-4 space-y-2">
                        <div id="countdown" class="text-xs text-gray-400">Tekrar gönder: <span id="timer">60</span> sn</div>
                        <a href="?action=resend_2fa" id="resendBtn"
                            class="hidden text-sm text-green-600 hover:text-green-800 font-semibold underline">
                            <i class="fa-solid fa-sync mr-1"></i>Kodu Tekrar Gönder
                        </a>
                    </div>

                    <div class="text-center mt-2 border-t pt-3">
                        <a href="?action=cancel_2fa" class="text-xs text-gray-500 hover:text-gray-700">Farklı hesaba geç /
                            İptal</a>
                    </div>
                </form>
                <script>
                    let timeLeft = 60;
                    const timer = setInterval(function () {
                        timeLeft--;
                        document.getElementById('timer').innerText = timeLeft;
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            document.getElementById('countdown').classList.add('hidden');
                            document.getElementById('resendBtn').classList.remove('hidden');
                        }
                    }, 1000);
                </script>
            <?php else: ?>
                <form method="POST" action="" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fa-solid fa-user text-indigo-600 mr-2"></i>Kullanıcı Adı
                        </label>
                        <input type="text" name="username" required autofocus
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition"
                            placeholder="Kullanıcı adınızı girin">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fa-solid fa-lock text-indigo-600 mr-2"></i>Şifre
                        </label>
                        <input type="password" name="password" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 transition"
                            placeholder="Şifrenizi girin">
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:from-indigo-700 hover:to-purple-700 transform hover:scale-105 transition shadow-lg">
                        <i class="fa-solid fa-sign-in-alt mr-2"></i>Giriş Yap
                    </button>
                </form>
            <?php endif; ?>

            <!-- Footer Removed -->
        </div>
    </div>
</body>

</html>