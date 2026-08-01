<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
require_admin();

$page_title = 'İstatistikler - DReklam';

// Türkçe ay isimleri
$tr_months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

// Genel İstatistikler
$stats = [];
$stats['total_sites'] = $pdo->query("SELECT COUNT(*) FROM sites")->fetchColumn();
$stats['active_sites'] = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'active'")->fetchColumn();
$stats['cancelled_sites'] = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'cancelled'")->fetchColumn();
$stats['transferred_sites'] = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'transferred'")->fetchColumn();
$stats['total_revenue'] = $pdo->query("SELECT SUM(price) FROM sites WHERE status = 'active'")->fetchColumn() ?? 0;
$stats['added_this_month'] = $pdo->query("SELECT COUNT(*) FROM sites WHERE DATE(created_at) >= DATE('now', 'start of month')")->fetchColumn();
$stats['cancelled_this_month'] = $pdo->query("SELECT COUNT(*) FROM sites WHERE status = 'cancelled' AND DATE(updated_at) >= DATE('now', 'start of month')")->fetchColumn();


// AY BAZLI ANALİZ (Optimize Edilmiş Tek Sorgu)
// Tüm verileri tek seferde çekip PHP tarafında işliyoruz (SQLite performans optimizasyonu)

$sql = "
    SELECT 
        strftime('%m', renewal_date) as renewal_month,
        strftime('%m', last_renewed_at) as renewed_month,
        strftime('%m', created_at) as created_month,
        strftime('%m', updated_at) as updated_month,
        status,
        price,
        last_renewed_at,
        created_at,
        updated_at
    FROM sites
";

$all_sites = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$monthly_analysis = [];
// Diziyi boş verilerle başlat
for ($m = 1; $m <= 12; $m++) {
    $month_str = str_pad($m, 2, '0', STR_PAD_LEFT);
    $monthly_analysis[$month_str] = [
        'month' => $tr_months[$m - 1],
        'month_num' => $m,
        'renewals' => 0,
        'renewed' => 0,
        'added' => 0,
        'cancelled' => 0,
        'transferred' => 0,
        'revenue' => 0,
        'active' => 0
    ];
}

foreach ($all_sites as $site) {
    // 1. O ayda yenilenecek siteler (Aktif ve renewal_month eşleşen)
    if ($site['status'] === 'active' && $site['renewal_month']) {
        $monthly_analysis[$site['renewal_month']]['renewals']++;
        $monthly_analysis[$site['renewal_month']]['active']++;
        $monthly_analysis[$site['renewal_month']]['revenue'] += $site['price'];
    }

    // 2. O ayda yenilenen siteler
    if ($site['renewed_month']) {
        $monthly_analysis[$site['renewed_month']]['renewed']++;
    }

    // 3. O ayda eklenen siteler
    if ($site['created_month']) {
        $monthly_analysis[$site['created_month']]['added']++;
    }

    // 4. İptal ve Transfer (update date bazlı)
    if ($site['updated_month']) {
        if ($site['status'] === 'cancelled') {
            $monthly_analysis[$site['updated_month']]['cancelled']++;
        }
        if ($site['status'] === 'transferred') {
            $monthly_analysis[$site['updated_month']]['transferred']++;
        }
    }
}

// Key'leri sayısal indekse çevir (grafikler için)
$monthly_analysis = array_values($monthly_analysis);

// Aylık ortalama gelir hesapla (12 ayın toplam gelirinin ortalaması)
$stats['avg_monthly_revenue'] = array_sum(array_column($monthly_analysis, 'revenue')) / 12;

// Durum dağılımı
$status_distribution = $pdo->query("SELECT status, COUNT(*) as count FROM sites GROUP BY status")->fetchAll();

// Paket türü dağılımı
$package_distribution = $pdo->query("
    SELECT package_type, COUNT(*) as count, SUM(price) as revenue
    FROM sites WHERE status = 'active'
    GROUP BY package_type ORDER BY count DESC
")->fetchAll();

// En çok sitesi olan müşteriler
$top_customers = $pdo->query("
    SELECT c.full_name, COUNT(s.id) as site_count, SUM(s.price) as total_revenue
    FROM customers c LEFT JOIN sites s ON c.id = s.customer_id
    WHERE s.status = 'active'
    GROUP BY c.id ORDER BY site_count DESC LIMIT 10
")->fetchAll();

// Site yaşı dağılımı
$age_distribution = $pdo->query("
    SELECT 
        CASE 
            WHEN julianday('now') - julianday(start_date) < 365 THEN '0-1 yıl'
            WHEN julianday('now') - julianday(start_date) < 730 THEN '1-2 yıl'
            WHEN julianday('now') - julianday(start_date) < 1095 THEN '2-3 yıl'
            WHEN julianday('now') - julianday(start_date) < 1460 THEN '3-4 yıl'
            ELSE '4+ yıl'
        END as age_range, COUNT(*) as count
    FROM sites WHERE start_date IS NOT NULL AND status = 'active'
    GROUP BY age_range
")->fetchAll();

// Yenilenme oranı
$renewal_rate = $pdo->query("
    SELECT COUNT(CASE WHEN last_renewed_at IS NOT NULL THEN 1 END) * 100.0 / COUNT(*) as rate
    FROM sites WHERE status = 'active'
")->fetchColumn();

// Ek İstatistikler
// Ortalama aylık eklenen site sayısı (son 12 ayın ortalaması)
$avg_monthly_additions = $pdo->query("
    SELECT COUNT(*) / 12.0 as avg 
    FROM sites 
    WHERE created_at >= DATE('now', '-12 months')
")->fetchColumn();

// Bu ay yenilenecek siteler ve geliri
$this_month_renewals = $pdo->query("
    SELECT COUNT(*) FROM sites 
    WHERE status = 'active' AND strftime('%Y-%m', renewal_date) = strftime('%Y-%m', 'now')
")->fetchColumn();

$this_month_revenue = $pdo->query("
    SELECT SUM(price) FROM sites 
    WHERE status = 'active' AND strftime('%Y-%m', renewal_date) = strftime('%Y-%m', 'now')
")->fetchColumn() ?? 0;

// Eğer bu ay 0 ise, gelecek aylardan ilk 0 olmayanı bul
$display_month = date('n'); // Şu anki ay
$display_month_name = $tr_months[$display_month - 1];
$display_renewals = $this_month_renewals;
$display_revenue = $this_month_revenue;

if ($this_month_renewals == 0) {
    // Gelecek aylarda yenileme var mı kontrol et
    for ($i = 1; $i <= 12; $i++) {
        $check_month = ($display_month + $i - 1) % 12 + 1;
        $check_month_str = str_pad($check_month, 2, '0', STR_PAD_LEFT);

        $check_renewals = $pdo->query("
            SELECT COUNT(*) FROM sites 
            WHERE status = 'active' AND strftime('%m', renewal_date) = '$check_month_str'
        ")->fetchColumn();

        if ($check_renewals > 0) {
            $display_month = $check_month;
            $display_month_name = $tr_months[$check_month - 1];
            $display_renewals = $check_renewals;
            $display_revenue = $pdo->query("
                SELECT SUM(price) FROM sites 
                WHERE status = 'active' AND strftime('%m', renewal_date) = '$check_month_str'
            ")->fetchColumn() ?? 0;
            break;
        }
    }
}

// Bir sonraki ayı bul (display_month'tan sonraki ilk dolu ay)
$next_month = 0;
$next_month_name = '';
$next_renewals = 0;
$next_revenue = 0;

for ($i = 1; $i <= 12; $i++) {
    $check_month = ($display_month + $i - 1) % 12 + 1;
    $check_month_str = str_pad($check_month, 2, '0', STR_PAD_LEFT);

    $check_renewals = $pdo->query("
        SELECT COUNT(*) FROM sites 
        WHERE status = 'active' AND strftime('%m', renewal_date) = '$check_month_str'
    ")->fetchColumn();

    if ($check_renewals > 0) {
        $next_month = $check_month;
        $next_month_name = $tr_months[$check_month - 1];
        $next_renewals = $check_renewals;
        $next_revenue = $pdo->query("
            SELECT SUM(price) FROM sites 
            WHERE status = 'active' AND strftime('%m', renewal_date) = '$check_month_str'
        ")->fetchColumn() ?? 0;
        break;
    }
}

$extra_stats = [
    'avg_price' => $stats['active_sites'] > 0 ? $stats['total_revenue'] / $stats['active_sites'] : 0,
    'avg_monthly_additions' => $avg_monthly_additions,
    'display_month' => $display_month_name,
    'display_renewals' => $display_renewals,
    'display_revenue' => $display_revenue,
    'next_month_name' => $next_month_name,
    'next_renewals' => $next_renewals,
    'next_revenue' => $next_revenue,
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn(),
];


?>
<?php include 'includes/head.php'; ?>

<body class="bg-gray-900 flex h-screen overflow-hidden">
    <!-- Background Design Elements -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="z-10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold logo-font text-white flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-indigo-500/20 rounded-xl flex items-center justify-center border border-indigo-500/30">
                            <i class="fa-solid fa-chart-line text-indigo-400 text-xl"></i>
                        </div>
                        İstatistikler & Analizler
                    </h2>
                    <p class="text-slate-400 text-xs mt-1 tracking-wider uppercase">Sistem Performans Verileri ve
                        Finansal Analizler</p>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="window.print()"
                        class="px-5 py-2.5 bg-white/5 border border-white/10 text-white rounded-2xl hover:bg-white/10 transition-all flex items-center gap-2 font-bold text-sm">
                        <i class="fa-solid fa-print text-indigo-400"></i>Yazdır
                    </button>
                    <div
                        class="inline-flex items-center gap-2 bg-white/5 border border-white/10 px-4 py-2 rounded-2xl text-slate-300">
                        <i class="fa-solid fa-calendar-day text-indigo-400"></i>
                        <span class="text-sm font-medium"><?= date('d.m.Y') ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-8 pt-2 custom-scrollbar">



            <!-- Visualization Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <!-- Monthly Volume -->
                <div class="glass-card rounded-[2.5rem] p-8">
                    <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <i class="fa-solid fa-chart-column text-blue-500"></i>
                                Aylık Yenileme Hacmi
                            </h3>
                            <p class="text-slate-500 text-xs mt-1 uppercase tracking-wider">Aylar bazında site sayıları
                            </p>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="renewalsChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Revenue Projection -->
                <div class="glass-card rounded-[2.5rem] p-8">
                    <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <i class="fa-solid fa-wave-square text-emerald-500"></i>
                                Gelir Projeksiyonu
                            </h3>
                            <p class="text-slate-500 text-xs mt-1 uppercase tracking-wider">Aylık beklenen nakit akışı
                            </p>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Secondary Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                <div class="glass-card rounded-[2.5rem] p-8">
                    <h3 class="text-sm font-bold text-slate-300 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-circle-nodes text-purple-500"></i>
                        Site Durumları
                    </h3>
                    <div class="h-56">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <div class="glass-card rounded-[2.5rem] p-8">
                    <h3 class="text-sm font-bold text-slate-300 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-amber-500"></i>
                        Paket Segmentasyonu
                    </h3>
                    <div class="h-56">
                        <canvas id="packageChart"></canvas>
                    </div>
                </div>

                <div class="glass-card rounded-[2.5rem] p-8">
                    <h3 class="text-sm font-bold text-slate-300 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-hourglass-half text-teal-500"></i>
                        Hizmet Yaşı (LTV)
                    </h3>
                    <div class="h-56">
                        <canvas id="ageChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Detailed Analysis Table (The Master Table) -->
            <div class="glass-card rounded-[2.5rem] p-8 mb-10 overflow-hidden relative">
                <div class="absolute top-0 right-0 p-8 opacity-5">
                    <i class="fa-solid fa-table-list text-8xl text-indigo-500"></i>
                </div>
                <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6 relative z-10">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-3">
                            <i class="fa-solid fa-calendar-check text-indigo-500"></i>
                            Kümülatif Aylık Performans
                        </h3>
                        <p class="text-slate-500 text-xs mt-1">Yıllardan bağımsız, ay bazlı birleştirilmiş tüm veri
                            analizi</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                        <span class="text-[10px] font-black text-indigo-300 uppercase tracking-widest">Canlı Veri</span>
                    </div>
                </div>

                <div class="overflow-x-auto relative z-10 custom-scrollbar">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="text-slate-400 border-b border-white/5 uppercase tracking-tighter">
                                <th class="px-4 py-4 font-black">Analiz Dönemi (Ay)</th>
                                <th class="px-4 py-4 text-center font-black">Aktif Portföy</th>
                                <th class="px-4 py-4 text-center font-black">Beklenen Yenileme</th>
                                <th class="px-4 py-4 text-center font-black">Başarılı Yenileme</th>
                                <th class="px-4 py-4 text-center font-black">Yeni Kazanım</th>
                                <th class="px-4 py-4 text-center font-black">Kaybedilen (Churn)</th>
                                <th class="px-4 py-4 text-right font-black">Brüt Gelir (₺)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php
                            $current_month = (int) date('n');
                            foreach ($monthly_analysis as $month_data):
                                $is_current = ($month_data['month_num'] == $current_month);
                                $row_class = $is_current ? 'bg-indigo-500/10 border-l-[4px] border-indigo-500' : 'hover:bg-white/[0.02] transition-colors';
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td class="px-4 py-5">
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="text-sm font-bold <?= $is_current ? 'text-indigo-400' : 'text-slate-200' ?>"><?= $month_data['month'] ?></span>
                                            <?php if ($is_current): ?>
                                                <span
                                                    class="bg-indigo-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-tighter">Aktif
                                                    Ay</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-5 text-center font-bold text-slate-300"><?= $month_data['active'] ?>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <?php if ($month_data['renewals'] > 0): ?>
                                            <span
                                                class="px-2.5 py-1 <?= $month_data['renewals'] > 10 ? 'bg-red-500/20 text-red-400' : 'bg-amber-500/20 text-amber-400' ?> rounded-lg font-black border border-current/10">
                                                <?= $month_data['renewals'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-slate-600">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <?= $month_data['renewed'] > 0 ? '<span class="text-emerald-400 font-bold flex items-center justify-center gap-1"><i class="fa-solid fa-check-double text-[8px]"></i>' . $month_data['renewed'] . '</span>' : '<span class="text-slate-600">-</span>' ?>
                                    </td>
                                    <td class="px-4 py-5 text-center">
                                        <?= $month_data['added'] > 0 ? '<span class="text-blue-400 font-bold flex items-center justify-center gap-1"><i class="fa-solid fa-plus text-[8px]"></i>' . $month_data['added'] . '</span>' : '<span class="text-slate-600">-</span>' ?>
                                    </td>
                                    <td class="px-4 py-5 text-center text-red-400 font-bold">
                                        <?= $month_data['cancelled'] > 0 ? $month_data['cancelled'] : '<span class="text-slate-600">-</span>' ?>
                                    </td>
                                    <td class="px-4 py-5 text-right font-black text-emerald-400">
                                        <?= $month_data['revenue'] > 0 ? number_format($month_data['revenue'], 0, ',', '.') . ' ₺' : '<span class="text-slate-600">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-white/5 text-xs font-black uppercase tracking-widest text-slate-300">
                            <tr>
                                <td class="px-4 py-5 rounded-bl-[2rem]">GENEL TOPLAM</td>
                                <td class="px-4 py-5 text-center"><?= $stats['active_sites'] ?></td>
                                <td class="px-4 py-5 text-center">
                                    <?= array_sum(array_column($monthly_analysis, 'renewals')) ?>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    <?= array_sum(array_column($monthly_analysis, 'renewed')) ?>
                                </td>
                                <td class="px-4 py-5 text-center">
                                    <?= array_sum(array_column($monthly_analysis, 'added')) ?>
                                </td>
                                <td class="px-4 py-5 text-center text-red-400"><?= $stats['cancelled_sites'] ?></td>
                                <td class="px-4 py-5 text-right text-emerald-400 rounded-br-[2rem]">
                                    <?= number_format($stats['total_revenue'], 0, ',', '.') ?> ₺
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Bottom Insights (Top Customers) -->
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-8 mb-8">
                <div class="glass-card rounded-[2.5rem] p-8">
                    <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <i class="fa-solid fa-medal text-amber-500"></i>
                                Top 10 Portföy (En Değerli Müşteriler)
                            </h3>
                            <p class="text-slate-500 text-xs mt-1 uppercase tracking-wider">Aktif site ve gelir bazlı
                                sıralama</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php $rank = 1;
                        foreach ($top_customers as $customer): ?>
                            <div
                                class="flex items-center justify-between p-4 bg-white/5 border border-white/5 rounded-2xl hover:bg-white/10 transition-all group">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-8 h-8 rounded-xl bg-gradient-to-br <?= $rank <= 3 ? 'from-amber-400 to-orange-600' : 'from-slate-600 to-slate-800' ?> text-white text-xs flex items-center justify-center font-black shadow-lg">
                                        <?= $rank++ ?>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-bold text-slate-200 group-hover:text-amber-400 transition-colors">
                                            <?= htmlspecialchars($customer['full_name']) ?>
                                        </p>
                                        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-widest">
                                            <?= $customer['site_count'] ?> Aktif Proje
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-emerald-400">
                                        <?= number_format($customer['total_revenue'], 0, ',', '.') ?> <span
                                            class="text-[10px] font-normal">₺</span>
                                    </p>
                                    <p class="text-[9px] text-slate-600 font-bold">Toplam Aylık Değer</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <!-- Quick Stats Summary (Compact Design) -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-8 pb-4">
                <!-- Total Sites -->
                <div class="glass-card p-4 rounded-xl flex items-center gap-3 relative overflow-hidden group">
                    <div
                        class="absolute -right-3 -top-3 w-12 h-12 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all">
                    </div>
                    <div
                        class="w-10 h-10 bg-blue-500/10 rounded-lg flex items-center justify-center border border-blue-500/20 shrink-0">
                        <i class="fa-solid fa-globe text-blue-400 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[9px] font-black uppercase tracking-wider">Toplam Site</h4>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <p class="text-lg font-black text-white"><?= $stats['total_sites'] ?></p>
                            <span class="text-[9px] text-blue-400 font-bold">Aktif: <?= $stats['active_sites'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="glass-card p-4 rounded-xl flex items-center gap-3 relative overflow-hidden group">
                    <div
                        class="absolute -right-3 -top-3 w-12 h-12 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all">
                    </div>
                    <div
                        class="w-10 h-10 bg-emerald-500/10 rounded-lg flex items-center justify-center border border-emerald-500/20 shrink-0">
                        <i class="fa-solid fa-lira-sign text-emerald-400 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[9px] font-black uppercase tracking-wider">Toplam Gelir</h4>
                        <p class="text-lg font-black text-white mt-0.5">
                            <?= number_format($stats['total_revenue'], 0, ',', '.') ?> <span
                                class="text-xs font-normal opacity-50">₺</span></p>
                    </div>
                </div>

                <!-- Monthly Average -->
                <div class="glass-card p-4 rounded-xl flex items-center gap-3 relative overflow-hidden group">
                    <div
                        class="absolute -right-3 -top-3 w-12 h-12 bg-purple-500/10 rounded-full blur-xl group-hover:bg-purple-500/20 transition-all">
                    </div>
                    <div
                        class="w-10 h-10 bg-purple-500/10 rounded-lg flex items-center justify-center border border-purple-500/20 shrink-0">
                        <i class="fa-solid fa-coins text-purple-400 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[9px] font-black uppercase tracking-wider">Aylık Ort.</h4>
                        <p class="text-lg font-black text-white mt-0.5">
                            <?= number_format($stats['avg_monthly_revenue'], 0, ',', '.') ?> <span
                                class="text-xs font-normal opacity-50">₺</span></p>
                    </div>
                </div>

                <!-- Monthly Renewal -->
                <div class="glass-card p-4 rounded-xl flex items-center gap-3 relative overflow-hidden group">
                    <div
                        class="absolute -right-3 -top-3 w-12 h-12 bg-orange-500/10 rounded-full blur-xl group-hover:bg-orange-500/20 transition-all">
                    </div>
                    <div
                        class="w-10 h-10 bg-orange-500/10 rounded-lg flex items-center justify-center border border-orange-500/20 shrink-0">
                        <i class="fa-solid fa-bell text-orange-400 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[9px] font-black uppercase tracking-wider">
                            <?= $extra_stats['display_month'] ?> Yenileme</h4>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <p class="text-lg font-black text-white"><?= $extra_stats['display_renewals'] ?></p>
                            <span
                                class="text-[9px] text-orange-400 font-bold"><?= number_format($extra_stats['display_revenue'], 0, ',', '.') ?>
                                ₺</span>
                        </div>
                    </div>
                </div>

                <!-- Customers -->
                <div class="glass-card p-4 rounded-xl flex items-center gap-3 relative overflow-hidden group">
                    <div
                        class="absolute -right-3 -top-3 w-12 h-12 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-500/20 transition-all">
                    </div>
                    <div
                        class="w-10 h-10 bg-indigo-500/10 rounded-lg flex items-center justify-center border border-indigo-500/20 shrink-0">
                        <i class="fa-solid fa-users text-indigo-400 text-sm"></i>
                    </div>
                    <div>
                        <h4 class="text-slate-500 text-[9px] font-black uppercase tracking-wider">Müşteriler</h4>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <p class="text-lg font-black text-white"><?= $extra_stats['total_customers'] ?></p>
                            <span class="text-[9px] text-indigo-400 font-bold">Kayıtlı</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Global Chart Defaults for Dark Theme
        Chart.defaults.color = '#64748b';
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 11;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 12;
        Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };

        const monthlyData = <?= json_encode($monthly_analysis) ?>;
        const monthLabels = monthlyData.map(d => d.month);

        // Grid Configuration
        const commonScales = {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                ticks: { color: '#64748b' }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#64748b' }
            }
        };

        // 1. Aylık Yenileme Hacmi
        new Chart(document.getElementById('renewalsChart'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Yenilenecek Site',
                    data: monthlyData.map(d => d.renewals),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 8,
                    hoverBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: commonScales
            }
        });

        // 2. Gelir Projeksiyonu
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Beklenen Gelir (₺)',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: 'rgba(255,255,255,0.2)',
                    pointBorderWidth: 4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: commonScales
            }
        });

        // 3. Durum Dağılımı (Doughnut)
        const statusData = <?= json_encode(array_values($status_distribution)) ?>;
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                datasets: [{
                    data: statusData.map(d => d.count),
                    backgroundColor: ['#10b981', '#ef4444', '#3b82f6', '#f59e0b', '#8b5cf6'],
                    borderWidth: 0,
                    hoverOffset: 20
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 10, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // 4. Paket Dağılımı
        const packageData = <?= json_encode(array_values($package_distribution)) ?>;
        new Chart(document.getElementById('packageChart'), {
            type: 'bar',
            data: {
                labels: packageData.map(d => d.package_type),
                datasets: [{
                    label: 'Segment',
                    data: packageData.map(d => d.count),
                    backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899'],
                    borderRadius: 10,
                    barThickness: 24
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: commonScales
            }
        });

        // 5. Site Yaşı (LTV)
        const ageData = <?= json_encode(array_values($age_distribution)) ?>;
        new Chart(document.getElementById('ageChart'), {
            type: 'polarArea',
            data: {
                labels: ageData.map(d => d.age_range),
                datasets: [{
                    data: ageData.map(d => d.count),
                    backgroundColor: [
                        'rgba(20, 184, 166, 0.5)',
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(168, 85, 247, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                        'rgba(251, 146, 60, 0.5)'
                    ],
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        angleLines: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { display: false }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 9 }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>