import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
  withCredentials: true,
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("auth_token");
    if (token) config.headers.Authorization = `Bearer ${token}`;
    config.headers.Accept = "application/json";
    return config;
  },
  (error) => Promise.reject(error)
);

export default api;


export async function fetchInvoices() {
  const { data } = await api.get("/invoices");
  return data;
}

export async function fetchInvoice(id) {
  const { data } = await api.get(`/invoices/${id}`);
  return data;
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

export async function adminFetchInvoices(params = {}) {
  const res = await api.get("/admin/invoices", { params });
  return res.data;
}

export async function adminFetchInvoice(id) {
  const res = await api.get(`/admin/invoices/${id}`);
  return res.data;
}

export async function adminGenerateInvoicesForMonth(payload) {
  // payload: { month: "2026-02" }
  const res = await api.post("/admin/invoices/generate-month", payload);
  return res.data;
}


export async function adminMarkInvoicePaid(id) {
  const res = await api.post(`/admin/invoices/${id}/paid`);
  return res.data;
}
