<?php
// Set timezone to TR
date_default_timezone_set('Europe/Istanbul');

// includes/db.php - Veritabanı bağlantısı ve tablo oluşturma

try {
    $db_file = __DIR__ . '/../database.sqlite';
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Tabloları oluştur
    $commands = [
        // Kullanıcılar tablosu (Admin, Sekreter)
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('admin', 'secretary')),
            name_surname TEXT NOT NULL,
            phone TEXT,
            email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Müşteriler tablosu (Site sahipleri)
        "CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            company_name TEXT,
            phone TEXT NOT NULL,
            email TEXT,
            address TEXT,
            city TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Siteler tablosu
        "CREATE TABLE IF NOT EXISTS sites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            domain TEXT NOT NULL,
            renewal_date DATE NOT NULL,
            package_type TEXT NOT NULL,
            price DECIMAL(10,2),
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'expired', 'cancelled', 'transferred', 'requested', 'accepted')),
            notes TEXT,
            last_renewed_at DATETIME,
            whatsapp_sent INTEGER DEFAULT 0,
            whatsapp_sent_at DATETIME,
            start_date DATE,
            api_expires_at DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )",

        // WhatsApp Rehber (Contacts)
        "CREATE TABLE IF NOT EXISTS whatsapp_contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            jid TEXT UNIQUE NOT NULL,
            name TEXT,
            group_name TEXT,
            number TEXT,
            type TEXT DEFAULT 'individual' CHECK(type IN ('individual', 'group')),
            unread_count INTEGER DEFAULT 0,
            last_message_time DATETIME,
            is_imported INTEGER DEFAULT 0,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // WhatsApp Mesajlar (Messages)
        "CREATE TABLE IF NOT EXISTS whatsapp_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            remote_jid TEXT NOT NULL,
            message_id TEXT UNIQUE NOT NULL,
            from_me INTEGER DEFAULT 0,
            push_name TEXT,
            content TEXT,
            message_type TEXT DEFAULT 'text',
            timestamp INTEGER,
            raw_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(remote_jid) REFERENCES whatsapp_contacts(jid) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS whatsapp_templates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            type TEXT DEFAULT 'whatsapp' CHECK(type IN ('whatsapp', 'email')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // WhatsApp mesaj geçmişi
        "CREATE TABLE IF NOT EXISTS whatsapp_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            site_id INTEGER NOT NULL,
            customer_id INTEGER NOT NULL,
            message_type TEXT NOT NULL,
            phone_number TEXT NOT NULL,
            message_text TEXT,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(site_id) REFERENCES sites(id) ON DELETE CASCADE,
            FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )",

        // Security: IP Whitelist
        "CREATE TABLE IF NOT EXISTS ip_whitelist (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT UNIQUE NOT NULL,
            description TEXT,
            created_by INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Security: API Access Logs
        "CREATE TABLE IF NOT EXISTS api_access_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            endpoint TEXT,
            method TEXT,
            status TEXT CHECK(status IN ('allowed', 'denied')),
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )",

        // Sistem ayarları
        "CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )",

        // Aktivite logları
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            details TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    ];

    // Performans için WAL modu (Write-Ahead Logging) - Eşzamanlı okuma/yazma hızını artırır
    $pdo->exec("PRAGMA journal_mode = WAL;");
    $pdo->exec("PRAGMA synchronous = NORMAL;");
    // Veritabanı kilitleme hatasını önlemek için bekleme süresi (ms)
    $pdo->exec("PRAGMA busy_timeout = 30000;");

    foreach ($commands as $command) {
        $pdo->exec($command);
    }

    // İNDEKSLER (Performans Optimizasyonu)
    // WhatsApp Queue Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_queue (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        site_id INTEGER,
        phone TEXT,
        message TEXT,
        scheduled_at DATETIME,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Reminders Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS reminders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        site_id INTEGER,
        title TEXT NOT NULL,
        description TEXT,
        reminder_date DATE NOT NULL,
        reminder_time TEXT DEFAULT '09:00',
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'cancelled')),
        snoozed_until DATE,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME,
        is_notified INTEGER DEFAULT 0,
        alarm_enabled INTEGER DEFAULT 0,
        FOREIGN KEY(site_id) REFERENCES sites(id) ON DELETE CASCADE,
        FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Cron Jobs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cron_jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_type TEXT NOT NULL,
        job_name TEXT NOT NULL,
        job_data TEXT,
        scheduled_time TEXT NOT NULL,
        scheduled_date DATE NOT NULL,
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'failed', 'cancelled')),
        last_run_at DATETIME,
        next_run_at DATETIME,
        is_recurring INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create Indexes
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_sites_customer_id ON sites(customer_id)",
        "CREATE INDEX IF NOT EXISTS idx_sites_status ON sites(status)",
        "CREATE INDEX IF NOT EXISTS idx_sites_renewal_date ON sites(renewal_date)",
        "CREATE INDEX IF NOT EXISTS idx_sites_status_renewal ON sites(status, renewal_date)",
        "CREATE INDEX IF NOT EXISTS idx_customers_search ON customers(full_name, phone, company_name)",
        "CREATE INDEX IF NOT EXISTS idx_reminders_status_date ON reminders(status, reminder_date)",
        "CREATE INDEX IF NOT EXISTS idx_cron_jobs_status ON cron_jobs(status, scheduled_date, scheduled_time)",
        // WhatsApp Indexes
        "CREATE INDEX IF NOT EXISTS idx_whatsapp_contacts_jid ON whatsapp_contacts(jid)",
        "CREATE INDEX IF NOT EXISTS idx_whatsapp_contacts_type ON whatsapp_contacts(type)",
        "CREATE INDEX IF NOT EXISTS idx_whatsapp_messages_jid ON whatsapp_messages(remote_jid)",
        "CREATE INDEX IF NOT EXISTS idx_whatsapp_messages_timestamp ON whatsapp_messages(timestamp)",
        "CREATE INDEX IF NOT EXISTS idx_whatsapp_messages_message_id ON whatsapp_messages(message_id)",
        "CREATE INDEX IF NOT EXISTS idx_api_logs_created_at ON api_access_logs(created_at)"
    ];

    foreach ($indexes as $index) {
        $pdo->exec($index);
    }

    // Varsayılan Admin kullanıcısı install.php üzerinden oluşturulur.
    // Otomatik oluşturma kaldırıldı.

    // Varsayılan paket fiyatları
    $defaultSettings = [
        'package_pro_price' => '5000',
        'package_basic_price' => '2500',
        'company_name' => 'Site Takip A.Ş.',
        'company_phone' => '',
        'company_email' => '',
        'whatsapp_reminder_days' => '30,15,7,1'
    ];

    foreach ($defaultSettings as $key => $value) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE key = ?");
        $check->execute([$key]);
        if ($check->fetchColumn() == 0) {
            $insert = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
            $insert->execute([$key, $value]);
        }
    }

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
