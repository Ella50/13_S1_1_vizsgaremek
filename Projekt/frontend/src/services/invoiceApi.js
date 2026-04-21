import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
  withCredentials: true,
});


api.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

// interceptor a BOM karakter kezelésére
api.interceptors.response.use(
  (response) => {

    if (typeof response.data === 'string') {
      try {
        const cleanJson = response.data.replace(/^\uFEFF/, '');
        response.data = JSON.parse(cleanJson);
      } catch (e) {
        console.error('JSON parse error in interceptor:', e);
      }
    }
    return response;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export default api;

export async function fetchInvoices() {
  const { data } = await api.get("/invoices");
  return data;
}

export async function adminFetchInvoices(params) {
  try {
    const { month, search, page, per_page, status } = params;
    const queryParams = new URLSearchParams();
    if (month) queryParams.append('month', month);
    if (search) queryParams.append('search', search);
    if (page) queryParams.append('page', page);
    if (per_page) queryParams.append('per_page', per_page);
    if (status) queryParams.append('status', status);
    
    const response = await api.get(`/admin/invoices?${queryParams.toString()}`);
    return response.data;
  } catch (error) {
    console.error("Hiba az adminFetchInvoices-ben:", error);
    throw error;
  }
}

export async function adminFetchInvoice(id) {
  const res = await api.get(`/admin/invoices/${id}`);
  return res.data;
}

export async function adminGenerateInvoicesForMonth(month) {
  const res = await api.post("/admin/invoices/generate-month", { month });
  return res.data;
}

export async function adminMarkInvoicePaid(invoiceId) {
  try {
    const response = await api.put(`/admin/invoices/${invoiceId}/paid`);
    return response.data;
  } catch (error) {
    console.error('API hiba a fizetettre állításkor:', error);
    throw error;
  }
}

export async function adminMarkInvoiceUnpaid(invoiceId) {
  try {
    const response = await api.put(`/admin/invoices/${invoiceId}/unpaid`);
    return response.data;
  } catch (error) {
    console.error('API hiba a visszaállításkor:', error);
    throw error;
  }
}