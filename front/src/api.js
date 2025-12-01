import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Intercepteur pour ajouter le token JWT
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Intercepteur pour gérer les erreurs 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Auth
export const login = (email, password) => 
  api.post('/login', { email, password });

export const register = (email, password, name) => 
  api.post('/register', { email, password, name });

// Invoices
export const getInvoices = () => 
  api.get('/invoices');

export const getInvoice = (id) => 
  api.get(`/invoices/show?id=${id}`);

export const deleteInvoice = (id) => 
  api.delete(`/invoices?id=${id}`);

// Upload
export const uploadDocument = (file) => {
  const formData = new FormData();
  formData.append('document', file);
  
  return api.post('/upload', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
};

export const analyzeDocument = (file) => {
  const formData = new FormData();
  formData.append('document', file);
  
  return api.post('/upload/analyze', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });
};

export default api;

