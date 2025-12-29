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

<aside id='sidebar'
    class='w-20 bg-gradient-to-b from-indigo-600 to-purple-700 text-white flex flex-col transition-all duration-300 shadow-2xl'>
    <!-- Logo ve Başlık -->
    <div class='p-6 border-b border-indigo-500'>
        <div class='flex items-center gap-3'>
            <div class='bg-white rounded-lg p-2'>
                <i class='fa-solid fa-globe text-indigo-600 text-2xl'></i>
            </div>
            <div class='sidebar-text hidden opacity-0'>
                <h1 class='text-xl font-bold'>DReklam</h1>
                <p class='text-xs text-indigo-200'>Site Takip</p>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Bilgisi -->
    <div class='p-4 border-b border-indigo-500'>
        <div class='flex items-center gap-3'>
            <div class='w-10 h-10 rounded-full bg-indigo-400 flex items-center justify-center'>
                <i class='fa-solid fa-user text-white'></i>
            </div>
            <div class='sidebar-text hidden opacity-0 flex-1'>
                <p class='font-semibold text-sm'><?= htmlspecialchars($_SESSION['name_surname']) ?></p>
                <p class='text-xs text-indigo-200'><?= ucfirst($_SESSION['role']) ?></p>
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
                    class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'dashboard' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                    <i class='fa-solid fa-chart-line text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Dashboard</span>
                </a>
            </li>
            <li>
                <a href='sites.php'
                    class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'sites' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                    <i class='fa-solid fa-globe text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Siteler</span>
                </a>
            </li>
            <li>
                <a href='customers.php'
                    class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'customers' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                    <i class='fa-solid fa-users text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Müşteriler</span>
                </a>
            </li>
            <li>
                <a href='contacts.php'
                    class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'contacts' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                    <i class='fa-solid fa-address-book text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Rehber</span>
                </a>
            </li>
            <li>
                <a href='calendar.php'
                    class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'calendar' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                    <i class='fa-solid fa-calendar text-lg w-5'></i>
                    <span class='sidebar-text hidden opacity-0'>Takvim</span>
                </a>
            </li>

            <?php if ($_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href='statistics.php'
                        class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'statistics' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                        <i class='fa-solid fa-chart-pie text-lg w-5'></i>
                        <span class='sidebar-text hidden opacity-0'>İstatistikler</span>
                    </a>
                </li>

                <li class='pt-4 mt-4 border-t border-indigo-500'>
                    <a href='settings.php'
                        class='flex items-center gap-3 p-3 rounded-lg transition <?= $current_page == 'settings' ? 'bg-white text-indigo-600 shadow-lg' : 'hover:bg-indigo-500' ?>'>
                        <i class='fa-solid fa-cog text-lg w-5'></i>
                        <span class='sidebar-text hidden opacity-0'>Ayarlar</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Alt Kısım -->
    <div class='p-4 border-t border-indigo-500'>
        <!-- Notification Button -->
        <button id="notificationBtn" onclick="toggleNotifications()"
            class="relative w-full flex items-center justify-center gap-2 p-2 rounded-lg bg-indigo-500 hover:bg-indigo-400 transition mb-2">
            <i class="fa-regular fa-clock text-lg"></i>
            <span class="sidebar-text hidden opacity-0 ml-2 text-sm">Bekleyenler</span>
            <span id="pendingCountBadge"
                class="hidden absolute top-0 right-0 transform translate-x-1/3 -translate-y-1/3 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-indigo-600 min-w-[20px] text-center">0</span>
        </button>

        <button id='toggleSidebar'
            class='w-full flex items-center justify-center gap-2 p-2 rounded-lg bg-indigo-500 hover:bg-indigo-400 transition'>
            <i class='fa-solid fa-chevron-right transition-transform'></i>
        </button>
        <a href='logout.php'
            class='mt-2 w-full flex items-center justify-center gap-2 p-2 rounded-lg bg-red-500 hover:bg-red-600 transition'>
            <i class='fa-solid fa-sign-out-alt'></i>
            <span class='sidebar-text hidden opacity-0'>Çıkış</span>
        </a>
    </div>
</aside>

<!-- Notification Modal -->
<div id="notificationModal"
    class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-[2000] flex justify-center items-start pt-20">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden animate-fade-in-down">
        <div class="bg-indigo-600 p-4 flex justify-between items-center text-white">
            <h3 class="font-bold flex items-center gap-2">
                <i class="fa-solid fa-clock"></i> Bekleyen İşlemler
            </h3>
            <button onclick="toggleNotifications()" class="hover:text-gray-200"><i
                    class="fa-solid fa-times"></i></button>
        </div>
        <div class="max-h-[60vh] overflow-y-auto p-0" id="notificationList">
            <!-- Content -->
            <div class="p-8 text-center text-gray-500">
                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Yükleniyor...</p>
            </div>
        </div>
        <div class="p-3 bg-gray-50 border-t text-center">
            <a href="settings.php" class="text-sm text-indigo-600 font-medium hover:underline">Tümünü Yönet</a>
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
            list.innerHTML = '<div class="p-8 text-center text-gray-500"><i class="fa-regular fa-check-circle text-4xl mb-2 text-green-500"></i><p>Bekleyen işlem yok!</p></div>';
            return;
        }

        let html = '<div class="divide-y divide-gray-100">';
        pendingJobs.forEach(job => {
            let jobData = {};
            try { jobData = JSON.parse(job.job_data || '{}'); } catch (e) { }

            let icon = 'fa-clock';
            let color = 'text-gray-500';
            let bgClass = 'bg-white';

            if (job.job_type.includes('whatsapp')) { icon = 'fa-brands fa-whatsapp'; color = 'text-green-600'; }
            if (job.job_type.includes('mail')) { icon = 'fa-envelope'; color = 'text-blue-600'; }
            if (job.job_type.includes('backup')) { icon = 'fa-database'; color = 'text-red-600'; }

            html += `
                <div class="p-4 hover:bg-gray-50 transition flex gap-3 items-start">
                    <div class="mt-1 ${color}"><i class="${icon}"></i></div>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-gray-800">${job.job_name}</h4>
                        <p class="text-xs text-gray-500 mt-1">${jobData.site_domain || ''} ${jobData.note ? '- ' + jobData.note : ''}</p>
                        <div class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                            <i class="fa-regular fa-calendar"></i> ${job.scheduled_date} ${job.scheduled_time}
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