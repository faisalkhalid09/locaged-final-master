document.addEventListener('DOMContentLoaded', function() {
    // Re-initialize sidebar functionality
    initializeSidebar();
});

function initializeSidebar() {
    // Toggle sidebar functionality
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const mainContent = document.querySelector('.main-content');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const toggleText = document.getElementById('toggleText');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');


            // Update the text based on sidebar state
            if (sidebar.classList.contains('collapsed')) {
                toggleText.textContent = toggleText?.dataset?.expandText || 'Expand';
            } else {
                toggleText.textContent = toggleText?.dataset?.collapseText || 'Collapse';
            }
        });
    }

// Active state functionality for sidebar menu items
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', function (e) {
            // If it's a submenu toggle, prevent default and manage active classes
            if (this.parentElement.classList.contains('has-submenu')) {
                e.preventDefault();

                // If the submenu is already active, we don't want to deactivate others
                const parentLi = this.parentElement;
                if (!parentLi.classList.contains('active')) {
                    // Remove active class from all other parent items
                    document.querySelectorAll('.sidebar-menu .has-submenu').forEach(item => {
                        item.classList.remove('active');
                    });
                }
                // Toggle active class on the clicked parent
                parentLi.classList.toggle('active');

                // Desktop floating submenu overlay handling
                const isDesktop = window.matchMedia('(min-width: 992px)').matches;
                if (isDesktop) {
                    if (parentLi.classList.contains('active')) {
                        parentLi.classList.add('submenu-open');
                        overlay && overlay.classList.add('show');
                        // Position submenu relative to hovered parent item
                        const rect = this.getBoundingClientRect();
                        document.documentElement.style.setProperty('--submenu-top', Math.max(16, rect.top) + 'px');
                    } else {
                        parentLi.classList.remove('submenu-open');
                        // hide overlay if no other submenu is open
                        const anyOpen = document.querySelector('.has-submenu.submenu-open');
                        if (!anyOpen) overlay && overlay.classList.remove('show');
                    }
                }

            }
            // For regular links, the browser will navigate and the active state
            // will be set on page load by the Blade directive.
        });
    });

    // Also open submenus on hover for desktop
    const isDesktopNow = window.matchMedia('(min-width: 992px)');
    const bindHover = () => {
        if (!isDesktopNow.matches) return;
        document.querySelectorAll('.sidebar .has-submenu').forEach(item => {
            const trigger = item.querySelector('a.menu-toggle');
            if (!trigger) return;
            let closeTimer;
            trigger.addEventListener('mouseenter', function () {
                clearTimeout(closeTimer);
                item.classList.add('active','submenu-open');
                overlay && overlay.classList.add('show');
                const rect = this.getBoundingClientRect();
                document.documentElement.style.setProperty('--submenu-top', Math.max(16, rect.top) + 'px');
            });
            const submenu = item.querySelector('.submenu');
            if (submenu) {
                submenu.addEventListener('mouseenter', function () {
                    clearTimeout(closeTimer);
                    item.classList.add('active','submenu-open');
                    overlay && overlay.classList.add('show');
                });
                submenu.addEventListener('mouseleave', function () {
                    closeTimer = setTimeout(() => {
                        item.classList.remove('active','submenu-open');
                        const anyOpen = document.querySelector('.has-submenu.submenu-open');
                        if (!anyOpen) overlay && overlay.classList.remove('show');
                    }, 120);
                });
            }
            item.addEventListener('mouseleave', function () {
                closeTimer = setTimeout(() => {
                    item.classList.remove('active','submenu-open');
                    const anyOpen = document.querySelector('.has-submenu.submenu-open');
                    if (!anyOpen) overlay && overlay.classList.remove('show');
                }, 120);
            });
        });
    };
    bindHover();
    isDesktopNow.addEventListener('change', () => {
        // cleanup overlay when switching breakpoints
        overlay && overlay.classList.remove('show');
    });

    // Clicking overlay closes any open submenu
    if (overlay) {
        overlay.addEventListener('click', () => {
            document.querySelectorAll('.has-submenu.submenu-open').forEach(el => el.classList.remove('submenu-open'));
            overlay.classList.remove('show');
        });
    }

}
