(function () {
    var dialog = document.getElementById('adminLoginDialog');
    var openButton = document.querySelector('[data-login-open]');
    var closeButton = document.querySelector('[data-login-close]');

    if (!dialog || !openButton || !closeButton) {
        return;
    }

    openButton.addEventListener('click', function () {
        dialog.showModal();
        dialog.querySelector('input[name="username"]').focus();
    });

    closeButton.addEventListener('click', function () {
        dialog.close();
        openButton.focus();
    });

    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) {
            dialog.close();
            openButton.focus();
        }
    });
})();
