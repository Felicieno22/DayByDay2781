// Fonction pour formater les montants
function formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Fonction pour formater les dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonction pour obtenir la classe CSS du statut
function getStatusClass(status) {
    switch (status) {
        case 'COMPLETED':
            return 'status-completed';
        case 'PENDING':
            return 'status-pending';
        case 'CANCELLED':
            return 'status-cancelled';
        default:
            return '';
    }
}

// Fonction pour mettre à jour les statistiques
function updateStatistics(data) {
    document.getElementById('totalPayments').textContent = data.totalPayments;
    document.getElementById('totalAmount').textContent = formatAmount(data.totalAmount);
    document.getElementById('pendingPayments').textContent = data.pendingPayments;
}

// Fonction pour afficher les paiements dans le tableau
function displayPayments(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    tbody.innerHTML = '';

    payments.forEach(payment => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${payment.id}</td>
            <td>${formatAmount(payment.amount)}</td>
            <td><span class="status-badge ${getStatusClass(payment.status)}">${payment.status}</span></td>
            <td>${formatDate(payment.createdAt)}</td>
            <td>
                <button class="btn btn-sm btn-primary me-2" onclick="editPayment(${payment.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePayment(${payment.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Fonction pour éditer un paiement
async function editPayment(paymentId) {
    try {
        const response = await fetch(`/api/payments/${paymentId}`);
        const payment = await response.json();
        
        document.getElementById('editPaymentId').value = payment.id;
        document.getElementById('editPaymentAmount').value = payment.amount;
        document.getElementById('editPaymentStatus').value = payment.status;
        document.getElementById('editPaymentDate').value = payment.createdAt;
        
        const modal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
        modal.show();
    } catch (error) {
        console.error('Erreur lors du chargement du paiement:', error);
        alert('Erreur lors du chargement du paiement');
    }
}

// Fonction pour mettre à jour un paiement
async function updatePayment(event) {
    event.preventDefault();
    const paymentId = document.getElementById('editPaymentId').value;
    const amount = document.getElementById('editPaymentAmount').value;
    const status = document.getElementById('editPaymentStatus').value;

    try {
        const response = await fetch(`/api/payments/${paymentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: paymentId,
                amount: parseFloat(amount),
                status: status,
                createdAt: document.getElementById('editPaymentDate').value
            })
        });

        if (response.ok) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
            modal.hide();
            loadPaymentData();
            alert('Paiement mis à jour avec succès');
        } else {
            throw new Error('Erreur lors de la mise à jour du paiement');
        }
    } catch (error) {
        console.error('Erreur lors de la mise à jour du paiement:', error);
        alert('Erreur lors de la mise à jour du paiement');
    }
}

// Fonction pour supprimer un paiement
async function deletePayment(paymentId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')) {
        try {
            const response = await fetch(`/api/payments/${paymentId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                if (modal) modal.hide();
                loadPaymentData();
                alert('Paiement supprimé avec succès');
            } else {
                throw new Error('Erreur lors de la suppression du paiement');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression du paiement:', error);
            alert('Erreur lors de la suppression du paiement');
        }
    }
}

// Fonction pour charger les données des paiements
async function loadPaymentData() {
    try {
        const response = await fetch('/api/dashboard/payments/details');
        const data = await response.json();
        
        updateStatistics(data);
        displayPayments(data.payments);
    } catch (error) {
        console.error('Erreur lors du chargement des données:', error);
    }
}

// Initialisation de la page
document.addEventListener('DOMContentLoaded', function() {
    loadPaymentData();
    
    // Gestionnaire d'événements pour le formulaire de mise à jour
    document.getElementById('editPaymentForm').addEventListener('submit', updatePayment);
}); 