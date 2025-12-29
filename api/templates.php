<?php
// api/templates.php - Mesaj Şablonları API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

while (ob_get_level())
    ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_login();
// require_admin(); // Removed global restriction

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT * FROM message_templates ORDER BY id ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $templates]);
        exit;
    }

    if ($action === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM message_templates WHERE id = ?");
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($template) {
            echo json_encode(['status' => 'success', 'data' => $template]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Şablon bulunamadı']);
        }
        exit;
    }

    if ($action === 'save') {
        require_admin(); // Only admin can save
        $id = $_POST['id'] ?? 0;
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $type = $_POST['type'] ?? 'whatsapp';

        if (empty($title) || empty($message)) {
            echo json_encode(['status' => 'error', 'message' => 'Başlık ve mesaj zorunludur']);
            exit;
        }

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE message_templates SET title = ?, message = ?, type = ? WHERE id = ?");
            $stmt->execute([$title, $message, $type, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO message_templates (title, message, type) VALUES (?, ?, ?)");
            $stmt->execute([$title, $message, $type]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Şablon kaydedildi']);
        exit;
    }

    if ($action === 'delete') {
        require_admin(); // Only admin can delete
        $id = $_POST['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM message_templates WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Şablon silindi']);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
