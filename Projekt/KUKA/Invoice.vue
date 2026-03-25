<template>
  <div class="wrap">
    <h1>Számláim</h1>

    <div v-if="loading">Betöltés...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else>
      <div v-if="invoices.length === 0">Nincs még számlád.</div>

      <div v-else class="list">
        <div class="card" v-for="inv in invoices" :key="inv.id">
          <div class="row">
            <div>
              <div class="title">{{ inv.invoiceNumber }}</div>
              <div class="muted">{{ formatMonth(inv.billingMonth) }}</div>
            </div>

            <div class="right">
              <div class="amount">{{ formatFt(inv.totalAmount) }}</div>
              <div class="status">{{ inv.invoiceStatus }}</div>
            </div>
          </div>

          <div class="actions">
            <button @click="open(inv.id)">Megtekintés</button>
            <button @click="pdf(inv)">PDF</button>
          </div>
        </div>
      </div>

      <div v-if="selected" class="modal-overlay" @click.self="close">
        <div class="modal">
          <div class="modal-header">
            <h2>{{ selected.invoiceNumber }}</h2>
            <button class="close" @click="close">×</button>
          </div>

          <div class="meta">
            <div>Hónap: <strong>{{ formatMonth(selected.billingMonth) }}</strong></div>
            <div>Összeg: <strong>{{ formatFt(selected.totalAmount) }}</strong></div>
            <div>Státusz: <strong>{{ selected.invoiceStatus }}</strong></div>
            <div>
              <button @click="pdf(selected)">Letöltés PDF-ben</button>
            </div>
          </div>

          <h3>Tételek</h3>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Dátum</th>
                  <th>Menü</th>
                  <th>Opció</th>
                  <th>Ár</th>
                  <th>Státusz</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="o in selected.orders" :key="o.id">
                  <td>{{ o.orderDate }}</td>
                  <td>{{ o.menuItemName || "—" }}</td>
                  <td>{{ o.selectedOption }}</td>
                  <td>{{ formatFt(o.amount) }}</td>
                  <td>{{ o.orderStatus }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { fetchInvoices, fetchInvoice, downloadInvoicePdf } from "@/services/invoiceApi";

const loading = ref(false);
const error = ref("");
const invoices = ref([]);

const selected = ref(null);

function formatFt(n) {
  const x = Number(n || 0);
  return x.toLocaleString("hu-HU") + " Ft";
}

function formatMonth(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
}

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const data = await fetchInvoices();
    invoices.value = data.invoices || data || [];
  } catch (e) {
    error.value = e?.response?.data?.message || "Hiba a számlák betöltésekor";
  } finally {
    loading.value = false;
  }
}

async function open(id) {
  try {
    const data = await fetchInvoice(id);
    selected.value = data.invoice || data;
  } catch (e) {
    error.value = e?.response?.data?.message || "Hiba a számla betöltésekor";
  }
}

function close() {
  selected.value = null;
}

async function pdf(inv) {
  const filename = `${inv.invoiceNumber || "szamla"}.pdf`;
  await downloadInvoicePdf(inv.id, filename);
}

async function downloadInvoice(id) {
  const response = await axios.get(`/api/invoices/${id}/pdf`, {
    responseType: 'blob'
  })

  const url = window.URL.createObjectURL(new Blob([response.data]))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `szamla-${id}.pdf`)
  document.body.appendChild(link)
  link.click()
}

load();
</script>

<style scoped>
.wrap 
{ max-width: 1100px; margin: 0 auto; padding: 24px; }
.error { color: #b00020; background: #ffe7ea; padding: 12px; border-radius: 8px; }
.list { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px; }
.card { border: 1px solid #e6e6e6; border-radius: 12px; padding: 14px; background: #fff; }
.row { display: flex; justify-content: space-between; gap: 12px; }
.title { font-weight: 700; }
.muted { color: #666; font-size: 13px; margin-top: 4px; }
.right { text-align: right; }
.amount { font-weight: 700; }
.status { font-size: 13px; color: #444; margin-top: 4px; }
.actions { display: flex; gap: 8px; margin-top: 12px; }
button { padding: 8px 10px; border-radius: 8px; border: 1px solid #ddd; background: #fff; cursor: pointer; }
button:hover { background: #f5f5f5; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); display: flex; align-items: center; justify-content: center; padding: 16px; }
.modal { width: min(1000px, 100%); background: #fff; border-radius: 12px; padding: 16px; max-height: 90vh; overflow: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; }
.close { font-size: 22px; width: 40px; height: 40px; border-radius: 10px; }
.meta { display: flex; gap: 14px; flex-wrap: wrap; margin: 10px 0 16px; align-items: center; }
.table-wrap { overflow: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { border-bottom: 1px solid #eee; padding: 10px; text-align: left; white-space: nowrap; }
th { background: #fafafa; }
</style>
