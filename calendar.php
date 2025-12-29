<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'Takvim - DReklam';
?>
<?php include 'includes/head.php'; ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet'>

<body class='bg-gray-50 flex h-screen overflow-hidden'>
    <?php include 'includes/sidebar.php'; ?>

    <div class='flex-1 flex flex-col h-screen overflow-hidden'>
        <header class='bg-white shadow-sm z-10 p-4 border-b border-gray-200'>
            <h2 class='text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2'>
                <i class='fa-solid fa-calendar text-yellow-600'></i> Yenileme Takvimi
            </h2>
        </header>

        <main class='flex-1 overflow-auto p-6'>
            <div class='bg-white rounded-xl shadow-lg p-6 h-full'>
                <div id='calendar' class='h-full'></div>
            </div>
        </main>
    </div>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/tr.global.min.js'></script>
    <script>
        let calendar;

        $(document).ready(function () {
            initSidebar();
            initCalendar();
        });

        function initSidebar() {
            $('#toggleSidebar').click(function () {
                const sb = $('#sidebar');
                const isExpanded = sb.hasClass('w-64');
                const texts = $('.sidebar-text');
                const icon = $(this).find('i');
                if (isExpanded) {
                    sb.removeClass('w-64').addClass('w-20');
                    texts.addClass('hidden opacity-0');
                    icon.removeClass('rotate-180');
                } else {
                    sb.removeClass('w-20').addClass('w-64');
                    setTimeout(() => texts.removeClass('hidden opacity-0'), 150);
                    icon.addClass('rotate-180');
                }
            });
        }

        function initCalendar() {
            const calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'tr',
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek'
                },
                height: '100%',
                eventSources: [
                    {
                        url: 'api/sites.php',
                        method: 'GET',
                        extraParams: { action: 'list', filter: 'all' },
                        success: function (content) {
                            return content.data.map(site => ({
                                title: site.domain + ' (' + site.customer_name + ')',
                                start: site.renewal_date,
                                backgroundColor: site.status_color,
                                borderColor: site.status_color,
                                extendedProps: { ...site, type: 'site' }
                            }));
                        }
                    },
                    {
                        url: 'api/reminders.php',
                        method: 'GET',
                        extraParams: { action: 'list', status: 'pending' },
                        success: function (content) {
                            return content.data.map(reminder => ({
                                title: '🔔 ' + reminder.title,
                                start: reminder.reminder_date,
                                backgroundColor: '#f59e0b', // Yellow/Orange
                                borderColor: '#d97706',
                                extendedProps: { ...reminder, type: 'reminder' }
                            }));
                        }
                    }
                ],
                eventClick: function (info) {
                    const site = info.event.extendedProps;
                    Swal.fire({
                        title: site.domain,
                        html:
                            `<div class="text-left space-y-2">
                                <p><strong>Müşteri:</strong> ${site.customer_name}</p>
                                <p><strong>Yenileme Tarihi:</strong> ${formatDate(site.renewal_date)}</p>
                                <p><strong>Kalan Gün:</strong> <span style="color: ${site.status_color}; font-weight: bold;">${site.days_until} gün</span></p>
                                <p><strong>Paket:</strong> <span class="package-${site.package_type.toLowerCase()}">${site.package_type}</span></p>
                                <p><strong>Fiyat:</strong> ${formatCurrency(site.price)}</p>
                            </div>`
                        ,
                        icon: 'info',
                        confirmButtonText: 'Tamam'
                    });
                }
            });

            calendar.render();
        }

        function formatDate(date) {
            if (!date) return '-';
            const d = new Date(date);
            return d.toLocaleDateString('tr-TR');
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(amount);
        }
    </script>
</body>

</html>