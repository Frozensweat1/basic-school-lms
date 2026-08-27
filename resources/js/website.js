import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

let websiteAbortController = null;

function focusableElements(container) {
    return [...container.querySelectorAll(
        'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    )].filter((element) => !element.hidden && element.offsetParent !== null);
}

function initialiseWebsiteShell() {
    websiteAbortController?.abort();
    websiteAbortController = new AbortController();
    const { signal } = websiteAbortController;

    const toggle = document.querySelector('[data-website-menu-toggle]');
    const menu = document.querySelector('[data-website-menu]');
    const backdrop = document.querySelector('[data-website-menu-backdrop]');
    const desktopMedia = window.matchMedia('(min-width: 768px)');
    const desktopNav = document.querySelector('[data-website-desktop-nav]');

    if (toggle && menu && backdrop) {
        const openIcon = toggle.querySelector('[data-menu-icon="open"]');
        const closeIcon = toggle.querySelector('[data-menu-icon="close"]');

        const setMenuOpen = (open, restoreFocus = true) => {
            menu.classList.toggle('hidden', !open);
            backdrop.classList.toggle('hidden', !open);
            menu.setAttribute('aria-hidden', open ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
            openIcon?.classList.toggle('hidden', open);
            closeIcon?.classList.toggle('hidden', !open);
            document.body.classList.toggle('website-menu-open', open);

            if (open) {
                requestAnimationFrame(() => focusableElements(menu)[0]?.focus({ preventScroll: true }));
            } else if (restoreFocus) {
                toggle.focus({ preventScroll: true });
            }
        };

        const isOpen = () => toggle.getAttribute('aria-expanded') === 'true';

        toggle.addEventListener('click', () => setMenuOpen(!isOpen()), { signal });
        backdrop.addEventListener('click', () => setMenuOpen(false), { signal });
        menu.querySelectorAll('a[href]').forEach((link) => {
            link.addEventListener('click', () => setMenuOpen(false, false), { signal });
        });

        document.addEventListener('click', (event) => {
            if (!isOpen() || menu.contains(event.target) || toggle.contains(event.target)) return;
            setMenuOpen(false);
        }, { signal });

        document.addEventListener('keydown', (event) => {
            if (!isOpen()) return;

            if (event.key === 'Escape') {
                event.preventDefault();
                setMenuOpen(false);
                return;
            }

            if (event.key !== 'Tab') return;
            const focusable = focusableElements(menu);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }, { signal });

        desktopMedia.addEventListener('change', (event) => {
            if (event.matches && isOpen()) setMenuOpen(false, false);
        }, { signal });

        setMenuOpen(false, false);
    }

    if (desktopNav) {
        const desktopNavItems = () => [...desktopNav.querySelectorAll('[data-desktop-nav-item]')]
            .filter((item) => !item.hidden && item.offsetParent !== null);

        const focusNavItem = (targetIndex) => {
            const items = desktopNavItems();
            if (!items.length) return;

            const index = (targetIndex + items.length) % items.length;
            items[index].focus({ preventScroll: true });
        };

        desktopNav.addEventListener('keydown', (event) => {
            if (!desktopMedia.matches) return;
            if (!['ArrowRight', 'ArrowLeft', 'Home', 'End'].includes(event.key)) return;

            const items = desktopNavItems();
            const currentIndex = items.findIndex((item) => item === document.activeElement);
            if (currentIndex < 0) return;

            event.preventDefault();

            if (event.key === 'Home') {
                focusNavItem(0);
                return;
            }

            if (event.key === 'End') {
                focusNavItem(items.length - 1);
                return;
            }

            const direction = event.key === 'ArrowRight' ? 1 : -1;
            focusNavItem(currentIndex + direction);
        }, { signal });
    }

    initialiseGalleryLightbox(signal);
}

function initialiseGalleryLightbox(signal) {
    const dialog = document.querySelector('[data-gallery-dialog]');
    if (!dialog) return;

    const image = dialog.querySelector('[data-gallery-image]');
    const caption = dialog.querySelector('[data-gallery-caption]');
    const closeButton = dialog.querySelector('[data-gallery-close]');
    let trigger = null;

    const close = () => {
        if (typeof dialog.close === 'function' && dialog.open) dialog.close();
        else dialog.removeAttribute('open');
    };

    document.querySelectorAll('[data-gallery-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const source = button.dataset.gallerySrc;
            if (!source || !image) return;

            trigger = button;
            const alternative = button.dataset.galleryAlt ?? '';
            const description = button.dataset.galleryCaption ?? alternative;
            image.src = source;
            image.alt = alternative;

            if (caption) {
                caption.textContent = description;
                caption.classList.toggle('hidden', !description);
            }

            document.body.classList.add('website-gallery-open');
            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', '');
            requestAnimationFrame(() => closeButton?.focus({ preventScroll: true }));
        }, { signal });
    });

    closeButton?.addEventListener('click', close, { signal });
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) close();
    }, { signal });
    dialog.addEventListener('close', () => {
        document.body.classList.remove('website-gallery-open');
        if (image) image.removeAttribute('src');
        trigger?.focus({ preventScroll: true });
        trigger = null;
    }, { signal });
    dialog.addEventListener('cancel', () => {
        document.body.classList.remove('website-gallery-open');
    }, { signal });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialiseWebsiteShell, { once: true });
} else {
    initialiseWebsiteShell();
}

document.addEventListener('livewire:navigated', initialiseWebsiteShell);
