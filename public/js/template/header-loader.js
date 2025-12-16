// Header loader script
document.addEventListener('DOMContentLoaded', function() {
    // For file:// protocol, we'll include the header content directly in the script
    const headerContent = `<!-- Header -->
<header class="header d-md-flex justify-content-between align-items-center me-3">
  <div class="header-left">
    <a href="./search.html" class="text-decoration-none text-muted"> 
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search" />
      </div>
    </a>
  </div>
  <div class="header-right mt-lg-0 mt-5 position-relative">
    <img src="./assets/logo.png" alt="logo" width="175"  />
    <a href="./notification.html" class="text-decoration-none">
      <div class="notification-badge">
        <img src="./assets/notification.svg" alt="">
        <span class="badge">5</span>
      </div>
    </a>
    
    <div class="user-avatar ps-2">
      <img
        src="./assets/97baf907c0c14b0ffd910004086ee5821fbb6b33.jpg"
        class="user"
        alt="User"
      />
      <div class=" d-flex justify-content-between pointer " id="toggleDropdown">
        <div class="user-info me-2">
          <span class="name">Jacob Mohamed</span>
          <span class="role">CEO</span>
        </div>
        <i class="fa-solid fa-caret-down pointer"  id="toggleDropdown"></i>
        <div class="postion-relative">
          <div class="pop p-4 d-none"  id="dropdownMenu">
            <div class="d-flex align-items-center">
              <img
                src="./assets/97baf907c0c14b0ffd910004086ee5821fbb6b33.jpg"
                alt=""
              />
              <div class="ms-3" >
                <h3>Jacob Mohamed</h3>
                <h4>Jacob123mo@gmail.com</h4>
              </div>
            </div>
            <a href="./profile.html" class="text-decoration-none text-dark">
              <div class="d-flex justify-content-between mt-3 mb-2">
                <h6><i class="fa-solid fa-user me-2" style="color: #9E9E9E;"></i>My Profile</h6>
                <i class="fa-solid fa-chevron-right pointer"></i>
              </div>
            </a>
            <div class="d-flex justify-content-between mb-2">
              <h6><i class="fa-solid fa-earth-americas me-2" style="color: #9E9E9E;"></i>English(U.S)</h6>
              <i class="fa-solid fa-chevron-right pointer" id="toggleDropdown2"></i>
            </div>
            <div class="d-flex justify-content-between mb-2">
              <h6><i class="fa-solid fa-circle-info me-2" style="color: #9E9E9E;"></i>Help</h6>
              <i class="fa-solid fa-chevron-right pointer"></i>
            </div>
          </div>
          <div class="language p-4 d-none" id="dropdownMenu2">
            <h3 class="mb-3"><i class="fa-solid fa-angle-left me-2 pointer" id="closeLanguage" style="color: #9E9E9E;"></i>Change Language </h3>
            <div class="">
              <div class="d-flex justify-content-between mb-2">
                  <h6><img src="./assets/Flags.svg" class="me-2" alt="">French</h6>
                  <input type="checkbox" class="form-check-input ms-2" >
              </div>
              <div class="d-flex justify-content-between mb-2">
                   <h6><img src="./assets/Flags (1).svg" class="me-2" alt="">English(U.S)</h6>
                  <input type="checkbox" class="form-check-input ms-2" >
              </div>
              <div class="d-flex justify-content-between mb-2">
              <h6><img src="./assets/Flags (2).svg" class="me-2" alt="">Arabic </h6>
                  <input type="checkbox" class="form-check-input ms-2" >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>`;

    // Find the header container and replace its content
    const headerContainer = document.querySelector('header.header');
    if (headerContainer) {
        // Clear existing content and insert the header
        headerContainer.outerHTML = headerContent;
        
        // Re-initialize header functionality
        initializeHeader();
    }
});

function initializeHeader() {
    // Dropdown functionality
    const toggleIcon = document.getElementById("toggleDropdown");
    const dropdown = document.getElementById("dropdownMenu");

    if (toggleIcon && dropdown) {
        toggleIcon.addEventListener("click", function () {
            dropdown.classList.toggle("d-none");
        });

        document.addEventListener("click", function (event) {
            if (!toggleIcon.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add("d-none");
            }
        });
    }

    // Language dropdown functionality
    const toggleIcon2 = document.getElementById("toggleDropdown2");
    const dropdown2 = document.getElementById("dropdownMenu2");
    const closeLanguageBtn = document.getElementById("closeLanguage"); 

    if (toggleIcon2 && dropdown2 && dropdown) {
        toggleIcon2.addEventListener("click", function (event) {
            event.stopPropagation(); // Prevent event from bubbling up
            dropdown2.style.position = dropdown.style.position;
            dropdown2.style.top = dropdown.offsetTop + 'px';
            dropdown2.style.left = dropdown.offsetLeft + 'px';
            dropdown2.style.width = dropdown.offsetWidth + 'px';
            dropdown2.style.height = dropdown.offsetHeight + 'px';
            dropdown2.style.background = getComputedStyle(dropdown).background;
            dropdown2.style.borderRadius = getComputedStyle(dropdown).borderRadius;
            dropdown2.style.boxShadow = getComputedStyle(dropdown).boxShadow;
            dropdown.classList.add('d-none'); // Hide screen1
            dropdown2.classList.remove('d-none'); // Show screen2
        });
    }

    if (closeLanguageBtn && dropdown2 && dropdown) {
        closeLanguageBtn.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            dropdown2.classList.add('d-none'); // Hide screen2
            dropdown.classList.remove('d-none'); // Show screen1
        });
    }

    if (toggleIcon2 && dropdown2) {
        document.addEventListener("click", function (event) {
            if (
                !toggleIcon2.contains(event.target) &&
                !dropdown2.contains(event.target)
            ) {
                dropdown2.classList.add("d-none");
            }
        });
    }
}