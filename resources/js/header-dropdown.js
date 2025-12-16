document.addEventListener('DOMContentLoaded',function () {

    const toggleIcon = document.getElementById("toggleDropdown");
    const dropdown = document.getElementById("dropdownMenu");

    toggleIcon?.addEventListener("click", function () {
        dropdown.classList.toggle("d-none");
    });

    document.addEventListener("click", function (event) {
        if (toggleIcon && dropdown)
        if (!toggleIcon.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add("d-none");
        }
    });
    const toggleIcon2 = document.getElementById("toggleDropdown2");
    const languageSelector = document.getElementById("languageSelector");
    const dropdown2 = document.getElementById("dropdownMenu2");
    const closeLanguageBtn = document.getElementById("closeLanguage");

    function openLanguageDropdown() {
        // Hide the main dropdown and show the language dropdown
        dropdown.classList.add("d-none");
        dropdown2.classList.remove("d-none");
    }

    toggleIcon2?.addEventListener("click", function (event) {
        event.stopPropagation();
        openLanguageDropdown();
    });

    languageSelector?.addEventListener("click", function (event) {
        event.stopPropagation();
        openLanguageDropdown();
    });

    closeLanguageBtn?.addEventListener("click", function (event) {
        // Hide language dropdown and show main dropdown
        dropdown2.classList.add("d-none");
        dropdown.classList.remove("d-none");
        event.stopPropagation();
    });

    document.addEventListener("click", function (event) {
        if (toggleIcon2 && dropdown2 && languageSelector) {
            if (
                !toggleIcon2.contains(event.target) &&
                !languageSelector.contains(event.target) &&
                !dropdown2.contains(event.target) &&
                !closeLanguageBtn.contains(event.target)
            ) {
                dropdown2.classList.add("d-none");
            }
        }

    });
})
