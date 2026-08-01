<?php
// includes/sidebar.php - Yan menü
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!-- Hamburger Menu Button (Mobile Only) -->
<button class="hamburger-menu" id="mobileMenuBtn" onclick="toggleMobileSidebar()">
    <i class="fa-solid fa-bars"></i>
</button>

<!-- Sidebar Overlay (Mobile Only) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleMobileSidebar()"></div>

<style>
    :root {
        --sidebar-bg: rgba(255, 255, 255, 0.85);
        --sidebar-border: rgba(0, 0, 0, 0.08);
        --sidebar-hover: rgba(0, 0, 0, 0.04);
        --sidebar-active: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --primary: #4f46e5;
    }

    #sidebar {
        background: var(--sidebar-bg) !important;
        backdrop-filter: blur(20px) !important;
        -webkit-backdrop-filter: blur(20px) !important;
        border-right: 1px solid var(--sidebar-border) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .sidebar-item {
        transition: all 0.2s ease;
        margin: 4px 0;
        border: 1px solid transparent;
    }

    .sidebar-item:hover {
        background: var(--sidebar-hover) !important;
        border-color: rgba(0, 0, 0, 0.05);
        transform: translateX(4px);
    }

    .sidebar-item.active {
        background: var(--sidebar-active) !important;
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4) !important;
        border-color: rgba(0, 0, 0, 0.1);
        color: white !important;
    }

    .sidebar-text {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 500;
        letter-spacing: 0.01em;
    }

    .logo-font {
        font-family: 'Outfit', sans-serif;
    }

    #notificationBtn {
        background: rgba(0, 0, 0, 0.02) !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s ease;
    }

    #notificationBtn:hover {
        background: rgba(0, 0, 0, 0.05) !important;
        border-color: rgba(0, 0, 0, 0.1) !important;
    }

    #toggleSidebar,
    #logoutBtn {
        background: rgba(0, 0, 0, 0.02) !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        transition: all 0.3s ease;
    }

    #toggleSidebar:hover {
        background: rgba(79, 70, 229, 0.1) !important;
        color: var(--primary) !important;
    }

    #logoutBtn:hover {
        background: rgba(239, 68, 68, 0.1) !important;
        color: #ef4444 !important;
        border-color: rgba(239, 68, 68, 0.2) !important;
    }
</style>

<aside id='sidebar' class='w-20 text-white flex flex-col transition-all duration-300 shadow-2xl relative z-[100]'>
    <!-- Logo ve Başlık -->
    <div class='p-6 border-b border-white/10'>
        <div class='flex items-center gap-3'>
            <div
                class='w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20'>
                <i class='fa-solid fa-globe text-white text-xl'></i>
            </div>
            <div class='sidebar-text hidden opacity-0'>
                <h1 class='text-xl font-bold logo-font tracking-tight'>DReklam</h1>
                <p class='text-[10px] text-blue-400 font-bold uppercase tracking-widest'>Site Takip</p>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Bilgisi -->
    <div class='p-4 border-b border-white/10'>
        <div class='flex items-center gap-3 bg-white/5 p-2 rounded-2xl border border-white/5'>
            <div class='w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center border border-blue-500/30'>
                <i class='fa-solid fa-user text-blue-400'></i>
            </div>
            <div class='sidebar-text hidden opacity-0 flex-1 min-w-0'>
                <p class='font-bold text-sm truncate'><?= htmlspecialchars($_SESSION['name_surname']) ?></p>
                <p class='text-[10px] text-slate-400 uppercase font-black tracking-tighter'>
                    <?= ucfirst($_SESSION['role']) ?>
                </p>
            </div>
        </div>
    </div>

    <?php
    // Refresh permissions from DB to ensure they are up-to-date
    $can_send_whatsapp = 0;
    $can_send_email = 0;

    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin') {
            $can_send_whatsapp = 1;
            $can_send_email = 1;
        } else {
            // Check DB if possible
            if (isset($pdo)) {
                $stmt = $pdo->prepare("SELECT can_send_whatsapp, can_send_email FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $perms = $stmt->fetch();
                if ($perms) {
                    $can_send_whatsapp = $perms['can_send_whatsapp'];
                    $can_send_email = $perms['can_send_email'];
                }
            } else {
                // Fallback to session or default
                $can_send_whatsapp = $_SESSION['can_send_whatsapp'] ?? 0;
                $can_send_email = $_SESSION['can_send_email'] ?? 0;
            }
        }
        // Update session
        $_SESSION['can_send_whatsapp'] = $can_send_whatsapp;
        $_SESSION['can_send_email'] = $can_send_email;
    }
    ?>
    <script>
        window.currentUser = {
            id: <?= $_SESSION['user_id'] ?? 0 ?>,
            role: '<?= $_SESSION['role'] ?? 'guest' ?>',
            permissions: {
                whatsapp: <?= $can_send_whatsapp ?>,
                email: <?= $can_send_email ?>
            }
        };
    </script>

    <!-- Ana Menü -->
    <nav class='flex-1 p-4 overflow-y-auto'>
        <ul class='space-y-2'>
            <li>
                <a href='dashboard.php'
                    class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'dashboard' ? 'active shadow-lg' : '' ?>'>
                    <i class='fa-solid fa-chart-line text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Dashboard</span>
                </a>
            </li>
            <li>
                <a href='sites.php'
                    class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'sites' ? 'active shadow-lg' : '' ?>'>
                    <i class='fa-solid fa-globe text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Siteler</span>
                </a>
            </li>
            <li>
                <a href='customers.php'
                    class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'customers' ? 'active shadow-lg' : '' ?>'>
                    <i class='fa-solid fa-users text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Müşteriler</span>
                </a>
            </li>
            <li>
                <a href='contacts.php'
                    class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'contacts' ? 'active shadow-lg' : '' ?>'>
                    <i class='fa-solid fa-address-book text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Rehber</span>
                </a>
            </li>
            <li>
                <a href='calendar.php'
                    class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'calendar' ? 'active shadow-lg' : '' ?>'>
                    <i class='fa-solid fa-calendar text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Takvim</span>
                </a>
            </li>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href='statistics.php'
                        class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'statistics' ? 'active shadow-lg' : '' ?>'>
                        <i class='fa-solid fa-chart-pie text-lg w-5'></i>
                        <span class='sidebar-text hidden opacity-0'>İstatistikler</span>
                    </a>
                </li>

                <li class='pt-4 mt-4 border-t border-white/10'>
                    <a href='settings.php'
                        class='sidebar-item flex items-center gap-3 p-3 rounded-xl transition <?= $current_page == 'settings' ? 'active shadow-lg' : '' ?>'>
                        <i class='fa-solid fa-cog text-lg w-5'></i>
                        <span class='sidebar-text hidden opacity-0'>Ayarlar</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Alt Kısım -->
    <div class='p-4 border-t border-white/10 space-y-2'>
        <!-- Notification Button -->
        <button id="notificationBtn" onclick="toggleNotifications()"
            class="relative w-full flex items-center justify-center gap-2 p-2 rounded-xl transition">
            <i class="fa-regular fa-clock text-lg"></i>
            <span class="sidebar-text hidden opacity-0 ml-2 text-sm font-bold">Bekleyenler</span>
            <span id="pendingCountBadge"
                class="hidden absolute top-0 right-0 transform translate-x-1/2 -translate-y-1/2 bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full border-2 border-[#0f172a] min-w-[22px] text-center shadow-lg">0</span>
        </button>

        <button id='toggleSidebar' class='w-full flex items-center justify-center gap-2 p-2 rounded-xl transition'>
            <i class='fa-solid fa-chevron-right transition-transform'></i>
        </button>
        <a href='logout.php' id="logoutBtn"
            class='w-full flex items-center justify-center gap-2 p-2 rounded-xl transition'>
            <i class='fa-solid fa-sign-out-alt'></i>
            <span class='sidebar-text hidden opacity-0 font-bold'>Çıkış</span>
        </a>
    </div>
</aside>

<!-- Notification Modal -->
<div id="notificationModal"
    class="fixed inset-0 bg-black/40 backdrop-blur-sm hidden z-[2000] flex justify-center items-start pt-24 transition-all duration-300">
    <div
        class="glass-card w-full max-w-md mx-4 rounded-3xl border border-black/5 shadow-2xl relative overflow-hidden animate-fade-in-down">
        <!-- Header -->
        <div class="p-6 border-b border-black/5 flex justify-between items-center bg-black/5">
            <h3 class="font-bold text-slate-800 flex items-center gap-3 logo-font tracking-wide">
                <div
                    class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center border border-indigo-500/30">
                    <i class="fa-solid fa-clock text-indigo-600"></i>
                </div>
                Bekleyen İşlemler
            </h3>
            <button onclick="toggleNotifications()"
                class="w-8 h-8 rounded-lg bg-black/5 hover:bg-black/10 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-all border border-black/5 hover:border-black/10">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <!-- Content Area -->
        <div class="max-h-[60vh] overflow-y-auto custom-scrollbar p-2" id="notificationList">
            <!-- Loading State -->
            <div class="p-12 text-center text-slate-500 flex flex-col items-center">
                <div class="w-12 h-12 border-4 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin mb-4">
                </div>
                <p class="text-sm font-medium animate-pulse">İşlemler yükleniyor...</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-black/5 bg-white/80 text-center backdrop-blur-md">
            <a href="settings.php"
                class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-indigo-600 hover:text-indigo-700 transition-colors py-2 px-4 rounded-xl hover:bg-indigo-500/10 border border-transparent hover:border-indigo-500/20">
                <span>Tümünü Yönet</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Background Task Runner -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Init badge check
        updateNotificationBadge();
        setInterval(updateNotificationBadge, 30000); // Check every 30s

        function runCron() {
            // console.log('Checking for background tasks...');
            fetch('api/cron.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.message === 'Sync completed') {
                        // console.log('Background Sync: Completed');
                    }
                })
                .catch(err => console.error('Background Sync Error:', err));
        }

        // Run after 5 seconds initially, then every 45 seconds
        // PERFORMANCE DEBUG: Temporarily disabled to check if this is causing lag
        // setTimeout(runCron, 5000);
        // setInterval(runCron, 45000);
    });

    // Mobile Sidebar Toggle
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebar && overlay) {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }
    }

    // Notifications Logic
    function toggleNotifications() {
        const modal = document.getElementById('notificationModal');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            loadNotifications();
        } else {
            modal.classList.add('hidden');
        }
    }

    function updateNotificationBadge() {
        fetch('api/tasks.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const count = data.stats.pending || 0;
                    const badge = document.getElementById('pendingCountBadge');
                    if (count > 0) {
                        badge.textContent = count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            })
            .catch(e => console.error('Badge update error:', e));
    }

    function loadNotifications() {
        const list = document.getElementById('notificationList');
        list.innerHTML = '<div class="p-8 text-center text-gray-500"><i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i><p>Yükleniyor...</p></div>';

        fetch('api/tasks.php?action=list')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    renderNotificationList(data.jobs);
                }
            });
    }

    function renderNotificationList(jobs) {
        const list = document.getElementById('notificationList');
        const pendingJobs = jobs.filter(j => j.status === 'pending');

        if (pendingJobs.length === 0) {
            list.innerHTML = '<div class="p-12 text-center text-slate-500 flex flex-col items-center"><div class="w-16 h-16 rounded-2xl bg-emerald-500/10 flex items-center justify-center mb-4"><i class="fa-regular fa-check-circle text-2xl text-emerald-400"></i></div><p class="font-bold text-sm uppercase tracking-widest opacity-70">Bekleyen işlem yok</p></div>';
            return;
        }

        let html = '<div class="space-y-1">';
        pendingJobs.forEach(job => {
            let jobData = {};
            try { jobData = JSON.parse(job.job_data || '{}'); } catch (e) { }

            let icon = 'fa-clock';
            let color = 'text-slate-400';

            if (job.job_type.includes('whatsapp')) { icon = 'fa-brands fa-whatsapp'; color = 'text-emerald-400'; }
            if (job.job_type.includes('mail')) { icon = 'fa-envelope'; color = 'text-blue-400'; }
            if (job.job_type.includes('backup')) { icon = 'fa-database'; color = 'text-rose-400'; }

            html += `
                <div class="p-4 hover:bg-white/5 transition-all rounded-xl border border-transparent hover:border-white/5 flex gap-4 items-start group">
                    <div class="mt-0.5 w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center border border-white/5 group-hover:border-white/10 transition-colors shrink-0">
                        <i class="${icon} ${color} text-sm"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold text-white leading-tight mb-1 group-hover:text-indigo-300 transition-colors">${job.job_name}</h4>
                        <p class="text-xs text-slate-400 font-medium truncate">${jobData.site_domain || ''} ${jobData.note ? `<span class="opacity-50 mx-1">|</span> ${jobData.note}` : ''}</p>
                        <div class="text-[10px] text-slate-500 font-bold uppercase tracking-wider mt-2 flex items-center gap-1.5 opacity-70">
                            <i class="fa-regular fa-calendar"></i> ${job.scheduled_date} <span class="w-0.5 h-0.5 bg-slate-500 rounded-full"></span> ${job.scheduled_time}
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        list.innerHTML = html;
    }
</script>

<?php include __DIR__ . '/whatsapp_widget.php'; ?>