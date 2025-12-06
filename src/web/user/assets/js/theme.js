/**
 * Theme Manager for Dark/Light Mode
 * Auto-detects system preference and allows manual toggle
 * Persists user preference in localStorage
 */

(function () {
    'use strict';

    const THEME_KEY = 'user-theme-preference';
    const DARK_CLASS = 'dark-mode';

    /**
     * Get the current theme preference
     * Priority: localStorage > Telegram WebApp > System preference
     */
    function getThemePreference() {
        // 1. Check localStorage first (user's manual choice)
        const savedTheme = localStorage.getItem(THEME_KEY);
        if (savedTheme) {
            return savedTheme;
        }

        // 2. Check Telegram WebApp color scheme
        if (window.Telegram?.WebApp?.colorScheme) {
            return window.Telegram.WebApp.colorScheme;
        }

        // 3. Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }

        return 'light';
    }

    /**
     * Apply theme to the document
     */
    function applyTheme(theme) {
        if (theme === 'dark') {
            document.body.classList.add(DARK_CLASS);
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.body.classList.remove(DARK_CLASS);
            document.documentElement.removeAttribute('data-theme');
        }

        // Update all toggle buttons
        updateToggleButtons(theme);
    }

    /**
     * Toggle between dark and light mode
     */
    function toggleTheme() {
        const currentTheme = document.body.classList.contains(DARK_CLASS) ? 'dark' : 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        // Save preference
        localStorage.setItem(THEME_KEY, newTheme);

        // Apply theme
        applyTheme(newTheme);

        return newTheme;
    }

    /**
     * Update toggle button icons
     */
    function updateToggleButtons(theme) {
        const toggleButtons = document.querySelectorAll('.theme-toggle');
        toggleButtons.forEach(btn => {
            const sunIcon = btn.querySelector('.fa-sun');
            const moonIcon = btn.querySelector('.fa-moon');

            if (sunIcon && moonIcon) {
                if (theme === 'dark') {
                    sunIcon.style.display = 'inline-block';
                    moonIcon.style.display = 'none';
                } else {
                    sunIcon.style.display = 'none';
                    moonIcon.style.display = 'inline-block';
                }
            }
        });
    }

    /**
     * Create toggle button HTML
     */
    function createToggleButton() {
        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.setAttribute('aria-label', 'تغییر تم');
        button.setAttribute('title', 'تغییر تم روشن/تاریک');
        button.innerHTML = '<i class="fas fa-moon"></i><i class="fas fa-sun"></i>';
        button.addEventListener('click', toggleTheme);
        return button;
    }

    /**
     * Initialize theme system
     */
    function initTheme() {
        // Apply initial theme
        const theme = getThemePreference();
        applyTheme(theme);

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                // Only auto-switch if user hasn't set a preference
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    // Expose functions globally
    window.ThemeManager = {
        toggle: toggleTheme,
        apply: applyTheme,
        getPreference: getThemePreference,
        createButton: createToggleButton
    };
})();
