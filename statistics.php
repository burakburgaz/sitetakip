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

<body class="bg-gradient-to-br from-gray-50 to-blue-50 flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="bg-white shadow-md z-10 p-4 border-b-2 border-indigo-600">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-chart-line text-indigo-600"></i>
                        İstatistikler & Analizler
                    </h2>
                    <p class="text-xs text-gray-600 mt-1">Aylık bazlı detaylı analiz - Tüm yıllar birleşik</p>
                </div>
                <button onclick="window.print()"
                    class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm">
                    <i class="fa-solid fa-print mr-1"></i>Yazdır
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4">
            <!-- Özet Kartları -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-md p-3 text-white">
                    <p class="text-xs opacity-90">Toplam Site</p>
                    <h3 class="text-2xl font-bold"><?= $stats['total_sites'] ?></h3>
                    <p class="text-xs opacity-75">Aktif: <?= $stats['active_sites'] ?></p>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-md p-3 text-white">
                    <p class="text-xs opacity-90">Toplam Aylık Gelir</p>
                    <h3 class="text-xl font-bold"><?= format_currency($stats['total_revenue']) ?></h3>
                    <p class="text-xs opacity-75">Aylık Ort: <?= format_currency($stats['avg_monthly_revenue']) ?></p>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-md p-3 text-white">
                    <p class="text-xs opacity-90">Ortalama Site Fiyatı</p>
                    <h3 class="text-xl font-bold"><?= format_currency($extra_stats['avg_price']) ?></h3>
                    <p class="text-xs opacity-75">Aylık Ort:
                        <?= number_format($extra_stats['avg_monthly_additions'], 1) ?> site
                    </p>
                </div>
                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg shadow-md p-3 text-white">
                    <p class="text-xs opacity-90"><?= $extra_stats['display_month'] ?> Ayı Yenilenecek</p>
                    <h3 class="text-2xl font-bold"><?= $extra_stats['display_renewals'] ?></h3>
                    <p class="text-xs opacity-75">Gelir: <?= format_currency($extra_stats['display_revenue']) ?></p>
                </div>
                <?php if ($extra_stats['next_renewals'] > 0): ?>
                    <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg shadow-md p-3 text-white">
                        <p class="text-xs opacity-90"><?= $extra_stats['next_month_name'] ?> Ayı Yenilenecek</p>
                        <h3 class="text-2xl font-bold"><?= $extra_stats['next_renewals'] ?></h3>
                        <p class="text-xs opacity-75">Gelir: <?= format_currency($extra_stats['next_revenue']) ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Grafikler -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <div class="bg-white rounded-lg shadow-md p-3">
                    <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-calendar-alt text-blue-600 text-xs"></i>Aylık Yenilenecek Site Sayısı
                    </h3>
                    <div class="h-48"><canvas id="renewalsChart"></canvas></div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-3">
                    <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-coins text-green-600 text-xs"></i>Aylık Gelecek Gelir (₺)
                    </h3>
                    <div class="h-48"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-3">
                    <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-chart-pie text-purple-600 text-xs"></i>Durum Dağılımı
                    </h3>
                    <div class="h-48"><canvas id="statusChart"></canvas></div>
                </div>
                <div class="bg-white rounded-lg shadow-md p-3">
                    <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-box text-indigo-600 text-xs"></i>Paket Dağılımı
                    </h3>
                    <div class="h-48"><canvas id="packageChart"></canvas></div>
                </div>
            </div>

            <!-- AYLIK DETAYLI ANALİZ TABLOSU (12 AY - AY BAZLI) -->
            <div class="bg-white rounded-lg shadow-md p-3 mb-4">
                <h3 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-table text-indigo-600"></i>
                    Aylık Detaylı Analiz (12 Ay - Tüm Yıllar Birleşik)
                </h3>
                <p class="text-xs text-gray-600 mb-3">Her satır bir ayı temsil eder (hangi yıl olursa olsun). Örnek:
                    Ocak satırında 2024 Ocak, 2025 Ocak, 2026 Ocak hepsi birleştirilmiştir.</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                            <tr>
                                <th class="px-2 py-2 text-left font-semibold">Ay</th>
                                <th class="px-2 py-2 text-center font-semibold">Aktif Site</th>
                                <th class="px-2 py-2 text-center font-semibold">Yenilenecek</th>
                                <th class="px-2 py-2 text-center font-semibold">Yenilendi</th>
                                <th class="px-2 py-2 text-center font-semibold">Eklenen</th>
                                <th class="px-2 py-2 text-center font-semibold">İptal</th>
                                <th class="px-2 py-2 text-center font-semibold">Transfer</th>
                                <th class="px-2 py-2 text-right font-semibold">Gelir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $current_month = (int) date('n'); // Şu anki ay numarası (1-12)
                            foreach ($monthly_analysis as $month_data):
                                $is_current = ($month_data['month_num'] == $current_month);
                                $row_class = $is_current ? 'bg-indigo-100 border-l-4 border-indigo-600' : 'hover:bg-gray-50';
                                $text_class = $is_current ? 'font-bold text-indigo-900' : 'text-gray-700';
                                ?>
                                <tr class="<?= $row_class ?> border-b border-gray-100 transition">
                                    <td class="px-2 py-2 <?= $text_class ?>">
                                        <?= $month_data['month'] ?>
                                        <?php if ($is_current): ?>
                                            <span
                                                class="ml-1 px-1.5 py-0.5 bg-indigo-600 text-white text-xs rounded-full">●</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-2 text-center"><span
                                            class="text-green-700 font-semibold"><?= $month_data['active'] ?></span></td>
                                    <td class="px-2 py-2 text-center">
                                        <?php if ($month_data['renewals'] > 0): ?>
                                            <span
                                                class="px-2 py-0.5 <?= $month_data['renewals'] > 10 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?> rounded text-xs font-bold">
                                                <?= $month_data['renewals'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <?= $month_data['renewed'] > 0 ? '<span class="text-green-600 font-semibold">✓ ' . $month_data['renewed'] . '</span>' : '<span class="text-gray-300">-</span>' ?>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <?= $month_data['added'] > 0 ? '<span class="text-blue-600 font-semibold">+ ' . $month_data['added'] . '</span>' : '<span class="text-gray-300">-</span>' ?>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <?= $month_data['cancelled'] > 0 ? '<span class="text-red-600 font-semibold">' . $month_data['cancelled'] . '</span>' : '<span class="text-gray-300">-</span>' ?>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <?= $month_data['transferred'] > 0 ? '<span class="text-indigo-600 font-semibold">' . $month_data['transferred'] . '</span>' : '<span class="text-gray-300">-</span>' ?>
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <?= $month_data['revenue'] > 0 ? '<span class="font-bold text-green-600">' . number_format($month_data['revenue'], 0, ',', '.') . '₺</span>' : '<span class="text-gray-300">-</span>' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gradient-to-r from-gray-700 to-gray-800 text-white font-bold text-xs">
                            <tr>
                                <td class="px-2 py-2">TOPLAM</td>
                                <td class="px-2 py-2 text-center"><?= $stats['active_sites'] ?></td>
                                <td class="px-2 py-2 text-center">
                                    <?= array_sum(array_column($monthly_analysis, 'renewals')) ?>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <?= array_sum(array_column($monthly_analysis, 'renewed')) ?>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <?= array_sum(array_column($monthly_analysis, 'added')) ?>
                                </td>
                                <td class="px-2 py-2 text-center"><?= $stats['cancelled_sites'] ?></td>
                                <td class="px-2 py-2 text-center"><?= $stats['transferred_sites'] ?></td>
                                <td class="px-2 py-2 text-right">
                                    <?= number_format($stats['total_revenue'], 0, ',', '.') ?>₺
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Ek Bilgiler -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <!-- Top Müşteriler -->
                <div class="bg-white rounded-lg shadow-md p-3">
                    <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-trophy text-yellow-600 text-xs"></i>Top 10 Müşteriler
                    </h3>
                    <div class="space-y-1">
                        <?php $rank = 1;
                        foreach ($top_customers as $customer): ?>
                            <div
                                class="flex items-center justify-between p-2 bg-gray-50 rounded hover:bg-blue-50 transition">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-gradient-to-br from-yellow-400 to-yellow-600 text-white text-xs flex items-center justify-center font-bold"><?= $rank++ ?></span>
                                    <span
                                        class="text-xs font-semibold text-gray-800"><?= htmlspecialchars($customer['full_name']) ?></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span
                                        class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-xs font-bold"><?= $customer['site_count'] ?></span>
                                    <span
                                        class="text-xs font-bold text-green-600"><?= format_currency($customer['total_revenue']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Site Yaşı -->
                <div class="bg-white rounded-lg shadow-md p-3">
                    <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-1">
                        <i class="fa-solid fa-calendar-days text-teal-600 text-xs"></i>Site Yaşı Dağılımı
                    </h3>
                    <div class="h-64"><canvas id="ageChart"></canvas></div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.font.size = 11;

        const monthlyData = <?= json_encode($monthly_analysis) ?>;
        const monthLabels = monthlyData.map(d => d.month);

        // Aylık Yenilenecek Site Sayısı
        new Chart(document.getElementById('renewalsChart'), {
            type: 'bar',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Yenilenecek',
                    data: monthlyData.map(d => d.renewals),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // Aylık Gelecek Gelir
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Gelir (₺)',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        const statusData = <?= json_encode(array_values($status_distribution)) ?>;
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                datasets: [{ data: statusData.map(d => d.count), backgroundColor: ['#10b981', '#ef4444', '#3b82f6', '#f59e0b', '#8b5cf6'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
        });

        const packageData = <?= json_encode(array_values($package_distribution)) ?>;
        new Chart(document.getElementById('packageChart'), {
            type: 'bar',
            data: {
                labels: packageData.map(d => d.package_type),
                datasets: [{ label: 'Site', data: packageData.map(d => d.count), backgroundColor: ['rgba(99, 102, 241, 0.8)', 'rgba(139, 92, 246, 0.8)', 'rgba(236, 72, 153, 0.8)'] }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        const ageData = <?= json_encode(array_values($age_distribution)) ?>;
        new Chart(document.getElementById('ageChart'), {
            type: 'polarArea',
            data: {
                labels: ageData.map(d => d.age_range),
                datasets: [{ data: ageData.map(d => d.count), backgroundColor: ['rgba(20, 184, 166, 0.6)', 'rgba(59, 130, 246, 0.6)', 'rgba(168, 85, 247, 0.6)', 'rgba(236, 72, 153, 0.6)', 'rgba(251, 146, 60, 0.6)'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } } } }
        });
    </script>
</body>

</html>