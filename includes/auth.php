<?php
// includes/auth.php - Oturum yönetimi ve yetkilendirme

// Çerez ayarlarını yapılandır (PHP 7.3+)
if (PHP_VERSION_ID >= 70300) {
    $isSecure = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_set_cookie_params([
        'lifetime' => 0,                    // Browser kapatılınca son bulsun
        'path' => '/',
        'secure' => $isSecure,              // HTTPS'de çalışırsa secure olsun
        'httponly' => true,                 // JavaScript erişimi engelle (XSS koruması)
        'samesite' => 'Lax'                 // CSRF koruması (Lax = aynı site + güvenli cross-site)
    ]);
} else {
    // PHP 7.2 ve altı için fallback
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
}

// Session başlat (eğer başlamadıysa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session başlatma işareti
if (!isset($_SESSION['initiated'])) {
    $_SESSION['initiated'] = true;
}

function require_login()
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}

function require_admin()
{
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die('Bu sayfaya erişim yetkiniz yok.');
    }
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_name()
{
    return $_SESSION['name_surname'] ?? 'Kullanıcı';
}

function get_current_user_role()
{
    return $_SESSION['role'] ?? null;
}
