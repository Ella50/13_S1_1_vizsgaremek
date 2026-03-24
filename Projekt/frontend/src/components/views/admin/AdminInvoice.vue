<template>
  <div class="wrap">
    <div class="head">
      <h1>Számlák (Admin)</h1>

      <div class="toolbar">
        <label class="field">
          <span>Hónap</span>
          <input type="month" v-model="billingMonth" />
        </label>

        <button 
          class="primary" 
          :disabled="generating || !billingMonth" 
          @click="generate"
        >
          {{ generating ? "Generálás..." : "Számlák generálása" }}
        </button>
      </div>
    </div>

    <div v-if="loading">
      Betöltés...
    </div>
    
    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else>
      <div class="summary" v-if="rows.length">
        <div>
          <strong>{{ rows.length }}</strong> számla
        </div>
        <div>
          <strong>{{ formatFt(totalSum) }}</strong> összesen
        </div>
      </div>

      <div v-if="rows.length === 0" class="empty">
        Nincs találat erre a hónapra.
      </div>

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
                  <div class="uname">
                    {{ inv.user?.firstName + " " + inv.user?.lastName || inv.firstName || "—" }}
                  </div>
                  <div class="muted">
                    {{ inv.user?.email || inv.userEmail || "" }}
                  </div>
                </div>
              </td>
              <td>{{ inv.invoiceNumber }}</td>
              <td>{{ formatMonth(inv.billingMonth) }}</td>
              <td>
                <strong>{{ formatFt(Math.round(inv.totalAmount * 1.27)) }}</strong>
              </td>
              <td>
                <span class="badge" :data-status="inv.invoiceStatus">
                  {{ inv.invoiceStatus }}
                </span>
              </td>
              <td>{{ formatDate(inv.dueDate) }}</td>
              <td class="actions">
                <button 
                  class="view-btn" 
                  @click="open(inv.id)"
                >
                  Megtekintés
                </button>
                
                <button 
                  class="pdf-btn" 
                  @click="pdf(inv)"
                >
                  PDF
                </button>
                
                <button
                  class="paid-btn"
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

      <!-- MODAL -->
      <div v-if="selected" class="modal-overlay" @click.self="close">
        <div class="modal">
          <div class="modal-header">
            <h2>{{ selected.invoiceNumber }}</h2>
            <button class="close-btn" @click="close">
              ×
            </button>
          </div>

          <div class="meta">
            <div>
              Felhasználó: 
              <strong>{{ selected.user?.firstName + " " + selected.user?.lastName || selected.userName || "—" }}</strong>
            </div>
            <div>
              Hónap: 
              <strong>{{ formatMonth(selected.billingMonth) }}</strong>
            </div>
            <div>
              Összeg: 
              <strong>{{ formatFt(selected.totalAmount) }}</strong>
            </div>
            <div>
              Státusz: 
              <strong>{{ selected.invoiceStatus }}</strong>
            </div>
            <div>
              Határidő: 
              <strong>{{ formatDate(selected.dueDate) }}</strong>
            </div>
            
            <div class="meta-actions">
              <button 
                class="pdf-btn" 
                @click="pdf(selected)"
              >
                Letöltés PDF-ben
              </button>
              
              <button
                class="paid-btn"
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
                  <th>Opció</th>
                  <th>Ár</th>
                  <th>Státusz</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="o in (selected.orders || [])" :key="o.id">
                  <td>{{ formatDate(o.orderDate) }}</td>
                  <td>{{ o.selectedOption }}</td>
                  <td>{{ formatFt(o.amount) }}</td>
                  <td>{{ o.orderStatus }}</td>
                </tr>
                
                <tr v-if="!selected.orders || selected.orders.length === 0">
                  <td colspan="5" class="muted">
                    Nincs tétel.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="modal-footer">
            <button 
              class="close-btn" 
              @click="close"
            >
              Bezárás
            </button>
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

import api from "@/services/api";

const loading = ref(false);
const generating = ref(false);
const error = ref("");

const rows = ref([]);
const selected = ref(null);
const payingId = ref(null);

const billingMonth = ref(""); // YYYY-MM

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

const totalSum = computed(() => {
  const arr = Array.isArray(rows.value) ? rows.value : [];

  return arr.reduce((sum, i) => {
    const net = Number(i.totalAmount || 0);
    const gross = net * 1.27; // Nettó → Bruttó
    return sum + gross;
  }, 0);
});

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const response = await adminFetchInvoices({
      month: billingMonth.value
    });
    
    console.log("Teljes response:", response);
    
    let invoiceData = [];
    
    // Ha string, távolítsuk el a BOM karaktert és parse-oljuk
    if (typeof response === 'string') {
      try {
        // BOM karakter eltávolítása
        const cleanJson = response.replace(/^\uFEFF/, '');
        const parsed = JSON.parse(cleanJson);
        console.log("Parse-olt adat:", parsed);
        
        if (parsed?.invoices?.data) {
          invoiceData = parsed.invoices.data;
        }
      } catch (e) {
        console.error("Hiba a JSON parse során:", e);
      }
    }
    // Ha már objektum
    else if (response && typeof response === 'object') {
      if (response.invoices?.data) {
        invoiceData = response.invoices.data;
      }
    }
    
    rows.value = invoiceData;
    console.log("Beállított rows:", rows.value);
    console.log("rows hossza:", rows.value.length);
    
  } catch (e) {
    error.value = e?.response?.data?.message || "Hiba a számlák betöltésekor";
    console.error("Hiba:", e);
  } finally {
    loading.value = false;
  }
}

async function generate() {
  if (!billingMonth.value) return;

  try {
    await adminGenerateInvoicesForMonth(billingMonth.value);
    await load();
  } catch (e) {
    console.log(e.response?.data);
  }
}

async function open(id) {
  console.log('Opening invoice with ID:', id);
  error.value = "";
  
  try {
    const response = await adminFetchInvoice(id);
    console.log('Raw API response:', response);
    
    let invoiceData = null;
    
    // Ha string, távolítsuk el a BOM karaktert és parse-oljuk
    if (typeof response === 'string') {
      try {
        // BOM karakter eltávolítása
        const cleanJson = response.replace(/^\uFEFF/, '');
        const parsed = JSON.parse(cleanJson);
        console.log('Parse-olt adat:', parsed);
        
        if (parsed?.invoice) {
          invoiceData = parsed.invoice;
        } else if (parsed?.data?.invoice) {
          invoiceData = parsed.data.invoice;
        }
      } catch (e) {
        console.error('Hiba a JSON parse során:', e);
      }
    }
    // Ha már objektum
    else if (response && typeof response === 'object') {
      if (response.invoice) {
        invoiceData = response.invoice;
      } else if (response.data?.invoice) {
        invoiceData = response.data.invoice;
      } else if (response.id) { // Ha maga a response a számla
        invoiceData = response;
      }
    }
    
    if (!invoiceData) {
      console.error('Nem sikerült kinyerni a számla adatokat:', response);
      error.value = "Hibás adatformátum";
      return;
    }
    
    console.log('Beállított invoice adat:', invoiceData);
    selected.value = invoiceData;
    
  } catch (e) {
    console.error('Hiba a számla betöltésekor:', e);
    error.value = e?.response?.data?.message || e?.message || "Hiba a számla betöltésekor";
  }
}

function close() {
  selected.value = null;
}

async function pdf(inv) {
  try {
    console.log('PDF letöltés indítása, ID:', inv.id);
    const filename = `${inv.invoiceNumber || 'szamla'}.pdf`;
    
    // Token lekérése - próbáljuk ki mindkét lehetséges nevet
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
    
    if (!token) {
      console.error('Nincs token a localStorage-ban!');
      console.log('Elérhető localStorage kulcsok:', Object.keys(localStorage));
      error.value = 'Nincs bejelentkezve - hiányzik a token';
      return;
    }
    
    console.log('Token megtalálva, hossza:', token.length);
    
    // Közvetlen fetch hívás
    const response = await fetch(`http://localhost:8000/api/admin/invoices/${inv.id}/pdf`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf'
      }
    });
    
    console.log('Válasz státusz:', response.status);
    console.log('Válasz headerek:', Object.fromEntries(response.headers.entries()));
    
    if (response.status === 401) {
      error.value = 'Nincs jogosultság (401) - jelentkezz be újra';
      return;
    }
    
    if (!response.ok) {
      const errorText = await response.text();
      console.error('Hiba válasz:', errorText);
      error.value = `Hiba: ${response.status} - ${errorText}`;
      return;
    }
    
    // Ellenőrizzük a content-type-ot
    const contentType = response.headers.get('content-type');
    console.log('Content-Type:', contentType);
    
    if (contentType && contentType.includes('application/json')) {
      // JSON hibát kaptunk
      const errorData = await response.json();
      console.error('Szerver hiba:', errorData);
      error.value = errorData.message || 'Hiba a PDF letöltésekor';
      return;
    }
    
    // Blob létrehozása és letöltés
    const blob = await response.blob();
    console.log('Blob mérete:', blob.size, 'típusa:', blob.type);
    
    if (blob.size < 100) {
      // Lehet, hogy hibaüzenet
      const text = await blob.text();
      console.warn('Kis méretű blob tartalma:', text);
      if (text.includes('error') || text.includes('hiba')) {
        error.value = text;
        return;
      }
    }
    
    // Letöltés
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    console.log('PDF letöltés sikeres');
    
  } catch (e) {
    console.error('PDF letöltési hiba részletesen:', e);
    error.value = e.message || "Hiba a PDF letöltésekor";
  }
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

watch(rows, (newRows) => {
  console.log("rows frissült, hossza:", newRows?.length);
  console.log("Első elem:", newRows[0]);
}, { immediate: true, deep: true });

watch(selected, (newVal) => {
  console.log('selected változott:', newVal);
  if (newVal) {
    console.log('Modal megnyitva, ID:', newVal.id);
  }
}, { deep: true, immediate: true });
</script>

<style scoped>
.wrap {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
  position: relative;
  z-index: 1;
}

.error {
  color: #b00020;
  background: #ffe7ea;
  padding: 12px;
  border-radius: 10px;
  margin: 12px 0;
}

.head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  flex-wrap: wrap;
}

.toolbar {
  display: flex;
  gap: 10px;
  align-items: end;
  flex-wrap: wrap;
}

.field {
  display: grid;
  gap: 6px;
  font-size: 13px;
  color: #444;
}

.field input {
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 8px 10px;
  background: #fff;
}

.summary {
  display: flex;
  gap: 16px;
  margin: 10px 0 14px;
  color: #333;
}

.empty {
  padding: 14px;
  border: 1px dashed #ddd;
  border-radius: 12px;
  color: #666;
}

.table-wrap {
  overflow: auto;
  border: 1px solid #eee;
  border-radius: 12px;
  background: #fff;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  border-bottom: 1px solid #f0f0f0;
  padding: 10px;
  text-align: left;
  white-space: nowrap;
}

th {
  background: #fafafa;
  font-weight: 700;
  font-size: 13px;
  color: #333;
}

.u .uname {
  font-weight: 700;
}

.muted {
  color: #666;
  font-size: 12px;
  margin-top: 2px;
}

.actions {
  display: flex;
  gap: 8px;
}

button {
  padding: 8px 10px;
  border-radius: 10px;
  border: 1px solid #ddd;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
}

button:hover {
  background: #f5f5f5;
}

button.primary {
  border-color: #cfe8d8;
  background: #e9f7ef;
}

button.primary:hover {
  background: #dcf3e7;
}

button:disabled {
  opacity: .6;
  cursor: not-allowed;
}

/* Speciális gomb színek */
.view-btn {
  background-color: aqua !important;
  border-color: #00cccc !important;
  color: black !important;
}

.view-btn:hover {
  background-color: #00ffff !important;
}

.pdf-btn {
  background-color: blue !important;
  border-color: #0000cc !important;
  color: white !important;
}

.pdf-btn:hover {
  background-color: #0000ff !important;
}

.paid-btn {
  background-color: lightgreen !important;
  border-color: #90ee90 !important;
  color: black !important;
}

.paid-btn:hover {
  background-color: #98fb98 !important;
}

.close-btn {
  background-color: red !important;
  border-color: #cc0000 !important;
  color: white !important;
  font-weight: bold !important;
}

.close-btn:hover {
  background-color: #ff3333 !important;
}

/* Modal bezáró gomb speciális */
.modal-header .close-btn {
  font-size: 22px;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Badge stílusok */
.badge {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 999px;
  border: 1px solid #ddd;
  font-size: 12px;
}

.badge[data-status="Fizetve"] {
  border-color: #bfe7c8;
  background: #e9f7ef;
}

.badge[data-status="Generálva"] {
  border-color: #e6e6e6;
  background: #f7f7f7;
}

.badge[data-status="Függőben lévő"] {
  border-color: #ffe0b2;
  background: #fff3e0;
}

.badge[data-status="Lejárt"] {
  border-color: #ffcdd2;
  background: #ffebee;
}

/* MODAL STÍLUSOK */
.modal-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  background: rgba(0, 0, 0, 0.6) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 16px !important;
  z-index: 999999 !important;
}

.modal {
  background: white !important;
  width: 90% !important;
  max-width: 1000px !important;
  max-height: 90vh !important;
  overflow: auto !important;
  padding: 20px !important;
  border-radius: 14px !important;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
  position: relative !important;
  display: block !important;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.meta {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  margin: 10px 0 16px;
  align-items: center;
  background: #f8f9fa;
  padding: 12px;
  border-radius: 8px;
}

.meta-actions {
  display: flex;
  gap: 8px;
  margin-left: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 16px;
  margin-top: 16px;
  border-top: 1px solid #eee;
}

.modal h3 {
  margin: 20px 0 10px;
  font-size: 1.2rem;
}

/* Reszponzív stílusok */
@media (max-width: 768px) {
  .wrap {
    padding: 16px;
  }
  
  .head {
    flex-direction: column;
    align-items: stretch;
  }
  
  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .meta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .meta-actions {
    margin-left: 0;
    width: 100%;
  }
  
  .actions {
    flex-direction: column;
  }
  
  .modal {
    width: 95%;
    padding: 16px;
  }
}
</style>
