import Chart from 'chart.js/auto';


const chartEl = document.getElementById('approvalChart');
const donutEl = document.getElementById('docTypesDonut');

if (chartEl) {
    const weeklyData = JSON.parse(chartEl.dataset.weekly);
    const monthlyData = JSON.parse(chartEl.dataset.monthly);
    const yearlyData = JSON.parse(chartEl.dataset.yearly);

    const ctx = chartEl.getContext('2d');
    const approvedLabel = chartEl.dataset.approvedLabel || 'Approved';
    const pendingLabel = chartEl.dataset.pendingLabel || 'Pending';
    const declinedLabel = chartEl.dataset.declinedLabel || 'Declined';
    const expiredLabel = chartEl.dataset.expiredLabel || 'Expired';


    const labelsWeekly = weeklyData.map(item => item.day.substring(0, 3).toUpperCase());
    const labelsMonthly = monthlyData.map(item => {
        const [year, month] = item.month.split('-');
        return new Date(year, month - 1).toLocaleString('default', { month: 'short' }).toUpperCase();
    });
    const labelsYearly = yearlyData.map(item => item.year);

    function getData(dataArray, statusKey, length) {
        if (!Array.isArray(dataArray)) return Array(length).fill(0);
        return dataArray.map(item => item[statusKey] ?? 0);
    }


    // Initial chart config - will update data dynamically
    const config = {
        type: 'bar',
        data: {
            labels: labelsMonthly,
            datasets: [
                { label: approvedLabel, data: getData(monthlyData, 'approved', 12), backgroundColor: '#28a745', borderRadius: 6, barThickness: 14 },
                { label: pendingLabel, data: getData(monthlyData, 'pending', 12), backgroundColor: '#f1c40f', borderRadius: 6, barThickness: 14 },
                { label: declinedLabel, data: getData(monthlyData, 'declined', 12), backgroundColor: '#dc3545', borderRadius: 6, barThickness: 14 },
                { label: expiredLabel, data: getData(monthlyData, 'expired', 12), backgroundColor: '#343a40', borderRadius: 6, barThickness: 14 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    right: 20
                }
            },
            plugins: {
                legend: {
                    display: true
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
                    ticks: {
                        stepSize: 500000,
                        callback: function (value) {
                            if (value >= 1000000) return (value / 1000000) + 'M';
                            if (value >= 1000) return (value / 1000) + 'k';
                            return value;
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
        }
    };

    const chart = new Chart(ctx, config);

    // Button group handler for switching data/timeframe
    const buttons = document.querySelectorAll('.button-timeframe');
    buttons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and add to clicked
            buttons.forEach(btn => btn.classList.remove('button-active'));
            button.classList.add('button-active');

            const period = button.getAttribute('data-period');
            let labels, dataLength, dataSource;

            if (period === 'weekly') {
                labels = labelsWeekly;
                dataLength = 7;
                dataSource = weeklyData;
            } else if (period === 'monthly') {
                labels = labelsMonthly;
                dataLength = 12;
                dataSource = monthlyData;
            } else if (period === 'yearly') {
                labels = labelsYearly;
                dataLength = 5;
                dataSource = yearlyData;
            }

            // Update chart data and labels
            chart.data.labels = labels;
            chart.data.datasets[0].data = getData(dataSource, 'approved', dataLength);
            chart.data.datasets[1].data = getData(dataSource, 'pending', dataLength);
            chart.data.datasets[2].data = getData(dataSource, 'declined', dataLength);
            chart.data.datasets[3].data = getData(dataSource, 'expired', dataLength);

            chart.update();
        });
    });
}


if (donutEl) {
    const donutData = JSON.parse(donutEl.dataset.donutData);
    const chartType = donutData.type || donutEl.dataset.chartType;
    const noDataLabel = donutEl.dataset.noDataLabel || 'No Data';
    const ctx2 = donutEl.getContext('2d');

    // Color palette for hierarchical donut segments
    const colorPalette = [
        '#2563eb', '#3b82f6', '#60a5fa', '#6366f1', '#8b5cf6', '#a855f7', '#22c55e', '#10b981', '#14b8a6',
        '#06b6d4', '#0ea5e9', '#f59e0b', '#ef4444', '#84cc16', '#e11d48', '#f97316', '#475569', '#0ea5a5'
    ];

    window.currentDonutChart = null;

    // Navigation state for multi-level drilldown
    let donutHistoryStack = [];
    let currentDonutState = {
        type: chartType,
        data: donutData.data || [],
        title: donutData.title,
        subtitle: donutData.subtitle,
    };

    function updateDonutHeader(state) {
        if (!state) return;
        const titleEl = document.getElementById('donutTitle');
        const subtitleEl = document.getElementById('donutSubtitle');
        if (titleEl) titleEl.textContent = state.title || '';
        if (subtitleEl) subtitleEl.textContent = state.subtitle || '';

        const backBtnWrapper = document.getElementById('donutBackButton');
        if (backBtnWrapper) {
            backBtnWrapper.style.display = donutHistoryStack.length > 0 ? 'block' : 'none';
        }
    }

    function createDonutChartFromState(state) {
        const data = state.data || [];
        const type = state.type;

        if (window.currentDonutChart) {
            window.currentDonutChart.destroy();
        }

        // Handle empty data
        if (!data || data.length === 0) {
            window.currentDonutChart = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: [noDataLabel],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e9ecef'],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '66%',
                    layout: { padding: 0 },
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
            return;
        }

        const labels = data.map(item => item.name);
        const values = data.map(item => item.count);
        const colors = data.map((_, index) => colorPalette[index % colorPalette.length]);

        window.currentDonutChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                layout: { padding: 0 },
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                const val = ctx.raw;
                                const total = values.reduce((a, b) => a + b, 0);
                                const pct = total ? (val / total * 100).toFixed(1) : 0;
                                return `${ctx.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                },
                onClick: function (event, elements) {
                    if (!elements.length) return;
                    const clickedIndex = elements[0].index;
                    const clickedItem = data[clickedIndex];
                    if (!clickedItem || !clickedItem.id) return;
                    handleDonutDrilldown(type, clickedItem.id);
                }
            }
        });
    }

    function applyDonutState(nextState) {
        currentDonutState = nextState;
        updateDonutHeader(nextState);
        createDonutChartFromState(nextState);
    }

    function pushCurrentStateToHistory() {
        if (currentDonutState) {
            donutHistoryStack.push(currentDonutState);
        }
    }

    function handleDonutDrilldown(type, id) {
        if (type === 'departments') {
            pushCurrentStateToHistory();
            loadSubDepartmentsForDepartment(id);
        } else if (type === 'sub_departments') {
            pushCurrentStateToHistory();
            loadServicesForSubDepartment(id);
        } else if (type === 'services') {
            pushCurrentStateToHistory();
            loadCategoriesForService(id);
        } else if (type === 'categories') {
            // Final level: redirect to Documents by-category page so that
            // users land on the normal documents view instead of File Audit.
            window.location.href = `/documents/by-category/${id}?show_expired=1`;
        } else {
            // subcategories or any other terminal level – no further drilldown
        }
    }

    function fetchAndApplyDonut(url) {
        fetch(url)
            .then(response => response.json())
            .then(result => {
                if (result.error) {
                    console.error('Error loading donut data:', result.error);
                    // Roll back history step on error
                    if (donutHistoryStack.length > 0) {
                        donutHistoryStack.pop();
                        updateDonutHeader(currentDonutState);
                        createDonutChartFromState(currentDonutState);
                    }
                    return;
                }

                const nextState = {
                    type: result.type,
                    data: result.data || [],
                    title: result.title,
                    subtitle: result.subtitle,
                };

                applyDonutState(nextState);
            })
            .catch(error => {
                console.error('Error loading donut data:', error);
                if (donutHistoryStack.length > 0) {
                    donutHistoryStack.pop();
                    updateDonutHeader(currentDonutState);
                    createDonutChartFromState(currentDonutState);
                }
            });
    }

    function loadSubDepartmentsForDepartment(departmentId) {
        fetchAndApplyDonut(`/departments/${departmentId}/sub-departments`);
    }

    function loadServicesForSubDepartment(subDepartmentId) {
        fetchAndApplyDonut(`/sub-departments/${subDepartmentId}/services`);
    }

    function loadCategoriesForService(serviceId) {
        fetchAndApplyDonut(`/services/${serviceId}/categories`);
    }

    function goBackToDepartments() {
        if (!donutHistoryStack.length) {
            return;
        }
        const previousState = donutHistoryStack.pop();
        applyDonutState(previousState);
    }

    // Expose back handler globally (name kept for Blade compatibility)
    window.goBackToDepartments = goBackToDepartments;

    // Initial render
    applyDonutState(currentDonutState);
}


