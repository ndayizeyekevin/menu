(function () {
    var slides = Array.prototype.slice.call(document.querySelectorAll('.hero-slideshow .hero-photo'));
    var toggle = document.querySelector('[data-hero-toggle]');

    if (slides.length < 2 || !toggle) {
        if (toggle) toggle.hidden = true;
        return;
    }

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reducedMotion.matches) {
        toggle.hidden = true;
        return;
    }

    var current = Math.max(0, slides.findIndex(function (slide) {
        return slide.classList.contains('is-active');
    }));
    var paused = false;
    var timer = null;

    function scheduleNext() {
        window.clearTimeout(timer);
        if (!paused && document.visibilityState === 'visible') {
            timer = window.setTimeout(showNext, 7000);
        }
    }

    function showNext() {
        slides[current].classList.remove('is-active');
        current = (current + 1) % slides.length;
        slides[current].classList.add('is-active');
        scheduleNext();
    }

    function updateToggle() {
        var icon = toggle.querySelector('i');
        var label = paused ? 'Play hero slideshow' : 'Pause hero slideshow';
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('aria-pressed', paused ? 'true' : 'false');
        toggle.querySelector('.visually-hidden').textContent = label;
        icon.classList.toggle('fa-pause', !paused);
        icon.classList.toggle('fa-play', paused);
    }

    toggle.addEventListener('click', function () {
        paused = !paused;
        updateToggle();
        scheduleNext();
    });

    document.addEventListener('visibilitychange', scheduleNext);
    reducedMotion.addEventListener('change', function (event) {
        if (event.matches) {
            paused = true;
            window.clearTimeout(timer);
            toggle.hidden = true;
        }
    });

    scheduleNext();
})();
