// Sample data for files
const filesData = [
  {
    id: 1,
    name: 'File name',
    type: 'excel',
    date: '12/9/2024',
    time: '11:00pm',
    status: 'approved'
  },
  {
    id: 2,
    name: 'File name',
    type: 'word',
    date: '12/9/2024',
    time: '11:00pm',
    status: 'approved'
  },
  {
    id: 3,
    name: 'File name',
    type: 'pdf',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 4,
    name: 'File name',
    type: 'excel',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 5,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 6,
    name: 'File name',
    type: 'pdf',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 7,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
    {
    id: 8,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  }
  ,
    {
    id: 9,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 10,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  }
  ,
    {
    id: 11,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
    {
    id: 12,
    name: 'File name',
    type: 'pdf',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 13,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
    {
    id: 14,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  }
  ,
    {
    id: 15,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  },
  {
    id: 16,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  }
  ,
    {
    id: 17,
    name: 'File name',
    type: 'word',
    date: '3/5/2024',
    time: '11:00pm',
    status: 'pending'
  }
];

// Function to get file icon based on type
function getFileIcon(type) {
  const icons = {
    excel: '<i class="fas fa-file-excel"></i>',
    pdf: '<i class="fas fa-file-pdf"></i>',
    word: '<i class="fas fa-file-word"></i>'
  };
  return icons[type] || '<i class="fas fa-file"></i>';
}

// Pagination variables
let currentPage = 1;
const rowsPerPage = 7;

// Function to render files table with pagination
function renderFilesTable() {
  const tbody = document.getElementById('filesTableBody');

  const start = (currentPage - 1) * rowsPerPage;
  const end = start + rowsPerPage;
  const paginatedData = filesData.slice(start, end);

  
  tbody.innerHTML = paginatedData.map(file => `
    <tr>
      <td>
        <div class="file-item ms-3">
          <label class="custom-checkbox me-2">
            <input type="checkbox" class="check-size me-2" />
            <span class="custom-box"></span>
          </label>
          <div class="file-icon ${file.type}">
            ${getFileIcon(file.type)}
          </div>
          <div>
            <div class="file-name">${file.name}</div>
          </div>
        </div>
      </td>
      <td>
        <p class="file-date text-center">${file.date}<br>${file.time}</p>
      </td>
      <td class="text-center marg-aprove">
        <span class="status-badge ${file.status}">
          ${file.status.charAt(0).toUpperCase() + file.status.slice(1)}
        </span>
      </td>
      <td>
        <div class="file-actions d-flex justify-content-center">
          <button class="btn-action me-2">Details</button>
          <button class="btn-action">Edit</button>
          <div class="position-relative d-inline-block ms-3">
            <button class="bg-transparent border-0 fw-bol toggle-menu-btn2">
              <i class="fa-solid fa-ellipsis-vertical fa-sm"></i>
            </button>
            <div class="sidebar-menu2 bg-white p-3 rounded-3 shadow Z-3" style="display: none;">
            <div class="menu-item mb-3 pointer">
              <img src="./assets/message-edit.svg" class="me-2" alt="">
              <span class="menu-text">Rename</span>
            </div>
            <div class="menu-item mb-3 pointer">
              <img src="./assets/document-favorite.svg" class="me-2" alt="">
              <span class="menu-text">Activity</span>
            </div>
            <div class="menu-item mb-3 pointer">
              <img src="./assets/user.svg" class="me-2" alt="">
              <span class="menu-text">Details</span>
            </div>
            <div class="menu-item mb-3 pointer">
              <img src="./assets/2 (2).svg" class="me-2" alt="">
              <span class="menu-text">Download</span>
            </div>
            <div class="menu-item mb-3 pointer">
              <img src="./assets/star.svg" class="me-2" alt="">
              <span class="menu-text">Add Favorite</span>
            </div>
            </div>
          </div>
        </div>
      </td>
    </tr>
  `).join('');

  // تحديث الباجينيشن
  renderPagination();
}

// Function to render pagination buttons
function renderPagination() {
  const paginationContainer = document.querySelector('.table-pagination');
  const totalPages = Math.ceil(filesData.length / rowsPerPage);

  let paginationHTML = `
    <button class="btn-page bg-transparent" ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(currentPage - 1)">
      <i class="fa-solid fa-chevron-left"></i> Back
    </button>
  `;

  for (let i = 1; i <= totalPages; i++) {
    paginationHTML += `<span class="page-num ${i === currentPage ? 'active' : ''}" onclick="changePage(${i})">${i}</span>`;
  }

  paginationHTML += `
    <button class="btn-page bg-transparent" ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(currentPage + 1)">
      Next <i class="fa-solid fa-chevron-right"></i>
    </button>
  `;

  paginationContainer.innerHTML = paginationHTML;
}

// Function to change page
function changePage(page) {
  currentPage = page;
  renderFilesTable();
}


renderFilesTable();


// Function to create approval chart
 const ctx = document.getElementById('approvalChart').getContext('2d');

  const chartDatasets = {
    weekly: {
      labels: ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'],
      datasets: [
        {
          label: 'Approved',
          data: [50000, 100000, 600000, 200000, 30000, 500000, 300000],
          backgroundColor: '#28a745',
          borderRadius: 6,
          barThickness: 14
        },
        {
          label: 'Pending',
          data: [100000, 1500000, 800000, 300000, 10000, 200000, 200000],
          backgroundColor: '#f1c40f',
          borderRadius: 6,
          barThickness: 14
        },
        {
          label: 'Rejected',
          data: [90000, 120000, 300000, 70000, 10000, 700000, 250000],
          backgroundColor: '#dc3545',
          borderRadius: 6,
          barThickness: 14
        }
      ]
    },
    monthly: {
      labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
      datasets: [
        {
          label: 'Approved',
          data: [800000, 700000, 500000, 600000],
          backgroundColor: '#28a745',
          borderRadius: 6,
          barThickness: 14
        },
        {
          label: 'Pending',
          data: [1000000, 900000, 1100000, 950000],
          backgroundColor: '#f1c40f',
          borderRadius: 6,
          barThickness: 14
        },
        {
          label: 'Rejected',
          data: [200000, 150000, 250000, 100000],
          backgroundColor: '#dc3545',
          borderRadius: 6,
          barThickness: 14
        }
      ]
    },
    yearly: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
      datasets: [
        {
          label: 'Approved',
          data: [1000000, 1200000, 900000, 1100000, 950000, 1050000],
          backgroundColor: '#28a745',
          borderRadius: 6,
          barThickness: 14
        },
        {
          label: 'Pending',
          data: [500000, 600000, 550000, 650000, 700000, 600000],
          backgroundColor: '#f1c40f',
          borderRadius: 6,
          barThickness: 14
        },
        {
          label: 'Rejected',
          data: [300000, 200000, 400000, 350000, 250000, 300000],
          backgroundColor: '#dc3545',
          borderRadius: 6,
          barThickness: 14
        }
      ]
    }
  };

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false
      }
    },
    scales: {
      x: {
        grid: {
          display: false
        },
        ticks: {
          font: {
            size: 12
          },
          color: '#6c757d'
        }
      },
      y: {
        beginAtZero: true,
        max: 1500000,
        ticks: {
          stepSize: 500000,
          callback: function (value) {
            return value >= 1000000 ? (value / 1000000) + 'M' : (value / 1000) + 'k';
          },
          font: {
            size: 12
          },
          color: '#6c757d'
        },
        grid: {
          color: '#e9ecef'
        }
      }
    },
    elements: {
      bar: {
        borderRadius: 6
      }
    }
  };

  let approvalChart = new Chart(ctx, {
    type: 'bar',
    data: chartDatasets.monthly, // default
    options: chartOptions
  });

  document.getElementById('chartFilters').addEventListener('click', function (e) {
    if (e.target.tagName === 'BUTTON') {
      const type = e.target.getAttribute('data-type');

      // Update chart data with fresh copies to ensure re-render
      const newData = chartDatasets[type];
      approvalChart.data.labels = [...newData.labels];
      approvalChart.data.datasets = newData.datasets.map(ds => ({ ...ds }));
      approvalChart.update();

      // Update active button style
      document.querySelectorAll('#chartFilters button').forEach(btn => btn.classList.remove('button-active'));
      e.target.classList.add('button-active');
    }
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

// Function to handle search functionality
function setupSearch() {
  const searchInput = document.querySelector('.search-files input');
  
  searchInput.addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    const filteredFiles = filesData.filter(file => 
      file.name.toLowerCase().includes(searchTerm)
    );
    
    // Re-render table with filtered data
    const tbody = document.getElementById('filesTableBody');
    tbody.innerHTML = filteredFiles.map(file => `
      <tr>
        <td><input type="checkbox"></td>
        <td>
          <div class="file-item">
            <div class="file-icon ${file.type}">
              ${getFileIcon(file.type)}
            </div>
            <div>
              <div class="file-name">${file.name}</div>
              <div class="file-date">${file.date}</div>
            </div>
          </div>
        </td>
        <td>
          <div class="file-date">${file.date}<br>${file.time}</div>
        </td>
        <td>
          <span class="status-badge ${file.status}">
            ${file.status.charAt(0).toUpperCase() + file.status.slice(1)}
          </span>
        </td>
        <td>
          <div class="file-actions">
            <button class="btn-action">Details</button>
            <button class="btn-action">Edit</button>
            <button class="btn-action">⋮</button>
          </div>
        </td>
      </tr>
    `).join('');
  });
}

// Function to handle tab switching
function setupTabs() {
  const tabs = document.querySelectorAll('.tab');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    });
  });
}

// Function to handle category hover effects
function setupCategoryHovers() {
  const categoryItems = document.querySelectorAll('.category-item');
  
  categoryItems.forEach(item => {
    item.addEventListener('mouseenter', () => {
      item.style.transform = 'translateX(5px)';
    });
    
    item.addEventListener('mouseleave', () => {
      item.style.transform = 'translateX(0)';
    });
  });
}

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', () => {
  renderFilesTable();
  if (typeof createApprovalChart === 'function') {
    createApprovalChart();
  }
  animateCounters();
  setupSearch();
  setupTabs();
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
});

// Add click handlers for various interactive elements
document.addEventListener('click', (e) => {
  // Handle file action buttons
  if (e.target.classList.contains('btn-action')) {
    const action = e.target.textContent;
    console.log(`${action} action clicked`);
  }
  
  // Handle export button
  if (e.target.closest('.btn-export')) {
    console.log('Export button clicked');
  }
  
  // Handle upload button
  if (e.target.closest('.btn-upload')) {
    console.log('Upload button clicked');
  }
});

// Toggle three-dots menus in Recent Files (dashboard)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.toggle-menu-btn2');
  if (!btn) return;

  const currentMenu = btn.parentElement.querySelector('.sidebar-menu2');
  if (!currentMenu) return;

  // Close other menus
  document.querySelectorAll('.files-table .sidebar-menu2').forEach(menu => {
    if (menu !== currentMenu) {
      menu.classList.remove('show');
      menu.style.display = 'none';
    }
  });

  const isVisible = currentMenu.classList.contains('show');
  if (isVisible) {
    currentMenu.classList.remove('show');
    setTimeout(() => { currentMenu.style.display = 'none'; }, 300);
  } else {
    currentMenu.style.display = 'block';
    setTimeout(() => { currentMenu.classList.add('show'); }, 10);
  }
});

// Close menus when clicking outside
document.addEventListener('click', (e) => {
  if (e.target.closest('.sidebar-menu2') || e.target.closest('.toggle-menu-btn2')) return;
  document.querySelectorAll('.files-table .sidebar-menu2').forEach(menu => {
    if (menu.classList.contains('show')) {
      menu.classList.remove('show');
      setTimeout(() => { menu.style.display = 'none'; }, 300);
    }
  });
});


const nextBtn = document.getElementById("nextBtn");
if (nextBtn) {
  nextBtn.addEventListener("click", function () {
    const promptLayer = document.getElementById("promptLayer");
    if (promptLayer) {
      promptLayer.classList.remove("d-none");
    }
  });
}

const btnOk = document.querySelector(".btn-ok");
if (btnOk) {
  btnOk.addEventListener("click", function () {
    const promptLayer = document.getElementById("promptLayer");
    if (promptLayer) {
      promptLayer.classList.add("d-none");
    }
  });
}
  
