<?php
// api/backup.php - Yedekleme API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

while (ob_get_level())
    ob_end_clean();

require_login();
require_admin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // Otomatik yedekleme (cron'dan çağrılır)
    if ($action === 'auto_backup') {
        $dbFile = __DIR__ . '/../database.sqlite';
        $backupDir = __DIR__ . '/../backup';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sqlite';

        if (copy($dbFile, $backupFile)) {
            // Eski yedekleri temizle (30 günden eski olanları)
            $files = glob($backupDir . '/backup_*.sqlite');
            foreach ($files as $file) {
                if (filemtime($file) < strtotime('-30 days')) {
                    unlink($file);
                }
            }

            json_response(['status' => 'success', 'message' => 'Yedekleme tamamlandı', 'file' => basename($backupFile)]);
        } else {
            json_response(['status' => 'error', 'message' => 'Yedekleme başarısız'], 500);
        }
    }

    // Yedekleri listele
    if ($action === 'list') {
        header('Content-Type: application/json');
        $backupDir = __DIR__ . '/../backup';
        $backups = [];

        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/backup_*.sqlite');
            rsort($files); // En yeni önce

            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'timestamp' => filemtime($file)
                ];
            }
        }

        json_response(['status' => 'success', 'data' => $backups]);
    }

    // Yedek sil
    if ($action === 'delete') {
        header('Content-Type: application/json');
        $filename = $_POST['filename'] ?? '';

        if (empty($filename) || strpos($filename, '..') !== false) {
            json_response(['status' => 'error', 'message' => 'Geçersiz dosya adı']);
        }

        $backupFile = __DIR__ . '/../backup/' . basename($filename);

        if (file_exists($backupFile) && unlink($backupFile)) {
            log_activity($pdo, 'Yedek Silindi', $filename);
            json_response(['status' => 'success', 'message' => 'Yedek silindi']);
        } else {
            json_response(['status' => 'error', 'message' => 'Dosya bulunamadı']);
        }
    }

    // Yedeği indir (backup klasöründen)
    if ($action === 'download_backup') {
        $filename = $_GET['filename'] ?? '';

        if (empty($filename) || strpos($filename, '..') !== false) {
            die('Geçersiz dosya adı');
        }

        $backupFile = __DIR__ . '/../backup/' . basename($filename);

        if (file_exists($backupFile)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/x-sqlite3');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($backupFile));
            readfile($backupFile);
            exit;
        } else {
            die('Dosya bulunamadı');
        }
    }

    // Yedeği geri yükle (backup klasöründen)
    if ($action === 'restore_backup') {
        header('Content-Type: application/json');
        $filename = $_POST['filename'] ?? '';

        if (empty($filename) || strpos($filename, '..') !== false) {
            json_response(['status' => 'error', 'message' => 'Geçersiz dosya adı']);
        }

        $backupFile = __DIR__ . '/../backup/' . basename($filename);
        $dbFile = __DIR__ . '/../database.sqlite';

        if (!file_exists($backupFile)) {
            json_response(['status' => 'error', 'message' => 'Yedek dosyası bulunamadı']);
        }

        // Önce şu anki DB'yi yedekle
        copy($dbFile, $dbFile . '.pre_restore_' . date('YmdHis'));

        if (copy($backupFile, $dbFile)) {
            log_activity($pdo, 'Yedek Geri Yüklendi', $filename);
            json_response(['status' => 'success', 'message' => 'Yedek başarıyla geri yüklendi']);
        } else {
            json_response(['status' => 'error', 'message' => 'Geri yükleme başarısız']);
        }
    }

    // Manuel yedekleme (download)
    if ($action === 'download_db' || $action === 'download') {
        $dbFile = __DIR__ . '/../database.sqlite';
        if (file_exists($dbFile)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/x-sqlite3');
            header('Content-Disposition: attachment; filename="site_takip_backup_' . date('Y-m-d_H-i') . '.sqlite"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($dbFile));
            readfile($dbFile);
            exit;
        } else {
            die('Veritabanı dosyası bulunamadı');
        }
    }

    // Manuel yedek yükleme
    if ($action === 'restore_db' || $action === 'restore') {
        ob_start();
        header('Content-Type: application/json');

        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            json_response(['status' => 'error', 'message' => 'Dosya yükleme hatası']);
        }

        $tmpName = $_FILES['backup_file']['tmp_name'];
        $dbFile = __DIR__ . '/../database.sqlite';

        // Validate SQLITE header
        $handle = fopen($tmpName, 'rb');
        $header = fread($handle, 16);
        fclose($handle);

        if (strpos($header, 'SQLite format 3') === false) {
            json_response(['status' => 'error', 'message' => 'Geçersiz SQLite dosyası']);
        }

        // Create auto-backup before restore
        copy($dbFile, $dbFile . '.bak_' . date('YmdHis'));

        if (copy($tmpName, $dbFile)) {
            log_activity($pdo, 'Yedek Yüklendi', 'Veritabanı yedeği geri yüklendi');
            json_response(['status' => 'success', 'message' => 'Yedek başarıyla yüklendi']);
        } else {
            json_response(['status' => 'error', 'message' => 'Dosya kopyalama hatası']);
        }
    }

} catch (Exception $e) {
    if (in_array($action, ['restore_db', 'restore', 'list', 'delete', 'restore_backup', 'auto_backup'])) {
        json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
    } else {
        die("Hata: " . $e->getMessage());
    }
}
