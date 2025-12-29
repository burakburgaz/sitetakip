<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'WhatsApp Rehber - DReklam';
include 'includes/head.php';
?>

<body class="bg-gray-50 flex h-screen overflow-hidden">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="bg-white shadow-sm z-10 p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-brands fa-whatsapp text-green-600"></i>
                    WhatsApp Rehber
                </h2>
                <div class="flex gap-2">
                    <button onclick="syncChats()" id="syncBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-solid fa-sync"></i>
                        <span class="hidden sm:inline">Konuşmaları Getir (API)</span>
                    </button>
                    <a href="api/whatsapp.php?action=export_excel" target="_blank"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-solid fa-file-excel"></i>
                        <span class="hidden sm:inline">Excel İndir</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 sm:p-6">

            <!-- Filters & Actions -->
            <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-wrap gap-4 items-center justify-between">
                <div class="flex items-center gap-2">
                    <button onclick="filterType('all')"
                        class="filter-btn active px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Tümü</button>
                    <button onclick="filterType('individual')"
                        class="filter-btn px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Kişiler</button>
                    <button onclick="filterType('group')"
                        class="filter-btn px-3 py-1.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Gruplar</button>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="deleteSelected()"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-xs font-semibold transition">
                        <i class="fa-solid fa-trash mr-1"></i>Seçilenleri Sil
                    </button>
                    <button onclick="importSelected()"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded text-xs font-semibold transition">
                        <i class="fa-solid fa-user-plus mr-1"></i>Seçilenleri Müşteri Yap
                    </button>
                </div>
            </div>

            <!-- Contacts Table -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-bold">
                                <th class="p-4 w-10">
                                    <input type="checkbox" id="selectAll"
                                        class="rounded text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th class="p-4">İsim / Grup İsmi</th>
                                <th class="p-4">Numara / ID</th>
                                <th class="p-4">Tür</th>
                                <th class="p-4">Son İşlem</th>
                                <th class="p-4 text-right">Durum</th>
                            </tr>
                        </thead>
                        <tbody id="contactsTableBody" class="divide-y divide-gray-100 text-sm">
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-500">Yükleniyor...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentFilter = 'all';
        let chatPollInterval = null;  // Global polling interval

        $(document).ready(function () {
            loadContacts();

            $('#selectAll').change(function () {
                $('.contact-check').prop('checked', $(this).is(':checked'));
            });

            // Sidebar logic
            $('#toggleSidebar').click(function () {
                const sb = $('#sidebar');
                const isExpanded = sb.hasClass('w-64');
                const texts = $('.sidebar-text');
                const icon = $(this).find('i');
                if (isExpanded) {
                    sb.removeClass('w-64').addClass('w-20');
                    texts.addClass('hidden opacity-0');
                    icon.removeClass('fa-chevron-left').addClass('fa-chevron-right');
                } else {
                    sb.removeClass('w-20').addClass('w-64');
                    setTimeout(() => texts.removeClass('hidden opacity-0'), 150);
                    icon.removeClass('fa-chevron-right').addClass('fa-chevron-left');
                }
            });

            // Auto-refresh contact list every 15 seconds to update unread badges
            setInterval(function () {
                console.log('🔄 [Contacts] Auto-refreshing contact list...');
                loadContacts();
            }, 15000); // 15 seconds
        });

        function filterType(type) {
            currentFilter = type;
            $('.filter-btn').removeClass('active bg-indigo-100 text-indigo-700').addClass('bg-gray-100 text-gray-600');
            $(`.filter-btn:contains('${type === 'all' ? 'Tümü' : (type === 'individual' ? 'Kişiler' : 'Gruplar')}')`).removeClass('bg-gray-100 text-gray-600').addClass('active bg-indigo-100 text-indigo-700');
            loadContacts();
        }

        function loadContacts() {
            $.get('api/whatsapp.php', { action: 'list_contacts', type: currentFilter }, function (res) {
                if (res.status === 'success') {
                    renderContacts(res.data);
                }
            });
        }

        function renderContacts(contacts) {
            const tbody = $('#contactsTableBody');
            tbody.empty();

            if (contacts.length === 0) {
                tbody.html('<tr><td colspan="6" class="p-6 text-center text-gray-500">Kayıt bulunamadı. "Konuşmaları Getir" butonunu kullanın.</td></tr>');
                return;
            }

            contacts.forEach(c => {
                const displayName = c.type === 'group' ? (c.group_name || c.name) : c.name;
                const safeName = displayName.replace(/'/g, "\\'"); // Escape quotes
                const displayNumber = c.type === 'group' ? '-' : c.number;
                const typeLabel = c.type === 'group' ?
                    '<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs font-semibold">Grup</span>' :
                    '<span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-semibold">Kişi</span>';

                const importedBadge = c.is_imported ?
                    '<span class="text-green-600 text-xs font-bold"><i class="fa-solid fa-check mr-1"></i>Ekli</span>' :
                    '<span class="text-gray-400 text-xs">Değil</span>';

                // Unread message badge
                const unreadBadge = (c.unread_count && c.unread_count > 0) ?
                    `<span class="inline-flex items-center justify-center px-2 py-0.5 ml-2 text-xs font-bold text-white bg-red-500 rounded-full animate-pulse">${c.unread_count}</span>` :
                    '';

                const row = `
                    <tr class="hover:bg-gray-50 transition group cursor-pointer" onclick="fetchChat('${c.jid}', '${safeName}')">
                        <td class="p-4" onclick="event.stopPropagation()">
                            <input type="checkbox" class="contact-check rounded text-indigo-600 focus:ring-indigo-500" value="${c.jid}">
                        </td>
                        <td class="p-4 font-medium text-gray-800">
                             <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs font-bold relative">
                                    ${displayName ? displayName.substring(0, 2).toUpperCase() : '?'}
                                    ${unreadBadge ? '<span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>' : ''}
                                </div>
                                <div class="flex items-center">
                                    ${displayName || 'Bilinmiyor'}
                                    ${unreadBadge}
                                </div>
                             </div>
                        </td>
                        <td class="p-4 text-gray-600 font-mono text-xs">${displayNumber}</td>
                        <td class="p-4">${typeLabel}</td>
                        <td class="p-4 text-gray-500 text-xs">${c.last_message_time || '-'}</td>
                        <td class="p-4 text-right">${importedBadge}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        }


        function fetchChat(jid, name) {
            console.log('🔵 fetchChat called for JID:', jid);

            // Mark messages as read when chat is opened
            $.post('api/whatsapp.php', { action: 'mark_as_read', jid: jid }, function () {
                console.log('✅ Messages marked as read for:', jid);
                // Refresh contact list to update unread badge
                loadContacts();
                // Update floating widget badge
                if (typeof loadUnreadChats === 'function') {
                    loadUnreadChats();
                }
            });

            // Show loading
            Swal.fire({
                title: 'Sohbet Yükleniyor...',
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.post('api/whatsapp.php', { action: 'fetch_messages', jid: jid }, function (res) {
                if (res.status === 'success') {
                    renderChatModal(res.data, jid, name);
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            }).fail(function () {
                Swal.fire('Hata', 'Mesajlar alınamadı', 'error');
            });
        }

        function renderChatModal(messages, jid, name) {
            const generateBubble = (msg) => {
                let icon = '';
                if (msg.type === 'image') icon = '<i class="fa-solid fa-image mr-1"></i>';
                else if (msg.type === 'video') icon = '<i class="fa-solid fa-video mr-1"></i>';
                else if (msg.type === 'document') icon = '<i class="fa-solid fa-file mr-1"></i>';
                else if (msg.type === 'audio') icon = '<i class="fa-solid fa-microphone mr-1"></i>';
                else if (msg.content.includes('[Süreli Mesaj]')) icon = '<i class="fa-solid fa-clock mr-1 text-yellow-500"></i>';

                const isMe = Boolean(msg.fromMe);
                const align = isMe ? 'self-end' : 'self-start';
                const color = isMe ? 'bg-green-100 text-gray-800' : 'bg-white text-gray-800';

                // Format timestamp
                let time = '';
                try {
                    time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'numeric' });
                } catch (e) { time = '-'; }

                return `
                <div class="${align} max-w-[80%] rounded-lg shadow-sm p-3 ${color} mb-2" data-id="${msg.id}">
                    <p class="text-sm pb-1 break-words py-1">${icon}${msg.content}</p>
                    <p class="text-[10px] text-gray-400 text-right">${time}</p>
                </div>
                `;
            };

            const contentHtml = messages && messages.length > 0
                ? messages.map(generateBubble).join('')
                : '<div class="text-center text-gray-500 mt-10">Mesaj geçmişi bulunamadı.</div>';

            let containerHtml = `<div class="flex flex-col h-[400px] overflow-y-auto p-4 bg-gray-100 rounded-lg" id="chatContainer">`;
            containerHtml += contentHtml;
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
                title: name || jid,
                html: containerHtml,
                width: '600px',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Kapat',
                allowOutsideClick: false,
                allowEscapeKey: false,
                willClose: () => {
                    // STOP POLLING WHEN DIALOG CLOSES
                    if (chatPollInterval) {
                        clearInterval(chatPollInterval);
                        chatPollInterval = null;
                        console.log('🛑 Chat polling stopped');
                    }
                },
                didOpen: () => {
                    const container = document.getElementById('chatContainer');
                    if (container) container.scrollTop = container.scrollHeight;

                    // START AUTO-REFRESH POLLING (1 second interval)
                    chatPollInterval = setInterval(() => {
                        console.log('🔄 Auto-refreshing chat messages...');

                        $.post('api/whatsapp.php', { action: 'fetch_messages', jid: jid, force_refresh: 1 }, function (res) {
                            if (res.status === 'success' && res.data) {
                                const currentScroll = container.scrollTop;
                                const maxScroll = container.scrollHeight - container.clientHeight;
                                const isAtBottom = (maxScroll - currentScroll) < 50;

                                // Update chat content
                                const newContent = res.data.map(generateBubble).join('');
                                $('#chatContainer').html(newContent);

                                // Auto-scroll only if user was at bottom
                                if (isAtBottom) {
                                    container.scrollTop = container.scrollHeight;
                                }
                            }
                        }).fail(function () {
                            console.warn('⚠️ Chat refresh failed');
                        });
                    }, 1000); // 1 second

                    console.log('✅ Chat polling started (1 sec interval)');

                    // Send Logic
                    const sendMessage = () => {
                        const msg = $('#chatInput').val();
                        if (!msg.trim()) return;

                        $('#chatInput').prop('disabled', true);
                        $('#sendChatBtn').prop('disabled', true);

                        $.post('api/whatsapp.php', {
                            action: 'send_message',
                            jid: jid,
                            message: msg
                        }, function (res) {
                            if (res.status === 'success') {
                                $('#chatInput').val('').prop('disabled', false).focus();
                                // Append message locally for better UX
                                const tempMsg = {
                                    fromMe: true,
                                    content: msg,
                                    timestamp: Math.floor(Date.now() / 1000),
                                    type: 'text'
                                };
                                $('#chatContainer').append(generateBubble(tempMsg));
                                container.scrollTop = container.scrollHeight;
                            } else {
                                Swal.showValidationMessage(res.message || 'Gönderilemedi');
                                $('#chatInput').prop('disabled', false);
                            }
                            $('#sendChatBtn').prop('disabled', false);
                        });
                    };

                    $('#sendChatBtn').click(sendMessage);
                    $('#chatInput').on('keypress', function (e) {
                        if (e.which == 13) sendMessage();
                    });
                }
            });
        }

        function syncChats() {
            // Enhanced UI with real-time logging
            let stepsHtml = `
                <div class="text-left text-sm space-y-3 p-2">
                    <div id="step1" class="flex items-center gap-3 text-gray-500">
                        <i class="fa-solid fa-circle-notch fa-spin step-icon"></i>
                        <span>API ve Telefon Bağlantısı Kontrol Ediliyor...</span>
                    </div>
                    <div id="step2" class="flex items-center gap-3 text-gray-400">
                        <i class="fa-solid fa-circle step-icon text-gray-300"></i>
                        <span>Sohbetler Evolution API'den Çekiliyor...</span>
                    </div>
                    <div id="step3" class="flex items-center gap-3 text-gray-400">
                        <i class="fa-solid fa-circle step-icon text-gray-300"></i>
                        <span>Kişiler ve Numaralar Rehbere Kaydediliyor...</span>
                    </div>
                    
                    <!-- Real-time Log Container -->
                    <div class="mt-4 p-4 bg-gray-900 rounded-lg border border-gray-700 max-h-64 overflow-y-auto" 
                         id="logContainer" style="display: none;">
                        <div class="text-xs font-mono text-green-400 space-y-1" id="logContent"></div>
                    </div>
                    
                    <!-- Summary Box -->
                    <div id="summaryBox" class="hidden mt-4 p-4 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg border border-green-200">
                        <h4 class="font-bold text-green-800 mb-2 flex items-center gap-2">
                            <i class="fa-solid fa-check-circle"></i> İşlem Özeti
                        </h4>
                        <div id="summaryContent" class="text-sm text-gray-700"></div>
                    </div>
                </div>
            `;

            Swal.fire({
                title: '📱 WhatsApp Senkronizasyonu',
                html: stepsHtml,
                width: '700px',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    executeSyncSteps();
                }
            });
        }

        async function executeSyncSteps() {
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

            const addLog = (msg, type = 'info') => {
                const logContainer = $('#logContainer');
                const logContent = $('#logContent');

                logContainer.show();

                let color = 'text-green-400';
                if (type === 'error') color = 'text-red-400';
                else if (type === 'warning') color = 'text-yellow-400';
                else if (type === 'success') color = 'text-cyan-400';

                logContent.append(`<div class="${color}">▸ ${msg}</div>`);

                // Auto-scroll to bottom
                logContainer[0].scrollTop = logContainer[0].scrollHeight;
            };

            try {
                // STEP 1: CONNECTION CHECK
                updateStep('step1', 'loading');
                addLog('🔌 Evolution API bağlantısı test ediliyor...');

                const connRes = await $.post('api/whatsapp.php', { action: 'check_connection' });

                if (connRes.status === 'success') {
                    updateStep('step1', 'success', '✅ API ve Telefon Bağlı');
                    addLog('✅ Bağlantı başarılı: ' + connRes.message, 'success');
                } else {
                    throw new Error(connRes.message || 'Bağlantı hatası');
                }

                // Small delay for better UX
                await new Promise(resolve => setTimeout(resolve, 300));

                // STEP 2: FETCH CHATS WITH REAL-TIME PROGRESS
                updateStep('step2', 'loading', '⏳ Sohbetler çekiliyor...');
                addLog('📡 Tüm sohbet listesi Evolution API\'den isteniyor...');

                const startTime = Date.now();
                const fetchRes = await $.post('api/whatsapp.php', { action: 'fetch_remote_chats' });

                if (fetchRes.status === 'success') {
                    const duration = ((Date.now() - startTime) / 1000).toFixed(1);

                    updateStep('step2', 'success', `✅ ${fetchRes.count} Sohbet Çekildi`);

                    // Display server logs
                    if (fetchRes.logs && fetchRes.logs.length > 0) {
                        addLog('📋 Server İşlem Detayları:', 'success');
                        fetchRes.logs.forEach(log => {
                            addLog(`[${log.time}] ${log.message}`, 'info');
                        });
                    }

                    addLog(`⏱️ İşlem süresi: ${duration} saniye`, 'success');

                    // STEP 3: SAVING TO DATABASE (auto-completed by server)
                    updateStep('step3', 'success', `✅ ${fetchRes.count} Kişi/Grup Kaydedildi`);
                    addLog('💾 Tüm kişiler ve gruplar rehbere kaydedildi', 'success');

                    // Show summary
                    $('#summaryBox').removeClass('hidden');
                    $('#summaryContent').html(`
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-users text-blue-600"></i>
                                <span><strong>${fetchRes.count}</strong> kişi/grup</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-layer-group text-purple-600"></i>
                                <span><strong>${fetchRes.pages || 1}</strong> sayfa işlendi</span>
                            </div>
                            <div class="flex items-center gap-2 col-span-2">
                                <i class="fa-solid fa-clock text-green-600"></i>
                                <span>Süre: <strong>${duration}s</strong></span>
                            </div>
                        </div>
                        ${fetchRes.names && fetchRes.names.length > 0 ? `
                            <div class="mt-3 pt-3 border-t border-gray-300">
                                <p class="text-xs text-gray-600 mb-1">Örnek kayıtlar (ilk 10):</p>
                                <div class="text-xs text-gray-500 max-h-24 overflow-y-auto">
                                    ${fetchRes.names.map(n => `• ${n}`).join('<br>')}
                                </div>
                            </div>
                        ` : ''}
                    `);

                    setTimeout(() => {
                        Swal.fire({
                            icon: 'success',
                            title: '🎉 Tamamlandı!',
                            html: `
                                <div class="text-left">
                                    <p class="mb-2"><strong>${fetchRes.count}</strong> yeni sohbet/kişi bulundu ve rehbere eklendi.</p>
                                    <p class="text-sm text-gray-600">Sayfa yenileniyor...</p>
                                </div>
                            `,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => loadContacts());
                    }, 1500);
                } else {
                    throw new Error(fetchRes.message || 'Sohbetler alınamadı');
                }

            } catch (error) {
                console.error(error);
                let errMsg = error.message || (error.responseJSON ? error.responseJSON.message : 'Bilinmeyen Hata');

                addLog('❌ HATA: ' + errMsg, 'error');

                // Determine which step failed
                if ($('#step1').hasClass('text-blue-600')) {
                    updateStep('step1', 'error', '❌ Bağlantı Hatası');
                } else if ($('#step2').hasClass('text-blue-600')) {
                    updateStep('step2', 'error', '❌ Sohbet Çekme Hatası');
                } else {
                    updateStep('step3', 'error', '❌ Kaydetme Hatası');
                }

                Swal.update({
                    showConfirmButton: true,
                    confirmButtonText: 'Kapat',
                    confirmButtonColor: '#d33'
                });
            }
        }

        function deleteSelected() {
            const selected = $('.contact-check:checked').map(function () { return $(this).val(); }).get();

            if (selected.length === 0) {
                Swal.fire('Uyarı', 'Lütfen en az bir kişi seçin', 'warning');
                return;
            }

            Swal.fire({
                title: 'Silme Onayı',
                text: `${selected.length} kişiyi ve tüm mesaj geçmişlerini silmek istiyor musunuz? Bu işlem geri alınamaz!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, Sil',
                confirmButtonColor: '#d33',
                cancelButtonText: 'İptal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/whatsapp.php', { action: 'delete_contacts', jids: selected }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi!', res.message, 'success').then(() => loadContacts());
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }

        function importSelected() {
            const selected = $('.contact-check:checked').map(function () { return $(this).val(); }).get();

            if (selected.length === 0) {
                Swal.fire('Uyarı', 'Lütfen en az bir kişi seçin', 'warning');
                return;
            }

            Swal.fire({
                title: 'Onay',
                text: `${selected.length} kişiyi müşteri olarak eklemek istiyor musunuz?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Evet, Ekle'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('api/whatsapp.php', { action: 'import_to_customers', jids: selected }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Başarılı', res.message, 'success').then(() => loadContacts());
                        } else {
                            Swal.fire('Hata', res.message, 'error');
                        }
                    });
                }
            });
        }
    </script>
</body>

</html>