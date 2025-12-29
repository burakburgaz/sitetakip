// assets/js/api-helper.js - API URL yardımcısı

/**
 * API çağrıları için temel URL'i döndürür
 * Hem localhost hem canlı sunucuda çalışır
 */
function getApiUrl(endpoint) {
    // Mevcut sayfanın base URL'ini al
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');

    // API prefix'i kaldır (eğer varsa)
    const cleanEndpoint = endpoint.replace(/^\/+/, '');

    // Tam API URL'ini oluştur  
    return `${baseUrl}/${cleanEndpoint}`;
}

/**
 * Güvenli AJAX GET isteği
 */
function apiGet(endpoint, data = {}) {
    return $.get(getApiUrl(endpoint), data);
}

/**
 * Güvenli AJAX POST isteği
 */
function apiPost(endpoint, data = {}) {
    return $.post(getApiUrl(endpoint), data);
}

// Export to global scope
window.getApiUrl = getApiUrl;
window.apiGet = apiGet;
window.apiPost = apiPost;
