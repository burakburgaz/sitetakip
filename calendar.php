<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'Takvim - DReklam';
?>
<?php include 'includes/head.php'; ?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet'>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">

<style>
    /* FullCalendar Dark Theme Adjustments */
    .fc {
        --fc-border-color: rgba(255, 255, 255, 0.1);
        --fc-daygrid-event-dot-width: 8px;
        color: #f8fafc;
    }

    .fc .fc-toolbar-title {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 1.5rem !important;
    }

    .fc .fc-button-primary {
        background-color: var(--glass-bg) !important;
        border: 1px solid var(--glass-border) !important;
        color: #f8fafc !important;
        text-transform: capitalize;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .fc .fc-button-primary:hover {
        background-color: var(--glass-hover) !important;
    }

    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background: var(--primary) !important;
        border-color: var(--primary) !important;
    }

    .fc-theme-standard td,
    .fc-theme-standard th {
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    .fc .fc-day-today {
        background: rgba(59, 130, 246, 0.05) !important;
    }

    .fc .fc-col-header-cell-cushion {
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem 0;
    }

    .fc-event {
        border-radius: 6px !important;
        padding: 2px 4px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        border: none !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
    }

    .fc-event:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }

    /* Scrollbar */
    ::-webkit-scrollbar {
        width: 6px;
    }

    ::-webkit-scrollbar-track {
        background: transparent;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .swal2-popup {
        background: #1e293b !important;
        color: #f8fafc !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(20px) !important;
    }
</style>

<body class="bg-gray-900 flex h-screen overflow-hidden">
    <?php include 'includes/bg_blobs.php'; ?>

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="z-10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold logo-font text-white flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-yellow-500/20 rounded-xl flex items-center justify-center border border-yellow-500/30">
                            <i class="fa-solid fa-calendar text-yellow-400 text-xl"></i>
                        </div>
                        Yenileme Takvimi
                    </h2>
                    <p class="text-slate-400 text-xs mt-1 tracking-wider uppercase">Site Yenilemeleri ve Hatırlatmalar
                    </p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto p-8 pt-2">
            <div class="glass-card rounded-[2.5rem] p-8 h-full shadow-2xl relative overflow-hidden">
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
            initCalendar();
        });

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
                                backgroundColor: site.status_color || '#3b82f6',
                                borderColor: site.status_color || '#3b82f6',
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
                                backgroundColor: '#f59e0b',
                                borderColor: '#d97706',
                                extendedProps: { ...reminder, type: 'reminder' }
                            }));
                        }
                    }
                ],
                eventClick: function (info) {
                    const site = info.event.extendedProps;
                    if (site.type === 'site') {
                        Swal.fire({
                            title: site.domain,
                            html:
                                `<div class="text-left space-y-3 p-2">
                                    <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
                                        <p class="text-xs font-bold text-slate-500 uppercase mb-1">Müşteri</p>
                                        <p class="text-white font-semibold">${site.customer_name}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
                                            <p class="text-xs font-bold text-slate-500 uppercase mb-1">Yenileme Tarihi</p>
                                            <p class="text-white font-semibold">${formatDate(site.renewal_date)}</p>
                                        </div>
                                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
                                            <p class="text-xs font-bold text-slate-500 uppercase mb-1">Kalan Gün</p>
                                            <p class="font-black" style="color: ${site.status_color || '#3b82f6'}">${site.days_until} gün</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
                                            <p class="text-xs font-bold text-slate-500 uppercase mb-1">Paket</p>
                                            <p class="text-white font-semibold">${site.package_type}</p>
                                        </div>
                                        <div class="bg-white/5 p-4 rounded-2xl border border-white/10">
                                            <p class="text-xs font-bold text-slate-500 uppercase mb-1">Fiyat</p>
                                            <p class="text-emerald-400 font-bold">${formatCurrency(site.price)}</p>
                                        </div>
                                    </div>
                                </div>`
                            ,
                            showCancelButton: true,
                            confirmButtonText: 'Siteye Git',
                            cancelButtonText: 'Kapat',
                            confirmButtonColor: '#3b82f6'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'sites.php?id=' + site.id;
                            }
                        });
                    } else {
                        Swal.fire({
                            title: 'Hatırlatma',
                            text: site.title + (site.description ? " - " + site.description : ""),
                            icon: 'info',
                            confirmButtonText: 'Tamam'
                        });
                    }
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