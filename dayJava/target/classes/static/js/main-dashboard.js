// Configuration des couleurs pour les graphiques
const chartColors = {
    primary: '#4e73df',
    secondary: '#858796',
    success: '#1cc88a',
    info: '#36b9cc',
    warning: '#f6c23e',
    danger: '#e74a3b',
    light: '#f8f9fc',
    dark: '#5a5c69'
};

// Initialisation des graphiques
let offerStatusChart;
let projectStatusChart;
let clientProjectsChart;

// Fonction pour formater les montants
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Fonction pour mettre à jour les totaux
function updateTotals(data) {
    document.getElementById('totalOffers').textContent = data.totalOffers;
    document.getElementById('totalProjects').textContent = data.totalProjects;
    document.getElementById('totalClients').textContent = data.totalClients;
}

// Fonction pour initialiser le graphique des statuts des offres
function initOfferStatusChart(data) {
    const ctx = document.getElementById('offerStatusChart').getContext('2d');
    offerStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data.offerStatusData),
            datasets: [{
                data: Object.values(data.offerStatusData),
                backgroundColor: [
                    chartColors.success,
                    chartColors.warning,
                    chartColors.danger
                ],
                hoverBackgroundColor: [
                    chartColors.success,
                    chartColors.warning,
                    chartColors.danger
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Fonction pour initialiser le graphique des statuts des projets
function initProjectStatusChart(data) {
    const ctx = document.getElementById('projectStatusChart').getContext('2d');
    projectStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(data.projectStatusData),
            datasets: [{
                data: Object.values(data.projectStatusData),
                backgroundColor: [
                    chartColors.success,
                    chartColors.primary,
                    chartColors.danger
                ],
                hoverBackgroundColor: [
                    chartColors.success,
                    chartColors.primary,
                    chartColors.danger
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Fonction pour initialiser le graphique des projets par client
function initClientProjectsChart(data) {
    const ctx = document.getElementById('clientProjectsChart').getContext('2d');
    clientProjectsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(data.clientProjectsData),
            datasets: [{
                label: 'Nombre de projets',
                data: Object.values(data.clientProjectsData),
                backgroundColor: chartColors.info,
                hoverBackgroundColor: chartColors.info
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Fonction pour charger les données du dashboard
async function loadDashboardData() {
    try {
        const response = await fetch('/api/dashboard/overview');
        const data = await response.json();
        
        updateTotals(data);
        initOfferStatusChart(data);
        initProjectStatusChart(data);
        initClientProjectsChart(data);
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    }
}

// Initialisation du dashboard
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
});

// Données des graphiques
const projectData = /*[[${projectStats}]]*/ {
    totalProjects: 0
};

const offerData = /*[[${offerStats}]]*/ {
    totalOffers: 0
};

const invoiceData = /*[[${invoiceStats}]]*/ {
    totalInvoices: 0
};

// Graphique des Projets
new Chart(document.getElementById('projectStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Total Projets'],
        datasets: [{
            data: [projectData.totalProjects],
            backgroundColor: ['#4e73df']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Graphique des Offres
new Chart(document.getElementById('offerStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Total Offres'],
        datasets: [{
            data: [offerData.totalOffers],
            backgroundColor: ['#36b9cc']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Graphique des Factures
new Chart(document.getElementById('invoiceStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Total Factures'],
        datasets: [{
            data: [invoiceData.totalInvoices],
            backgroundColor: ['#f6c23e']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
}); 