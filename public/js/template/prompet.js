
document.getElementById("nextBtn").addEventListener("click", function () {
  document.getElementById("promptLayer").classList.remove("d-none");
});

document.getElementById("nextBtn3").addEventListener("click", function () {
  document.getElementById("promptLayer3").classList.remove("d-none");
});

document.getElementById("nextBtn2").addEventListener("click", function () {
  document.getElementById("promptLayer2").classList.remove("d-none");
});


function closePrompt() {
  document.getElementById("promptLayer").classList.add("d-none");
}


function closePrompt2() {
  document.getElementById("promptLayer2").classList.add("d-none");
}

function closePrompt3() {
  document.getElementById("promptLayer3").classList.add("d-none");
}


document.querySelectorAll(".btn-cancel").forEach(el =>
  el.addEventListener("click", closePrompt)
);
document.querySelectorAll(".exit").forEach(el =>
  el.addEventListener("click", closePrompt)
);

document.getElementById("exitBtn4").addEventListener("click", closePrompt);


document.getElementById("exitBtn2")?.addEventListener("click", closePrompt2);
document.getElementById("exitBtn3")?.addEventListener("click", closePrompt2);
document.getElementById("cancelBtn")?.addEventListener("click", closePrompt2);
