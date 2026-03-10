import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
  withCredentials: true,
});

// FONTOS: token az interceptorból (mert ez egy külön axios instance)
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default api;


export async function fetchInvoices() {
  const { data } = await api.get("/invoices");
  return data;
}

export async function adminFetchInvoices(params) {
  try {
    const response = await api.get('/admin/invoices', { params });
    console.log("TELJES RESPONSE:", response);
    console.log("RESPONSE.DATA:", response.data);
    
    // Ha string, távolítsuk el a BOM karaktert és parse-oljuk
    if (typeof response.data === 'string') {
      // BOM karakter eltávolítása
      const cleanJson = response.data.replace(/^\uFEFF/, '');
      return JSON.parse(cleanJson);
    }
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
  const res = await api.post(
    "/admin/invoices/generate-month",
    { month }
  );
  return res.data;
}


export async function adminMarkInvoicePaid(id) {
  const res = await api.post(`/admin/invoices/${id}/paid`);
  return res.data;
}

export async function downloadInvoicePdf(id, filename = "szamla.pdf") {
  const res = await api.get(`/invoices/${id}/pdf`, { responseType: "blob" });

  const blob = new Blob([res.data], { type: "application/pdf" });
  const url = window.URL.createObjectURL(blob);

  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();

  window.URL.revokeObjectURL(url);
}
