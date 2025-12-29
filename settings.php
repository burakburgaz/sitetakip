<?php
// Settings Page - Tabbed Interface
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_admin();
$page_title = 'Ayarlar - DReklam';
?>
<?php include 'includes/head.php'; ?>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="bg-white shadow-sm z-10 p-4 border-b border-gray-200">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-cog text-purple-600"></i> Sistem Ayarları
            </h2>
        </header>

        <main class="flex-1 overflow-auto p-6">
            <div class="max-w-6xl mx-auto" style="overflow: visible !important; min-height: 600px;">
                <!-- Tabs -->
                <div class="mb-6 bg-white rounded-t-xl shadow-sm border-b border-gray-200">
                    <!-- Mobile Dropdown (Açılır Menü) -->
                    <div class="md:hidden p-4">
                        <label for="settingsTabSelect" class="block text-sm font-medium text-gray-700 mb-2">Ayarlar
                            Menüsü</label>
                        <select id="settingsTabSelect" onchange="switchTab(this.value)"
                            class="block w-full border border-gray-300 rounded-lg p-2.5 bg-gray-50 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="general">Genel Ayarlar</option>
                            <option value="tasks">Görevler</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="smtp">SMTP / Mail</option>
                            <option value="history">İşlem Geçmişi</option>
                            <option value="api">API Ayarları</option>
                            <option value="access">Erişim (IP)</option>
                            <option value="googlesheets">Google Sheets</option>
                            <option value="backup">Yedekleme</option>
                            <option value="users">Kullanıcılar</option>
                        </select>
                    </div>

                    <!-- Desktop Tabs (Normal Tablar) -->
                    <div class="hidden md:flex md:flex-nowrap md:overflow-x-auto">
                        <button onclick="switchTab('general')"
                            class="tab-btn active px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-indigo-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="general">
                            <i class="fa-solid fa-sliders mr-2"></i> Genel
                        </button>
                        <button onclick="switchTab('tasks')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-blue-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="tasks">
                            <i class="fa-solid fa-clock mr-2"></i> Görevler
                        </button>
                        <button onclick="switchTab('whatsapp')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-green-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="whatsapp">
                            <i class="fa-brands fa-whatsapp mr-2"></i> WhatsApp
                        </button>
                        <button onclick="switchTab('smtp')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-blue-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="smtp">
                            <i class="fa-solid fa-envelope mr-2"></i> SMTP
                        </button>
                        <button onclick="switchTab('history')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-orange-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="history">
                            <i class="fa-solid fa-history mr-2"></i> Geçmiş
                        </button>
                        <button onclick="switchTab('api')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-purple-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="api">
                            <i class="fa-solid fa-code mr-2"></i> API
                        </button>
                        <button onclick="switchTab('access')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-indigo-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="access">
                            <i class="fa-solid fa-shield-halved mr-2"></i> Erişim
                        </button>
                        <button onclick="switchTab('googlesheets')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-green-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="googlesheets">
                            <i class="fa-solid fa-file-excel mr-2"></i> Sheet
                        </button>
                        <button onclick="switchTab('backup')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-red-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="backup">
                            <i class="fa-solid fa-database mr-2"></i> Yedek
                        </button>
                        <button onclick="switchTab('users')"
                            class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-purple-600 focus:outline-none whitespace-nowrap flex items-center"
                            data-tab="users">
                            <i class="fa-solid fa-users mr-2"></i> Kullanıcılar
                        </button>
                    </div>
                </div>

                <!-- General Tab -->
                <div id="general-tab" class="tab-content" style="display: block;">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                            <i class="fa-solid fa-tags text-purple-600"></i> Paket Fiyatları
                        </h3>
                        <form id="priceForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">PRO Paket Fiyatı
                                        (₺)</label>
                                    <input type="number" name="package_pro_price" id="proPriceInput" step="0.01"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">BASIC Paket Fiyatı
                                        (₺)</label>
                                    <input type="number" name="package_basic_price" id="basicPriceInput" step="0.01"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition font-bold">
                                <i class="fa-solid fa-save mr-2"></i>Fiyatları Kaydet
                            </button>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                            <i class="fa-solid fa-building text-blue-600"></i> Firma Bilgileri
                        </h3>
                        <form id="companyForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Firma Adı</label>
                                    <input type="text" name="company_name" id="companyNameInput"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Telefon</label>
                                    <input type="text" name="company_phone" id="companyPhoneInput"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">E-posta</label>
                                    <input type="email" name="company_email" id="companyEmailInput"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                            </div>
                            <button type="submit"
                                class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg hover:from-blue-700 hover:to-cyan-700 transition font-bold">
                                <i class="fa-solid fa-save mr-2"></i>Firma Bilgilerini Kaydet
                            </button>
                        </form>
                    </div>
                </div>


                <!-- Tasks Tab (Görevler) -->
                <div id="tasks-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-clock text-blue-600"></i> Planlı Görevler
                            </h3>
                            <button onclick="loadCronJobs()"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fa-solid fa-sync mr-1"></i>Yenile
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <p class="text-sm text-blue-600 font-semibold">Bekleyen</p>
                                <p class="text-2xl font-bold text-blue-700" id="pendingJobsCount">0</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <p class="text-sm text-green-600 font-semibold">Tamamlanan</p>
                                <p class="text-2xl font-bold text-green-700" id="completedJobsCount">0</p>
                            </div>
                            <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                <p class="text-sm text-red-600 font-semibold">Başarısız</p>
                                <p class="text-2xl font-bold text-red-700" id="failedJobsCount">0</p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <p class="text-sm text-purple-600 font-semibold">Tekrarlayan</p>
                                <p class="text-2xl font-bold text-purple-700" id="recurringJobsCount">0</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 text-gray-700 font-semibold">
                                    <tr>
                                        <th class="p-3 text-left">Görev Detayı</th>
                                        <th class="p-3 text-left">Tür</th>
                                        <th class="p-3 text-left">Zaman</th>
                                        <th class="p-3 text-left">Durum</th>
                                        <th class="p-3 text-left">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="cronJobsList" class="divide-y divide-gray-200">
                                    <tr>
                                        <td colspan="5" class="p-4 text-center text-gray-500">Yükleniyor...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <div class="flex items-center gap-4">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fa-brands fa-whatsapp text-green-600"></i> Bekleyen WhatsApp Mesajları
                                </h3>
                                <!-- Filter Tabs -->
                                <div class="flex gap-2 ml-4">
                                    <button onclick="filterWhatsAppQueue('pending')"
                                        class="wa-filter-btn active px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                                        data-filter="pending">
                                        Bekleyen
                                    </button>
                                    <button onclick="filterWhatsAppQueue('all')"
                                        class="wa-filter-btn px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition"
                                        data-filter="all">
                                        Tümü
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="clearWhatsAppLogs()" id="clearLogsBtn"
                                    class="text-sm text-red-600 hover:text-red-800 font-medium hidden">
                                    <i class="fa-solid fa-trash mr-1"></i>Logları Temizle
                                </button>
                                <button onclick="loadWhatsAppQueue()"
                                    class="text-sm text-green-600 hover:text-green-800 font-medium">
                                    <i class="fa-solid fa-sync mr-1"></i>Yenile
                                </button>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-3">Telefon</th>
                                        <th class="p-3">Mesaj</th>
                                        <th class="p-3">Zamanlanma</th>
                                        <th class="p-3">Durum</th>
                                        <th class="p-3">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="whatsappQueueList">
                                    <tr>
                                        <td colspan="5" class="p-4 text-center">Yükleniyor...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <!-- WhatsApp Tab -->
                <div id="whatsapp-tab" class="tab-content hidden space-y-6">

                    <!-- Gelen Kutusu (Inbox) -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-inbox text-green-600"></i> Sohbet Geçmişi & Rehber
                            </h3>
                            <div class="flex gap-2">
                                <button onclick="syncChatsSettings()" id="syncBtnSettings"
                                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200 transition font-medium">
                                    <i class="fa-solid fa-sync mr-1"></i>Senkronize Et
                                </button>
                                <button onclick="loadWhatsAppContacts()"
                                    class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200 transition">
                                    <i class="fa-solid fa-refresh mr-1"></i>Yenile
                                </button>
                            </div>
                        </div>

                        <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-700 sticky top-0">
                                    <tr>
                                        <th class="p-3">Kişi / Grup</th>
                                        <th class="p-3">Numara</th>
                                        <th class="p-3">Son İşlem</th>
                                        <th class="p-3 text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="waContactsList" class="divide-y divide-gray-100">
                                    <tr>
                                        <td colspan="4" class="p-4 text-center text-gray-500">Yükleniyor...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mesaj Şablonları -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-comment-dots text-indigo-600"></i> Mesaj Şablonları
                            </h3>
                            <button onclick="openTemplateModal()"
                                class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">
                                <i class="fa-solid fa-plus mr-1"></i> Yeni Şablon
                            </button>
                        </div>
                        <div class="mb-4 bg-blue-50 p-4 rounded-lg text-sm text-blue-800">
                            <strong>Kullanılabilir Kısa Kodlar:</strong>
                            <span class="inline-block bg-white px-2 py-1 rounded border ml-1">[ADI SOYADI]</span>
                            <span class="inline-block bg-white px-2 py-1 rounded border ml-1">[SITE]</span>
                            <span class="inline-block bg-white px-2 py-1 rounded border ml-1">[TARIH]</span>
                            <span class="inline-block bg-white px-2 py-1 rounded border ml-1">[PAKET]</span>
                        </div>
                        <div id="templatesList" class="space-y-3">
                            <!-- Templates will be loaded here -->
                        </div>
                    </div>



                    <!-- Admin Daily WhatsApp Reminder -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                            <i class="fa-solid fa-user-shield text-green-600"></i> Yönetici Günlük WhatsApp Hatırlatma
                        </h3>
                        <form id="adminWaForm">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Yönetici Telefon
                                        Numarası</label>
                                    <input type="text" id="dailyWaPhone" name="daily_whatsapp_phone"
                                        class="w-full border rounded-lg px-4 py-2" placeholder="905xxxxxxxxx">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Gönderim Saati</label>
                                    <input type="time" id="dailyWaTime" name="daily_whatsapp_time"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                            </div>
                            <div class="text-right flex justify-end gap-3">
                                <button type="button" onclick="sendAdminWaNow()"
                                    class="px-6 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 font-medium">
                                    <i class="fa-solid fa-paper-plane mr-2"></i>Şimdi Gönder
                                </button>
                                <button type="submit"
                                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold">
                                    <i class="fa-solid fa-save mr-2"></i>Ayarları Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SMTP Tab -->
                <div id="smtp-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                            <i class="fa-solid fa-server text-blue-600"></i> SMTP Sunucu Ayarları
                        </h3>
                        <form id="smtpForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sunucu (Host)</label>
                                    <input type="text" name="smtp_host" id="smtpHost" placeholder="smtp.hostinger.com"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Port</label>
                                    <input type="number" name="smtp_port" id="smtpPort" placeholder="587"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kullanıcı
                                        (E-posta)</label>
                                    <input type="text" name="smtp_user" id="smtpUser" placeholder="info@dreklam.org"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Şifre</label>
                                    <input type="password" name="smtp_pass" id="smtpPass"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Gönderen Email</label>
                                    <input type="email" name="smtp_from_email" id="smtpFromEmail"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Gönderen Adı</label>
                                    <input type="text" name="smtp_from_name" id="smtpFromName" placeholder="Şirket Adı"
                                        class="w-full border rounded-lg px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Güvenlik</label>
                                    <select name="smtp_security" id="smtpSecurity"
                                        class="w-full border rounded-lg px-4 py-2">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="">Yok</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2 border-t pt-4 mt-2">
                                    <h4 class="text-md font-bold text-gray-800 mb-3 block w-full"><i
                                            class="fa-solid fa-clock text-indigo-600 mr-2"></i>Günlük Hatırlatma Maili
                                    </h4>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Gönderim Saati</label>
                                    <input type="time" name="daily_reminder_time" id="dailyReminderTime"
                                        class="w-full border rounded-lg px-4 py-2">
                                    <p class="text-xs text-gray-500 mt-1">Örn: 09:00. Boş bırakırsanız gönderilmez.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kime Gönderilecek
                                        (Email)</label>
                                    <input type="email" name="daily_reminder_email" id="dailyReminderEmail"
                                        placeholder="yonetici@ornek.com" class="w-full border rounded-lg px-4 py-2">
                                </div>
                            </div>
                            <div class="flex gap-4 pt-2">
                                <button type="submit"
                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition font-bold">
                                    <i class="fa-solid fa-save mr-2"></i>Ayarları Kaydet
                                </button>
                                <button type="button" onclick="testSMTP()"
                                    class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-bold">
                                    <i class="fa-solid fa-paper-plane mr-2"></i>Test Et
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- History Tab -->
                <div id="history-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-history text-orange-600"></i> İşlem Geçmişi
                            </h3>
                            <button onclick="clearHistory()"
                                class="text-sm text-red-600 hover:text-red-800 font-medium">
                                <i class="fa-solid fa-trash mr-1"></i>Geçmişi Temizle
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 text-gray-700 font-bold">
                                    <tr>
                                        <th class="p-3">Tarih</th>
                                        <th class="p-3">Kullanıcı</th>
                                        <th class="p-3">İşlem</th>
                                        <th class="p-3">Detay</th>
                                    </tr>
                                </thead>
                                <tbody id="historyList" class="divide-y divide-gray-200">
                                    <!-- Logs via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>



                <!-- API Tab -->
                <div id="api-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-bold text-gray-800">Hostinger API Ayarları</h2>
                            <img src="https://assets.hostinger.com/images/logo-hostinger-black.svg" alt="Hostinger"
                                class="h-6 opacity-80">
                        </div>
                        <p class="text-sm text-gray-600 mb-6">Hostinger panelinizden aldığınız API anahtarını girerek
                            alan adlarınızı otomatik senkronize edebilirsiniz.</p>

                        <form id="apiForm" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                        <i class="fa-solid fa-key"></i>
                                    </span>
                                    <input type="text" id="hostingerApiKey"
                                        class="w-full pl-10 border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Hostinger API Anahtarı">
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Örn: oFpeQRrop1BTZkG1ztAyDF6RW441azruH2...</p>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                                    <i class="fa-solid fa-save mr-2"></i>Kaydet
                                </button>
                            </div>
                        </form>
                    </div>



                    <form id="evolutionApiForm" class="space-y-4">
                        <!-- Evolution API Settings -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Evolution API (WhatsApp)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">API URL</label>
                                    <input type="text" id="evoApiUrl" name="evolution_api_url"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder="https://api.example.com">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Instance
                                        Name</label>
                                    <input type="text" id="evoInstance" name="evolution_instance_name"
                                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                        placeholder="MyInstance">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">API Key</label>
                                <input type="password" id="evoApiKey" name="evolution_api_key"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    placeholder="API Key">
                            </div>
                            <div class="flex justify-end gap-3 border-t pt-4">
                                <button type="button" onclick="checkEvolutionStatus()"
                                    class="bg-blue-100 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-200 font-medium">
                                    <i class="fa-solid fa-signal mr-2"></i>Durum Kontrolü
                                </button>
                                <button type="button" onclick="testEvolution()"
                                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 font-medium">
                                    <i class="fa-solid fa-flask mr-2"></i>Test Et
                                </button>
                                <button type="submit"
                                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-bold">
                                    <i class="fa-solid fa-save mr-2"></i>Kaydet
                                </button>
                            </div>
                        </div>
                    </form>


                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mt-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fa-solid fa-webhook text-blue-600 mr-2"></i>
                            WhatsApp Webhook Ayarları
                            <span class="text-sm font-normal text-gray-500">(Gerçek Zamanlı Mesajlar)</span>
                        </h2>

                        <!-- Webhook Status Card -->
                        <div id="webhookStatusCard"
                            class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-info text-blue-600 text-xl mt-1"></i>
                                <div>
                                    <h3 class="font-bold text-blue-900 mb-1">Webhook Nedir?</h3>
                                    <p class="text-sm text-blue-800">
                                        Webhook sayesinde Evolution API, gelen WhatsApp mesajlarını otomatik olarak
                                        sisteminize gönderir.
                                        Bu sayede müşterilerinizle olan sohbetleri gerçek zamanlı olarak
                                        görüntüleyebilirsiniz.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Webhook URL Form -->
                        <div class="bg-gray-50 p-5 rounded-lg border mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fa-solid fa-link mr-1"></i>
                                Webhook URL
                                <span class="text-xs font-normal text-gray-500">(Düzenlenebilir)</span>
                            </label>
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="webhookUrlInput"
                                    class="flex-1 border border-gray-300 rounded-lg px-4 py-2.5 bg-white font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="https://yourdomain.com/api/whatsapp_webhook.php">
                                <button type="button" onclick="saveWebhookUrl()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 whitespace-nowrap font-medium">
                                    <i class="fa-solid fa-save mr-1"></i>Kaydet
                                </button>
                                <button type="button" onclick="copyWebhookUrl()"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 whitespace-nowrap">
                                    <i class="fa-solid fa-copy mr-1"></i>Kopyala
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">
                                <i class="fa-solid fa-info-circle mr-1"></i>
                                Bu URL'yi düzenleyebilir ve kayıt edebilirsiniz. Kaydetme işlemi yerel ayarlara kaydeder, Evolution API'ye kaydetmek için "Webhook Kaydet" butonunu kullanın.
                            </p>
                        </div>

                        <!-- Webhook Actions -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                            <button type="button" onclick="registerWebhook()"
                                class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 font-medium flex items-center justify-center gap-2">
                                <i class="fa-solid fa-check-circle"></i>
                                Webhook Kaydet
                            </button>
                            <button type="button" onclick="checkWebhookStatus()"
                                class="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 font-medium flex items-center justify-center gap-2">
                                <i class="fa-solid fa-search"></i>
                                Durumu Kontrol Et
                            </button>
                            <button type="button" onclick="testWebhook()"
                                class="bg-purple-600 text-white px-4 py-3 rounded-lg hover:bg-purple-700 font-medium flex items-center justify-center gap-2">
                                <i class="fa-solid fa-flask"></i>
                                Test Et
                            </button>
                        </div>

                        <!-- Webhook Status Display -->
                        <div id="webhookStatusDisplay" class="hidden bg-white p-4 rounded-lg border mb-4">
                            <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-info-circle text-blue-600"></i>
                                Webhook Durumu
                            </h4>
                            <div id="webhookStatusContent" class="text-sm space-y-2">
                                <!-- JS ile doldurulacak -->
                            </div>
                        </div>

                        <!-- Setup Instructions -->
                        <details
                            class="bg-gradient-to-r from-amber-50 to-orange-50 p-5 rounded-lg border border-amber-200">
                            <summary class="font-bold text-amber-900 cursor-pointer flex items-center gap-2">
                                <i class="fa-solid fa-book"></i>
                                Adım Adım Kurulum Rehberi
                            </summary>
                            <div class="mt-4 space-y-4 text-sm text-amber-900">
                                <div class="flex gap-3">
                                    <span
                                        class="flex-shrink-0 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold">1</span>
                                    <div>
                                        <p class="font-semibold mb-1">Evolution API Bilgilerini Girin</p>
                                        <p class="text-amber-800">Yukarıdaki "Evolution API (WhatsApp)" bölümünde
                                            API URL, Instance Name ve API Key bilgilerinizi girin ve kaydedin.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <span
                                        class="flex-shrink-0 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold">2</span>
                                    <div>
                                        <p class="font-semibold mb-1">Webhook URL'ini Kopyalayın</p>
                                        <p class="text-amber-800">Otomatik oluşturulan Webhook URL'ini yukarıdaki
                                            "Kopyala" butonuyla kopyalayın.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <span
                                        class="flex-shrink-0 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold">3</span>
                                    <div>
                                        <p class="font-semibold mb-1">Webhook'u Evolution API'ye Kaydedin</p>
                                        <p class="text-amber-800">"Webhook Kaydet" butonuna tıklayın. Sistem
                                            otomatik olarak Evolution API'ye webhook URL'inizi kaydedecektir.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <span
                                        class="flex-shrink-0 w-8 h-8 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold">4</span>
                                    <div>
                                        <p class="font-semibold mb-1">Durumu Kontrol Edin</p>
                                        <p class="text-amber-800">"Durumu Kontrol Et" butonuyla webhook'un başarıyla
                                            kaydedildiğini doğrulayın.</p>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <span
                                        class="flex-shrink-0 w-8 h-8 bg-green-600 text-white rounded-full flex items-center justify-center font-bold">✓</span>
                                    <div>
                                        <p class="font-semibold mb-1 text-green-700">Tamamlandı!</p>
                                        <p class="text-green-800">Artık gelen WhatsApp mesajları otomatik olarak
                                            sisteminize kaydedilecek ve sohbet ekranlarında gerçek zamanlı olarak
                                            görünecektir.</p>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mt-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Manuel Senkronizasyon</h2>
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-600 flex-1 mr-4">
                                Sisteme yeni eklenen alan adlarını çekmek ve mevcut alan adlarının bitiş tarihlerini
                                güncellemek için aşağıdaki butonu kullanabilirsiniz.
                            </p>
                            <button type="button" onclick="syncHostinger()"
                                class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 shadow flex items-center gap-2 whitespace-nowrap">
                                <i class="fa-solid fa-rotate"></i> Siteleri Getir / Senkronize Et
                            </button>
                        </div>
                    </div>
                </div>
                <!-- API TAB BİTTİ -->

                <!-- ACCESS (IP) TAB -->
                <div id="access-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2
                            class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center justify-between">
                            <span><i class="fa-solid fa-shield-halved text-indigo-600 mr-2"></i>Erişim Güvenliği (IP
                                Whitelist)</span>
                            <button onclick="openAddIpModal()"
                                class="text-xs bg-indigo-100 text-indigo-700 px-3 py-1 rounded hover:bg-indigo-200"><i
                                    class="fa-solid fa-plus mr-1"></i>Yeni IP Ekle</button>
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">API erişimine izin verilen IP adreslerini buradan
                            yönetebilirsiniz. Kendi sunucunuz (localhost) otomatik olarak izinlidir.</p>

                        <div
                            class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 flex items-center justify-between mb-4">
                            <div>
                                <h3 class="font-bold text-indigo-900">IP Kısıtlaması (Güvenlik Modu)</h3>
                                <p class="text-xs text-indigo-800">Aktif edildiğinde sadece listedeki IP'ler API'ye
                                    erişebilir. Kapalıyken herkese açıktır.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="ipRestrictionToggle" class="sr-only peer"
                                    onchange="toggleIpRestriction()">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600">
                                </div>
                            </label>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200 mb-6">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-700 font-semibold uppercase text-xs">
                                    <tr>
                                        <th class="p-3">IP Adresi</th>
                                        <th class="p-3">Açıklama</th>
                                        <th class="p-3 text-right">İşlem</th>
                                    </tr>
                                </thead>
                                <tbody id="ipWhitelistBody" class="divide-y divide-gray-100">
                                    <tr>
                                        <td colspan="3" class="p-4 text-center text-gray-500">Yükleniyor...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-md font-bold text-gray-800"><i
                                    class="fa-solid fa-file-list-line text-gray-500 mr-2"></i>Son Erişim Logları</h3>
                            <div class="flex gap-2 text-xs">
                                <button onclick="loadApiAccessLogs('all')"
                                    class="px-3 py-1 bg-gray-200 hover:bg-gray-300 rounded text-gray-700 font-medium">Tümü</button>
                                <button onclick="loadApiAccessLogs('allowed')"
                                    class="px-3 py-1 bg-green-100 hover:bg-green-200 rounded text-green-700 font-medium">Başarılı</button>
                                <button onclick="loadApiAccessLogs('denied')"
                                    class="px-3 py-1 bg-red-100 hover:bg-red-200 rounded text-red-700 font-medium">Hatalı
                                    / Engellenen</button>
                                <button onclick="clearApiLogs()"
                                    class="px-3 py-1 bg-gray-800 hover:bg-gray-900 rounded text-white font-medium ml-2"
                                    title="Tüm logları sil"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="bg-gray-900 rounded-lg p-4 font-mono text-xs text-gray-300 h-[500px] overflow-y-auto"
                            id="apiAccessLogs">
                            <div class="text-center text-gray-500 py-10">Log yok...</div>
                        </div>
                    </div>
                </div>

                <!-- SHEET TAB - YENİDEN YAZILDI -->
                <div id="googlesheets-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h2 class="text-lg font-bold mb-4"><i class="fa-solid fa-file-excel text-green-600 mr-2"></i>
                            Google Sheets</h2>
                        <p class="text-gray-600 mb-4">Google Sheets entegrasyonu için webhook URL'nizi buraya
                            girebilirsiniz.</p>
                        <form id="googleSheetsForm">
                            <div class="mb-4">
                                <label class="block text-sm font-semibold mb-2">Webhook URL</label>
                                <input type="text" id="webhookUrl" name="webhook_url"
                                    class="w-full border rounded-lg px-4 py-2"
                                    placeholder="https://script.google.com/...">
                            </div>
                            <button type="submit"
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                                <i class="fa-solid fa-save mr-2"></i>Kaydet
                            </button>
                        </form>
                    </div>
                </div>

                <!-- BACKUP TAB - YENİDEN YAZILDI -->
                <div id="backup-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h2 class="text-lg font-bold mb-4"><i class="fa-solid fa-database text-red-600 mr-2"></i>
                            Yedekleme</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-6 rounded-lg border">
                                <h3 class="font-bold mb-2">Yedek Al</h3>
                                <p class="text-sm text-gray-600 mb-4">Veritabanını indirin.</p>
                                <a href="api/backup.php?action=download"
                                    class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                                    <i class="fa-solid fa-download mr-2"></i>Yedek İndir
                                </a>
                            </div>
                            <div class="bg-gray-50 p-6 rounded-lg border">
                                <h3 class="font-bold mb-2">Geri Yükle</h3>
                                <p class="text-sm text-gray-600 mb-4">Yedek dosyayı yükleyin.</p>
                                <form id="restoreForm">
                                    <input type="file" name="backup_file" accept=".sqlite" required
                                        class="block w-full text-sm mb-3">
                                    <input type="hidden" name="action" value="restore">
                                    <button type="submit"
                                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                        <i class="fa-solid fa-upload mr-2"></i>Geri Yükle
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USERS TAB -->
                <div id="users-tab" class="tab-content hidden space-y-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-bold"><i class="fa-solid fa-users text-purple-600 mr-2"></i>
                                Kullanıcı Yönetimi</h2>
                            <button onclick="openUserModal()"
                                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                                <i class="fa-solid fa-plus mr-2"></i>Yeni Kullanıcı
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Kullanıcı Adı</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Tam Ad</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">E-posta</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">Rol</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody" class="divide-y">
                                    <!-- JS ile doldurulacak -->
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
        class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop p-4 overflow-hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-auto flex flex-col max-h-[90vh]">
            <div class="bg-purple-600 text-white p-5 rounded-t-2xl flex-shrink-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold" id="userModalTitle">Yeni Kullanıcı</h3>
                    <button onclick="closeUserModal()" class="text-white hover:text-gray-200">
                        <i class="fa-solid fa-times text-2xl"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto p-6 custom-scrollbar">
                <form id="userForm" class="space-y-4">
                    <input type="hidden" id="userId" name="id">
                    <input type="hidden" name="action" id="userFormAction" value="create">

                    <div id="usernameGroup">
                        <label class="block text-sm font-semibold mb-2">Kullanıcı Adı *</label>
                        <input type="text" name="username" id="userUsername" required
                            class="w-full border rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Tam Ad *</label>
                        <input type="text" name="name_surname" id="userNameSurname" required
                            class="w-full border rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">E-posta</label>
                        <input type="email" name="email" id="userEmail" class="w-full border rounded-lg px-4 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Telefon</label>
                        <input type="text" name="phone" id="userPhone" class="w-full border rounded-lg px-4 py-2"
                            placeholder="5xxxxxxxxx">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Rol *</label>
                        <select name="role" id="userRole" required class="w-full border rounded-lg px-4 py-2">
                            <option value="admin">Admin</option>
                            <option value="user">Kullanıcı</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-4 gap-2 pt-4">
                        <!-- Active User Switch -->
                        <div class="flex flex-col items-center gap-2 p-2 border rounded-lg hover:bg-gray-50">
                            <span class="text-xs font-bold text-gray-700">Aktiflik</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" id="userIsActive" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-600">
                                </div>
                            </label>
                        </div>

                        <!-- WhatsApp Permission Switch -->
                        <div class="flex flex-col items-center gap-2 p-2 border rounded-lg hover:bg-gray-50">
                            <span class="text-xs font-bold text-gray-700">WhatsApp</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="can_send_whatsapp" id="userCanWa" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600">
                                </div>
                            </label>
                        </div>

                        <!-- Email Permission Switch -->
                        <div class="flex flex-col items-center gap-2 p-2 border rounded-lg hover:bg-gray-50">
                            <span class="text-xs font-bold text-gray-700">E-posta</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="can_send_email" id="userCanEmail" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                                </div>
                            </label>
                        </div>

                        <!-- 2FA Switch -->
                        <div class="flex flex-col items-center gap-2 p-2 border rounded-lg hover:bg-gray-50">
                            <span class="text-xs font-bold text-gray-700">2FA</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="wa_2fa_enabled" id="user2FA" class="sr-only peer">
                                <div
                                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500">
                                </div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-2">Şifre <span id="passwordHint"></span></label>
                        <input type="password" name="password" id="userPassword"
                            class="w-full border rounded-lg px-4 py-2">
                    </div>

                    <div class="pt-4 flex justify-end gap-3 border-t mt-4">
                        <button type="button" onclick="closeUserModal()"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">İptal</button>
                        <button type="submit"
                            class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 font-medium">
                            <i class="fa-solid fa-save mr-2"></i>Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Template Modal -->
    <div id="templateModal"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4">
            <div class="bg-indigo-600 text-white p-4 rounded-t-xl flex justify-between items-center">
                <h3 class="font-bold text-lg" id="modalTitle">Şablon Düzenle</h3>
                <button onclick="closeTemplateModal()" class="text-white hover:text-gray-200"><i
                        class="fa-solid fa-times"></i></button>
            </div>
            <form id="templateForm" class="p-6 space-y-4">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="templateId">
                <input type="hidden" name="type" value="whatsapp">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Şablon Başlığı</label>
                    <input type="text" name="title" id="templateTitle" required
                        class="w-full border rounded-lg px-4 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mesaj İçeriği</label>
                    <textarea name="message" id="templateMessage" rows="6" required
                        class="w-full border rounded-lg px-4 py-2 font-mono text-sm"></textarea>
                </div>
                <button type="submit"
                    class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-bold">Kaydet</button>
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
            loadHistory();
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
            console.log('=== SWITCHING TO TAB:', tabName, '===');

            // Tüm tab'ları gizle
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });

            // Seçili tab'ı göster - FLEX + IMPORTANT!
            const selectedTab = document.getElementById(tabName + '-tab');
            if (selectedTab) {
                selectedTab.style.cssText = 'display: flex !important; flex-direction: column; gap: 1.5rem; width: 100%; min-height: 400px;';

                // PARENT DEBUG!
                const parent = selectedTab.parentElement;
                console.log('Parent element:', parent);
                console.log('Parent classes:', parent.className);
                console.log('Parent display:', window.getComputedStyle(parent).display);
                console.log('Parent overflow:', window.getComputedStyle(parent).overflow);
                console.log('Parent width:', parent.offsetWidth);
                console.log('Parent height:', parent.offsetHeight);

                // Force parent to be visible
                parent.style.overflow = 'visible';
                parent.style.minHeight = '600px';

                // DEBUG
                console.log('Tab found and shown!');
                console.log('Tab innerHTML length:', selectedTab.innerHTML.length);
                console.log('Tab children:', selectedTab.children.length);
                console.log('Tab first 500 chars:', selectedTab.innerHTML.substring(0, 500));

                // Force reflow
                setTimeout(() => {
                    console.log('AFTER TIMEOUT - Tab offsetHeight:', selectedTab.offsetHeight);
                    console.log('AFTER TIMEOUT - Tab offsetWidth:', selectedTab.offsetWidth);
                    console.log('AFTER TIMEOUT - Tab display:', window.getComputedStyle(selectedTab).display);
                }, 100);

            } else {
                console.error('❌ TAB NOT FOUND:', tabName + '-tab');
            }

            // Button styling
            $('.tab-btn').removeClass('active border-indigo-600 text-indigo-600').addClass('border-transparent text-gray-600');
            $(`.tab-btn[data-tab='${tabName}']`).addClass('active border-indigo-600 text-indigo-600').removeClass('border-transparent text-gray-600');

            // Sync Mobile Select
            $('#settingsTabSelect').val(tabName);

            if (tabName === 'history') {
                loadHistory();
            }
            if (tabName === 'users') {
                loadUsers();
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

        // History Logic
        function loadHistory() {
            $.get('api/logs.php', { action: 'list', limit: 50 }, function (res) {
                if (res.status === 'success') {
                    let html = '';
                    res.data.forEach(log => {
                        const undoBtn = log.previous_data
                            ? `<button onclick="undoAction(${log.id})" class="text-xs bg-orange-100 text-orange-700 hover:bg-orange-200 px-2 py-1 rounded ml-2" title="İşlemi Geri Al"><i class="fa-solid fa-undo mr-1"></i>Geri Al</button>`
                            : '';

                        html += `
                            <tr class="hover:bg-gray-50 border-b">
                                <td class="p-3 whitespace-nowrap text-gray-600">${log.date_formatted}</td>
                                <td class="p-3 font-medium text-gray-800">${log.user_name || 'Sistem'}</td>
                                <td class="p-3 text-indigo-600 font-semibold flex items-center">
                                    ${log.action}
                                    ${undoBtn}
                                </td>
                                <td class="p-3 text-gray-600 text-xs">${log.details}</td>
                            </tr>
                        `;
                    });
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="4" class="p-4 text-center text-gray-500">Kayıt bulunamadı.</td></tr>';
                    }
                    $('#historyList').html(html);
                }
            });
        }

        function undoAction(id) {
            Swal.fire({
                title: 'İşlem Geri Alınacak',
                text: "Bu işlem geri alınacak ve veri eski haline döndürülecek. Onaylıyor musunuz?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Geri Al',
                confirmButtonColor: '#f97316'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/logs.php', { action: 'undo', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', res.message, 'success');
                            loadHistory();
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        function clearHistory() {
            Swal.fire({
                title: 'Emin misiniz?',
                text: "Tüm işlem geçmişi silinecek!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Temizle',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get('api/logs.php', { action: 'clear' }, function (res) {
                        if (res.status === 'success') {
                            loadHistory();
                            Swal.fire('Temizlendi', 'Geçmiş silindi', 'success');
                        }
                    });
                }
            });
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
                        Swal.fire('Başarılı', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Hata', res.message, 'error');
                    }
                },
                error: function (xhr) {
                    Swal.fire('Hata', 'Yükleme başarısız: ' + xhr.responseText, 'error');
                }
            });
        });

        function deleteTemplate(id) {
            Swal.fire({
                title: 'Emin misiniz?',
                text: "Şablon silinecek!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/templates.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            loadTemplates();
                            Swal.fire('Silindi', '', 'success');
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
                    Swal.fire({ icon: 'success', title: 'Kaydedildi', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            });
        });

        // API Key Logic
        function loadApiSettings() {
            $.get('api/settings.php', function (data) {
                if (data.hostinger_api_key) $('#hostingerApiKey').val(data.hostinger_api_key);
                if (data.google_sheets_webhook_url) $('#webhookUrl').val(data.google_sheets_webhook_url);
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
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500 });
                }
            });
        });

        function loadWaQueue() {
            $('#waQueueTableBody').html('<tr><td colspan="4" class="text-center py-4">Yükleniyor...</td></tr>');
            $.get('api/settings.php', { action: 'get_wa_queue' }, function (res) {
                if (res.status === 'success' && res.data.length > 0) {
                    let html = '';
                    res.data.forEach(item => {
                        // Safe encode message for passing to function
                        const safeMsg = encodeURIComponent(item.message);
                        html += `
                            <tr class="border-b hover:bg-gray-50 transition" id="queue-${item.id}">
                                <td class="px-4 py-2 text-gray-800">${item.scheduled_at_formatted}</td>
                                <td class="px-4 py-2">
                                    <div class="font-bold text-gray-800">${item.domain || '-'}</div>
                                    <div class="text-xs text-gray-500">${item.phone}</div>
                                </td>
                                <td class="px-4 py-2 text-xs text-gray-600 truncate max-w-xs" title="${item.message}">${item.message.substring(0, 50)}...</td>
                                <td class="px-4 py-2">
                                    <div class="flex gap-2">
                                        <button onclick="sendWaQueueNow(${item.id})" class="text-green-600 hover:text-green-800 text-sm font-medium" title="Şimdi Gönder">
                                            <i class="fa-solid fa-paper-plane"></i>
                                        </button>
                                        <button onclick="editWaQueueItem(${item.id}, '${safeMsg}', '${item.scheduled_at}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Düzenle">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button onclick="deleteWaQueueItem(${item.id})" class="text-red-600 hover:text-red-800 text-sm font-medium" title="İptal Et">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    $('#waQueueTableBody').html(html);
                } else {
                    $('#waQueueTableBody').html('<tr><td colspan="4" class="text-center py-4 text-gray-500">Bekleyen mesaj yok.</td></tr>');
                }
            });
        }

        function deleteWaQueueItem(id) {
            Swal.fire({
                title: 'İptal Et?',
                text: "Bu zamanlı mesaj iptal edilecek.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, İptal Et'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', { action: 'delete_wa_queue', id: id }, function (res) {
                        if (res.status === 'success') {
                            $(`#queue-${id}`).remove();
                            if ($('#waQueueTableBody tr').length === 0) loadWaQueue();
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'İptal Edildi', showConfirmButton: false, timer: 1500 });
                        }
                    });
                }
            });
        }

        function sendWaQueueNow(id) {
            Swal.fire({
                title: 'Şimdi Gönder?',
                text: "Bu mesaj kuyruktan alınıp hemen gönderilecek.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Gönder',
                confirmButtonColor: '#16a34a'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading() });
                    $.post('api/settings.php', { action: 'send_wa_queue_now', id: id }, function (res) {
                        if (res.status === 'success') {
                            $(`#queue-${id}`).remove();
                            if ($('#waQueueTableBody tr').length === 0) loadWaQueue();
                            Swal.fire('Başarılı', 'Mesaj gönderildi.', 'success');
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        function editWaQueueItem(id, encodedMsg, currentDateTime) {
            const currentMsg = decodeURIComponent(encodedMsg);
            // format YYYY-MM-DD HH:MM:SS to YYYY-MM-DDTHH:MM for input type=datetime-local
            const formattedDate = currentDateTime.replace(' ', 'T').substring(0, 16);

            Swal.fire({
                title: 'Mesajı Düzenle',
                html: `
                    <div class="text-left">
                         <label class="block text-sm font-semibold mb-1">Tarih ve Saat</label>
                         <input type="datetime-local" id="editWaDate" value="${formattedDate}" class="w-full border rounded px-3 py-2 mb-3">
                         
                         <label class="block text-sm font-semibold mb-1">Mesaj</label>
                         <textarea id="editWaMessage" rows="5" class="w-full border rounded px-3 py-2 text-sm">${currentMsg}</textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Kaydet',
                preConfirm: () => {
                    const newDate = $('#editWaDate').val();
                    const newMsg = $('#editWaMessage').val();
                    if (!newDate || !newMsg) {
                        Swal.showValidationMessage('Lütfen boş bırakmayın');
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
                            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Güncellendi', showConfirmButton: false, timer: 1500 });
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        $('#apiForm').submit(function (e) {
            e.preventDefault();
            // Handle Checkbox Manually
            let syncEnabled = $('#syncEnabled').is(':checked') ? 1 : 0;
            // Merge with other data
            let data = {
                action: 'save_all_api',
                hostinger_api_key: $('#hostingerApiKey').val(),
                google_sheets_webhook_url: $('#webhookUrl').val(),
                google_sheets_sync_enabled: syncEnabled
            };

            $.post('api/settings.php', data, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500 });
                }
            });
        });

        $('#evolutionApiForm').submit(function (e) {
            e.preventDefault();
            $.post('api/settings.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500 });
                }
            });
        });

        // Separate handler if user saves Google Sheets form independently? 
        // Actually the UI has separate forms. I should update googleSheetsForm handler.
        $('#googleSheetsForm').submit(function (e) {
            e.preventDefault();
            let syncEnabled = $('#syncEnabled').is(':checked') ? 1 : 0;
            $.post('api/settings.php', {
                action: 'save_gs',
                google_sheets_webhook_url: $('#webhookUrl').val(),
                google_sheets_sync_enabled: syncEnabled
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi!', text: res.message, timer: 1500 });
                }
            });
        });

        function syncHostinger() {
            Swal.fire({
                title: 'Hostinger ile Senkronize Ediliyor...',
                text: 'Bu işlem biraz zaman alabilir.',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            $.post('api/hostinger.php', { action: 'sync' }, function (res) {
                if (res.status === 'success') {
                    let msg = res.message;
                    if (res.added && res.added.length > 0) {
                        msg += '<br><br><strong>Eklenen Siteler:</strong><br>' + res.added.join('<br>');
                    }
                    Swal.fire({
                        title: 'Tamamlandı',
                        html: msg,
                        icon: 'success'
                    }).then(() => location.reload()); // Reload to update dates
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            }).fail(function (xhr) {
                Swal.fire('Hata', 'İstek başarısız: ' + xhr.responseText, 'error');
            });
        }

        function checkEvolutionStatus() {
            const url = $('#evoApiUrl').val();
            const instance = $('#evoInstance').val();
            const key = $('#evoApiKey').val();

            if (!url || !instance || !key) {
                Swal.fire('Hata', 'Lütfen API bilgilerini eksiksiz girin.', 'warning');
                return;
            }

            Swal.fire({ title: 'Kontrol Ediliyor...', didOpen: () => Swal.showLoading() });

            $.post('api/evolution.php', {
                action: 'check_status',
                api_url: url,
                instance: instance,
                api_key: key
            }, function (res) {
                if (res.status === 'success') {
                    // Check instance state
                    const state = res.data.instance?.state || 'Bilinmiyor';
                    let stateTr = state;
                    let icon = 'info';

                    if (state === 'open') {
                        stateTr = 'Açık (Bağlı)';
                        icon = 'success';
                    } else if (state === 'close') {
                        stateTr = 'Kapalı (Bağlantı Yok)';
                        icon = 'error';
                    } else if (state === 'connecting') {
                        stateTr = 'Bağlanıyor...';
                        icon = 'warning';
                    }

                    Swal.fire({
                        title: 'Bağlantı Durumu',
                        html: `<div class="text-xl font-bold mb-2">${stateTr}</div>
                               <div class="text-sm text-gray-500">Instance: ${res.data.instance?.instanceName || '-'}</div>`,
                        icon: icon
                    });
                } else {
                    Swal.fire('Hata', res.message + '<br><small>' + (res.detail || '') + '</small>', 'error');
                }
            }).fail(function (xhr) {
                Swal.fire('Hata', 'İstek başarısız: ' + xhr.responseText, 'error');
            });
        }

        function testEvolution() {
            Swal.fire({
                title: 'Test Mesajı Gönder',
                html: `
                    <div class="text-left">
                        <label class="block text-sm font-bold mb-1">Telefon Numarası</label>
                        <input type="text" id="testPhone" class="w-full border rounded px-3 py-2" placeholder="905xxxxxxxxx">
                        <p class="text-xs text-gray-500 mt-1">90 ile başlayarak yazın.</p>
                        
                        <label class="block text-sm font-bold mb-1 mt-3">Mesaj</label>
                        <textarea id="testMsg" class="w-full border rounded px-3 py-2">Test mesajıdır.</textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Gönder',
                cancelButtonText: 'İptal',
                preConfirm: () => {
                    const phone = $('#testPhone').val();
                    const msg = $('#testMsg').val();
                    if (!phone || !msg) {
                        Swal.showValidationMessage('Numara ve mesaj gerekli.');
                        return false;
                    }
                    return { phone, msg };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading() });
                    // Send test request
                    // We use values from Inputs because user might not have saved them yet
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
                            Swal.fire('Başarılı', 'Mesaj gönderildi.', 'success');
                        } else {
                            Swal.fire('Hata', res.message + '<br><small>' + (res.detail || '') + '</small>', 'error');
                        }
                    }).fail(function (xhr) {
                        Swal.fire('Hata', 'İstek başarısız: ' + xhr.responseText, 'error');
                    });
                }
            });
        }

        // Google Sheets Logic
        function copyScriptCode() {
            const code = $('#scriptCode').val();
            navigator.clipboard.writeText(code).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Kod kopyalandı!',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        }

        $('#googleSheetsForm').submit(function (e) {
            e.preventDefault();
            const url = $('#webhookUrl').val();
            if (url && !url.includes('/exec')) {
                Swal.fire('Hata', 'URL adresi "/exec" ile bitmelidir. Lütfen Dağıtım (Deploy) URL\'sini kullandığınızdan emin olun.', 'warning');
                return;
            }
            $.post('api/googlesheets.php', { action: 'save_url', webhook_url: url }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Kaydedildi', text: res.message, timer: 1500 });
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            });
        });

        $('#webhookUrl').on('input', function () {
            const url = $(this).val();
            if (url && !url.includes('/exec')) {
                $(this).addClass('border-red-500 ring-2 ring-red-500').removeClass('focus:border-green-500 focus:ring-green-500');
            } else {
                $(this).removeClass('border-red-500 ring-2 ring-red-500').addClass('focus:border-green-500 focus:ring-green-500');
            }
        });

        function exportToGoogleSheets() {
            Swal.fire({
                title: 'Google Sheets\'e Aktarılıyor...',
                text: 'Tüm site verileri gönderiliyor.',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false
            });

            $.post('api/googlesheets.php', { action: 'export' }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı!',
                        text: res.message
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
                        html = '<tr><td colspan="5" class="p-4 text-center text-gray-500">Henüz görev yok</td></tr>';
                    } else {
                        res.jobs.forEach(job => {
                            const statusColors = {
                                'pending': 'bg-blue-100 text-blue-800',
                                'completed': 'bg-green-100 text-green-800',
                                'failed': 'bg-red-100 text-red-800',
                                'cancelled': 'bg-gray-100 text-gray-800'
                            };
                            const statusColor = statusColors[job.status] || 'bg-gray-100';

                            // Parse job_data to show details
                            let jobData = {};
                            try {
                                jobData = JSON.parse(job.job_data || '{}');
                            } catch (e) { }

                            let detailHtml = `<div class="font-semibold text-gray-900">${job.job_name}</div>`;
                            if (jobData.site_domain) {
                                detailHtml += `<div class="text-xs text-gray-600 mt-1"><i class="fa-solid fa-globe mr-1"></i>${jobData.site_domain}</div>`;
                            }
                            if (jobData.note) {
                                detailHtml += `<div class="text-xs text-gray-500 mt-1">${jobData.note.substring(0, 50)}${jobData.note.length > 50 ? '...' : ''}</div>`;
                            }
                            if (job.last_run_at) {
                                detailHtml += `<div class="text-xs text-gray-400 mt-1"><i class="fa-regular fa-clock mr-1"></i>Son: ${job.last_run_at}</div>`;
                            }

                            // Show error log for failed jobs
                            if (job.status === 'failed' && job.error_log) {
                                detailHtml += `<div class="text-xs text-red-600 mt-2 p-2 bg-red-50 rounded border border-red-200"><i class="fa-solid fa-exclamation-triangle mr-1"></i>${job.error_log}</div>`;
                            }

                            const typeLabels = {
                                'reminder_alarm': 'Hatırlatma Alarm',
                                'daily_mail_reminder': 'Günlük Mail',
                                'daily_whatsapp_reminder': 'Günlük WhatsApp',
                                'daily_backup': 'Yedekleme'
                            };
                            const typeLabel = typeLabels[job.job_type] || job.job_type;

                            // Format date as DD-MM-YYYY
                            const dateParts = job.scheduled_date.split('-');
                            const formattedDate = dateParts.length === 3 ? `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}` : job.scheduled_date;

                            html += `<tr class="hover:bg-gray-50">
                                <td class="p-3">${detailHtml}</td>
                                <td class="p-3"><span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded font-medium">${typeLabel}</span></td>
                                <td class="p-3 text-sm">
                                    <div class="font-medium">${formattedDate}</div>
                                    <div class="text-xs text-gray-500">${job.scheduled_time}</div>
                                </td>
                                <td class="p-3"><span class="text-xs ${statusColor} px-3 py-1 rounded-full font-semibold uppercase">${job.status}</span></td>
                                <td class="p-3">
                                    <div class="flex gap-2">
                                        ${job.status === 'pending' && (job.job_type === 'daily_mail_reminder' || job.job_type === 'daily_whatsapp_reminder') ? `<button onclick="runJobNow(${job.id})" class="text-green-600 hover:text-green-800" title="Şimdi Çalıştır"><i class="fa-solid fa-play"></i></button>` : ''}
                                        ${job.status === 'pending' ? `<button onclick="cancelJob(${job.id})" class="text-orange-600 hover:text-orange-800" title="İptal Et"><i class="fa-solid fa-ban"></i></button>` : ''}
                                        ${job.status !== 'pending' ? `<button onclick="deleteJob(${job.id})" class="text-red-600 hover:text-red-800" title="Sil"><i class="fa-solid fa-trash"></i></button>` : ''}
                                    </div>
                                </td>
                            </tr>`;
                        });
                    }
                    $('#cronJobsList').html(html);
                }
            });
        }

        // Global variable to store all queue data
        let allWhatsAppQueue = [];
        let currentQueueFilter = 'pending';

        // Load WhatsApp Queue
        function loadWhatsAppQueue() {
            $.get('api/tasks.php', { action: 'queue' }, function (res) {
                if (res.status === 'success') {
                    allWhatsAppQueue = res.queue;
                    renderWhatsAppQueue();
                }
            });
        }

        // Filter WhatsApp Queue
        function filterWhatsAppQueue(filter) {
            currentQueueFilter = filter;

            // Update button styles
            $('.wa-filter-btn').removeClass('active bg-blue-100 text-blue-700').addClass('bg-gray-100 text-gray-700');
            $(`.wa-filter-btn[data-filter="${filter}"]`).removeClass('bg-gray-100 text-gray-700').addClass('active bg-blue-100 text-blue-700');

            renderWhatsAppQueue();
        }

        // Render WhatsApp Queue based on filter
        function renderWhatsAppQueue() {
            const filteredQueue = currentQueueFilter === 'all'
                ? allWhatsAppQueue
                : allWhatsAppQueue.filter(item => item.status === 'pending');

            let html = '';
            if (filteredQueue.length === 0) {
                const emptyMessage = currentQueueFilter === 'pending' ? 'Bekleyen mesaj yok' : 'Hiç mesaj yok';
                html = `<tr><td colspan="5" class="p-4 text-center text-gray-500">${emptyMessage}</td></tr>`;
            } else {
                filteredQueue.forEach(item => {
                    const preview = item.message.substring(0, 50) + (item.message.length > 50 ? '...' : '');
                    const statusColors = {
                        'pending': 'bg-blue-100 text-blue-700',
                        'sent': 'bg-green-100 text-green-700',
                        'failed': 'bg-red-100 text-red-700'
                    };
                    const statusColor = statusColors[item.status] || 'bg-gray-100';

                    const statusLabels = {
                        'pending': 'Bekliyor',
                        'sent': 'Gönderildi',
                        'failed': 'Başarısız'
                    };
                    const statusLabel = statusLabels[item.status] || item.status;

                    html += `<tr class="hover:bg-gray-50">
                        <td class="p-3">${item.phone}</td>
                        <td class="p-3 text-xs">${preview}</td>
                        <td class="p-3 text-xs">${item.scheduled_at}</td>
                        <td class="p-3"><span class="text-xs ${statusColor} px-2 py-1 rounded font-semibold">${statusLabel}</span></td>
                        <td class="p-3">
                            ${item.status === 'pending' ? `<button onclick="deleteQueueItem(${item.id})" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>` : '-'}
                        </td>
                    </tr>`;
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
            $('#waContactsList').html('<tr><td colspan="4" class="p-4 text-center text-gray-500">Yükleniyor...</td></tr>');
            $.get('api/whatsapp.php', { action: 'list_contacts' }, function (res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#waContactsList').html('<tr><td colspan="4" class="p-4 text-center text-gray-500">Kayıt bulunamadı. "Senkronize Et" butonunu kullanın.</td></tr>');
                        return;
                    }

                    let html = '';
                    res.data.forEach(c => {
                        const displayName = c.type === 'group' ? (c.group_name || c.name) : c.name;
                        const safeName = (displayName || 'Bilinmiyor').replace(/'/g, "\\'");
                        const displayNumber = c.type === 'group' ? '-' : c.number;
                        const typeBadge = c.type === 'group'
                            ? '<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs font-semibold">Grup</span>'
                            : '<span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold">Kişi</span>';

                        html += `
                            <tr class="hover:bg-gray-50 transition border-b">
                                <td class="p-3 font-medium text-gray-800">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold">
                                            ${displayName ? displayName.substring(0, 2).toUpperCase() : '?'}
                                        </div>
                                        ${displayName || 'Bilinmiyor'} ${typeBadge}
                                    </div>
                                </td>
                                <td class="p-3 text-gray-600 font-mono text-xs">${displayNumber}</td>
                                <td class="p-3 text-gray-500 text-xs">${c.last_message_time || '-'}</td>
                                <td class="p-3 text-right">
                                    <button onclick="fetchChatSettings('${c.jid}', '${safeName}')" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-xs transition">
                                        <i class="fa-solid fa-comments mr-1"></i>Mesajlar
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
            Swal.fire({ title: 'Sohbet Yükleniyor...', didOpen: () => Swal.showLoading() });

            let payload = { action: 'fetch_messages', jid: jid, force_refresh: 1 };

            $.post('api/whatsapp.php', payload, function (res) {
                if (res.status === 'success') {
                    renderChatModalSettings(res.data, jid, name, res.source);
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            }).fail(function () {
                Swal.fire('Hata', 'Mesajlar alınamadı', 'error');
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
                const color = isMe ? 'bg-green-100 text-gray-800' : 'bg-white text-gray-800';

                let time = '';
                try { time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'numeric' }); } catch (e) { }

                const senderName = isMe ? 'Ben' : (msg.pushName || name || 'Karşı Taraf');

                return `
                <div class="flex flex-col ${align} max-w-[80%] mb-2">
                    <span class="text-[10px] text-gray-500 mb-0.5 px-1 ${isMe ? 'text-right' : 'text-left'}">${senderName}</span>
                    <div class="${color} p-2 rounded-lg shadow-sm border border-gray-200 relative">
                        <div class="text-sm break-words">${icon}${msg.content}</div>
                        <div class="text-[10px] text-gray-400 text-right mt-1 flex justify-end items-center gap-1">
                            ${time}
                            ${isMe ? '<i class="fa-solid fa-check-double text-blue-500"></i>' : ''}
                        </div>
                    </div>
                </div>`;
            };

            let contentHtml = '';
            if (!messages || messages.length === 0) {
                contentHtml = '<p class="text-center text-gray-500 py-10">Mesaj bulunamadı.</p>';
            } else {
                // Sort by timestamp
                messages.sort((a, b) => a.timestamp - b.timestamp);
                messages.forEach(m => {
                    contentHtml += generateBubble(m);
                });
            }



            // Header with Refresh and Status
            let statusIcon = source === 'database' ? '<i class="fa-solid fa-database text-gray-400" title="Veritabanı"></i>' : '<i class="fa-solid fa-cloud-arrow-down text-green-500" title="API / Canlı"></i>';
            let statusText = source === 'database' ? 'Arşiv' : 'Canlı';

            let containerHtml = `
                <div class="flex justify-between items-center mb-2 px-1">
                <div class="text-xs text-gray-500 flex items-center gap-1 bg-gray-100 px-2 py-1 rounded">
                    ${statusIcon} <span class="font-mono">${statusText}</span>
                    <span id="webhookStatusIndicator" class="ml-2 w-2 h-2 rounded-full bg-green-500" title="Webhook Aktif"></span>
                </div>
                <div class="flex gap-2">
                    <button id="showDebugLogsBtn" class="text-gray-500 text-xs hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-bug"></i> Log (Debug)
                    </button>
                    <button id="refreshChatBtn" class="text-indigo-600 text-xs hover:underline flex items-center gap-1">
                        <i class="fa-solid fa-sync"></i> Yenile (API)
                    </button>
                </div>
            </div>
                <div class="flex flex-col h-[400px] overflow-y-auto p-4 bg-gray-100 rounded-lg text-left" id="chatContainerSettings">`;

            containerHtml += contentHtml;
            containerHtml += '</div>';

            // Input area
            containerHtml += `
            <div class="mt-4 flex gap-2">
                <input type="text" id="chatInputSettings" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Mesaj yazın...">
                <button id="sendChatBtnSettings" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition"><i class="fa-solid fa-paper-plane"></i></button>
            </div>`;

            // Title Formatting
            let titleName = name || 'Bilinmiyor';
            if (titleName === 'Você' || titleName === 'Voce') titleName = 'Müşteri';

            const phoneNumber = jid.replace('@s.whatsapp.net', '').replace('@g.us', '');
            const displayTitle = `${titleName} (${phoneNumber})`;

            Swal.fire({
                title: displayTitle,
                html: containerHtml,
                width: '600px',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Kapat',
                didOpen: () => {
                    const container = document.getElementById('chatContainerSettings');
                    if (container) container.scrollTop = container.scrollHeight;

                    // Send Logic
                    const sendMessage = () => {
                        const msg = $('#chatInputSettings').val();
                        if (!msg.trim()) return;

                        $('#chatInputSettings').prop('disabled', true);
                        $('#sendChatBtnSettings').prop('disabled', true);

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
                            $('#sendChatBtnSettings').prop('disabled', false);
                        });
                    };

                    $('#sendChatBtnSettings').click(sendMessage);
                    $('#chatInputSettings').on('keypress', function (e) { if (e.which == 13) sendMessage(); });

                    // Bind Refresh
                    $('#refreshChatBtn').click(function () {
                        Swal.close(); // Close current modal to re-open or just reload content?
                        // Better to keep modal open but refreshing content inside is harder structure-wise with Swal
                        // Let's just re-call fetchChatSettings which will show Loading Swal and then re-render
                        fetchChatSettings(jid, name, true);
                    });

                    // Bind Log Viewer
                    $('#showDebugLogsBtn').click(function () {
                        $.get('api/whatsapp.php', { action: 'get_debug_logs' }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    title: 'API Debug Logları',
                                    html: `<pre class="text-left text-xs bg-gray-900 text-green-400 p-4 rounded h-[400px] overflow-auto whitespace-pre-wrap font-mono">${res.data}</pre>`,
                                    width: '800px',
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
            // STEP 1: UI Initialization
            let stepsHtml = `
                <div class="text-left text-sm space-y-3 p-2">
                    <div id="step1" class="flex items-center gap-3 text-gray-500">
                        <i class="fa-solid fa-circle-notch fa-spin step-icon"></i>
                        <span>API ve Telefon Bağlantısı Kontrol Ediliyor...</span>
                    </div>
                    <div id="step2" class="flex items-center gap-3 text-gray-400">
                        <i class="fa-solid fa-circle step-icon text-gray-300"></i>
                        <span>Sohbetler Telefondan Çekiliyor...</span>
                    </div>
                    <div id="stepResult" class="hidden mt-4 p-3 bg-gray-50 rounded border text-xs font-mono text-gray-600"></div>
                </div>
            `;

            Swal.fire({
                title: 'WhatsApp Senkronizasyonu',
                html: stepsHtml,
                allowOutsideClick: false,
                showConfirmButton: false,
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

                if (status === 'loading') {
                    el.removeClass('text-gray-400 text-green-600 text-red-600').addClass('text-blue-600 font-medium');
                    icon.attr('class', 'fa-solid fa-circle-notch fa-spin step-icon');
                } else if (status === 'success') {
                    el.removeClass('text-gray-400 text-blue-600 text-gray-500').addClass('text-green-600 font-bold');
                    icon.attr('class', 'fa-solid fa-check-circle step-icon');
                } else if (status === 'error') {
                    el.removeClass('text-gray-400 text-blue-600').addClass('text-red-600 font-bold');
                    icon.attr('class', 'fa-solid fa-times-circle step-icon');
                } else { // pending
                    el.removeClass('text-blue-600 text-green-600').addClass('text-gray-400');
                    icon.attr('class', 'fa-solid fa-circle step-icon text-gray-300');
                }

                if (text) span.text(text);
            };

            const logResult = (msg) => {
                const box = $('#stepResult');
                box.removeClass('hidden').append(`<div>> ${msg}</div>`);
                box.scrollTop(box[0].scrollHeight);
            };

            try {
                // STEP 1: CONNECTION CHECK
                updateStep('step1', 'loading');
                logResult('API Bağlantısı kontrol ediliyor...');

                const connRes = await $.post('api/whatsapp.php', { action: 'check_connection' });

                if (connRes.status === 'success') {
                    updateStep('step1', 'success', 'API ve Telefon Bağlı');
                    logResult('Bağlantı başarılı: ' + connRes.message);
                } else {
                    throw new Error(connRes.message || 'Bağlantı hatası');
                }

                // STEP 2: FETCH CHATS
                updateStep('step2', 'loading');
                logResult('Sohbet listesi isteniyor (Evolution API)...');

                let page = 1;
                let totalFetched = 0;
                let hasMore = true;

                while (hasMore) {
                    logResult(`📄 Sayfa ${page} işleniyor...`);
                    const fetchRes = await $.post('api/whatsapp.php', { action: 'fetch_remote_chats', page: page, limit: 50 });

                    if (fetchRes.status === 'success') {
                        const fetchedCount = fetchRes.count || 0;
                        totalFetched += fetchedCount;

                        // Display fetched names
                        if (fetchRes.names && Array.isArray(fetchRes.names)) {
                            fetchRes.names.forEach(name => {
                                logResult(`<span class="text-green-600">+</span> ${name}`);
                            });
                        }

                        // Log page summary
                        logResult(`<span class="text-blue-600">→ Sayfa ${page}: ${fetchedCount} sohbet çekildi (Toplam: ${totalFetched})</span>`);

                        hasMore = fetchRes.has_more;
                        page = fetchRes.next_page; // next page index

                        if (fetchedCount === 0) hasMore = false;

                    } else {
                        throw new Error(fetchRes.message || 'Sohbetler alınamadı');
                    }
                }

                updateStep('step2', 'success', 'Sohbetler Getirildi');
                logResult(`✅ TOPLAM: ${totalFetched} sohbet güncellendi.`);

                setTimeout(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı',
                        text: `${totalFetched} kişi/sohbet güncellendi.`,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => loadWhatsAppContacts());
                }, 1000);

            } catch (error) {
                console.error(error);
                let errMsg = error.message || (error.responseJSON ? error.responseJSON.message : 'Bilinmeyen Hata');

                // Determine which step failed
                if ($('#step1').hasClass('text-blue-600')) {
                    updateStep('step1', 'error', 'Bağlantı Hatası');
                } else {
                    updateStep('step2', 'error', 'Sohbet Çekme Hatası');
                }

                logResult('HATA: ' + errMsg);

                Swal.update({
                    showConfirmButton: true,
                    confirmButtonText: 'Kapat',
                    confirmButtonColor: '#d33'
                });
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
                const roleClass = u.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';

                // Active/Passive Badge
                let statusBadge = (u.is_active == 1 || u.is_active === undefined || u.is_active === null)
                    ? '<span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Aktif</span>'
                    : '<span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">Pasif</span>';

                html += `<tr class="hover:bg-gray-50">`;
                html += `<td class="px-4 py-3"><strong>${u.username}</strong></td>`;
                html += `<td class="px-4 py-3">${u.name_surname}</td>`;
                html += `<td class="px-4 py-3">${u.email || '-'}</td>`;
                html += `<td class="px-4 py-3"><span class="px-3 py-1 rounded-full text-xs font-bold ${roleClass}">${u.role === 'admin' ? 'Admin' : 'Kullanıcı'}</span> ${statusBadge}</td>`;
                html += `<td class="px-4 py-3"><div class="flex gap-2">`;
                html += `<button onclick="editUser(${u.id})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"><i class="fa-solid fa-edit"></i></button>`;
                html += `<button onclick="showLoginLogs(${u.id})" class="px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm" title="Giriş Kayıtları"><i class="fa-solid fa-history"></i></button>`;
                html += `<button onclick="deleteUser(${u.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"><i class="fa-solid fa-trash"></i></button>`;
                html += `</div></td></tr>`;
            });
            $('#usersTableBody').html(html);
        }

        function openUserModal() {
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
            $('#userIsActive').prop('checked', true); // Default Active
            $('#userModal').removeClass('hidden').addClass('flex');
        }

        function closeUserModal() {
            $('#userModal').addClass('hidden').removeClass('flex');
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
                        $('#userModal').removeClass('hidden').addClass('flex');
                    }
                }
            });
        }

        function deleteUser(id) {
            Swal.fire({
                title: 'Kullanıcı Silinecek',
                text: 'Bu işlem geri alınamaz!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'İptal'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('api/users.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi!', res.message, 'success');
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
                        Swal.fire({ icon: 'success', title: 'Başarılı!', text: res.message, timer: 2000 });
                        closeUserModal();
                        loadUsers();
                    } else {
                        Swal.fire('Hata!', res.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire('Hata!', 'Sunucu hatası: ' + error, 'error');
                }
            });
        });

        function showLoginLogs(userId) {
            Swal.fire({ title: 'Yükleniyor...', didOpen: () => Swal.showLoading() });

            $.get('api/users.php', { action: 'get_login_logs', user_id: userId }, function (res) {
                Swal.close();
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        Swal.fire('Bilgi', 'Kayıt bulunamadı.', 'info');
                        return;
                    }

                    let html = '';
                    res.data.forEach(log => {
                        let statusColor = 'text-gray-600';
                        if (log.status === 'success') statusColor = 'text-green-600 font-bold';
                        if (log.status === 'failed') statusColor = 'text-red-600 font-bold';
                        if (log.status === '2fa_sent') statusColor = 'text-blue-600';

                        html += `<tr>
                            <td class="p-2 border-b">${log.created_at}</td>
                            <td class="p-2 border-b">${log.username || '-'}</td>
                            <td class="p-2 border-b text-xs font-mono">${log.ip_address}</td>
                            <td class="p-2 border-b ${statusColor}">${log.status}</td>
                            <td class="p-2 border-b text-xs">${log.details}</td>
                        </tr>`;
                    });

                    $('#loginLogsBody').html(html);
                    $('#loginHistoryModal').removeClass('hidden').addClass('flex');
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            });
        }

        function closeLoginHistoryModal() {
            $('#loginHistoryModal').addClass('hidden').removeClass('flex');
        }

        function toggleIpRestriction() {
            const isChecked = $('#ipRestrictionToggle').is(':checked');
            const val = isChecked ? '1' : '0';

            $.post('api/settings.php', { action: 'toggle_ip_restriction', value: val }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Ayarlar güncellendi', showConfirmButton: false, timer: 1500 });
                } else {
                    Swal.fire('Hata', 'Ayar kaydedilemedi', 'error');
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

            $('#ipWhitelistBody').html('<tr><td colspan="3" class="p-4 text-center text-gray-500">Yükleniyor...</td></tr>');
            $.get('api/settings.php', { action: 'list_ips' }, function (res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#ipWhitelistBody').html('<tr><td colspan="3" class="p-4 text-center text-gray-500">Kayıt yok. Sadece localhost erişebilir.</td></tr>');
                    } else {
                        let html = '';
                        res.data.forEach(ip => {
                            html += `
                                <tr class="hover:bg-gray-50 border-b">
                                    <td class="p-3 font-mono text-gray-800">${ip.ip_address}</td>
                                    <td class="p-3 text-gray-600">${ip.description || '-'}</td>
                                    <td class="p-3 text-right">
                                        <button onclick="deleteIp(${ip.id})" class="text-red-500 hover:text-red-700 text-sm"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#ipWhitelistBody').html(html);
                    }
                }
            });
        }

        function loadApiAccessLogs(status = '') {
            if (status === 'all') status = '';

            $('#apiAccessLogs').html('<div class="text-center text-gray-500 py-4">Logs loading...</div>');
            $.get('api/settings.php', { action: 'get_api_logs', status: status }, function (res) {
                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        $('#apiAccessLogs').html('<div class="text-center text-gray-500 py-10">Log yok...</div>');
                    } else {
                        let html = '';
                        res.data.forEach(log => {
                            const color = log.status === 'allowed' ? 'text-green-400' : 'text-red-400';
                            const icon = log.status === 'allowed' ? '✓' : '✗';
                            const logDate = new Date(log.created_at.replace(' ', 'T') + 'Z');
                            const timeStr = logDate.toLocaleString('tr-TR');
                            html += `<div class="mb-1 border-b border-gray-800 pb-1 last:border-0">
                                <span class="text-gray-500">[${timeStr}]</span> 
                                <span class="${color} font-bold mx-1">${icon} ${log.status.toUpperCase()}</span> 
                                <span class="text-blue-300">${log.ip_address}</span> -> 
                                <span class="text-yellow-200">${log.method}</span> ${log.endpoint}
                            </div>`;
                        });
                        $('#apiAccessLogs').html(html);
                    }
                }
            });
        }

        function clearApiLogs() {
            Swal.fire({
                title: 'Logları Temizle',
                text: 'Tüm erişim logları silinecek? Bu işlem geri alınamaz.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'İptal',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', { action: 'clear_api_logs' }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', 'Loglar temizlendi.', 'success');
                            loadApiAccessLogs();
                        } else {
                            Swal.fire('Hata', res.message, 'error');
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
                text: 'Erişimi kaldırmak istediğinize emin misiniz?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/settings.php', { action: 'delete_ip', id: id }, function (res) {
                        if (res.status === 'success') {
                            loadIpWhitelist();
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        // Load IP data when settings tab is opened (assuming user clicks Settings tab or Evolution API section)
        // Or just load regularly. The tabs logic is separate. 
        // We'll hook into something or just call it if visible.
        // For simplicity, let's call it on doc ready and refresh every 30s if tab is open?
        // Or just lazy load.
        $(document).ready(function () {
            // Hook into tab switch if possible, or just load once
            loadIpWhitelist();
            loadApiAccessLogs();

            // Refresh logs occasionally
            setInterval(() => {
                if ($('#apiAccessLogs').is(':visible')) {
                    loadApiAccessLogs();
                }
            }, 10000);
        });

    </script>

    <!-- Login Logs Modal -->
    <div id="loginHistoryModal"
        class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 h-3/4 flex flex-col">
            <div class="bg-indigo-600 text-white p-6 rounded-t-2xl flex justify-between items-center">
                <h3 class="text-xl font-bold">Giriş Geçmişi</h3>
                <button onclick="$('#loginHistoryModal').addClass('hidden').removeClass('flex')"
                    class="text-white text-2xl"><i class="fa-solid fa-times"></i></button>
            </div>
            <div class="p-6 flex-1 overflow-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 font-bold">
                        <tr>
                            <th class="p-3">Tarih</th>
                            <th class="p-3">Kullanıcı (Ref)</th>
                            <th class="p-3">IP</th>
                            <th class="p-3">Durum</th>
                            <th class="p-3">Detay</th>
                        </tr>
                    </thead>
                    <tbody id="loginLogsBody" class="divide-y relative"></tbody>
                </table>
            </div>
        </div>
    </div>
    <style>
        .tab-btn.active {
            border-bottom-width: 2px;
        }
    </style>
</body>

</html>