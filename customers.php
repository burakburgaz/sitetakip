<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'Müşteriler - DReklam';

// Veritabanına status kolonu ekle (yoksa)
try {
    $pdo->exec("ALTER TABLE customers ADD COLUMN status TEXT DEFAULT 'active' CHECK(status IN ('active', 'passive'))");
} catch (PDOException $e) {
    // Kolon zaten var, devam et
}
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
        --secondary: #10b981;
        --bg-dark: #0f172a;
        --glass-bg: rgba(255, 255, 255, 0.03);
        --glass-border: rgba(255, 255, 255, 0.1);
        --glass-hover: rgba(255, 255, 255, 0.06);
    }



    .glass-header {
        background: rgba(255, 255, 255, 0.8) !important;
        backdrop-filter: blur(10px) !important;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
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

    .stat-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        background: var(--glass-hover) !important;
        border-color: rgba(16, 185, 129, 0.3) !important;
    }

    #customersTable tr {
        transition: all 0.2s ease;
    }

    #customersTable tr:hover {
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

    /* Select2 Dark Theme Styling */
    .select2-container--default .select2-selection--single {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 1.25rem !important;
        height: 56px !important;
        display: flex !important;
        align-items: center !important;
        transition: all 0.3s ease !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: rgba(16, 185, 129, 0.5) !important;
        background: rgba(255, 255, 255, 0.05) !important;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #cbd5e1 !important;
        padding-left: 44px !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #64748b !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 56px !important;
        right: 12px !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #64748b transparent transparent transparent !important;
    }

    .select2-dropdown {
        background: #0f172a !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 1.25rem !important;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6) !important;
        backdrop-filter: blur(20px) !important;
        overflow: hidden !important;
        margin-top: 8px !important;
        padding: 4px !important;
    }

    .select2-search--dropdown {
        padding: 8px !important;
    }

    .select2-search--dropdown .select2-search__field {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        color: white !important;
        padding: 8px 12px !important;
    }

    .select2-results__option {
        color: #94a3b8 !important;
        padding: 10px 14px !important;
        border-radius: 0.75rem !important;
        margin-bottom: 2px !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
    }

    .select2-results__option--highlighted[aria-selected] {
        background: rgba(16, 185, 129, 0.2) !important;
        color: #10b981 !important;
    }

    .select2-results__option[aria-selected=true] {
        background: rgba(16, 185, 129, 0.1) !important;
        color: #10b981 !important;
    }
</style>

<body class="flex h-screen overflow-hidden">
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
    <?php include 'includes/sidebar.php'; ?>

    <div class='flex-1 flex flex-col h-screen overflow-hidden relative z-10'>
        <header class='glass-header z-20 p-4 border-b border-white/10'>
            <div class='flex items-center justify-between max-w-[1600px] mx-auto w-full'>
                <h2 class='text-xl font-bold logo-font text-white flex items-center gap-3'>
                    <div
                        class='w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/30'>
                        <i class='fa-solid fa-users text-emerald-400'></i>
                    </div>
                    <span>Müşteriler</span>
                </h2>
                <div class='flex gap-3'>
                    <button id='bulkActionsBtn'
                        class='hidden px-5 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm rounded-xl hover:shadow-lg hover:shadow-purple-500/30 transition-all font-bold active:scale-95'>
                        <i class='fa-solid fa-tasks mr-2'></i>Toplu İşlemler
                    </button>
                    <button onclick='openCustomerModal()'
                        class='px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm rounded-xl hover:shadow-lg hover:shadow-emerald-500/30 transition-all flex items-center gap-2 font-bold active:scale-95'>
                        <i class='fa-solid fa-user-plus text-xs'></i>Yeni Müşteri Ekle
                    </button>
                </div>
            </div>
        </header>

        <main class='flex-1 overflow-auto p-4 md:p-6 lg:p-8'>
            <div class="max-w-[1600px] mx-auto space-y-6">
                <!-- Main Content Card -->
                <div class='glass-card rounded-2xl border border-white/10 overflow-hidden'>
                    <!-- Toolbar -->
                    <div
                        class='p-6 border-b border-white/10 bg-white/5 flex flex-col lg:flex-row items-center justify-between gap-6'>
                        <!-- Filter -->
                        <div class="w-full lg:w-auto flex flex-col sm:flex-row items-center gap-4">
                            <div class="relative w-full sm:w-56">
                                <i
                                    class="fa-solid fa-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <select id="filterSelect" onchange="filterCustomers(this.value)"
                                    class="w-full pl-10 pr-10 py-3 bg-white/5 border border-white/10 rounded-xl text-sm focus:outline-none focus:border-emerald-500 appearance-none font-semibold text-slate-200 cursor-pointer transition-all hover:bg-white/10">
                                    <option value="all" class="bg-slate-900">👥 Tüm Müşteriler</option>
                                    <option value="active" class="bg-slate-900">✅ Aktif Müşteriler</option>
                                    <option value="passive" class="bg-slate-900">🚫 Pasif Müşteriler</option>
                                </select>
                                <i
                                    class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Search -->
                        <div class="relative w-full lg:w-96">
                            <i
                                class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type='text' id='searchInput' placeholder='Müşteri ara...'
                                class='w-full pl-11 pr-4 py-3 bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:border-emerald-500 text-sm placeholder-slate-500 text-slate-200 transition-all hover:bg-white/10'>
                        </div>
                    </div>

                    <div id='customersTable' class='overflow-x-auto min-h-[400px]'>
                        <div class="flex flex-col items-center justify-center py-24 space-y-4">
                            <div
                                class="w-12 h-12 border-4 border-emerald-500/20 border-t-emerald-500 rounded-full animate-spin">
                            </div>
                            <p class="text-slate-400 font-medium animate-pulse">Müşteriler yükleniyor...</p>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Customers -->
                    <div
                        class="glass-card stat-card p-6 rounded-[2rem] flex items-center justify-between border border-white/10 shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fa-solid fa-users text-7xl text-blue-500"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Toplam Müşteri
                            </p>
                            <h3 class="text-3xl font-bold text-white" id="statTotal">...</h3>
                        </div>
                        <div
                            class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center border border-blue-500/20 relative z-10">
                            <i class="fa-solid fa-users text-blue-400"></i>
                        </div>
                    </div>

                    <!-- Active Customers -->
                    <div
                        class="glass-card stat-card p-6 rounded-[2rem] flex items-center justify-between border border-white/10 shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fa-solid fa-user-check text-7xl text-emerald-500"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Aktif Müşteriler
                            </p>
                            <h3 class="text-3xl font-bold text-emerald-400" id="statActive">...</h3>
                        </div>
                        <div
                            class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/20 relative z-10">
                            <i class="fa-solid fa-user-check text-emerald-400"></i>
                        </div>
                    </div>

                    <!-- Passive Customers -->
                    <div
                        class="glass-card stat-card p-6 rounded-[2rem] flex items-center justify-between border border-white/10 shadow-2xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <i class="fa-solid fa-user-xmark text-7xl text-red-500"></i>
                        </div>
                        <div class="relative z-10">
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Pasif Müşteriler
                            </p>
                            <h3 class="text-3xl font-bold text-red-400" id="statPassive">...</h3>
                        </div>
                        <div
                            class="w-12 h-12 bg-red-500/10 rounded-2xl flex items-center justify-center border border-red-500/20 relative z-10">
                            <i class="fa-solid fa-user-xmark text-red-400"></i>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Context Menu -->
    <div id='contextMenu'
        class='hidden fixed glass-card rounded-2xl shadow-2xl border border-white/10 z-[100] w-64 overflow-hidden py-1 backdrop-blur-2xl'>
        <div class="px-4 py-3 border-b border-white/5 mb-1">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Müşteri İşlemleri</p>
        </div>
        <button onclick='contextMenuAction("whatsapp")'
            class='w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group'>
            <div
                class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-all">
                <i class='fa-brands fa-whatsapp text-emerald-400 text-sm'></i>
            </div>
            <span class="text-sm font-semibold">WhatsApp Gönder</span>
        </button>
        <button onclick='contextMenuAction("toggle_status")'
            class='w-full text-left px-4 py-3 hover:bg-white/5 flex items-center justify-start gap-3 text-slate-300 transition-all group'>
            <div
                class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-500/20 transition-all">
                <i class='fa-solid fa-toggle-on text-blue-400 text-sm'></i>
            </div>
            <span id='statusToggleText' class="text-sm font-semibold">Pasif Yap</span>
        </button>
        <div class="my-1 border-t border-white/5"></div>
        <button onclick='contextMenuAction("delete")'
            class='w-full text-left px-4 py-3 hover:bg-red-500/10 flex items-center justify-start gap-3 text-red-400 transition-all group'>
            <div
                class="w-8 h-8 rounded-lg bg-red-500/10 flex items-center justify-center group-hover:bg-red-500/20 transition-all">
                <i class='fa-solid fa-trash text-red-500 text-sm'></i>
            </div>
            <span class="text-sm font-bold">Sil</span>
        </button>
    </div>

    <!-- WhatsApp Template Modal -->
    <div id='whatsappModal'
        class='fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-50 p-4 overflow-y-auto transition-all duration-300'>
        <div
            class='glass-card rounded-[2.5rem] border border-white/10 w-full max-w-3xl shadow-[0_0_100px_-20px_rgba(16,185,129,0.3)] overflow-hidden scale-in relative'>

            <!-- Header Section -->
            <div
                class='p-8 pb-6 border-b border-white/5 bg-gradient-to-br from-emerald-500/10 via-transparent to-teal-500/10 flex items-center justify-between relative'>
                <div class="flex items-center gap-5">
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 rotate-3 group-hover:rotate-0 transition-transform">
                        <i class='fa-brands fa-whatsapp text-white text-xl'></i>
                    </div>
                    <div>
                        <h3 class='text-2xl font-black logo-font text-white tracking-tight'>WhatsApp Mesajı</h3>
                        <p class="text-slate-400 text-sm font-medium mt-1">Müşterinize hızlıca ulaşın</p>
                    </div>
                </div>
                <button onclick='closeWhatsappModal()'
                    class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 hover:border-white/20 transition-all active:scale-95 group">
                    <i class='fa-solid fa-times text-lg group-hover:rotate-90 transition-transform'></i>
                </button>
            </div>

            <div class='p-8'>
                <div class='space-y-8'>
                    <!-- Müşteri Bilgileri -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-emerald-500">Müşteri Bilgileri
                            </h4>
                        </div>
                        <div
                            class='grid grid-cols-1 md:grid-cols-2 gap-6 bg-white/[0.02] p-6 rounded-[2rem] border border-white/5'>
                            <div class="space-y-2 group">
                                <label class='block text-xs font-bold text-slate-500 ml-1'>Ad Soyad</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' id='waCustomerName' readonly
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-emerald-500/50 transition-all cursor-default opacity-70'>
                                </div>
                            </div>
                            <div class="space-y-2 group">
                                <label class='block text-xs font-bold text-slate-500 ml-1'>Telefon</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' id='waCustomerPhone' readonly
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-emerald-500/50 transition-all cursor-default opacity-70'>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Müşteri Siteleri -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-blue-500">Müşteri Siteleri
                            </h4>
                        </div>
                        <div id='waCustomerSites'
                            class='bg-white/[0.02] p-4 rounded-2xl border border-white/5 max-h-32 overflow-y-auto flex flex-wrap gap-2'>
                            <p class='text-xs text-slate-500 font-bold italic animate-pulse'>Yükleniyor...</p>
                        </div>
                        <p class='text-[10px] text-slate-500 font-bold uppercase tracking-widest pl-2'>Mesajda bahsetmek
                            istediğiniz siteleri seçin</p>
                    </div>

                    <!-- Mesaj Şablonu -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-purple-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-purple-500">Mesaj Şablonu</h4>
                        </div>
                        <div class="relative group">
                            <i
                                class="fa-solid fa-file-alt absolute left-4 top-1/2 -translate-y-1/2 text-purple-400 text-sm"></i>
                            <select id='whatsappTemplate'
                                class='w-full pl-11 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-purple-500/50 appearance-none transition-all cursor-pointer'
                                onchange='updateWhatsappPreview()'>
                                <option value='reminder' class="bg-slate-900">🔔 Hatırlatma Mesajı</option>
                                <option value='renewal' class="bg-slate-900">⚡ Yenileme Mesajı</option>
                                <option value='custom' class="bg-slate-900">✍️ Özel Mesaj</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Mesaj Önizleme -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Mesaj Önizleme</h4>
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-pen-to-square absolute left-4 top-4 text-slate-500 text-sm"></i>
                            <textarea id='whatsappMessage' rows='8'
                                class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-[1.5rem] text-white text-sm focus:outline-none focus:border-slate-500/50 transition-all font-mono resize-none'></textarea>
                        </div>
                        <div
                            class='flex items-center gap-2 pl-2 text-[10px] text-slate-500 font-bold uppercase tracking-widest'>
                            <i class='fa-solid fa-circle-info text-blue-500'></i>
                            <span>Müşteri adı ve firma bilgileri otomatik eklenecektir</span>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class='flex gap-4 pt-4'>
                        <button onclick='closeWhatsappModal()'
                            class='flex-1 h-14 bg-white/5 border border-white/10 text-white rounded-2xl hover:bg-white/10 transition-all font-bold logo-font text-sm active:scale-95'>
                            İptal
                        </button>
                        <button onclick='sendWhatsAppMessage()'
                            class='flex-[2] h-14 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-2xl hover:shadow-[0_10px_30px_-5px_rgba(16,185,129,0.4)] transition-all font-black logo-font text-sm active:scale-95 flex items-center justify-center gap-2'>
                            <i class='fa-brands fa-whatsapp text-lg'></i>WhatsApp'ta Aç
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Müşteri Modal -->
    <div id='customerModal'
        class='fixed inset-0 bg-slate-950/80 backdrop-blur-xl hidden items-center justify-center z-50 p-4 overflow-y-auto transition-all duration-300'>
        <div
            class='glass-card rounded-[2.5rem] border border-white/10 w-full max-w-3xl shadow-[0_0_100px_-20px_rgba(16,185,129,0.3)] overflow-hidden scale-in relative'>

            <!-- Header Section -->
            <div
                class='p-8 pb-6 border-b border-white/5 bg-gradient-to-br from-emerald-500/10 via-transparent to-teal-500/10 flex items-center justify-between relative'>
                <div class="flex items-center gap-5">
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/20 rotate-3 group-hover:rotate-0 transition-transform">
                        <i class='fa-solid fa-user-plus text-white text-xl'></i>
                    </div>
                    <div>
                        <h3 class='text-2xl font-black logo-font text-white tracking-tight' id='modalTitle'>Yeni Müşteri
                            Ekle</h3>
                        <p class="text-slate-400 text-sm font-medium mt-1">Sistemdeki müşteri ağınızı genişletin</p>
                    </div>
                </div>
                <button onclick='closeCustomerModal()'
                    class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 hover:border-white/20 transition-all active:scale-95 group">
                    <i class='fa-solid fa-times text-lg group-hover:rotate-90 transition-transform'></i>
                </button>
            </div>

            <form id='customerForm' class='p-8 space-y-8'>
                <input type='hidden' id='customerId' name='id'>
                <input type='hidden' name='action' id='formAction' value='create'>

                <div class='space-y-8'>
                    <!-- Group 1: Basic Info -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-emerald-500">Temel Bilgiler
                            </h4>
                        </div>
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div class="space-y-2 group">
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-emerald-400 transition-colors'>Ad
                                    Soyad *</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-user absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' name='full_name' id='fullName' required
                                        placeholder="Müşteri adını girin"
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 placeholder-slate-600 transition-all'>
                                </div>
                            </div>
                            <div class="space-y-2 group">
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-emerald-400 transition-colors'>Firma
                                    Adı</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-building absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' name='company_name' id='companyName'
                                        placeholder="Varsa firma adı"
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 placeholder-slate-600 transition-all'>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 2: Contact Info -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-blue-500">İletişim & Konum
                            </h4>
                        </div>
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-6'>
                            <div class="space-y-2 group">
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-blue-400 transition-colors'>Telefon
                                    *</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-phone absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' name='phone' id='phone' required placeholder='5XXXXXXXXX'
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 placeholder-slate-600 transition-all'>
                                </div>
                            </div>
                            <div class="space-y-2 group">
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-blue-400 transition-colors'>E-posta</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='email' name='email' id='email' placeholder="example@mail.com"
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 placeholder-slate-600 transition-all'>
                                </div>
                            </div>
                            <div class="space-y-2 group">
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-blue-400 transition-colors'>Şehir</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-city absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' name='city' id='city' placeholder="Şehir girin"
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 placeholder-slate-600 transition-all'>
                                </div>
                            </div>
                            <div class="space-y-2 group">
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-emerald-400 transition-colors'>Durum</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-toggle-on absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <select name='status' id='customerStatus'
                                        class='w-full pl-11 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 appearance-none transition-all cursor-pointer'>
                                        <option value='active' class="bg-slate-900 text-white">✅ Aktif Müşteri</option>
                                        <option value='passive' class="bg-slate-900 text-white">🚫 Pasif Müşteri
                                        </option>
                                    </select>
                                    <i
                                        class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-xs pointer-events-none"></i>
                                </div>
                            </div>
                            <div class='md:col-span-2 space-y-2 group'>
                                <label
                                    class='block text-xs font-bold text-slate-400 ml-1 group-focus-within:text-blue-400 transition-colors'>Adres</label>
                                <div class="relative">
                                    <i
                                        class="fa-solid fa-location-dot absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm"></i>
                                    <input type='text' name='address' id='address' placeholder="Detaylı adres bilgisi"
                                        class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-blue-500/50 focus:ring-4 focus:ring-blue-500/10 placeholder-slate-600 transition-all'>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Group 3: Sites -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-purple-500 rounded-full"></span>
                            <div class="flex items-center gap-2">
                                <h4 class="text-xs font-black uppercase tracking-[0.2em] text-purple-500">Site
                                    İlişkilendirme</h4>
                                <span
                                    class="text-[10px] bg-purple-500/10 text-purple-400 px-2 py-0.5 rounded-full border border-purple-500/20 font-bold uppercase tracking-wider">Opsiyonel</span>
                            </div>
                        </div>
                        <div class="bg-white/5 border border-white/10 rounded-[2rem] p-6 space-y-4">
                            <div class='relative group/select'>
                                <i
                                    class="fa-solid fa-globe absolute left-4 top-1/2 -translate-y-1/2 text-purple-400/70 text-sm z-10"></i>
                                <select id='addSiteDropdown'
                                    class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm focus:outline-none focus:border-purple-500/50 transition-all'>
                                    <option value=''>Site seçerek ekleyin...</option>
                                </select>
                            </div>

                            <div id='selectedSitesContainer'
                                class='min-h-[80px] border-2 border-dashed border-white/5 rounded-2xl p-4 transition-all bg-white/[0.02]'>
                                <div id='selectedSitesTags' class='flex flex-wrap gap-3'>
                                    <div class="w-full flex flex-col items-center justify-center py-4 opacity-30 select-none"
                                        id='noSitesText'>
                                        <i class="fa-solid fa-cloud-moon text-2xl mb-2"></i>
                                        <span class='text-xs font-bold italic uppercase tracking-widest'>Bağlı site
                                            bulunamadı</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden input for form submission -->
                            <input type='hidden' id='customerSitesHidden' name='sites'>
                        </div>
                    </div>

                    <!-- Group 4: Notes -->
                    <div class="space-y-4 text-left">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-1.5 h-6 bg-slate-500 rounded-full"></span>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Notlar</h4>
                        </div>
                        <div class="relative">
                            <i class="fa-solid fa-pen-to-square absolute left-4 top-4 text-slate-500 text-sm"></i>
                            <textarea name='notes' id='notes' rows='4' placeholder="Müşteri hakkında kısa notlar..."
                                class='w-full pl-11 pr-4 py-4 bg-white/5 border border-white/10 rounded-[1.5rem] text-white text-sm focus:outline-none focus:border-slate-500/50 focus:ring-4 focus:ring-slate-500/10 placeholder-slate-600 transition-all resize-none'></textarea>
                        </div>
                    </div>
                </div>

                <div class='flex gap-4 pt-6'>
                    <button type='button' onclick='closeCustomerModal()'
                        class='flex-1 h-14 bg-white/5 border border-white/10 text-white rounded-2xl hover:bg-white/10 transition-all font-bold logo-font text-sm active:scale-95 shadow-lg shadow-black/20'>
                        Vazgeç
                    </button>
                    <button type='submit'
                        class='flex-[2] h-14 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-2xl hover:shadow-[0_10px_30px_-5px_rgba(16,185,129,0.4)] transition-all font-black logo-font text-sm active:scale-95 flex items-center justify-center gap-2'>
                        <i class='fa-solid fa-save'></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script src='https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js'></script>
    <link href='https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' rel='stylesheet' />
    <script src='assets/js/sidebar.js'></script>
    <script src='assets/js/customers.js'></script>
    <script src='assets/js/mobile-long-press.js'></script>
</body>

</html>