// Sites Management JavaScript - Geliştirilmiş Versiyon
let currentFilter = 'upcoming';
let searchQuery = '';
let selectedSites = [];
let contextMenuSiteId = null;

$(document).ready(function () {
    initializePage();
});

function initializePage() {
    // Check URL parameters for filter
    const urlParams = new URLSearchParams(window.location.search);
    const filterParam = urlParams.get('filter');
    if (filterParam) {
        currentFilter = filterParam;
        $('#filterSelect').val(filterParam);
        // Clean URL without reload
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    loadSites();

    // Search
    $('#searchInput').on('input', function () {
        searchQuery = $(this).val();
        loadSites();
    });

    // Context menu - close on click outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#contextMenu').length) {
            $('#contextMenu').addClass('hidden');
        }
    });

    // Prevent default context menu on table rows
    $(document).on('contextmenu', '.site-row', function (e) {
        e.preventDefault();
        const siteId = $(this).data('id');
        showContextMenu(e.pageX, e.pageY, siteId);
        return false;
    });

    // Double click to edit
    $(document).on('dblclick', '.site-row', function () {
        const siteId = $(this).data('id');
        editSite(siteId);
    });
}

function filterSites(filter) {
    currentFilter = filter;
    // Update dropdown if not already selected (e.g. called from code)
    if ($('#filterSelect').val() !== filter) {
        $('#filterSelect').val(filter);
    }
    loadSites();
}

function loadSites() {
    $('#sitesTable').html('<div class="text-center py-12"><div class="spinner mx-auto"></div><p class="mt-4 text-gray-600">Yükleniyor...</p></div>');

    $.get('api/sites.php', {
        action: 'list',
        filter: currentFilter,
        search: searchQuery
    }, function (res) {
        if (res.status === 'success') {
            renderSitesTable(res.data);
        }
    });
}

function renderSitesTable(sites) {
    if (sites.length === 0) {
        $('#sitesTable').html('<div class="text-center py-12 text-gray-500"><i class="fa-solid fa-globe fa-3x mb-4"></i><p>Site bulunamadı</p></div>');
        return;
    }

    let html = '<table class="data-table sites-table"><thead><tr>';
    html += '<th class="w-8"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>';
    html += '<th>Site Bilgileri</th>';
    html += '<th class="hidden md:table-cell">Müşteri</th>';
    html += '<th class="hidden lg:table-cell">Paket</th>';
    html += '<th class="hidden sm:table-cell">Yenileme</th>';
    html += '<th class="hidden md:table-cell text-center">Durum</th>';
    html += '<th class="w-10"></th>'; // Actions
    html += '</tr></thead><tbody>';

    sites.forEach(s => {
        // Status icon
        let statusIcon = '';
        if (s.status === 'requested') {
            statusIcon = '<i class="fa-solid fa-paper-plane text-blue-600" title="İstendi"></i>';
        } else if (s.status === 'accepted') {
            statusIcon = '<i class="fa-solid fa-check text-green-600" title="Kabul Etti"></i>';
        } else if (s.status === 'active') {
            if (s.last_renewed_at) {
                statusIcon = '<i class="fa-solid fa-check-circle text-emerald-600" title="Yenilendi"></i>';
            } else {
                statusIcon = '<i class="fa-solid fa-circle text-green-600" title="Aktif"></i>';
            }
        } else if (s.status === 'cancelled') {
            statusIcon = '<i class="fa-solid fa-ban text-red-600" title="İptal Edildi"></i>';
        } else if (s.status === 'transferred') {
            statusIcon = '<i class="fa-solid fa-exchange-alt text-indigo-600" title="Transfer Edildi"></i>';
        } else if (s.status === 'expired') {
            statusIcon = '<i class="fa-solid fa-exclamation-triangle text-orange-600" title="Süresi Doldu"></i>';
        }

        if (s.whatsapp_sent_at) {
            const date = new Date(s.whatsapp_sent_at);
            statusIcon += `<div class="text-[10px] text-gray-500 mt-1">${date.toLocaleDateString()}</div>`;
        }

        // Package badge
        const packageBadge = s.package_type === 'PRO'
            ? '<span class="package-pro text-[10px]">PRO</span>'
            : '<span class="package-basic text-[10px]">BASIC</span>';

        // Days until renewal
        const daysText = s.days_until >= 0
            ? `${s.days_until} gün`
            : `<span class="text-red-600">${Math.abs(s.days_until)} gün gecikmiş</span>`;

        // API Date Logic
        let apiExpiresInfo = '';
        if (s.api_expires_at) {
            const apiDate = new Date(s.api_expires_at);
            apiExpiresInfo = `<span class="text-gray-400" title="Hostinger Bitiş Tarihi"> <img src="https://assets.hostinger.com/images/logo-hostinger-black.svg" class="h-2 w-auto inline opacity-50 relative -top-px"> ${apiDate.toLocaleDateString('tr-TR')}</span>`;

            // Accept Button Logic - Only if API date > Renewal Date
            if (new Date(s.api_expires_at) > new Date(s.renewal_date)) {
                apiExpiresInfo += ` <button onclick="acceptRenewalSite(${s.id})" class="ml-1 text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded border border-green-200 hover:bg-green-200 transition"><i class="fa-solid fa-check"></i></button>`;
            }
        }

        // Site age
        const siteAge = calculateSiteAge(s.start_date);
        // Only show badge if years >= 2
        const ageBadge = (siteAge && parseInt(siteAge) >= 2)
            ? `<span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-purple-600 rounded-full ml-1" title="${siteAge} Yıllık">${siteAge}</span>`
            : '';

        // Mobile-visible extra info (shown in main column on mobile)
        const mobileInfo = `
            <div class="mt-1 space-y-0.5 md:hidden text-xs text-gray-500">
                <div class="flex items-center gap-1"><i class="fa-solid fa-user text-gray-400 w-4"></i> ${s.customer_name}</div>
                <div class="flex items-center gap-1"><i class="fa-solid fa-clock text-gray-400 w-4"></i> ${formatDate(s.renewal_date)} (${daysText})</div>
            </div>
        `;

        html += `<tr class="site-row hover:bg-gray-50 transition-colors" data-id="${s.id}">`;
        html += `<td class="w-8"><input type="checkbox" class="site-checkbox hidden" value="${s.id}" onchange="updateBulkActions()"></td>`;
        html += `<td>
            <div class="flex flex-col">
                <div class="flex items-center flex-wrap gap-1">
                    <a href="http://${s.domain}" target="_blank" class="text-sm md:text-lg font-bold text-indigo-600 hover:text-indigo-700 truncate max-w-[150px] sm:max-w-none" title="Siteyi Ziyaret Et">
                        ${s.domain}
                    </a>
                    ${ageBadge}
                </div>
                <!-- Mobile only extra details -->
                ${mobileInfo}
                <!-- API Info & Start Date (Desktop/Mobile unified mostly) -->
                <div class="text-[10px] md:text-xs text-gray-400 mt-0.5 flex items-center flex-wrap gap-1">
                   ${s.start_date ? `<span>Açılış: ${formatDate(s.start_date)}</span>` : ''}
                   ${apiExpiresInfo ? `<span class="hidden sm:inline">|</span> ${apiExpiresInfo}` : ''}
                </div>
            </div>
        </td>`;

        // Hide these columns on mobile
        html += `<td class="hidden md:table-cell text-sm text-gray-700"><strong>${s.customer_name}</strong></td>`;
        html += `<td class="hidden lg:table-cell">${packageBadge}</td>`;
        html += `<td class="hidden sm:table-cell text-xs">${formatDate(s.renewal_date)}<br><span class="text-xs md:text-sm text-gray-500 font-medium">${daysText}</span></td>`;
        html += `<td class="hidden md:table-cell text-center">${statusIcon}</td>`;

        html += `<td class="text-right p-2">
            <div class="relative inline-block">
                <button onclick="toggleActionsMenu(${s.id})" class="px-3 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
                    <i class="fa-solid fa-ellipsis-v"></i>
                </button>
            <div id="actions-${s.id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                <button onclick="editSite(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm">
                    <i class="fa-solid fa-pen-to-square mr-2"></i> Düzenle
                </button>
                <button onclick="toggleReminder(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm">
                    <i class="fa-solid fa-bell mr-2"></i> Hatırlatma
                </button>
                <button onclick="sendWhatsApp(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm">
                    <i class="fa-brands fa-whatsapp mr-2"></i> WhatsApp Gönder
                </button>
                <button onclick="openHistoryModal(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-sm">
                    <i class="fa-solid fa-history mr-2"></i> Geçmiş
                </button>
                ${(currentFilter === 'cancelled' || currentFilter === 'transferred') ? `
                    <button onclick="deleteSite(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-2 hover:bg-red-50 text-red-600 text-sm">
                        <i class="fa-solid fa-trash mr-2"></i> Sil
                    </button>
                    ` : ''}
            </div>
        </div>
        </td ></tr > `;
    });

    html += '</tbody></table>';
    $('#sitesTable').html(html);
}

function calculateSiteAge(startDate) {
    if (!startDate) return '-';

    const start = new Date(startDate);
    const now = new Date();
    const diffTime = Math.abs(now - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    const years = Math.floor(diffDays / 365);

    return years > 0 ? years : '<1';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('tr-TR');
}

function toggleActionsMenu(siteId) {
    const menu = $(`#actions - ${siteId} `);
    $('.actions-menu').not(menu).addClass('hidden'); // Close others
    menu.toggleClass('hidden');
}

// Close actions menu when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('button').hasClass('fa-ellipsis-v') && !$(e.target).closest('.actions-menu').length) {
        $('.actions-menu').addClass('hidden');
    }
});

function toggleSelectAll(checkbox) {
    if (checkbox.checked) {
        $('.site-checkbox').removeClass('hidden');
    } else {
        $('.site-checkbox').addClass('hidden');
    }
    $('.site-checkbox').prop('checked', checkbox.checked);
    updateBulkActions();
}

function updateBulkActions() {
    selectedSites = $('.site-checkbox:checked').map(function () {
        return $(this).val();
    }).get();

    if (selectedSites.length > 0) {
        $('#bulkActionsBtn').removeClass('hidden').text(`${selectedSites.length} Site Seçildi - Toplu İşlemler`);
    } else {
        $('#bulkActionsBtn').addClass('hidden');
    }
}

$('#bulkActionsBtn').on('click', function () {
    showBulkActionsMenu();
});

function showBulkActionsMenu() {
    Swal.fire({
        title: `${selectedSites.length} Site Seçildi`,
        html: `
            < div class="space-y-2" >
                <button onclick="bulkRenew()" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fa-solid fa-sync mr-2"></i>Toplu Yenile
                </button>
                <button onclick="bulkChangeStatus('cancelled')" class="w-full px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                    <i class="fa-solid fa-ban mr-2"></i>İptal Et
                </button>
                <button onclick="bulkChangeStatus('transferred')" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    <i class="fa-solid fa-exchange-alt mr-2"></i>Transfer Et
                </button>
                <button onclick="bulkWhatsApp()" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    <i class="fa-brands fa-whatsapp mr-2"></i>Toplu WhatsApp Mesajı
                </button>
                <button onclick="bulkDelete()" class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    <i class="fa-solid fa-trash mr-2"></i>Toplu Sil
                </button>
            </div >
            `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Kapat'
    });
}

function bulkRenew() {
    Swal.close();
    Swal.fire({
        title: 'Toplu Yenileme',
        text: `${selectedSites.length} site + 1 yıl yenilenecek.Onaylıyor musunuz ? `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Yenile',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', {
                action: 'bulk_renew',
                ids: selectedSites
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Başarılı!', res.message, 'success');
                    loadSites();
                    selectedSites = [];
                    $('#selectAll').prop('checked', false);
                    updateBulkActions();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

function bulkChangeStatus(status) {
    Swal.close();
    const statusText = status === 'cancelled' ? 'İptal Edildi' : 'Transfer Edildi';
    Swal.fire({
        title: `Toplu Durum Değiştirme`,
        text: `${selectedSites.length} site "${statusText}" olarak işaretlenecek.Onaylıyor musunuz ? `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Evet, Değiştir',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', {
                action: 'bulk_status',
                ids: selectedSites,
                status: status
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Başarılı!', res.message, 'success');
                    loadSites();
                    selectedSites = [];
                    $('#selectAll').prop('checked', false);
                    updateBulkActions();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

function acceptRenewalSite(id) {
    Swal.fire({
        title: 'Yenilemeyi Onayla?',
        text: "Bu site için ödeme alındığını ve sürenin uzatılacağını onaylıyor musunuz?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Onayla',
        confirmButtonColor: '#16a34a', // green-600
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/hostinger.php', { action: 'accept_renewal', id: id }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Başarılı', res.message, 'success');
                    loadSites();
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            });
        }
    });
}

function bulkWhatsApp() {
    Swal.close();

    // Fetch Templates first
    $.get('api/templates.php', { action: 'list' }, function (res) {
        const templates = res.data || [];
        let optionsHtml = '<option value="">Şablon Seçiniz...</option>';
        templates.filter(t => t.type === 'whatsapp').forEach(t => {
            optionsHtml += `< option value = "${t.id}" data - message="${encodeURIComponent(t.message)}" > ${t.title}</option > `;
        });

        Swal.fire({
            title: `< i class="fa-brands fa-whatsapp text-green-600 mr-2" ></i > Toplu Mesaj Gönder(${selectedSites.length} Site)`,
            html: `
            < div class="text-left space-y-4" >
                    <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
                        <i class="fa-solid fa-info-circle mr-1"></i>
                        Seçilen ${selectedSites.length} sitenin her biri için, müşteriye özel (Ad Soyad, Domain, Tarih) bilgilerle mesaj oluşturulup sıraya alınacaktır.
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mesaj Şablonu</label>
                        <select id="bulkTemplate" class="w-full border rounded px-3 py-2 bg-gray-50">${optionsHtml}</select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">Mesaj İçeriği (Şablon)</label>
                        <textarea id="bulkMessage" rows="5" class="w-full border rounded px-3 py-2 text-sm" placeholder="Şablon seçin veya buraya yazın... Değişkenler: [ADI SOYADI], [SITE], [TARIH]"></textarea>
                    </div>

                    <div class="border-t pt-4 mt-2">
                         <label class="flex items-center space-x-2 cursor-pointer mb-2">
                            <input type="checkbox" id="bulkScheduleToggle" class="form-checkbox h-4 w-4 text-green-600">
                            <span class="text-sm font-bold text-gray-700">Zamanlı Gönderim</span>
                         </label>
                         <div id="bulkScheduleContainer" class="hidden pl-6">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gönderim Tarihi ve Saati</label>
                            <input type="datetime-local" id="bulkScheduleDate" class="w-full border rounded px-3 py-2 text-sm">
                            <p class="text-xs text-gray-500 mt-1">Belirlenen saatte gönderim başlar.</p>
                         </div>
                    </div>
                </div >
            `,
            width: '600px',
            showCancelButton: true,
            confirmButtonText: 'Kuyruğa Ekle',
            confirmButtonColor: '#25D366',
            cancelButtonText: 'İptal',
            didOpen: () => {
                $('#bulkTemplate').on('change', function () {
                    const selectedOption = $(this).find(':selected');
                    const encodedMsg = selectedOption.data('message');
                    if (encodedMsg) {
                        $('#bulkMessage').val(decodeURIComponent(encodedMsg));
                    }
                });

                $('#bulkScheduleToggle').on('change', function () {
                    if ($(this).is(':checked')) {
                        $('#bulkScheduleContainer').removeClass('hidden');
                    } else {
                        $('#bulkScheduleContainer').addClass('hidden');
                    }
                });
            },
            preConfirm: () => {
                const message = $('#bulkMessage').val();
                const isScheduled = $('#bulkScheduleToggle').is(':checked');
                const scheduleDate = $('#bulkScheduleDate').val();

                if (!message) {
                    Swal.showValidationMessage('Lütfen bir mesaj içeriği girin');
                    return false;
                }
                if (isScheduled && !scheduleDate) {
                    Swal.showValidationMessage('Lütfen tarih seçin');
                    return false;
                }

                return { message, isScheduled, scheduleDate };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'İşleniyor...', didOpen: () => Swal.showLoading() });

                $.post('api/sites.php', {
                    action: 'bulk_whatsapp_schedule',
                    ids: selectedSites,
                    message_template: result.value.message,
                    scheduled_at: result.value.isScheduled ? result.value.scheduleDate : ''
                }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı!',
                            html: `${res.message} <br><small class="text-gray-500">Cron servisi tarafından gönderilecektir.</small>`,
                            confirmButtonText: 'Tamam'
                        });
                        selectedSites = [];
                        $('#selectAll').prop('checked', false);
                        updateBulkActions();
                        // Opsiyonel: Tabloyu yenilemeye gerek yok çünkü arka planda gidiyor, ama durum belki değişir?
                        // loadSites(); 
                    } else {
                        Swal.fire('Hata', res.message, 'error');
                    }
                });
            }
        });
    });
}

function bulkDelete() {
    Swal.close();
    Swal.fire({
        title: 'Toplu Silme',
        text: `${selectedSites.length} site silinecek! Bu işlem geri alınamaz.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', {
                action: 'bulk_delete',
                ids: selectedSites
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Silindi!', res.message, 'success');
                    loadSites();
                    selectedSites = [];
                    $('#selectAll').prop('checked', false);
                    updateBulkActions();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

function showContextMenu(x, y, siteId) {
    contextMenuSiteId = siteId;
    const menu = $('#contextMenu');
    menu.removeClass('hidden');
    menu.css({ top: y + 'px', left: x + 'px' });
}

function contextMenuAction(action) {
    $('#contextMenu').addClass('hidden');

    switch (action) {
        case 'edit':
            editSite(contextMenuSiteId);
            break;
        case 'status':
            changeStatus(contextMenuSiteId);
            break;
        case 'reminder':
            addReminder(contextMenuSiteId);
            break;
        case 'chat':
            showChatHistory(contextMenuSiteId);
            break;
        case 'whatsapp':
            sendWhatsApp(contextMenuSiteId);
            break;
        case 'mail':
            sendMail(contextMenuSiteId);
            break;
        case 'delete':
            deleteSite(contextMenuSiteId);
            break;
    }
}

function changeStatus(siteId) {
    Swal.fire({
        title: 'Durum Değiştir',
        html: `
            <div class="space-y-2 p-4">
                <button onclick="updateSiteStatus(${siteId}, 'requested')" class="w-full px-4 py-3 bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-lg transition font-semibold">
                    <i class="fa-solid fa-paper-plane mr-2"></i>İstendi
                </button>
                <button onclick="updateSiteStatus(${siteId}, 'accepted')" class="w-full px-4 py-3 bg-green-100 hover:bg-green-200 text-green-800 rounded-lg transition font-semibold">
                    <i class="fa-solid fa-check mr-2"></i>Kabul Etti
                </button>
                <button onclick="renewSite(${siteId})" class="w-full px-4 py-3 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded-lg transition font-semibold">
                    <i class="fa-solid fa-check-circle mr-2"></i>Yenilendi (+1 Yıl)
                </button>
                <button onclick="updateSiteStatus(${siteId}, 'transferred')" class="w-full px-4 py-3 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded-lg transition font-semibold">
                    <i class="fa-solid fa-exchange-alt mr-2"></i>Transfer
                </button>
                <button onclick="updateSiteStatus(${siteId}, 'cancelled')" class="w-full px-4 py-3 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg transition font-semibold">
                    <i class="fa-solid fa-ban mr-2"></i>İptal
                </button>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Kapat',
        width: '400px'
    });
}

function updateSiteStatus(siteId, status) {
    Swal.close();

    // Status labels
    const statusLabels = {
        'requested': 'İstendi',
        'accepted': 'Kabul Etti',
        'active': 'Yenilendi',
        'transferred': 'Transfer',
        'cancelled': 'İptal'
    };

    $.post('api/sites.php', {
        action: 'update_status',
        id: siteId,
        status: status
    }, function (res) {
        if (res.status === 'success') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Durum: ${statusLabels[status]}`,
                showConfirmButton: false,
                timer: 2000
            });
            loadSites();
        } else {
            Swal.fire('Hata!', res.message, 'error');
        }
    });
}

function changeSiteStatus(siteId, status) {
    const statusText = status === 'cancelled' ? 'İptal Edildi' : 'Transfer Edildi';
    Swal.fire({
        title: 'Durum Değiştir',
        text: `Site "${statusText}" olarak işaretlenecek. Onaylıyor musunuz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', {
                action: 'update_status',
                id: siteId,
                status: status
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
                    loadSites();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

function sendWhatsApp(siteId) {
    if (window.currentUser && !window.currentUser.permissions.whatsapp) {
        Swal.fire({
            icon: 'error',
            title: 'Yetki Yok',
            text: 'WhatsApp mesajı gönderme yetkiniz bulunmamaktadır.'
        });
        return;
    }

    $.when(
        $.get('api/sites.php', { action: 'get', id: siteId }),
        $.get('api/templates.php', { action: 'list' }),
        $.get('api/settings.php') // Fetch settings to check API availability
    ).done(function (siteRes, templatesRes, settingsRes) {
        const site = siteRes[0].data;
        const templates = templatesRes[0].data || [];
        const settings = settingsRes[0]; // Assuming json_response directly returns the object or inside data? 
        // settings.php returns object directly in recent implementations based on view_file api/settings.php

        const hasEvolution = settings.evolution_api_url && settings.evolution_api_key && settings.evolution_instance_name;

        $.get('api/customers.php', { action: 'get', id: site.customer_id }, function (custRes) {
            const customer = custRes.data;
            let optionsHtml = '<option value="">Şablon Seçiniz...</option>';
            templates.filter(t => t.type === 'whatsapp').forEach(t => {
                optionsHtml += `<option value="${t.id}" data-message="${encodeURIComponent(t.message)}">${t.title}</option>`;
            });

            const sendButtonText = hasEvolution ? '<i class="fa-solid fa-paper-plane mr-2"></i>API ile Gönder' : '<i class="fa-brands fa-whatsapp mr-2"></i>WhatsApp Web';

            Swal.fire({
                title: '<i class="fa-brands fa-whatsapp text-green-600 mr-2"></i>WhatsApp Gönder',
                html: `
                    <div class="text-left space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Müşteri</label>
                                <input type="text" id="waCustomerName" value="${customer.full_name}" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Telefon</label>
                                <input type="text" id="waCustomerPhone" value="${customer.phone}" class="w-full border rounded px-3 py-2 text-sm">
                            </div>
                        </div>
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
                                <span class="text-sm font-bold text-gray-700">Zamanlı Gönderim (İleri Tarihli)</span>
                             </label>
                             <div id="waScheduleContainer" class="hidden pl-6">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gönderim Tarihi ve Saati</label>
                                <input type="datetime-local" id="waScheduleDate" class="w-full border rounded px-3 py-2 text-sm">
                                <p class="text-xs text-gray-500 mt-1">Seçilen tarih ve saatte otomatik gönderilecektir.</p>
                             </div>
                        </div>
                        ` : ''}

                        ${!hasEvolution ? '<p class="text-xs text-orange-600 bg-orange-50 p-2 rounded mt-2">Not: Evolution API ayarlı değil. WhatsApp Web açılacak.</p>' : '<p class="text-xs text-green-600 bg-green-50 p-2 rounded mt-2">API ayarlı. Mesaj otomatik gönderilecek.</p>'}
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: sendButtonText,
                confirmButtonColor: '#25D366',
                didOpen: () => {
                    // Template change handler
                    $('#waTemplate').on('change', function () {
                        const template = $(this).val();
                        const name = $('#waCustomerName').val();
                        const siteParams = {
                            domain: site.domain || '',
                            renewal_date: site.renewal_date || '',
                            package_type: site.package_type || ''
                        };

                        let message = '';

                        const selectedOption = $(this).find(':selected');
                        const encodedMsg = selectedOption.data('message');

                        if (encodedMsg) {
                            message = decodeURIComponent(encodedMsg);
                            message = message.replace(/\[ADI SOYADI\]/g, name || '');
                            message = message.replace(/\[SITE\]/g, siteParams.domain);
                            message = message.replace(/\[TARIH\]/g, formatDate(siteParams.renewal_date));
                            message = message.replace(/\[PAKET\]/g, siteParams.package_type);
                        }

                        $('#waMessage').val(message);
                    });

                    // Schedule toggle handler
                    $('#waScheduleToggle').on('change', function () {
                        if ($(this).is(':checked')) {
                            $('#waScheduleContainer').removeClass('hidden');
                        } else {
                            $('#waScheduleContainer').addClass('hidden');
                        }
                    });
                },
                preConfirm: () => {
                    const phone = $('#waCustomerPhone').val();
                    const message = $('#waMessage').val();
                    const isScheduled = $('#waScheduleToggle').is(':checked');
                    const scheduleDate = $('#waScheduleDate').val();

                    if (!phone || !message) {
                        Swal.showValidationMessage('Lütfen tüm alanları doldurun');
                        return false;
                    }

                    if (isScheduled && !scheduleDate) {
                        Swal.showValidationMessage('Lütfen gönderim tarihi seçiniz');
                        return false;
                    }

                    return { phone, message, isScheduled, scheduleDate };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const phone = result.value.phone.replace(/\D/g, '');
                    const cleanPhone = phone.startsWith('0') ? phone.substring(1) : phone;
                    const finalPhone = cleanPhone.startsWith('90') ? cleanPhone : '90' + cleanPhone;

                    // SCHEDULE LOGIC
                    if (result.value.isScheduled) {
                        $.post('api/sites.php', {
                            action: 'schedule_whatsapp',
                            site_id: siteId,
                            phone: finalPhone,
                            message: result.value.message,
                            scheduled_at: result.value.scheduleDate
                        }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire('Planlandı', 'Mesaj gönderim kuyruğuna eklendi.', 'success');
                            } else {
                                Swal.fire('Hata', res.message, 'error');
                            }
                        }).fail(function (xhr) {
                            let msg = 'İşlem başarısız.';
                            if (xhr.status === 403) msg = 'WhatsApp planlama yetkiniz yok!';
                            else if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                            Swal.fire('Hata', msg, 'error');
                        });
                        return; // Stop execution here
                    }


                    if (hasEvolution) {
                        // Send via API (Immediate)
                        Swal.fire({
                            title: 'Gönderiliyor...',
                            text: 'Lütfen bekleyin',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });

                        $.post('api/sites.php', {
                            action: 'send_whatsapp_api',
                            site_id: siteId,
                            phone: finalPhone,
                            message: result.value.message
                        }, function (res) {
                            if (res.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Başarılı',
                                    text: 'Mesaj API üzerinden gönderildi.',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => loadSites());
                            } else {
                                Swal.fire('Hata', 'API hatası: ' + res.message, 'error');
                            }
                        }).fail(function (xhr) {
                            let msg = 'Sunucu hatası oluştu.';
                            if (xhr.status === 403) {
                                msg = 'WhatsApp gönderme yetkiniz bulunmamaktadır!';
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                msg = 'Hata: ' + xhr.status;
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Yetki Yok',
                                text: msg
                            });
                        });

                    } else {
                        // Fallback to Web
                        const url = `https://web.whatsapp.com/send?phone=${finalPhone}&text=${encodeURIComponent(result.value.message)}`;
                        window.open(url, '_blank');

                        $.post('api/sites.php', {
                            action: 'log_whatsapp',
                            site_id: siteId
                        });

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'WhatsApp açıldı ve durum güncellendi',
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => loadSites());
                    }
                }
            });
        });
    });
}

function sendMail(siteId) {
    if (window.currentUser && !window.currentUser.permissions.email) {
        Swal.fire({
            icon: 'error',
            title: 'Yetki Yok',
            text: 'E-posta gönderme yetkiniz bulunmamaktadır.'
        });
        return;
    }

    $.when(
        $.get('api/sites.php', { action: 'get', id: siteId }),
        $.get('api/templates.php', { action: 'list' })
    ).done(function (siteRes, templatesRes) {
        const site = siteRes[0].data;
        const templates = templatesRes[0].data || [];

        $.get('api/customers.php', { action: 'get', id: site.customer_id }, function (custRes) {
            const customer = custRes.data;
            let optionsHtml = '<option value="">Şablon Seçiniz...</option>';
            // Allow WA templates to be used in Mail too, or filter if type exists
            templates.forEach(t => {
                optionsHtml += `<option value="${t.id}" data-message="${encodeURIComponent(t.message)}" data-title="${t.title}">${t.title}</option>`;
            });

            Swal.fire({
                title: '<i class="fa-solid fa-envelope text-blue-600 mr-2"></i>Mail Gönder',
                html: `
                    <div class="text-left space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase">Müşteri</label><div class="font-medium">${customer.full_name}</div></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase">Site</label><div class="font-medium">${site.domain}</div></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">E-posta Adresi</label>
                            <input type="email" id="mailTo" value="${customer.email || ''}" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Şablon</label>
                            <select id="mailTemplate" class="w-full border rounded px-3 py-2 bg-gray-50">${optionsHtml}</select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Konu</label>
                            <input type="text" id="mailSubject" class="w-full border rounded px-3 py-2" value="Site Yenileme Hakkında">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Mesaj</label>
                            <textarea id="mailMessage" rows="8" class="w-full border rounded px-3 py-2"></textarea>
                        </div>
                    </div>
                `,
                width: '600px',
                showCancelButton: true,
                confirmButtonText: 'Gönder',
                confirmButtonColor: '#3b82f6',
                didOpen: () => {
                    $('#mailTemplate').on('change', function () {
                        const encodedMsg = $(this).find(':selected').data('message');
                        // const title = $(this).find(':selected').data('title');
                        // if(title) $('#mailSubject').val(title); // Optional: change subject
                        if (encodedMsg) {
                            let msg = decodeURIComponent(encodedMsg);
                            const name = customer.full_name || ''; // Could optionally make customer name editable too but email is priority

                            msg = msg.replace(/\[ADI SOYADI\]/g, name);
                            msg = msg.replace(/\[SITE\]/g, site.domain || '');
                            msg = msg.replace(/\[TARIH\]/g, formatDate(site.renewal_date) || '');
                            msg = msg.replace(/\[PAKET\]/g, site.package_type || '');
                            $('#mailMessage').val(msg);
                        }
                    });
                },
                preConfirm: () => {
                    const subject = $('#mailSubject').val();
                    const message = $('#mailMessage').val();
                    const to = $('#mailTo').val();

                    if (!to) { Swal.showValidationMessage('E-posta adresi zorunludur'); return false; }
                    if (!subject || !message) { Swal.showValidationMessage('Konu ve mesaj zorunludur'); return false; }

                    return { to, subject, message };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Gönderiliyor...', didOpen: () => Swal.showLoading() });
                    $.post('api/send_mail.php', {
                        action: 'send',
                        site_id: siteId,
                        to: result.value.to,
                        subject: result.value.subject,
                        message: result.value.message
                    }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', res.message, 'success');
                        } else {
                            // Fix for [object Object] error
                            let errorMsg = res.message;
                            if (res.logs && Array.isArray(res.logs)) {
                                errorMsg += '<br><pre class="text-xs text-left mt-2 bg-gray-100 p-2 overflow-auto h-32">' + res.logs.join('\n') + '</pre>';
                            } else if (typeof res.logs === 'object') {
                                errorMsg += '<br><pre class="text-xs text-left mt-2 bg-gray-100 p-2 overflow-auto h-32">' + JSON.stringify(res.logs, null, 2) + '</pre>';
                            }
                            Swal.fire('Hata', errorMsg, 'error');
                        }
                    }).fail(function (xhr) {
                        Swal.fire('Hata', 'Sunucu hatası: ' + xhr.responseText, 'error');
                    });
                }
            });
        });
    });
}

function addReminder(siteId) {
    // Calculate tomorrow's date
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];

    Swal.fire({
        title: 'Hatırlatma Ekle',
        html: `
            <div class="text-left space-y-3">
                <div>
                    <label class="block text-sm font-semibold mb-2">Başlık</label>
                    <input type="text" id="reminderTitle" value="Yenileme Hatırlatması" class="w-full border rounded px-3 py-2">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold mb-2">Tarih</label>
                        <input type="date" id="reminderDate" value="${tomorrowStr}" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Saat</label>
                        <input type="time" id="reminderTime" value="09:00" class="w-full border rounded px-3 py-2">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Not</label>
                    <textarea id="reminderNote" rows="3" class="w-full border rounded px-3 py-2" placeholder="Opsiyonel..."></textarea>
                </div>
                <div class="flex items-center gap-3 bg-yellow-50 p-3 rounded-lg border border-yellow-200">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="reminderAlarm" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-500"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900">
                                <i class="fa-solid fa-bell mr-1"></i>Alarm Kur (WhatsApp Bildirimi)
                            </span>
                    </label>
                </div>
            </div>
            `,
        showCancelButton: true,
        confirmButtonText: 'Kaydet',
        cancelButtonText: 'İptal',
        width: '500px',
        preConfirm: () => {
            const title = $('#reminderTitle').val();
            const date = $('#reminderDate').val();
            const time = $('#reminderTime').val();
            const note = $('#reminderNote').val();
            const alarm = $('#reminderAlarm').is(':checked') ? 1 : 0;

            if (!title || !date) {
                Swal.showValidationMessage('Lütfen başlık ve tarih girin');
                return false;
            }

            return { title, date, time, note, alarm };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', {
                action: 'add_reminder',
                site_id: siteId,
                title: result.value.title,
                reminder_date: result.value.date,
                reminder_time: result.value.time,
                note: result.value.note,
                alarm_enabled: result.value.alarm
            }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Eklendi!', 'Hatırlatma başarıyla eklendi', 'success').then(() => loadSites());
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

// editSite is in sites.php inline script (opens modal)

function renewSite(id) {
    Swal.fire({
        title: 'Site Yenile',
        text: 'Site +1 yıl yenilenecek. Onaylıyor musunuz?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, Yenile',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', { action: 'renew', id: id }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Yenilendi!', res.message, 'success');
                    loadSites();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

function deleteSite(id) {
    Swal.fire({
        title: 'Site Silinecek',
        text: 'Bu işlem geri alınamaz!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('api/sites.php', { action: 'delete', id: id }, function (res) {
                if (res.status === 'success') {
                    Swal.fire('Silindi!', res.message, 'success');
                    loadSites();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        }
    });
}

// Excel Export
function exportSites() {
    window.location.href = `api/sites.php?action=export&filter=${currentFilter}&search=${searchQuery}`;
}

// Chat History Functions
function showChatHistory(mixedId, isCustomer = false) {
    console.log('🔵 showChatHistory called', { mixedId, isCustomer });

    Swal.close();
    Swal.fire({
        title: 'Sohbet Yükleniyor...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false
    });

    if (!isCustomer) {
        console.log('🔍 Fetching site data for siteId:', mixedId);

        $.get('api/sites.php', { action: 'get', id: mixedId }, function (res) {
            console.log('✅ Site data received:', res);

            if (res.status === 'success' && res.data.customer_id) {
                console.log('🔍 Fetching chat for customerId:', res.data.customer_id);
                fetchChat(res.data.customer_id);
            } else {
                console.error('❌ Customer not found in site data:', res);
                Swal.fire('Hata', 'Müşteri bulunamadı', 'error');
            }
        }).fail(function (xhr, status, error) {
            console.error('❌ Failed to fetch site data:', { xhr, status, error, responseText: xhr.responseText });
            Swal.fire('Hata', 'Site bilgisi alınamadı: ' + error, 'error');
        });
    } else {
        console.log('🔍 Direct customer chat for customerId:', mixedId);
        fetchChat(mixedId);
    }

    function fetchChat(customerId) {
        console.log('🔵 fetchChat called for customerId:', customerId);

        $.get('api/whatsapp.php', { action: 'get_messages_by_customer', customer_id: customerId }, function (res) {
            console.log('✅ WhatsApp API response:', res);

            if (res.status === 'success') {
                console.log('📱 Rendering chat modal with', res.data.length, 'messages');
                renderChatModal(res.data, res.jid);
            } else {
                console.error('❌ WhatsApp API error:', res);
                Swal.fire('Hata', res.message, 'error');
            }
        }).fail(function (xhr, status, error) {
            console.error('❌ Failed to fetch WhatsApp messages:', {
                xhr,
                status,
                error,
                responseText: xhr.responseText,
                url: 'api/whatsapp.php?action=get_messages_by_customer&customer_id=' + customerId
            });

            Swal.fire({
                icon: 'error',
                title: 'Mesaj Alınamadı',
                html: `<p>WhatsApp mesajları alınamadı.</p><pre class="text-xs text-left mt-2 bg-gray-100 p-2 max-h-32 overflow-auto">${xhr.responseText || error}</pre>`,
                width: '600px'
            });
        });
    }
}

var chatPollInterval;

function renderChatModal(messages, jid) {
    if (chatPollInterval) clearInterval(chatPollInterval);

    const generateBubble = (msg) => {
        console.log('Rendering message:', msg); // DEBUG

        let icon = '';
        if (msg.type === 'image') icon = '<i class="fa-solid fa-image mr-1"></i>';
        else if (msg.type === 'video') icon = '<i class="fa-solid fa-video mr-1"></i>';
        else if (msg.type === 'document') icon = '<i class="fa-solid fa-file mr-1"></i>';
        else if (msg.type === 'audio') icon = '<i class="fa-solid fa-microphone mr-1"></i>';

        // Convert fromMe to boolean (API might send 0/1 or true/false)
        const isMe = Boolean(msg.fromMe);
        const align = isMe ? 'self-end' : 'self-start';
        const color = isMe ? 'bg-green-100 text-gray-800' : 'bg-white text-gray-800';
        const time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'numeric' });

        return `
        <div class="${align} max-w-[80%] rounded-lg shadow-sm p-3 ${color}">
            <p class="text-sm pb-1 break-words py-1">${icon}${msg.content}</p>
            <p class="text-[10px] text-gray-400 text-right">${time}</p>
        </div>
        `;
    };

    let containerHtml = `<div class="flex flex-col space-y-3 h-[400px] overflow-y-auto p-4 bg-gray-100 rounded-lg" id="chatContainer">`;
    containerHtml += messages.length === 0
        ? '<p class="text-center text-gray-500 mt-10">Mesaj geçmişi bulunamadı.</p>'
        : messages.map(generateBubble).join('');
    containerHtml += '</div>';

    containerHtml += `
    <div class="mt-4 flex gap-2">
        <input type="text" id="chatInput" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Mesaj yazın...">
        <button id="sendChatBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
    `;

    Swal.fire({
        title: `Sohbet: ${jid}`,
        html: containerHtml,
        width: '600px',
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Kapat',
        allowOutsideClick: false, // Prevent accidental close
        allowEscapeKey: false, // Prevent ESC key close
        willClose: () => {
            if (chatPollInterval) clearInterval(chatPollInterval);
        },
        didOpen: () => {
            const container = document.getElementById('chatContainer');
            if (container) container.scrollTop = container.scrollHeight;

            // START AUTO-REFRESH POLLING (1 second interval)
            chatPollInterval = setInterval(() => {
                console.log('🔄 [Sites] Auto-refreshing chat messages...');

                $.post('api/whatsapp.php', { action: 'fetch_messages', jid: jid, force_refresh: 1 }, function (res) {
                    if (res.status === 'success' && res.data) {
                        const currentScroll = container.scrollTop;
                        const maxScroll = container.scrollHeight - container.clientHeight;
                        const isAtBottom = (maxScroll - currentScroll) < 50;

                        // Update chat content
                        const newContent = res.data.length === 0
                            ? '<p class="text-center text-gray-500 mt-10">Mesaj geçmişi bulunamadı.</p>'
                            : res.data.map(generateBubble).join('');

                        $('#chatContainer').html(newContent);

                        // Auto-scroll only if user was at bottom
                        if (isAtBottom) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }).fail(function () {
                    console.warn('⚠️ [Sites] Chat refresh failed');
                });
            }, 1000); // 1 second

            console.log('✅ [Sites] Chat polling started (1 sec interval)');

            // Send Logic
            $('#sendChatBtn').click(function () {
                const msg = $('#chatInput').val();
                if (!msg.trim()) return;

                $('#chatInput').prop('disabled', true);
                $(this).prop('disabled', true);

                $.post('api/whatsapp.php', {
                    action: 'send_message',
                    jid: jid,
                    message: msg
                }, function (res) {
                    if (res.status === 'success') {
                        $('#chatInput').val('').prop('disabled', false).focus();
                        $('#sendChatBtn').prop('disabled', false);
                    } else {
                        Swal.showValidationMessage(res.message || 'Gönderilemedi');
                        $('#chatInput').prop('disabled', false);
                        $('#sendChatBtn').prop('disabled', false);
                    }
                }).fail(function () {
                    Swal.showValidationMessage('API Hatası');
                    $('#chatInput').prop('disabled', false);
                    $('#sendChatBtn').prop('disabled', false);
                });
            });

            $('#chatInput').on('keypress', function (e) {
                if (e.which == 13) $('#sendChatBtn').click();
            });
        }
    });
}
