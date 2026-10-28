(function () {
    var themeKey = 'kabc-menu-theme';
    var root = document.documentElement;
    var themeToggle = document.querySelector('[data-theme-toggle]');

    function updateThemeToggle() {
        if (!themeToggle) return;

        var isDark = root.dataset.theme !== 'light';
        var label = themeToggle.querySelector('.theme-toggle-label');
        var icon = themeToggle.querySelector('.theme-toggle-icon');
        themeToggle.setAttribute('aria-pressed', String(isDark));
        themeToggle.setAttribute('aria-label', 'Switch to ' + (isDark ? 'light' : 'dark') + ' theme');

        if (label) label.textContent = isDark ? 'Light theme' : 'Dark theme';
        if (icon) {
            icon.classList.toggle('fa-sun', isDark);
            icon.classList.toggle('fa-moon', !isDark);
        }
    }

    updateThemeToggle();
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            localStorage.setItem(themeKey, root.dataset.theme);
            updateThemeToggle();
        });
    }

    var searchInput = document.getElementById('menuSearch');
    var columns = document.querySelector('.menu-columns');
    var categoryBlocks = document.querySelectorAll('.category-block');
    var searchState = document.getElementById('searchState');

    if (!searchInput || !columns) return;

    function normalize(text) {
        return String(text || '').trim().toLowerCase();
    }

    function matches(text, query) {
        return normalize(text).indexOf(query) !== -1;
    }

    function updateSearch() {
        var query = normalize(searchInput.value);
        var anyVisible = false;

        categoryBlocks.forEach(function (block) {
            var rows = Array.from(block.querySelectorAll('.menu-row'));
            var labels = Array.from(block.querySelectorAll('.subcat-label'));
            var visibleCount = 0;

            rows.forEach(function (row) {
                var text = row.dataset.search || row.textContent || '';
                var visible = query === '' || matches(text, query);
                row.style.display = visible ? '' : 'none';
                if (visible) visibleCount += 1;
            });

            labels.forEach(function (label) {
                var next = label.nextElementSibling;
                var labelVisible = false;

                while (next && !next.classList.contains('subcat-label')) {
                    if (next.classList.contains('menu-row') && next.style.display !== 'none') {
                        labelVisible = true;
                        break;
                    }
                    next = next.nextElementSibling;
                }

                label.style.display = labelVisible ? '' : 'none';
            });

            var blockVisible = visibleCount > 0;
            block.style.display = blockVisible ? '' : 'none';
            if (blockVisible) anyVisible = true;

            var countElement = block.querySelector('.cat-count');
            if (countElement) {
                countElement.textContent = visibleCount + ' item' + (visibleCount === 1 ? '' : 's');
            }
        });

        if (columns) {
            columns.style.display = anyVisible ? 'grid' : 'none';
        }
        if (searchState) {
            searchState.style.display = anyVisible ? 'none' : '';
        }
    }

    searchInput.addEventListener('input', updateSearch);
})();
