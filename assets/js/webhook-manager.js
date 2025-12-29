
// ========== WEBHOOK FUNCTIONS ==========

// Load webhook URL from database on page load
$(document).ready(function () {
    loadWebhookUrl();
});

// Load Webhook URL from database
function loadWebhookUrl() {
    $.get('api/settings.php', function (data) {
        const webhookUrl = data.whatsapp_webhook_url || '';

        // If no saved URL, auto-detect from current domain
        if (!webhookUrl) {
            const currentDomain = window.location.origin;
            const autoUrl = currentDomain + '/api/whatsapp_webhook.php';
            $('#webhookUrlInput').val(autoUrl);
        } else {
            $('#webhookUrlInput').val(webhookUrl);
        }
    }).fail(function () {
        // Fallback to auto-detect if settings API fails
        const currentDomain = window.location.origin;
        const autoUrl = currentDomain + '/api/whatsapp_webhook.php';
        $('#webhookUrlInput').val(autoUrl);
    });
}

// Save Webhook URL to database
function saveWebhookUrl() {
    const webhookUrl = $('#webhookUrlInput').val().trim();

    if (!webhookUrl) {
        Swal.fire('Hata', 'Webhook URL boş olamaz', 'error');
        return;
    }

    // Basic URL validation
    if (!webhookUrl.startsWith('http://') && !webhookUrl.startsWith('https://')) {
        Swal.fire('Hata', 'Geçerli bir URL girin (http:// veya https:// ile başlamalı)', 'error');
        return;
    }

    Swal.fire({
        title: 'Kaydediliyor...',
        didOpen: () => Swal.showLoading()
    });

    $.post('api/settings.php', {
        whatsapp_webhook_url: webhookUrl
    }, function (res) {
        Swal.close();
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Kaydedildi!',
                text: 'Webhook URL başarıyla kaydedildi',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Hata', res.message || 'Kaydetme başarısız', 'error');
        }
    }).fail(function () {
        Swal.close();
        Swal.fire('Hata', 'Kaydetme sırasında bir hata oluştu', 'error');
    });
}


// Copy Webhook URL to clipboard
function copyWebhookUrl() {
    const webhookUrl = $('#webhookUrlInput').val();
    navigator.clipboard.writeText(webhookUrl).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Kopyalandı!',
            text: 'Webhook URL panoya kopyalandı',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(() => {
        $('#webhookUrlInput').select();
        document.execCommand('copy');
        Swal.fire({
            icon: 'success',
            title: 'Kopyalandı!',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

// Register Webhook to Evolution API
function registerWebhook() {
    const webhookUrl = $('#webhookUrlInput').val();

    if (!webhookUrl) {
        Swal.fire('Hata', 'Webhook URL bulunamadı', 'error');
        return;
    }

    Swal.fire({
        title: 'Webhook Kaydediliyor...',
        html: 'Evolution API ile bağlantı kuruluyor...',
        didOpen: () => Swal.showLoading(),
        allowOutsideClick: false
    });

    $.post('api/whatsapp.php', {
        action: 'set_webhook',
        webhook_url: webhookUrl
    }, function (res) {
        Swal.close();
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                html: `
                    <p class="text-sm text-gray-700 mb-2">Webhook başarıyla kaydedildi!</p>
                    <div class="bg-green-50 p-3 rounded border border-green-200 text-left">
                        <p class="text-xs text-green-800">✅ URL: ${webhookUrl}</p>
                        <p class="text-xs text-green-800 mt-1">✅ Evolution API'ye kaydedildi</p>
                    </div>
                `,
                confirmButtonText: 'Tamam'
            });
        } else {
            // Show detailed error message from API
            let errorHtml = `<p class="mb-2">${res.message || 'Webhook kaydedilemedi'}</p>`;

            if (res.debug) {
                errorHtml += `
                    <div class="bg-red-50 p-3 rounded text-left text-xs mt-3">
                        <p class="font-bold mb-2">Debug Bilgisi:</p>
                        <p><strong>HTTP Kodu:</strong> ${res.debug.code || 'Bilinmiyor'}</p>
                        ${res.debug.body ? `<pre class="mt-2 overflow-auto max-h-40">${JSON.stringify(res.debug.body, null, 2)}</pre>` : ''}
                    </div>
                `;
            }

            Swal.fire({
                icon: 'error',
                title: 'Webhook Hatası',
                html: errorHtml,
                width: '600px'
            });
        }
    }).fail(function (xhr) {
        Swal.close();

        let errorMsg = 'API isteği başarısız oldu';
        let debugInfo = '';

        try {
            const response = JSON.parse(xhr.responseText);
            if (response.message) {
                errorMsg = response.message;
            }
            if (response.debug) {
                debugInfo = `
                    <div class="bg-red-50 p-3 rounded text-left text-xs mt-3">
                        <p class="font-bold mb-2">Hata Detayı:</p>
                        <pre class="overflow-auto max-h-40">${JSON.stringify(response.debug, null, 2)}</pre>
                    </div>
                `;
            }
        } catch (e) {
            errorMsg = `HTTP ${xhr.status}: ${xhr.statusText || 'Sunucu Hatası'}`;
            if (xhr.responseText) {
                debugInfo = `
                    <div class="bg-red-50 p-3 rounded text-left text-xs mt-3">
                        <p class="font-bold mb-2">Yanıt:</p>
                        <pre class="overflow-auto max-h-40">${xhr.responseText.substring(0, 500)}</pre>
                    </div>
                `;
            }
        }

        Swal.fire({
            icon: 'error',
            title: 'İstek Başarısız',
            html: `
                <p class="mb-2">${errorMsg}</p>
                <p class="text-sm text-gray-600">Lütfen Evolution API ayarlarınızı kontrol edin.</p>
                ${debugInfo}
            `,
            width: '600px'
        });
    });
}

// Check Webhook Status
function checkWebhookStatus() {
    Swal.fire({
        title: 'Durum Kontrol Ediliyor...',
        didOpen: () => Swal.showLoading()
    });

    $.post('api/whatsapp.php', {
        action: 'get_webhook'
    }, function (res) {
        Swal.close();

        if (res.status === 'success' && res.data) {
            const webhook = res.data.data || res.data;
            const enabled = webhook.enabled || webhook.webhook?.enabled;
            const url = webhook.url || webhook.webhook?.url || 'Bilinmiyor';
            const events = webhook.events || webhook.webhook?.events || [];

            let statusHtml = `
                <div class="bg-white p-4 rounded-lg space-y-3 text-left">
                    <div class="flex items-center gap-2 p-2 rounded ${enabled ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}">
                        <i class="fa-solid ${enabled ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                        <span class="font-bold">Durum: ${enabled ? 'Aktif' : 'Deaktif'}</span>
                    </div>
                    <div class="text-sm">
                        <p class="font-semibold text-gray-700">URL:</p>
                        <p class="font-mono text-xs bg-gray-50 p-2 rounded break-all">${url}</p>
                    </div>
                    ${events.length > 0 ? `
                        <div class="text-sm">
                            <p class="font-semibold text-gray-700">Dinlenen Eventler:</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                ${events.map(e => `<span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">${e}</span>`).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;

            $('#webhookStatusContent').html(statusHtml);
            $('#webhookStatusDisplay').removeClass('hidden');

            Swal.fire({
                icon: 'info',
                title: 'Webhook Durumu',
                html: statusHtml,
                confirmButtonText: 'Tamam',
                width: '600px'
            });
        } else {
            Swal.fire('Bilgi', 'Webhook bilgisi bulunamadı. Lütfen önce webhook kaydedin.', 'info');
        }
    }).fail(function () {
        Swal.close();
        Swal.fire('Hata', 'Durum sorgulanamadı', 'error');
    });
}

// Test Webhook
function testWebhook() {
    const webhookUrl = $('#webhookUrlInput').val();

    Swal.fire({
        title: 'Webhook Test Ediliyor...',
        html: `Test mesajı gönderiliyor...`,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: webhookUrl,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            event: 'TEST_EVENT',
            data: {
                key: {
                    id: 'TEST_' + Date.now(),
                    remoteJid: '0000000000@s.whatsapp.net',
                    fromMe: false
                },
                message: {
                    conversation: 'Bu bir test mesajıdır'
                },
                pushName: 'Test User',
                messageTimestamp: Math.floor(Date.now() / 1000)
            }
        }),
        success: function (res) {
            Swal.close();
            Swal.fire({
                icon: 'success',
                title: 'Test Başarılı!',
                html: `
                    <p class="mb-2">Webhook başarıyla test edildi</p>
                    <div class="bg-gray-50 p-3 rounded text-left">
                        <p class="text-xs"><strong>Yanıt:</strong></p>
                        <pre class="text-xs mt-1 overflow-auto">${JSON.stringify(res, null, 2)}</pre>
                    </div>
                `,
                confirmButtonText: 'Tamam'
            });
        },
        error: function (xhr) {
            Swal.close();
            const responseText = xhr.responseText || 'Bilinmeyen hata';
            Swal.fire({
                icon: 'error',
                title: 'Test Başarısız',
                html: `
                    <p class="mb-2">Webhook test edilemedi</p>
                    <div class="bg-red-50 p-3 rounded text-left">
                        <p class="text-xs text-red-800"><strong>Hata:</strong> ${responseText}</p>
                    </div>
                `,
                confirmButtonText: 'Tamam'
            });
        }
    });
}
