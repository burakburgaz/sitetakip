<?php
// Start output buffering and clean everything
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// api/customers.php - Müşteri yönetimi API
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/logger.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    // LİSTELE
    if ($action === 'list' || $action === 'search') {
        $search = $_GET['q'] ?? $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'all';

        $sql = "SELECT c.*, 
                GROUP_CONCAT(s.domain, '||') as sites,
                GROUP_CONCAT(s.id, '||') as site_ids
                FROM customers c
                LEFT JOIN sites s ON c.id = s.customer_id AND s.status = 'active'
                WHERE 1=1";
        $params = [];

        // Filter by status
        if ($filter === 'active') {
            $sql .= " AND (c.status = 'active' OR c.status IS NULL)";
        } elseif ($filter === 'passive') {
            $sql .= " AND c.status = 'passive'";
        }

        // Search
        if ($search) {
            $sql .= " AND (c.full_name LIKE ? OR c.company_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " GROUP BY c.id ORDER BY c.full_name ASC LIMIT 200";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();

        // Process sites data
        foreach ($customers as &$customer) {
            if ($customer['sites']) {
                $customer['sites'] = explode('||', $customer['sites']);
                $customer['site_ids'] = explode('||', $customer['site_ids']);
            } else {
                $customer['sites'] = [];
                $customer['site_ids'] = [];
            }
            $customer['status'] = $customer['status'] ?? 'active';
        }

        // Select2 formatı için dönüştür
        if (isset($_GET['q'])) {
            $results = [];
            foreach ($customers as $c) {
                $text = $c['full_name'];
                if ($c['company_name'])
                    $text .= ' (' . $c['company_name'] . ')';
                $results[] = ['id' => $c['id'], 'text' => $text];
            }
            json_response(['results' => $results]);
        } else {
            json_response(['status' => 'success', 'data' => $customers]);
        }
    }

    // DETAY
    if ($action === 'get') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if ($customer) {
            // Get customer's sites
            $stmt = $pdo->prepare("SELECT id, domain FROM sites WHERE customer_id = ? AND status = 'active'");
            $stmt->execute([$id]);
            $sites = $stmt->fetchAll();

            $customer['sites'] = array_column($sites, 'domain');
            $customer['site_ids'] = array_column($sites, 'id');
            $customer['site_count'] = count($sites);
            $customer['status'] = $customer['status'] ?? 'active';

            json_response(['status' => 'success', 'data' => $customer]);
        } else {
            json_response(['status' => 'error', 'message' => 'Müşteri bulunamadı'], 404);
        }
    }

    // EKLE
    if ($action === 'create') {
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $phone = format_phone($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $city = sanitize_input($_POST['city'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        $sites_raw = $_POST['sites'] ?? '';

        // Parse sites - can be array or comma-separated string
        if (is_array($sites_raw)) {
            $sites = array_filter($sites_raw); // Remove empty values
        } else {
            $sites = $sites_raw ? array_filter(explode(',', $sites_raw)) : [];
        }

        if (!$full_name || !$phone) {
            json_response(['status' => 'error', 'message' => 'Ad Soyad ve Telefon gerekli'], 400);
        }

        if ($email && !validate_email($email)) {
            json_response(['status' => 'error', 'message' => 'Geçersiz e-posta adresi'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO customers (full_name, company_name, phone, email, address, city, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $company_name, $phone, $email, $address, $city, $notes, $status]);

        $customer_id = $pdo->lastInsertId();

        // Update site relationships
        if (!empty($sites)) {
            $stmt = $pdo->prepare("UPDATE sites SET customer_id = ? WHERE id = ?");
            foreach ($sites as $site_id) {
                if ($site_id) { // Skip empty values
                    $stmt->execute([$customer_id, $site_id]);
                }
            }
        }

        log_activity($pdo, 'Müşteri Eklendi', "Ad: $full_name");

        json_response(['status' => 'success', 'message' => 'Müşteri eklendi', 'id' => $customer_id, 'full_name' => $full_name]);
    }

    // GÜNCELLE
    if ($action === 'update') {
        $id = $_POST['id'] ?? 0;
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $company_name = sanitize_input($_POST['company_name'] ?? '');
        $phone = format_phone($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $address = sanitize_input($_POST['address'] ?? '');
        $city = sanitize_input($_POST['city'] ?? '');
        $notes = sanitize_input($_POST['notes'] ?? '');
        $status = sanitize_input($_POST['status'] ?? 'active');
        $sites_raw = $_POST['sites'] ?? '';

        // Parse sites - can be array or comma-separated string
        if (is_array($sites_raw)) {
            $sites = array_filter($sites_raw); // Remove empty values
        } else {
            $sites = $sites_raw ? array_filter(explode(',', $sites_raw)) : [];
        }

        if (!$id || !$full_name || !$phone) {
            json_response(['status' => 'error', 'message' => 'Gerekli alanları doldurun'], 400);
        }

        if ($email && !validate_email($email)) {
            json_response(['status' => 'error', 'message' => 'Geçersiz e-posta adresi'], 400);
        }

        $stmt = $pdo->prepare("UPDATE customers SET full_name = ?, company_name = ?, phone = ?, email = ?, address = ?, city = ?, notes = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$full_name, $company_name, $phone, $email, $address, $city, $notes, $status, $id]);

        // Update site relationships
        // First, get current customer's sites
        $stmt = $pdo->prepare("SELECT id FROM sites WHERE customer_id = ?");
        $stmt->execute([$id]);
        $currentSites = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove sites that are no longer associated (set to customer ID 0 or leave unchanged)
        // We don't set to NULL because customer_id is NOT NULL
        foreach ($currentSites as $currentSiteId) {
            if (!in_array($currentSiteId, $sites)) {
                // Site is no longer associated with this customer
                // Option: either keep unchanged or assign to a default "unassigned" customer
                // For now, we'll keep it unchanged (do nothing)
            }
        }

        // Assign selected sites to this customer
        if (!empty($sites)) {
            $stmt = $pdo->prepare("UPDATE sites SET customer_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            foreach ($sites as $site_id) {
                if ($site_id) { // Skip empty values
                    $stmt->execute([$id, $site_id]);
                }
            }
        }

        log_activity($pdo, 'Müşteri Güncellendi', "ID: $id");

        json_response(['status' => 'success', 'message' => 'Müşteri güncellendi']);
    }

    // DURUM GÜNCELLE
    if ($action === 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $stmt = $pdo->prepare("UPDATE customers SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $id]);

        log_activity($pdo, 'Müşteri Durumu Güncellendi', "ID: $id, Durum: $status");

        json_response(['status' => 'success', 'message' => 'Durum güncellendi']);
    }

    // TOPLU DURUM GÜNCELLE
    if ($action === 'bulk_status') {
        $ids = $_POST['ids'] ?? [];
        $status = $_POST['status'] ?? 'active';

        if (empty($ids)) {
            json_response(['status' => 'error', 'message' => 'Müşteri seçilmedi'], 400);
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE customers SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
        $params = array_merge([$status], $ids);
        $stmt->execute($params);

        $count = $stmt->rowCount();
        log_activity($pdo, 'Toplu Durum Güncelleme', "$count müşteri $status yapıldı");

        json_response(['status' => 'success', 'message' => "$count müşteri durumu güncellendi"]);
    }

    // TOPLU SİL
    if ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];

        if (empty($ids)) {
            json_response(['status' => 'error', 'message' => 'Müşteri seçilmedi'], 400);
        }

        // Check for sites
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sites WHERE customer_id IN ($placeholders)");
        $stmt->execute($ids);
        $site_count = $stmt->fetchColumn();

        if ($site_count > 0) {
            json_response(['status' => 'error', 'message' => "Seçili müşterilere ait $site_count site var. Önce siteleri silin."], 400);
        }

        $stmt = $pdo->prepare("DELETE FROM customers WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        $count = $stmt->rowCount();
        log_activity($pdo, 'Toplu Müşteri Silme', "$count müşteri silindi");

        json_response(['status' => 'success', 'message' => "$count müşteri silindi"]);
    }

    // SİL
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;

        // Önce müşteriye ait site var mı kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sites WHERE customer_id = ?");
        $stmt->execute([$id]);
        $site_count = $stmt->fetchColumn();

        if ($site_count > 0) {
            json_response(['status' => 'error', 'message' => "Bu müşteriye ait $site_count site var. Önce siteleri silin."], 400);
        }

        $stmt = $pdo->prepare("SELECT full_name FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if ($customer) {
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);

            log_activity($pdo, 'Müşteri Silindi', "Ad: {$customer['full_name']}");

            json_response(['status' => 'success', 'message' => 'Müşteri silindi']);
        } else {
            json_response(['status' => 'error', 'message' => 'Müşteri bulunamadı'], 404);
        }
    }

    // WHATSAPP LOG
    if ($action === 'log_whatsapp') {
        $customer_id = $_POST['customer_id'] ?? 0;
        $message_type = $_POST['message_type'] ?? 'custom';
        $message_text = $_POST['message_text'] ?? '';

        $stmt = $pdo->prepare("SELECT phone FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch();

        if ($customer) {
            // Log to database (if needed in future)
            log_activity($pdo, 'WhatsApp Gönderildi', "Müşteri ID: $customer_id, Tip: $message_type");
            json_response(['status' => 'success', 'message' => 'Log kaydedildi']);
        } else {
            json_response(['status' => 'error', 'message' => 'Müşteri bulunamadı'], 404);
        }
    }

} catch (Exception $e) {
    log_error('Customers API Error', ['error' => $e->getMessage()]);
    json_response(['status' => 'error', 'message' => 'Bir hata oluştu: ' . $e->getMessage()], 500);
}
