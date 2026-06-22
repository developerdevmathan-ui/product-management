const hide = (element) => {
    element.hidden = true;
    element.style.display = 'none';
};

const show = (element) => {
    element.hidden = false;
    element.style.display = '';
};

const toggle = (element, visible) => {
    if (visible) {
        show(element);
    } else {
        hide(element);
    }
};

const focusableElements = (container) => [
    ...container.querySelectorAll(
        'a, button, input:not([type="hidden"]), textarea, select, details, [tabindex]:not([tabindex="-1"])',
    ),
].filter((element) => !element.hasAttribute('disabled'));

const bootDropdowns = () => {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const menu = dropdown.querySelector('[data-dropdown-menu]');

        if (!trigger || !menu) {
            return;
        }

        const close = () => toggle(menu, false);
        const open = () => toggle(menu, true);

        close();

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            menu.hidden ? open() : close();
        });

        menu.addEventListener('click', close);
        dropdown.addEventListener('close', close);

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target)) {
                close();
            }
        });
    });
};

const bootMobileNavigation = () => {
    document.querySelectorAll('[data-mobile-nav]').forEach((navigation) => {
        const toggleButton = navigation.querySelector('[data-mobile-nav-toggle]');
        const menu = navigation.querySelector('[data-mobile-nav-menu]');
        const openIcon = navigation.querySelector('[data-mobile-nav-open-icon]');
        const closeIcon = navigation.querySelector('[data-mobile-nav-close-icon]');

        if (!toggleButton || !menu) {
            return;
        }

        let isOpen = false;

        const render = () => {
            toggle(menu, isOpen);
            menu.classList.toggle('hidden', !isOpen);
            openIcon?.classList.toggle('hidden', isOpen);
            closeIcon?.classList.toggle('hidden', !isOpen);
        };

        toggleButton.addEventListener('click', () => {
            isOpen = !isOpen;
            render();
        });

        render();
    });
};

const bootModals = () => {
    document.querySelectorAll('[data-modal]').forEach((modal) => {
        const name = modal.dataset.modal;
        const shouldShow = modal.dataset.show === 'true';

        const close = () => {
            hide(modal);
            document.body.classList.remove('overflow-y-hidden');
        };

        const open = () => {
            show(modal);
            document.body.classList.add('overflow-y-hidden');

            if (modal.hasAttribute('data-focusable')) {
                setTimeout(() => focusableElements(modal)[0]?.focus(), 100);
            }
        };

        modal.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', close);
        });

        modal.querySelectorAll('[data-modal-backdrop]').forEach((backdrop) => {
            backdrop.addEventListener('click', close);
        });

        modal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusables = focusableElements(modal);

            if (focusables.length === 0) {
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });

        window.addEventListener('open-modal', (event) => {
            if (event.detail === name) {
                open();
            }
        });

        window.addEventListener('close-modal', (event) => {
            if (!event.detail || event.detail === name) {
                close();
            }
        });

        shouldShow ? open() : close();
    });

    document.querySelectorAll('[data-open-modal]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            window.dispatchEvent(new CustomEvent('open-modal', { detail: button.dataset.openModal }));
        });
    });
};

const bootAutoDismiss = () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        const delay = Number.parseInt(element.dataset.autoDismiss || '2000', 10);

        setTimeout(() => hide(element), Number.isNaN(delay) ? 2000 : delay);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    bootDropdowns();
    bootMobileNavigation();
    bootModals();
    bootAutoDismiss();
});
