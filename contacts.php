<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_login();
$page_title = 'WhatsApp Rehber - DReklam';
include 'includes/head.php';
?>

<body class="flex h-screen overflow-hidden">
    <?php include 'includes/bg_blobs.php'; ?>

    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- Header -->
        <header class="z-20 p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-black text-white logo-font tracking-tight flex items-center gap-3">
                        <div
                            class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center border border-emerald-500/30">
                            <i class="fa-brands fa-whatsapp text-emerald-400"></i>
                        </div>
                        WhatsApp Rehber
                    </h2>
                    <p class="text-slate-400 mt-1 text-sm font-medium">Evolution API ile senkronize rehber yönetimi</p>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="syncChats()" id="syncBtn"
                        class="px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-2xl font-bold border border-white/10 transition flex items-center gap-2">
                        <i class="fa-solid fa-rotate mr-2"></i>
                        <span>Konuşmaları Getir (API)</span>
                    </button>
                    <a href="api/whatsapp.php?action=export_excel" target="_blank"
                        class="px-6 py-3 btn-gradient-primary rounded-2xl flex items-center gap-2"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4) !important;">
                        <i class="fa-solid fa-file-excel mr-2"></i>
                        <span>Excel İndir</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto p-4 sm:p-6">

            <!-- Filters & Actions -->
            <div class="glass-card rounded-[2.5rem] p-6 mb-8 flex flex-wrap gap-6 items-center justify-between">
                <div class="flex items-center gap-3 p-1.5 bg-white/5 rounded-[1.5rem] border border-white/5">
                    <button onclick="filterType('all')"
                        class="filter-btn active px-6 py-2.5 rounded-xl text-xs font-bold transition">Tümü</button>
                    <button onclick="filterType('individual')"
                        class="filter-btn px-6 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition">Kişiler</button>
                    <button onclick="filterType('group')"
                        class="filter-btn px-6 py-2.5 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition">Gruplar</button>
                </div>

                <div class="flex items-center gap-4">
                    <button onclick="deleteSelected()"
                        class="px-6 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-2xl text-xs font-bold transition">
                        <i class="fa-solid fa-trash mr-2"></i>Seçilenleri Sil
                    </button>
                    <button onclick="importSelected()"
                        class="px-6 py-3 bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 rounded-2xl text-xs font-bold transition">
                        <i class="fa-solid fa-user-plus mr-2"></i>Seçilenleri Müşteri Yap
                    </button>
                </div>
            </div>

            <!-- Contacts Table -->
            <div class="glass-card rounded-[2.5rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="p-6 w-16">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="selectAll"
                                            class="w-5 h-5 bg-white/5 border border-white/10 rounded focus:ring-primary">
                                    </div>
                                </th>
                                <th class="p-6 text-xs font-bold text-slate-500 uppercase tracking-widest">İsim / Grup
                                    İsmi</th>
                                <th class="p-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Numara / ID
                                </th>
                                <th class="p-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Tür</th>
                                <th class="p-6 text-xs font-bold text-slate-500 uppercase tracking-widest">Son İşlem
                                </th>
                                <th class="p-6 text-right text-xs font-bold text-slate-500 uppercase tracking-widest">
                                    Durum</th>
                            </tr>
                        </thead>
                        <tbody id="contactsTableBody" class="text-sm text-slate-300">
                            <tr>
                                <td colspan="6" class="p-20 text-center text-slate-500 italic">Veriler yükleniyor...
                                </td>
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
            $('.filter-btn').removeClass('active text-white bg-primary').addClass('text-slate-400 hover:text-white');
            const typeText = type === 'all' ? 'Tümü' : (type === 'individual' ? 'Kişiler' : 'Gruplar');
            $(`.filter-btn:contains('${typeText}')`).addClass('active text-white bg-primary').removeClass('text-slate-400');
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
                tbody.html('<tr><td colspan="6" class="p-20 text-center text-slate-500 italic">Kayıt bulunamadı. "Konuşmaları Getir" butonunu kullanın.</td></tr>');
                return;
            }

            contacts.forEach(c => {
                const displayName = c.type === 'group' ? (c.group_name || c.name) : c.name;
                const safeName = displayName.replace(/'/g, "\\'");
                const displayNumber = c.type === 'group' ? '-' : c.number;

                const typeLabel = c.type === 'group' ?
                    '<span class="px-3 py-1 bg-purple-500/10 text-purple-400 rounded-full text-[10px] font-bold border border-purple-500/20 uppercase tracking-wider">Grup</span>' :
                    '<span class="px-3 py-1 bg-blue-500/10 text-blue-400 rounded-full text-[10px] font-bold border border-blue-500/20 uppercase tracking-wider">Kişi</span>';

                const importedBadge = c.is_imported ?
                    '<span class="text-emerald-400 text-xs font-bold flex items-center gap-1 justify-end"><i class="fa-solid fa-check-circle"></i>Ekli</span>' :
                    '<span class="text-slate-600 text-xs font-medium">Değil</span>';

                const unreadBadge = (c.unread_count && c.unread_count > 0) ?
                    `<span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 ml-2 text-[10px] font-black text-white bg-primary rounded-full shadow-[0_0_15px_rgba(59,130,246,0.5)] animate-pulse">${c.unread_count}</span>` :
                    '';

                const row = `
                    <tr class="hover:bg-white/[0.02] transition-colors group cursor-pointer border-b border-white/5" onclick="fetchChat('${c.jid}', '${safeName}')">
                        <td class="p-6" onclick="event.stopPropagation()">
                            <input type="checkbox" class="contact-check w-5 h-5 bg-white/5 border border-white/10 rounded focus:ring-primary" value="${c.jid}">
                        </td>
                        <td class="p-6">
                             <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-primary text-sm font-black relative group-hover:scale-110 transition-transform">
                                    ${displayName ? displayName.substring(0, 2).toUpperCase() : '?'}
                                    ${unreadBadge ? '<span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-primary rounded-full border-2 border-[#0f172a]"></span>' : ''}
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-white font-bold group-hover:text-primary transition-colors flex items-center gap-2">
                                        ${displayName || 'Bilinmiyor'}
                                        ${unreadBadge}
                                    </span>
                                    <span class="text-slate-500 text-xs font-medium">WhatsApp Sohbet</span>
                                </div>
                             </div>
                        </td>
                        <td class="p-6 text-slate-400 font-mono text-xs">${displayNumber}</td>
                        <td class="p-6">${typeLabel}</td>
                        <td class="p-6 text-slate-400 text-xs font-medium">${c.last_message_time || '-'}</td>
                        <td class="p-6 text-right">${importedBadge}</td>
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
            if (chatPollInterval) clearInterval(chatPollInterval);

            const generateBubble = (msg) => {
                let icon = '';
                if (msg.type === 'image') icon = '<i class="fa-solid fa-image mr-1"></i>';
                else if (msg.type === 'video') icon = '<i class="fa-solid fa-video mr-1"></i>';
                else if (msg.type === 'document') icon = '<i class="fa-solid fa-file mr-1"></i>';
                else if (msg.type === 'audio') icon = '<i class="fa-solid fa-microphone mr-1"></i>';
                else if (msg.content.includes('[Süreli Mesaj]')) icon = '<i class="fa-solid fa-clock mr-1 text-yellow-500"></i>';

                const isMe = Boolean(msg.fromMe);
                const align = isMe ? 'self-end' : 'self-start';
                // Premium Dark
                const color = isMe 
                    ? 'bg-emerald-500/10 text-emerald-100 border border-emerald-500/20 rounded-tr-none' 
                    : 'bg-white/5 text-slate-200 border border-white/10 rounded-tl-none';

                let time = '';
                try {
                    time = new Date(msg.timestamp * 1000).toLocaleString('tr-TR', { hour: '2-digit', minute: '2-digit', day: 'numeric', month: 'numeric' });
                } catch (e) { time = '-'; }

                return `
                <div class="${align} max-w-[80%] rounded-2xl p-4 shadow-sm ${color} mb-3 backdrop-blur-sm transition-all hover:scale-[1.01]">
                    <p class="text-sm pb-1 break-words leading-relaxed">${icon}${msg.content}</p>
                    <p class="text-[10px] text-white/40 text-right mt-1 font-medium">${time}</p>
                </div>
                `;
            };

            // Glass Container
            let containerHtml = `<div class="flex flex-col h-[500px] overflow-y-auto p-6 bg-black/40 rounded-3xl border border-white/5 custom-scrollbar backdrop-blur-md" id="chatContainer">`;

            const contentHtml = messages && messages.length > 0
                ? messages.map(generateBubble).join('')
                : `
                <div class="flex flex-col items-center justify-center h-full text-slate-500 opacity-60">
                    <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mb-4">
                        <i class="fa-regular fa-comments text-3xl"></i>
                    </div>
                    <p class="font-medium text-sm">Mesaj geçmişi bulunamadı</p>
                </div>`;

            containerHtml += contentHtml;
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
                title: `<span class="text-white logo-font tracking-wide text-xl">${name || jid}</span>`,
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
                <div class="text-left space-y-4 p-4">
                    <div id="step1" class="flex items-center gap-4 text-slate-500 p-4 bg-white/5 rounded-2xl border border-white/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center">
                            <i class="fa-solid fa-circle-notch fa-spin step-icon text-xl"></i>
                        </div>
                        <span class="font-bold">API ve Telefon Bağlantısı Kontrol Ediliyor...</span>
                    </div>
                    <div id="step2" class="flex items-center gap-4 text-slate-500 p-4 bg-white/5 rounded-2xl border border-white/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center">
                            <i class="fa-solid fa-circle step-icon text-gray-300 text-xl"></i>
                        </div>
                        <span class="font-bold">Sohbetler Evolution API'den Çekiliyor...</span>
                    </div>
                    <div id="step3" class="flex items-center gap-4 text-slate-500 p-4 bg-white/5 rounded-2xl border border-white/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center">
                            <i class="fa-solid fa-circle step-icon text-gray-300 text-xl"></i>
                        </div>
                        <span class="font-bold">Kişiler ve Numaralar Rehbere Kaydediliyor...</span>
                    </div>
                    
                    <!-- Real-time Log Container -->
                    <div class="mt-6 p-4 bg-black/60 rounded-2xl border border-white/5 max-h-48 overflow-y-auto custom-scrollbar" id="logContainer" style="display: none;">
                        <div class="text-[10px] font-mono text-emerald-400 space-y-1.5" id="logContent"></div>
                    </div>
                    
                    <!-- Summary Box -->
                    <div id="summaryBox" class="hidden mt-6 p-6 bg-emerald-500/10 rounded-[2rem] border border-emerald-500/20">
                        <h4 class="font-black text-emerald-400 mb-4 flex items-center gap-2 uppercase tracking-widest text-xs">
                            <i class="fa-solid fa-check-circle"></i> İşlem Özeti
                        </h4>
                        <div id="summaryContent" class="text-sm text-slate-300"></div>
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
                    el.removeClass('text-slate-500 text-emerald-400 text-rose-400 border-white/5 border-emerald-500/20 border-rose-500/20').addClass('text-blue-400 border-blue-500/20 font-bold');
                    icon.attr('class', 'fa-solid fa-circle-notch fa-spin step-icon text-xl');
                } else if (status === 'success') {
                    el.removeClass('text-slate-500 text-blue-400 text-rose-400 border-white/5 border-blue-500/20 border-rose-500/20').addClass('text-emerald-400 border-emerald-500/20 font-bold');
                    icon.attr('class', 'fa-solid fa-check-circle step-icon text-xl');
                } else if (status === 'error') {
                    el.removeClass('text-slate-500 text-blue-400 text-emerald-400 border-white/5 border-blue-500/20 border-emerald-500/20').addClass('text-rose-400 border-rose-500/20 font-bold');
                    icon.attr('class', 'fa-solid fa-times-circle step-icon text-xl');
                } else { // pending
                    el.removeClass('text-blue-400 text-emerald-400 text-rose-400 border-blue-500/20 border-emerald-500/20 border-rose-500/20').addClass('text-slate-500 border-white/5');
                    icon.attr('class', 'fa-solid fa-circle step-icon text-gray-300 text-xl');
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