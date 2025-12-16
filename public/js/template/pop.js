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

const toggleIcon2 = document.getElementById("toggleDropdown2");
const dropdown2 = document.getElementById("dropdownMenu2");
const closeLanguageBtn = document.getElementById("closeLanguage"); 

if (toggleIcon2 && dropdown2) {
  toggleIcon2.addEventListener("click", function () {
    dropdown2.classList.toggle("d-none");
  });
}

if (closeLanguageBtn) {
  closeLanguageBtn.addEventListener("click", function (event) {
    dropdown2.classList.add("d-none"); 
    event.stopPropagation(); 
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
