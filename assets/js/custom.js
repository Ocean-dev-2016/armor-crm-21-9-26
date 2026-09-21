function showToast(message, type = 'success') {

    const toast = document.getElementById('commonToast');
    const header = document.getElementById('commonToastHeader');
    const title = document.getElementById('commonToastTitle');
    const body = document.getElementById('commonToastBody');
    const icon = document.getElementById('commonToastIcon');

    // Remove old classes
    header.classList.remove(
        'bg-success',
        'bg-danger',
        'bg-warning',
        'bg-info'
    );

    // Set toast based on type
    switch (type) {

        case 'success':
            header.classList.add('bg-success');
            title.textContent = 'Success';
            icon.setAttribute('data-lucide', 'check-circle');
            break;

        case 'error':
            header.classList.add('bg-danger');
            title.textContent = 'Error';
            icon.setAttribute('data-lucide', 'x-circle');
            break;

        case 'warning':
            header.classList.add('bg-warning');
            title.textContent = 'Warning';
            icon.setAttribute('data-lucide', 'alert-triangle');
            break;

        case 'info':
            header.classList.add('bg-info');
            title.textContent = 'Information';
            icon.setAttribute('data-lucide', 'info');
            break;
    }

    body.textContent = message;

    // Recreate Lucide icon
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Show Bootstrap Toast
    const bsToast = bootstrap.Toast.getOrCreateInstance(toast, {
        delay: 3500
    });

    bsToast.show();
}