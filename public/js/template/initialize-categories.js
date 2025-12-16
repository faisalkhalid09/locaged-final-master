// Sample data - Replace this with your backend API call
const sampleCategories = [
    {
        name: 'Archives',
        icon: './assets/Group 634.svg',
        status: 'Pending',
        pendingCount: 25,
        total: 100,
        progress: 100,
        progressColor: '#f0d672',
        colorClass: ''
    },
    {
        name: 'Finance',
        icon: './assets/Group 6.svg',
        status: 'Pending',
        pendingCount: 40,
        total: 400,
        progress: 100,
        progressColor: '#e63946',
        colorClass: 'red-pending'
    },
    {
        name: 'Marketing',
        icon: './assets/Clip path group.svg',
        status: 'Pending',
        pendingCount: 40,
        total: 350,
        progress: 100,
        progressColor: '#68a0fd',
        colorClass: 'blue-pending'
    },
    {
        name: 'Sales',
        icon: './assets/Group 8.svg',
        status: 'Pending',
        pendingCount: 50,
        total: 771,
        progress: 100,
        progressColor: '#47a778',
        colorClass: 'green-pending'
    },    {
        name: 'Software',
        icon: './assets/Clip path group.svg',
        status: 'Pending',
        pendingCount: 150,
        total: 771,
        progress: 100,
        progressColor: '#68a0fd',
        colorClass: 'blue-pending'
    },
        {
        name: 'Customer Service',
        icon: './assets/Group 8.svg',
        status: 'Pending',
        pendingCount: 120,
        total: 800,
        progress: 100,
        progressColor: '#47a778',
        colorClass: 'green-pending'
    }, 
    // Add more categories as needed
];

// Initialize pagination when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    // Create pagination instance
    const categoryPagination = new Pagination('categoriesContainer', 4);
    
    // Initialize with data
    // In a real application, you would fetch this data from your backend
    // Example:
    // async function initCategories() {
    //     try {
    //         const response = await fetch('/api/categories');
    //         const categories = await response.json();
    //         categoryPagination.init(categories);
    //     } catch (error) {
    //         console.error('Error fetching categories:', error);
    //     }
    // }
    
    // For now, initialize with sample data
    categoryPagination.init(sampleCategories);
});
