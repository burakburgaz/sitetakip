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
<html lang="tr" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - DReklam Site Takip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --bg-dark: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f8fafc;
            overflow: hidden;
        }

        .bg-blobs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
        }

        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%);
            filter: blur(80px);
            border-radius: 50%;
            animation: move 20s infinite alternate;
        }

        .blob-1 { top: -100px; left: -100px; animation-delay: 0s; }
        .blob-2 { bottom: -100px; right: -100px; animation-delay: -5s; background: linear-gradient(135deg, rgba(14, 165, 233, 0.2) 0%, rgba(34, 197, 94, 0.2) 100%); }
        .blob-3 { top: 40%; left: 60%; animation-delay: -10s; width: 300px; height: 300px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.15) 0%, rgba(249, 115, 22, 0.15) 100%); }

        @keyframes move {
            from { transform: translate(0, 0) rotate(0deg) scale(1); }
            to { transform: translate(100px, 50px) rotate(90deg) scale(1.1); }
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .input-glass {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-glass:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            outline: none;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4);
            filter: brightness(1.1);
        }

        .btn-gradient:active {
            transform: translateY(0);
        }

        .logo-font {
            font-family: 'Outfit', sans-serif;
            letter-spacing: -0.02em;
        }

        .animate-up {
            animation: slideUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }

        /* Custom Scrollbar for modern feel */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6">
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="w-full max-w-[440px] animate-up">
        <div class="text-center mb-10">
            <div class="relative inline-flex mb-6 stagger-1 opacity-0 animate-up" style="animation-fill-mode: forwards;">
                <div class="absolute inset-0 bg-blue-500 blur-2xl opacity-20 animate-pulse"></div>
                <div class="relative flex items-center justify-center w-20 h-20 glass-card rounded-3xl rotate-12 hover:rotate-0 transition-transform duration-500">
                    <i class="fa-solid fa-rocket text-blue-400 text-4xl"></i>
                </div>
            </div>
            <h1 class="text-5xl font-bold logo-font text-white mb-3 stagger-2 opacity-0 animate-up" style="animation-fill-mode: forwards;">
                DReklam
            </h1>
            <p class="text-blue-200/60 font-medium tracking-widest uppercase text-xs stagger-3 opacity-0 animate-up" style="animation-fill-mode: forwards;">
                Site Takip Yönetimi
            </p>
        </div>

        <div class="glass-card rounded-[2.5rem] p-10 stagger-3 opacity-0 animate-up shadow-[0_32px_64px_-16px_rgba(0,0,0,0.6)]" style="animation-fill-mode: forwards;">
            <?php if ($error): ?>
                <div class="mb-8 p-4 bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl flex items-center gap-3 text-sm">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-8 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center gap-3 text-sm">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($show_2fa): ?>
                <form method="POST" action="" class="space-y-8">
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-14 h-14 bg-emerald-500/10 rounded-2xl mb-4 border border-emerald-500/20">
                            <i class="fa-brands fa-whatsapp text-emerald-400 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Güvenlik Doğrulaması</h3>
                        <p class="text-sm text-slate-400 leading-relaxed px-4">
                            <span class="text-emerald-400 font-semibold"><?= $masked_phone ?></span> numaralı WhatsApp ve kayıtlı E-posta adresinize gönderilen 6 haneli kodu giriniz.
                        </p>
                    </div>

                    <div class="relative">
                        <input type="text" name="2fa_code" required autofocus autocomplete="off"
                            class="w-full px-6 py-5 input-glass rounded-2xl text-center text-3xl tracking-[0.5em] font-bold placeholder:text-slate-700"
                            placeholder="000000" maxlength="6">
                        <div class="absolute -bottom-6 left-0 right-0 text-center">
                             <div id="countdown" class="text-[10px] text-slate-500 uppercase tracking-widest">Yeni kod için bekleyin: <span id="timer" class="text-slate-300">60</span>s</div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full btn-gradient text-white font-bold py-5 rounded-2xl shadow-lg flex items-center justify-center gap-3 group">
                            <span>Sisteme Giriş Yap</span>
                            <i class="fa-solid fa-chevron-right text-xs group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>

                    <div class="flex flex-col items-center gap-4 pt-2">
                        <a href="?action=resend_2fa" id="resendBtn"
                            class="hidden text-sm text-emerald-400 hover:text-emerald-300 font-semibold transition-colors flex items-center gap-2">
                            <i class="fa-solid fa-rotate"></i>
                            <span>Kodu Tekrar Gönder</span>
                        </a>
                        <a href="?action=cancel_2fa" class="text-xs text-slate-500 hover:text-slate-300 transition-colors border-b border-transparent hover:border-slate-700 pb-0.5">
                            Farklı hesapla giriş yap
                        </a>
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
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">
                            Kullanıcı Adı
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fa-solid fa-user text-slate-500 group-focus-within:text-blue-400 transition-colors"></i>
                            </div>
                            <input type="text" name="username" required autofocus
                                class="w-full pl-12 pr-6 py-4 input-glass rounded-2xl transition-all"
                                placeholder="kullanıcı_adi">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest ml-1">
                            Şifre
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <i class="fa-solid fa-shield-halved text-slate-500 group-focus-within:text-blue-400 transition-colors"></i>
                            </div>
                            <input type="password" name="password" required
                                class="w-full pl-12 pr-6 py-4 input-glass rounded-2xl transition-all"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full btn-gradient text-white font-bold py-5 rounded-2xl shadow-lg flex items-center justify-center gap-3 group">
                            <span>Oturum Aç</span>
                            <i class="fa-solid fa-arrow-right-to-bracket group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="mt-8 text-center stagger-3 opacity-0 animate-up" style="animation-fill-mode: forwards; animation-delay: 0.5s;">
            <p class="text-slate-500 text-xs tracking-wider">
                &copy; <?= date('Y') ?> DReklam. Tüm hakları saklıdır.
            </p>
        </div>
    </div>
</body>

</html>