const tg = window.Telegram.WebApp;

// Initialize Telegram Web App
function initApp() {
    tg.expand();

    // Set theme
    document.documentElement.setAttribute('data-theme', tg.colorScheme);

    // Listen for theme changes
    tg.onEvent('themeChanged', function () {
        document.documentElement.setAttribute('data-theme', tg.colorScheme);
    });

    // Main button setup
    tg.MainButton.setParams({
        text: 'بستن پنل',
        is_visible: false
    });
}

// Show loading
function showLoading() {
    document.querySelector('.loading-overlay').style.display = 'flex';
}

// Hide loading
function hideLoading() {
    document.querySelector('.loading-overlay').style.display = 'none';
}

// Format price
function formatPrice(price) {
    return new Intl.NumberFormat('fa-IR').format(price) + ' تومان';
}

// Initialize
document.addEventListener('DOMContentLoaded', initApp);
