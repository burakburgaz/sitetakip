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

<body class='bg-gray-50 flex h-screen overflow-hidden'>
    <?php include 'includes/sidebar.php'; ?>

    <div class='flex-1 flex flex-col h-screen overflow-hidden'>
        <header class='bg-white shadow-sm z-10 p-4 border-b border-gray-200'>
            <div class='flex items-center justify-between'>
                <h2 class='text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2'>
                    <i class='fa-solid fa-users text-green-600'></i> Müşteriler
                </h2>
                <div class='flex gap-2'>
                    <button id='bulkActionsBtn'
                        class='px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition hidden'>
                        <i class='fa-solid fa-tasks mr-2'></i>Toplu İşlemler
                    </button>
                    <button onclick='openCustomerModal()'
                        class='px-4 py-2 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-lg hover:from-green-700 hover:to-teal-700 transition'>
                        <i class='fa-solid fa-user-plus mr-2'></i>Yeni Müşteri Ekle
                    </button>
                </div>
            </div>
        </header>

        <main class='flex-1 overflow-auto p-6'>
            <!-- Main Content Card -->
            <div class='bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden'>
                <!-- Toolbar -->
                <div
                    class='p-5 border-b border-gray-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-4'>
                    <!-- Filter -->
                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-56">
                            <select id="filterSelect" onchange="filterCustomers(this.value)"
                                class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all appearance-none cursor-pointer font-medium hover:bg-gray-100">
                                <option value="all">Tüm Müşteriler</option>
                                <option value="active">Aktif Müşteriler</option>
                                <option value="passive">Pasif Müşteriler</option>
                            </select>
                            <i
                                class="fa-solid fa-filter absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            <i
                                class="fa-solid fa-chevron-down absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="relative w-full sm:w-72">
                        <input type='text' id='searchInput' placeholder='Müşteri ara...'
                            class='w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all'>
                        <i class="fa-solid fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <div id='customersTable' class='overflow-x-auto'>
                    <div class='text-center py-12'>
                        <div class='spinner mx-auto'></div>
                        <p class='mt-4 text-gray-600'>Yükleniyor...</p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards (Moved to Bottom) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <!-- Total Customers -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Toplam Müşteri</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="statTotal">...</h3>
                    </div>
                    <div
                        class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 shadow-sm">
                        <i class="fa-solid fa-users text-xl"></i>
                    </div>
                </div>

                <!-- Active Customers -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Aktif Müşteriler</p>
                        <h3 class="text-2xl font-bold text-green-600" id="statActive">...</h3>
                    </div>
                    <div
                        class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center text-green-600 shadow-sm">
                        <i class="fa-solid fa-user-check text-xl"></i>
                    </div>
                </div>

                <!-- Passive Customers -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 mb-1">Pasif Müşteriler</p>
                        <h3 class="text-2xl font-bold text-red-600" id="statPassive">...</h3>
                    </div>
                    <div
                        class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-red-600 shadow-sm">
                        <i class="fa-solid fa-user-xmark text-xl"></i>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Context Menu -->
    <div id='contextMenu'
        class='hidden fixed bg-white shadow-2xl rounded-lg py-2 z-[100] border border-gray-200 min-w-[200px]'>
        <button onclick='contextMenuAction("whatsapp")'
            class='w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-3'>
            <i class='fa-brands fa-whatsapp text-green-600'></i>
            <span>WhatsApp Gönder</span>
        </button>
        <button onclick='contextMenuAction("toggle_status")'
            class='w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center gap-3'>
            <i class='fa-solid fa-toggle-on text-blue-600'></i>
            <span id='statusToggleText'>Pasif Yap</span>
        </button>
        <hr class='my-1'>
        <button onclick='contextMenuAction("delete")'
            class='w-full text-left px-4 py-2 hover:bg-red-50 flex items-center gap-3 text-red-600'>
            <i class='fa-solid fa-trash'></i>
            <span>Sil</span>
        </button>
    </div>

    <!-- WhatsApp Template Modal -->
    <div id='whatsappModal'
        class='fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop'>
        <div class='bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto'>
            <div class='bg-gradient-to-r from-green-600 to-teal-600 text-white p-6 rounded-t-2xl'>
                <div class='flex items-center justify-between'>
                    <h3 class='text-xl font-bold'><i class='fa-brands fa-whatsapp mr-2'></i>WhatsApp Mesajı Gönder</h3>
                    <button onclick='closeWhatsappModal()' class='text-white hover:text-gray-200'>
                        <i class='fa-solid fa-times text-2xl'></i>
                    </button>
                </div>
            </div>
            <div class='p-6'>
                <div class='space-y-5'>
                    <!-- Müşteri Bilgileri -->
                    <div class='bg-gray-50 rounded-lg p-4 border border-gray-200'>
                        <h4 class='text-sm font-bold text-gray-700 mb-3 flex items-center gap-2'>
                            <i class='fa-solid fa-user text-indigo-600'></i>
                            Müşteri Bilgileri
                        </h4>
                        <div class='grid grid-cols-1 md:grid-cols-2 gap-3'>
                            <div>
                                <label class='block text-xs font-semibold text-gray-600 mb-1'>Ad Soyad</label>
                                <input type='text' id='waCustomerName'
                                    class='w-full border rounded-lg px-3 py-2 text-sm'>
                            </div>
                            <div>
                                <label class='block text-xs font-semibold text-gray-600 mb-1'>Telefon</label>
                                <input type='text' id='waCustomerPhone'
                                    class='w-full border rounded-lg px-3 py-2 text-sm'>
                            </div>
                        </div>
                    </div>

                    <!-- Müşteri Siteleri -->
                    <div class='bg-blue-50 rounded-lg p-4 border border-blue-200'>
                        <h4 class='text-sm font-bold text-gray-700 mb-3 flex items-center gap-2'>
                            <i class='fa-solid fa-globe text-blue-600'></i>
                            Müşteri Siteleri
                        </h4>
                        <div id='waCustomerSites' class='max-h-32 overflow-y-auto'>
                            <p class='text-xs text-gray-500'>Yükleniyor...</p>
                        </div>
                        <p class='text-xs text-gray-500 mt-2'>Mesajda bahsetmek istediğiniz siteleri seçin</p>
                    </div>

                    <!-- Mesaj Şablonu -->
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>
                            <i class='fa-solid fa-file-alt text-green-600 mr-1'></i>
                            Mesaj Şablonu
                        </label>
                        <select id='whatsappTemplate' class='w-full border rounded-lg px-4 py-2'
                            onchange='updateWhatsappPreview()'>
                            <option value='reminder'>Hatırlatma Mesajı</option>
                            <option value='renewal'>Yenileme Mesajı</option>
                            <option value='custom'>Özel Mesaj</option>
                        </select>
                    </div>

                    <!-- Mesaj Önizleme -->
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Mesaj Önizleme</label>
                        <textarea id='whatsappMessage' rows='8'
                            class='w-full border rounded-lg px-4 py-2 text-sm font-mono bg-gray-50'></textarea>
                        <div class='flex items-start gap-2 mt-2 text-xs text-gray-500'>
                            <i class='fa-solid fa-info-circle mt-0.5'></i>
                            <p>Müşteri adı, firma ve seçili siteler otomatik eklenecektir</p>
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class='flex gap-3 pt-2'>
                        <button onclick='closeWhatsappModal()'
                            class='flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium'>
                            İptal
                        </button>
                        <button onclick='sendWhatsAppMessage()'
                            class='flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-lg hover:from-green-700 hover:to-teal-700 transition font-bold'>
                            <i class='fa-brands fa-whatsapp mr-2'></i>WhatsApp'ta Aç
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Müşteri Modal -->
    <div id='customerModal'
        class='fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop'>
        <div class='bg-white rounded-2xl shadow-2xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto'>
            <div class='sticky top-0 bg-gradient-to-r from-green-600 to-teal-600 text-white p-6 rounded-t-2xl'>
                <div class='flex items-center justify-between'>
                    <h3 class='text-xl font-bold' id='modalTitle'>Yeni Müşteri Ekle</h3>
                    <button onclick='closeCustomerModal()' class='text-white hover:text-gray-200'>
                        <i class='fa-solid fa-times text-2xl'></i>
                    </button>
                </div>
            </div>
            <form id='customerForm' class='p-6 space-y-4'>
                <input type='hidden' id='customerId' name='id'>
                <input type='hidden' name='action' id='formAction' value='create'>

                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Ad Soyad *</label>
                        <input type='text' name='full_name' id='fullName' required
                            class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Firma Adı</label>
                        <input type='text' name='company_name' id='companyName'
                            class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Telefon *</label>
                        <input type='text' name='phone' id='phone' required placeholder='5XXXXXXXXX'
                            class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>E-posta</label>
                        <input type='email' name='email' id='email' class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Şehir</label>
                        <input type='text' name='city' id='city' class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Durum</label>
                        <select name='status' id='customerStatus' class='w-full border rounded-lg px-4 py-2'>
                            <option value='active'>Aktif</option>
                            <option value='passive'>Pasif</option>
                        </select>
                    </div>
                    <div class='md:col-span-2'>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Adres</label>
                        <input type='text' name='address' id='address' class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div class='md:col-span-2'>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>
                            <i class='fa-solid fa-globe text-indigo-600 mr-1'></i>
                            Site İlişkilendirme
                        </label>

                        <!-- Site ekleme dropdown -->
                        <div class='mb-3'>
                            <select id='addSiteDropdown' class='w-full border rounded-lg px-4 py-2 bg-white'>
                                <option value=''>Site seçerek ekleyin...</option>
                            </select>
                        </div>

                        <!-- Seçili siteler - tag görünümü -->
                        <div id='selectedSitesContainer'
                            class='min-h-[60px] border-2 border-dashed border-gray-300 rounded-lg p-3 bg-gray-50'>
                            <div id='selectedSitesTags' class='flex flex-wrap gap-2'>
                                <span class='text-gray-400 text-sm' id='noSitesText'>Henüz site eklenmedi</span>
                            </div>
                        </div>

                        <!-- Hidden input for form submission -->
                        <input type='hidden' id='customerSitesHidden' name='sites'>
                    </div>
                    <div class='md:col-span-2'>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Notlar</label>
                        <textarea name='notes' id='notes' rows='3'
                            class='w-full border rounded-lg px-4 py-2'></textarea>
                    </div>
                </div>

                <div class='flex gap-3 pt-4'>
                    <button type='button' onclick='closeCustomerModal()'
                        class='flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium'>
                        İptal
                    </button>
                    <button type='submit'
                        class='flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-lg hover:from-green-700 hover:to-teal-700 transition font-bold'>
                        <i class='fa-solid fa-save mr-2'></i>Kaydet
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