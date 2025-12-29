<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'Siteler - DReklam';
?>
<?php include 'includes/head.php'; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">

<style>
    :root {
        --primary: #3b82f6;
        --primary-dark: #2563eb;
        --bg-dark: #0f172a;
        --glass-bg: rgba(255, 255, 255, 0.03);
        --glass-border: rgba(255, 255, 255, 0.1);
        --glass-hover: rgba(255, 255, 255, 0.06);
    }

    body {
        background-color: var(--bg-dark) !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        color: #f8fafc;
    }

    .bg-blobs {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        overflow: hidden;
        background: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
        pointer-events: none;
    }

    .blob {
        position: absolute;
        width: 600px;
        height: 600px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(147, 51, 234, 0.1) 100%);
        filter: blur(80px);
        border-radius: 50%;
        animation: move 25s infinite alternate;
    }

    .blob-1 {
        top: -100px;
        left: -100px;
        animation-delay: 0s;
    }

    .blob-2 {
        bottom: -100px;
        right: -200px;
        animation-delay: -5s;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(34, 197, 94, 0.1) 100%);
    }

    .blob-3 {
        top: 30%;
        right: 10%;
        animation-delay: -10s;
        width: 400px;
        height: 400px;
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.08) 0%, rgba(249, 115, 22, 0.08) 100%);
    }

    @keyframes move {
        from {
            transform: translate(0, 0) rotate(0deg);
        }

        to {
            transform: translate(100px, 100px) rotate(90deg);
        }
    }

    .glass-header {
        background: rgba(15, 23, 42, 0.4) !important;
        backdrop-filter: blur(10px) !important;
        border-bottom: 1px solid var(--glass-border) !important;
    }

    .logo-font {
        font-family: 'Outfit', sans-serif;
    }

    .glass-card {
        background: var(--glass-bg) !important;
        backdrop-filter: blur(12px) !important;
        -webkit-backdrop-filter: blur(12px) !important;
        border: 1px solid var(--glass-border) !important;
        box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5) !important;
    }

    /* Table Improvements */
    #sitesTable tr {
        transition: all 0.2s ease;
    }

    #sitesTable tr:hover {
        background: var(--glass-hover) !important;
    }

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
</style>

<body class="flex h-screen overflow-hidden">
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative z-10">
        <header class="glass-header z-20 p-4 border-b border-white/10">
            <div class="flex items-center justify-between max-w-[1600px] mx-auto w-full">
                <h2 class="text-xl font-bold logo-font text-white flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center border border-blue-500/30">
                        <i class="fa-solid fa-globe text-blue-400"></i>
                    </div>
                    <span>Siteler</span>
                </h2>
                <div class="flex items-center gap-3">
                    <div class="flex bg-white/5 rounded-xl p-1 border border-white/10">
                        <button onclick="importSites()" title="Excel İçe Aktar"
                            class="p-2.5 text-slate-400 hover:text-blue-400 hover:bg-white/5 rounded-lg transition-all active:scale-95">
                            <i class="fa-solid fa-file-import"></i>
                        </button>
                        <button onclick="exportSites()" title="Excel Dışa Aktar"
                            class="p-2.5 text-slate-400 hover:text-emerald-400 hover:bg-white/5 rounded-lg transition-all active:scale-95">
                            <i class="fa-solid fa-file-excel"></i>
                        </button>
                    </div>
                    <button onclick="openSiteModal()"
                        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm rounded-xl hover:shadow-lg hover:shadow-blue-500/30 transition-all flex items-center gap-2 font-bold active:scale-95">
                        <i class="fa-solid fa-plus text-xs"></i><span class="hidden sm:inline">Yeni Site Ekle</span>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto p-4 md:p-6 lg:p-8">
            <div class="max-w-[1600px] mx-auto space-y-6">
                <div class="glass-card rounded-2xl border border-white/10 overflow-hidden">
                    <div
                        class="p-6 border-b border-white/10 bg-white/5 flex flex-col lg:flex-row items-center justify-between gap-6">
                        <!-- Filters -->
                        <div class="w-full lg:w-auto flex flex-col sm:flex-row items-center gap-4">
                            <div class="relative w-full sm:w-72">
                                <i
                                    class="fa-solid fa-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <select id="filterSelect" onchange="filterSites(this.value)"
                                    class="w-full pl-10 pr-10 py-3 bg-white/5 border border-white/10 rounded-xl text-sm focus:outline-none focus:border-blue-500 appearance-none font-semibold text-slate-200 cursor-pointer transition-all hover:bg-white/10">
                                    <option value="upcoming" selected class="bg-slate-900">📅 Yaklaşan (30 gün)</option>
                                    <option value="all" class="bg-slate-900">📋 Tüm Siteler</option>
                                    <option value="active" class="bg-slate-900">✅ Aktif</option>
                                    <option value="expired" class="bg-slate-900">⚠️ Süresi Dolmuş</option>
                                    <option value="cancelled" class="bg-slate-900">🚫 İptal Edilenler</option>
                                    <option value="transferred" class="bg-slate-900">🔄 Transfer Edilenler</option>
                                </select>
                                <i
                                    class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            </div>

                            <button id="bulkActionsBtn"
                                class="hidden px-5 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:shadow-lg hover:shadow-purple-500/30 transition-all text-xs font-bold whitespace-nowrap active:scale-95">
                                <i class="fa-solid fa-tasks mr-2"></i>Seçilenlerle İşlem Yap
                            </button>
                        </div>

                        <!-- Search -->
                        <div class="relative w-full lg:w-96">
                            <i
                                class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" id="searchInput" placeholder="Site, müşteri veya notlarda ara..."
                                class="w-full pl-11 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-blue-500 text-sm placeholder-slate-500 text-slate-200 transition-all hover:bg-white/10">
                        </div>
                    </div>

                    <div id="sitesTable" class="overflow-x-auto min-h-[400px]">
                        <div class="flex flex-col items-center justify-center py-24 space-y-4">
                            <div
                                class="w-12 h-12 border-4 border-blue-500/20 border-t-blue-500 rounded-full animate-spin">
                            </div>
                            <p class="text-slate-400 font-medium animate-pulse">Siteler yükleniyor...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Context Menu for Right Click -->
    <div id="contextMenu"
        class="hidden fixed glass-card rounded-2xl shadow-2xl border border-white/10 z-50 w-64 overflow-hidden py-1 backdrop-blur-2xl">
        <div class="px-4 py-3 border-b border-white/5 mb-1">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">İşlemler</p>
        </div>
        <button onclick="contextMenuAction('edit')"
            class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition-all">
                <i class="fa-solid fa-edit text-blue-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold">Düzenle</span>
        </button>
        <button onclick="contextMenuAction('status')"
            class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-purple-500/10 flex items-center justify-center group-hover:bg-purple-500/20 transition-all">
                <i class="fa-solid fa-exchange-alt text-purple-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold">Durum Değiştir</span>
        </button>
        <button onclick="contextMenuAction('reminder')"
            class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-yellow-500/10 flex items-center justify-center group-hover:bg-yellow-500/20 transition-all">
                <i class="fa-solid fa-bell text-yellow-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold">Hatırlatma Ekle</span>
        </button>
        <button onclick="contextMenuAction('chat')"
            class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center group-hover:bg-indigo-500/20 transition-all">
                <i class="fa-solid fa-comments text-indigo-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold">Sohbet Geçmişi</span>
        </button>
        <button onclick="contextMenuAction('whatsapp')"
            class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-all">
                <i class="fa-brands fa-whatsapp text-emerald-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold">WhatsApp</span>
        </button>
        <button onclick="contextMenuAction('mail')"
            class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center group-hover:bg-sky-500/20 transition-all">
                <i class="fa-solid fa-envelope text-sky-400 text-sm"></i>
            </div>
            <span class="text-sm font-semibold">Mail Gönder</span>
        </button>
        <div class="my-1 border-t border-white/5"></div>
        <button onclick="contextMenuAction('delete')"
            class="w-full text-left px-4 py-3 hover:bg-red-500/10 flex items-center justify-start gap-3 text-red-400 transition-all group">
            <div
                class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center group-hover:bg-red-500/20 transition-all">
                <i class="fa-solid fa-trash text-red-500 text-sm"></i>
            </div>
            <span class="text-sm font-bold">Sil</span>
        </button>
    </div>

    <!-- Site Modal -->
    <div id="siteModal"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-md hidden items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="glass-card rounded-3xl border border-white/10 w-full max-w-2xl shadow-2xl overflow-hidden scale-in">
            <div
                class="p-6 border-b border-white/10 bg-gradient-to-r from-blue-600/20 to-indigo-600/20 flex items-center justify-between">
                <h3 class="text-xl font-bold font-['Outfit'] text-white flex items-center gap-3" id="modalTitle">
                    <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center">
                        <i class="fa-solid fa-globe text-white text-sm"></i>
                    </div>
                    <span>Yeni Site Ekle</span>
                </h3>
                <button onclick="closeSiteModal()"
                    class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all active:scale-95">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="siteForm" class="p-8 space-y-6">
                <input type="hidden" id="siteId" name="id">
                <input type="hidden" name="action" id="formAction" value="create">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2 space-y-2">
                        <label
                            class="block text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-user text-blue-500"></i>
                            Müşteri *
                            <span class="text-[10px] font-normal text-slate-500 normal-case">(Aramak için yazmaya
                                başlayın)</span>
                        </label>
                        <div class="flex gap-2">
                            <div class="flex-1 relative">
                                <select id="customerId" name="customer_id" required
                                    class="w-full border border-white/10 rounded-xl px-4 py-3 bg-white/5 text-slate-200">
                                    <option value="">Müşteri ara veya seç...</option>
                                </select>
                            </div>
                            <button type="button" onclick="openQuickCustomer()"
                                class="w-12 h-12 bg-emerald-600/20 text-emerald-500 hover:bg-emerald-600 hover:text-white border border-emerald-600/30 rounded-xl transition-all flex items-center justify-center active:scale-95"
                                title="Hızlı Müşteri Ekle">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Domain *</label>
                        <input type="text" name="domain" id="siteDomain" required placeholder="ornek.com"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-blue-500 transition-all">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Yenileme Tarihi
                            *</label>
                        <input type="date" name="renewal_date" id="renewalDate" required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-blue-500 transition-all [color-scheme:dark]">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Paket Tipi
                            *</label>
                        <select name="package_type" id="packageType" required
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-blue-500 transition-all appearance-none cursor-pointer"
                            onchange="updatePrice()">
                            <option value="BASIC" class="bg-slate-900 text-white">BASIC</option>
                            <option value="PRO" class="bg-slate-900 text-white">PRO</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Fiyat
                            (₺)</label>
                        <input type="number" name="price" id="sitePrice" step="0.01"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-blue-500 transition-all">
                    </div>
                    <div id="statusGroup" class="hidden space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Durum</label>
                        <select name="status" id="siteStatus"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-blue-500 transition-all appearance-none cursor-pointer">
                            <option value="active" selected class="bg-slate-900">Aktif</option>
                            <option value="expired" class="bg-slate-900">Süresi Dolmuş</option>
                            <option value="cancelled" class="bg-slate-900">İptal Edildi</option>
                            <option value="transferred" class="bg-slate-900">Transfer</option>
                        </select>
                    </div>
                    <div id="hostingerDates"
                        class="md:col-span-2 hidden bg-blue-500/10 p-4 rounded-2xl border border-blue-500/20">
                        <div
                            class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-server"></i>
                            Hostinger Bilgileri
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold mb-1">Açılış</span>
                                <span id="hostStart" class="text-sm font-semibold text-slate-200">-</span>
                            </div>
                            <div class="bg-white/5 p-3 rounded-xl border border-white/5">
                                <span class="block text-[10px] text-slate-500 uppercase font-bold mb-1">Bitiş</span>
                                <span id="hostExpire" class="text-sm font-semibold text-slate-200">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Notlar</label>
                        <textarea name="notes" id="siteNotes" rows="3" placeholder="Opsiyonel notlar..."
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-blue-500 transition-all"></textarea>
                    </div>
                </div>

                <div class="flex gap-4 pt-6 border-t border-white/10">
                    <button type="button" onclick="closeSiteModal()"
                        class="flex-1 px-6 py-3.5 bg-white/5 text-slate-300 rounded-2xl hover:bg-white/10 transition-all font-bold active:scale-95">
                        İptal
                    </button>
                    <button type="submit"
                        class="flex-1 px-6 py-3.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl hover:shadow-lg hover:shadow-blue-500/30 transition-all font-bold active:scale-95">
                        <i class="fa-solid fa-save mr-2"></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hızlı Müşteri Modal -->
    <div id="quickCustomerModal"
        class="fixed inset-0 bg-slate-950/60 backdrop-blur-md hidden items-center justify-center z-[60] p-4">
        <div class="glass-card rounded-3xl border border-white/10 w-full max-w-md shadow-2xl overflow-hidden scale-in">
            <div
                class="p-6 border-b border-white/10 bg-gradient-to-r from-emerald-600/20 to-teal-600/20 flex items-center justify-between">
                <h3 class="text-xl font-bold font-['Outfit'] text-white flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center">
                        <i class="fa-solid fa-user-plus text-white text-sm"></i>
                    </div>
                    <span>Hızlı Müşteri Ekle</span>
                </h3>
                <button onclick="closeQuickCustomer()"
                    class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all active:scale-95">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="quickCustomerForm" class="p-8 space-y-6">
                <input type="hidden" name="action" value="create">
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Ad Soyad *</label>
                    <input type="text" name="full_name" required placeholder="Ad Soyad giriniz"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-emerald-500 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Firma Adı</label>
                    <input type="text" name="company_name" placeholder="Varsa firma adı"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-emerald-500 transition-all">
                </div>
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Telefon *</label>
                    <input type="text" name="phone" required placeholder="5XXXXXXXXX"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-slate-200 focus:outline-none focus:border-emerald-500 transition-all">
                </div>
                <button type="submit"
                    class="w-full px-6 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl hover:shadow-lg hover:shadow-emerald-500/30 transition-all font-bold active:scale-95">
                    <i class="fa-solid fa-user-plus mr-2"></i>Müşteri Ekle
                </button>
            </form>
        </div>
    </div>

    <!-- Select2 CSS -->
    <link href='https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' rel='stylesheet' />

    <!-- Scripts -->
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script src='assets/js/api-helper.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js'></script>
    <script>
        let packagePrices = { PRO: 5000, BASIC: 2500 };

        $(document).ready(function () {
            initSidebar();
            loadPackagePrices();
            initSelect2();
            // sites.js handles loadSites() and search functionality
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

        function loadPackagePrices() {
            $.get('api/settings.php', function (data) {
                if (data.package_pro_price) packagePrices.PRO = parseFloat(data.package_pro_price);
                if (data.package_basic_price) packagePrices.BASIC = parseFloat(data.package_basic_price);
            });
        }

        function initSelect2() {
            // Destroy existing instance if any
            if ($('#customerId').hasClass("select2-hidden-accessible")) {
                $('#customerId').select2('destroy');
            }

            $('#customerId').select2({
                ajax: {
                    url: 'api/customers.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term || '',
                            action: 'search'
                        };
                    },
                    processResults: function (data) {
                        if (data.results) {
                            return { results: data.results };
                        }
                        return { results: [] };
                    },
                    cache: true
                },
                placeholder: 'Müşteri ara veya seç...',
                allowClear: true,
                minimumInputLength: 0,
                language: {
                    inputTooShort: function () {
                        return 'Müşteri aramak için yazmaya başlayın...';
                    },
                    noResults: function () {
                        return 'Müşteri bulunamadı';
                    },
                    searching: function () {
                        return 'Aranıyor...';
                    }
                },
                dropdownParent: $('#siteModal'),
                width: '100%'
            });
        }
        // filterSites, loadSites, renderSitesTable are now in sites.js

        function openSiteModal() {
            $('#siteForm')[0].reset();
            $('#siteId').val('');
            $('#formAction').val('create');
            $('#modalTitle').text('Yeni Site Ekle');
            $('#statusGroup').addClass('hidden');

            // Reset and reinitialize Select2
            if ($('#customerId').hasClass("select2-hidden-accessible")) {
                $('#customerId').val(null).trigger('change');
            } else {
                initSelect2();
            }

            $('#siteModal').removeClass('hidden').addClass('flex');
        }

        function closeSiteModal() {
            $('#siteModal').addClass('hidden').removeClass('flex');
        }

        // Global function for editing sites (called from sites.js)
        window.editSite = function (id) {
            $.get('api/sites.php', { action: 'get', id: id }, function (res) {
                if (res.status === 'success') {
                    const site = res.data;
                    $('#siteId').val(site.id);
                    $('#formAction').val('update');
                    $('#modalTitle').text('Site Düzenle');

                    // Ensure Select2 is initialized
                    if (!$('#customerId').hasClass("select2-hidden-accessible")) {
                        initSelect2();
                    }

                    // Clear and set customer
                    $('#customerId').empty();
                    const newOption = new Option(site.customer_name, site.customer_id, true, true);
                    $('#customerId').append(newOption).trigger('change');

                    $('#siteDomain').val(site.domain);
                    $('#renewalDate').val(site.renewal_date);
                    $('#packageType').val(site.package_type);
                    $('#sitePrice').val(site.price);
                    $('#siteStatus').val(site.status);
                    $('#siteNotes').val(site.notes);
                    $('#statusGroup').removeClass('hidden');

                    // Show Hostinger dates if available
                    if (site.start_date || site.api_expires_at) {
                        $('#hostStart').text(site.start_date ? new Date(site.start_date).toLocaleDateString('tr-TR') : '-');
                        $('#hostExpire').text(site.api_expires_at ? new Date(site.api_expires_at).toLocaleDateString('tr-TR') : '-');
                        $('#hostingerDates').removeClass('hidden');
                    }

                    $('#siteModal').removeClass('hidden').addClass('flex');
                }
            });
        };

        window.renewSite = function (id) {
            Swal.fire({
                title: 'Site Yenilenecek',
                text: 'Yenileme tarihi 1 yıl ileriye taşınacak. Onaylıyor musunuz?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Yenile',
                cancelButtonText: 'İptal'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('api/sites.php', { action: 'renew', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Yenilendi!', res.message, 'success');
                            loadSites();
                        }
                    });
                }
            });
        };

        window.deleteSite = function (id) {
            Swal.fire({
                title: 'Site Silinecek',
                text: 'Bu işlem geri alınamaz!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'İptal'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('api/sites.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi!', res.message, 'success');
                            loadSites();
                        }
                    });
                }
            });
        };

        // sendWhatsApp fonksiyonu assets/js/sites.js içinde tanımlıdır ve gelişmiş özelliklere sahiptir.
        // Buradaki basit versiyon kaldırıldı.

        function updatePrice() {
            const packageType = $('#packageType').val();
            $('#sitePrice').val(packagePrices[packageType] || 0);
        }

        $('#siteForm').submit(function (e) {
            e.preventDefault();
            const data = $(this).serialize();
            $.post('api/sites.php', data, function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Başarılı!', text: res.message, timer: 2000 });
                    closeSiteModal();
                    loadSites();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        });

        function openQuickCustomer() {
            $('#quickCustomerModal').removeClass('hidden').addClass('flex');
        }

        function closeQuickCustomer() {
            $('#quickCustomerModal').addClass('hidden').removeClass('flex');
        }

        $('#quickCustomerForm').submit(function (e) {
            e.preventDefault();
            $.post('api/customers.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    $('#customerId').append(new Option(res.full_name, res.id, true, true)).trigger('change');
                    closeQuickCustomer();
                    $('#quickCustomerForm')[0].reset();
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Müşteri eklendi', timer: 2000, showConfirmButton: false });
                }
            });
        });

        function exportSites() {
            $.get('api/sites.php', { action: 'list', filter: currentFilter, search: searchQuery }, function (res) {
                if (res.status === 'success' && res.data.length > 0) {
                    const data = res.data.map(s => ({
                        'Domain': s.domain,
                        'Müşteri': s.customer_name,
                        'Yenileme Tarihi': formatDate(s.renewal_date),
                        'Kalan Gün': s.days_until,
                        'Paket': s.package_type,
                        'Fiyat': s.price,
                        'Telefon': s.customer_phone
                    }));
                    const ws = XLSX.utils.json_to_sheet(data);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Siteler');
                    XLSX.writeFile(wb, 'siteler_' + new Date().toISOString().split('T')[0] + '.xlsx');
                }
            });
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
    <style>
        .scale-in {
            animation: scaleIn 0.3s ease-out forwards;
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Select2 Dark Modern Styling */
        .select2-container--default .select2-selection--single {
            height: 48px !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.75rem !important;
            padding: 0.5rem 1rem !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #e2e8f0 !important;
            padding-left: 0 !important;
            font-size: 0.875rem !important;
            font-weight: 500 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px !important;
            right: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #94a3b8 transparent transparent transparent !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #3b82f6 !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        .select2-dropdown {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5) !important;
            padding: 4px !important;
            margin-top: 4px !important;
            overflow: hidden !important;
        }

        .select2-search--dropdown .select2-search__field {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 0.5rem !important;
            color: #f8fafc !important;
            padding: 8px 12px !important;
        }

        .select2-results__option {
            padding: 10px 14px !important;
            border-radius: 0.5rem !important;
            color: #94a3b8 !important;
            font-size: 0.875rem !important;
            margin-bottom: 2px !important;
            transition: all 0.2s !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: rgba(59, 130, 246, 0.2) !important;
            color: #3b82f6 !important;
        }
    </style>
    <!-- jQuery, SweetAlert2, API Helper, Select2 already loaded above -->
    <script src='assets/js/sites.js'></script>
    <script src='assets/js/import-sites.js'></script>
    <script src='assets/js/mobile-long-press.js'></script>
</body>

</html>