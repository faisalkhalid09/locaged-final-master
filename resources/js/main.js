// Sample data for files

import {Modal} from "bootstrap";

window.addEventListener('show-preview-modal', () => {
    const modalEl = document.getElementById('previewModal');
    const modal = new Modal(modalEl);
    modal.show();
});


// Function to animate counter numbers
function animateCounters() {
  const counters = document.querySelectorAll('.stat-content h3');

  counters.forEach(counter => {
    const target = parseInt(counter.textContent.replace(/,/g, ''));
    const increment = target / 100;
    let current = 0;

    const updateCounter = () => {
      if (current < target) {
        current += increment;
        counter.textContent = Math.ceil(current).toLocaleString();
        requestAnimationFrame(updateCounter);
      } else {
        counter.textContent = target.toLocaleString();
      }
    };

    updateCounter();
  });
}



// Function to handle category hover effects
function setupCategoryHovers() {
  const categoryItems = document.querySelectorAll('.category-item-hover');

  categoryItems.forEach(item => {
    item.addEventListener('mouseenter', () => {
      item.style.transform = 'translateX(5px)';
      item.style.background = '#e9ecef';
    });

    item.addEventListener('mouseleave', () => {
      item.style.transform = 'translateX(0)';
        item.style.background = '#fff';
    });
  });
}

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', () => {

  animateCounters();
  setupCategoryHovers();

  // Add some interactive effects
  const statCards = document.querySelectorAll('.stat-card');
  statCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-5px)';
      card.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.1)';
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0)';
      card.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.04)';
    });
  });



    document.getElementById("nextBtn")?.addEventListener("click", function () {
        document.getElementById("promptLayer").classList.remove("d-none");
    });

    document.querySelector(".btn-ok")?.addEventListener("click", function () {
        document.getElementById("promptLayer").classList.add("d-none");
    });

    document.getElementById("promptLayer")?.addEventListener("click", function (e) {
        if (e.target.id === "promptLayer") {
            document.getElementById("promptLayer").classList.add("d-none");
        }
    });

});

const toggle = document.getElementById("ocr-toggle");
const switchText = document.getElementById("switch-text");

if (toggle) {
    toggle.addEventListener("change", () => {
        if (switchText) {
            if (toggle.checked) {
                switchText.textContent = "ON";
            } else {
                switchText.textContent = "";
            }
        }

    });
}
