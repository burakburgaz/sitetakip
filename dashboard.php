<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();

$page_title = 'Dashboard - DReklam';

// İstatistikleri çek
$stats = [];

// Automatic Hostinger Sync (Poor Man's Cron)
// DISABLED FOR PERFORMANCE: This was blocking page load.
// Use manual sync button or a real cron job instead.
/*
$lastSync = $pdo->query("SELECT value FROM settings WHERE key = 'hostinger_last_sync'")->fetchColumn();
if (!$lastSync || (time() - strtotime($lastSync) > 3600)) {
    // Run sync in background (fire and forget check)
    // Since windows support is limited for non-blocking exec, we will just do a blocking call if it's due.
    // Ideally use AJAX on page load but user asked for "Automatic".
    // We'll verify if we have a key first.
    $apiKeySync = $pdo->query("SELECT value FROM settings WHERE key = 'hostinger_api_key'")->fetchColumn();
    if ($apiKeySync) {
        // Use Javascript Trigger later or include simple logic here.
        // Javascript is better to avoid page load delay.
        $trigger_sync = true;
    }
}
*/

// Toplam site sayısı
$stmt = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'active'");
$stats['total_sites'] = $stmt->fetchColumn();

// Toplam müşteri sayısı
$stmt = $pdo->query("SELECT COUNT(*) FROM customers");
$stats['total_customers'] = $stmt->fetchColumn();

// Bu ay yenilenecek siteler
$stmt = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'active' AND DATE(renewal_date) BETWEEN DATE('now') AND DATE('now', '+30 days')");
$stats['renewals_this_month'] = $stmt->fetchColumn();

// Süresi dolmuş siteler
$stmt = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'active' AND DATE(renewal_date) < DATE('now')");
$stats['expired_sites'] = $stmt->fetchColumn();



// Son eklenen siteler
$recent_sites = $pdo->query("
    SELECT s.*, c.full_name as customer_name 
    FROM sites s 
    JOIN customers c ON s.customer_id = c.id 
    ORDER BY s.created_at DESC 
    LIMIT 5
")->fetchAll();

// Yaklaşan yenilemeler (30 gün içinde)
$upcoming_renewals = $pdo->query("
    SELECT s.*, c.full_name as customer_name, c.phone as customer_phone 
    FROM sites s 
    JOIN customers c ON s.customer_id = c.id 
    WHERE s.status IN ('active', 'requested', 'accepted') 
    AND DATE(s.renewal_date) BETWEEN DATE('now') AND DATE('now', '+30 days')
    ORDER BY s.renewal_date ASC 
    LIMIT 10
")->fetchAll();

// Planlanmış Mesajlar (Bekleyenler)
$scheduled_msgs = $pdo->query("
    SELECT q.id, q.message, q.scheduled_at, q.phone, s.domain, q.status
    FROM whatsapp_queue q
    LEFT JOIN sites s ON q.site_id = s.id
    WHERE q.status = 'pending'
    ORDER BY q.scheduled_at ASC
    LIMIT 5
")->fetchAll();

// Gönderilen/İşlenen Mesajlar
$queue_history = $pdo->query("
    SELECT q.id, q.message, q.scheduled_at, q.phone, s.domain, q.status
    FROM whatsapp_queue q
    LEFT JOIN sites s ON q.site_id = s.id
    WHERE q.status != 'pending'
    ORDER BY q.scheduled_at DESC
    LIMIT 5
")->fetchAll();
?>
<?php include 'includes/head.php'; ?>

<style>
    /* Dashboard-specific overrides */
    #sidebar {
        background: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border-right: 1px solid var(--glass-border) !important;
    }

    #sidebar .bg-white.text-indigo-600 {
        background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%) !important;
        color: white !important;
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4) !important;
    }

    #sidebar .hover\:bg-indigo-500:hover {
        background: var(--glass-hover) !important;
    }

    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        background: var(--glass-hover);
        border-color: rgba(59, 130, 246, 0.3);
    }

    .renewal-item,
    .reminder-item {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        transition: all 0.2s ease;
    }

    .renewal-item:hover,
    .reminder-item:hover {
        background: var(--glass-hover) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        transform: translateX(4px);
    }

    .renewal-item.bg-red-50 {
        border-left: 4px solid #ef4444 !important;
    }

    .renewal-item.bg-yellow-50 {
        border-left: 4px solid #f59e0b !important;
    }

    .renewal-item.bg-green-50 {
        border-left: 4px solid #10b981 !important;
    }

    .reminder-item.bg-red-50 {
        border-left: 4px solid #ef4444 !important;
    }

    .reminder-item.bg-yellow-50 {
        border-left: 4px solid #f59e0b !important;
    }

    .reminder-item.bg-blue-50 {
        border-left: 4px solid #3b82f6 !important;
    }
</style>

<body class="bg-gray-900 flex h-screen overflow-hidden">
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Premium Context Menu -->
    <div id="premiumContextMenu"
        class="hidden fixed glass-card rounded-2xl shadow-2xl border border-white/10 z-[100] w-64 overflow-hidden py-1 backdrop-blur-2xl transition-all duration-200">
        <div class="px-4 py-3 border-b border-white/5 mb-1 bg-white/[0.02]">
            <p id="ctxTitle" class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-0.5">İşlemler
            </p>
            <p id="ctxSubtitle" class="text-[11px] font-bold text-slate-200 truncate"></p>
        </div>
        <div id="ctxItems" class="space-y-0.5" style="max-height: 400px; overflow-y: auto;">
            <!-- Dynamic Actions -->
        </div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="z-10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold logo-font text-slate-800 flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center border border-blue-200">
                            <i class="fa-solid fa-chart-pie text-blue-600 text-xl"></i>
                        </div>
                        Kontrol Paneli
                    </h2>
                    <p class="text-slate-500 text-xs mt-1 tracking-wider uppercase">Sistem Özet Verileri ve Yaklaşan
                        Hatırlatmalar</p>
                </div>
                <div class="text-right">
                    <div
                        class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200 px-4 py-2 rounded-2xl text-slate-600">
                        <i class="fa-solid fa-calendar-day text-blue-600"></i>
                        <span class="text-sm font-medium"><?= date('d.m.Y') ?></span>
                        <span
                            class="text-xs text-slate-500 ml-2 border-l border-slate-200 pl-2"><?= date('H:i') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-8 pt-2">



            <!-- İki Sütunlu Widget Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

                <!-- SOL KOLON (Yenilemeler) -->
                <div class="space-y-8">
                    <!-- Yaklaşan Yenilemeler -->
                    <div class="glass-card rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-8 opacity-10">
                            <i class="fa-solid fa-clock-rotate-left text-8xl text-blue-600"></i>
                        </div>
                        <div class="flex items-center justify-between mb-8 border-b border-slate-200 pb-6 relative z-10">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-3">
                                    <i class="fa-solid fa-bell text-yellow-600 text-lg"></i>
                                    Yaklaşan Yenilemeler
                                </h3>
                                <p class="text-slate-500 text-xs mt-1">Ödeme günü yaklaşan web siteleri</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    class="bg-yellow-100 text-yellow-600 text-[10px] font-bold px-3 py-1 rounded-full border border-yellow-200"><?= count($upcoming_renewals) ?>
                                    KAYIT</span>
                                <a href="sites.php?filter=upcoming"
                                    class="text-xs font-bold text-blue-600 hover:text-blue-500 transition-colors uppercase tracking-wider">Tümü</a>
                            </div>
                        </div>
                        <div class="space-y-3 relative z-10" id="renewalsWidget">
                            <?php if (empty($upcoming_renewals)): ?>
                                <div class="text-center py-10">
                                    <div
                                        class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-200">
                                        <i class="fa-solid fa-check text-slate-500 text-2xl"></i>
                                    </div>
                                    <p class="text-slate-500 text-sm italic">Yaklaşan yenileme bulunmuyor</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($upcoming_renewals as $site): ?>
                                    <?php
                                    $days = days_until_renewal($site['renewal_date']);
                                    $status = get_renewal_status($days);
                                    $urgency_class = $days <= 7 ? 'bg-red-50' : ($days <= 15 ? 'bg-yellow-50' : 'bg-green-50');

                                    $status_badge = '';
                                    if ($site['whatsapp_sent'] == 1) {
                                        $wa_time = $site['whatsapp_sent_at'] ? date('d.m H:i', strtotime($site['whatsapp_sent_at'])) : '';
                                        $status_badge = '<span class="text-[10px] bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full flex items-center gap-1 border border-emerald-200" title="Mesaj Gönderildi"><i class="fa-brands fa-whatsapp"></i> ' . $wa_time . '</span>';
                                    } elseif ($site['status'] == 'requested')
                                        $status_badge = '<span class="text-[10px] bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full border border-blue-200"><i class="fa-solid fa-paper-plane mr-1 text-[8px]"></i>İstendi</span>';
                                    elseif ($site['status'] == 'accepted')
                                        $status_badge = '<span class="text-[10px] bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-full border border-emerald-200"><i class="fa-solid fa-check mr-1 text-[8px]"></i>Kabul</span>';

                                    $api_date_display = '';
                                    $accept_btn = '';
                                    if (!empty($site['api_expires_at'])) {
                                        if (strtotime($site['api_expires_at']) > strtotime($site['renewal_date'])) {
                                            $api_date_display = '<div class="text-[10px] text-blue-600/80 font-medium mt-1 inline-flex items-center gap-1.5"><i class="fa-solid fa-shield-heart text-blue-500"></i> API: ' . format_date($site['api_expires_at']) . '</div>';
                                            $accept_btn = '<button onclick="event.stopPropagation(); acceptRenewal(' . $site['id'] . ')" class="bg-blue-600 hover:bg-blue-700 text-white text-[10px] font-bold px-3 py-1 rounded-lg transition shadow-lg flex items-center gap-1.5 ml-auto" title="Yenilemeyi Onayla"><i class="fa-solid fa-check-circle"></i>Onayla</button>';
                                        } else {
                                            $api_date_display = '<div class="text-[10px] text-slate-600 mt-1">API: ' . format_date($site['api_expires_at']) . '</div>';
                                        }
                                    }
                                    ?>
                                    <div class="renewal-item <?= $urgency_class ?> p-4 rounded-2xl cursor-pointer group"
                                        data-id="<?= $site['id'] ?>" data-domain="<?= htmlspecialchars($site['domain']) ?>"
                                        data-customer="<?= htmlspecialchars($site['customer_name']) ?>"
                                        data-phone="<?= htmlspecialchars($site['customer_phone']) ?>"
                                        data-status="<?= $site['status'] ?>"
                                        onclick="showRenewalMenu(event, <?= $site['id'] ?>); return false;"
                                        oncontextmenu="showRenewalMenu(event, <?= $site['id'] ?>); return false;">

                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-3 mb-1.5 flex-wrap">
                                                    <p
                                                        class="font-bold text-slate-800 text-base group-hover:text-blue-600 transition-colors">
                                                        <?= htmlspecialchars($site['domain']) ?>
                                                    </p>
                                                    <?= $status_badge ?>
                                                </div>
                                                <div class="flex items-center gap-4 text-xs">
                                                    <span class="text-slate-600 flex items-center gap-1.5"><i
                                                            class="fa-solid fa-user text-slate-500"></i><?= htmlspecialchars($site['customer_name']) ?></span>
                                                    <span
                                                        class="font-bold text-emerald-600 text-xs bg-emerald-100 px-2 py-0.5 rounded-lg border border-emerald-200"><?= number_format($site['price'], 0, ',', '.') ?>
                                                        ₺</span>
                                                </div>
                                                <?= $api_date_display ?>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <div class="flex flex-col items-end gap-1">
                                                    <?php if ($accept_btn):
                                                        echo $accept_btn;
                                                    endif; ?>
                                                    <p
                                                        class="text-sm font-black <?= $days <= 7 ? 'text-red-600' : 'text-slate-700' ?> uppercase tracking-tighter">
                                                        <?= $days ?> GÜN KALDI
                                                    </p>
                                                    <p class="text-[10px] font-bold text-slate-500 tracking-widest uppercase">
                                                        <?= format_date($site['renewal_date']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- SAĞ KOLON (Hatırlatmalar + Mesajlar) -->
                <div class="space-y-8">
                    <!-- Hatırlatmalar -->
                    <div class="glass-card rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                        <div class="flex items-center justify-between mb-8 border-b border-slate-200 pb-6">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-3">
                                    <i class="fa-solid fa-thumbtack text-blue-600 text-lg"></i>
                                    Aktif Hatırlatmalar
                                </h3>
                                <p class="text-slate-500 text-xs mt-1">Sizin için önemli notlar ve uyarılar</p>
                            </div>
                            <a href="calendar.php#reminders"
                                class="text-xs font-bold text-blue-600 hover:text-blue-500 uppercase tracking-wider">Tümü</a>
                        </div>
                        <div class="space-y-3" id="remindersWidget">
                            <?php
                            $reminders = $pdo->query("
                                SELECT r.*, s.domain, c.full_name as customer_name
                                FROM reminders r
                                LEFT JOIN sites s ON r.site_id = s.id
                                LEFT JOIN customers c ON s.customer_id = c.id
                                WHERE r.status = 'pending' 
                                AND DATE(r.reminder_date) <= DATE('now', '+30 days')
                                ORDER BY r.reminder_date ASC
                                LIMIT 6
                            ")->fetchAll();

                            if (empty($reminders)): ?>
                                <div class="text-center py-10">
                                    <div
                                        class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-200">
                                        <i class="fa-solid fa-calendar-check text-slate-400 text-2xl"></i>
                                    </div>
                                    <p class="text-slate-500 text-sm italic">Bekleyen hatırlatmanız yok</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($reminders as $reminder): ?>
                                    <?php
                                    $days_left = days_until_renewal($reminder['reminder_date']);
                                    $urgency_class = $days_left <= 3 ? 'bg-red-50' : ($days_left <= 7 ? 'bg-yellow-50' : 'bg-blue-50');
                                    ?>
                                    <div class="reminder-item <?= $urgency_class ?> p-4 rounded-2xl cursor-pointer"
                                        data-id="<?= $reminder['id'] ?>" onclick="showReminderDetail(<?= $reminder['id'] ?>)"
                                        oncontextmenu="showReminderMenu(event, <?= $reminder['id'] ?>); return false;">
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-slate-700 text-sm mb-1 truncate">
                                                    <?= htmlspecialchars($reminder['title']) ?>
                                                </p>
                                                <?php if ($reminder['domain']): ?>
                                                    <p
                                                        class="text-[10px] font-bold text-blue-600/80 uppercase tracking-widest flex items-center gap-1.5">
                                                        <i class="fa-solid fa-link text-[8px]"></i>
                                                        <?= htmlspecialchars($reminder['domain']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($reminder['description']): ?>
                                                    <p class="text-[11px] text-slate-500 mt-1 lines-1 italic">
                                                        "<?= htmlspecialchars($reminder['description']) ?>"
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-right shrink-0">
                                                <p
                                                    class="text-xs font-black <?= $days_left <= 3 ? 'text-red-600' : ($days_left <= 7 ? 'text-yellow-600' : 'text-blue-600') ?> uppercase">
                                                    <?= $days_left ?> GÜN
                                                </p>
                                                <p class="text-[9px] font-bold text-slate-500 uppercase tracking-tighter">
                                                    <?= format_date($reminder['reminder_date']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Son Gönderilen Mesajlar -->
                    <div class="glass-card rounded-[2.5rem] p-8 shadow-2xl relative overflow-hidden">
                        <div class="flex items-center justify-between mb-8 border-b border-slate-200 pb-6">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800 flex items-center gap-3">
                                    <i class="fa-solid fa-paper-plane text-emerald-600 text-lg"></i>
                                    Mesaj Kuyruğu
                                </h3>
                                <p class="text-slate-500 text-xs mt-1">Gönderime hazır ve son tamamlananlar</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <!-- Planlananlar -->
                            <?php if (!empty($scheduled_msgs)): ?>
                                <div
                                    class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1 flex items-center gap-2">
                                    <div class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></div> Bekleyen
                                    Planlar
                                </div>
                                <?php foreach ($scheduled_msgs as $msg): ?>
                                    <div
                                        class="p-4 bg-slate-50 rounded-2xl border border-slate-200 group hover:bg-slate-100 transition-all">
                                        <div class="flex items-start gap-4">
                                            <div
                                                class="w-8 h-8 rounded-xl bg-yellow-100 flex items-center justify-center border border-yellow-200 shrink-0">
                                                <i class="fa-regular fa-clock text-yellow-600 text-sm"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs text-slate-700 font-medium leading-relaxed italic">
                                                    "<?= htmlspecialchars($msg['message']) ?>"
                                                </p>
                                                <div class="flex items-center gap-3 mt-3">
                                                    <span
                                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest"><?= format_date($msg['scheduled_at'], 'H:i d.m.y') ?></span>
                                                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                    <span
                                                        class="text-[10px] font-bold text-blue-600 uppercase tracking-widest"><i
                                                            class="fa-solid fa-phone mr-1"></i><?= htmlspecialchars($msg['phone']) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- Gönderilenler / Geçmiş -->
                            <?php if (!empty($queue_history)): ?>
                                <?php if (!empty($scheduled_msgs))
                                    echo '<div class="h-px bg-slate-200 my-6"></div>'; ?>
                                <div class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">Son
                                    İşlemler</div>
                                <?php foreach ($queue_history as $msg): ?>
                                    <?php
                                    $statusClass = 'bg-slate-50 border-slate-200';
                                    $icon = 'fa-check text-slate-500';
                                    $iconColor = 'bg-slate-100 border-slate-200';
                                    $statusText = $msg['status'];

                                    if ($msg['status'] == 'sent') {
                                        $icon = 'fa-check-double text-emerald-600';
                                        $iconColor = 'bg-emerald-100 border-emerald-200';
                                        $statusText = 'Başarılı';
                                    } elseif ($msg['status'] == 'failed') {
                                        $icon = 'fa-times text-red-600';
                                        $iconColor = 'bg-red-100 border-red-200';
                                        $statusText = 'Hata';
                                    } elseif ($msg['status'] == 'cancelled') {
                                        $icon = 'fa-ban text-orange-600';
                                        $iconColor = 'bg-orange-100 border-orange-200';
                                        $statusText = 'İptal';
                                    }
                                    ?>
                                    <div
                                        class="flex items-start gap-4 p-4 rounded-2xl border border-slate-200 hover:bg-slate-50 transition">
                                        <div
                                            class="w-8 h-8 rounded-xl <?= $iconColor ?> border flex items-center justify-center shrink-0">
                                            <i class="fa-solid <?= $icon ?> text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs text-slate-700 font-medium leading-relaxed truncate">
                                                <?= htmlspecialchars($msg['message']) ?>
                                            </p>
                                            <div class="flex items-center gap-3 mt-2 flex-wrap">
                                                <span
                                                    class="text-[10px] font-bold text-slate-600"><?= format_date($msg['scheduled_at'], 'H:i d.m') ?></span>
                                                <span class="text-[10px] text-slate-500 border-l border-slate-200 pl-2 ml-2"><i
                                                        class="fa-solid fa-phone mr-1"></i><?= htmlspecialchars($msg['phone']) ?></span>
                                                <span
                                                    class="text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-md <?= $msg['status'] == 'sent' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600' ?> ml-auto"><?= $statusText ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (empty($queue_history) && empty($scheduled_msgs)): ?>
                                <div class="text-center py-6">
                                    <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest">Henüz mesaj
                                        kaydı bulunmuyor</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Quick Stats Cards (New Compact Design) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8 pb-4">
                <div class="glass-card stat-card p-4 rounded-xl flex items-center gap-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200 shrink-0">
                        <i class="fa-solid fa-globe text-blue-600 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Toplam Site</h4>
                        <p class="text-xl font-bold text-slate-800 mt-0.5"><?= $stats['total_sites'] ?></p>
                    </div>
                </div>

                <div class="glass-card stat-card p-4 rounded-xl flex items-center gap-4">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center border border-purple-200 shrink-0">
                        <i class="fa-solid fa-users text-purple-600 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Müşteriler</h4>
                        <p class="text-xl font-bold text-slate-800 mt-0.5"><?= $stats['total_customers'] ?></p>
                    </div>
                </div>

                <div class="glass-card stat-card p-4 rounded-xl flex items-center gap-4">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center border border-yellow-200 shrink-0">
                        <i class="fa-solid fa-bolt text-yellow-600 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Bu Ay</h4>
                        <p class="text-xl font-bold text-slate-800 mt-0.5"><?= $stats['renewals_this_month'] ?></p>
                    </div>
                </div>

                <div class="glass-card stat-card p-4 rounded-xl flex items-center gap-4">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center border border-red-200 shrink-0">
                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[10px] font-bold uppercase tracking-widest">Süresi Dolan</h4>
                        <p class="text-xl font-bold text-slate-800 mt-0.5"><?= $stats['expired_sites'] ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/api-helper.js"></script>
    <script>
        // Chat polling interval
        let chatPollInterval = null;

        // Start: Helper Code for WhatsApp Dashboard
        function formatDate(dateString) {
            if (!dateString) return '';
            const d = new Date(dateString);
            return d.toLocaleDateString('tr-TR');
        }

        // Send WhatsApp Dashboard
        function sendWhatsAppDashboard(siteId) {
            // Permission Check
            if (window.currentUser && !window.currentUser.permissions.whatsapp) {
                Swal.fire({
                    icon: 'error',
                    title: 'Yetki Yok',
                    text: 'WhatsApp gönderme yetkiniz bulunmamaktadır!'
                });
                return;
            }

            $.when(
                $.get('api/sites.php', { action: 'get', id: siteId }),
                $.get('api/templates.php', { action: 'list' }),
                $.get('api/settings.php')
            ).done(function (siteRes, templatesRes, settingsRes) {
                const site = siteRes[0].data;
                const templates = templatesRes[0].data || [];
                const settings = settingsRes[0];

                const hasEvolution = settings.evolution_api_url && settings.evolution_api_key && settings.evolution_instance_name;

                $.get('api/customers.php', { action: 'get', id: site.customer_id }, function (custRes) {
                    const customer = custRes.data;
                    let optionsHtml = '<option class="bg-slate-800 text-white" value="">Şablon Seçiniz...</option>';
                    templates.filter(t => t.type === 'whatsapp').forEach(t => {
                        optionsHtml += `<option class="bg-slate-800 text-white" value="${t.id}" data-message="${encodeURIComponent(t.message)}">${t.title}</option>`;
                    });

                    const sendButtonText = hasEvolution ? '<i class="fa-solid fa-paper-plane mr-2"></i>API ile Gönder' : '<i class="fa-brands fa-whatsapp mr-2"></i>WhatsApp Web';

                    Swal.fire({
                        title: '<i class="fa-brands fa-whatsapp text-green-600 mr-2"></i>WhatsApp Gönder',
                        html: `
                            <div class="text-left space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Müşteri</label>
                                        <input type="text" id="waCustomerName" value="${customer.full_name}" class="w-full border rounded px-3 py-2 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Telefon</label>
                                        <input type="text" id="waCustomerPhone" value="${customer.phone}" class="w-full border rounded px-3 py-2 text-sm">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Mesaj Şablonu</label>
                                    <select id="waTemplate" class="w-full border rounded px-3 py-2 bg-slate-800 text-white border-white/10">${optionsHtml}</select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Mesaj</label>
                                    <textarea id="waMessage" rows="6" class="w-full border rounded px-3 py-2"></textarea>
                                </div>

                                ${hasEvolution ? `
                                <div class="border-t pt-4 mt-2">
                                     <label class="flex items-center space-x-2 cursor-pointer mb-2">
                                         <input type="checkbox" id="waScheduleToggle" class="form-checkbox h-4 w-4 text-green-600">
                                         <span class="text-sm font-bold text-white">Zamanlı Gönderim (İleri Tarihli)</span>
                                      </label>
                                      <div id="waScheduleContainer" class="hidden pl-6">
                                         <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gönderim Tarihi ve Saati</label>
                                         <input type="datetime-local" id="waScheduleDate" class="w-full border rounded px-3 py-2 text-sm">
                                         <p class="text-xs text-gray-500 mt-1">Seçilen tarih ve saatte otomatik gönderilecektir.</p>
                                      </div>
                                </div>
                                ` : ''}

                                ${!hasEvolution ? '<p class="text-xs text-orange-600 bg-orange-50 p-2 rounded mt-2">Not: Evolution API ayarlı değil. WhatsApp Web açılacak.</p>' : ''}
                            </div>
                        `,
                        width: '600px',
                        showCancelButton: true,
                        confirmButtonText: sendButtonText,
                        confirmButtonColor: '#25D366',
                        didOpen: () => {
                            $('#waTemplate').on('change', function () {
                                const template = $(this).val();
                                const name = $('#waCustomerName').val();
                                const siteParams = {
                                    domain: site.domain || '',
                                    renewal_date: site.renewal_date || '',
                                    package_type: site.package_type || ''
                                };

                                let message = '';
                                const selectedOption = $(this).find(':selected');
                                const encodedMsg = selectedOption.data('message');

                                if (encodedMsg) {
                                    message = decodeURIComponent(encodedMsg);
                                    message = message.replace(/\[ADI SOYADI\]/g, name || '');
                                    message = message.replace(/\[SITE\]/g, siteParams.domain);
                                    message = message.replace(/\[TARIH\]/g, formatDate(siteParams.renewal_date));
                                    message = message.replace(/\[PAKET\]/g, siteParams.package_type);
                                }
                                $('#waMessage').val(message);
                            });

                            $('#waScheduleToggle').on('change', function () {
                                if ($(this).is(':checked')) {
                                    $('#waScheduleContainer').removeClass('hidden');
                                } else {
                                    $('#waScheduleContainer').addClass('hidden');
                                }
                            });
                        },
                        preConfirm: () => {
                            const phone = $('#waCustomerPhone').val();
                            const message = $('#waMessage').val();
                            const isScheduled = $('#waScheduleToggle').is(':checked');
                            const scheduleDate = $('#waScheduleDate').val();

                            if (!phone || !message) {
                                Swal.showValidationMessage('Lütfen tüm alanları doldurun');
                                return false;
                            }
                            if (isScheduled && !scheduleDate) {
                                Swal.showValidationMessage('Lütfen gönderim tarihi seçiniz');
                                return false;
                            }
                            return { phone, message, isScheduled, scheduleDate };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const phone = result.value.phone.replace(/\D/g, '');
                            const cleanPhone = phone.startsWith('0') ? phone.substring(1) : phone;
                            const finalPhone = cleanPhone.startsWith('90') ? cleanPhone : '90' + cleanPhone;

                            if (result.value.isScheduled) {
                                $.post('api/sites.php', {
                                    action: 'schedule_whatsapp',
                                    site_id: siteId,
                                    phone: finalPhone,
                                    message: result.value.message,
                                    scheduled_at: result.value.scheduleDate
                                }, function (res) {
                                    if (res.status === 'success') {
                                        Swal.fire('Planlandı', 'Mesaj gönderim kuyruğuna eklendi.', 'success');
                                    } else {
                                        Swal.fire('Hata', res.message, 'error');
                                    }
                                }).fail(function (xhr) {
                                    let msg = 'İşlem başarısız.';
                                    if (xhr.status === 403) msg = 'WhatsApp planlama yetkiniz yok!';
                                    else if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                                    Swal.fire('Hata', msg, 'error');
                                });
                                return;
                            }

                            if (hasEvolution) {
                                Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading() });
                                $.post('api/sites.php', {
                                    action: 'send_whatsapp_api',
                                    site_id: siteId,
                                    phone: finalPhone,
                                    message: result.value.message
                                }, function (res) {
                                    if (res.status === 'success') {
                                        Swal.fire('Başarılı', 'Mesaj gönderildi.', 'success').then(() => location.reload());
                                    } else {
                                        Swal.fire('Hata', res.message, 'error');
                                    }
                                }).fail(function (xhr) {
                                    let msg = 'Sunucu hatası oluştu.';
                                    if (xhr.status === 403) {
                                        msg = 'WhatsApp gönderme yetkiniz bulunmamaktadır!';
                                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                        msg = xhr.responseJSON.message;
                                    } else if (xhr.responseText) {
                                        msg = 'Hata: ' + xhr.status;
                                    }
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Yetki Yok',
                                        text: msg
                                    });
                                });
                            } else {
                                const url = `https://api.whatsapp.com/send?phone=${finalPhone}&text=${encodeURIComponent(result.value.message)}`;
                                window.open(url, '_blank');
                                $.post('api/sites.php', { action: 'log_whatsapp', site_id: siteId }, function () { location.reload(); });
                            }
                        }
                    });
                });
            });
        }

        $(document).ready(function () {
            // Sidebar Toggle
            $('#toggleSidebar').click(function () {
                const sb = $('#sidebar');
                const isExpanded = sb.hasClass('w-64');
                const texts = $('.sidebar-text');
                const icon = $(this).find('i');

                if (isExpanded) {
                    // Collapse sidebar
                    sb.removeClass('w-64').addClass('w-20');
                    texts.addClass('hidden opacity-0');
                    icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
                    localStorage.setItem('sidebarExpanded', 'false');
                } else {
                    // Expand sidebar
                    sb.removeClass('w-20').addClass('w-64');
                    setTimeout(() => texts.removeClass('hidden opacity-0'), 150);
                    icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
                    localStorage.setItem('sidebarExpanded', 'true');
                }
            });

            // Initial Sidebar State
            if (localStorage.getItem('sidebarExpanded') === 'true') {
                $('#sidebar').removeClass('w-20').addClass('w-64');
                $('.sidebar-text').removeClass('hidden opacity-0');
                $('#toggleSidebar i').removeClass('fa-chevron-right').addClass('fa-chevron-left');
            } else {
                $('#sidebar').removeClass('w-64').addClass('w-20');
                $('.sidebar-text').addClass('hidden opacity-0');
                $('#toggleSidebar i').removeClass('fa-chevron-left').addClass('fa-chevron-right');
            }

            // Auto Sync Trigger
            <?php if (isset($trigger_sync) && $trigger_sync): ?>
                $.post('api/hostinger.php', { action: 'sync' }, function (res) {
                    console.log('Auto sync completed:', res.message);
                    if (res.added && res.added.length > 0) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'Yeni siteler tespit edildi!',
                            text: res.added.join(', '),
                            timer: 5000
                        });
                        setTimeout(() => location.reload(), 2000);
                    } else if (res.updated_count > 0) {
                        // Reload to show new dates
                        location.reload();
                    }
                });
            <?php endif; ?>
        });

        // Accept Renewal (Sync API Date)
        function acceptRenewal(id) {
            Swal.fire({
                title: 'Yenilemeyi Onayla?',
                text: "Bu site için ödeme alındığını ve sürenin uzatılacağını onaylıyor musunuz? Tarih, Hostinger verisiyle güncellenecektir.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Onayla',
                confirmButtonColor: '#16a34a', // green-600
                cancelButtonText: 'İptal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/hostinger.php', { action: 'accept_renewal', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', res.message, 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        // Context Menu Handler
        let currentCtxId = null;
        let currentCtxType = null;

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#premiumContextMenu').length) {
                $('#premiumContextMenu').addClass('hidden');
            }
        });

        function showRenewalMenu(event, siteId) {
            event.preventDefault();
            event.stopPropagation();

            const item = $(event.currentTarget);
            const domain = item.data('domain');
            const status = item.data('status');

            currentCtxId = siteId;
            currentCtxType = 'site';

            let itemsHtml = `
                <button onclick="renewalAction(${siteId}, 'chat')" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-indigo-500/10 flex items-center justify-center group-hover:bg-indigo-500/20 transition-all"><i class="fa-solid fa-comments text-indigo-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Sohbet Geçmişi</span>
                </button>
                <button onclick="renewalAction(${siteId}, 'whatsapp')" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-all"><i class="fa-brands fa-whatsapp text-emerald-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">WhatsApp Gönder</span>
                </button>
                <button onclick="renewalAction(${siteId}, 'edit')" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition-all"><i class="fa-solid fa-edit text-blue-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Düzenle</span>
                </button>
                <button onclick="renewalAction(${siteId}, 'renew')" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-all"><i class="fa-solid fa-sync text-emerald-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Yenile (+1 Yıl)</span>
                </button>
                <button onclick="renewalAction(${siteId}, 'status')" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-500/20 transition-all"><i class="fa-solid fa-exchange-alt text-purple-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Durum Değiştir</span>
                </button>
                <button onclick="renewalAction(${siteId}, 'reminder')" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-yellow-500/10 flex items-center justify-center group-hover:bg-yellow-500/20 transition-all"><i class="fa-solid fa-bell text-yellow-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Hatırlatma Ekle</span>
                </button>
            `;

            if (status === 'cancelled') {
                itemsHtml += `
                    <div class="my-1 border-t border-white/5"></div>
                    <button onclick="renewalAction(${siteId}, 'delete')" class="w-full text-left px-4 py-2.5 hover:bg-red-500/10 flex items-center justify-start gap-3 text-red-400 transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-red-500/10 flex items-center justify-center group-hover:bg-red-500/20 transition-all"><i class="fa-solid fa-trash text-red-500 text-xs text-center w-4"></i></div>
                        <span class="text-xs font-bold">Kalıcı Olarak Sil</span>
                    </button>
                `;
            }

            renderContextMenu(event.clientX, event.clientY, domain, itemsHtml);
        }

        // Reminder Menu Functions
        function showReminderMenu(event, reminderId) {
            event.preventDefault();
            event.stopPropagation();

            const item = $(event.currentTarget);
            const title = item.find('.font-bold').first().text().trim();

            currentCtxId = reminderId;
            currentCtxType = 'reminder';

            let itemsHtml = `
                <button onclick="snoozeReminder(${reminderId})" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-yellow-500/10 flex items-center justify-center group-hover:bg-yellow-500/20 transition-all"><i class="fa-solid fa-clock text-yellow-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Ertele / Düzenle</span>
                </button>
                <button onclick="completeReminder(${reminderId})" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-all"><i class="fa-solid fa-check text-emerald-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Tamamlandı</span>
                </button>
                <button onclick="addReminderNote(${reminderId})" class="w-full text-left px-4 py-2.5 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
                    <div class="w-7 h-7 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition-all"><i class="fa-solid fa-note-sticky text-blue-400 text-xs text-center w-4"></i></div>
                    <span class="text-xs font-semibold">Not Ekle</span>
                </button>
            `;

            renderContextMenu(event.clientX, event.clientY, title, itemsHtml);
        }

        function renderContextMenu(x, y, title, itemsHtml) {
            const menu = $('#premiumContextMenu');
            $('#ctxSubtitle').text(title);
            $('#ctxItems').html(itemsHtml);

            menu.removeClass('hidden');

            const menuWidth = menu.outerWidth();
            const menuHeight = menu.outerHeight();
            const windowWidth = $(window).width();
            const windowHeight = $(window).height();

            // Sola dayalı hizalama (Menu to the left of cursor)
            let finalX = x - menuWidth;
            let finalY = y;

            // Ekran sınırları kontrolü
            if (finalX < 10) finalX = x + 5; // Click near left edge -> show to the right
            if (finalY + menuHeight > windowHeight) finalY = windowHeight - menuHeight - 10;
            if (finalY < 0) finalY = 10;

            menu.css({ top: finalY + 'px', left: finalX + 'px' });
        }

        function renewalAction(siteId, action) {
            $('#premiumContextMenu').addClass('hidden');

            if (action === 'chat') {
                showChatHistory(siteId); // New
            } else if (action === 'whatsapp') {
                sendWhatsAppDashboard(siteId);
            } else if (action === 'edit') {
                window.location.href = `sites.php?id=${siteId}`;

            } else if (action === 'renew') {
                Swal.fire({
                    title: 'Site Yenile',
                    text: 'Yenileme tarihi 1 yıl ileriye taşınacak. Onaylıyor musunuz?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Evet, Yenile',
                    cancelButtonText: 'İptal'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.post('api/sites.php', { action: 'renew', id: siteId }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire('Yenilendi!', 'Site başarıyla yenilendi', 'success').then(() => location.reload());
                            }
                        });
                    }
                });

            } else if (action === 'status') {
                // Status change modal - inline
                Swal.fire({
                    title: 'Durum Değiştir',
                    html: `
                        <div class="space-y-2 p-4">
                            <button onclick="updateSiteStatusDash(${siteId}, 'requested')" class="w-full px-4 py-3 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-lg transition font-semibold">
                                <i class="fa-solid fa-paper-plane mr-2"></i>İstendi
                            </button>
                            <button onclick="updateSiteStatusDash(${siteId}, 'accepted')" class="w-full px-4 py-3 bg-green-100 hover:bg-green-200 text-green-800 rounded-lg transition font-semibold">
                                <i class="fa-solid fa-check mr-2"></i>Kabul Etti
                            </button>
                            <button onclick="updateSiteStatusDash(${siteId}, 'active')" class="w-full px-4 py-3 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-lg transition font-semibold">
                                <i class="fa-solid fa-check-circle mr-2"></i>Yenilendi
                            </button>
                            <button onclick="updateSiteStatusDash(${siteId}, 'transferred')" class="w-full px-4 py-3 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded-lg transition font-semibold">
                                <i class="fa-solid fa-exchange-alt mr-2"></i>Transfer
                            </button>
                            <button onclick="updateSiteStatusDash(${siteId}, 'cancelled')" class="w-full px-4 py-3 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition font-semibold">
                                <i class="fa-solid fa-ban mr-2"></i>İptal
                            </button>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Kapat',
                    width: '400px'
                });

            } else if (action === 'reminder') {
                // Calculate tomorrow's date
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                const tomorrowStr = tomorrow.toISOString().split('T')[0];

                // Add reminder modal - inline
                Swal.fire({
                    title: 'Hatırlatma Ekle',
                    html: `
                        <div class="text-left space-y-3">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Başlık</label>
                                <input type="text" id="reminderTitle" value="Yenileme Hatırlatması" class="w-full border rounded px-3 py-2">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Tarih</label>
                                    <input type="date" id="reminderDate" value="${tomorrowStr}" class="w-full border rounded px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Saat</label>
                                    <input type="time" id="reminderTime" value="09:00" class="w-full border rounded px-3 py-2">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Not</label>
                                <textarea id="reminderNote" rows="3" class="w-full border rounded px-3 py-2" placeholder="Opsiyonel..."></textarea>
                            </div>
                            <div class="flex items-center gap-3 bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="reminderAlarm" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900">
                                        <i class="fa-solid fa-bell mr-1"></i>Alarm Kur (WhatsApp Bildirimi)
                                    </span>
                                </label>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Kaydet',
                    cancelButtonText: 'İptal',
                    preConfirm: () => {
                        const title = $('#reminderTitle').val();
                        const date = $('#reminderDate').val();
                        const time = $('#reminderTime').val();
                        const note = $('#reminderNote').val();
                        const alarm = $('#reminderAlarm').is(':checked') ? 1 : 0;

                        if (!title || !date) {
                            Swal.showValidationMessage('Lütfen başlık ve tarih girin');
                            return false;
                        }
                        return { title, date, time, note, alarm };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('api/sites.php', {
                            action: 'add_reminder',
                            site_id: siteId,
                            title: result.value.title,
                            reminder_date: result.value.date,
                            reminder_time: result.value.time,
                            note: result.value.note,
                            alarm_enabled: result.value.alarm
                        }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire('Eklendi!', 'Hatırlatma başarıyla eklendi', 'success').then(() => location.reload());
                            }
                        });
                    }
                });

            } else if (action === 'delete') {
                Swal.fire({
                    title: 'Siteyi Sil?',
                    text: 'Bu işlem geri alınamaz!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Evet, Sil',
                    cancelButtonText: 'İptal',
                    confirmButtonColor: '#dc2626'
                }).then(result => {
                    if (result.isConfirmed) {
                        $.post('api/sites.php', { action: 'delete', id: siteId }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire('Silindi!', 'Site başarıyla silindi', 'success').then(() => location.reload());
                            }
                        });
                    }
                });
            }
        }

        function updateSiteStatusDash(siteId, status) {
            Swal.close();
            const statusLabels = {
                'requested': 'İstendi',
                'accepted': 'Kabul Etti',
                'active': 'Yenilendi',
                'transferred': 'Transfer',
                'cancelled': 'İptal'
            };

            $.post('api/sites.php', {
                action: 'update_status',
                id: siteId,
                status: status
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Durum: ${statusLabels[status]}`,
                        showConfirmButton: false,
                        timer: 2000
                    });
                    setTimeout(() => location.reload(), 2000);
                }
            });
        }

        // Reminder Detail Modal
        function showReminderDetail(reminderId) {
            $.get('api/reminders.php', { action: 'get', id: reminderId }, function (res) {
                if (res.status === 'success') {
                    const reminder = res.data;
                    const notes = reminder.description ? reminder.description.split('\n\n').filter(n => n.trim()) : [];
                    const notesHTML = notes.length > 0
                        ? notes.map(n => `<div class="bg-gray-50 p-2 rounded text-sm text-left mb-2">${n.replace(/\n/g, '<br>')}</div>`).join('')
                        : '<p class="text-gray-500 text-sm">Henüz not eklenmemiş</p>';

                    Swal.fire({
                        title: `<i class="fa-solid fa-bell text-yellow-500 mr-2"></i>${reminder.title}`,
                        html: `
                            <div class="text-left space-y-4">
                                <div class="bg-blue-50 p-3 rounded">
                                    <p class="text-sm font-semibold text-blue-900">Hatırlatma Bilgileri:</p>
                                    ${reminder.domain ? `<p class="text-sm text-blue-700">🌐 ${reminder.domain}</p>` : ''}
                                    <p class="text-sm text-blue-700">📅 Tarih: ${new Date(reminder.reminder_date).toLocaleDateString('tr-TR')} ${reminder.reminder_time}</p>
                                    <p class="text-sm text-blue-700">⏰ Kalan: ${Math.ceil((new Date(reminder.reminder_date) - new Date()) / (1000 * 60 * 60 * 24))} gün</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold mb-2">Notlar:</p>
                                    ${notesHTML}
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button onclick="reminderDetailAction(${reminderId}, 'note')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                                        <i class="fa-solid fa-note-sticky mr-1"></i>Not Ekle
                                    </button>
                                    <button onclick="reminderDetailAction(${reminderId}, 'edit')" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm">
                                        <i class="fa-solid fa-edit mr-1"></i>Düzenle
                                    </button>
                                    ${reminder.site_id ? `<button onclick="reminderDetailAction(${reminderId}, 'whatsapp', ${reminder.site_id})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                        <i class="fa-brands fa-whatsapp mr-1"></i>WhatsApp
                                    </button>` : ''}
                                    <button onclick="reminderDetailAction(${reminderId}, 'complete')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                                        <i class="fa-solid fa-check mr-1"></i>Tamamla
                                    </button>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        cancelButtonText: 'Kapat',
                        showConfirmButton: false,
                        width: '600px'
                    });
                }
            });
        }

        function reminderDetailAction(reminderId, action, siteId = null) {
            if (action === 'note') {
                Swal.close();
                addReminderNote(reminderId);
            } else if (action === 'edit') {
                Swal.close();
                snoozeReminder(reminderId);
            } else if (action === 'whatsapp' && siteId) {
                Swal.close();
                window.location.href = `sites.php?action=whatsapp&id=${siteId}`;
            } else if (action === 'complete') {
                Swal.close();
                completeReminder(reminderId);
            }
        }

        // Complete already handled by snooze/complete/note


        function snoozeReminder(id) {
            $.get('api/reminders.php', { action: 'get', id: id }, function (res) {
                const current = res.data;
                Swal.close();
                Swal.fire({
                    title: 'Hatırlatmayı Düzenle',
                    html: `
                        <div class="text-left space-y-3">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Başlık</label>
                                <input type="text" id="editTitle" value="${current.title}" class="w-full border rounded px-3 py-2">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Tarih</label>
                                    <input type="date" id="editDate" value="${current.reminder_date}" class="w-full border rounded px-3 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Saat</label>
                                    <input type="time" id="editTime" value="${current.reminder_time}" class="w-full border rounded px-3 py-2">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Not</label>
                                <textarea id="editNote" rows="3" class="w-full border rounded px-3 py-2">${current.description || ''}</textarea>
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Güncelle',
                    cancelButtonText: 'İptal',
                    preConfirm: () => {
                        const title = $('#editTitle').val();
                        const date = $('#editDate').val();
                        const time = $('#editTime').val();
                        const note = $('#editNote').val();
                        if (!title || !date) {
                            Swal.showValidationMessage('Lütfen başlık ve tarih girin');
                            return false;
                        }
                        return { title, date, time, note };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('api/reminders.php', {
                            action: 'update',
                            id: id,
                            title: result.value.title,
                            date: result.value.date,
                            time: result.value.time,
                            note: result.value.note
                        }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire('Güncellendi!', 'Hatırlatma güncellendi', 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Hata', res.message, 'error');
                            }
                        });
                    }
                });
            });
        }

        function completeReminder(id) {
            Swal.close();
            Swal.fire({
                title: 'Tamamlandı olarak işaretle?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet',
                cancelButtonText: 'İptal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/reminders.php', {
                        action: 'complete',
                        id: id
                    }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Tamamlandı!', 'Hatırlatma işaretlendi', 'success').then(() => location.reload());
                        }
                    });
                }
            });
        }

        function addReminderNote(id) {
            Swal.close();
            Swal.fire({
                title: 'Not Ekle',
                html: `
                    <textarea id="reminderNote" rows="4" class="w-full border rounded px-3 py-2" placeholder="Not yazın..."></textarea>
                `,
                showCancelButton: true,
                confirmButtonText: 'Kaydet',
                cancelButtonText: 'İptal',
                preConfirm: () => {
                    return $('#reminderNote').val();
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/reminders.php', {
                        action: 'add_note',
                        id: id,
                        note: result.value
                    }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Kaydedildi!', 'Not eklendi', 'success');
                        }
                    });
                }
            });
        }
        // Chat History Functions
        function showChatHistory(mixedId, isCustomer = false) {
            console.log('🔵 [Dashboard] showChatHistory called', { mixedId, isCustomer });

            Swal.close();
            Swal.fire({
                title: 'Sohbet Yükleniyor...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            if (!isCustomer) {
                console.log('🔍 [Dashboard] Fetching site data for siteId:', mixedId);

                $.get('api/sites.php', { action: 'get', id: mixedId }, function (res) {
                    console.log('✅ [Dashboard] Site data received:', res);

                    if (res.status === 'success' && res.data.customer_id) {
                        console.log('🔍 [Dashboard] Fetching chat for customerId:', res.data.customer_id);
                        fetchChat(res.data.customer_id);
                    } else {
                        console.error('❌ [Dashboard] Customer not found in site data:', res);
                        Swal.fire('Hata', 'Müşteri bulunamadı', 'error');
                    }
                }).fail(function (xhr, status, error) {
                    console.error('❌ [Dashboard] Failed to fetch site data:', { xhr, status, error, responseText: xhr.responseText });
                    Swal.fire('Hata', 'Site bilgisi alınamadı: ' + error, 'error');
                });
            } else {
                console.log('🔍 [Dashboard] Direct customer chat for customerId:', mixedId);
                fetchChat(mixedId);
            }

            function fetchChat(customerId) {
                console.log('🔵 [Dashboard] fetchChat called for customerId:', customerId);

                $.get('api/whatsapp.php', { action: 'get_messages_by_customer', customer_id: customerId }, function (res) {
                    console.log('✅ [Dashboard] WhatsApp API response:', res);

                    if (res.status === 'success') {
                        console.log('📱 [Dashboard] Rendering chat modal with', res.data.length, 'messages');
                        renderChatModal(res.data, res.jid);
                    } else {
                        console.error('❌ [Dashboard] WhatsApp API error:', res);
                        Swal.fire('Hata', res.message, 'error');
                    }
                }).fail(function (xhr, status, error) {
                    console.error('❌ [Dashboard] Failed to fetch WhatsApp messages:', {
                        xhr,
                        status,
                        error,
                        responseText: xhr.responseText,
                        url: 'api/whatsapp.php?action=get_messages_by_customer&customer_id=' + customerId
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Mesaj Alınamadı',
                        html: `<p>WhatsApp mesajları alınamadı.</p><pre class="text-xs text-left mt-2 bg-gray-100 p-2 max-h-32 overflow-auto">${xhr.responseText || error}</pre>`,
                        width: '600px'
                    });
                });
            }
        }

        function renderChatModal(initialMessages, jid) {
            if (chatPollInterval) clearInterval(chatPollInterval);

            // Keep track of the last rendered message timestamp/ID to prevent full re-renders
            // However, since we receive the full list, simple diffing is easier.

            const generateBubble = (msg) => {
                let icon = '';
                if (msg.type === 'image') icon = '<i class="fa-solid fa-image mr-1"></i>';
                else if (msg.type === 'video') icon = '<i class="fa-solid fa-video mr-1"></i>';
                else if (msg.type === 'document') icon = '<i class="fa-solid fa-file mr-1"></i>';
                else if (msg.type === 'audio') icon = '<i class="fa-solid fa-microphone mr-1"></i>';

                const isMe = Boolean(msg.fromMe);
                const align = isMe ? 'self-end' : 'self-start';
                // Premium Design
                const color = isMe
                    ? 'bg-emerald-500/10 text-emerald-100 border border-emerald-500/20 rounded-tr-none'
                    : 'bg-white/5 text-slate-200 border border-white/10 rounded-tl-none';

                const time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'numeric' });

                return `
                <div class="${align} max-w-[80%] rounded-2xl p-4 shadow-sm ${color} mb-3 backdrop-blur-sm transition-all hover:scale-[1.01]" data-id="${msg.id}">
                    <p class="text-sm pb-1 break-words leading-relaxed">${icon}${msg.content}</p>
                    <p class="text-[10px] text-white/40 text-right mt-1 font-medium">${time}</p>
                </div>
                `;
            };

            const generateContent = (msgs) => {
                if (!msgs || msgs.length === 0) {
                    return `
                    <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-60">
                        <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                            <i class="fa-regular fa-comments text-3xl"></i>
                        </div>
                        <p class="font-medium text-sm">Mesaj geçmişi bulunamadı</p>
                    </div>`;
                }
                return msgs.map(generateBubble).join('');
            };

            let containerHtml = `<div class="flex flex-col h-[500px] overflow-y-auto p-6 bg-black/40 rounded-3xl border border-white/5 custom-scrollbar backdrop-blur-md" id="chatContainer">`;
            containerHtml += generateContent(initialMessages);
            containerHtml += '</div>';

            containerHtml += `
            <div class="mt-4 flex gap-3">
                <input type="text" id="chatInput" 
                    class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-sm focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 placeholder:text-slate-600 transition-all outline-none" 
                    placeholder="Mesajınızı yazın...">
                <button id="sendChatBtn" 
                    class="w-14 h-14 btn-gradient-primary rounded-2xl flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 transition-transform active:scale-95 group">
                    <i class="fa-solid fa-paper-plane text-xl text-white group-hover:rotate-12 transition-transform"></i>
                </button>
            </div>
            `;

            let isPolling = false;

            Swal.fire({
                title: `<span class="text-white logo-font tracking-wide text-xl">Sohbet: <span class="text-slate-400 text-base font-normal ml-2 font-sans">${jid}</span></span>`,
                html: containerHtml,
                width: '700px',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: {
                    popup: 'glass-card rounded-[2.5rem] border border-white/10 p-0 overflow-hidden',
                    htmlContainer: 'p-6 !mt-0',
                    title: '!p-6 !pb-0 !text-left border-b border-white/5',
                    actions: 'border-t border-white/5 !py-4'
                },
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Kapat',
                allowOutsideClick: false,
                allowEscapeKey: false,
                willClose: () => {
                    if (chatPollInterval) clearInterval(chatPollInterval);
                },
                didOpen: () => {
                    const container = document.getElementById('chatContainer');
                    if (container) container.scrollTop = container.scrollHeight;

                    // START AUTO-REFRESH POLLING (1 second interval)
                    // Extract customer ID from JID for polling
                    const customerId = jid.split('@')[0]; // Get customer ID from current context

                    chatPollInterval = setInterval(() => {
                        console.log('🔄 [Dashboard] Auto-refreshing chat messages...');

                        // Get customer_id - need to extract from somewhere accessible
                        // Since we're in showChatHistory(mixedId), we need to determine customerId
                        // Best approach: Pass customerId along with jid
                        // For now, poll using JID directly
                        $.post('api/whatsapp.php', { action: 'fetch_messages', jid: jid, force_refresh: 1 }, function (res) {
                            if (res.status === 'success' && res.data) {
                                const currentScroll = container.scrollTop;
                                const maxScroll = container.scrollHeight - container.clientHeight;
                                const isAtBottom = (maxScroll - currentScroll) < 50;

                                // Update chat content
                                $('#chatContainer').html(generateContent(res.data));

                                // Auto-scroll only if user was at bottom
                                if (isAtBottom) {
                                    container.scrollTop = container.scrollHeight;
                                }
                            }
                        }).fail(function () {
                            console.warn('⚠️ [Dashboard] Chat refresh failed');
                        });
                    }, 1000); // 1 second

                    console.log('✅ [Dashboard] Chat polling started (1 sec interval)');

                    // Send Logic
                    const sendMessage = () => {
                        const msg = $('#chatInput').val();
                        if (!msg.trim()) return;

                        $('#chatInput').prop('disabled', true);
                        $('#sendChatBtn').prop('disabled', true);

                        $.post('api/whatsapp.php', {
                            action: 'send_message',
                            jid: jid,
                            message: msg
                        }, function (res) {
                            if (res.status === 'success') {
                                $('#chatInput').val('').prop('disabled', false).focus();
                            } else {
                                Swal.showValidationMessage(res.message || 'Gönderilemedi');
                                $('#chatInput').prop('disabled', false);
                            }
                            $('#sendChatBtn').prop('disabled', false);
                        }).fail(function () {
                            Swal.showValidationMessage('API Hatası');
                            $('#chatInput').prop('disabled', false);
                            $('#sendChatBtn').prop('disabled', false);
                        });
                    };

                    $('#sendChatBtn').click(sendMessage);
                    $('#chatInput').on('keypress', function (e) {
                        if (e.which == 13) sendMessage();
                    });
                }
            });
        }




    </script>
    <script src="assets/js/mobile-long-press.js"></script>
</body>

</html>