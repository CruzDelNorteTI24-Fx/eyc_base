function eycToggleModule(id) {
    const module = document.querySelector(`.sidebar-module[data-module="${id}"]`);
    if (!module) return;

    const wasOpen = module.classList.contains('open');

    document.querySelectorAll('.sidebar-module').forEach(item => {
        item.classList.remove('open');
    });

    if (!wasOpen) {
        module.classList.add('open');
        localStorage.setItem('eyc_sidebar_module', id);
    } else {
        localStorage.removeItem('eyc_sidebar_module');
    }
}

function eycToggleSidebarDesktop() {
    if (!window.matchMedia('(min-width: 992px)').matches) {
        return;
    }

    document.body.classList.toggle('sidebar-collapsed');

    const collapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('eyc_sidebar_collapsed', collapsed ? '1' : '0');
}

function eycToggleSidebarMobile() {
    document.body.classList.remove('sidebar-collapsed');

    const sidebar = document.getElementById('sidebareyc');
    const overlay = document.getElementById('sidebarOverlay');
    const button = document.querySelector('[data-sidebar-mobile-toggle]');

    if (!sidebar || !overlay) return;

    eycSetSidebarMobile(!sidebar.classList.contains('active'), sidebar, overlay, button);
}

function eycSetSidebarMobile(open, sidebar, overlay, button) {
    const isMobile = window.matchMedia('(max-width: 991px)').matches;

    if (!sidebar || !overlay) return;

    sidebar.classList.toggle('active', open && isMobile);
    overlay.classList.toggle('active', open && isMobile);
    document.body.classList.toggle('sidebar-mobile-open', open && isMobile);

    if (button) {
        const isOpen = open && isMobile;
        const icon = button.querySelector('i');

        button.classList.toggle('is-active', isOpen);
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        button.setAttribute('aria-label', isOpen ? 'Cerrar menu' : 'Abrir menu');

        if (icon) {
            icon.classList.toggle('bi-list', !isOpen);
            icon.classList.toggle('bi-x-lg', isOpen);
        }
    }

    if (open && isMobile) {
        document.body.classList.remove('sidebar-collapsed');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebareyc');
    if (!sidebar) return;

    const overlay = document.getElementById('sidebarOverlay');
    const mobileButton = document.querySelector('[data-sidebar-mobile-toggle]');
    const desktopQuery = window.matchMedia('(min-width: 992px)');

    document.body.classList.add('with-sidebar');

    function syncSidebarMode() {
        if (desktopQuery.matches) {
            eycSetSidebarMobile(false, sidebar, overlay, mobileButton);

            if (localStorage.getItem('eyc_sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        } else {
            document.body.classList.remove('sidebar-collapsed');
            eycSetSidebarMobile(false, sidebar, overlay, mobileButton);
        }
    }

    syncSidebarMode();

    if (desktopQuery.addEventListener) {
        desktopQuery.addEventListener('change', syncSidebarMode);
    } else if (desktopQuery.addListener) {
        desktopQuery.addListener(syncSidebarMode);
    }

    const activeLink = document.querySelector('.sidebar-link.active');

    if (activeLink) {
        const module = activeLink.closest('.sidebar-module');
        if (module) {
            module.classList.add('open');
            localStorage.setItem('eyc_sidebar_module', module.dataset.module);
        }
    } else {
        const savedModule = localStorage.getItem('eyc_sidebar_module');
        const module = savedModule
            ? document.querySelector(`.sidebar-module[data-module="${savedModule}"]`)
            : document.querySelector('.sidebar-module');

        if (module) module.classList.add('open');
    }

    const search = document.getElementById('sidebarSearch');

    if (search) {
        search.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();

            document.querySelectorAll('.sidebar-module').forEach(module => {
                let hasVisible = false;

                module.querySelectorAll('.sidebar-link').forEach(link => {
                    const text = link.textContent.toLowerCase();
                    const match = text.includes(q);

                    link.style.display = match ? 'flex' : 'none';

                    if (match) hasVisible = true;
                });

                module.style.display = hasVisible ? 'block' : 'none';

                if (q && hasVisible) {
                    module.classList.add('open');
                }
            });
        });
    }

    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            eycSetSidebarMobile(false, sidebar, overlay, mobileButton);
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            eycSetSidebarMobile(false, sidebar, overlay, mobileButton);
        }
    });
});
