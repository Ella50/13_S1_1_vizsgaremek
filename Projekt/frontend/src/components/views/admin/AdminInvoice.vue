<template>
  <div class="wrap">
    <div class="head">
      <h1>Számlák (Admin)</h1>

      <div class="toolbar">
        <label class="field">
          <span>Hónap</span>
          <input type="month" v-model="billingMonth" />
        </label>

        <button class="primary" :disabled="generating || !billingMonth" @click="generate">
          {{ generating ? "Generálás..." : "Számlák generálása" }}
        </button>
      </div>
    </div>

    <div v-if="loading">Betöltés...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else>
      <div class="summary" v-if="rows.length">
        <div><strong>{{ rows.length }}</strong> számla</div>
        <div><strong>{{ formatFt(totalSum) }}</strong> összesen</div>
      </div>

      <div v-if="rows.length === 0" class="empty">Nincs találat erre a hónapra.</div>

      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Felhasználó</th>
              <th>Számlaszám</th>
              <th>Hónap</th>
              <th>Összeg</th>
              <th>Státusz</th>
              <th>Határidő</th>
              <th>Műveletek</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="inv in rows" :key="inv.id">
              <td>
                <div class="u">
                  <div class="uname">{{ inv.user?.name || inv.userName || "—" }}</div>
                  <div class="muted">{{ inv.user?.email || inv.userEmail || "" }}</div>
                </div>
              </td>
              <td>{{ inv.invoiceNumber }}</td>
              <td>{{ formatMonth(inv.billingMonth) }}</td>
              <td><strong>{{ formatFt(inv.totalAmount) }}</strong></td>
              <td>
                <span class="badge" :data-status="inv.invoiceStatus">{{ inv.invoiceStatus }}</span>
              </td>
              <td>{{ formatDate(inv.dueDate) }}</td>
              <td class="actions">
                <button @click="open(inv.id)">Megtekintés</button>
                <button @click="pdf(inv)">PDF</button>
                <button
                  class="ok"
                  v-if="inv.invoiceStatus !== 'Fizetve'"
                  @click="markPaid(inv)"
                  :disabled="payingId === inv.id"
                >
                  {{ payingId === inv.id ? "Mentés..." : "Fizetve" }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="selected" class="modal-overlay" @click.self="close">
        <div class="modal">
          <div class="modal-header">
            <h2>{{ selected.invoiceNumber }}</h2>
            <button class="close" @click="close">×</button>
          </div>

          <div class="meta">
            <div>Felhasználó: <strong>{{ selected.user?.name || selected.userName || "—" }}</strong></div>
            <div>Hónap: <strong>{{ formatMonth(selected.billingMonth) }}</strong></div>
            <div>Összeg: <strong>{{ formatFt(selected.totalAmount) }}</strong></div>
            <div>Státusz: <strong>{{ selected.invoiceStatus }}</strong></div>
            <div>Határidő: <strong>{{ formatDate(selected.dueDate) }}</strong></div>
            <div class="meta-actions">
              <button @click="pdf(selected)">Letöltés PDF-ben</button>
              <button
                class="ok"
                v-if="selected.invoiceStatus !== 'Fizetve'"
                @click="markPaid(selected)"
                :disabled="payingId === selected.id"
              >
                {{ payingId === selected.id ? "Mentés..." : "Fizetve" }}
              </button>
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
                <tr v-for="o in (selected.orders || [])" :key="o.id">
                  <td>{{ formatDate(o.orderDate) }}</td>
                  <td>{{ o.menuItemName || o.menuItem?.name || "—" }}</td>
                  <td>{{ o.selectedOption }}</td>
                  <td>{{ formatFt(o.amount) }}</td>
                  <td>{{ o.orderStatus }}</td>
                </tr>
                <tr v-if="!selected.orders || selected.orders.length === 0">
                  <td colspan="5" class="muted">Nincs tétel.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="modal-footer">
            <button @click="close">Bezárás</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import {
  adminFetchInvoices,
  adminFetchInvoice,
  adminGenerateInvoicesForMonth,
  adminMarkInvoicePaid,
  downloadInvoicePdf
} from "@/services/invoiceApi";

const loading = ref(false);
const generating = ref(false);
const error = ref("");

const rows = ref([]);
const selected = ref(null);

const payingId = ref(null);

const billingMonth = ref(""); // YYYY-MM (input type="month")

function formatFt(n) {
  const x = Number(n || 0);
  return x.toLocaleString("hu-HU") + " Ft";
}

function formatMonth(dateStr) {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
}

function formatDate(dateStr) {
  if (!dateStr) return "—";
  const d = new Date(dateStr);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function monthToFirstDay(yyyyMm) {
  if (!yyyyMm) return null;
  const [y, m] = yyyyMm.split("-");
  return `${y}-${m}-01`;
}

const totalSum = computed(() =>
  rows.value.reduce((sum, i) => sum + Number(i.totalAmount || 0), 0)
);

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const month = monthToFirstDay(billingMonth.value);
    const res = await adminFetchInvoices({ month: selectedMonth.value })
    rows.value = res.data ?? res.invoices ?? []
  } catch (e) {
    error.value = e?.response?.data?.message || "Hiba a számlák betöltésekor";
  } finally {
    loading.value = false;
  }
}

async function generate() {
  generating.value = true;
  error.value = "";
  try {
    const month = monthToFirstDay(billingMonth.value);
    await adminGenerateInvoicesForMonth({ billingMonth: month });
    await load();
  } catch (e) {
    error.value = e?.response?.data?.message || "Hiba a számlák generálásakor";
  } finally {
    generating.value = false;
  }
}

async function open(id) {
  error.value = "";
  try {
    const data = await adminFetchInvoice(id);
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

async function markPaid(inv) {
  payingId.value = inv.id;
  error.value = "";
  try {
    await adminMarkInvoicePaid(inv.id);
    if (selected.value?.id === inv.id) {
      const data = await adminFetchInvoice(inv.id);
      selected.value = data.invoice || data;
    }
    await load();
  } catch (e) {
    error.value = e?.response?.data?.message || "Hiba a fizetettre állításkor";
  } finally {
    payingId.value = null;
  }
}

function setDefaultMonth() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, "0");
  billingMonth.value = `${y}-${m}`;
}

setDefaultMonth();
load();

watch(billingMonth, () => {
  load();
});
</script>

<style scoped>
.wrap { max-width: 1200px; margin: 0 auto; padding: 24px; }
.error { color: #b00020; background: #ffe7ea; padding: 12px; border-radius: 10px; margin: 12px 0; }

.head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; }
.toolbar { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
.field { display: grid; gap: 6px; font-size: 13px; color: #444; }
.field input { border: 1px solid #ddd; border-radius: 10px; padding: 8px 10px; background: #fff; }

.summary { display: flex; gap: 16px; margin: 10px 0 14px; color: #333; }
.empty { padding: 14px; border: 1px dashed #ddd; border-radius: 12px; color: #666; }

.table-wrap { overflow: auto; border: 1px solid #eee; border-radius: 12px; background: #fff; }
table { width: 100%; border-collapse: collapse; }
th, td { border-bottom: 1px solid #f0f0f0; padding: 10px; text-align: left; white-space: nowrap; }
th { background: #fafafa; font-weight: 700; font-size: 13px; color: #333; }

.u .uname { font-weight: 700; }
.muted { color: #666; font-size: 12px; margin-top: 2px; }

.actions { display: flex; gap: 8px; }
button { padding: 8px 10px; border-radius: 10px; border: 1px solid #ddd; background: #fff; cursor: pointer; }
button:hover { background: #f5f5f5; }
button.primary { border-color: #cfe8d8; background: #e9f7ef; }
button.primary:hover { background: #dcf3e7; }
button.ok { border-color: #d6eaff; background: #edf6ff; }
button.ok:hover { background: #e2f0ff; }
button:disabled { opacity: .6; cursor: not-allowed; }

.badge { display: inline-flex; padding: 4px 10px; border-radius: 999px; border: 1px solid #ddd; font-size: 12px; }
.badge[data-status="Fizetve"] { border-color: #bfe7c8; background: #e9f7ef; }
.badge[data-status="Generálva"] { border-color: #e6e6e6; background: #f7f7f7; }
.badge[data-status="Függőben lévő"] { border-color: #ffe0b2; background: #fff3e0; }
.badge[data-status="Lejárt"] { border-color: #ffcdd2; background: #ffebee; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); display: flex; align-items: center; justify-content: center; padding: 16px; z-index: 50; }
.modal { width: min(1000px, 100%); background: #fff; border-radius: 14px; padding: 16px; max-height: 90vh; overflow: auto; }
.modal-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.close { font-size: 22px; width: 40px; height: 40px; border-radius: 12px; }
.meta { display: flex; gap: 14px; flex-wrap: wrap; margin: 10px 0 16px; align-items: center; }
.meta-actions { display: flex; gap: 8px; }
.modal-footer { display: flex; justify-content: flex-end; padding-top: 12px; }
</style>
