// Customers Management JavaScript
let currentFilter = 'all';
let searchQuery = '';
let selectedCustomers = [];
let contextMenuCustomerId = null;
let contextMenuCustomerStatus = null;

const whatsappTemplates = {
    reminder: `Sayın {name},

{company} için web sitesi hizmetlerimizle ilgili hatırlatma mesajımızdır.

Bilgi almak için bizimle iletişime geçebilirsiniz.

İyi günler dileriz.`,
    renewal: `Hocam merhabalar nasılsınız 🙏🏻
Oluşturduğumuz web sayfamızının yılı 15 gün sonra dolacaktır. 
Hasta ziyaretçi oranı iyi gö

zükmektedir. Yenileme yapmak ister misiniz?

Paket içeriği;
SSL Sertifikası (Antivirüs)
Tema Lisansı
Sunucu kiralama (1 yıllık)
Destek (1 yıllık)


Tüm ücretler dahil; 10.000₺/yıl



Bilgilerinize Sunarım, Saygılarımızla`,
    custom: `Sayın {name},

`
};

$(document).ready(function () {
    // Check URL parameters for filter
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter');
    if (filterParam) {
        currentFilter = filterParam;
        $('#filterSelect').val(filterParam);
        // Clean URL without reload
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    loadCustomers();

    $('#searchInput').on('input', function () {
        searchQuery = $(this).val();
        loadCustomers();
    });

    // Context menu - close on click outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#contextMenu').length) {
            $('#contextMenu').addClass('hidden');
        }
    });

    // Prevent default context menu on table rows
    $(document).on('contextmenu', '.customer-row', function (e) {
        e.preventDefault();
        const customerId = $(this).data('id');
        const customerStatus = $(this).data('status');
        showContextMenu(e.pageX, e.pageY, customerId, customerStatus);
        return false;
    });

    // Customer Form Submit
    $('#customerForm').on('submit', function (e) {
        e.preventDefault();
        saveCustomer();
    });
});

function formatPhoneNumber(phone) {
    if (!phone) return '';
    let p = phone.replace(/\D/g, '');
    if (p.startsWith('90') && p.length > 10) p = p.substring(2);
    if (p.startsWith('0') && p.length === 11) p = p.substring(1);
    if (p.length === 10) {
        return '0 (' + p.substring(0, 3) + ') ' + p.substring(3, 6) + ' ' + p.substring(6, 8) + ' ' + p.substring(8);
    }
    return phone;
}

function filterCustomers(filter) {
    currentFilter = filter;
    if ($('#filterSelect').val() !== filter) {
        $('#filterSelect').val(filter);
    }
    loadCustomers();
}

function loadCustomers() {
    $('#customersTable').html('<div class="text-center py-12"><div class="spinner mx-auto"></div><p class="mt-4 text-gray-600">Yükleniyor...</p></div>');

    $.get('api/customers.php', {
        action: 'list',
        filter: currentFilter,
        search: searchQuery
    }, function (res) {
        if (res.status === 'success') {
            const customers = res.data;

            // Calculate Stats
            const total = customers.length;
            const active = customers.filter(c => c.status === 'active').length;
            const passive = customers.filter(c => c.status === 'passive').length;

            // Animate Numbers
            $('#statTotal').text(total);
            $('#statActive').text(active);
            $('#statPassive').text(passive);

            renderCustomersTable(customers);
        }
    });
}

function renderCustomersTable(customers) {
    if (customers.length === 0) {
        $('#customersTable').html(`
            <div class="text-center py-16">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-users text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">Müşteri Bulunamadı</h3>
                <p class="text-gray-500 mt-1">Arama kriterlerinize uygun kayıt yok.</p>
            </div>
        `);
        return;
    }

    let html = `
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr>
                    <th class="p-4 w-10 border-b border-gray-200">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" 
                            class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                    </th>
                    <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Müşteri Bilgileri</th>
                    <th class="hidden md:table-cell p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Firma</th>
                    <th class="hidden sm:table-cell p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Telefon</th>
                    <th class="hidden xl:table-cell p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Siteler</th>
                    <th class="hidden lg:table-cell p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200">Durum</th>
                    <th class="p-4 w-24 border-b border-gray-200 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">`;

    customers.forEach(c => {
        const statusBadge = c.status === 'active'
            ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 bg-green-600 rounded-full mr-1.5"></span>Aktif</span>'
            : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><span class="w-1.5 h-1.5 bg-gray-500 rounded-full mr-1.5"></span>Pasif</span>';

        // Show only first 3 sites, with indicator if more exist
        let sitesDisplay = '';
        if (c.sites && c.sites.length > 0) {
            const visibleSites = c.sites.slice(0, 3);
            sitesDisplay = visibleSites.map(s => `
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-700 mr-1 mb-1 border border-indigo-100">
                    <i class="fa-solid fa-globe text-[10px] mr-1 opacity-70"></i>${s}
                </span>`
            ).join('');

            if (c.sites.length > 3) {
                const remaining = c.sites.length - 3;
                sitesDisplay += `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">+${remaining}</span>`;
            }
        } else {
            sitesDisplay = '<span class="text-gray-400 text-xs italic">Site eklenmemiş</span>';
        }

        // Mobile-visible extra info
        const mobileInfo = `
            <div class="mt-2 space-y-1 md:hidden">
                ${c.company_name ? `<div class="flex items-center text-xs text-gray-500"><i class="fa-solid fa-building w-4 text-gray-400"></i> ${c.company_name}</div>` : ''}
                <div class="flex items-center text-xs text-gray-500"><i class="fa-solid fa-phone w-4 text-gray-400"></i> ${formatPhoneNumber(c.phone)}</div>
                <div class="mt-2 flex items-center justify-between">
                    <div>${statusBadge}</div>
                    ${c.sites && c.sites.length > 0 ? `<div class="text-xs text-indigo-600 font-medium">${c.sites.length} Site</div>` : ''}
                </div>
            </div>
        `;

        html += `<tr class="customer-row group hover:bg-gray-50 transition-colors duration-150" data-id="${c.id}" data-status="${c.status || 'active'}">
            <td class="p-4 align-top sm:align-middle">
                <input type="checkbox" class="customer-checkbox hidden w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" value="${c.id}" onchange="updateBulkActions()">
            </td>
            <td class="p-4 align-top sm:align-middle">
                <div>
                    <div class="font-semibold text-gray-900">${c.full_name}</div>
                    ${mobileInfo}
                </div>
            </td>
            <td class="hidden md:table-cell p-4 align-middle text-sm text-gray-600">${c.company_name || '-'}</td>
            <td class="hidden sm:table-cell p-4 align-middle text-sm">
                <a href="tel:${c.phone}" class="text-gray-600 hover:text-indigo-600 transition-colors font-mono">
                    ${formatPhoneNumber(c.phone)}
                </a>
            </td>
            <td class="hidden xl:table-cell p-4 align-middle text-sm max-w-sm">${sitesDisplay}</td>
            <td class="hidden lg:table-cell p-4 align-middle">${statusBadge}</td>
            <td class="p-4 align-middle text-right">
                <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="openWhatsappModal(${c.id})" class="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 hover:scale-105 transition-all" title="WhatsApp Mesajı Gönder"><i class="fa-brands fa-whatsapp text-lg"></i></button>
                    <button onclick="editCustomer(${c.id})" class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 hover:scale-105 transition-all" title="Düzenle"><i class="fa-solid fa-edit"></i></button>
                    <button onclick="deleteCustomer(${c.id})" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 hover:scale-105 transition-all" title="Sil"><i class="fa-solid fa-trash"></i></button>
                </div>
                <!-- Mobile only action button dots -->
                 <button class="md:hidden text-gray-400"><i class="fa-solid fa-ellipsis-vertical"></i></button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    $('#customersTable').html(html);
}

// MODAL FUNCTIONS
function openCustomerModal() {
    $('#customerForm')[0].reset();
    $('#customerId').val('');
    $('#formAction').val('create');
    $('#modalTitle').text('Yeni Müşteri Ekle');
    $('#customerStatus').val('active');
    customerSiteIds = [];

    // Reset Select2
    if ($('#addSiteDropdown').data('select2')) {
        $('#addSiteDropdown').val(null).trigger('change');
    }

    loadAvailableSites();
    updateSelectedSitesDisplay();
    $('#customerModal').removeClass('hidden').addClass('flex');
}

function closeCustomerModal() {
    $('#customerModal').removeClass('flex').addClass('hidden');
}

function editCustomer(customerId) {
    $.get('api/customers.php', { action: 'get', id: customerId }, function (res) {
        if (res.status === 'success') {
            const customer = res.data;

            $('#customerId').val(customer.id);
            $('#formAction').val('update');
            $('#modalTitle').text('Müşteriyi Düzenle');
            $('#fullName').val(customer.full_name);
            $('#companyName').val(customer.company_name || '');
            $('#phone').val(customer.phone);
            $('#email').val(customer.email || '');
            $('#city').val(customer.city || '');
            $('#address').val(customer.address || '');
            $('#notes').val(customer.notes || '');
            $('#customerStatus').val(customer.status || 'active');

            // Load sites for this customer
            customerSiteIds = customer.site_ids || [];

            loadAvailableSites();
            updateSelectedSitesDisplay();

            $('#customerModal').removeClass('hidden').addClass('flex');
        }
    });
}

function saveCustomer() {
    const formData = $('#customerForm').serialize();

    $.post('api/customers.php', formData, function (res) {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            });
            closeCustomerModal();
            loadCustomers();
        } else {
            Swal.fire('Hata!', res.message, 'error');
        }
    });
}

function deleteCustomer(customerId) {
    Swal.fire({
        title: 'Müşteri Silinecek',
        text: 'Bu işlem geri alınamaz!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/customers.php', { action: 'delete', id: customerId }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Silindi!', res.message, 'success');
                    loadCustomers();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

// Context Menu Functions
function showContextMenu(x, y, customerId, customerStatus) {
    contextMenuCustomerId = customerId;
    contextMenuCustomerStatus = customerStatus;
    const menu = $('#contextMenu');

    // Update toggle status text
    if (customerStatus === 'active') {
        $('#statusToggleText').text('Pasif Yap');
    } else {
        $('#statusToggleText').text('Aktif Yap');
    }

    menu.removeClass('hidden');
    menu.css({ top: y + 'px', left: x + 'px' });
}

function contextMenuAction(action) {
    $('#contextMenu').addClass('hidden');

    switch (action) {
        case 'whatsapp':
            openWhatsappModal(contextMenuCustomerId);
            break;
        case 'toggle_status':
            const newStatus = contextMenuCustomerStatus === 'active' ? 'passive' : 'active';
            $.post('api/customers.php', {
                action: 'update_status',
                id: contextMenuCustomerId,
                status: newStatus
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Durum güncellendi',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    loadCustomers();
                }
            });
            break;
        case 'delete':
            deleteCustomer(contextMenuCustomerId);
            break;
    }
}

function toggleSelectAll(checkbox) {
    if (checkbox.checked) {
        $('.customer-checkbox').removeClass('hidden');
    } else {
        $('.customer-checkbox').addClass('hidden');
    }
    $('.customer-checkbox').prop('checked', checkbox.checked);
    updateBulkActions();
}

function updateBulkActions() {
    selectedCustomers = $('.customer-checkbox:checked').map(function () {
        return $(this).val();
    }).get();

    if (selectedCustomers.length > 0) {
        $('#bulkActionsBtn').removeClass('hidden').text(`${selectedCustomers.length} Müşteri Seçildi - Toplu İşlemler`);
    } else {
        $('#bulkActionsBtn').addClass('hidden');
    }
}

$('#bulkActionsBtn').on('click', function () {
    showBulkActionsMenu();
});

// Bulk Actions
function showBulkActionsMenu() {
    Swal.fire({
        title: `${selectedCustomers.length} Müşteri Seçildi`,
        html: `
            <div class="space-y-2">
                <button onclick="bulkUpdateStatus('active')" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fa-solid fa-check mr-2"></i>Aktif Yap
                </button>
                <button onclick="bulkUpdateStatus('passive')" class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                    <i class="fa-solid fa-ban mr-2"></i>Pasif Yap
                </button>
                <button onclick="bulkWhatsApp()" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fa-brands fa-whatsapp mr-2"></i>Toplu WhatsApp Mesajı
                </button>
                <button onclick="bulkDelete()" class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    <i class="fa-solid fa-trash mr-2"></i>Sil
                </button>
            </div>
            `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Kapat'
    });
}

function bulkUpdateStatus(status) {
    Swal.close();
    $.post('api/customers.php', {
        action: 'bulk_update_status',
        ids: selectedCustomers,
        status: status
    }, function (res) {
        if (res.status === 'success') {
            Swal.fire('Başarılı', res.message, 'success');
            loadCustomers();
            selectedCustomers = [];
            $('#selectAll').prop('checked', false);
            updateBulkActions();
        } else {
            Swal.fire('Hata', res.message, 'error');
        }
    });
}

function bulkDelete() {
    Swal.close();
    Swal.fire({
        title: 'Toplu Silme',
        text: `${selectedCustomers.length} müşteri silinecek! Bu işlem geri alınamaz.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/customers.php', {
                action: 'bulk_delete',
                ids: selectedCustomers
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Silindi!', res.message, 'success');
                    loadCustomers();
                    selectedCustomers = [];
                    $('#selectAll').prop('checked', false);
                    updateBulkActions();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

// Searchable Dropdown & Modal Logic
let customerSiteIds = [];

function loadAvailableSites() {
    $.get('api/sites.php', { action: 'list_all' }, function (res) {
        if (res.status === 'success') {
            const dropdown = $('#addSiteDropdown');
            dropdown.empty();
            dropdown.append(new Option('Site seçerek ekleyin...', ''));

            res.data.forEach(site => {
                if (!customerSiteIds.includes(site.id.toString())) {
                    const option = new Option(site.domain, site.id);
                    dropdown.append(option);
                }
            });

            if (!dropdown.hasClass("select2-hidden-accessible")) {
                dropdown.select2({
                    dropdownParent: $('#customerModal'),
                    placeholder: 'Site ara...',
                    allowClear: true,
                    width: '100%'
                });
            } else {
                dropdown.trigger('change');
            }

            // On site selection
            dropdown.off('select2:select').on('select2:select', function (e) {
                const siteId = e.params.data.id;
                if (siteId && !customerSiteIds.includes(siteId)) {
                    customerSiteIds.push(siteId);
                    updateSelectedSitesDisplay();
                    loadAvailableSites(); // Refresh dropdown
                }
                // Reset dropdown
                $(this).val(null).trigger('change');
            });
        }
    });
}

function updateSelectedSitesDisplay() {
    $('#customerSitesHidden').val(JSON.stringify(customerSiteIds));

    if (customerSiteIds.length === 0) {
        $('#selectedSitesTags').html('<span class="text-gray-400 text-sm" id="noSitesText">Henüz site eklenmedi</span>');
        return;
    }

    // Get site names
    $.get('api/sites.php', { action: 'list_all' }, function (res) {
        if (res.status === 'success') {
            let tagsHtml = '';
            customerSiteIds.forEach(siteId => {
                const site = res.data.find(s => s.id.toString() === siteId.toString());
                if (site) {
                    tagsHtml += `
                        <span class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-800 px-3 py-1.5 rounded-full text-sm font-medium">
                            <i class="fa-solid fa-globe text-xs"></i>
                            ${site.domain}
                            <button type="button" onclick="removeSiteFromCustomer('${siteId}')" class="hover:bg-indigo-200 rounded-full p-0.5">
                                <i class="fa-solid fa-times text-xs"></i>
                            </button>
                        </span>
                    `;
                }
            });
            $('#selectedSitesTags').html(tagsHtml);
        }
    });
}

function removeSiteFromCustomer(siteId) {
    customerSiteIds = customerSiteIds.filter(id => id != siteId);
    updateSelectedSitesDisplay();
    loadAvailableSites();
}

// WhatsApp Logic
function openWhatsappModal(customerId) {
    contextMenuCustomerId = customerId;
    if (window.currentUser && !window.currentUser.permissions.whatsapp) {
        Swal.fire({ icon: 'error', title: 'Yetki Yok', text: 'WhatsApp mesajı gönderme yetkiniz bulunmamaktadır.' });
        return;
    }

    $.when(
        $.get('api/customers.php', { action: 'get', id: customerId }),
        $.get('api/templates.php', { action: 'list' }),
        $.get('api/settings.php')
    ).done(function (custRes, templatesRes, settingsRes) {
        const customer = custRes[0].data;
        const templates = templatesRes[0].data || [];
        const settings = settingsRes[0];

        const hasEvolution = settings.evolution_api_url && settings.evolution_api_key && settings.evolution_instance_name;

        let optionsHtml = '<option value="">Şablon Seçiniz...</option>';
        templates.filter(t => t.type === 'whatsapp').forEach(t => {
            optionsHtml += `<option value="${t.id}" data-message="${encodeURIComponent(t.message)}">${t.title}</option>`;
        });

        let sitesHtml = '';
        if (customer.sites && customer.sites.length > 0) {
            sitesHtml = `
             <div class="mt-2 p-2 bg-blue-50 rounded text-xs">
                <label class="block font-bold mb-1">Siteler (Mesajda [SITELER] olarak kullanılabilir):</label>
                <div class="flex flex-wrap gap-2">
                    ${customer.sites.map(s => `<span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">${s}</span>`).join('')}
                </div>
             </div>`;
        }

        const sendButtonText = hasEvolution ? '<i class="fa-solid fa-paper-plane mr-2"></i>API ile Gönder' : '<i class="fa-brands fa-whatsapp mr-2"></i>WhatsApp Web';

        Swal.fire({
            title: '<i class="fa-brands fa-whatsapp text-green-600 mr-2"></i>WhatsApp Gönder',
            html: `
                <div class="text-left space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Müşteri</label>
                            <input type="text" id="waCustomerName" value="${customer.full_name}" class="w-full border rounded px-3 py-2 text-sm bg-gray-50" readonly>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Telefon</label>
                            <input type="text" id="waCustomerPhone" value="${customer.phone}" class="w-full border rounded px-3 py-2 text-sm">
                        </div>
                    </div>
                    ${sitesHtml}
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mesaj Şablonu</label>
                        <select id="waTemplate" class="w-full border rounded px-3 py-2 bg-gray-50">${optionsHtml}</select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mesaj</label>
                        <textarea id="waMessage" rows="6" class="w-full border rounded px-3 py-2"></textarea>
                    </div>
                    
                    ${hasEvolution ? `
                    <div class="border-t pt-4 mt-2">
                         <label class="flex items-center space-x-2 cursor-pointer mb-2">
                            <input type="checkbox" id="waScheduleToggle" class="form-checkbox h-4 w-4 text-green-600">
                            <span class="text-sm font-bold text-gray-700">Zamanl

ı Gönderim</span>
                         </label>
                         <div id="waScheduleContainer" class="hidden pl-6">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gönderim Tarihi</label>
                            <input type="datetime-local" id="waScheduleDate" class="w-full border rounded px-3 py-2 text-sm">
                         </div>
                    </div>
                    ` : ''}

                    ${!hasEvolution ? '<p class="text-xs text-orange-600 bg-orange-50 p-2 rounded mt-2">Evolution API ayarlı değil. WhatsApp Web açılacak.</p>' : '<p class="text-xs text-green-600 bg-green-50 p-2 rounded mt-2">API ayarlı. Mesaj otomatik gönderilecek.</p>'}
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: sendButtonText,
            confirmButtonColor: '#25D366',
            didOpen: () => {
                $('#waTemplate').on('change', function () {
                    const selectedOption = $(this).find(':selected');
                    const encodedMsg = selectedOption.data('message');
                    if (encodedMsg) {
                        let message = decodeURIComponent(encodedMsg);
                        message = message.replace(/\[ADI SOYADI\]/g, customer.full_name || '');
                        message = message.replace(/\[SITELER\]/g, (customer.sites || []).join(', '));
                        $('#waMessage').val(message);
                    }
                });

                $('#waScheduleToggle').on('change', function () {
                    $('#waScheduleContainer').toggleClass('hidden', !$(this).is(':checked'));
                });
            },
            preConfirm: () => {
                const phone = $('#waCustomerPhone').val();
                const message = $('#waMessage').val();
                const isScheduled = $('#waScheduleToggle').is(':checked');
                const scheduleDate = $('#waScheduleDate').val();

                if (!phone || !message) { Swal.showValidationMessage('Tüm alanları doldurun'); return false; }
                if (isScheduled && !scheduleDate) { Swal.showValidationMessage('Tarih seçin'); return false; }
                return { phone, message, isScheduled, scheduleDate };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const phone = result.value.phone.replace(/\D/g, '');
                const finalPhone = (phone.startsWith('90') ? phone : '90' + (phone.startsWith('0') ? phone.substring(1) : phone));
                const msg = result.value.message;

                if (result.value.isScheduled) {
                    $.post('api/customers.php', {
                        action: 'schedule_whatsapp',
                        customer_id: customerId,
                        phone: finalPhone,
                        message: msg,
                        scheduled_at: result.value.scheduleDate
                    }, function (res) {
                        if (res.status === 'success') Swal.fire('Planlandı', 'Mesaj kuyruğa eklendi', 'success');
                        else Swal.fire('Hata', res.message, 'error');
                    });
                } else if (hasEvolution) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading() });
                    $.post('api/customers.php', {
                        action: 'send_whatsapp_api',
                        customer_id: customerId,
                        phone: finalPhone,
                        message: msg
                    }, function (res) {
                        if (res.status === 'success') Swal.fire('Başarılı', 'Mesaj gönderildi', 'success');
                        else Swal.fire('Hata', res.message, 'error');
                    });
                } else {
                    const url = `https://api.whatsapp.com/send?phone=${finalPhone}&text=${encodeURIComponent(msg)}`;
                    window.open(url, '_blank');
                    $.post('api/customers.php', { action: 'log_whatsapp', customer_id: customerId, message_text: msg, phone: finalPhone, message_type: 'custom' });
                }
            }
        });
    });
}

function bulkWhatsApp() {
    Swal.close();
    $.get('api/templates.php', { action: 'list' }, function (res) {
        const templates = res.data || [];
        let optionsHtml = '<option value="">Şablon Seçiniz...</option>';
        templates.filter(t => t.type === 'whatsapp').forEach(t => {
            optionsHtml += `<option value="${t.id}" data-message="${encodeURIComponent(t.message)}">${t.title}</option>`;
        });

        Swal.fire({
            title: `<i class="fa-brands fa-whatsapp text-green-600 mr-2"></i>Toplu Mesaj (${selectedCustomers.length} Müşteri)`,
            html: `
            <div class="text-left space-y-4">
                    <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
                        <i class="fa-solid fa-info-circle mr-1"></i>
                        Seçilen ${selectedCustomers.length} müşteriye mesaj gönderilecek. [ADI SOYADI] gibi değişkenler otomatik doldurulur.
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mesaj Şablonu</label>
                        <select id="bulkTemplate" class="w-full border rounded px-3 py-2 bg-gray-50">${optionsHtml}</select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mesaj İçeriği</label>
                        <textarea id="bulkMessage" rows="5" class="w-full border rounded px-3 py-2 text-sm" placeholder="Mesajınız..."></textarea>
                    </div>
                    <div class="border-t pt-4 mt-2">
                         <label class="flex items-center space-x-2 cursor-pointer mb-2">
                            <input type="checkbox" id="bulkScheduleToggle" class="form-checkbox h-4 w-4 text-green-600">
                            <span class="text-sm font-bold text-gray-700">Zamanlı Gönderim</span>
                         </label>
                         <div id="bulkScheduleContainer" class="hidden pl-6">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gönderim Tarihi</label>
                            <input type="datetime-local" id="bulkScheduleDate" class="w-full border rounded px-3 py-2 text-sm">
                         </div>
                    </div>
                </div>
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Kuyruğa Ekle',
            confirmButtonColor: '#25D366',
            didOpen: () => {
                $('#bulkTemplate').on('change', function () {
                    const selectedOption = $(this).find(':selected');
                    const encodedMsg = selectedOption.data('message');
                    if (encodedMsg) $('#bulkMessage').val(decodeURIComponent(encodedMsg));
                });
                $('#bulkScheduleToggle').on('change', function () {
                    $('#bulkScheduleContainer').toggleClass('hidden', !$(this).is(':checked'));
                });
            },
            preConfirm: () => {
                const message = $('#bulkMessage').val();
                const isScheduled = $('#bulkScheduleToggle').is(':checked');
                const scheduleDate = $('#bulkScheduleDate').val();
                if (!message) { Swal.showValidationMessage('Mesaj girin'); return false; }
                if (isScheduled && !scheduleDate) { Swal.showValidationMessage('Tarih seçin'); return false; }
                return { message, isScheduled, scheduleDate };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'İşleniyor...', didOpen: () => Swal.showLoading() });
                $.post('api/customers.php', {
                    action: 'bulk_whatsapp_schedule',
                    ids: selectedCustomers,
                    message_template: result.value.message,
                    scheduled_at: result.value.isScheduled ? result.value.scheduleDate : ''
                }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Başarılı', text: res.message }).then(() => {
                            selectedCustomers = []; $('#selectAll').prop('checked', false); updateBulkActions();
                        });
                    } else {
                        Swal.fire('Hata', res.message, 'error');
                    }
                });
            }
        });
    });
}
