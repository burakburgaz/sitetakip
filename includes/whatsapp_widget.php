<!-- Floating WhatsApp Widget -->
<div id="whatsappFloatingWidget" class="fixed bottom-6 right-6 z-50">
    <!-- Main Button -->
    <div class="relative">
        <button id="waFloatingBtn" onclick="toggleWhatsAppWidget()"
            class="bg-gradient-to-br from-green-500 to-green-600 text-white w-16 h-16 rounded-full shadow-2xl hover:shadow-green-500/50 hover:scale-110 transition-all duration-300 flex items-center justify-center group">
            <i class="fa-brands fa-whatsapp text-3xl group-hover:rotate-12 transition-transform"></i>
        </button>

        <!-- Unread Badge -->
        <div id="waUnreadBadge"
            class="hidden absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center animate-pulse shadow-lg">
            0
        </div>
    </div>

    <!-- Chat List Popup -->
    <div id="waChatsPopup"
        class="hidden absolute bottom-20 right-0 w-80 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-brands fa-whatsapp text-2xl"></i>
                <span class="font-bold text-lg">WhatsApp Mesajlar</span>
            </div>
            <button onclick="toggleWhatsAppWidget()" class="text-white hover:text-gray-200 transition">
                <i class="fa-solid fa-times text-xl"></i>
            </button>
        </div>

        <!-- Chats List -->
        <div id="waChatsContainer" class="max-h-96 overflow-y-auto">
            <div class="flex items-center justify-center py-12 text-gray-500">
                <div class="text-center">
                    <i class="fa-solid fa-spinner fa-spin text-3xl mb-2"></i>
                    <p>Yükleniyor...</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 border-t border-gray-200 p-3 text-center">
            <a href="contacts.php"
                class="text-sm text-green-600 hover:text-green-700 font-semibold flex items-center justify-center gap-2">
                <i class="fa-solid fa-address-book"></i>
                Tüm Sohbetler
            </a>
        </div>
    </div>
</div>

<style>
    /* Animation for popup */
    #waChatsPopup {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Pulse animation for new messages */
    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    #waUnreadBadge.animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Scrollbar styling */
    #waChatsContainer::-webkit-scrollbar {
        width: 6px;
    }

    #waChatsContainer::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #waChatsContainer::-webkit-scrollbar-thumb {
        background: #10b981;
        border-radius: 3px;
    }

    #waChatsContainer::-webkit-scrollbar-thumb:hover {
        background: #059669;
    }
</style>

<script>
    // WhatsApp Floating Widget - Wait for jQuery
    (function () {
        function initWidget() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initWidget, 100);
                return;
            }

            let waWidgetInterval = null;

            jQuery(document).ready(function ($) {
                // Initial load
                loadUnreadChats();

                // Aggressive polling: 2.5 seconds
                waWidgetInterval = setInterval(loadUnreadChats, 2500);

                $(document).on('click', function (e) {
                    if (!$(e.target).closest('#whatsappFloatingWidget').length) {
                        $('#waChatsPopup').addClass('hidden');
                    }
                });
            });

            window.toggleWhatsAppWidget = function () {
                const popup = jQuery('#waChatsPopup');
                if (popup.hasClass('hidden')) {
                    popup.removeClass('hidden');
                    loadUnreadChats();
                } else {
                    popup.addClass('hidden');
                }
            };

            function loadUnreadChats() {
                // Direct AJAX call without waiting for another ready event
                jQuery.get('api/whatsapp.php', { action: 'get_unread_chats' })
                    .done(function (res) {
                        if (res.status === 'success') {
                            updateBadge(res.total_unread);
                            renderChats(res.chats || []);
                        }
                    })
                    .fail(function (xhr) {
                        // Silent failure to avoid console spam
                    });
            }

            window.loadUnreadChats = loadUnreadChats;

            function updateBadge(count) {
                const badge = jQuery('#waUnreadBadge');
                if (count > 0) {
                    badge.text(count > 99 ? '99+' : count).removeClass('hidden');
                } else {
                    badge.addClass('hidden');
                }
            }

            function renderChats(chats) {
                const container = jQuery('#waChatsContainer');
                if (chats.length === 0) {
                    container.html('<div class="flex items-center justify-center py-12 text-gray-500"><div class="text-center"><i class="fa-solid fa-check-circle text-4xl text-green-500 mb-2"></i><p class="font-semibold">Tüm mesajlar okundu</p></div></div>');
                    return;
                }

                let html = '';
                chats.forEach(chat => {
                    const badge = chat.unread_count > 0 ? `<span class="bg-green-500 text-white text-xs font-bold rounded-full px-2 py-0.5">${chat.unread_count}</span>` : '';
                    const msg = chat.last_message ? (chat.last_message.length > 40 ? chat.last_message.substring(0, 40) + '...' : chat.last_message) : 'Mesaj yok';
                    const time = chat.last_message_time ? formatTime(chat.last_message_time) : '';
                    const name = escapeHtml(chat.name || 'Bilinmiyor');

                    html += `<div class="border-b border-gray-100 hover:bg-gray-50 transition cursor-pointer p-4" onclick="openChatFromWidget('${chat.jid}', '${name}')">
                        <div class="flex items-start gap-3">
                            <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold text-lg flex-shrink-0">
                                ${chat.name ? chat.name.substring(0, 2).toUpperCase() : '?'}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="font-semibold text-gray-800 truncate">${name}</h4>
                                    ${badge}
                                </div>
                                <p class="text-sm text-gray-600 truncate">${escapeHtml(msg)}</p>
                                <p class="text-xs text-gray-400 mt-1">${time}</p>
                            </div>
                        </div>
                    </div>`;
                });
                container.html(html);
            }

            window.openChatFromWidget = function (jid, name) {
                jQuery.post('api/whatsapp.php', { action: 'mark_as_read', jid: jid }, function () {
                    loadUnreadChats();
                });
                window.location.href = `contacts.php?open_chat=${encodeURIComponent(jid)}`;
            };

            function formatTime(timestamp) {
                const now = new Date();
                const time = new Date(timestamp);
                const diff = Math.floor((now - time) / 60000);
                if (diff < 1) return 'Şimdi';
                if (diff < 60) return `${diff} dk önce`;
                if (diff < 1440) return `${Math.floor(diff / 60)} saat önce`;
                if (diff < 10080) return `${Math.floor(diff / 1440)} gün önce`;
                return time.toLocaleDateString('tr-TR');
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        initWidget();
    })();
</script>