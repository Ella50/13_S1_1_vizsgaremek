import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || "http://localhost:8000/api",
  withCredentials: true,
});

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
