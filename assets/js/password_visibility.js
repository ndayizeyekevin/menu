(function () {
    function addPasswordToggles() {
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.dataset.visibilityToggle === 'ready') return;
            input.dataset.visibilityToggle = 'ready';

            var wrapper = document.createElement('span');
            wrapper.className = 'password-field';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            var button = document.createElement('button');
            button.className = 'password-visibility-toggle';
            button.type = 'button';
            button.setAttribute('aria-label', 'Show password');
            button.setAttribute('aria-pressed', 'false');
            button.title = 'Show password';
            button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle><path class="password-eye-slash" d="M4 4l16 16"></path></svg>';

            button.addEventListener('click', function () {
                var isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
                button.setAttribute('aria-pressed', String(!isVisible));
                button.title = isVisible ? 'Show password' : 'Hide password';
                input.focus();
            });

            wrapper.appendChild(button);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addPasswordToggles);
    } else {
        addPasswordToggles();
    }
})();
