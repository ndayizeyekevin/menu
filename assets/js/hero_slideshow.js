(function () {
    var slides = Array.prototype.slice.call(document.querySelectorAll('.hero-slideshow .hero-photo'));
    if (slides.length < 2) {
        return;
    }

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reducedMotion.matches) {
        return;
    }

    var current = Math.max(0, slides.findIndex(function (slide) {
        return slide.classList.contains('is-active');
    }));
    var timer = null;

    function scheduleNext() {
        window.clearTimeout(timer);
        if (!reducedMotion.matches && document.visibilityState === 'visible') {
            timer = window.setTimeout(showNext, 7000);
        }
    }

    function showNext() {
        slides[current].classList.remove('is-active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('is-active');
        scheduleNext();
    }

    document.addEventListener('visibilitychange', scheduleNext);
    reducedMotion.addEventListener('change', function (event) {
        window.clearTimeout(timer);
        if (!event.matches) scheduleNext();
    });

    scheduleNext();
})();
