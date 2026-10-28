(function () {
    var themeKey = 'kabc-admin-theme';
    var savedTheme = localStorage.getItem(themeKey);
    var root = document.documentElement;

    root.dataset.theme = savedTheme === 'dark' ? 'dark' : 'light';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.querySelector('[data-theme-toggle]');

        if (!toggle) {
            return;
        }

        var label = toggle.querySelector('.theme-toggle-label');
        var icon = toggle.querySelector('.theme-toggle-icon');

        function updateToggle() {
            var isDark = root.dataset.theme === 'dark';
            toggle.setAttribute('aria-pressed', String(isDark));
            label.textContent = isDark ? 'Light theme' : 'Dark theme';
            icon.classList.toggle('fa-sun', isDark);
            icon.classList.toggle('fa-moon', !isDark);
        }

        toggle.addEventListener('click', function () {
            var nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            root.dataset.theme = nextTheme;
            localStorage.setItem(themeKey, nextTheme);
            updateToggle();
        });

        updateToggle();
    });
})();
