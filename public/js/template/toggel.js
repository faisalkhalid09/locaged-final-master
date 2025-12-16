
  const toggle = document.getElementById("ocr-toggle");
  const switchText = document.getElementById("switch-text");

  toggle.addEventListener("change", () => {
    if (toggle.checked) {
      switchText.textContent = "ON";
    } else {
      switchText.textContent = "";
    }
  });
