class Pagination {
    constructor(containerId, itemsPerPage = 4) {
        this.container = document.getElementById(containerId);
        this.itemsPerPage = itemsPerPage;
        this.currentPage = 1;
        this.totalItems = 0;
        this.totalPages = 0;
        this.items = [];
        this.paginationContainer = null;
    }

    // Initialize pagination with data
    init(items) {
        this.items = items;
        this.totalItems = items.length;
        this.totalPages = Math.ceil(this.totalItems / this.itemsPerPage);
        this.setupPaginationControls();
        this.displayItems();
    }

    // Create pagination controls
    setupPaginationControls() {
        if (!this.paginationContainer) {
            this.paginationContainer = document.createElement('div');
            this.paginationContainer.className = 'pagination';
            this.container.appendChild(this.paginationContainer);
        }
        this.updatePaginationControls();
    }

    // Update pagination controls UI
    updatePaginationControls() {
        const maxVisiblePages = 5;
        let html = '';

        // Previous button
        html += `<a href="#" class="left-pagination page-num ${this.currentPage === 1 ? 'disabled' : ''}" data-page="prev">
            <i class="fa-solid fa-angle-left"></i>
        </a>`;

        // Calculate visible page numbers
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(this.totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // First page
        if (startPage > 1) {
            html += `<a href="#" class="page-num" data-page="1">1</a>`;
            if (startPage > 2) html += '<span class="page-dots">...</span>';
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            if (i === this.currentPage) {
                html += `<span class="page-info pagenation-active">${i}</span>`;
            } else {
                html += `<a href="#" class="page-num" data-page="${i}">${i}</a>`;
            }
        }

        // Last page
        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) html += '<span class="page-dots">...</span>';
            html += `<a href="#" class="page-num" data-page="${this.totalPages}">${this.totalPages}</a>`;
        }

        // Next button
        html += `<a href="#" class="left-pagination page-num ${this.currentPage === this.totalPages ? 'disabled' : ''}" data-page="next">
            <i class="fa-solid fa-chevron-right"></i>
        </a>`;

        this.paginationContainer.innerHTML = html;
        this.attachEventListeners();
    }

    // Handle pagination events
    attachEventListeners() {
        this.paginationContainer.querySelectorAll('.page-num').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.currentTarget.dataset.page;
                
                if (page === 'prev') {
                    if (this.currentPage > 1) this.goToPage(this.currentPage - 1);
                } else if (page === 'next') {
                    if (this.currentPage < this.totalPages) this.goToPage(this.currentPage + 1);
                } else {
                    this.goToPage(parseInt(page));
                }
            });
        });
    }

    // Navigate to specific page
    goToPage(page) {
        this.currentPage = page;
        this.updatePaginationControls();
        this.displayItems();
    }

    // Display items for current page
    displayItems() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const pageItems = this.items.slice(start, end);
        
        const archiveContainer = this.container.querySelector('.archive');
        if (archiveContainer) {
            archiveContainer.innerHTML = pageItems.map(item => this.renderCategoryCard(item)).join('');
        }
    }

    // Render individual category card
    renderCategoryCard(category) {
        return `
            <div class="col-md-6">
                <div class="border p-4 rounded-2">
                    <div class="d-flex justify-content-between">
                        <img src="${category.icon}" alt="${category.name}" />
                        <div class="d-flex">
                            <h5 class="d-flex mt-1">
                                <div class="color-pending ${category.colorClass} me-2 mt-1"></div>
                                ${category.status}
                            </h5>
                            <p class="ms-2">${category.pendingCount}</p>
                        </div>
                    </div>
                    <h3 class="mt-3">${category.name}</h3>
                    <div class="d-flex align-items-center mt-2">
                        <div class="progress" style="width: 100%; height: 8px; background-color: #eee; border-radius: 10px;">
                            <div class="progress-bar" role="progressbar" 
                                style="width: ${category.progress}%; border-radius: 10px; background-color: ${category.progressColor};"
                                aria-valuenow="${category.progress}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        <span class="ms-2">${category.total}</span>
                    </div>
                </div>
            </div>`;
    }
}
