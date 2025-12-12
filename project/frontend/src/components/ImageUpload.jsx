import { useState } from 'react';
import { uploadImage } from '../services/api';

function ImageUpload() {
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [uploadedImage, setUploadedImage] = useState(null);

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      setSelectedFile(file);
      setMessage('');
      setError('');
      
      const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
      setMessage(`Fichier sélectionné : ${file.name} (${sizeMB} MB)`);
    }
  };

  const handleUpload = async () => {
    if (!selectedFile) {
      setError('Veuillez sélectionner une image');
      return;
    }

    setUploading(true);
    setError('');
    setMessage('');

    const formData = new FormData();
    formData.append('image', selectedFile);

    try {
      const response = await uploadImage(formData);
      setMessage(`✅ Image uploadée avec succès ! (${(response.data.size / 1024).toFixed(0)} KB)`);
      setUploadedImage(response.data);
      setSelectedFile(null);
    } catch (err) {
      if (err.response?.status === 413) {
        setError('❌ Erreur 413 : Image trop volumineuse ! La limite est de 2MB.');
      } else {
        setError(`❌ Erreur lors de l'upload : ${err.message}`);
      }
      console.error('Upload error:', err);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="card">
      <h3>📸 Upload d'Image</h3>
      <p style={{ color: '#7f8c8d', fontSize: '0.9em', marginBottom: '1rem' }}>
        Testez l'upload d'images (limite : 2MB)
      </p>

      <div style={{ marginBottom: '1rem' }}>
        <input
          type="file"
          accept="image/*"
          onChange={handleFileSelect}
          style={{ marginBottom: '0.5rem' }}
        />
      </div>

      {message && !error && (
        <div style={{ 
          padding: '0.8rem', 
          backgroundColor: '#d4edda', 
          color: '#155724',
          borderRadius: '4px',
          marginBottom: '1rem',
          fontSize: '0.9em'
        }}>
          {message}
        </div>
      )}

      {error && (
        <div className="error" style={{ marginBottom: '1rem', fontSize: '0.9em' }}>
          {error}
        </div>
      )}

      {uploadedImage && (
        <div style={{ 
          padding: '0.8rem', 
          backgroundColor: '#f8f9fa',
          borderRadius: '4px',
          marginBottom: '1rem',
          fontSize: '0.85em'
        }}>
          <strong>✅ Image optimisée avec succès !</strong>
          <div>Taille originale: {(uploadedImage.original_size / 1024).toFixed(2)} KB</div>
          <div>Taille optimisée: {(uploadedImage.optimized_size / 1024).toFixed(2)} KB</div>
          <div>Réduction: <strong>{uploadedImage.reduction_percent}%</strong></div>
          
          {uploadedImage.images && (
            <div style={{ marginTop: '0.5rem' }}>
              <strong>Versions générées:</strong>
              <ul style={{ marginTop: '0.3rem', marginBottom: 0, paddingLeft: '1.5rem' }}>
                <li>Large: {uploadedImage.images.large.width}x{uploadedImage.images.large.height} ({(uploadedImage.images.large.size / 1024).toFixed(1)} KB)</li>
                <li>Medium: {uploadedImage.images.medium.width}x{uploadedImage.images.medium.height} ({(uploadedImage.images.medium.size / 1024).toFixed(1)} KB)</li>
                <li>Thumbnail: {uploadedImage.images.thumbnail.width}x{uploadedImage.images.thumbnail.height} ({(uploadedImage.images.thumbnail.size / 1024).toFixed(1)} KB)</li>
                <li>WebP: {(uploadedImage.images.webp.size / 1024).toFixed(1)} KB</li>
              </ul>
              
              {/* PERF-002: Display optimized image with lazy loading, WebP support, and srcset */}
              <div style={{ marginTop: '1rem' }}>
                <picture>
                  <source 
                    srcSet={`/storage/images/${uploadedImage.images.webp.filename}`} 
                    type="image/webp" 
                  />
                  <img
                    src={`/storage/images/${uploadedImage.images.large.filename}`}
                    srcSet={`
                      /storage/images/${uploadedImage.images.thumbnail.filename} 300w,
                      /storage/images/${uploadedImage.images.medium.filename} 600w,
                      /storage/images/${uploadedImage.images.large.filename} 1200w
                    `}
                    sizes="(max-width: 600px) 300px, (max-width: 1200px) 600px, 1200px"
                    width={uploadedImage.images.large.width}
                    height={uploadedImage.images.large.height}
                    loading="lazy"
                    alt="Uploaded image"
                    style={{ maxWidth: '100%', height: 'auto', borderRadius: '4px', marginTop: '0.5rem' }}
                  />
                </picture>
              </div>
            </div>
          )}
        </div>
      )}

      <button 
        onClick={handleUpload} 
        disabled={!selectedFile || uploading}
        style={{ marginRight: '0.5rem' }}
      >
        {uploading ? '⏳ Upload en cours...' : '📤 Uploader'}
      </button>

      {selectedFile && (
        <button 
          onClick={() => {
            setSelectedFile(null);
            setMessage('');
            setError('');
          }}
          style={{ backgroundColor: '#95a5a6' }}
        >
          Annuler
        </button>
      )}

      <div style={{ 
        marginTop: '1.5rem', 
        padding: '1rem', 
        backgroundColor: '#fff3cd',
        borderRadius: '4px',
        fontSize: '0.85em'
      }}>
        <strong>💡 Pour tester le BUG-003 :</strong>
        <ol style={{ marginTop: '0.5rem', marginBottom: 0, paddingLeft: '1.5rem' }}>
          <li>Essayez d'uploader une image &lt; 2MB → ✅ Devrait fonctionner</li>
          <li>Essayez d'uploader une image &gt; 2MB → ❌ Devrait échouer avec erreur 413</li>
        </ol>
      </div>
    </div>
  );
}

export default ImageUpload;

