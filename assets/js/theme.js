/**
 * Theme Toggle Functionality for Mossé Luxe
 * Handles dark/light theme switching with localStorage persistence
 */

class ThemeManager {
    constructor() {
        this.html = document.documentElement;
        this.toggleButton = document.getElementById('theme-toggle');
        this.headerLogo = document.getElementById('header-logo');
        this.announcementBar = document.getElementById('announcement-bar');

        this.lightLogoSrc = window.SITE_URL + 'assets/images/logo-dark.png';
        this.darkLogoSrc = window.SITE_URL + 'assets/images/logo-light.png';

        this.init();
    }

    init() {
        // Get saved theme preference or detect system preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const initialTheme = savedTheme || (prefersDark ? 'dark' : 'light');

        // Set initial theme
        this.setTheme(initialTheme);

        // Add event listener to toggle button
        if (this.toggleButton) {
            this.toggleButton.addEventListener('click', () => {
                this.toggleTheme();
            });
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('theme')) {
                // Only auto-switch if no manual preference set
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    setTheme(theme) {
        this.html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        this.updateLogo(theme);
        this.updateUI(theme);
    }

    toggleTheme() {
        const currentTheme = this.html.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        this.setTheme(newTheme);
    }

    updateLogo(theme) {
        if (this.headerLogo) {
            // Add transition class for smooth logo change
            this.headerLogo.style.transition = 'opacity 0.3s ease';
            this.headerLogo.style.opacity = '0';

            setTimeout(() => {
                // Use different logos for different themes
                if (theme === 'dark') {
                    // Check if dark logo exists, fallback to light logo
                    this.headerLogo.src = this.darkLogoSrc;
                    this.headerLogo.onerror = () => {
                        this.headerLogo.src = this.lightLogoSrc;
                    };
                } else {
                    this.headerLogo.src = this.lightLogoSrc;
                }
                this.headerLogo.style.opacity = '1';
            }, 150);
        }
    }

    updateUI(theme) {
        // Update announcement bar colors
        if (this.announcementBar) {
            if (theme === 'dark') {
                this.announcementBar.style.backgroundColor = 'var(--brand-bg-primary)';
                this.announcementBar.style.color = 'var(--brand-text-primary)';
            } else {
                this.announcementBar.style.backgroundColor = '';
                this.announcementBar.style.color = '';
            }
        }
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
});

// Export for potential use in other scripts
window.ThemeManager = ThemeManager;
