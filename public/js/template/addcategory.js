let subcategories = [];

function addSubcategory() {
  const subInput = document.getElementById("subcategoryInput");
  const subName = subInput.value.trim();
  if (!subName) return;

  if (!subcategories.includes(subName)) {
    subcategories.push(subName);
    renderSubcategories();
  }

  subInput.value = "";
}

function renderSubcategories() {
  const list = document.getElementById("subcategoryList");
  list.innerHTML = "";

  subcategories.forEach((sub, index) => {
    const row = document.createElement("div");
    row.className = "category-row";

    const name = document.createElement("div");
    name.innerText = sub;

    const del = document.createElement("span");
    del.className = "delete-btn";
    del.innerHTML = "&trash;";
    del.onclick = () => {
      subcategories.splice(index, 1);
      renderSubcategories();
    };

    row.appendChild(name);
    row.appendChild(del);
    list.appendChild(row);
  });
}

function addCategory() {
  const catInput = document.getElementById("categoryInput");
  const catName = catInput.value.trim();
  if (!catName) return;

  const container = document.getElementById("categoryList");

  const row = document.createElement("div");
  row.className = "category-row";

  const nameCol = document.createElement("div");
  nameCol.classList.add("col-4");
  nameCol.innerText = catName;

  const select = document.createElement("select");
  select.className = "form-select";
  select.multiple = true;
  select.style.width = "200px";

  subcategories.forEach(sub => {
    const opt = document.createElement("option");
    opt.value = sub;
    opt.text = sub;
    select.appendChild(opt);
  });

  const del = document.createElement("span");
  del.className = "delete-btn";
  del.innerHTML = "&trash;";
  del.onclick = () => {
    container.removeChild(row);
  };

  row.appendChild(nameCol);
  row.appendChild(select);
  row.appendChild(del);
  container.appendChild(row);

  catInput.value = "";
}
document.addEventListener('DOMContentLoaded', function() {
    const addSubcategoryBtn = document.getElementById('addSubcategoryBtn');
    const subcategoryContainer = document.querySelector('.subcategory-container');
    const categoryForm = document.getElementById('categoryForm');
    const categoryInput = document.getElementById('categoryInput');
    
    // Add new subcategory input
    addSubcategoryBtn.addEventListener('click', function() {
        const subcategoryItem = document.createElement('div');
        subcategoryItem.className = 'subcategory-item mb-3';
        subcategoryItem.innerHTML = `
            <div class="d-flex align-items-center">
                <input type="text" class="form-control custom-input" placeholder="Enter subcategory">
                <button type="button" class="btn btn-outline-danger btn-sm ms-2 remove-subcategory">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        subcategoryContainer.appendChild(subcategoryItem);
        
        // Add event listener for remove button
        const removeBtn = subcategoryItem.querySelector('.remove-subcategory');
        removeBtn.addEventListener('click', function() {
            subcategoryItem.remove();
        });
        
        // Focus on the new input
        const newInput = subcategoryItem.querySelector('input');
        newInput.focus();
    });
    
    // Form submission
    categoryForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const categoryName = categoryInput.value.trim();
        const subcategoryInputs = subcategoryContainer.querySelectorAll('input[type="text"]');
        const subcategories = [];
        
        subcategoryInputs.forEach(input => {
            if (input.value.trim()) {
                subcategories.push(input.value.trim());
            }
        });
        
        if (categoryName && subcategories.length > 0) {
            addCategoryToList(categoryName, subcategories);
            resetForm();
            showSuccessMessage();
        } else {
            showErrorMessage();
        }
    });
    
    function addCategoryToList(categoryName, subcategories) {
        const categoryList = document.querySelector('.category-list');
        const categoryItem = document.createElement('div');
        categoryItem.className = 'category-item';
        
        let subcategoryHTML = '';
        subcategories.forEach(subcategory => {
            subcategoryHTML += `
                <div class="subcategory-display-item">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-file subcategory-icon me-2"></i>
                        <span class="subcategory-name">${escapeHtml(subcategory)}</span>
                        <button class="btn btn-link btn-sm ms-auto p-0 link-btn">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        
        categoryItem.innerHTML = `
            <div class="d-flex align-items-center category-header">
                <i class="fas fa-folder category-icon me-2"></i>
                <span class="category-name">${escapeHtml(categoryName)}</span>
                <button class="btn btn-link btn-sm ms-auto p-0 link-btn">
                    <i class="fas fa-link"></i>
                </button>
            </div>
            <div class="subcategory-display-list">
                ${subcategoryHTML}
            </div>
        `;
        
        categoryList.appendChild(categoryItem);
        
        // Add animation
        categoryItem.style.opacity = '0';
        categoryItem.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            categoryItem.style.transition = 'all 0.3s ease';
            categoryItem.style.opacity = '1';
            categoryItem.style.transform = 'translateY(0)';
        }, 100);
    }
    
    function resetForm() {
        categoryInput.value = '';
        const subcategoryInputs = subcategoryContainer.querySelectorAll('input[type="text"]');
        subcategoryInputs.forEach(input => {
            input.value = '';
        });
        
        // Remove extra subcategory inputs, keep only first two
        const subcategoryItems = subcategoryContainer.querySelectorAll('.subcategory-item');
        for (let i = 2; i < subcategoryItems.length; i++) {
            subcategoryItems[i].remove();
        }
        
        // Focus back to category input
        categoryInput.focus();
    }
    
    function showSuccessMessage() {
        const message = document.createElement('div');
        message.className = 'alert alert-success alert-dismissible fade show mt-3';
        message.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            Category and subcategories added successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.getElementById('categoryForm');
        form.parentNode.insertBefore(message, form.nextSibling);
        
        // Auto dismiss after 3 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.remove();
            }
        }, 3000);
    }
    
    function showErrorMessage() {
        const message = document.createElement('div');
        message.className = 'alert alert-danger alert-dismissible fade show mt-3';
        message.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            Please enter a category name and at least one subcategory.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.getElementById('categoryForm');
        form.parentNode.insertBefore(message, form.nextSibling);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.remove();
            }
        }, 5000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Add functionality to link buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.link-btn')) {
            const categoryName = e.target.closest('.category-item, .subcategory-display-item').querySelector('.category-name, .subcategory-name').textContent;
            console.log('Link clicked for:', categoryName);
            
            // Visual feedback
            const linkBtn = e.target.closest('.link-btn');
            const originalColor = linkBtn.style.color;
            linkBtn.style.color = '#3b82f6';
            
            setTimeout(() => {
                linkBtn.style.color = originalColor;
            }, 200);
            
            // You can add your link functionality here
            // For example: window.open(`/category/${encodeURIComponent(categoryName)}`, '_blank');
        }
    });
    
    // Enhanced input validation
    categoryInput.addEventListener('input', function() {
        const value = this.value.trim();
        if (value.length > 50) {
            this.setCustomValidity('Category name must be 50 characters or less');
        } else if (value.length > 0 && value.length < 2) {
            this.setCustomValidity('Category name must be at least 2 characters');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Add validation for subcategory inputs
    document.addEventListener('input', function(e) {
        if (e.target.matches('.subcategory-container input[type="text"]')) {
            const value = e.target.value.trim();
            if (value.length > 50) {
                e.target.setCustomValidity('Subcategory name must be 50 characters or less');
            } else {
                e.target.setCustomValidity('');
            }
        }
    });
});
const roomInput = document.getElementById('roomInput');
        const shelfInput = document.getElementById('shelfInput');
        const rowInput = document.getElementById('rowInput');
        const addLocationBtn = document.getElementById('addLocationBtn');
        const locationList = document.getElementById('locationList');

        const locations = [
            {room: 1, shelf: 4, row: 3},
            {room: 2, shelf: 5, row: 6},
            {room: 5, shelf: 7, row: 8},
            {room: 9, shelf: 2, row: 10}
        ];

        function renderLocations() {
            locationList.innerHTML = '';
            locations.forEach((loc, index) => {
                const item = document.createElement('div');
                item.className = 'location-item';
                item.innerHTML = `
                    <span>Room: ${loc.room} shelf : ${loc.shelf} Row : ${loc.row}</span>
                    <button class="btn-delete" onclick="deleteLocation(${index})">Delete</button>
                `;
                locationList.appendChild(item);
            });
        }

        function deleteLocation(index) {
            locations.splice(index, 1);
            renderLocations();
        }

        addLocationBtn.addEventListener('click', () => {
            const room = roomInput.value.trim();
            const shelf = shelfInput.value.trim();
            const row = rowInput.value.trim();

            if (room && shelf && row) {
                locations.push({room, shelf, row});
                roomInput.value = '';
                shelfInput.value = '';
                rowInput.value = '';
                renderLocations();
            }
        });

        renderLocations();