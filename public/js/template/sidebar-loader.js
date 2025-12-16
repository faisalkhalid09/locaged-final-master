// Sidebar loader script
document.addEventListener('DOMContentLoaded', function() {
    // For file:// protocol, we'll use a different approach
    // We'll include the sidebar content directly in the script
    const sidebarContent = `<!-- Sidebar -->
<nav id="sidebar" class="sidebar collapsed  position-absolute">
<div class="text-center mb-2 d-flex justify-content-center ">
  <a href="./index.html" class="text-decoration-none ms-0 ps-0"><img src="./assets/Logo 1.svg" alt="logo" class="sidebar-logo mb-3 mt-4 expanded-only pointer" /></a>
  <a href="./index.html" class="text-decoration-none ms-1 ps-0"><img src="./assets/Frame 2078547825 1.svg" alt="" class="collapsed-only  mb-1 mt-4 pointer" /></a>
</div>

    <ul class="sidebar-menu mt-1">
      <li class="mt-1">
        <a href="./index.html">
    <img src="./assets/dashboard-square-01.svg" class="me-3" />
    <span class="sidebar-text">Dashboard</span>
  </a>
</li>

      <li class="has-submenu">
        <a href="./upload.html" class="menu-toggle"> 
          <img src="./assets/document-text.svg" class="me-3" />
          <span class="sidebar-text">Documents</span>
        </a>
          <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./upload.html"><img src="./assets/icons8_upload-2.svg" class="me-2" /><span class="sidebar-text">Upload</span></a></li>
          <li class="mt-2"><a href="./uploaddoc.html"><img src="./assets/document-text2.svg" class="me-2" /><span class="sidebar-text">Upload (Alt)</span></a></li>
          <li class="mt-2"><a href="./openfile.html"><img src="./assets/folder-open.svg" class="me-2" /><span class="sidebar-text">Open File</span></a></li>
          <li class="mt-2"><a href="./viewfile.html"><img src="./assets/eye.svg" class="me-2" /><span class="sidebar-text">View File</span></a></li>
          <li class="mt-2"><a href="./documentinfo.html"><img src="./assets/info-circle.svg" class="me-2" /><span class="sidebar-text">Document Info</span></a></li>
          <li class="mt-2"><a href="./docversion.html"><img src="./assets/document-favorite.svg" class="me-2" /><span class="sidebar-text">Versions</span></a></li>
          <li class="mt-2"><a href="./dochistory.html"><img src="./assets/rotate-left.svg" class="me-2" /><span class="sidebar-text">History</span></a></li>
          <li class="mt-2"><a href="./send.html"><img src="./assets/message-edit.svg" class="me-2" /><span class="sidebar-text">Send</span></a></li>
          <li class="mt-2"><a href="./pysicallocationfile2.html"><img src="./assets/Flags.svg" class="me-2" /><span class="sidebar-text">Physical Location</span></a></li>
        </ul>
      </li>

      <li class="has-submenu">
        <a href="./approvels.html" class="menu-toggle">
          <img src="./assets/verify.svg" class="me-3" />
          <span class="sidebar-text">Approvals</span>
        </a>
        <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./approvels.html"><img src="./assets/tick-circle.svg" class="me-2" /><span class="sidebar-text">Approvals</span></a></li>
          <li class="mt-2"><a href="./fileforapprovels.html"><img src="./assets/verify.svg" class="me-2" /><span class="sidebar-text">Files for Approvals</span></a></li>
        </ul>
    </li>

      <li class="has-submenu">
        <a href="./activitylog.html" class="menu-toggle">
          <img src="./assets/rotate-left.svg" class="me-3" />
          <span class="sidebar-text">Audit & Activity</span>
        </a>
        <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./activity.html"><img src="./assets/clock.svg" class="me-2" /><span class="sidebar-text">Activity</span></a></li>
          <li class="mt-2"><a href="./activitylog.html"><img src="./assets/elements.svg" class="me-2" /><span class="sidebar-text">Activity Log</span></a></li>
          <li class="mt-2"><a href="./fileaudit.html"><img src="./assets/document-text2.svg" class="me-2" /><span class="sidebar-text">File Audit</span></a></li>
          <li class="mt-2"><a href="./useraudit.html"><img src="./assets/user.svg" class="me-2" /><span class="sidebar-text">User Audit</span></a></li>
        </ul>
    </li>

      <li class="has-submenu">
        <a href="./allcategory.html" class="menu-toggle">
          <img src="./assets/category.svg" class="me-3" />
          <span class="sidebar-text">Categories & Tags</span>
        </a>
        <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./allcategory.html"><img src="./assets/category.svg" class="me-2" /><span class="sidebar-text">All Categories</span></a></li>
          <li class="mt-2"><a href="./addcategorylastversion.html"><img src="./assets/edit-2.svg" class="me-2" /><span class="sidebar-text">Add Category</span></a></li>
          <li class="mt-2"><a href="./newcategoriesscreen.html"><img src="./assets/elements.svg" class="me-2" /><span class="sidebar-text">New Categories Screen</span></a></li>
          <li class="mt-2"><a href="./addtags.html"><img src="./assets/star.svg" class="me-2" /><span class="sidebar-text">Add Tags</span></a></li>
  </ul>
      </li>

      <li class="has-submenu">
        <a href="./addrole2.html" class="menu-toggle">
          <img src="./assets/user.svg" class="me-3" />
          <span class="sidebar-text">Roles & Members</span>
        </a>
        <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./addrole2.html"><img src="./assets/brifecase-tick.svg" class="me-2" /><span class="sidebar-text">Add Role</span></a></li>
          <li class="mt-2"><a href="./addviewmember2.html"><img src="./assets/user.svg" class="me-2" /><span class="sidebar-text">Add Member View</span></a></li>
        </ul>
      </li>

      <li class="has-submenu">
        <a href="./ocrstatus.html" class="menu-toggle">
          <img src="./assets/eye.svg" class="me-3" />
          <span class="sidebar-text">OCR</span>
        </a>
        <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./ocrstatus.html"><img src="./assets/eye4.svg" class="me-2" /><span class="sidebar-text">OCR Status</span></a></li>
          <li class="mt-2"><a href="./canviewocr.html"><img src="./assets/eye2.svg" class="me-2" /><span class="sidebar-text">Can View OCR</span></a></li>
        </ul>
      </li>

      <li class="has-submenu">
        <a href="./localization2.html" class="menu-toggle">
          <img src="./assets/setting-2.svg" class="me-3" />
          <span class="sidebar-text">Settings</span>
        </a>
        <ul class="submenu list-unstyled ms-4">
          <li class="mt-2"><a href="./localization2.html"><img src="./assets/Flags.svg" class="me-2" /><span class="sidebar-text">Localization</span></a></li>
          <li class="mt-2"><a href="./profile.html"><img src="./assets/user.svg" class="me-2" /><span class="sidebar-text">Profile</span></a></li>
          <li class="mt-2"><a href="./notification.html"><img src="./assets/notification.svg" class="me-2" /><span class="sidebar-text">Notification</span></a></li>
        </ul>
      </li>

      <li>
        <a href="./search.html">
          <img src="./assets/status-up.svg" class="me-3" />
          <span class="sidebar-text">Search</span>
        </a>
      </li>
    </ul>

    <div class="bottom-icons text-center mt-auto">
      <a href="#"><img src="./assets/help-circle.svg" /><span class="sidebar-text">Help</span></a>
      <a href="#" id="toggleSidebar"><img src="./assets/elements.svg" /><span class="sidebar-text" id="toggleText">Collapse</span></a>
    </div>
  </nav>`;

    // Find the sidebar container and replace its content
    const sidebarContainer = document.querySelector('.col-md-1.position-relative') || document.querySelector('.col-md-1');
    if (sidebarContainer) {
        // Clear existing content and insert the sidebar
        sidebarContainer.innerHTML = sidebarContent;
        
        // Re-initialize sidebar functionality
        initializeSidebar();
        
        // Set initial toggle text based on sidebar state
        const sidebar = document.getElementById('sidebar');
        const toggleText = document.getElementById('toggleText');
        if (sidebar && toggleText) {
            if (sidebar.classList.contains('collapsed')) {
                toggleText.textContent = 'Expand';
            } else {
                toggleText.textContent = 'Collapse';
            }
        }
    }
});

function initializeSidebar() {
    // Toggle sidebar functionality
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function () {
            const sidebar = document.getElementById('sidebar');
            const toggleText = document.getElementById('toggleText');
            
            sidebar.classList.toggle('collapsed');
            
            // Update the text based on sidebar state
            if (sidebar.classList.contains('collapsed')) {
                toggleText.textContent = 'Expand';
            } else {
                toggleText.textContent = 'Collapse';
            }
        });
    }

    // Menu toggle functionality - handle clicks for navigation and smooth hover transitions
    let globalHideTimeout;
    let globalIsHovering = false;
    
    // Get the entire sidebar container
    const sidebarContainer = document.querySelector('.sidebar');
    
    if (sidebarContainer) {
        // Global mouse enter for sidebar
        sidebarContainer.addEventListener('mouseenter', function() {
            globalIsHovering = true;
            clearTimeout(globalHideTimeout);
        });
        
        // Global mouse leave for sidebar
        sidebarContainer.addEventListener('mouseleave', function() {
            globalIsHovering = false;
            globalHideTimeout = setTimeout(() => {
                if (!globalIsHovering) {
                    // Hide all submenus when leaving the entire sidebar
                    document.querySelectorAll('.submenu').forEach(submenu => {
                        submenu.style.display = 'none';
                    });
                }
            }, 300);
        });
    }
    
    document.querySelectorAll('.has-submenu').forEach(submenuContainer => {
        const menuToggle = submenuContainer.querySelector('.menu-toggle');
        const submenu = submenuContainer.querySelector('.submenu');
        let hideTimeout;
        
        if (menuToggle && submenu) {
            // Show submenu on hover
            submenuContainer.addEventListener('mouseenter', function() {
                clearTimeout(hideTimeout);
                clearTimeout(globalHideTimeout);
                submenu.style.display = 'block';
            });
            
            // Hide submenu with delay when mouse leaves
            submenuContainer.addEventListener('mouseleave', function(e) {
                hideTimeout = setTimeout(() => {
                    // Only hide if not hovering over the sidebar and not hovering over another submenu
                    if (!globalIsHovering && !isHoveringOverAnySubmenu()) {
                        submenu.style.display = 'none';
                    }
                }, 200);
            });
            
            // Handle click on menu toggle - navigate to the page
            menuToggle.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href && href !== '#') {
                    window.location.href = href;
                }
            });
        }
    });
    
    // Helper function to check if mouse is hovering over any submenu
    function isHoveringOverAnySubmenu() {
        const submenus = document.querySelectorAll('.submenu');
        for (let submenu of submenus) {
            if (submenu.matches(':hover')) {
                return true;
            }
        }
        return false;
    }

    // Active state functionality for sidebar menu items
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', function (e) {
            // Only handle active state for menu toggles (submenu parents)
            if (this.classList.contains('menu-toggle')) {
                e.preventDefault();
                // Remove active class from all menu toggles
                document.querySelectorAll('.sidebar-menu .menu-toggle').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Add active class to clicked menu toggle
                this.classList.add('active');
            }
            // For regular links, let them navigate normally - active state will be set on the target page
        });
    });

    // Set active state based on current page
    setActiveStateBasedOnCurrentPage();
}

function setActiveStateBasedOnCurrentPage() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    
    // Remove any existing active classes
    document.querySelectorAll('.sidebar-menu a').forEach(item => {
        item.classList.remove('active');
    });
    
    // Find the matching menu item and make it active
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        const href = link.getAttribute('href');
        if (href) {
            // Extract the page name from href (remove ./ and any query parameters)
            const hrefPage = href.replace('./', '').split('?')[0];
            
            // Check if this link matches the current page
            if (hrefPage === currentPage || (currentPage === 'index.html' && hrefPage === 'index.html')) {
                link.classList.add('active');
                
                // If this is a submenu item, also activate its parent
                const submenuItem = link.closest('.submenu li a');
                if (submenuItem) {
                    const parentMenu = submenuItem.closest('.has-submenu').querySelector('.menu-toggle');
                    if (parentMenu) {
                        parentMenu.classList.add('active');
                    }
                }
            }
        }
    });
} 