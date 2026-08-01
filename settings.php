<?php
// Settings Page - Tabbed Interface
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_admin();
$page_title = 'Ayarlar - DReklam';
?>
<?php include 'includes/head.php'; ?>

<style>
    :root {
        --tab-active: #3b82f6;
        --tab-inactive: #64748b;
    }

    .tab-btn {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .tab-btn::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--tab-active), transparent);
        transition: all 0.4s ease;
        transform: translateX(-50%);
    }

    .tab-btn.active::after {
        width: 80%;
    }

    .tab-btn:hover {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #f8fafc !important;
        transform: translateY(-2px);
    }

    .tab-btn.active {
        color: var(--tab-active) !important;
        background: rgba(59, 130, 246, 0.1) !important;
    }

    .input-premium {
        background: rgba(15, 23, 42, 0.6) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: white !important;
        border-radius: 1rem !important;
        transition: all 0.3s ease !important;
    }

    .input-premium:focus {
        border-color: var(--tab-active) !important;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
        background: rgba(15, 23, 42, 0.8) !important;
    }

    .table-premium thead th {
        background: rgba(255, 255, 255, 0.02) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.1em !important;
        font-size: 0.7rem !important;
        font-weight: 800 !important;
        color: #64748b !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        padding: 1.25rem 1rem !important;
    }

    .table-premium tbody td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.02) !important;
        padding: 1.25rem 1rem !important;
        vertical-align: middle !important;
    }

    .table-premium tr:hover {
        background: rgba(255, 255, 255, 0.01) !important;
    }

    .btn-gradient-indigo {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%) !important;
        box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4) !important;
    }

    .btn-gradient-blue {
        background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%) !important;
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4) !important;
    }

    .btn-gradient-emerald {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%) !important;
        box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4) !important;
    }
</style>

<body class="bg-gray-900 flex h-screen overflow-hidden">
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="z-10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold logo-font text-white flex items-center gap-3">
                        <div
                            class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center border border-blue-500/30">
                            <i class="fa-solid fa-cog text-blue-400 text-xl"></i>
                        </div>
                        Sistem Ayarları
                    </h2>
                    <p class="text-slate-400 text-xs mt-1 tracking-wider uppercase">Platform Konfigürasyonu ve Yönetimi
                    </p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto p-8 pt-2">
            <div class="max-w-7xl mx-auto w-full pb-10">
                <!-- Tabs -->
                <div
                    class="mb-10 glass-card rounded-[2.5rem] p-3 flex flex-nowrap overflow-x-auto custom-scrollbar no-scrollbar border border-white/5">
                    <button onclick="switchTab('general')"
                        class="tab-btn active px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="general">
                        <i class="fa-solid fa-sliders text-lg"></i> Genel
                    </button>
                    <button onclick="switchTab('tasks')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="tasks">
                        <i class="fa-solid fa-clock text-lg"></i> Görevler
                    </button>
                    <button onclick="switchTab('whatsapp')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="whatsapp">
                        <i class="fa-brands fa-whatsapp text-lg"></i> WhatsApp
                    </button>
                    <button onclick="switchTab('smtp')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="smtp">
                        <i class="fa-solid fa-envelope text-lg"></i> SMTP
                    </button>
                    <button onclick="switchTab('history')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="history">
                        <i class="fa-solid fa-history text-lg"></i> Geçmiş
                    </button>
                    <button onclick="switchTab('api')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="api">
                        <i class="fa-solid fa-code text-lg"></i> API
                    </button>
                    <button onclick="switchTab('access')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="access">
                        <i class="fa-solid fa-shield-halved text-lg"></i> Erişim
                    </button>

                    <button onclick="switchTab('backup')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="backup">
                        <i class="fa-solid fa-database text-lg"></i> Yedek
                    </button>
                    <button onclick="switchTab('users')"
                        class="tab-btn px-8 py-5 text-sm font-bold flex items-center gap-3 rounded-2xl whitespace-nowrap"
                        data-tab="users">
                        <i class="fa-solid fa-users text-lg"></i> Kullanıcılar
                    </button>
                </div>

                <!-- General Tab -->
                <div id="general-tab" class="tab-content" style="display: block;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="glass-card rounded-[2.5rem] p-8">
                            <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center border border-purple-500/20">
                                    <i class="fa-solid fa-tags text-purple-400"></i>
                                </div>
                                Paket Fiyatları
                            </h3>
                            <form id="priceForm" class="space-y-6">
                                <div class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">PRO
                                            Paket Fiyatı (₺)</label>
                                        <input type="number" name="package_pro_price" id="proPriceInput" step="0.01"
                                            class="w-full input-premium p-4">
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">BASIC
                                            Paket Fiyatı (₺)</label>
                                        <input type="number" name="package_basic_price" id="basicPriceInput" step="0.01"
                                            class="w-full input-premium p-4">
                                    </div>
                                </div>
                                <button type="submit"
                                    class="w-full py-4 rounded-2xl btn-gradient-indigo font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i>Fiyatları Kaydet
                                </button>
                            </form>
                        </div>

                        <div class="glass-card rounded-[2.5rem] p-8">
                            <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20">
                                    <i class="fa-solid fa-building text-blue-400"></i>
                                </div>
                                Firma Bilgileri
                            </h3>
                            <form id="companyForm" class="space-y-4">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Firma
                                        Adı</label>
                                    <input type="text" name="company_name" id="companyNameInput"
                                        class="w-full input-premium p-4">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Telefon</label>
                                    <input type="text" name="company_phone" id="companyPhoneInput"
                                        class="w-full input-premium p-4">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">E-posta</label>
                                    <input type="email" name="company_email" id="companyEmailInput"
                                        class="w-full input-premium p-4">
                                </div>
                                <button type="submit"
                                    class="w-full py-4 rounded-2xl btn-gradient-blue font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i>Firma Bilgilerini Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>


                <!-- Tasks Tab (Görevler) -->
                <div id="tasks-tab" class="tab-content hidden space-y-8">
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20">
                                    <i class="fa-solid fa-clock text-blue-400"></i>
                                </div>
                                Planlı Görevler
                            </h3>
                            <button onclick="loadCronJobs()"
                                class="text-sm font-bold text-blue-400 hover:text-blue-300 flex items-center gap-2 transition-colors">
                                <i class="fa-solid fa-sync"></i>Yenile
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                            <div
                                class="bg-blue-500/5 p-6 rounded-[1.5rem] border border-blue-500/10 hover:bg-blue-500/10 transition-all">
                                <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-1">Bekleyen
                                </p>
                                <p class="text-3xl font-black text-white" id="pendingJobsCount">0</p>
                            </div>
                            <div
                                class="bg-emerald-500/5 p-6 rounded-[1.5rem] border border-emerald-500/10 hover:bg-emerald-500/10 transition-all">
                                <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-1">
                                    Tamamlanan</p>
                                <p class="text-3xl font-black text-white" id="completedJobsCount">0</p>
                            </div>
                            <div
                                class="bg-red-500/5 p-6 rounded-[1.5rem] border border-red-500/10 hover:bg-red-500/10 transition-all">
                                <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1">Başarısız
                                </p>
                                <p class="text-3xl font-black text-white" id="failedJobsCount">0</p>
                            </div>
                            <div
                                class="bg-purple-500/5 p-6 rounded-[1.5rem] border border-purple-500/10 hover:bg-purple-500/10 transition-all">
                                <p class="text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-1">
                                    Tekrarlayan</p>
                                <p class="text-3xl font-black text-white" id="recurringJobsCount">0</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm table-premium">
                                <thead>
                                    <tr>
                                        <th class="p-4 text-left">Görev Detayı</th>
                                        <th class="p-4 text-left">Tür</th>
                                        <th class="p-4 text-left">Zaman</th>
                                        <th class="p-4 text-left">Durum</th>
                                        <th class="p-4 text-left">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="cronJobsList" class="text-slate-300">
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-slate-500 italic">Yükleniyor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex items-center justify-between mb-8 border-b border-white/10 pb-6">
                            <div class="flex items-center gap-6">
                                <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20">
                                        <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                                    </div>
                                    Bekleyen Mesajlar
                                </h3>
                                <div class="flex gap-2 p-1 bg-white/5 rounded-full border border-white/10">
                                    <button onclick="filterWhatsAppQueue('pending')"
                                        class="wa-filter-btn active px-4 py-1.5 text-[10px] font-black uppercase tracking-tighter rounded-full transition"
                                        data-filter="pending">Bekleyen</button>
                                    <button onclick="filterWhatsAppQueue('all')"
                                        class="wa-filter-btn px-4 py-1.5 text-[10px] font-black uppercase tracking-tighter rounded-full transition text-slate-500"
                                        data-filter="all">Tümü</button>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <button onclick="clearWhatsAppLogs()" id="clearLogsBtn"
                                    class="text-xs font-bold text-red-400 hover:text-red-300 hidden transition-colors">
                                    <i class="fa-solid fa-trash mr-2"></i>Logları Temizle
                                </button>
                                <button onclick="loadWhatsAppQueue()"
                                    class="text-sm font-bold text-emerald-400 hover:text-emerald-300 transition-colors">
                                    <i class="fa-solid fa-sync mr-2"></i>Yenile
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm table-premium">
                                <thead>
                                    <tr>
                                        <th class="p-4 text-left">Telefon</th>
                                        <th class="p-4 text-left">Mesaj</th>
                                        <th class="p-4 text-left">Zamanlanma</th>
                                        <th class="p-4 text-left">Durum</th>
                                        <th class="p-4 text-left">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="whatsappQueueList" class="text-slate-300">
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-slate-500 italic">Yükleniyor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <!-- WhatsApp Tab -->
                <div id="whatsapp-tab" class="tab-content hidden space-y-8">
                    <!-- Gelen Kutusu (Inbox) -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20">
                                    <i class="fa-solid fa-inbox text-emerald-400"></i>
                                </div>
                                Sohbet Geçmişi & Rehber
                            </h3>
                            <div class="flex gap-4">
                                <button onclick="syncChatsSettings()" id="syncBtnSettings"
                                    class="text-sm font-bold text-blue-400 hover:text-blue-300 flex items-center gap-2 transition-colors">
                                    <i class="fa-solid fa-sync"></i>Senkronize Et
                                </button>
                                <button onclick="loadWhatsAppContacts()"
                                    class="text-sm font-bold text-slate-400 hover:text-slate-300 flex items-center gap-2 transition-colors">
                                    <i class="fa-solid fa-refresh"></i>Yenile
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto max-h-[500px] overflow-y-auto custom-scrollbar pr-2">
                            <table class="w-full text-sm text-left table-premium">
                                <thead class="sticky top-0 z-20">
                                    <tr>
                                        <th class="p-4">Kişi / Grup</th>
                                        <th class="p-4">Numara</th>
                                        <th class="p-4">Son İşlem</th>
                                        <th class="p-4 text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="waContactsList" class="text-slate-300">
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-500 italic">Yükleniyor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mesaj Şablonları -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20">
                                    <i class="fa-solid fa-comment-dots text-indigo-400"></i>
                                </div>
                                Mesaj Şablonları
                            </h3>
                            <button onclick="openTemplateModal()"
                                class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-sm font-bold transition shadow-lg shadow-indigo-500/20">
                                <i class="fa-solid fa-plus mr-2"></i> Yeni Şablon
                            </button>
                        </div>
                        <div class="mb-6 bg-blue-500/5 border border-blue-500/10 p-6 rounded-2xl">
                            <p class="text-xs font-black text-blue-400 uppercase tracking-[0.2em] mb-3">Kullanılabilir
                                Kısa Kodlar</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-[10px] font-bold text-slate-300">[ADI
                                    SOYADI]</span>
                                <span
                                    class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-[10px] font-bold text-slate-300">[SITE]</span>
                                <span
                                    class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-[10px] font-bold text-slate-300">[TARIH]</span>
                                <span
                                    class="px-3 py-1 bg-white/5 border border-white/10 rounded-lg text-[10px] font-bold text-slate-300">[PAKET]</span>
                            </div>
                        </div>
                        <div id="templatesList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Templates will be loaded here -->
                        </div>
                    </div>

                    <!-- Admin Daily WhatsApp Reminder -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <h3
                            class="text-xl font-bold text-white mb-8 border-b border-white/10 pb-6 flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20">
                                <i class="fa-solid fa-user-shield text-emerald-400"></i>
                            </div>
                            Yönetici Günlük Hatırlatma
                        </h3>
                        <form id="adminWaForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Yönetici
                                        Telefon Numarası</label>
                                    <input type="text" id="dailyWaPhone" name="daily_whatsapp_phone"
                                        class="w-full input-premium p-4" placeholder="905xxxxxxxxx">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Gönderim
                                        Saati</label>
                                    <input type="time" id="dailyWaTime" name="daily_whatsapp_time"
                                        class="w-full input-premium p-4">
                                </div>
                            </div>
                            <div class="flex justify-end gap-4">
                                <button type="button" onclick="sendAdminWaNow()"
                                    class="px-8 py-4 bg-white/5 hover:bg-white/10 text-white rounded-2xl font-bold border border-white/10 transition">
                                    <i class="fa-solid fa-paper-plane mr-2"></i>Şimdi Gönder
                                </button>
                                <button type="submit"
                                    class="px-8 py-4 rounded-2xl btn-gradient-emerald font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i>Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SMTP Tab -->
                <div id="smtp-tab" class="tab-content hidden space-y-8">
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <h3
                            class="text-xl font-bold text-white mb-8 border-b border-white/10 pb-6 flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20">
                                <i class="fa-solid fa-envelope text-blue-400"></i>
                            </div>
                            SMTP Ayarları
                        </h3>
                        <form id="smtpForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">SMTP
                                        Host</label>
                                    <input type="text" id="smtpHost" name="smtp_host" class="w-full input-premium p-4">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Port</label>
                                    <input type="number" id="smtpPort" name="smtp_port"
                                        class="w-full input-premium p-4">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">E-posta</label>
                                    <input type="email" id="smtpUser" name="smtp_user" class="w-full input-premium p-4">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Şifre</label>
                                    <input type="password" id="smtpPass" name="smtp_pass"
                                        class="w-full input-premium p-4">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Şifreleme
                                        (SSL/TLS)</label>
                                    <select id="smtpSecure" name="smtp_secure"
                                        class="w-full input-premium p-4 bg-gray-900 border-none">
                                        <option value="ssl">SSL</option>
                                        <option value="tls">TLS</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Gönderen
                                        Adı</label>
                                    <input type="text" id="smtpFromName" name="smtp_from_name"
                                        class="w-full input-premium p-4">
                                </div>
                            </div>
                            <div class="flex justify-end gap-4">
                                <button type="button" onclick="testSmtp()"
                                    class="px-8 py-4 bg-white/5 hover:bg-white/10 text-white rounded-2xl font-bold border border-white/10 transition">
                                    <i class="fa-solid fa-vial mr-2"></i>Test Maili Gönder
                                </button>
                                <button type="submit"
                                    class="px-8 py-4 rounded-2xl btn-gradient-blue font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i>Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- History Tab -->
                <div id="history-tab" class="tab-content hidden">
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-orange-500/10 rounded-xl flex items-center justify-center border border-orange-500/20">
                                    <i class="fa-solid fa-history text-orange-400"></i>
                                </div>
                                İşlem Geçmişi
                            </h3>
                            <div class="flex gap-4">
                                <button onclick="loadLogs()"
                                    class="text-sm font-bold text-orange-400 hover:text-orange-300 transition-colors">
                                    <i class="fa-solid fa-sync mr-2"></i>Yenile
                                </button>
                                <button onclick="clearHistory()"
                                    class="text-sm font-bold text-rose-500 hover:text-rose-400 transition-colors">
                                    <i class="fa-solid fa-trash-can mr-2"></i>Geçmişi Temizle
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto max-h-[600px] overflow-y-auto custom-scrollbar pr-2">
                            <table class="w-full text-sm table-premium">
                                <thead class="sticky top-0 z-20">
                                    <tr>
                                        <th class="p-4">Tarih</th>
                                        <th class="p-4">Kullanıcı</th>
                                        <th class="p-4">İşlem</th>
                                        <th class="p-4">Detay</th>
                                        <th class="p-4">IP</th>
                                    </tr>
                                </thead>
                                <tbody id="logsList" class="text-slate-300">
                                    <tr>
                                        <td colspan="5" class="p-10 text-center text-slate-500 italic">Yükleniyor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <!-- API Tab -->
                <div id="api-tab" class="tab-content hidden space-y-8">
                    <!-- Hostinger API -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20">
                                    <i class="fa-solid fa-server text-indigo-400"></i>
                                </div>
                                Hostinger API Ayarları
                            </h3>
                            <button onclick="syncHostinger()"
                                class="px-8 py-3 btn-gradient-indigo text-white rounded-2xl text-sm font-bold transition shadow-lg shadow-indigo-500/20">
                                <i class="fa-solid fa-sync mr-2"></i> Siteleri Senkronize Et
                            </button>
                        </div>
                        <form id="apiForm" class="space-y-6">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Hostinger
                                    API Key</label>
                                <input type="password" name="hostinger_api_key" id="hostingerApiKey"
                                    class="w-full input-premium p-4" placeholder="••••••••••••••••">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-8 py-4 rounded-2xl btn-gradient-indigo font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i> API Anahtarını Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Evolution API (WhatsApp) -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20">
                                    <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                                </div>
                                Evolution API (WhatsApp)
                            </h3>
                            <div class="flex gap-4">
                                <button onclick="checkEvolutionStatus()"
                                    class="text-sm font-bold text-slate-400 hover:text-slate-300 flex items-center gap-2 transition-colors">
                                    <i class="fa-solid fa-heart-pulse"></i>Durum Kontrolü
                                </button>
                                <button onclick="testEvolution()"
                                    class="text-sm font-bold text-emerald-400 hover:text-emerald-300 flex items-center gap-2 transition-colors">
                                    <i class="fa-solid fa-paper-plane"></i>Test Gönder
                                </button>
                            </div>
                        </div>
                        <form id="evolutionApiForm" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">API
                                        URL</label>
                                    <input type="text" name="evolution_api_url" id="evoApiUrl"
                                        class="w-full input-premium p-4" placeholder="https://api.yourdomain.com">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">API
                                        Key</label>
                                    <input type="password" name="evolution_api_key" id="evoApiKey"
                                        class="w-full input-premium p-4" placeholder="••••••••••••••••">
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Instance
                                        Name</label>
                                    <input type="text" name="evolution_instance_name" id="evoInstance"
                                        class="w-full input-premium p-4" placeholder="DREKLAM">
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-8 py-4 rounded-2xl btn-gradient-emerald font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i> API Bilgilerini Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Webhook Settings -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20">
                                    <i class="fa-solid fa-link text-blue-400"></i>
                                </div>
                                Webhook Ayarları
                            </h3>
                            <button onclick="registerWebhook()"
                                class="px-8 py-3 btn-gradient-blue text-white rounded-2xl text-sm font-bold transition shadow-lg shadow-blue-500/20">
                                <i class="fa-solid fa-bolt mr-2"></i> Webhook Kaydet (Evolution API)
                            </button>
                        </div>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4 bg-white/5 border border-white/10 p-6 rounded-2xl">
                                <div class="flex-1">
                                    <label
                                        class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Sizin
                                        Webhook URL'niz</label>
                                    <input type="text" id="webhookUrlInput" readonly
                                        class="w-full input-premium p-4 bg-transparent border-none text-slate-400 font-mono text-xs"
                                        value="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/whatsapp_webhook.php'; ?>">
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="saveWebhookUrl()"
                                        class="mt-6 px-4 py-2 bg-blue-600/10 text-blue-400 rounded-xl hover:bg-blue-600/20 transition border border-blue-500/20 text-xs font-bold">
                                        KAYDET
                                    </button>
                                    <button onclick="copyWebhookUrl()"
                                        class="mt-6 p-4 bg-blue-500/10 text-blue-400 rounded-xl hover:bg-blue-500/20 transition">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-blue-500/5 p-6 rounded-2xl border border-blue-500/10">
                                    <h4 class="text-white font-bold mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-circle-check text-emerald-400"></i> Local Webhook
                                    </h4>
                                    <p class="text-sm text-slate-400">Webhook dosyası sisteminizde mevcut ve
                                        erişilebilir
                                        durumda.</p>
                                </div>
                                <div class="bg-indigo-500/5 p-6 rounded-2xl border border-indigo-500/10">
                                    <h4 class="text-white font-bold mb-2 flex items-center gap-2">
                                        <i class="fa-solid fa-circle-info text-blue-400"></i> Evolution Status
                                    </h4>
                                    <p class="text-sm text-slate-400">Instance üzerinde kayıtlı webhook durumunu butona
                                        basarak kontrol edebilirsiniz.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
                                <button type="button" onclick="checkWebhookStatus()"
                                    class="bg-blue-600/10 text-blue-400 px-4 py-3 rounded-xl hover:bg-blue-600/20 font-bold flex items-center justify-center gap-2 border border-blue-400/20 transition">
                                    <i class="fa-solid fa-search"></i>
                                    Durumu Kontrol Et
                                </button>
                                <button type="button" onclick="testWebhook()"
                                    class="bg-purple-600/10 text-purple-400 px-4 py-3 rounded-xl hover:bg-purple-600/20 font-bold flex items-center justify-center gap-2 border border-purple-400/20 transition">
                                    <i class="fa-solid fa-flask"></i>
                                    Test Et
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Google Sheets Entegrasyonu (Apps Script) -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center border border-emerald-500/20">
                                    <i class="fa-solid fa-file-excel text-emerald-400"></i>
                                </div>
                                Google Sheets Entegrasyonu (Apps Script)
                            </h3>
                            <button onclick="exportToGoogleSheets()"
                                class="px-8 py-3 btn-gradient-emerald text-white rounded-2xl text-sm font-bold transition shadow-lg shadow-emerald-500/20">
                                <i class="fa-solid fa-file-export mr-2"></i> Tüm Verileri Şimdi Aktar
                            </button>
                        </div>

                        <form id="googleSheetsForm" class="space-y-6">
                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Google
                                    Apps Script Web App URL</label>
                                <input type="url" name="google_sheets_webhook_url" id="googleSheetsWebhookUrl"
                                    class="w-full input-premium p-4"
                                    placeholder="https://script.google.com/macros/s/.../exec">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="px-8 py-4 rounded-2xl btn-gradient-emerald font-bold text-white">
                                    <i class="fa-solid fa-save mr-2"></i> URL Kaydet
                                </button>
                            </div>
                        </form>

                        <div class="mt-10 pt-10 border-t border-white/10">
                            <h4 class="text-lg font-bold text-white mb-6">Apps Script Kurulum Rehberi</h4>
                            <div class="space-y-4">
                                <div class="bg-white/5 p-6 rounded-2xl border border-white/10 flex gap-4">
                                    <div
                                        class="w-8 h-8 bg-blue-500 text-white rounded-lg flex items-center justify-center font-bold flex-shrink-0">
                                        1</div>
                                    <p class="text-slate-300 text-sm">Google Sheets dökümanınızda <span
                                            class="text-white font-bold">Uzantılar > Apps Script</span> kısmına
                                        gidin.</p>
                                </div>
                                <div class="bg-white/5 p-6 rounded-2xl border border-white/10 flex gap-4">
                                    <div
                                        class="w-8 h-8 bg-blue-500 text-white rounded-lg flex items-center justify-center font-bold flex-shrink-0">
                                        2</div>
                                    <div class="flex-1">
                                        <p class="text-slate-300 text-sm mb-4">Aşağıdaki butona basarak kodu kopyalayın
                                            ve Script editörüne yapıştırın.</p>
                                        <button onclick="copyScriptCode()"
                                            class="px-6 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition border border-white/10">
                                            <i class="fa-solid fa-copy mr-2"></i> Script Kodunu Kopyala
                                        </button>
                                    </div>
                                </div>
                                <div class="bg-white/5 p-6 rounded-2xl border border-white/10 flex gap-4">
                                    <div
                                        class="w-8 h-8 bg-blue-500 text-white rounded-lg flex items-center justify-center font-bold flex-shrink-0">
                                        3</div>
                                    <p class="text-slate-300 text-sm">Script'i <span class="text-white font-bold">Yeni
                                            dağıtım (Web Uygulaması)</span> olarak yayınlayın ve URL'yi yukarıya
                                        yapıştırın.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- API TAB BİTTİ -->

                <!-- Access Tab -->
                <div id="access-tab" class="tab-content hidden space-y-8">
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-red-500/10 rounded-xl flex items-center justify-center border border-red-500/20">
                                    <i class="fa-solid fa-shield-halved text-red-400"></i>
                                </div>
                                IP Kısıtlaması (Whitelist)
                            </h3>
                            <div class="flex items-center gap-4">
                                <span class="text-sm font-bold text-slate-500">Kısıtlama:</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="ipRestrictionToggle" class="sr-only peer"
                                        onchange="toggleIpRestriction()">
                                    <div
                                        class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500">
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="col-span-2">
                                <label
                                    class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Yeni
                                    IP Adresi Ekle</label>
                                <div class="flex gap-4">
                                    <input type="text" id="newIpInput" class="flex-1 input-premium p-4"
                                        placeholder="0.0.0.0">
                                    <button onclick="addIpAddress()"
                                        class="px-8 py-4 btn-gradient-blue text-white rounded-2xl font-bold transition">
                                        Ekle
                                    </button>
                                </div>
                            </div>
                            <div class="bg-red-500/5 p-6 rounded-2xl border border-red-500/10">
                                <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1">Sizin IP
                                    Adresiniz</p>
                                <p class="text-xl font-mono font-black text-white">
                                    <?php echo $_SERVER['REMOTE_ADDR']; ?>
                                </p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm table-premium">
                                <thead>
                                    <tr>
                                        <th class="p-4 text-left">IP Adresi</th>
                                        <th class="p-4 text-left">Eklenme Tarihi</th>
                                        <th class="p-4 text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="ipWhitelist" class="text-slate-300">
                                    <tr>
                                        <td colspan="3" class="p-10 text-center text-slate-500 italic">Yükleniyor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Access Logs -->
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20">
                                    <i class="fa-solid fa-list-ul text-indigo-400"></i>
                                </div>
                                Son API Erişimleri
                            </h3>
                            <div class="flex gap-4">
                                <button onclick="loadApiAccessLogs()"
                                    class="text-xs font-bold text-slate-400 hover:text-white transition-colors">
                                    <i class="fa-solid fa-sync mr-2"></i>Yenile
                                </button>
                                <button onclick="clearApiLogs()"
                                    class="text-xs font-bold text-rose-400 hover:text-rose-300 transition-colors">
                                    <i class="fa-solid fa-trash-can mr-2"></i>Logları Temizle
                                </button>
                            </div>
                        </div>
                        <div id="apiAccessLogs" class="space-y-3 max-h-[500px] overflow-y-auto no-scrollbar pr-2">
                            <div class="p-10 text-center text-slate-500 italic">Yükleniyor...</div>
                        </div>
                    </div>
                </div>


                <!-- BACKUP TAB -->
                <div id="backup-tab" class="tab-content hidden space-y-8">
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-rose-500/10 rounded-xl flex items-center justify-center border border-rose-500/20">
                                    <i class="fa-solid fa-database text-rose-400"></i>
                                </div>
                                Veritabanı Yedekleme & Geri Yükleme
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div
                                class="bg-white/5 p-8 rounded-[2rem] border border-white/10 hover:bg-white/10 transition-all group">
                                <div
                                    class="w-14 h-14 bg-indigo-500/20 rounded-2xl flex items-center justify-center mb-6 border border-indigo-500/30 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-download text-indigo-400 text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Yedek Al</h3>
                                <p class="text-sm text-slate-400 mb-8 leading-relaxed">Mevcut veritabanının bir
                                    kopyasını SQLITE formatında güvenli bir şekilde bilgisayarınıza indirin.</p>
                                <a href="api/backup.php?action=download"
                                    class="inline-flex items-center px-10 py-4 btn-gradient-indigo text-white rounded-2xl font-bold transition shadow-lg shadow-indigo-500/20">
                                    <i class="fa-solid fa-cloud-arrow-down mr-2"></i> Yedek Dosyasını İndir
                                </a>
                            </div>
                            <div
                                class="bg-rose-500/5 p-8 rounded-[2rem] border border-rose-500/10 hover:bg-rose-500/10 transition-all group">
                                <div
                                    class="w-14 h-14 bg-rose-500/20 rounded-2xl flex items-center justify-center mb-6 border border-rose-500/30 group-hover:scale-110 transition-transform">
                                    <i class="fa-solid fa-upload text-rose-400 text-2xl"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Geri Yükle</h3>
                                <p class="text-sm text-slate-400 mb-8 leading-relaxed">Daha önce aldığınız bir yedek
                                    dosyasını (.sqlite) yükleyerek verilerinizi anında geri getirin.</p>
                                <form id="restoreForm" class="space-y-4">
                                    <div class="relative">
                                        <input type="file" name="backup_file" id="backup_file" accept=".sqlite" required
                                            class="hidden">
                                        <label for="backup_file"
                                            class="w-full flex items-center justify-center p-4 border-2 border-dashed border-rose-500/30 rounded-2xl cursor-pointer hover:border-rose-500/50 transition-colors text-slate-400 text-sm font-medium">
                                            <i class="fa-solid fa-file-export mr-2"></i> Dosya Seçin
                                        </label>
                                    </div>
                                    <input type="hidden" name="action" value="restore">
                                    <button type="submit"
                                        class="w-full px-6 py-4 bg-rose-600 text-white rounded-2xl font-bold hover:bg-rose-700 transition shadow-lg shadow-rose-500/20 flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-undo"></i> Geri Yüklemeyi Başlat
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USERS TAB -->
                <div id="users-tab" class="tab-content hidden space-y-8">
                    <div class="glass-card rounded-[2.5rem] p-8">
                        <div class="flex justify-between items-center mb-8 border-b border-white/10 pb-6">
                            <h3 class="text-xl font-bold text-white flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center border border-purple-500/20">
                                    <i class="fa-solid fa-users text-purple-400"></i>
                                </div>
                                Kullanıcı Yönetimi
                            </h3>
                            <button onclick="openUserModal()"
                                class="px-8 py-3 btn-gradient-indigo text-white rounded-2xl text-sm font-bold transition shadow-lg shadow-indigo-500/20">
                                <i class="fa-solid fa-user-plus mr-2"></i> Yeni Kullanıcı Ekle
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm table-premium">
                                <thead>
                                    <tr>
                                        <th class="p-4 text-left">Kullanıcı</th>
                                        <th class="p-4 text-left">Yetki</th>
                                        <th class="p-4 text-left">Son Giriş</th>
                                        <th class="p-4 text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="usersList" class="text-slate-300">
                                    <tr>
                                        <td colspan="4" class="p-10 text-center text-slate-500 italic">Yükleniyor...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <!-- max-w-6xl mx-auto BİTTİ -->
        </main>
    </div>

    <!-- User Modal -->
    <div id="userModal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 transition-all duration-300 opacity-0">
        <div
            class="glass-card w-full max-w-xl rounded-[2.5rem] overflow-hidden border border-white/10 shadow-2xl transform scale-95 transition-all duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center">
                <h3 class="text-2xl font-bold text-white logo-font flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20">
                        <i class="fa-solid fa-user-gear text-indigo-400"></i>
                    </div>
                    <span id="userModalTitle">Yeni Kullanıcı</span>
                </h3>
                <button onclick="closeUserModal()"
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white transition-all">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="p-8 overflow-y-auto max-h-[70vh] custom-scrollbar">
                <form id="userForm" class="space-y-6">
                    <input type="hidden" id="userId" name="id">
                    <input type="hidden" name="action" id="userFormAction" value="create">

                    <div id="usernameGroup">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Kullanıcı
                            Adı *</label>
                        <input type="text" name="username" id="userUsername" required class="w-full input-premium p-4"
                            placeholder="Kullanıcı adı girin...">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Tam Ad
                            *</label>
                        <input type="text" name="name_surname" id="userNameSurname" required
                            class="w-full input-premium p-4" placeholder="Ad Soyad girin...">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">E-posta</label>
                            <input type="email" name="email" id="userEmail" class="w-full input-premium p-4"
                                placeholder="email@adresi.com">
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Telefon</label>
                            <input type="text" name="phone" id="userPhone" class="w-full input-premium p-4"
                                placeholder="5xxxxxxxxx">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Rol
                            *</label>
                        <select name="role" id="userRole" required class="w-full input-premium p-4 bg-slate-900">
                            <option value="admin">Admin (Tam Yetki)</option>
                            <option value="user">Kullanıcı (Kısıtlı Yetki)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div
                            class="bg-white/5 p-4 rounded-2xl border border-white/10 flex flex-col items-center gap-3 hover:bg-white/10 transition-colors">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Aktif</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" id="userIsActive" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-emerald-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white">
                                </div>
                            </label>
                        </div>
                        <div
                            class="bg-white/5 p-4 rounded-2xl border border-white/10 flex flex-col items-center gap-3 hover:bg-white/10 transition-colors">
                            <span
                                class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">WhatsApp</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="can_send_whatsapp" id="userCanWa" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-blue-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white">
                                </div>
                            </label>
                        </div>
                        <div
                            class="bg-white/5 p-4 rounded-2xl border border-white/10 flex flex-col items-center gap-3 hover:bg-white/10 transition-colors">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">E-posta</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="can_send_email" id="userCanEmail" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-purple-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white">
                                </div>
                            </label>
                        </div>
                        <div
                            class="bg-white/5 p-4 rounded-2xl border border-white/10 flex flex-col items-center gap-3 hover:bg-white/10 transition-colors">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">2FA</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="wa_2fa_enabled" id="user2FA" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-white/10 rounded-full peer peer-checked:bg-orange-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white">
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Şifre <span
                                id="passwordHint" class="lowercase font-normal text-slate-600"></span></label>
                        <input type="password" name="password" id="userPassword" class="w-full input-premium p-4"
                            placeholder="••••••••">
                    </div>

                    <div class="pt-4 flex gap-4">
                        <button type="button" onclick="closeUserModal()"
                            class="flex-1 py-4 bg-white/5 hover:bg-white/10 text-white rounded-2xl font-bold transition border border-white/10">İptal</button>
                        <button type="submit"
                            class="flex-[2] py-4 rounded-2xl btn-gradient-indigo text-white font-bold shadow-lg shadow-indigo-500/20">
                            <i class="fa-solid fa-save mr-2"></i>Kullanıcıyı Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div id="templateModal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 transition-all duration-300 opacity-0">
        <div
            class="glass-card w-full max-w-lg rounded-[2.5rem] overflow-hidden border border-white/10 shadow-2xl transform scale-95 transition-all duration-300">
            <div class="p-8 border-b border-white/10 flex justify-between items-center">
                <h3 class="text-2xl font-bold text-white logo-font flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20">
                        <i class="fa-solid fa-message text-blue-400"></i>
                    </div>
                    <span id="modalTitle">Şablon Düzenle</span>
                </h3>
                <button onclick="closeTemplateModal()"
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white transition-all">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="templateForm" class="p-8 space-y-6">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="templateId">
                <input type="hidden" name="type" value="whatsapp">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Şablon
                        Başlığı</label>
                    <input type="text" name="title" id="templateTitle" required class="w-full input-premium p-4"
                        placeholder="Örn: Hatırlatma Mesajı">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Mesaj
                        İçeriği</label>
                    <textarea name="message" id="templateMessage" rows="6" required
                        class="w-full input-premium p-4 font-mono text-sm leading-relaxed"
                        placeholder="Mesaj içeriğini buraya yazın..."></textarea>
                    <p class="mt-2 text-[10px] text-slate-500 italic">Değişkenler: {domain}, {date}, {owner}</p>
                </div>
                <div class="pt-4 flex gap-4">
                    <button type="button" onclick="closeTemplateModal()"
                        class="flex-1 py-4 bg-white/5 hover:bg-white/10 text-white rounded-2xl font-bold transition border border-white/10">İptal</button>
                    <button type="submit"
                        class="flex-[2] py-4 rounded-2xl btn-gradient-blue text-white font-bold shadow-lg shadow-blue-500/20">
                        <i class="fa-solid fa-save mr-2"></i> Şablonu Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/webhook-manager.js"></script>
    <script>
        $(document).ready(function () {
            initSidebar();
            loadSettings();
            loadTemplates();
            loadLogs();
            loadApiSettings();
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

        function switchTab(tabName) {
            // Tüm tab'ları gizle
            $('.tab-content').addClass('hidden').css('display', 'none');

            // Seçili tab'ı göster
            const $selectedTab = $('#' + tabName + '-tab');
            if ($selectedTab.length) {
                $selectedTab.removeClass('hidden').css('display', 'block');

                // Animasyon efekti için
                $selectedTab.addClass('animate-in fade-in slide-in-from-bottom-4 duration-500');
            }

            // Button styling
            $('.tab-btn').removeClass('active');
            $(`.tab-btn[data-tab='${tabName}']`).addClass('active');

            // Sync Mobile Select (if exists)
            $('#settingsTabSelect').val(tabName);

            if (tabName === 'history') loadLogs();
            if (tabName === 'users') loadUsers();
            if (tabName === 'tasks') loadCronJobs();
            if (tabName === 'whatsapp') {
                loadWhatsAppQueue();
                loadWhatsAppContacts();
            }
            if (tabName === 'access') {
                loadIpWhitelist();
                loadApiAccessLogs();
            }
            if (tabName === 'api') {
                loadApiSettings();
            }
        }

        function loadSettings() {
            $.get('api/settings.php', function (data) {
                // General
                $('#proPriceInput').val(data.package_pro_price || 5000);
                $('#basicPriceInput').val(data.package_basic_price || 2500);
                $('#companyNameInput').val(data.company_name || '');
                $('#companyPhoneInput').val(data.company_phone || '');
                $('#companyEmailInput').val(data.company_email || '');

                // WhatsApp
                $('#reminderDaysInput').val(data.whatsapp_reminder_days || '30,15,7,1');

                // SMTP
                $('#smtpHost').val(data.smtp_host || '');
                $('#smtpPort').val(data.smtp_port || '');
                $('#smtpUser').val(data.smtp_user || '');
                $('#smtpPass').val(data.smtp_pass || '');
                $('#smtpFromEmail').val(data.smtp_from_email || '');
                $('#smtpFromName').val(data.smtp_from_name || '');
                $('#smtpSecurity').val(data.smtp_security || 'tls');
                $('#dailyReminderTime').val(data.daily_reminder_time || '');
                $('#dailyReminderEmail').val(data.daily_reminder_email || '');
            });
        }

        // Generic form submit handler
        $('form').not('#templateForm').not('#restoreForm').submit(function (e) {
            e.preventDefault();
            $.post('api/settings.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500 });
                }
            });
        });

        // Templates Logic
        function loadTemplates() {
            $.get('api/templates.php', function (res) {
                if (res.status === 'success') {
                    let html = '';
                    res.data.forEach(t => {
                        html += `
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-gray-800">${t.title}</h4>
                                    <p class="text-sm text-gray-600 mt-1 whitespace-pre-wrap line-clamp-2">${t.message}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editTemplate(${t.id})" class="text-blue-600 hover:bg-blue-100 p-2 rounded"><i class="fa-solid fa-edit"></i></button>
                                    <button onclick="deleteTemplate(${t.id})" class="text-red-600 hover:bg-red-100 p-2 rounded"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        `;
                    });
                    $('#templatesList').html(html);
                }
            });
        }

        function openTemplateModal() {
            $('#templateForm')[0].reset();
            $('#templateId').val('');
            $('#modalTitle').text('Yeni Şablon');
            $('#templateModal').removeClass('hidden').addClass('flex');
        }

        function closeTemplateModal() {
            $('#templateModal').addClass('hidden').removeClass('flex');
        }

        function editTemplate(id) {
            $.get('api/templates.php', { action: 'get', id: id }, function (res) {
                if (res.status === 'success') {
                    $('#templateId').val(res.data.id);
                    $('#templateTitle').val(res.data.title);
                    $('#templateMessage').val(res.data.message);
                    $('#modalTitle').text('Şablon Düzenle');
                    $('#templateModal').removeClass('hidden').addClass('flex');
                }
            });
        }

        // SMTP Test
        function testSMTP() {
            Swal.fire({
                title: 'Test Maili Gönderiliyor',
                didOpen: () => Swal.showLoading()
            });
            $.post('api/send_mail.php', { action: 'test_smtp' }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Başarılı', res.message, 'success');
                } else {
                    let errorMsg = res.message;
                    if (res.details) errorMsg += '<br><small>' + res.details + '</small>';
                    Swal.fire('Hata', errorMsg, 'error');
                }
            });
        }

        // History (Logs) Logic
        function loadLogs() {
            $.get('api/logs.php', { action: 'list' }, function (res) {
                if (res.status === 'success') {
                    let html = '';
                    res.data.forEach(log => {
                        const undoBtn = log.previous_data
                            ? `<button onclick="undoAction(${log.id})" class="text-[10px] bg-orange-500/10 text-orange-400 hover:bg-orange-500 hover:text-white px-2 py-1 rounded-lg ml-2 border border-orange-500/20 transition-all flex items-center gap-1" title="İşlemi Geri Al"><i class="fa-solid fa-undo"></i>Geri Al</button>`
                            : '';

                        html += `
                            <tr class="group hover:bg-white/5 transition-all">
                                <td class="p-4 whitespace-nowrap text-slate-500 text-xs font-mono">${log.date_formatted}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center border border-indigo-500/20 text-indigo-400 font-bold text-[10px]">
                                            ${(log.user_name || 'Sys').substring(0, 1).toUpperCase()}
                                        </div>
                                        <div class="font-bold text-white text-sm">${log.user_name || 'Sistem'}</div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-indigo-400 font-bold text-xs flex items-center">
                                        ${log.action}
                                        ${undoBtn}
                                    </div>
                                </td>
                                <td class="p-4 text-slate-400 text-xs leading-relaxed">${log.details}</td>
                                <td class="p-4 text-slate-500 text-[10px] font-mono">${log.ip || '-'}</td>
                            </tr>
                        `;
                    });
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="5" class="p-10 text-center text-slate-500 italic">Henüz bir işlem kaydı bulunmuyor.</td></tr>';
                    }
                    $('#logsList').html(html);
                }
            });
        }

        function undoAction(id) {
            Swal.fire({
                title: 'İşlem Geri Alınacak',
                text: "Seçili işlem geri alınacak ve veriler eski haline döndürülecek. Onaylıyor musunuz?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Geri Al',
                confirmButtonColor: '#f97316',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/logs.php', { action: 'undo', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Başarılı', text: res.message, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                            loadLogs();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                        }
                    });
                }
            });
        }

        function clearHistory() {
            Swal.fire({
                title: 'İşlem Geçmişi Temizlensin mi?',
                text: "Tüm sistem kayıtları kalıcı olarak silinecektir!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Temizle',
                confirmButtonColor: '#f43f5e',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get('api/logs.php', { action: 'clear' }, function (res) {
                        if (res.status === 'success') {
                            loadLogs();
                            Swal.fire({ icon: 'success', title: 'Temizlendi', text: 'Geçmiş silindi', background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                        }
                    });
                }
            });
        }

        function loadTemplates() {
            $.get('api/templates.php', { type: 'whatsapp' }, function (res) {
                if (res.status === 'success') {
                    let html = '';
                    res.data.forEach(t => {
                        html += `
                            <div class="group bg-white/5 border border-white/10 rounded-2xl p-6 transition-all hover:bg-white/10 hover:border-blue-500/30">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-500/10 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-400">
                                            <i class="fa-solid fa-message"></i>
                                        </div>
                                        <h4 class="font-bold text-white text-lg">${t.title}</h4>
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="openTemplateModal(${t.id}, '${t.title}', \`${t.message.replace(/`/g, '\\`').replace(/\$\{/g, '\\${')}\`)" class="w-9 h-9 flex items-center justify-center bg-blue-500/10 text-blue-400 rounded-xl hover:bg-blue-500 hover:text-white transition border border-blue-500/20">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>
                                        <button onclick="deleteTemplate(${t.id})" class="w-9 h-9 flex items-center justify-center bg-rose-500/10 text-rose-400 rounded-xl hover:bg-rose-500 hover:text-white transition border border-rose-500/20">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="bg-black/20 rounded-xl p-4 border border-white/5">
                                    <p class="text-slate-400 text-sm leading-relaxed whitespace-pre-wrap font-mono">${t.message}</p>
                                </div>
                            </div>
                        `;
                    });
                    $('#templatesList').html(html || '<div class="col-span-full p-10 text-center text-slate-500 italic">Henüz şablon eklenmemiş.</div>');
                }
            });
        }

        function openTemplateModal(id = '', title = '', message = '') {
            const modal = $('#templateModal');
            $('#templateId').val(id);
            $('#templateTitle').val(title);
            $('#templateMessage').val(message);
            $('#modalTitle').text(id ? 'Şablon Düzenle' : 'Yeni Şablon');

            modal.removeClass('hidden').addClass('flex');
            setTimeout(() => {
                modal.removeClass('opacity-0').addClass('opacity-100');
                modal.find('.glass-card').removeClass('scale-95').addClass('scale-100');
            }, 10);
        }

        function closeTemplateModal() {
            const modal = $('#templateModal');
            modal.removeClass('opacity-100').addClass('opacity-0');
            modal.find('.glass-card').removeClass('scale-100').addClass('scale-95');
            setTimeout(() => {
                modal.removeClass('flex').addClass('hidden');
            }, 300);
        }

        // Restore Logic
        $('#restoreForm').submit(function (e) {
            e.preventDefault();

            Swal.fire({
                title: 'Yükleniyor...',
                text: 'Veritabanı geri yükleniyor. Lütfen bekleyin.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            var formData = new FormData(this);

            $.ajax({
                url: 'api/backup.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Başarılı', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Hata', text: 'Yükleme başarısız: ' + xhr.responseText, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                }
            });
        });

        function deleteTemplate(id) {
            Swal.fire({
                title: 'Şablonu Sil?',
                text: "Bu işlem geri alınamaz! Mesaj şablonu kalıcı olarak silinecektir.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil',
                confirmButtonColor: '#f43f5e',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/templates.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            loadTemplates();
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Şablon Silindi', timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    });
                }
            });
        }

        $('#templateForm').submit(function (e) {
            e.preventDefault();
            $.post('api/templates.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    closeTemplateModal();
                    loadTemplates();
                    Swal.fire({ icon: 'success', title: 'Kaydedildi', timer: 1500, showConfirmButton: false, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            });
        });

        // API Key Logic
        function loadApiSettings() {
            $.get('api/settings.php', function (data) {
                if (data.hostinger_api_key) $('#hostingerApiKey').val(data.hostinger_api_key);
                if (data.google_sheets_webhook_url) $('#googleSheetsWebhookUrl').val(data.google_sheets_webhook_url);
                if (data.google_sheets_sync_enabled == '1') {
                    $('#syncEnabled').prop('checked', true);
                } else {
                    $('#syncEnabled').prop('checked', false);
                }

                // Evolution
                if (data.evolution_api_url) $('#evoApiUrl').val(data.evolution_api_url);
                if (data.evolution_instance_name) $('#evoInstance').val(data.evolution_instance_name);
                if (data.evolution_api_key) $('#evoApiKey').val(data.evolution_api_key);

                // Admin Daily WhatsApp
                if (data.daily_whatsapp_phone) $('#dailyWaPhone').val(data.daily_whatsapp_phone);
                if (data.daily_whatsapp_time) $('#dailyWaTime').val(data.daily_whatsapp_time);
            });
            loadWaQueue();
        }

        $('#adminWaForm').submit(function (e) {
            e.preventDefault();
            let data = $(this).serialize();
            data += '&action=save_admin_wa';
            $.post('api/settings.php', data, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                }
            });
        });

        function loadWaQueue() {
            $('#waQueueTableBody').html('<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Yükleniyor...</td></tr>');
            $.get('api/settings.php', { action: 'get_wa_queue' }, function (res) {
                if (res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(item => {
                        const safeMsg = encodeURIComponent(item.message);
                        html += `
                            <tr class="group hover:bg-white/5 transition-all text-xs" id="queue-${item.id}">
                                <td class="p-4 whitespace-nowrap">
                                    <div class="font-bold text-white">${item.scheduled_at_formatted.split(' ')[0]}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">${item.scheduled_at_formatted.split(' ')[1]}</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-white mb-0.5 truncate max-w-[150px]">${item.domain || '-'}</div>
                                    <div class="text-[10px] text-indigo-400 font-mono">${item.phone}</div>
                                </td>
                                <td class="p-4 text-slate-400 italic leading-relaxed max-w-[250px] truncate" title="${item.message}">${item.message}</td>
                                <td class="p-4 text-right">
                                    <div class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button onclick="sendWaQueueNow(${item.id})" class="w-8 h-8 flex items-center justify-center bg-emerald-500/10 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition border border-emerald-500/20" title="Şimdi Gönder">
                                            <i class="fa-solid fa-paper-plane text-xs"></i>
                                        </button>
                                        <button onclick="editWaQueueItem(${item.id}, '${safeMsg}', '${item.scheduled_at}')" class="w-8 h-8 flex items-center justify-center bg-blue-500/10 text-blue-400 rounded-lg hover:bg-blue-500 hover:text-white transition border border-blue-500/20" title="Düzenle">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>
                                        <button onclick="deleteWaQueueItem(${item.id})" class="w-8 h-8 flex items-center justify-center bg-rose-500/10 text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition border border-rose-500/20" title="İptal Et">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#waQueueTableBody').html(html);
                } else {
                    $('#waQueueTableBody').html('<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Bekleyen mesaj bulunmuyor.</td></tr>');
                }
            });
        }

        function deleteWaQueueItem(id) {
            Swal.fire({
                title: 'İptal Et?',
                text: "Bu zamanlı mesaj kuyruktan kaldırılacak.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, İptal Et',
                confirmButtonColor: '#f43f5e',
                background: 'rgba(15, 23, 42, 0.9)',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', { action: 'delete_wa_queue', id: id }, function (res) {
                        if (res.status === 'success') {
                            $(`#queue-${id}`).fadeOut(300, function () {
                                $(this).remove();
                                if ($('#waQueueTableBody tr').length === 0) loadWaQueue();
                            });
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Mesaj İptal Edildi', showConfirmButton: false, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    });
                }
            });
        }

        function sendWaQueueNow(id) {
            Swal.fire({
                title: 'Şimdi Gönder?',
                text: "Bu mesaj hemen sıraya alınıp gönderilecek.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Gönder',
                confirmButtonColor: '#10b981',
                background: 'rgba(15, 23, 42, 0.9)',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading(), background: 'transparent', color: '#fff' });
                    $.post('api/settings.php', { action: 'send_wa_queue_now', id: id }, function (res) {
                        if (res.status === 'success') {
                            $(`#queue-${id}`).fadeOut(300, function () {
                                $(this).remove();
                                if ($('#waQueueTableBody tr').length === 0) loadWaQueue();
                            });
                            Swal.fire({ icon: 'success', title: 'Başarılı', text: 'Mesaj başarıyla gönderildi.', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    });
                }
            });
        }

        function editWaQueueItem(id, encodedMsg, currentDateTime) {
            const currentMsg = decodeURIComponent(encodedMsg);
            const formattedDate = currentDateTime.replace(' ', 'T').substring(0, 16);

            Swal.fire({
                title: 'Mesajı Düzenle',
                html: `
                    <div class="text-left space-y-4">
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-1">Tarih ve Saat</label>
                             <input type="datetime-local" id="editWaDate" value="${formattedDate}" class="input-premium w-full !p-3 text-sm">
                         </div>
                         <div>
                             <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-1">Mesaj İçeriği</label>
                             <textarea id="editWaMessage" rows="6" class="input-premium w-full !p-4 text-xs leading-relaxed no-scrollbar">${currentMsg}</textarea>
                         </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Güncelle',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#4f46e5',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: {
                    popup: 'glass-card rounded-[2.5rem] border border-white/10 p-8',
                    title: 'text-2xl font-bold logo-font text-white mb-6 border-b border-white/10 pb-4'
                },
                preConfirm: () => {
                    const newDate = $('#editWaDate').val();
                    const newMsg = $('#editWaMessage').val();
                    if (!newDate || !newMsg) {
                        Swal.showValidationMessage('Lütfen tüm alanları doldurun');
                        return false;
                    }
                    return { newDate, newMsg };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', {
                        action: 'edit_wa_queue',
                        id: id,
                        message: result.value.newMsg,
                        scheduled_at: result.value.newDate
                    }, function (res) {
                        if (res.status === 'success') {
                            loadWaQueue();
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Mesaj Güncellendi', showConfirmButton: false, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    });
                }
            });
        }

        $('#apiForm').submit(function (e) {
            e.preventDefault();
            $.post('api/settings.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                }
            });
        });

        $('#evolutionApiForm').submit(function (e) {
            e.preventDefault();
            $.post('api/settings.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                }
            });
        });

        $('#googleSheetsForm').submit(function (e) {
            e.preventDefault();
            $.post('api/settings.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff', customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' } });
                }
            });
        });

        function syncHostinger() {
            Swal.fire({
                title: 'Hostinger Senkronizasyonu',
                text: 'Veriler güncelleniyor, lütfen bekleyin...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2.5rem]' }
            });

            $.post('api/hostinger.php', { action: 'sync' }, function (res) {
                if (res.status === 'success') {
                    let msg = res.message;
                    if (res.added && res.added.length > 0) {
                        msg += '<br><br><div class="text-left bg-black/20 p-4 rounded-2xl border border-white/5 font-mono text-xs text-indigo-300 max-h-[150px] overflow-auto"><strong>Eklenen Siteler:</strong><br>' + res.added.join('<br>') + '</div>';
                    }
                    Swal.fire({
                        title: 'Tamamlandı',
                        html: msg,
                        icon: 'success',
                        background: 'rgba(15, 23, 42, 0.95)',
                        color: '#fff',
                        customClass: { popup: 'glass-card border border-white/10 rounded-[2.5rem]' }
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                }
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Hata', text: 'İstek başarısız: ' + xhr.responseText, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
            });
        }

        function checkEvolutionStatus() {
            const url = $('#evoApiUrl').val();
            const instance = $('#evoInstance').val();
            const key = $('#evoApiKey').val();

            if (!url || !instance || !key) {
                Swal.fire({ icon: 'warning', title: 'Eksik Bilgi', text: 'Lütfen API bilgilerini eksiksiz girin.', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                return;
            }

            Swal.fire({
                title: 'Bağlantı Kontrolü',
                text: 'Evolution API sunucusuyla iletişim kuruluyor...',
                didOpen: () => Swal.showLoading(),
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2.5rem]' }
            });

            $.post('api/evolution.php', {
                action: 'check_status',
                api_url: url,
                instance: instance,
                api_key: key
            }, function (res) {
                if (res.status === 'success') {
                    const state = res.data.instance?.state || 'Bilinmiyor';
                    let stateTr = state;
                    let icon = 'info';
                    let stateColor = 'text-blue-400';

                    if (state === 'open') {
                        stateTr = 'Açık (Bağlı)';
                        icon = 'success';
                        stateColor = 'text-emerald-400';
                    } else if (state === 'close') {
                        stateTr = 'Kapalı (Bağlantı Yok)';
                        icon = 'error';
                        stateColor = 'text-rose-400';
                    } else if (state === 'connecting') {
                        stateTr = 'Bağlanıyor...';
                        icon = 'warning';
                        stateColor = 'text-indigo-400';
                    }

                    Swal.fire({
                        title: 'Bağlantı Durumu',
                        html: `<div class="text-2xl font-bold mb-4 ${stateColor} logo-font">${stateTr}</div>
                               <div class="text-xs text-slate-500 font-mono bg-black/20 p-3 rounded-xl border border-white/5">Instance ID: ${res.data.instance?.instanceName || '-'}</div>`,
                        icon: icon,
                        background: 'rgba(15, 23, 42, 0.95)',
                        color: '#fff',
                        customClass: { popup: 'glass-card border border-white/10 rounded-[2.5rem]' }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', html: res.message + '<br><small class="opacity-50">' + (res.detail || '') + '</small>', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                }
            }).fail(function (xhr) {
                Swal.fire({ icon: 'error', title: 'Hata', text: 'İstek başarısız: ' + xhr.responseText, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
            });
        }

        function testEvolution() {
            Swal.fire({
                title: 'WhatsApp Test Mesajı',
                html: `
                    <div class="text-left space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-1">Telefon Numarası</label>
                            <input type="text" id="testPhone" class="input-premium w-full !p-3 text-sm" placeholder="905xxxxxxxxx">
                            <p class="text-[9px] text-slate-400 mt-2 px-1 italic">Not: Ülke kodu (90) ile birlikte yazın.</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2 px-1">Örnek Mesaj</label>
                            <textarea id="testMsg" class="input-premium w-full !p-4 text-xs h-32 no-scrollbar">Bu bir sistem test mesajıdır. Bağlantınız başarılı!</textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Gönder',
                cancelButtonText: 'İptal',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: {
                    popup: 'glass-card border border-white/10 rounded-[2.5rem] p-8',
                    title: 'text-2xl font-bold logo-font text-white mb-6 border-b border-white/10 pb-4'
                },
                preConfirm: () => {
                    const phone = $('#testPhone').val();
                    const msg = $('#testMsg').val();
                    if (!phone || !msg) {
                        Swal.showValidationMessage('Lütfen numara ve mesaj girin.');
                        return false;
                    }
                    return { phone, msg };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading(), background: 'transparent', color: '#fff' });
                    const url = $('#evoApiUrl').val();
                    const instance = $('#evoInstance').val();
                    const key = $('#evoApiKey').val();

                    $.post('api/evolution.php', {
                        action: 'test_send',
                        api_url: url,
                        instance: instance,
                        api_key: key,
                        phone: result.value.phone,
                        message: result.value.msg
                    }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Başarılı', text: 'Mesaj kuyruğa alındı ve gönderildi.', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', html: res.message + '<br><small class="opacity-50">' + (res.detail || '') + '</small>', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    }).fail(function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Hata', text: 'İstek başarısız: ' + xhr.responseText, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                    });
                }
            });
        }

        function copyScriptCode() {
            const code = $('#scriptCode').val();
            navigator.clipboard.writeText(code).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Kod kopyalandı!',
                    showConfirmButton: false,
                    timer: 2000,
                    background: 'rgba(15, 23, 42, 0.9)',
                    color: '#fff'
                });
            });
        }

        $('#googleSheetsForm').submit(function (e) {
            e.preventDefault();
            const url = $('#webhookUrl').val();
            if (url && !url.includes('/exec')) {
                Swal.fire({ icon: 'warning', title: 'Geçersiz URL', text: 'Dağıtım (Deploy) URL\'sini kullandığınızdan emin olun.', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                return;
            }
            $.post('api/googlesheets.php', { action: 'save_url', webhook_url: url }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Başarılı!', text: res.message, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                }
            });
        });

        function exportToGoogleSheets() {
            Swal.fire({
                title: 'Data Aktarımı',
                text: 'Tüm site verileri Google Sheets\'e aktarılıyor...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2.5rem]' }
            });

            $.post('api/googlesheets.php', { action: 'export' }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Aktarım Başarılı',
                        text: res.message,
                        background: 'rgba(15, 23, 42, 0.95)',
                        color: '#fff',
                        customClass: { popup: 'glass-card border border-white/10 rounded-[2.5rem]' }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: res.message
                    });
                }
            }).fail(function (xhr) {
                let msg = 'Sunucu hatası';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    msg = 'Hata: ' + xhr.responseText;
                }
                Swal.fire('Hata', msg, 'error');
            });
        }

        // SMTP Test
        function testSMTP() {
            Swal.fire({
                title: 'SMTP Test',
                input: 'email',
                inputLabel: 'Test E-postası Gönderilecek Adres',
                inputValue: $('#smtpFromEmail').val(),
                showCancelButton: true,
                confirmButtonText: 'Test Et',
                showLoaderOnConfirm: true,
                preConfirm: (email) => {
                    return $.post('api/send_mail.php', { action: 'test', test_email: email })
                        .then(response => {
                            if (response.status !== 'success') {
                                throw new Error(response.message + (response.logs ? '\nLoglar: ' + JSON.stringify(response.logs) : ''))
                            }
                            return response
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Hata: ${error}`)
                        })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Başarılı!',
                        text: 'Test maili gönderildi.',
                        icon: 'success'
                    });
                }
            });
        }

        // Initial Load
        loadHistory();
        loadSettings();
        loadApiSettings();
        function sendAdminWaNow() {
            Swal.fire({
                title: 'Rapor Gönderilsin mi?',
                text: "Yönetici telefon numarasına güncel durum raporu şimdi gönderilecek.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Gönder',
                confirmButtonColor: '#16a34a'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading() });

                    // Önce ayarları kaydetmemiz gerekebilir ama mevcut ayarları çekip gönderiyor backend.
                    // Formdaki değerleri gönderip backendde kullanmak daha mantıklı olabilir ama backend Settings'den okuyor.
                    // Kullanıcıya önce kaydetmesi gerektiğini hatırlatalım veya form verisini gönderelim.
                    // Backend'i, gönderilen post verisini (varsa) veya veritabanını kullanacak şekilde ayarlayalım.
                    // Şimdilik sadece tetikliyoruz, backend veritabanından okuyacak.

                    $.post('api/settings.php', { action: 'send_admin_wa_now' }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', 'Rapor gönderildi.', 'success');
                        } else {
                            Swal.fire('Hata', res.message || 'Gönderilemedi', 'error');
                        }
                    });
                }
            });
        }

        // Load Cron Jobs
        function loadCronJobs() {
            $.get('api/tasks.php', { action: 'list' }, function (res) {
                if (res.status === 'success') {
                    // Update stats
                    $('#pendingJobsCount').text(res.stats.pending);
                    $('#completedJobsCount').text(res.stats.completed);
                    $('#failedJobsCount').text(res.stats.failed);
                    $('#recurringJobsCount').text(res.stats.recurring);

                    // Update table
                    let html = '';
                    if (res.jobs.length === 0) {
                        html = '<tr><td colspan="5" class="p-10 text-center text-slate-500 italic">Henüz bir görev planlanmamış.</td></tr>';
                    } else {
                        res.jobs.forEach(job => {
                            const statusColors = {
                                'pending': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'completed': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'failed': 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                'cancelled': 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                            };
                            const statusColor = statusColors[job.status] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';

                            // Parse job_data to show details
                            let jobData = {};
                            try {
                                jobData = JSON.parse(job.job_data || '{}');
                            } catch (e) { }

                            let detailHtml = `<div class="font-bold text-white mb-1">${job.job_name}</div>`;
                            if (jobData.site_domain) {
                                detailHtml += `<div class="text-[10px] text-indigo-400 flex items-center gap-1"><i class="fa-solid fa-globe opacity-50"></i>${jobData.site_domain}</div>`;
                            }
                            if (job.last_run_at) {
                                detailHtml += `<div class="text-[10px] text-slate-500 flex items-center gap-1 mt-1"><i class="fa-regular fa-clock opacity-50"></i>Son: ${job.last_run_at}</div>`;
                            }

                            // Show error log for failed jobs
                            if (job.status === 'failed' && job.error_log) {
                                detailHtml += `<div class="text-[10px] text-rose-400 mt-2 p-2 bg-rose-500/5 rounded-lg border border-rose-500/10 leading-tight"><i class="fa-solid fa-exclamation-triangle mr-1"></i>${job.error_log}</div>`;
                            }

                            const typeLabels = {
                                'reminder_alarm': 'Alarm',
                                'daily_mail_reminder': 'Mail',
                                'daily_whatsapp_reminder': 'WhatsApp',
                                'daily_backup': 'Yedek'
                            };
                            const typeLabel = typeLabels[job.job_type] || job.job_type;

                            // Format date
                            const dateParts = job.scheduled_date.split('-');
                            const formattedDate = dateParts.length === 3 ? `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}` : job.scheduled_date;

                            html += `
                                <tr class="group hover:bg-white/5 transition-all">
                                    <td class="p-4">${detailHtml}</td>
                                    <td class="p-4"><span class="px-2.5 py-1 bg-white/5 text-slate-400 border border-white/10 rounded-lg text-[10px] font-bold uppercase tracking-wider">${typeLabel}</span></td>
                                    <td class="p-4">
                                        <div class="text-sm font-bold text-white">${formattedDate}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">${job.scheduled_time}</div>
                                    </td>
                                    <td class="p-4"><span class="px-2.5 py-1 ${statusColor} border rounded-lg text-[10px] font-bold uppercase tracking-wider">${job.status}</span></td>
                                    <td class="p-4">
                                        <div class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                            ${job.status === 'pending' && (job.job_type === 'daily_mail_reminder' || job.job_type === 'daily_whatsapp_reminder') ? `
                                            <button onclick="runJobNow(${job.id})" class="w-8 h-8 flex items-center justify-center bg-emerald-500/10 text-emerald-400 rounded-lg hover:bg-emerald-500 hover:text-white transition border border-emerald-500/20" title="Şimdi Çalıştır">
                                                <i class="fa-solid fa-play text-xs"></i>
                                            </button>` : ''}
                                            ${job.status === 'pending' ? `
                                            <button onclick="cancelJob(${job.id})" class="w-8 h-8 flex items-center justify-center bg-orange-500/10 text-orange-400 rounded-lg hover:bg-orange-500 hover:text-white transition border border-orange-500/20" title="İptal Et">
                                                <i class="fa-solid fa-ban text-xs"></i>
                                            </button>` : ''}
                                            <button onclick="deleteJob(${job.id})" class="w-8 h-8 flex items-center justify-center bg-rose-500/10 text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition border border-rose-500/20" title="Sil">
                                                <i class="fa-solid fa-trash text-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#cronJobsList').html(html);
                }
            });
        }

        let allWhatsAppQueue = [];
        let currentQueueFilter = 'pending';

        function loadWhatsAppQueue() {
            $.get('api/tasks.php', { action: 'queue' }, function (res) {
                if (res.status === 'success') {
                    allWhatsAppQueue = res.queue;
                    renderWhatsAppQueue();
                }
            });
        }

        function filterWhatsAppQueue(filter) {
            currentQueueFilter = filter;
            $('.wa-filter-btn').removeClass('active border-blue-500/50 bg-blue-500/10 text-blue-400').addClass('border-white/5 bg-white/5 text-slate-500');
            $(`.wa-filter-btn[data-filter="${filter}"]`).addClass('active border-blue-500/50 bg-blue-500/10 text-blue-400').removeClass('border-white/5 bg-white/5 text-slate-500');
            renderWhatsAppQueue();
        }

        function renderWhatsAppQueue() {
            const filteredQueue = currentQueueFilter === 'all'
                ? allWhatsAppQueue
                : allWhatsAppQueue.filter(item => item.status === 'pending');

            let html = '';
            if (filteredQueue.length === 0) {
                const emptyMessage = currentQueueFilter === 'pending' ? 'Bekleyen mesaj yok' : 'Hiç mesaj yok';
                html = `<tr><td colspan="5" class="p-10 text-center text-slate-500 italic">${emptyMessage}</td></tr>`;
            } else {
                filteredQueue.forEach(item => {
                    const preview = item.message.substring(0, 50) + (item.message.length > 50 ? '...' : '');
                    const statusColors = {
                        'pending': 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                        'sent': 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                        'failed': 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                    };
                    const statusColor = statusColors[item.status] || 'bg-slate-500/10 text-slate-400 border-slate-500/20';

                    html += `
                        <tr class="group hover:bg-white/5 transition-all">
                            <td class="p-4">
                                <div class="font-bold text-white">${item.phone}</div>
                                <div class="text-[10px] text-slate-500">${item.domain || '-'}</div>
                            </td>
                            <td class="p-4 text-xs text-slate-400 italic">${preview}</td>
                            <td class="p-4 text-xs text-slate-500 font-mono">${item.scheduled_at}</td>
                            <td class="p-4"><span class="px-2.5 py-1 ${statusColor} border rounded-lg text-[10px] font-bold uppercase tracking-wider">${item.status}</span></td>
                            <td class="p-4 text-right">
                                ${item.status === 'pending' ? `
                                <button onclick="deleteQueueItem(${item.id})" class="w-8 h-8 flex items-center justify-center bg-rose-500/10 text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition border border-rose-500/20 opacity-0 group-hover:opacity-100 mx-auto">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>` : '-'}
                            </td>
                        </tr>
                    `;
                });
            }
            $('#whatsappQueueList').html(html);
        }

        // Cancel Job
        function cancelJob(id) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: 'Bu görevi iptal etmek istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, İptal Et',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/tasks.php', { action: 'cancel', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('İptal Edildi', 'Görev iptal edildi', 'success');
                            loadCronJobs();
                        }
                    });
                }
            });
        }

        // Delete Job
        function deleteJob(id) {
            Swal.fire({
                title: 'Görevi Sil?',
                text: 'Bu görev kalıcı olarak silinecek!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'Vazgeç',
                confirmButtonColor: '#dc2626'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/tasks.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi', 'Görev silindi', 'success');
                            loadCronJobs();
                        }
                    });
                }
            });
        }

        // Delete Queue Item
        function deleteQueueItem(id) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: 'Bu mesajı silmek istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/tasks.php', { action: 'delete_queue', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi', 'Mesaj silindi', 'success');
                            loadWhatsAppQueue();
                        }
                    });
                }
            });
        }

        // Run Job Now
        function runJobNow(id) {
            Swal.fire({
                title: 'Şimdi Çalıştır?',
                text: 'Bu görev hemen çalıştırılacak!',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Çalıştır',
                cancelButtonText: 'Vazgeç'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Çalıştırılıyor...', didOpen: () => Swal.showLoading() });
                    $.post('api/tasks.php', { action: 'run_now', id: id }, function (res) {
                        Swal.close();
                        if (res.status === 'success') {
                            Swal.fire('Tamamlandı!', res.message, 'success');
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                        loadCronJobs();
                    }).fail(function () {
                        Swal.close();
                        Swal.fire('Hata', 'Görev çalıştırılamadı', 'error');
                    });
                }
            });
        }

        // Clear WhatsApp Logs
        function clearWhatsAppLogs() {
            Swal.fire({
                title: 'Logları Temizle?',
                text: 'Gönderilmiş ve başarısız mesajlar silinecek. Sadece bekleyen mesajlar kalacak!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Temizle',
                cancelButtonText: 'Vazgeç',
                confirmButtonColor: '#dc2626'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/tasks.php', { action: 'clear_logs' }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Temizlendi', res.message, 'success');
                            loadWhatsAppQueue();
                        }
                    });
                }
            });
        }

        // Filter WhatsApp Queue
        function filterWhatsAppQueue(filter) {
            currentQueueFilter = filter;

            // Update button styles
            $('.wa-filter-btn').removeClass('active bg-blue-100 text-blue-700').addClass('bg-gray-100 text-gray-700');
            $(`.wa-filter-btn[data-filter="${filter}"]`).removeClass('bg-gray-100 text-gray-700').addClass('active bg-blue-100 text-blue-700');

            // Show/hide clear logs button
            if (filter === 'all') {
                $('#clearLogsBtn').removeClass('hidden');
            } else {
                $('#clearLogsBtn').addClass('hidden');
            }

            renderWhatsAppQueue();
        }

        // Load on tasks tab switch
        $(document).on('click', '[data-tab="tasks"]', function () {
            loadCronJobs();
            loadWhatsAppQueue();
        });

        $(document).on('click', '[data-tab="whatsapp"]', function () {
            loadWhatsAppContacts();
            loadTemplates(); // Existing
        });

        // WHATSAPP INBOX LOGIC
        function loadWhatsAppContacts() {
            $('#waContactsList').html('<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Kişiler yükleniyor...</td></tr>');
            $.get('api/whatsapp.php', { action: 'list_contacts' }, function (res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#waContactsList').html('<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Kayıt bulunamadı. "Senkronize Et" butonunu kullanın.</td></tr>');
                        return;
                    }

                    let html = '';
                    res.data.forEach(c => {
                        const displayName = c.type === 'group' ? (c.group_name || c.name) : c.name;
                        const safeName = (displayName || 'Bilinmiyor').replace(/'/g, "\\'");
                        const displayNumber = c.type === 'group' ? '-' : c.number;
                        const typeBadge = c.type === 'group'
                            ? '<span class="bg-purple-500/10 text-purple-400 border border-purple-500/20 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Grup</span>'
                            : '<span class="bg-blue-500/10 text-blue-400 border border-blue-500/20 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Kişi</span>';

                        html += `
                            <tr class="group hover:bg-white/5 transition-all">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 text-xs font-bold transition-all group-hover:scale-110 group-hover:bg-indigo-500/10 group-hover:text-indigo-400 group-hover:border-indigo-500/20">
                                            ${displayName ? displayName.substring(0, 2).toUpperCase() : '?'}
                                        </div>
                                        <div>
                                            <div class="font-bold text-white mb-0.5">${displayName || 'Bilinmiyor'}</div>
                                            ${typeBadge}
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-400 font-mono text-xs">${displayNumber}</td>
                                <td class="p-4 text-slate-500 text-xs italic">${c.last_message_time || '-'}</td>
                                <td class="p-4 text-right">
                                    <button onclick="fetchChatSettings('${c.jid}', '${safeName}')" class="px-4 py-2 bg-emerald-500/10 text-emerald-400 rounded-xl hover:bg-emerald-500 hover:text-white transition-all text-xs font-bold border border-emerald-500/20 flex items-center gap-2 ml-auto">
                                        <i class="fa-solid fa-comments"></i> Sohbeti Aç
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#waContactsList').html(html);
                }
            });
        }

        function fetchChatSettings(jid, name, forceReq = true) {
            Swal.fire({
                title: 'Sohbet Yükleniyor...',
                didOpen: () => Swal.showLoading(),
                background: 'rgba(15, 23, 42, 0.9)',
                color: '#fff'
            });

            let payload = { action: 'fetch_messages', jid: jid, force_refresh: 1 };

            $.post('api/whatsapp.php', payload, function (res) {
                if (res.status === 'success') {
                    renderChatModalSettings(res.data, jid, name, res.source);
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                }
            }).fail(function () {
                Swal.fire({ icon: 'error', title: 'Hata', text: 'Mesajlar alınamadı', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
            });
        }

        function renderChatModalSettings(messages, jid, name, source) {
            const generateBubble = (msg) => {
                let icon = '';
                if (msg.type === 'image') icon = '<i class="fa-solid fa-image mr-1"></i>';
                else if (msg.type === 'video') icon = '<i class="fa-solid fa-video mr-1"></i>';
                else if (msg.type === 'document') icon = '<i class="fa-solid fa-file mr-1"></i>';
                else if (msg.type === 'audio') icon = '<i class="fa-solid fa-microphone mr-1"></i>';

                const isMe = Boolean(msg.fromMe);
                const align = isMe ? 'self-end' : 'self-start';
                // Premium Dark Mode Styles
                const color = isMe
                    ? 'bg-emerald-500/10 text-emerald-100 border border-emerald-500/20 rounded-tr-none'
                    : 'bg-white/5 text-slate-200 border border-white/10 rounded-tl-none';

                let time = '';
                try { time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit' }); } catch (e) { }

                const senderName = isMe ? 'Ben' : (msg.pushName || name || 'Karşı Taraf');

                return `
                <div class="${align} max-w-[80%] rounded-2xl p-4 shadow-sm ${color} mb-3 backdrop-blur-sm transition-all hover:scale-[1.01]">
                    <p class="text-[10px] text-white/30 mb-1 px-1 ${isMe ? 'text-right' : 'text-left'} uppercase tracking-wider font-bold">${senderName}</p>
                    <p class="text-sm pb-1 break-words leading-relaxed">${icon}${msg.content}</p>
                    <div class="text-[10px] text-white/40 text-right mt-1 flex justify-end items-center gap-1 font-medium">
                        ${time}
                        ${isMe ? '<i class="fa-solid fa-check-double text-blue-400/80 text-[10px]"></i>' : ''}
                    </div>
                </div>`;
            };

            let contentHtml = '';
            if (!messages || messages.length === 0) {
                contentHtml = `
                <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-60">
                    <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                        <i class="fa-regular fa-comments text-3xl"></i>
                    </div>
                    <p class="font-medium text-sm">Mesaj geçmişi bulunamadı</p>
                </div>`;
            } else {
                messages.sort((a, b) => a.timestamp - b.timestamp);
                messages.forEach(m => {
                    contentHtml += generateBubble(m);
                });
            }

            let statusIcon = source === 'database' ? '<i class="fa-solid fa-database text-slate-400"></i>' : '<i class="fa-solid fa-cloud-arrow-down text-emerald-400 animate-pulse"></i>';
            let statusText = source === 'database' ? 'Arşiv' : 'Canlı';
            let statusClass = source === 'database' ? 'bg-white/5 border-white/10 text-slate-400' : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400';

            // Premium Dark Container
            let containerHtml = `
            <div class="flex flex-col h-[600px] overflow-hidden">
                <div class="flex justify-between items-center mb-4 px-2">
                    <div class="text-[10px] flex items-center gap-2 px-3 py-1.5 rounded-xl border uppercase tracking-widest font-bold ${statusClass}">
                        ${statusIcon} <span>${statusText}</span>
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50"></div>
                    </div>
                    <div class="flex gap-4">
                        <button id="showDebugLogsBtn" class="text-slate-500 text-[10px] font-bold uppercase tracking-wider hover:text-white transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-bug"></i> Debug
                        </button>
                        <button id="refreshChatBtn" class="text-indigo-400 text-[10px] font-bold uppercase tracking-wider hover:text-indigo-300 transition-colors flex items-center gap-1.5">
                            <i class="fa-solid fa-sync"></i> API Yenile
                        </button>
                    </div>
                </div>
                <div class="flex-1 flex flex-col overflow-y-auto p-6 bg-black/40 rounded-3xl border border-white/5 custom-scrollbar backdrop-blur-md" id="chatContainerSettings">
                    ${contentHtml}
                </div>
                <div class="mt-4 flex gap-3">
                    <input type="text" id="chatInputSettings" 
                        class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-sm focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 placeholder:text-slate-600 transition-all outline-none" 
                        placeholder="Mesajınızı buraya yazın...">
                    <button id="sendChatBtnSettings" 
                         class="w-14 h-14 btn-gradient-primary rounded-2xl flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 transition-transform active:scale-95 group">
                        <i class="fa-solid fa-paper-plane text-xl text-white group-hover:rotate-12 transition-transform"></i>
                    </button>
                </div>
            </div>`;

            let titleName = name || 'Bilinmiyor';
            if (titleName === 'Você' || titleName === 'Voce') titleName = 'Müşteri';
            const phoneNumber = jid.replace('@s.whatsapp.net', '').replace('@g.us', '');
            const displayTitle = `<span class="text-white logo-font tracking-wide text-xl">${titleName} <span class="text-slate-400 font-mono text-base ml-2 font-normal">${phoneNumber}</span></span>`;

            Swal.fire({
                title: displayTitle,
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
                willClose: () => {
                    // Cleanup if needed
                },
                didOpen: () => {
                    const container = document.getElementById('chatContainerSettings');
                    if (container) container.scrollTop = container.scrollHeight;

                    const sendMessage = () => {
                        const msg = $('#chatInputSettings').val();
                        if (!msg.trim()) return;

                        $('#chatInputSettings').prop('disabled', true);
                        $('#sendChatBtnSettings').prop('disabled', true).addClass('opacity-50 cursor-not-allowed');

                        $.post('api/whatsapp.php', { action: 'send_message', jid: jid, message: msg }, function (res) {
                            if (res.status === 'success') {
                                $('#chatInputSettings').val('').prop('disabled', false).focus();
                                const tempMsg = { fromMe: true, content: msg, timestamp: Math.floor(Date.now() / 1000), type: 'text' };
                                $('#chatContainerSettings').append(generateBubble(tempMsg));
                                container.scrollTop = container.scrollHeight;
                            } else {
                                Swal.showValidationMessage(res.message || 'Gönderilemedi');
                                $('#chatInputSettings').prop('disabled', false);
                            }
                            $('#sendChatBtnSettings').prop('disabled', false).removeClass('opacity-50 cursor-not-allowed');
                        });
                    };

                    $('#sendChatBtnSettings').click(sendMessage);
                    $('#chatInputSettings').on('keypress', function (e) { if (e.which == 13) sendMessage(); });
                    $('#refreshChatBtn').click(function () { fetchChatSettings(jid, name, true); });
                    $('#showDebugLogsBtn').click(function () {
                        $.get('api/whatsapp.php', { action: 'get_debug_logs' }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    title: 'API Debug Logları',
                                    html: `<pre class="text-left text-[10px] bg-slate-950 text-emerald-400 p-6 rounded-xl h-[400px] overflow-auto whitespace-pre-wrap font-mono custom-scrollbar border border-white/10">${res.data}</pre>`,
                                    width: '850px',
                                    showConfirmButton: false,
                                    showCancelButton: true,
                                    cancelButtonText: 'Kapat'
                                });
                            }
                        });
                    });
                }
            });
        }

        function syncChatsSettings() {
            let stepsHtml = `
                <div class="text-left space-y-6 p-4">
                    <div id="step1" class="flex items-center gap-4 text-slate-500 opacity-50 transition-all duration-500">
                        <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                            <i class="fa-solid fa-link step-icon text-xs"></i>
                        </div>
                        <span class="text-sm font-bold uppercase tracking-wider">Bağlantı Kontrolü</span>
                    </div>
                    <div id="step2" class="flex items-center gap-4 text-slate-500 opacity-50 transition-all duration-500">
                        <div class="w-8 h-8 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center">
                            <i class="fa-solid fa-cloud-arrow-down step-icon text-xs"></i>
                        </div>
                        <span class="text-sm font-bold uppercase tracking-wider">Senkronizasyon</span>
                    </div>
                    <div id="stepResult" class="hidden mt-6 p-6 bg-slate-950/80 rounded-[1.5rem] border border-white/5 text-[10px] font-mono text-emerald-400 overflow-y-auto max-h-[250px] no-scrollbar backdrop-blur-md"></div>
                </div>
            `;

            Swal.fire({
                title: 'Sohbet Senkronizasyonu',
                html: stepsHtml,
                allowOutsideClick: false,
                showConfirmButton: false,
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: {
                    popup: 'glass-card rounded-[2.5rem] border border-white/10',
                    title: 'text-2xl font-bold logo-font text-white border-b border-white/10 pb-6'
                },
                didOpen: () => {
                    executeSyncStepsSettings();
                }
            });
        }

        async function executeSyncStepsSettings() {
            const updateStep = (id, status, text = null) => {
                const el = $(`#${id}`);
                const icon = el.find('.step-icon');
                const span = el.find('span');
                const iconBox = el.find('.w-8');

                if (status === 'loading') {
                    el.removeClass('opacity-50 text-slate-500 text-emerald-400 text-rose-400').addClass('opacity-100 text-blue-400');
                    iconBox.removeClass('bg-white/5 bg-emerald-500/10 bg-rose-500/10 border-white/10 border-emerald-500/20 border-rose-500/20').addClass('bg-blue-500/10 border-blue-500/30');
                    icon.attr('class', 'fa-solid fa-circle-notch fa-spin step-icon');
                } else if (status === 'success') {
                    el.removeClass('opacity-50 text-blue-400 text-slate-500').addClass('opacity-100 text-emerald-400 font-bold');
                    iconBox.removeClass('bg-white/5 bg-blue-500/10 border-white/10 border-blue-500/30 font-bold').addClass('bg-emerald-500/10 border-emerald-500/30');
                    icon.attr('class', 'fa-solid fa-check step-icon');
                } else if (status === 'error') {
                    el.removeClass('opacity-50 text-blue-400 text-slate-500').addClass('opacity-100 text-rose-400 font-bold');
                    iconBox.removeClass('bg-white/5 bg-blue-500/10 border-white/10 border-blue-500/30').addClass('bg-rose-500/10 border-rose-500/30');
                    icon.attr('class', 'fa-solid fa-triangle-exclamation step-icon');
                }

                if (text) span.text(text);
            };

            const logResult = (msg) => {
                const box = $('#stepResult');
                box.removeClass('hidden').append(`<div class="mb-1 opacity-70 border-l-2 border-emerald-500/30 pl-3 py-0.5"><span class="text-emerald-500/50 mr-2">➜</span>${msg}</div>`);
                box.scrollTop(box[0].scrollHeight);
            };

            try {
                updateStep('step1', 'loading');
                logResult('Bağlantı parametreleri doğrulanıyor...');
                const connRes = await $.post('api/whatsapp.php', { action: 'check_connection' });

                if (connRes.status === 'success') {
                    updateStep('step1', 'success');
                    logResult('Bağlantı sağlandı!');
                } else {
                    throw new Error(connRes.message || 'Bağlantı kurulamadı');
                }

                updateStep('step2', 'loading');
                logResult('Sirkülasyon verileri aktarılıyor...');

                let page = 1;
                let totalFetched = 0;
                let hasMore = true;

                while (hasMore) {
                    logResult(`<span class="text-blue-400 italic">Blok #${page} işleniyor...</span>`);
                    const fetchRes = await $.post('api/whatsapp.php', { action: 'fetch_remote_chats', page: page, limit: 50 });

                    if (fetchRes.status === 'success') {
                        const fetchedCount = fetchRes.count || 0;
                        totalFetched += fetchedCount;

                        if (fetchRes.names && Array.isArray(fetchRes.names)) {
                            fetchRes.names.forEach(name => {
                                logResult(`<span class="text-emerald-400">+</span> ${name}`);
                            });
                        }

                        logResult(`<span class="text-blue-400"># Blok ${page}: OK (${fetchedCount} kişi)</span>`);
                        hasMore = fetchRes.has_more;
                        page = fetchRes.next_page;
                        if (fetchedCount === 0) hasMore = false;
                    } else {
                        throw new Error(fetchRes.message || 'Senkronizasyon kesildi');
                    }
                }

                updateStep('step2', 'success');
                logResult(`✨ İşlem tamamlandı! Toplam ${totalFetched} veri güncellendi.`);

                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Tamamlandı',
                        text: `${totalFetched} kişi başarıyla senkronize edildi.`,
                        timer: 2000,
                        showConfirmButton: false,
                        background: 'rgba(15, 23, 42, 0.95)',
                        color: '#fff',
                        customClass: { popup: 'glass-card rounded-[2.5rem] border border-white/10' }
                    }).then(() => loadWhatsAppContacts());
                }, 1000);

            } catch (error) {
                console.error(error);
                let errMsg = error.message || (error.responseJSON ? error.responseJSON.message : 'Bilinmeyen Hata');

                if ($('#step1').hasClass('text-blue-400')) {
                    updateStep('step1', 'error');
                } else {
                    updateStep('step2', 'error');
                }

                logResult('HATA: ' + errMsg);
                Swal.update({ showConfirmButton: true, confirmButtonText: 'Kapat', confirmButtonColor: '#f43f5e' });
            }
        }


        // USERS MANAGEMENT
        function loadUsers() {
            $.get('api/users.php', { action: 'list' }, function (res) {
                if (res.status === 'success') {
                    renderUsersTable(res.data);
                }
            });
        }

        function renderUsersTable(users) {
            let html = '';
            users.forEach(u => {
                const roleClass = u.role === 'admin'
                    ? 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                    : 'bg-blue-500/10 text-blue-400 border-blue-500/20';

                const roleLabel = u.role === 'admin' ? 'Admin' : 'Kullanıcı';

                // Active/Passive Badge
                let isActive = (u.is_active == 1 || u.is_active === undefined || u.is_active === null);
                let statusBadge = isActive
                    ? '<span class="px-2.5 py-1 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-lg text-[10px] font-bold uppercase tracking-wider">Aktif</span>'
                    : '<span class="px-2.5 py-1 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-lg text-[10px] font-bold uppercase tracking-wider">Pasif</span>';

                html += `
                    <tr class="group hover:bg-white/5 transition-all">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20 text-indigo-400 font-bold">
                                    ${u.username.substring(0, 1).toUpperCase()}
                                </div>
                                <div>
                                    <div class="font-bold text-white">${u.username}</div>
                                    <div class="text-xs text-slate-500">${u.name_surname}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-bold border uppercase tracking-wider ${roleClass}">${roleLabel}</span>
                                ${statusBadge}
                            </div>
                        </td>
                        <td class="p-4 text-slate-400 text-xs font-mono">
                            ${u.email || '-'}
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="editUser(${u.id})" class="w-9 h-9 flex items-center justify-center bg-blue-500/10 text-blue-400 rounded-xl hover:bg-blue-500 text-sm transition border border-blue-500/20 hover:text-white" title="Düzenle">
                                    <i class="fa-solid fa-edit"></i>
                                </button>
                                <button onclick="showLoginLogs(${u.id})" class="w-9 h-9 flex items-center justify-center bg-purple-500/10 text-purple-400 rounded-xl hover:bg-purple-500 text-sm transition border border-purple-500/20 hover:text-white" title="Giriş Kayıtları">
                                    <i class="fa-solid fa-history"></i>
                                </button>
                                <button onclick="deleteUser(${u.id})" class="w-9 h-9 flex items-center justify-center bg-rose-500/10 text-rose-400 rounded-xl hover:bg-rose-500 text-sm transition border border-rose-500/20 hover:text-white" title="Sil">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            $('#usersList').html(html || '<tr><td colspan="4" class="p-10 text-center text-slate-500 italic">Kullanıcı bulunamadı.</td></tr>');
        }

        function openUserModal() {
            const modal = $('#userModal');
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#userFormAction').val('create');
            $('#userModalTitle').text('Yeni Kullanıcı');
            $('#usernameGroup').show();
            $('#userUsername').prop('required', true);
            $('#userPassword').prop('required', true);
            $('#passwordHint').text('*');
            $('#userCanWa').prop('checked', false);
            $('#user2FA').prop('checked', false);
            $('#userCanEmail').prop('checked', false);
            $('#userIsActive').prop('checked', true);

            modal.removeClass('hidden').addClass('flex');
            setTimeout(() => {
                modal.removeClass('opacity-0').addClass('opacity-100');
                modal.find('.glass-card').removeClass('scale-95').addClass('scale-100');
            }, 10);
        }

        function closeUserModal() {
            const modal = $('#userModal');
            modal.removeClass('opacity-100').addClass('opacity-0');
            modal.find('.glass-card').removeClass('scale-100').addClass('scale-95');
            setTimeout(() => {
                modal.removeClass('flex').addClass('hidden');
            }, 300);
        }

        function editUser(id) {
            $.get('api/users.php', { action: 'list' }, function (res) {
                if (res.status === 'success') {
                    const user = res.data.find(u => u.id == id);
                    if (user) {
                        $('#userId').val(user.id);
                        $('#userFormAction').val('update');
                        $('#userModalTitle').text('Kullanıcı Düzenle');
                        $('#userUsername').val(user.username);
                        $('#userNameSurname').val(user.name_surname);
                        $('#userRole').val(user.role);
                        $('#userEmail').val(user.email);
                        $('#userPhone').val(user.phone || '');
                        $('#usernameGroup').hide();
                        $('#userPassword').prop('required', false);
                        $('#userCanWa').prop('checked', user.can_send_whatsapp == 1);
                        $('#user2FA').prop('checked', user.wa_2fa_enabled == 1);
                        $('#userCanEmail').prop('checked', user.can_send_email == 1);

                        let isActive = (user.is_active == 1 || user.is_active === undefined || user.is_active === null);
                        $('#userIsActive').prop('checked', isActive);

                        $('#passwordHint').text('(Değiştirmek istemiyorsanız boş bırakın)');

                        const modal = $('#userModal');
                        modal.removeClass('hidden').addClass('flex');
                        setTimeout(() => {
                            modal.removeClass('opacity-0').addClass('opacity-100');
                            modal.find('.glass-card').removeClass('scale-95').addClass('scale-100');
                        }, 10);
                    }
                }
            });
        }

        function deleteUser(id) {
            Swal.fire({
                title: 'Kullanıcı Silinecek',
                text: 'Bu işlem geri alınamaz! Kullanıcıya ait tüm veriler silinebilir.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f43f5e',
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'İptal',
                background: 'rgba(15, 23, 42, 0.9)',
                color: '#fff'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('api/users.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Silindi!', text: res.message, timer: 1500 });
                            loadUsers();
                        } else {
                            Swal.fire('Hata!', res.message, 'error');
                        }
                    });
                }
            });
        }

        $('#userForm').submit(function (e) {
            e.preventDefault();
            $.ajax({
                url: 'api/users.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Başarılı!', text: res.message, timer: 1500 });
                        closeUserModal();
                        loadUsers();
                    } else {
                        Swal.fire('Hata!', res.message, 'error');
                    }
                }
            });
        });

        function showLoginLogs(userId) {
            Swal.fire({ title: 'Yükleniyor...', didOpen: () => Swal.showLoading(), background: 'transparent', color: '#fff' });

            $.get('api/users.php', { action: 'get_login_logs', user_id: userId }, function (res) {
                Swal.close();
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        Swal.fire({ icon: 'info', title: 'Bilgi', text: 'Kayıt bulunamadı.', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        return;
                    }

                    let html = '';
                    res.data.forEach(log => {
                        let statusColor = 'text-slate-400';
                        if (log.status === 'success') statusColor = 'text-emerald-400 font-bold';
                        if (log.status === 'failed') statusColor = 'text-rose-400 font-bold';
                        if (log.status === '2fa_sent') statusColor = 'text-blue-400';

                        html += `
                            <tr class="hover:bg-white/5 transition-all">
                                <td class="p-4 border-b border-white/5 text-slate-300 text-xs">${log.created_at}</td>
                                <td class="p-4 border-b border-white/5 text-slate-300 text-xs font-mono">${log.ip_address}</td>
                                <td class="p-4 border-b border-white/5 ${statusColor} text-xs uppercase tracking-wider">${log.status}</td>
                                <td class="p-4 border-b border-white/5 text-slate-400 text-[10px] leading-tight max-w-xs truncate" title="${log.details}">${log.details}</td>
                            </tr>
                        `;
                    });

                    $('#loginLogsBody').html(html);
                    const modal = $('#loginHistoryModal');
                    modal.removeClass('hidden').addClass('flex');
                    setTimeout(() => {
                        modal.removeClass('opacity-0').addClass('opacity-100');
                        modal.find('.glass-card').removeClass('scale-95').addClass('scale-100');
                    }, 10);
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            });
        }

        function closeLoginHistoryModal() {
            const modal = $('#loginHistoryModal');
            modal.removeClass('opacity-100').addClass('opacity-0');
            modal.find('.glass-card').removeClass('scale-100').addClass('scale-95');
            setTimeout(() => {
                modal.removeClass('flex').addClass('hidden');
            }, 300);
        }

        function toggleIpRestriction() {
            const isChecked = $('#ipRestrictionToggle').is(':checked');
            const val = isChecked ? '1' : '0';

            $.post('api/settings.php', { action: 'toggle_ip_restriction', value: val }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'İşlem Başarılı', text: 'IP kısıtlama ayarı güncellendi.', showConfirmButton: false, timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: 'Ayar kaydedilemedi', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                    $('#ipRestrictionToggle').prop('checked', !isChecked);
                }
            });
        }

        // --- IP ACCESS MANAGEMENT ---
        function loadIpWhitelist() {
            // Get Toggle Status
            $.get('api/settings.php', { action: 'get_ip_restriction' }, function (res) {
                if (res.status === 'success') {
                    $('#ipRestrictionToggle').prop('checked', res.enabled);
                }
            });

            $('#ipWhitelist').html('<tr><td colspan="3" class="p-10 text-center text-slate-500 italic">Yükleniyor...</td></tr>');
            $.get('api/settings.php', { action: 'list_ips' }, function (res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#ipWhitelist').html('<tr><td colspan="3" class="p-10 text-center text-slate-500 italic">Kayıt yok. Sadece localhost erişebilir.</td></tr>');
                    } else {
                        let html = '';
                        res.data.forEach(ip => {
                            html += `
                                <tr class="group hover:bg-white/5 transition-all">
                                    <td class="p-4 font-mono text-indigo-400 font-bold">${ip.ip_address}</td>
                                    <td class="p-4 text-slate-400 text-xs">${ip.created_at || '-'}</td>
                                    <td class="p-4 text-right">
                                        <button onclick="deleteIp(${ip.id})" class="w-8 h-8 flex items-center justify-center bg-rose-500/10 text-rose-400 rounded-lg hover:bg-rose-500 hover:text-white transition border border-rose-500/20 opacity-0 group-hover:opacity-100"><i class="fa-solid fa-trash text-xs"></i></button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#ipWhitelist').html(html);
                    }
                }
            });
        }

        function addIpAddress() {
            const ip = $('#newIpInput').val().trim();
            if (!ip) {
                Swal.fire({ icon: 'warning', title: 'Eksik Bilgi', text: 'Lütfen bir IP adresi girin.', background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                return;
            }

            $.post('api/settings.php', { action: 'add_ip', ip_address: ip }, function (res) {
                if (res.status === 'success') {
                    $('#newIpInput').val('');
                    loadIpWhitelist();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'IP Eklendi', timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                } else {
                    Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                }
            });
        }

        function loadApiAccessLogs(status = '') {
            if (status === 'all') status = '';

            $('#apiAccessLogs').html('<div class="p-10 text-center text-slate-500 italic">Yükleniyor...</div>');
            $.get('api/settings.php', { action: 'get_api_logs', status: status }, function (res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#apiAccessLogs').html('<div class="p-10 text-center text-slate-500 italic">Log bulunamadı.</div>');
                    } else {
                        let html = '';
                        res.data.forEach(log => {
                            const isAllowed = log.status === 'allowed';
                            const colorClass = isAllowed ? 'text-emerald-400' : 'text-rose-400';
                            const iconClass = isAllowed ? 'fa-circle-check' : 'fa-circle-xmark';

                            html += `
                                <div class="mb-3 p-4 bg-white/5 border border-white/5 rounded-xl hover:bg-white/10 transition-all flex items-center justify-between group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-8 h-8 rounded-lg ${isAllowed ? 'bg-emerald-500/10' : 'bg-rose-500/10'} flex items-center justify-center border ${isAllowed ? 'border-emerald-500/20' : 'border-rose-500/20'}">
                                            <i class="fa-solid ${iconClass} ${colorClass} text-xs"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs font-mono font-bold text-white">${log.ip_address}</span>
                                                <span class="text-[10px] uppercase font-black px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">${log.method}</span>
                                                <span class="text-[10px] font-mono text-slate-500">${log.endpoint}</span>
                                            </div>
                                            <div class="text-[9px] text-slate-600 mt-1 uppercase tracking-widest">${log.created_at}</div>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end opacity-0 group-hover:opacity-100 transition-opacity">
                                        <span class="text-[10px] font-mono text-slate-500">Agent: ${log.user_agent ? log.user_agent.substring(0, 30) : '-'}...</span>
                                    </div>
                                </div>`;
                        });
                        $('#apiAccessLogs').html(html);
                    }
                }
            });
        }

        function clearApiLogs() {
            Swal.fire({
                title: 'Erişim Logları Silinecek',
                text: 'Tüm API erişim kayıtları kalıcı olarak temizlenecektir.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Temizle',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#f43f5e',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', { action: 'clear_api_logs' }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Temizlendi', text: 'Tüm loglar silindi.', timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                            loadApiAccessLogs();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    });
                }
            });
        }

        function openAddIpModal() {
            Swal.fire({
                title: 'Yeni IP Adresi Ekle',
                html: `
                    <input type="text" id="newIpAddress" class="swal2-input" placeholder="IP Adresi (örn: 192.168.1.1)">
                    <input type="text" id="newIpDesc" class="swal2-input" placeholder="Açıklama (örn: Ofis)">
                `,
                showCancelButton: true,
                confirmButtonText: 'Ekle',
                preConfirm: () => {
                    const ip = Swal.getPopup().querySelector('#newIpAddress').value;
                    const desc = Swal.getPopup().querySelector('#newIpDesc').value;
                    if (!ip) {
                        Swal.showValidationMessage('IP adresi gereklidir');
                    }
                    return { ip: ip, description: desc };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', {
                        action: 'add_ip',
                        ip_address: result.value.ip,
                        description: result.value.description
                    }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', 'IP adresi eklendi', 'success');
                            loadIpWhitelist();
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        function deleteIp(id) {
            Swal.fire({
                title: 'IP Silinecek',
                text: 'Bu adresten kısıtlı bölgelere erişim iznini kaldırmak istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Kaldır',
                confirmButtonColor: '#f43f5e',
                background: 'rgba(15, 23, 42, 0.95)',
                color: '#fff',
                customClass: { popup: 'glass-card border border-white/10 rounded-[2rem]' }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', { action: 'delete_ip', id: id }, function (res) {
                        if (res.status === 'success') {
                            loadIpWhitelist();
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Silindi', timer: 1500, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Hata', text: res.message, background: 'rgba(15, 23, 42, 0.9)', color: '#fff' });
                        }
                    });
                }
            });
        }

        // Initial load
        $(document).ready(function () {
            // Check visibility of elements that need frequent updates
            setInterval(() => {
                if ($('#api-tab').is(':visible') && !$('#api-tab').hasClass('hidden')) {
                    // Could add status refreshers here
                }
            }, 10000);
        });

    </script>

    <!-- Login Logs Modal -->
    <div id="loginHistoryModal"
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center z-[100] p-4 transition-all duration-300 opacity-0">
        <div
            class="glass-card w-full max-w-4xl rounded-[2.5rem] overflow-hidden border border-white/10 shadow-2xl transform scale-95 transition-all duration-300 flex flex-col h-[80vh]">
            <div class="p-8 border-b border-white/10 flex justify-between items-center">
                <h3 class="text-2xl font-bold text-white logo-font flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center border border-purple-500/20">
                        <i class="fa-solid fa-history text-purple-400"></i>
                    </div>
                    Giriş Geçmişi
                </h3>
                <button onclick="closeLoginHistoryModal()"
                    class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white transition-all">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="p-8 flex-1 overflow-auto custom-scrollbar no-scrollbar">
                <table class="w-full text-sm table-premium">
                    <thead class="sticky top-0 z-20 bg-slate-950/50 backdrop-blur-md">
                        <tr>
                            <th class="p-4 text-left">Tarih</th>
                            <th class="p-4 text-left">IP Adresi</th>
                            <th class="p-4 text-left">Durum</th>
                            <th class="p-4 text-left">Detay/Cihaz</th>
                        </tr>
                    </thead>
                    <tbody id="loginLogsBody" class="text-slate-300"></tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>