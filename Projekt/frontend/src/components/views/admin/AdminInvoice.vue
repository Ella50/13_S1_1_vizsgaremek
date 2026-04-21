<template>
  <div class="admin-invoices">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Számlák kezelése</h1>

        <div class="header-controls">
         
            <div class="search-box">
              <input 
                type="text" 
                v-model="searchQuery" 
                placeholder="Keresés név vagy email alapján..."
                class="search-input"
                maxlength="255" 
                @input="onSearchInput"
              >
              <span class="search-icon">🔍</span>
            </div>

   
          <div class="filter-tabs">
              <button 
              :class="['filter-tab', { active: activeStatusFilter === 'unpaid' }]"
              @click="setStatusFilter('unpaid')"
            >
              Függőben lévő
            </button>
           <button 
              :class="['filter-tab', { active: activeStatusFilter === 'all' }]"
              @click="setStatusFilter('all')"
            >
              Összes
            </button>
            <button 
              :class="['filter-tab', { active: activeStatusFilter === 'paid' }]"
              @click="setStatusFilter('paid')"
            >
              Fizetve
            </button>

          </div>

           <div class="toolbar">
            <div class="form-group">
              <input type="month" v-model="billingMonth" class="form-control" />
            </div>

            <button 
              class="btn-primary" 
              :disabled="generating || !billingMonth" 
              @click="generate"
            >
              {{ generating ? "Generálás..." : "Számlák generálása" }}
            </button>
          </div>
        
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Betöltés...</p>
      </div>

      <div v-else-if="error" class="error-message">
        {{ error }}
      </div>

      <div v-else>


        <div v-if="rows.length === 0" class="empty-state">
          Nincs találat erre a hónapra.
        </div>

        <div v-else class="table-wrapper">
          <table class="data-table">
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
                  <div class="user-info">
                    <div class="user-name">
                      {{ inv.user?.lastName + " " + inv.user?.firstName || inv.firstName || "—" }}
                    </div>
                    <div class="user-email">
                      {{ inv.user?.email || inv.userEmail || "" }}
                    </div>
                  </div>
                </td>
                <td>{{ inv.invoiceNumber }}</td>
                <td>{{ formatMonth(inv.billingMonth) }}</td>
                <td class="amount">{{ formatFt(Math.round(inv.totalAmount * 1.27)) }}</td>
                <td>
                  <span class="status-badge" :class="getStatusClass(inv.invoiceStatus)">
                    {{ inv.invoiceStatus }}
                  </span>
                </td>
                <td>{{ formatDate(inv.dueDate) }}</td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button class="btn-action btn-view" @click="open(inv.id)">
                      Megtekintés
                    </button>
                    <button class="btn-action btn-pdf" @click="pdf(inv)">
                      PDF
                    </button>
                    <button
                      v-if="inv.invoiceStatus !== 'Fizetve'"
                      class="btn-action btn-paid"
                      @click="markPaid(inv)"
                      :disabled="payingId === inv.id"
                    >
                      {{ payingId === inv.id ? "Mentés..." : "Fizetve" }}
                    </button>
                    <button
                      v-if="inv.invoiceStatus === 'Fizetve'"
                      class="btn-action btn-unpaid"
                      @click="markUnpaid(inv)"
                      :disabled="payingId === inv.id"
                    >
                      {{ payingId === inv.id ? "Visszaállítás..." : "Visszavonás" }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination
          :current-page="currentPage"
          :last-page="lastPage"
          :total="total"
          :per-page="perPage"
          :per-page-options="[10, 25, 50, 100]"
          @update:page="changePage"
          @update:perPage="changePerPage"
        />

      </div>





      <!-- MODAL -->
      <div v-if="selected" class="modal-overlay" @click.self="close">
        <div class="modal">
          <div class="modal-header">
            <h2>{{ selected.invoiceNumber }}</h2>
            <button class="modal-close" @click="close">×</button>
          </div>

          <div class="modal-body">
            <div class="invoice-meta">
              <div class="meta-row">
                <span class="meta-label">Felhasználó:</span>
                <span class="meta-value">{{ selected.user?.firstName + " " + selected.user?.lastName || selected.userName || "—" }}</span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Hónap:</span>
                <span class="meta-value">{{ formatMonth(selected.billingMonth) }}</span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Összeg:</span>
                <span class="meta-value">{{ formatFt(selected.totalAmount) }}</span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Státusz:</span>
                <span class="meta-value">
                  <span class="status-badge" :class="getStatusClass(selected.invoiceStatus)">
                    {{ selected.invoiceStatus }}
                  </span>
                </span>
              </div>
              <div class="meta-row">
                <span class="meta-label">Határidő:</span>
                <span class="meta-value">{{ formatDate(selected.dueDate) }}</span>
              </div>
            </div>

            <div class="modal-actions">
              <button class="btn-modal btn-modal-pdf" @click="pdf(selected)">
                PDF letöltés
              </button>
              <button
                class="btn-modal btn-modal-paid"
                v-if="selected.invoiceStatus !== 'Fizetve'"
                @click="markPaid(selected)"
                :disabled="payingId === selected.id"
              >
                {{ payingId === selected.id ? "Mentés..." : "Fizetve" }}
              </button>
            </div>

            <h3 class="section-title">Tételek</h3>

            <div class="table-wrapper">
              <table class="data-table">
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
                    <td class="amount">{{ formatFt(o.amount) }}</td>
                    <td>{{ o.orderStatus }}</td>
                  </tr>
                  <tr v-if="!selected.orders || selected.orders.length === 0">
                    <td colspan="4" class="empty-row">Nincs tétel.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="modal-footer">
            <button class="btn-modal btn-modal-close" @click="close">Bezárás</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert értesítés -->
    <div class="alert-container" v-if="alertVisible">
      <div :class="['alert', alertType]">
        <strong v-if="alertTitle">{{ alertTitle }}</strong>
        <div>{{ alertMessage }}</div>
      </div>
    </div>

    <!-- Confirm modal -->
    <div v-if="confirmVisible" class="confirm-overlay">
      <div class="confirm-box">
        <h3 v-if="confirmTitle" class="confirm-title">{{ confirmTitle }}</h3>
        <p class="confirm-message">{{ confirmMessage }}</p>
        <div class="confirm-actions">
          <button class="btn-cancel" @click="confirmCancel">Mégse</button>
          <button class="btn-ok" @click="confirmOk">OK</button>
        </div>
      </div>
    </div>
    <div v-if="showPaymentMethodModal" class="modal-overlay" @click.self="showPaymentMethodModal = false">
      <div class="modal payment-modal">
        <div class="modal-header">
          <h2>Fizetési mód kiválasztása</h2>
          <button @click="showPaymentMethodModal = false" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <div class="invoice-summary">
            <div class="summary-item">
              <span class="summary-label">Számlaszám:</span>
              <span class="summary-value">{{ selectedInvoiceForPayment?.invoiceNumber }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Összeg:</span>
              <span class="summary-value amount-highlight">{{ formatFt(Math.round(selectedInvoiceForPayment?.totalAmount * 1.27)) }}</span>
            </div>
          </div>
          
          <div class="payment-options">
            <h3 class="options-title">Válassz fizetési módot</h3>
            
            <div class="option-group">
              <label 
                v-for="method in paymentMethods" 
                :key="method.value"
                :class="['payment-option', { selected: selectedPaymentMethod === method.value }]"
              >
                <input 
                  type="radio" 
                  :value="method.value" 
                  v-model="selectedPaymentMethod" 
                  class="payment-radio"
                >
                <div class="payment-card">
                  <div class="payment-icon">{{ method.icon }}</div>
                  <div class="payment-details">
                    <div class="payment-name">{{ method.label }}</div>
                    <div class="payment-desc">{{ method.description }}</div>
                  </div>
                  <div class="payment-check" v-if="selectedPaymentMethod === method.value">
                    ✓
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="showPaymentMethodModal = false">
            Mégse
          </button>
          <button 
            type="button" 
            class="btn-confirm" 
            @click="confirmPayment"
            :disabled="!selectedPaymentMethod"
          >
            <span>Fizetettre állítás</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onBeforeUnmount } from "vue";
import {
  adminFetchInvoices,
  adminFetchInvoice,
  adminGenerateInvoicesForMonth,
  adminMarkInvoicePaid,
  adminMarkInvoiceUnpaid
} from "@/services/invoiceApi";

import Pagination from '../../layout/Pagination.vue';

const loading = ref(false);
const generating = ref(false);
const error = ref("");

const rows = ref([]);
const selected = ref(null);
const payingId = ref(null);

const billingMonth = ref("");
const searchQuery = ref("");
const searchTimeout = ref(null);

// Pagináció állapotok
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(25)

// Alert állapotok
const alertVisible = ref(false);
const alertMessage = ref('');
const alertTitle = ref('');
const alertType = ref('success');
let alertTimeout = null;

// Confirm állapotok
const confirmVisible = ref(false);
const confirmMessage = ref('');
const confirmTitle = ref('');
let confirmResolver = null;

// Computed properties
const totalSum = computed(() => {
  const arr = Array.isArray(rows.value) ? rows.value : [];
  return arr.reduce((sum, i) => {
    const net = Number(i.totalAmount || 0);
    const gross = net * 1.27;
    return sum + gross;
  }, 0);
});

const showPaymentMethodModal = ref(false);
const selectedInvoiceForPayment = ref(null);
const selectedPaymentMethod = ref('');

const paymentMethods = [
  { value: 'készpénz', label: 'Készpénz'},
  { value: 'bankkártya', label: 'Bankkártya'},
  { value: 'banki utalás', label: 'Banki utalás'}
];

const activeStatusFilter = ref('all')

function setStatusFilter(status) {
  activeStatusFilter.value = status
  currentPage.value = 1  
  load()
}

// Alert metódusok
function showAlert({ message, type = 'success', title = '' }) {
  if (alertTimeout) {
    clearTimeout(alertTimeout);
  }
  
  alertMessage.value = message;
  alertType.value = type;
  alertTitle.value = title;
  alertVisible.value = true;
  
  alertTimeout = setTimeout(() => {
    alertVisible.value = false;
  }, 3000);
}

// Confirm metódusok
function showConfirm({ message, title = '' }) {
  confirmMessage.value = message;
  confirmTitle.value = title;
  confirmVisible.value = true;
  
  return new Promise((resolve) => {
    confirmResolver = resolve;
  });
}

function confirmOk() {
  confirmVisible.value = false;
  if (confirmResolver) {
    confirmResolver(true);
    confirmResolver = null;
  }
}

function confirmCancel() {
  confirmVisible.value = false;
  if (confirmResolver) {
    confirmResolver(false);
    confirmResolver = null;
  }
}

function changePage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  load()
  window.scrollTo(0, 0)
}

function changePerPage(newPerPage) {
  perPage.value = newPerPage
  currentPage.value = 1
  load()
}



  async function load() {
      console.log('activeStatusFilter.value:', activeStatusFilter.value);
    loading.value = true
    error.value = ""
    try {
      const params = {
        month: billingMonth.value,
        search: searchQuery.value,
        page: currentPage.value,
        per_page: perPage.value,
        status: activeStatusFilter.value 
      }
       console.log('Sending params:', params);
      const response = await adminFetchInvoices(params)


      let invoiceData = []
      let paginationData = {}

      if (typeof response === 'string') {
        try {
          const cleanJson = response.replace(/^\uFEFF/, '')
          const parsed = JSON.parse(cleanJson)
          if (parsed?.invoices?.data) {
            invoiceData = parsed.invoices.data
            paginationData = {
              current_page: parsed.invoices.current_page,
              last_page: parsed.invoices.last_page,
              per_page: parsed.invoices.per_page,
              total: parsed.invoices.total
            }
          } else if (Array.isArray(parsed?.invoices)) {
            invoiceData = parsed.invoices
          } else if (Array.isArray(parsed?.data)) {
            invoiceData = parsed.data
            paginationData = {
              current_page: parsed.current_page,
              last_page: parsed.last_page,
              per_page: parsed.per_page,
              total: parsed.total
            }
          } else if (Array.isArray(parsed)) {
            invoiceData = parsed
          }
        } catch (e) {
          console.error("JSON parse error:", e)
        }
      } else if (response && typeof response === 'object') {
        if (response.invoices?.data) {
          invoiceData = response.invoices.data
          paginationData = {
            current_page: response.invoices.current_page,
            last_page: response.invoices.last_page,
            per_page: response.invoices.per_page,
            total: response.invoices.total
          }
        } else if (Array.isArray(response.invoices)) {
          invoiceData = response.invoices
        } else if (Array.isArray(response.data)) {
          invoiceData = response.data
          paginationData = {
            current_page: response.current_page,
            last_page: response.last_page,
            per_page: response.per_page,
            total: response.total
          }
        } else if (Array.isArray(response)) {
          invoiceData = response
        }
      }

      rows.value = invoiceData

      // Ha van paginációs adat, használjuk, különben számoljuk (kompatibilitás miatt)
      if (paginationData.last_page) {
        currentPage.value = paginationData.current_page || 1
        lastPage.value = paginationData.last_page || 1
        total.value = paginationData.total || 0
        perPage.value = paginationData.per_page || 25
      } else {
        // Frontend pagináció (ha a backend nem adja vissza) – de ezt nem javasolt
        total.value = rows.value.length
        lastPage.value = Math.ceil(total.value / perPage.value) || 1
        currentPage.value = Math.min(currentPage.value, lastPage.value)
      }

    } catch (e) {
      error.value = e?.response?.data?.message || "Hiba a számlák betöltésekor"
      showAlert({ message: error.value, type: 'error' })
      console.error(e)
    } finally {
      loading.value = false
    }
}


// Keresés debounce
function onSearchInput() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    currentPage.value = 1
    load()
  }, 300)
}

// Segédfüggvények
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

function getStatusClass(status) {
  const classes = {
    'Fizetve': 'status-paid',
    'Generálva': 'status-generated',
    'Függőben lévő': 'status-pending',
    'Lejárt': 'status-expired'
  };
  return classes[status] || 'status-default';
}

// Generálás
async function generate() {
  if (!billingMonth.value) return;
  
  const confirmed = await showConfirm({
    title: 'Számlák generálása',
    message: `Biztosan legenerálod a(z) ${formatMonth(billingMonth.value)} havi számlákat?`
  });
  
  if (!confirmed) return;
  
  generating.value = true;
  try {
    await adminGenerateInvoicesForMonth(billingMonth.value);
    currentPage.value = 1;
    await load();
    showAlert({
      message: 'Számlák sikeresen legenerálva',
      type: 'success'
    });
  } catch (e) {
    console.log(e.response?.data);
    showAlert({
      message: e?.response?.data?.message || "Hiba a számlák generálásakor",
      type: 'error'
    });
  } finally {
    generating.value = false;
  }
}
function markPaid(inv) {
  console.log('markPaid called', inv); // Debug
  selectedInvoiceForPayment.value = inv;
  selectedPaymentMethod.value = '';
  showPaymentMethodModal.value = true;
}
// Fizetettre állítás
async function confirmPayment() {
  if (!selectedPaymentMethod.value) {
    showAlert({
      message: 'Kérlek válassz fizetési módot!',
      type: 'warning'
    });
    return;
  }
  
  const inv = selectedInvoiceForPayment.value;
  const confirmed = await showConfirm({
    title: 'Fizetettre állítás',
    message: `Biztosan fizetettre állítod a(z) ${inv.invoiceNumber} számú számlát?\nFizetési mód: ${selectedPaymentMethod.value}`
  });
  
  if (!confirmed) {
    showPaymentMethodModal.value = false;
    return;
  }
  
  payingId.value = inv.id;
  error.value = "";
  
  try {
    await adminMarkInvoicePaid(inv.id, selectedPaymentMethod.value);
    await load();
    
    if (selected.value?.id === inv.id) {
      selected.value = {
        ...selected.value,
        invoiceStatus: 'Fizetve',
        paymentMethod: selectedPaymentMethod.value
      };
    }
    
    showAlert({
      message: `Számla sikeresen fizetettre állítva (${selectedPaymentMethod.value})`,
      type: 'success'
    });
    
    showPaymentMethodModal.value = false;
    
  } catch (e) {
    console.error('Mark as paid error:', e);
    showAlert({
      message: e?.response?.data?.message || "Hiba a fizetettre állításkor",
      type: 'error'
    });
  } finally {
    payingId.value = null;
    selectedInvoiceForPayment.value = null;
  }
}

// Visszavonás (Fizetve -> Generálva)
async function markUnpaid(inv) {
  const confirmed = await showConfirm({
    title: 'Státusz visszaállítása',
    message: `Biztosan visszaállítod "Generálva" státuszra a(z) ${inv.invoiceNumber} számú számlát?`
  });
  
  if (!confirmed) return;
  
  payingId.value = inv.id;
  error.value = "";
  
  try {
    await adminMarkInvoiceUnpaid(inv.id);
    await load();
    
    if (selected.value?.id === inv.id) {
      selected.value = {
        ...selected.value,
        invoiceStatus: 'Generálva'
      };
    }
    
    showAlert({
      message: 'Számla státusza sikeresen visszaállítva',
      type: 'success'
    });
    
  } catch (e) {
    console.error('Mark as unpaid error:', e);
    showAlert({
      message: e?.response?.data?.message || "Hiba a visszaállításkor",
      type: 'error'
    });
  } finally {
    payingId.value = null;
  }
}

// Modal kezelés
async function open(id) {
  error.value = "";
  try {
    const response = await adminFetchInvoice(id);
    let invoiceData = null;

    if (typeof response === 'string') {
      try {
        const cleanJson = response.replace(/^\uFEFF/, '');
        const parsed = JSON.parse(cleanJson);
        if (parsed?.invoice) {
          invoiceData = parsed.invoice;
        } else if (parsed?.data?.invoice) {
          invoiceData = parsed.data.invoice;
        }
      } catch (e) {
        console.error('JSON parse error:', e);
      }
    } else if (response && typeof response === 'object') {
      if (response.invoice) {
        invoiceData = response.invoice;
      } else if (response.data?.invoice) {
        invoiceData = response.data.invoice;
      } else if (response.id) {
        invoiceData = response;
      }
    }

    if (!invoiceData) {
      error.value = "Hibás adatformátum";
      showAlert({
        message: error.value,
        type: 'error'
      });
      return;
    }

    selected.value = invoiceData;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || "Hiba a számla betöltésekor";
    showAlert({
      message: error.value,
      type: 'error'
    });
  }
}

function close() {
  selected.value = null;
}

// PDF letöltés
async function pdf(inv) {
  try {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
    if (!token) {
      showAlert({
        message: 'Nincs bejelentkezve - hiányzik a token',
        type: 'error'
      });
      return;
    }

    const response = await fetch(`http://localhost:8000/api/admin/invoices/${inv.id}/pdf`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/pdf'
      }
    });

    if (response.status === 401) {
      showAlert({
        message: 'Nincs jogosultság (401) - jelentkezz be újra',
        type: 'error'
      });
      return;
    }

    if (!response.ok) {
      showAlert({
        message: `Hiba: ${response.status}`,
        type: 'error'
      });
      return;
    }

    const contentType = response.headers.get('content-type');
    if (contentType && contentType.includes('application/json')) {
      const errorData = await response.json();
      showAlert({
        message: errorData.message || 'Hiba a PDF letöltésekor',
        type: 'error'
      });
      return;
    }

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${inv.invoiceNumber || 'szamla'}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
    
    showAlert({
      message: 'PDF sikeresen letöltve',
      type: 'success'
    });
  } catch (e) {
    console.error('PDF download error:', e);
    showAlert({
      message: e.message || "Hiba a PDF letöltésekor",
      type: 'error'
    });
  }
}

// Görgetés tiltás modalnál
watch(selected, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

onBeforeUnmount(() => {
  document.body.style.overflow = '';
});

watch(billingMonth, () => {
  currentPage.value = 1
  load()
})


// Alapértelmezett hónap beállítása
function setDefaultMonth() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, "0");
  billingMonth.value = `${y}-${m}`;
}

setDefaultMonth();
load();
</script>

<style scoped>
.admin-invoices {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
}

.content-card {
  background: var(--content-card);
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.title {
  font-size: 1.75rem;
  color: #8a1212;
  margin: 0;
  font-weight: 600;
}

.header-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.toolbar {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
}

.btn-primary {
  padding: 0.5rem 1rem;
  background: #1fa317;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 0.9rem;
  height: fit-content;
}

.btn-primary:hover:not(:disabled) {
  background: #158a0f;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #1fa317;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  background: #f9f9f9;
  border-radius: 12px;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #eee;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.data-table th {
  background: #f0a24a;
  color: #7b2c2c;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

.data-table tr:hover {
  background: #fef9ef;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 500;
  color: #333;
}

.user-email {
  font-size: 0.7rem;
  color: #888;
  margin-top: 2px;
}

.amount {
  font-weight: 600;
  color: #2c3e50;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.status-paid {
  background: #d4edda;
  color: #155724;
}

.status-generated {
  background: #e9ecef;
  color: #495057;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
}

.status-expired {
  background: #f8d7da;
  color: #721c24;
}

.status-default {
  background: #e9ecef;
  color: #495057;
}

.actions-cell {
  white-space: nowrap;
}

.actions-group {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  padding: 0.4rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-view {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-view:hover {
  background: #bbdef5;
}

.btn-pdf {
  background: #e8eaf6;
  color: #3f51b5;
}

.btn-pdf:hover {
  background: #c5cae9;
}

.btn-paid {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-paid:hover:not(:disabled) {
  background: #c8e6c9;
}

.btn-paid:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-unpaid {
  background: #fff3cd;
  color: #856404;
}

.btn-unpaid:hover:not(:disabled) {
  background: #ffeaa7;
}

/* Alert stílusok */
.alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10001;
}

.alert {
  margin-bottom: 10px;
  padding: 14px 18px;
  border-radius: 12px;
  color: white;
  min-width: 250px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  animation: slideIn 0.3s ease;
}

.alert.success {
  background: #4CAF50;
}

.alert.error {
  background: #ff4d4f;
}

.alert.warning {
  background: #ff9800;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Confirm stílusok */
.confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10002;
}

.confirm-box {
  background: white;
  padding: 24px;
  border-radius: 14px;
  min-width: 320px;
  text-align: center;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);
  animation: scaleIn 0.25s ease;
}

@keyframes scaleIn {
  from {
    transform: scale(0.6);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.confirm-title {
  margin-bottom: 10px;
}

.confirm-message {
  font-weight: bold;
  text-align: center;
  margin: 10px 0 20px 0;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.confirm-actions .btn-cancel {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.confirm-actions .btn-cancel:hover {
  background: #c0392b;
}

.confirm-actions .btn-ok {
  background: #2ecc71;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.confirm-actions .btn-ok:hover {
  background: #27ae60;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 20px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow: hidden;
  display: flex !important;
  position: relative !important;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  animation: modalFadeIn 0.2s ease-out;
}

@keyframes modalFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #eee;
  background: #fff7e6;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  color: #8a1212;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #888;
  padding: 0 0.5rem;
  transition: color 0.2s;
}

.modal-close:hover {
  color: #8a1212;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: flex-end;
  background: #fafafa;
}

.invoice-meta {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.meta-row {
  display: flex;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
}

.meta-row:last-child {
  border-bottom: none;
}

.meta-label {
  width: 100px;
  font-weight: 500;
  color: #666;
}

.meta-value {
  color: #333;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.btn-modal {
  padding: 0.6rem 1.25rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.btn-modal-pdf {
  background: #e8eaf6;
  color: #3f51b5;
}

.btn-modal-pdf:hover {
  background: #c5cae9;
  transform: translateY(-1px);
}

.btn-modal-paid {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-modal-paid:hover:not(:disabled) {
  background: #c8e6c9;
  transform: translateY(-1px);
}

.btn-modal-paid:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-modal-close {
  background: #e9ecef;
  color: #495057;
}

.btn-modal-close:hover {
  background: #dee2e6;
  transform: translateY(-1px);
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f0a24a;
}

.empty-row {
  text-align: center;
  color: #888;
  padding: 2rem !important;
}

/* Search box styles */
.search-box {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.search-input {
  padding: 0.5rem 0.75rem 0.5rem 2rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  width: 250px;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
}

.search-icon {
  position: absolute;
  left: 0.6rem;
  font-size: 0.85rem;
  color: #888;
  pointer-events: none;
}




/* Responsive */
@media (max-width: 768px) {
  .admin-invoices {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-controls {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    width: 100%;
  }

  .search-input {
    width: 100%;
  }

  .toolbar {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }

  .form-group {
    width: 100%;
  }

  .form-control {
    width: 100%;
  }

  .btn-primary {
    width: 100%;
    text-align: center;
  }

  .actions-group {
    flex-wrap: wrap;
  }

  .btn-action {
    padding: 0.3rem 0.6rem;
    font-size: 0.7rem;
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .meta-row {
    flex-direction: column;
    gap: 0.25rem;
  }

  .meta-label {
    width: auto;
  }

  .modal-actions {
    flex-direction: column;
  }

  .btn-modal {
    text-align: center;
  }
}

@media (max-width: 480px) {
  .title {
    font-size: 1.25rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.5rem;
  }

  .btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.65rem;
  }

  .search-input {
    padding: 0.4rem 0.6rem 0.4rem 1.8rem;
    font-size: 0.8rem;
  }

  .search-icon {
    left: 0.5rem;
    font-size: 0.75rem;
  }
}

.payment-modal {
  max-width: 480px;
}

.invoice-summary {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.summary-item:first-child {
  border-bottom: 1px solid #dee2e6;
}

.summary-label {
  font-size: 0.85rem;
  color: #6c757d;
  font-weight: 500;
}

.summary-value {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
}

.amount-highlight {
  color: #1fa317;
  font-size: 1.25rem;
}

.options-title {
  font-size: 1rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f0a24a;
  display: inline-block;
}

.option-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.payment-option {
  cursor: pointer;
  display: block;
}

.payment-radio {
  display: none;
}

.payment-card {
  display: flex;
  align-items: center;
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  background: white;
  transition: all 0.3s ease;
  position: relative;
}

.payment-option:hover .payment-card {
  border-color: #f0a24a;
  transform: translateX(4px);
}

.payment-option.selected .payment-card {
  border-color: #1fa317;
  background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
  box-shadow: 0 4px 12px rgba(31, 163, 23, 0.15);
}

.payment-icon {
  font-size: 2rem;
  margin-right: 1rem;
  min-width: 48px;
  text-align: center;
}

.payment-details {
  flex: 1;
}

.payment-name {
  font-weight: 600;
  font-size: 1rem;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.payment-desc {
  font-size: 0.75rem;
  color: #6c757d;
}

.payment-check {
  width: 28px;
  height: 28px;
  background: #1fa317;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1rem;
}

.btn-confirm {
  background: linear-gradient(135deg, #1fa317 0%, #158a0f 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-confirm:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(31, 163, 23, 0.3);
}

.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Zöld fizetve gomb a táblázatban */
.btn-paid {
  background: linear-gradient(135deg, #1fa317 0%, #158a0f 100%);
  color: white;
  border: none;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-paid:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(31, 163, 23, 0.3);
  background: linear-gradient(135deg, #158a0f 0%, #0f6b0a 100%);
}

.btn-paid:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Visszavonás gomb */
.btn-unpaid {
  background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
  color: white;
  border: none;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-unpaid:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(255, 152, 0, 0.3);
  background: linear-gradient(135deg, #f57c00 0%, #e65100 100%);
}

.payment-modal {
  animation: modalSlideUp 0.3s ease-out;
}

/* Filter tabs */
.filter-tabs {
  display: flex;
  gap: 0.5rem;
  background: #f5f5f5;
  padding: 0.25rem;
  border-radius: 12px;
}

.filter-tab {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  color: #666;
}

.filter-tab:hover {
  background: #e9ecef;
  color: #333;
}

.filter-tab.active {
  background: #f0a24a;
  color: #7b2c2c;
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.payment-option {
  animation: fadeIn 0.3s ease-out;
  animation-fill-mode: both;
}

.payment-option:nth-child(1) { animation-delay: 0.05s; }
.payment-option:nth-child(2) { animation-delay: 0.1s; }
.payment-option:nth-child(3) { animation-delay: 0.15s; }

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@media (max-width: 768px) {
  .filter-tabs {
    width: 100%;
    justify-content: stretch;
  }
  .filter-tab {
    flex: 1;
    text-align: center;
  }
}
</style>