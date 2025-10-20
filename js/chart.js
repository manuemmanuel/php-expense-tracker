// Chart.js configuration and utility functions for Expense Tracker

// Global chart configuration
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#6c757d';

// Color palette for charts
const chartColors = {
    primary: '#20c997',
    primaryDark: '#1aa179',
    secondary: '#6c757d',
    success: '#28a745',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#17a2b8',
    light: '#f8f9fa',
    dark: '#343a40'
};

// Extended color palette for multiple datasets
const extendedColors = [
    '#20c997', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1',
    '#fd7e14', '#20c997', '#6c757d', '#e83e8c', '#28a745',
    '#20c997', '#17a2b8', '#ffc107', '#dc3545', '#6f42c1'
];

// Utility function to format currency
function formatCurrency(value) {
    return '₹' + parseFloat(value).toFixed(2);
}

// Utility function to format numbers
function formatNumber(value) {
    return parseFloat(value).toLocaleString();
}

// Create a responsive line chart
function createLineChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Create a responsive bar chart
function createBarChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Create a responsive doughnut chart
function createDoughnutChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const dataset = data.datasets[0];
                                    const value = dataset.data[i];
                                    const total = dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    
                                    return {
                                        text: `${label}: ${formatCurrency(value)} (${percentage}%)`,
                                        fillStyle: dataset.backgroundColor[i],
                                        strokeStyle: dataset.borderColor || '#fff',
                                        lineWidth: dataset.borderWidth || 2,
                                        pointStyle: 'circle',
                                        hidden: false,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Create a responsive pie chart
function createPieChart(ctx, data, options = {}) {
    const defaultOptions = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return `${context.label}: ${formatCurrency(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    };
    
    return new Chart(ctx, Object.assign(defaultOptions, options));
}

// Animation utilities
function fadeIn(element, duration = 300) {
    element.style.opacity = 0;
    element.style.display = 'block';
    
    let start = performance.now();
    
    function animate(timestamp) {
        let progress = (timestamp - start) / duration;
        
        if (progress < 1) {
            element.style.opacity = progress;
            requestAnimationFrame(animate);
        } else {
            element.style.opacity = 1;
        }
    }
    
    requestAnimationFrame(animate);
}

function slideIn(element, direction = 'left', duration = 300) {
    const startPosition = direction === 'left' ? -100 : 100;
    element.style.transform = `translateX(${startPosition}px)`;
    element.style.opacity = 0;
    element.style.display = 'block';
    
    let start = performance.now();
    
    function animate(timestamp) {
        let progress = (timestamp - start) / duration;
        
        if (progress < 1) {
            const currentPosition = startPosition * (1 - progress);
            element.style.transform = `translateX(${currentPosition}px)`;
            element.style.opacity = progress;
            requestAnimationFrame(animate);
        } else {
            element.style.transform = 'translateX(0)';
            element.style.opacity = 1;
        }
    }
    
    requestAnimationFrame(animate);
}

// Form validation utilities
function validateAmount(amount) {
    const num = parseFloat(amount);
    return !isNaN(num) && num > 0;
}

function validateDate(dateString) {
    const date = new Date(dateString);
    return date instanceof Date && !isNaN(date);
}

function validateRequired(value) {
    return value && value.trim().length > 0;
}

// Local storage utilities for draft saving
function saveDraft(formId, data) {
    try {
        localStorage.setItem(`draft_${formId}`, JSON.stringify(data));
    } catch (e) {
        console.warn('Could not save draft:', e);
    }
}

function loadDraft(formId) {
    try {
        const draft = localStorage.getItem(`draft_${formId}`);
        return draft ? JSON.parse(draft) : null;
    } catch (e) {
        console.warn('Could not load draft:', e);
        return null;
    }
}

function clearDraft(formId) {
    try {
        localStorage.removeItem(`draft_${formId}`);
    } catch (e) {
        console.warn('Could not clear draft:', e);
    }
}

// Auto-save functionality for forms
function enableAutoSave(formId, interval = 2000) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    let saveTimer;
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => {
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                saveDraft(formId, data);
            }, interval);
        });
    });
}

// Load draft data into form
function loadDraftIntoForm(formId) {
    const form = document.getElementById(formId);
    const draft = loadDraft(formId);
    
    if (form && draft) {
        Object.keys(draft).forEach(key => {
            const element = form.querySelector(`[name="${key}"]`);
            if (element) {
                element.value = draft[key];
            }
        });
    }
}

// Export chart as image
function exportChart(chart, filename = 'chart.png') {
    const link = document.createElement('a');
    link.download = filename;
    link.href = chart.toBase64Image();
    link.click();
}

// Print chart
function printChart(chart) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Chart Print</title>
                <style>
                    body { margin: 0; padding: 20px; text-align: center; }
                    img { max-width: 100%; height: auto; }
                </style>
            </head>
            <body>
                <img src="${chart.toBase64Image()}" alt="Chart">
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Responsive chart resize handler
function handleChartResize(charts) {
    let resizeTimer;
    
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            charts.forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }, 250);
    });
}

// Initialize tooltips for Bootstrap
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize popovers for Bootstrap
function initializePopovers() {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

// Smooth scroll to element
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Debounce function for performance optimization
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction() {
        const context = this;
        const args = arguments;
        const later = function() {
            timeout = null;
            if (!immediate) func.apply(context, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(context, args);
    };
}

// Throttle function for performance optimization
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Initialize all common functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    initializeTooltips();
    initializePopovers();
    
    // Add loading states to forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            }
        });
    });
    
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('alert-success')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    });
});

// Export functions for global use
window.ExpenseTracker = {
    createLineChart,
    createBarChart,
    createDoughnutChart,
    createPieChart,
    formatCurrency,
    formatNumber,
    fadeIn,
    slideIn,
    validateAmount,
    validateDate,
    validateRequired,
    saveDraft,
    loadDraft,
    clearDraft,
    enableAutoSave,
    loadDraftIntoForm,
    exportChart,
    printChart,
    handleChartResize,
    smoothScrollTo,
    debounce,
    throttle,
    chartColors,
    extendedColors
};
