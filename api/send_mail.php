<?php
// api/send_mail.php - Mail Gönderme API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/smtp.php';

while (ob_get_level())
    ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=utf-8');

require_login();

$action = $_POST['action'] ?? '';

try {
    // Get SMTP Settings
    $stmt = $pdo->query("SELECT key, value FROM settings WHERE key LIKE 'smtp_%'");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['key']] = $row['value'];
    }

    if (empty($config['smtp_host'])) {
        echo json_encode(['status' => 'error', 'message' => 'SMTP ayarları yapılandırılmamış']);
        exit;
    }

    // Initialize SMTP
    $smtp = new SMTP(
        $config['smtp_host'],
        $config['smtp_port'],
        $config['smtp_user'],
        $config['smtp_pass'],
        $config['smtp_security']
    );

    $fromEmail = $config['smtp_from_email'];
    $fromName = $config['smtp_from_name'];

    if ($action === 'test') {
        require_admin();
        $to = $_POST['test_email'] ?? $fromEmail;
        $subject = 'SMTP Test Mesajı';
        $body = '<h1>SMTP Bağlantısı Başarılı!</h1><p>Bu bir test mesajıdır.</p>';

        if ($smtp->send($to, $subject, $body, $fromEmail, $fromName)) {
            echo json_encode(['status' => 'success', 'message' => 'Test maili başarıyla gönderildi!']);
        } else {
            $logs = $smtp->getLogs();
            echo json_encode(['status' => 'error', 'message' => 'Mail gönderilemedi', 'logs' => $logs]);
        }
    } elseif ($action === 'send') {
        $to = $_POST['to'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['message'] ?? '';
        $site_id = $_POST['site_id'] ?? 0;

        if (empty($to) || empty($subject) || empty($body)) {
            echo json_encode(['status' => 'error', 'message' => 'Tüm alanları doldurun']);
            exit;
        }

        // Template interpolation if raw message is provided
        // But frontend usually does the interpolation.

        // Wrap body in HTML
        $htmlBody = "<html><body>" . nl2br($body) . "</body></html>";

        if ($smtp->send($to, $subject, $htmlBody, $fromEmail, $fromName)) {
            // Log activity
            if ($site_id) {
                // Fetch site info
                $sStmt = $pdo->prepare("SELECT domain FROM sites WHERE id = ?");
                $sStmt->execute([$site_id]);
                $sDomain = $sStmt->fetchColumn();

                // Log to history
                log_activity($pdo, 'Mail Gönderildi', "Site: $sDomain | Kime: $to | Konu: $subject");
            }
            echo json_encode(['status' => 'success', 'message' => 'Mail başarıyla gönderildi']);
        } else {
            $logs = $smtp->getLogs();
            echo json_encode(['status' => 'error', 'message' => 'Mail gönderilemedi', 'logs' => $logs]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
