<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'Siteler - DReklam';
?>
<?php include 'includes/head.php'; ?>

<body class='bg-gray-50 flex h-screen overflow-hidden'>
    <?php include 'includes/sidebar.php'; ?>

    <div class='flex-1 flex flex-col h-screen overflow-hidden'>
        <header class='bg-white shadow-sm z-10 p-3 border-b border-gray-200'>
            <div class='flex items-center justify-between'>
                <h2 class='text-lg font-bold text-gray-800 flex items-center gap-2'>
                    <i class='fa-solid fa-globe text-indigo-600'></i> Siteler
                </h2>
                <div class='flex items-center gap-2'>
                    <div class="flex bg-gray-100 rounded-lg p-1">
                        <button onclick='importSites()' title='Excel İçe Aktar'
                            class='p-2 text-gray-600 hover:text-blue-600 hover:bg-white rounded-md transition'>
                            <i class='fa-solid fa-file-import'></i>
                        </button>
                        <button onclick='exportSites()' title='Excel Dışa Aktar'
                            class='p-2 text-gray-600 hover:text-green-600 hover:bg-white rounded-md transition'>
                            <i class='fa-solid fa-file-excel'></i>
                        </button>
                    </div>
                    <button onclick='openSiteModal()'
                        class='px-3 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition flex items-center gap-1 font-medium'>
                        <i class='fa-solid fa-plus'></i><span class="hidden sm:inline">Yeni Ekle</span>
                    </button>
                </div>
            </div>
        </header>

        <main class='flex-1 overflow-auto p-4'>
            <div class='bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden'>
                <div
                    class='p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row items-center justify-between gap-4'>
                    <!-- Yeni Nesil Filtreleme (Dropdown) -->
                    <div class="w-full sm:w-auto flex items-center gap-3">
                        <div class="relative w-full sm:w-64">
                            <i
                                class="fa-solid fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <select id="filterSelect" onchange="filterSites(this.value)"
                                class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500 appearance-none font-medium text-gray-700">
                                <option value="upcoming" selected>📅 Yaklaşan (30 gün)</option>
                                <option value="all">📋 Tüm Siteler</option>
                                <option value="active">✅ Aktif</option>
                                <option value="expired">⚠️ Süresi Dolmuş</option>
                                <option value="cancelled">🚫 İptal Edilenler</option>
                                <option value="transferred">🔄 Transfer Edilenler</option>
                            </select>
                            <i
                                class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>

                        <button id='bulkActionsBtn'
                            class='hidden px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-xs font-bold whitespace-nowrap'>
                            <i class='fa-solid fa-tasks mr-1'></i>İşlemler
                        </button>
                    </div>

                    <!-- Arama -->
                    <div class="relative w-full sm:w-64">
                        <i
                            class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type='text' id='searchInput' placeholder='Ara...'
                            class='w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm'>
                    </div>
                </div>

                <div id='sitesTable' class='overflow-x-auto'>
                    <div class='text-center py-12'>
                        <div class='spinner mx-auto'></div>
                        <p class='mt-4 text-gray-600'>Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Context Menu for Right Click -->
    <div id='contextMenu' class='hidden fixed bg-white rounded-lg shadow-2xl border border-gray-200 z-50 w-56'>
        <button onclick='contextMenuAction("edit")'
            class='w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center justify-start gap-2 text-gray-700 rounded-t-lg transition'>
            <i class='fa-solid fa-edit text-blue-600 w-4'></i>
            <span>Düzenle</span>
        </button>
        <button onclick='contextMenuAction("status")'
            class='w-full text-left px-4 py-3 hover:bg-purple-50 flex items-center justify-start gap-2 text-gray-700 transition'>
            <i class='fa-solid fa-exchange-alt text-purple-600 w-4'></i>
            <span>Durum Değiştir</span>
        </button>
        <button onclick='contextMenuAction("reminder")'
            class='w-full text-left px-4 py-3 hover:bg-yellow-50 flex items-center justify-start gap-2 text-gray-700 transition'>
            <i class='fa-solid fa-bell text-yellow-600 w-4'></i>
            <span>Hatırlatma Ekle</span>
        </button>
        <button onclick='contextMenuAction("chat")'
            class='w-full text-left px-4 py-3 hover:bg-indigo-50 flex items-center justify-start gap-2 text-gray-700 transition'>
            <i class='fa-solid fa-comments text-indigo-600 w-4'></i>
            <span>Sohbet Geçmişi</span>
        </button>
        <button onclick='contextMenuAction("whatsapp")'
            class='w-full text-left px-4 py-3 hover:bg-green-50 flex items-center justify-start gap-2 text-gray-700 transition'>
            <i class='fa-brands fa-whatsapp text-green-600 w-4'></i>
            <span>WhatsApp</span>
        </button>
        <button onclick='contextMenuAction("mail")'
            class='w-full text-left px-4 py-3 hover:bg-blue-50 flex items-center justify-start gap-2 text-gray-700 transition'>
            <i class='fa-solid fa-envelope text-blue-600 w-4'></i>
            <span>Mail Gönder</span>
        </button>
        <hr class='my-1'>
        <button onclick='contextMenuAction("delete")'
            class='w-full text-left px-4 py-3 hover:bg-red-50 flex items-center justify-start gap-2 text-red-600 rounded-b-lg transition'>
            <i class='fa-solid fa-trash w-4'></i>
            <span>Sil</span>
        </button>
    </div>

    <!-- Site Modal -->
    <div id='siteModal'
        class='fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop'>
        <div class='bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto'>
            <div class='sticky top-0 bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6 rounded-t-2xl'>
                <div class='flex items-center justify-between'>
                    <h3 class='text-xl font-bold' id='modalTitle'>Yeni Site Ekle</h3>
                    <button onclick='closeSiteModal()' class='text-white hover:text-gray-200'>
                        <i class='fa-solid fa-times text-2xl'></i>
                    </button>
                </div>
            </div>
            <form id='siteForm' class='p-6 space-y-4'>
                <input type='hidden' id='siteId' name='id'>
                <input type='hidden' name='action' id='formAction' value='create'>

                <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
                    <div class='md:col-span-2'>
                        <label class='block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2'>
                            <i class='fa-solid fa-user text-indigo-600'></i>
                            Müşteri *
                            <span class="text-xs font-normal text-gray-500">(Aramak için yazmaya başlayın)</span>
                        </label>
                        <div class='flex gap-2'>
                            <div class="flex-1 relative">
                                <select id='customerId' name='customer_id' required
                                    class='w-full border rounded-lg px-4 py-2'>
                                    <option value=''>Müşteri ara veya seç...</option>
                                </select>
                            </div>
                            <button type='button' onclick='openQuickCustomer()'
                                class='px-4 py-2 bg-green-600 text-white hover:bg-green-700 rounded-lg transition font-medium'
                                title='Hızlı Müşteri Ekle'>
                                <i class='fa-solid fa-user-plus'></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fa-solid fa-search mr-1"></i>
                            Müşteri adı veya firma adına göre arayabilirsiniz
                        </p>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Domain *</label>
                        <input type='text' name='domain' id='siteDomain' required placeholder='ornek.com'
                            class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Yenileme Tarihi *</label>
                        <input type='date' name='renewal_date' id='renewalDate' required
                            class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Paket Tipi *</label>
                        <select name='package_type' id='packageType' required class='w-full border rounded-lg px-4 py-2'
                            onchange='updatePrice()'>
                            <option value='BASIC'>BASIC</option>
                            <option value='PRO'>PRO</option>
                        </select>
                    </div>
                    <div>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Fiyat (₺)</label>
                        <input type='number' name='price' id='sitePrice' step='0.01'
                            class='w-full border rounded-lg px-4 py-2'>
                    </div>
                    <div id='statusGroup' class='hidden'>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Durum</label>
                        <select name='status' id='siteStatus' class='w-full border rounded-lg px-4 py-2'>
                            <option value='active' selected>Aktif</option>
                            <option value='expired'>Süresi Dolmuş</option>
                            <option value='cancelled'>İptal Edildi</option>
                            <option value='transferred'>Transfer</option>
                        </select>
                    </div>
                    <div id='hostingerDates' class='md:col-span-2 hidden bg-blue-50 p-3 rounded-lg'>
                        <div class='text-xs font-semibold text-blue-800 mb-2'><i class='fa-solid fa-server mr-1'></i>
                            Hostinger Bilgileri</div>
                        <div class='grid grid-cols-2 gap-2 text-xs'>
                            <div><span class='text-gray-600'>Açılış:</span> <span id='hostStart'
                                    class='font-medium'>-</span></div>
                            <div><span class='text-gray-600'>Bitiş:</span> <span id='hostExpire'
                                    class='font-medium'>-</span></div>
                        </div>
                    </div>
                    <div class='md:col-span-2'>
                        <label class='block text-sm font-semibold text-gray-700 mb-2'>Notlar</label>
                        <textarea name='notes' id='siteNotes' rows='3'
                            class='w-full border rounded-lg px-4 py-2'></textarea>
                    </div>
                </div>

                <div class='flex gap-3 pt-4'>
                    <button type='button' onclick='closeSiteModal()'
                        class='flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium'>
                        İptal
                    </button>
                    <button type='submit'
                        class='flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-bold'>
                        <i class='fa-solid fa-save mr-2'></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hızlı Müşteri Modal -->
    <div id='quickCustomerModal'
        class='fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-[60] modal-backdrop'>
        <div class='bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4'>
            <div class='bg-gradient-to-r from-green-600 to-teal-600 text-white p-6 rounded-t-2xl'>
                <div class='flex items-center justify-between'>
                    <h3 class='text-xl font-bold'>Hızlı Müşteri Ekle</h3>
                    <button onclick='closeQuickCustomer()' class='text-white hover:text-gray-200'>
                        <i class='fa-solid fa-times text-2xl'></i>
                    </button>
                </div>
            </div>
            <form id='quickCustomerForm' class='p-6 space-y-4'>
                <input type='hidden' name='action' value='create'>
                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Ad Soyad *</label>
                    <input type='text' name='full_name' required class='w-full border rounded-lg px-4 py-2'>
                </div>
                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Firma Adı</label>
                    <input type='text' name='company_name' class='w-full border rounded-lg px-4 py-2'>
                </div>
                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Telefon *</label>
                    <input type='text' name='phone' required placeholder='5XXXXXXXXX'
                        class='w-full border rounded-lg px-4 py-2'>
                </div>
                <button type='submit'
                    class='w-full px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 text-white rounded-lg hover:from-green-700 hover:to-teal-700 transition font-bold'>
                    <i class='fa-solid fa-user-plus mr-2'></i>Müşteri Ekle
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
        .filter-btn {
            background: #f3f4f6;
            color: #374151;
        }

        .filter-btn.active {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 42px !important;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            padding: 0.5rem 1rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 26px !important;
            padding-left: 0 !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 8px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #6366f1 !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #6366f1 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
        }
    </style>
    <!-- jQuery, SweetAlert2, API Helper, Select2 already loaded above -->
    <script src='assets/js/sites.js'></script>
    <script src='assets/js/import-sites.js'></script>
    <script src='assets/js/mobile-long-press.js'></script>
</body>

</html>