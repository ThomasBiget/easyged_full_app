import React, { useState, useRef } from 'react';
import { uploadDocument } from '../api';

function UploadModal({ onClose, onSuccess }) {
  const [file, setFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [result, setResult] = useState(null);
  const [dragover, setDragover] = useState(false);
  const fileInputRef = useRef(null);

  const handleDrop = (e) => {
    e.preventDefault();
    setDragover(false);
    const droppedFile = e.dataTransfer.files[0];
    if (droppedFile) {
      setFile(droppedFile);
      setError('');
    }
  };

  const handleFileSelect = (e) => {
    const selectedFile = e.target.files[0];
    if (selectedFile) {
      setFile(selectedFile);
      setError('');
    }
  };

  const handleUpload = async () => {
    if (!file) {
      setError('Veuillez sélectionner un fichier');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await uploadDocument(file);
      setResult(response.data);
    } catch (err) {
      setError(err.response?.data?.error || 'Erreur lors de l\'upload');
    } finally {
      setLoading(false);
    }
  };

  if (result) {
    return (
      <div className="modal-overlay" onClick={onClose}>
        <div className="modal" onClick={(e) => e.stopPropagation()}>
          <h2>✅ Facture importée !</h2>
          
          <div className="alert alert-success">
            La facture a été analysée et créée avec succès.
          </div>

          <div style={{ marginBottom: '1.5rem' }}>
            <h3 style={{ marginBottom: '1rem', fontSize: '1rem' }}>Données extraites :</h3>
            <div className="card" style={{ background: 'var(--bg-secondary)' }}>
              <p><strong>Fournisseur :</strong> {result.extracted_data?.supplier_name || '-'}</p>
              <p><strong>N° Facture :</strong> {result.extracted_data?.invoice_number || '-'}</p>
              <p><strong>Date :</strong> {result.extracted_data?.invoice_date || '-'}</p>
              <p><strong>Montant :</strong> {result.extracted_data?.total_amount} €</p>
              <p><strong>TVA :</strong> {result.extracted_data?.tva_percentage}%</p>
              {result.extracted_data?.line_items?.length > 0 && (
                <>
                  <p style={{ marginTop: '1rem' }}><strong>Lignes :</strong></p>
                  <ul style={{ marginLeft: '1rem', marginTop: '0.5rem' }}>
                    {result.extracted_data.line_items.map((item, i) => (
                      <li key={i} style={{ color: 'var(--text-secondary)', fontSize: '0.9rem' }}>
                        {item.description} - {item.quantity} x {item.unit_price}€
                      </li>
                    ))}
                  </ul>
                </>
              )}
            </div>
          </div>

          <button className="btn btn-primary" style={{ width: '100%' }} onClick={onSuccess}>
            Fermer
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={(e) => e.stopPropagation()}>
        <h2>📤 Importer une facture</h2>
        <p style={{ color: 'var(--text-secondary)', marginBottom: '1.5rem' }}>
          Uploadez une image ou un PDF de facture. L'OCR extraira automatiquement les informations.
        </p>

        {error && <div className="alert alert-error">{error}</div>}

        <div
          className={`upload-zone ${dragover ? 'dragover' : ''}`}
          onDragOver={(e) => { e.preventDefault(); setDragover(true); }}
          onDragLeave={() => setDragover(false)}
          onDrop={handleDrop}
          onClick={() => fileInputRef.current?.click()}
        >
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*,.pdf"
            onChange={handleFileSelect}
            style={{ display: 'none' }}
          />
          
          <div className="upload-icon">📄</div>
          
          {file ? (
            <p>
              <strong>{file.name}</strong>
              <br />
              <span style={{ fontSize: '0.85rem' }}>
                {(file.size / 1024 / 1024).toFixed(2)} MB
              </span>
            </p>
          ) : (
            <p>
              Glissez-déposez votre fichier ici
              <br />
              ou <span className="highlight">cliquez pour parcourir</span>
            </p>
          )}
        </div>

        <div style={{ display: 'flex', gap: '1rem', marginTop: '1.5rem' }}>
          <button 
            className="btn btn-secondary" 
            style={{ flex: 1 }}
            onClick={onClose}
            disabled={loading}
          >
            Annuler
          </button>
          <button 
            className="btn btn-primary" 
            style={{ flex: 1 }}
            onClick={handleUpload}
            disabled={!file || loading}
          >
            {loading ? 'Analyse en cours...' : 'Analyser & Importer'}
          </button>
        </div>

        {loading && (
          <p style={{ 
            textAlign: 'center', 
            marginTop: '1rem', 
            color: 'var(--text-secondary)',
            fontSize: '0.9rem' 
          }}>
            🤖 Claude analyse votre document...
          </p>
        )}
      </div>
    </div>
  );
}

export default UploadModal;

