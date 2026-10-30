(function () {
    var header = document.querySelector('.site-header');
    var navigation = document.getElementById('primaryNavigation');
    var toggle = document.querySelector('[data-nav-toggle]');

    if (!header || !navigation || !toggle) return;

    header.classList.add('nav-ready');
    toggle.hidden = false;

    function closeNavigation(restoreFocus) {
        navigation.classList.remove('is-open');
        header.classList.remove('menu-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation');
        toggle.querySelector('i').classList.remove('fa-xmark');
        toggle.querySelector('i').classList.add('fa-bars');
        if (restoreFocus) toggle.focus();
    }

    toggle.addEventListener('click', function () {
        var isOpen = toggle.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeNavigation(false);
            return;
        }
        navigation.classList.add('is-open');
        header.classList.add('menu-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close navigation');
        toggle.querySelector('i').classList.remove('fa-bars');
        toggle.querySelector('i').classList.add('fa-xmark');
    });

    navigation.addEventListener('click', function (event) {
        if (event.target.closest('a, button')) closeNavigation(false);
    });

    document.addEventListener('click', function (event) {
        if (toggle.getAttribute('aria-expanded') === 'true' && !header.contains(event.target)) {
            closeNavigation(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            closeNavigation(true);
        }
    });

    function updateHeader() {
        header.classList.toggle('is-scrolled', window.scrollY > 28);
    }
    window.addEventListener('scroll', updateHeader, { passive: true });
    updateHeader();
})();
