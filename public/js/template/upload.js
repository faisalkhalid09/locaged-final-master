
  document.getElementById("nextBtn").addEventListener("click", function () {
    document.getElementById("promptLayer").classList.remove("d-none");
  });




document.querySelector(".btn-cancel2").addEventListener("click", closePrompt);

 const toggle = document.getElementById("ocr-toggle");
  const switchText = document.getElementById("switch-text");

  toggle.addEventListener("change", () => {
    if (toggle.checked) {
      switchText.textContent = "ON";
    } else {
      switchText.textContent = "";
    }
  });
  