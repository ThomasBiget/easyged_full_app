import React, { useState, useEffect } from 'react';
import { getInvoices, deleteInvoice } from '../api';
import UploadModal from '../components/UploadModal';

function Dashboard({ onLogout }) {
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showUpload, setShowUpload] = useState(false);
  const user = JSON.parse(localStorage.getItem('user') || '{}');

  const fetchInvoices = async () => {
    try {
      const response = await getInvoices();
      setInvoices(response.data || []);
    } catch (err) {
      console.error('Erreur:', err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInvoices();
  }, []);

  const handleDelete = async (id) => {
    if (window.confirm('Supprimer cette facture ?')) {
      try {
        await deleteInvoice(id);
        setInvoices(invoices.filter(inv => inv.id !== id));
      } catch (err) {
        alert('Erreur lors de la suppression');
      }
    }
  };

  const handleUploadSuccess = () => {
    setShowUpload(false);
    fetchInvoices();
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('fr-FR');
  };

  const formatAmount = (amount) => {
    if (!amount) return '0,00 €';
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    }).format(amount);
  };

  const totalAmount = invoices.reduce((sum, inv) => sum + parseFloat(inv.total_amount || 0), 0);
  const pendingCount = invoices.filter(inv => inv.status?.toLowerCase() === 'pending').length;

  return (
    <>
      <header className="header">
        <div className="logo">🗂️ EasyGED</div>
        <div className="nav-links">
          <span style={{ color: 'var(--text-secondary)', marginRight: '1rem' }}>
            {user.name || user.email}
          </span>
          <button className="btn btn-secondary" onClick={onLogout}>
            Déconnexion
          </button>
        </div>
      </header>

      <div className="container">
        <div className="dashboard-header">
          <h1>Mes Factures</h1>
          <button className="btn btn-primary" onClick={() => setShowUpload(true)}>
            + Nouvelle Facture
          </button>
        </div>

        <div className="stats-grid">
          <div className="card stat-card">
            <div className="value">{invoices.length}</div>
            <div className="label">Total Factures</div>
          </div>
          <div className="card stat-card">
            <div className="value">{pendingCount}</div>
            <div className="label">En attente</div>
          </div>
          <div className="card stat-card">
            <div className="value">{formatAmount(totalAmount)}</div>
            <div className="label">Montant Total</div>
          </div>
        </div>

        <div className="card">
          {loading ? (
            <div className="loading">
              <div className="spinner"></div>
            </div>
          ) : invoices.length === 0 ? (
            <div className="empty-state">
              <div className="icon">📄</div>
              <p>Aucune facture pour le moment</p>
              <button 
                className="btn btn-primary" 
                style={{ marginTop: '1rem' }}
                onClick={() => setShowUpload(true)}
              >
                Importer ma première facture
              </button>
            </div>
          ) : (
            <div className="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Fournisseur</th>
                    <th>N° Facture</th>
                    <th>Date</th>
                    <th>Montant HT</th>
                    <th>TVA</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {invoices.map((invoice) => (
                    <tr key={invoice.id}>
                      <td>{invoice.supplier_name || '-'}</td>
                      <td>{invoice.invoice_number || '-'}</td>
                      <td>{formatDate(invoice.invoice_date)}</td>
                      <td>{formatAmount(invoice.total_amount)}</td>
                      <td>{invoice.tva_percentage}%</td>
                      <td>
                        <span className={`status status-${invoice.status?.toLowerCase()}`}>
                          {invoice.status}
                        </span>
                      </td>
                      <td>
                        <button 
                          className="btn btn-danger"
                          style={{ padding: '0.5rem 1rem', fontSize: '0.8rem' }}
                          onClick={() => handleDelete(invoice.id)}
                        >
                          Supprimer
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {showUpload && (
        <UploadModal 
          onClose={() => setShowUpload(false)}
          onSuccess={handleUploadSuccess}
        />
      )}
    </>
  );
}

export default Dashboard;

