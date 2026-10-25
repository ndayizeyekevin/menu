$(function () {
    $('#menu_id').select2({
        placeholder: 'Select a menu item',
        width: '100%',
        theme: 'classic'
    });

    $('form').on('submit', function () {
        $('#spinnerOverlay').addClass('active');
    });

    var feedback = document.getElementById('uploadFeedback');
    if (feedback && feedback.dataset.message) {
        Swal.fire({
            icon: feedback.dataset.type === 'success' ? 'success' : 'error',
            title: feedback.dataset.type === 'success' ? 'Success' : 'Oops!',
            text: feedback.dataset.message,
            confirmButtonColor: '#2ecc71'
        });
    }
});
