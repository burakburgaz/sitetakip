<?php
// Start output buffering and clean everything
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
// api/users.php - Kullanıcı yönetimi API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

require_admin(); // Sadece admin erişebilir

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    // LİSTELE
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, username, name_surname, role, phone, email, can_send_whatsapp, can_send_email, wa_2fa_enabled, is_active, created_at FROM users ORDER BY created_at DESC");
        $users = $stmt->fetchAll();

        // secretary → user olarak dönüştür (frontend için)
        foreach ($users as &$user) {
            if ($user['role'] === 'secretary') {
                $user['role'] = 'user';
            }
        }

        json_response(['status' => 'success', 'data' => $users]);
    }

    // EKLE
    if ($action === 'create') {
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name_surname = sanitize_input($_POST['name_surname'] ?? '');
        $role = $_POST['role'] ?? 'secretary'; // DB constraint: admin, secretary

        // Permissions
        $can_send_whatsapp = isset($_POST['can_send_whatsapp']) ? 1 : 0;
        $can_send_email = isset($_POST['can_send_email']) ? 1 : 0;
        $wa_2fa_enabled = isset($_POST['wa_2fa_enabled']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Frontend'den 'user' gelirse onu 'secretary' olarak kaydet
        if ($role === 'user') {
            $role = 'secretary';
        }

        $phone = format_phone($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');

        if (!$username || !$password || !$name_surname) {
            json_response(['status' => 'error', 'message' => 'Gerekli alanları doldurun'], 400);
        }

        // Kullanıcı adı kontrolü
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            json_response(['status' => 'error', 'message' => 'Bu kullanıcı adı zaten kullanılıyor'], 400);
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (username, password, name_surname, role, phone, email, can_send_whatsapp, can_send_email, wa_2fa_enabled, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $name_surname, $role, $phone, $email, $can_send_whatsapp, $can_send_email, $wa_2fa_enabled, $is_active]);

        log_activity($pdo, 'Kullanıcı Eklendi', "Username: $username");

        json_response(['status' => 'success', 'message' => 'Kullanıcı eklendi']);
    }

    // GÜNCELLE
    if ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $name_surname = sanitize_input($_POST['name_surname'] ?? '');
        $role = $_POST['role'] ?? 'secretary';

        // Permissions
        $can_send_whatsapp = isset($_POST['can_send_whatsapp']) ? 1 : 0;
        $can_send_email = isset($_POST['can_send_email']) ? 1 : 0;
        $wa_2fa_enabled = isset($_POST['wa_2fa_enabled']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Frontend'den 'user' gelirse onu 'secretary' olarak kaydet
        if ($role === 'user') {
            $role = 'secretary';
        }

        $phone = format_phone($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$id || !$name_surname) {
            json_response(['status' => 'error', 'message' => 'Gerekli alanları doldurun'], 400);
        }

        if ($password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name_surname = ?, role = ?, phone = ?, email = ?, password = ?, can_send_whatsapp = ?, can_send_email = ?, wa_2fa_enabled = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name_surname, $role, $phone, $email, $hashed_password, $can_send_whatsapp, $can_send_email, $wa_2fa_enabled, $is_active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name_surname = ?, role = ?, phone = ?, email = ?, can_send_whatsapp = ?, can_send_email = ?, wa_2fa_enabled = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$name_surname, $role, $phone, $email, $can_send_whatsapp, $can_send_email, $wa_2fa_enabled, $is_active, $id]);
        }

        log_activity($pdo, 'Kullanıcı Güncellendi', "ID: $id");

        json_response(['status' => 'success', 'message' => 'Kullanıcı güncellendi']);
    }

    // SİL
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;

        // Kendi hesabını silemez
        if ($id == get_current_user_id()) {
            json_response(['status' => 'error', 'message' => 'Kendi hesabınızı silemezsiniz'], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        log_activity($pdo, 'Kullanıcı Silindi', "ID: $id");

        json_response(['status' => 'success', 'message' => 'Kullanıcı silindi']);
    }

    // LOGLARI GETİR
    if ($action === 'get_login_logs') {
        $user_id = $_GET['user_id'] ?? 0;
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT * FROM login_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->query("SELECT l.*, u.username FROM login_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 100");
        }
        $logs = $stmt->fetchAll();
        json_response(['status' => 'success', 'data' => $logs]);
    }

} catch (Exception $e) {
    error_log('Users API Error: ' . $e->getMessage());
    json_response(['status' => 'error', 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
