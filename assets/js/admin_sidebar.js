(function () {
    var toggle = document.querySelector('.admin-sidebar-toggle');
    var panel = document.getElementById('adminSidebarPanel');

    if (!toggle || !panel) {
        return;
    }

    function setExpanded(expanded) {
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.setAttribute('aria-label', expanded ? 'Close admin navigation' : 'Open admin navigation');
        toggle.closest('.admin-sidebar').classList.toggle('menu-open', expanded);
    }

    toggle.addEventListener('click', function () {
        setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
    });

    panel.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            setExpanded(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
            setExpanded(false);
            toggle.focus();
        }
    });
})();
