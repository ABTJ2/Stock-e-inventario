document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const moduleHeaders = document.querySelectorAll('.module-header');
    const submenuItems = document.querySelectorAll('.submenu-item[href]:not([href="#"])');
    const exitAppButton = document.getElementById('exitAppButton');
    const passwordInput = document.getElementById('nueva_password');
    const confirmPasswordInput = document.getElementById('confirmar_password');
    const zeroSelectableInputs = document.querySelectorAll('[data-select-zero]');

    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            sidebar.classList.toggle('collapsed');
        });
    }

    syncActiveSidebarItem(submenuItems);

    moduleHeaders.forEach(function (header) {
        header.addEventListener('click', function (event) {
            event.preventDefault();

            const parent = this.closest('.module-item');
            if (!parent || !parent.querySelector('.submenu')) {
                return;
            }

            parent.classList.toggle('open');
        });
    });

    if (exitAppButton) {
        exitAppButton.addEventListener('click', function () {
            window.open('', '_self');
            window.close();

            if (!document.hidden) {
                window.history.back();
            }
        });
    }

    if (passwordInput && confirmPasswordInput) {
        const validateMatch = function () {
            if (confirmPasswordInput.value === '' || passwordInput.value === confirmPasswordInput.value) {
                confirmPasswordInput.setCustomValidity('');
                return;
            }

            confirmPasswordInput.setCustomValidity('Las contraseñas no coinciden.');
        };

        passwordInput.addEventListener('input', validateMatch);
        confirmPasswordInput.addEventListener('input', validateMatch);
    }

    zeroSelectableInputs.forEach(function (input) {
        input.addEventListener('focus', function () {
            if (this.value === '0' || this.value === '0.00') {
                this.select();
            }
        });
    });
});

function syncActiveSidebarItem(submenuItems) {
    if (!submenuItems.length) {
        return;
    }

    const currentPath = normalizePath(window.location.pathname);
    let activeItem = null;

    submenuItems.forEach(function (item) {
        item.classList.remove('active');

        const itemUrl = new URL(item.href, window.location.origin);
        if (normalizePath(itemUrl.pathname) === currentPath) {
            activeItem = item;
        }
    });

    if (!activeItem) {
        return;
    }

    activeItem.classList.add('active');

    const activeModule = activeItem.closest('.module-item');
    if (activeModule) {
        activeModule.classList.add('open', 'active');
    }
}

function normalizePath(pathname) {
    return decodeURIComponent(pathname)
        .replace(/\\/g, '/')
        .replace(/\/+/g, '/')
        .replace(/\/$/, '')
        .toLowerCase();
}
