(function () {
    var button = document.querySelector('[data-scroll-top]');

    if (!button) {
        return;
    }

    function updateVisibility() {
        button.hidden = window.scrollY < 400;
    }

    window.addEventListener('scroll', updateVisibility, { passive: true });
    updateVisibility();

    button.addEventListener('click', function () {
        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
    });
})();
