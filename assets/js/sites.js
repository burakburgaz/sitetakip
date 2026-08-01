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

    // Unified Menu Trigger (Click and Right Click)
    $(document).on('click', '.site-row', function (e) {
        // Don't trigger if clicking on specific interactive elements
        if ($(e.target).closest('a, button, input, .site-checkbox, .actions-menu').length) return;

        e.preventDefault();
        const siteId = $(this).data('id');
        const domain = $(this).find('a').first().text().trim();
        const row = $(this);

        showSiteMenu(e.pageX, e.pageY, siteId, domain, row);
    });

    $(document).on('contextmenu', '.site-row', function (e) {
        e.preventDefault();
        const siteId = $(this).data('id');
        const domain = $(this).find('a').first().text().trim();
        const row = $(this);

        showSiteMenu(e.pageX, e.pageY, siteId, domain, row);
        return false;
    });

    // Double click to edit
    $(document).on('dblclick', '.site-row', function (e) {
        if ($(e.target).closest('a, button, input').length) return;
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
    $('#sitesTable').html(`
        <div class="flex flex-col items-center justify-center py-24 space-y-4">
            <div class="w-12 h-12 border-4 border-blue-500/20 border-t-blue-500 rounded-full animate-spin"></div>
            <p class="text-slate-400 font-medium animate-pulse">Siteler yükleniyor...</p>
        </div>
    `);

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
        $('#sitesTable').html(`
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="w-20 h-20 rounded-full bg-slate-800/50 flex items-center justify-center mb-6">
                    <i class="fa-solid fa-globe text-slate-600 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-300 mb-2">Henüz Site Yok</h3>
                <p class="text-slate-500 max-w-xs">Aradığınız kriterlere uygun herhangi bir kayıt bulunamadı.</p>
            </div>
        `);
        return;
    }

    let html = `
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/5 bg-white/[0.02]">
                    <th class="px-6 py-4 w-12 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-center">
                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" 
                        class="w-4 h-4 rounded border-white/10 bg-white/5 text-blue-600 focus:ring-offset-slate-900 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Site Bilgileri</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest hidden md:table-cell">Müşteri</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest hidden lg:table-cell">Paket</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest hidden sm:table-cell">Yenileme</th>
                    <th class="px-6 py-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest hidden md:table-cell text-center">Durum</th>
                    <th class="px-6 py-4 w-20 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
    `;

    sites.forEach(s => {
        // Status formatting
        let statusConfig = {
            icon: 'fa-circle',
            color: 'text-slate-500',
            bg: 'bg-slate-500/10',
            border: 'border-slate-500/20',
            label: 'Bilinmiyor'
        };

        if (s.status === 'requested') {
            statusConfig = { icon: 'fa-paper-plane', color: 'text-blue-400', bg: 'bg-blue-400/10', border: 'border-blue-400/20', label: 'İstendi' };
        } else if (s.status === 'accepted') {
            statusConfig = { icon: 'fa-check-double', color: 'text-emerald-400', bg: 'bg-emerald-400/10', border: 'border-emerald-400/20', label: 'Kabul Etti' };
        } else if (s.status === 'active') {
            if (s.last_renewed_at) {
                statusConfig = { icon: 'fa-check-circle', color: 'text-emerald-500', bg: 'bg-emerald-500/10', border: 'border-emerald-500/20', label: 'Yenilendi' };
            } else {
                statusConfig = { icon: 'fa-check', color: 'text-emerald-400', bg: 'bg-emerald-400/10', border: 'border-emerald-400/20', label: 'Aktif' };
            }
        } else if (s.status === 'cancelled') {
            statusConfig = { icon: 'fa-ban', color: 'text-rose-400', bg: 'bg-rose-400/10', border: 'border-rose-400/20', label: 'İptal' };
        } else if (s.status === 'transferred') {
            statusConfig = { icon: 'fa-exchange-alt', color: 'text-indigo-400', bg: 'bg-indigo-400/10', border: 'border-indigo-400/20', label: 'Transfer' };
        } else if (s.status === 'expired') {
            statusConfig = { icon: 'fa-triangle-exclamation', color: 'text-amber-400', bg: 'bg-amber-400/10', border: 'border-amber-400/20', label: 'Süresi Doldu' };
        }

        const statusBadge = `
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full ${statusConfig.bg} ${statusConfig.border} border ${statusConfig.color}" title="${statusConfig.label}">
                <i class="fa-solid ${statusConfig.icon} text-[10px]"></i>
                <span class="text-[10px] font-bold uppercase tracking-wider">${statusConfig.label}</span>
            </div>
        `;

        // Package badge
        const packageBadge = s.package_type === 'PRO'
            ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">PRO</span>'
            : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-500/10 text-slate-400 border border-white/5">BASIC</span>';

        // Days until renewal
        const daysRemaining = parseInt(s.days_until);
        let daysColor = 'text-emerald-400';
        if (daysRemaining <= 7) daysColor = 'text-rose-400';
        else if (daysRemaining <= 20) daysColor = 'text-amber-400';

        const daysText = daysRemaining >= 0
            ? `<span class="${daysColor} font-bold">${daysRemaining} gün kaldı</span>`
            : `<span class="text-rose-500 font-bold">${Math.abs(daysRemaining)} gün gecikti</span>`;

        // API Date Logic
        let apiExpiresInfo = '';
        if (s.api_expires_at) {
            const apiDate = new Date(s.api_expires_at);
            apiExpiresInfo = `
                <div class="flex items-center gap-1.5 mt-1">
                    <div class="w-1 h-1 rounded-full bg-blue-500/50"></div>
                    <span class="text-[10px] text-blue-400/60 font-medium" title="Hostinger Verisi">
                        Hostinger: ${apiDate.toLocaleDateString('tr-TR')}
                    </span>
                    ${new Date(s.api_expires_at) > new Date(s.renewal_date) ? `
                        <button onclick="acceptRenewalSite(${s.id})" class="ml-1 text-[9px] bg-blue-500/20 text-blue-400 px-1.5 py-0.5 rounded border border-blue-500/30 hover:bg-blue-500 hover:text-white transition-all active:scale-90" title="Hostinger Tarihini Uygula">
                            Onayla
                        </button>
                    ` : ''}
                </div>
            `;
        }

        // Site age
        const siteAge = calculateSiteAge(s.start_date);
        const ageBadge = (siteAge && parseInt(siteAge) >= 2)
            ? `<div class="flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-gradient-to-tr from-purple-600 to-indigo-600 rounded-lg shadow-lg shadow-indigo-500/20 ml-2" title="${siteAge} Yıllık Değerli Müşteri">
                ${siteAge}
               </div>`
            : '';

        html += `
            <tr class="site-row group hover:bg-white/[0.02] transition-colors relative" data-id="${s.id}">
                <td class="px-6 py-4 text-center">
                    <input type="checkbox" class="site-checkbox w-4 h-4 rounded border-white/10 bg-white/5 text-blue-600 focus:ring-offset-slate-900 focus:ring-blue-500" value="${s.id}" onchange="updateBulkActions()">
                </td>
                <td class="px-6 py-5">
                    <div class="flex items-center">
                        <div class="flex flex-col">
                            <div class="flex items-center">
                                <a href="http://${s.domain}" target="_blank" class="text-base font-bold text-white hover:text-blue-400 transition-colors flex items-center gap-1">
                                    ${s.domain}
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-0 group-hover:opacity-100 transition-all ml-1"></i>
                                </a>
                                ${ageBadge}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5 font-medium flex items-center gap-2">
                                ${s.start_date ? `<span>Kayıt: ${formatDate(s.start_date)}</span>` : ''}
                                ${apiExpiresInfo}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-5 hidden md:table-cell">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-300">${s.customer_name || '<span class="text-red-400 italic text-xs">Müşteri Yok</span>'}</span>
                        ${s.customer_phone ? `<span class="text-[10px] text-slate-500 font-medium">${s.customer_phone}</span>` : ''}
                    </div>
                </td>
                <td class="px-6 py-5 hidden lg:table-cell whitespace-nowrap">
                    ${packageBadge}
                </td>
                <td class="px-6 py-5 hidden sm:table-cell whitespace-nowrap">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-300">${formatDate(s.renewal_date)}</span>
                        <span class="text-[11px] font-semibold">${daysText}</span>
                    </div>
                </td>
                <td class="px-6 py-5 hidden md:table-cell text-center">
                    ${statusBadge}
                </td>
                <td class="px-6 py-5 text-right">
                    <div class="relative inline-block">
                        <button onclick="toggleActionsMenu(${s.id})" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 text-slate-400 hover:text-white hover:bg-white/10 transition-all flex items-center justify-center">
                            <i class="fa-solid fa-ellipsis-v"></i>
                        </button>
                        <div id="actions-${s.id}" class="actions-menu hidden absolute right-0 mt-2 w-52 glass-card rounded-2xl shadow-2xl border border-white/10 z-[100] overflow-hidden backdrop-blur-3xl py-1">
                            <button onclick="editSite(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center gap-3 text-slate-300 transition-all">
                                <i class="fa-solid fa-pen-to-square text-blue-400 w-4"></i>
                                <span class="text-sm font-semibold">Düzenle</span>
                            </button>
                            <button onclick="toggleReminder(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center gap-3 text-slate-300 transition-all">
                                <i class="fa-solid fa-bell text-amber-400 w-4"></i>
                                <span class="text-sm font-semibold">Hatırlatma</span>
                            </button>
                            <button onclick="sendWhatsApp(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center gap-3 text-slate-300 transition-all">
                                <i class="fa-brands fa-whatsapp text-emerald-400 w-4"></i>
                                <span class="text-sm font-semibold">WhatsApp</span>
                            </button>
                            <button onclick="openHistoryModal(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-3 hover:bg-white/5 flex items-center gap-3 text-slate-300 transition-all">
                                <i class="fa-solid fa-history text-indigo-400 w-4"></i>
                                <span class="text-sm font-semibold">Geçmiş</span>
                            </button>
                            ${(currentFilter === 'cancelled' || currentFilter === 'transferred') ? `
                                <div class="my-1 border-t border-white/5"></div>
                                <button onclick="deleteSite(${s.id}); toggleActionsMenu(${s.id});" class="w-full text-left px-4 py-3 hover:bg-rose-500/10 flex items-center gap-3 text-rose-400 transition-all">
                                    <i class="fa-solid fa-trash w-4"></i>
                                    <span class="text-sm font-bold">Sil</span>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </td>
            </tr>
        `;
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
    const menu = $(`#actions-${siteId}`);
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
            <div class="grid grid-cols-1 gap-3 mt-4">
                <button onclick="bulkRenew()" class="flex items-center gap-3 px-5 py-3.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-2xl hover:bg-emerald-500 hover:text-white transition-all font-bold group">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-600/20 transition-all">
                        <i class="fa-solid fa-sync"></i>
                    </div>
                    <span>Yenileme Tarihini +1 Yıl Uzat</span>
                </button>
                <button onclick="bulkWhatsApp()" class="flex items-center gap-3 px-5 py-3.5 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-2xl hover:bg-blue-500 hover:text-white transition-all font-bold group">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center group-hover:bg-blue-600/20 transition-all">
                        <i class="fa-brands fa-whatsapp font-bold"></i>
                    </div>
                    <span>Toplu WhatsApp Mesajı Gönder</span>
                </button>
                <button onclick="bulkChangeStatus('cancelled')" class="flex items-center gap-3 px-5 py-3.5 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-2xl hover:bg-rose-500 hover:text-white transition-all font-bold group">
                    <div class="w-8 h-8 rounded-lg bg-rose-500/10 flex items-center justify-center group-hover:bg-rose-600/20 transition-all">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <span>Seçilenleri İptal Et</span>
                </button>
                <button onclick="bulkChangeStatus('transferred')" class="flex items-center gap-3 px-5 py-3.5 bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 rounded-2xl hover:bg-indigo-500 hover:text-white transition-all font-bold group">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center group-hover:bg-indigo-600/20 transition-all">
                        <i class="fa-solid fa-exchange-alt"></i>
                    </div>
                    <span>Seçilenleri Transfer Et</span>
                </button>
                <button onclick="bulkDelete()" class="flex items-center gap-3 px-5 py-3.5 bg-slate-500/10 text-slate-400 border border-slate-500/20 rounded-2xl hover:bg-rose-600 hover:text-white transition-all font-bold group">
                    <div class="w-8 h-8 rounded-lg bg-slate-500/10 flex items-center justify-center group-hover:bg-rose-600/20 transition-all">
                        <i class="fa-solid fa-trash"></i>
                    </div>
                    <span>Seçilenleri Kalıcı Olarak Sil</span>
                </button>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Kapat',
        customClass: {
            popup: 'glass-card border-white/10 rounded-3xl',
            title: 'font-["Outfit"] text-white',
            htmlContainer: 'text-slate-400',
            cancelButton: 'bg-white/5 text-slate-300 rounded-xl hover:bg-white/10'
        }
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

function showSiteMenu(x, y, siteId, domain, row) {
    contextMenuSiteId = siteId;
    const menu = $('#contextMenu');

    // Set Title
    $('#contextMenuTitle').text(domain || 'İşlemler');

    // Handle Delete Button Visibility based on status (like dashboard)
    const statusText = row.find('[title]').last().attr('title'); // Extract from status badge
    if (statusText === 'İptal' || statusText === 'Transfer' || currentFilter === 'cancelled' || currentFilter === 'transferred') {
        $('#delete-context-btn').removeClass('hidden');
    } else {
        $('#delete-context-btn').addClass('hidden');
    }

    // Position Menu
    menu.removeClass('hidden');

    // Adjust position if it goes off screen
    const menuWidth = menu.outerWidth();
    const menuHeight = menu.outerHeight();
    const windowWidth = $(window).width();
    const windowHeight = $(window).height();

    // Sola dayalı hizalama (Menu to the left of cursor)
    let finalX = x - menuWidth;
    let finalY = y;

    // Ekran sınırları kontrolü
    if (finalX < 10) finalX = x + 5; // Click near left edge -> show to the right
    if (finalY + menuHeight > windowHeight) finalY = windowHeight - menuHeight - 10;
    if (finalY < 0) finalY = 10;

    menu.css({ top: finalY + 'px', left: finalX + 'px' });
}

function showContextMenu(x, y, siteId) {
    // Fallback for older calls if any
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
        case 'renew':
            renewSite(contextMenuSiteId);
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
            let optionsHtml = '<option class="bg-slate-800 text-white" value="">Şablon Seçiniz...</option>';
            templates.filter(t => t.type === 'whatsapp').forEach(t => {
                optionsHtml += `<option class="bg-slate-800 text-white" value="${t.id}" data-message="${encodeURIComponent(t.message)}">${t.title}</option>`;
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
                            <select id="waTemplate" class="w-full border rounded px-3 py-2 bg-slate-800 text-white border-white/10">${optionsHtml}</select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Mesaj</label>
                            <textarea id="waMessage" rows="6" class="w-full border rounded px-3 py-2"></textarea>
                        </div>

                        ${hasEvolution ? `
                        <div class="border-t pt-4 mt-2">
                             <label class="flex items-center space-x-2 cursor-pointer mb-2">
                                <input type="checkbox" id="waScheduleToggle" class="form-checkbox h-4 w-4 text-green-600">
                                <span class="text-sm font-bold text-white">Zamanlı Gönderim (İleri Tarihli)</span>
                             </label>
                             <div id="waScheduleContainer" class="hidden pl-6">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Gönderim Tarihi ve Saati</label>
                                <input type="datetime-local" id="waScheduleDate" class="w-full border rounded px-3 py-2 text-sm">
                                <p class="text-xs text-gray-500 mt-1">Seçilen tarih ve saatte otomatik gönderilecektir.</p>
                             </div>
                        </div>
                        ` : ''}

                        ${!hasEvolution ? '<p class="text-xs text-orange-600 bg-orange-50 p-2 rounded mt-2">Not: Evolution API ayarlı değil. WhatsApp Web açılacak.</p>' : ''}
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
        // Premium Dark
        const color = isMe
            ? 'bg-emerald-500/10 text-emerald-100 border border-emerald-500/20 rounded-tr-none'
            : 'bg-white/5 text-slate-200 border border-white/10 rounded-tl-none';

        const time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'numeric' });

        return `
        <div class="${align} max-w-[80%] rounded-2xl p-4 shadow-sm ${color} mb-3 backdrop-blur-sm transition-all hover:scale-[1.01]">
            <p class="text-sm pb-1 break-words leading-relaxed">${icon}${msg.content}</p>
            <p class="text-[10px] text-white/40 text-right mt-1 font-medium">${time}</p>
        </div>
        `;
    };

    // Glass Container
    let containerHtml = `<div class="flex flex-col h-[500px] overflow-y-auto p-6 bg-black/40 rounded-3xl border border-white/5 custom-scrollbar backdrop-blur-md" id="chatContainer">`;

    if (messages.length === 0) {
        containerHtml += `
            <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-60">
                <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                    <i class="fa-regular fa-comments text-3xl"></i>
                </div>
                <p class="font-medium text-sm">Mesaj geçmişi bulunamadı</p>
            </div>`;
    } else {
        containerHtml += messages.map(generateBubble).join('');
    }
    containerHtml += '</div>';

    containerHtml += `
    <div class="mt-4 flex gap-3">
        <input type="text" id="chatInput" 
            class="flex-1 bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white text-sm focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 placeholder:text-slate-600 transition-all outline-none" 
            placeholder="Mesajınızı yazın...">
        <button id="sendChatBtn" 
            class="w-14 h-14 btn-gradient-primary rounded-2xl flex items-center justify-center shadow-lg shadow-primary/20 hover:scale-105 transition-transform active:scale-95 group">
            <i class="fa-solid fa-paper-plane text-xl text-white group-hover:rotate-12 transition-transform"></i>
        </button>
    </div>
    `;

    Swal.fire({
        title: `<span class="text-white logo-font tracking-wide text-xl">Sohbet: <span class="text-slate-400 text-base font-normal ml-2 font-sans">${jid}</span></span>`,
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
