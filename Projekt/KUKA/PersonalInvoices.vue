<template>
  <div class="personal-invoices">

    <div class="header">
      <h2>Személyes számlák</h2>
    </div>

    <!-- Figyelmeztetések -->
    <div v-if="alert.message" :class="['alert', `alert-${alert.type}`, 'alert-dismissible', 'fade', 'show']" role="alert">
      {{ alert.message }}
      <button type="button" class="btn-close" @click="clearAlert"></button>
    </div>

    <!-- Szűrők -->
    <div class="filters card">
      <div class="row">
        <div class="col-md-4">
          <div class="form-group">
            <label for="yearFilter">Év</label>
            <select id="yearFilter" v-model="filters.year" class="form-control" @change="loadInvoices">
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="statusFilter">Státusz</label>
            <select id="statusFilter" v-model="filters.status" class="form-control" @change="loadInvoices">
              <option value="">Összes</option>
              <option value="Fizetve">Fizetve</option>
              <option value="Generálva">Generálva</option>
              <option value="Függőben lévő">Függőben lévő</option>
              <option value="Lejárt">Lejárt</option>
            </select>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group">
            <label for="searchFilter">Számlaszám</label>
            <input
              id="searchFilter"
              v-model="filters.search"
              type="text"
              class="form-control"
              placeholder="Keresés számlaszám alapján"
              @input="debouncedSearch"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Betöltés állapota -->
    <div v-if="loading" class="text-center my-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Betöltés...</span>
      </div>
      <p class="mt-2">Számlák betöltése...</p>
    </div>

    <!-- Nincs számla üzenet -->
    <div v-else-if="invoices.length === 0" class="empty-state card text-center py-5">
      <div class="empty-icon mb-3">
        <i class="bi bi-receipt-cutoff" style="font-size: 3rem; color: #6c757d;"></i>
      </div>
      <h4>Nincs számla</h4>

    </div>

    <!-- Számlák listája -->
    <div v-else class="invoices-list">
      <div v-for="invoice in invoices" :key="invoice.id" class="invoice-card card mb-3">
        <div class="card-body">
          <div class="row align-items-center">
            <!-- Számla információ -->
            <div class="col-md-6">
              <div class="d-flex align-items-start">
                <div class="invoice-icon me-3">
                  <i class="bi bi-file-earmark-text" style="font-size: 2rem; color: #0d6efd;"></i>
                </div>
                <div>
                  <h5 class="card-title mb-1">{{ invoice.invoiceNumber }}</h5>
                  <div class="invoice-details text-muted">
                    <p class="mb-1">
                      <i class="bi bi-calendar me-1"></i>
                      <strong>Hónap:</strong> {{ formatMonth(invoice.billingMonth) }}
                    </p>
                    <p class="mb-1">
                      <i class="bi bi-calendar-check me-1"></i>
                      <strong>Kiadás dátuma:</strong> {{ formatDate(invoice.issueDate) }}
                    </p>
                    <p class="mb-1">
                      <i class="bi bi-calendar-x me-1"></i>
                      <strong>Fizetési határidő:</strong> {{ formatDate(invoice.dueDate) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Státusz és összeg -->
            <div class="col-md-3">
              <div class="d-flex flex-column">
                <span class="badge mb-2" :class="getStatusBadgeClass(invoice.invoiceStatus)">
                  {{ invoice.invoiceStatus }}
                </span>
                <div class="invoice-amount">
                  <h4 class="text-primary">{{ formatCurrency(invoice.totalAmount) }}</h4>
                  <small class="text-muted">Fizetési mód: {{ invoice.paymentMethod }}</small>
                </div>
              </div>
            </div>

            <!-- Műveletek -->
            <div class="col-md-3">
              <div class="d-flex flex-column gap-2">
                <!-- Megtekintés gomb -->
                <button 
                  class="btn btn-outline-primary btn-sm" 
                  @click="viewInvoice(invoice)"
                  :disabled="invoice.invoiceStatus === 'Függőben lévő'"
                  :title="invoice.invoiceStatus === 'Függőben lévő' ? 'Csak generált számla tekinthető meg' : ''"
                >
                  <i class="bi bi-eye me-1"></i> Megtekintés
                </button>
                
                <!-- Letöltés gomb -->
                <button 
                  class="btn btn-primary btn-sm" 
                  @click="downloadInvoice(invoice)"
                  :disabled="invoice.invoiceStatus === 'Függőben lévő'"
                  :title="invoice.invoiceStatus === 'Függőben lévő' ? 'Csak generált számla tölthető le' : ''"
                >
                  <i class="bi bi-download me-1"></i> PDF letöltése
                </button>
                
                <!-- Fizetési információk -->
                <div v-if="invoice.paidAt" class="text-success small mt-1">
                  <i class="bi bi-check-circle me-1"></i>
                  Fizetve: {{ formatDateTime(invoice.paidAt) }}
                </div>
              </div>
            </div>
          </div>

          <!-- Számla tételes listája (akkordion) -->
          <div class="mt-3">
            <button 
              class="btn btn-link p-0 text-decoration-none" 
              type="button" 
              @click="toggleInvoiceDetails(invoice.id)"
            >
              <span v-if="expandedInvoice === invoice.id">
                <i class="bi bi-chevron-up me-1"></i> Részletek elrejtése
              </span>
              <span v-else>
                <i class="bi bi-chevron-down me-1"></i> Rendelések megjelenítése
              </span>
            </button>

            <div v-if="expandedInvoice === invoice.id" class="invoice-details mt-3">
              <!-- Részletek betöltése -->
              <div v-if="loadingDetails === invoice.id" class="text-center py-2">
                <div class="spinner-border spinner-border-sm text-secondary" role="status">
                  <span class="visually-hidden">Betöltés...</span>
                </div>
              </div>
              
              <!-- Részletek táblázata -->
              <div v-else-if="invoiceDetails[invoice.id]" class="table-responsive">
                <table class="table table-sm table-hover">
                  <thead class="table-light">
                    <tr>
                      <th>Dátum</th>
                      <th>Étkezés</th>
                      <th>Menü</th>
                      <th>Ár kategória</th>
                      <th>Ár</th>
                      <th>Státusz</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="order in invoiceDetails[invoice.id]" :key="order.id">
                      <td>{{ formatDate(order.orderDate) }}</td>
                      <td>{{ order.menu_item_name }}</td>
                      <td>
                        <span class="badge" :class="getOptionBadgeClass(order.selectedOption)">
                          {{ order.selectedOption }}
                        </span>
                      </td>
                      <td>{{ order.price_category }}</td>
                      <td>{{ formatCurrency(order.price_amount) }}</td>
                      <td>
                        <span class="badge" :class="getOrderStatusBadgeClass(order.orderStatus)">
                          {{ order.orderStatus }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="table-light">
                    <tr>
                      <td colspan="4" class="text-end"><strong>Összesen:</strong></td>
                      <td colspan="2"><strong>{{ formatCurrency(invoice.totalAmount) }}</strong></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Lapozás -->
    <div v-if="invoices.length > 0 && pagination.total > pagination.perPage" class="pagination-container mt-4">
      <nav aria-label="Számlák lapozása">
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
            <button class="page-link" @click="changePage(pagination.currentPage - 1)">
              <i class="bi bi-chevron-left"></i>
            </button>
          </li>
          
          <li 
            v-for="page in visiblePages" 
            :key="page" 
            class="page-item" 
            :class="{ active: page === pagination.currentPage }"
          >
            <button class="page-link" @click="changePage(page)">{{ page }}</button>
          </li>
          
          <li class="page-item" :class="{ disabled: pagination.currentPage === pagination.lastPage }">
            <button class="page-link" @click="changePage(pagination.currentPage + 1)">
              <i class="bi bi-chevron-right"></i>
            </button>
          </li>
        </ul>
      </nav>
      <p class="text-center text-muted small">
        {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }} számla
      </p>
    </div>

    <!-- Számla előnézet modal -->
    <div v-if="selectedInvoice" class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Számla előnézet - {{ selectedInvoice.invoiceNumber }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Inline PDF viewer vagy számla HTML megjelenítése -->
            <div v-if="previewLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Betöltés...</span>
              </div>
              <p class="mt-2">Számla betöltése...</p>
            </div>
            
            <div v-else class="invoice-preview">
              <!-- Itt jelenik meg a PDF preview vagy HTML számla -->
              <iframe 
                v-if="previewUrl" 
                :src="previewUrl" 
                width="100%" 
                height="500" 
                frameborder="0"
              ></iframe>
              
              <div v-else class="alert alert-warning">
                A számla előnézete nem érhető el. Kérjük, töltse le a PDF fájlt.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bezárás</button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="downloadInvoice(selectedInvoice)"
              :disabled="selectedInvoice.invoiceStatus === 'Függőben lévő'"
            >
              <i class="bi bi-download me-1"></i> PDF letöltése
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// Adatmodell
const invoices = ref([])
const invoiceDetails = ref({})
const loading = ref(false)
const previewLoading = ref(false)
const loadingDetails = ref(null)
const expandedInvoice = ref(null)
const selectedInvoice = ref(null)
const previewUrl = ref(null)

// Alert rendszer a toast helyett
const alert = ref({
  message: '',
  type: 'info'
})

// Szűrők
const filters = ref({
  year: new Date().getFullYear(),
  status: '',
  search: ''
})

// Lapozás
const pagination = ref({
  currentPage: 1,
  perPage: 10,
  total: 0,
  lastPage: 1,
  from: 0,
  to: 0
})

// Elérhető évek (aktuális év + 2 év visszamenőleg)
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 3 }, (_, i) => currentYear - i)
})

// Látható oldalszámok
const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage
  const delta = 2
  
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    pages.push(i)
  }
  
  if (current - delta > 2) {
    pages.unshift('...')
  }
  if (current + delta < last - 1) {
    pages.push('...')
  }
  
  pages.unshift(1)
  if (last > 1) pages.push(last)
  
  return pages
})

// Alert kezelő
const showAlert = (message, type = 'info') => {
  alert.value = { message, type }
}

const clearAlert = () => {
  alert.value = { message: '', type: 'info' }
}

// Debounced keresés
let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.currentPage = 1
    loadInvoices()
  }, 500)
}

// Számlák betöltése
const loadInvoices = async () => {
  loading.value = true
  
  try {
    const params = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage,
      year: filters.value.year,
      ...(filters.value.status && { status: filters.value.status }),
      ...(filters.value.search && { search: filters.value.search })
    }
    
    const response = await axios.get('/user/personal-invoices', { params })
    
    invoices.value = response.data.data
    pagination.value = {
      currentPage: response.data.current_page,
      perPage: response.data.per_page,
      total: response.data.total,
      lastPage: response.data.last_page,
      from: response.data.from,
      to: response.data.to
    }
    
    showAlert(`${response.data.total} számla betöltve`, 'success')
  } catch (error) {
    console.error('Hiba a számlák betöltésekor:', error)
    showAlert('Hiba történt a számlák betöltése során', 'danger')
  } finally {
    loading.value = false
  }
}

// Számla részleteinek betöltése
const loadInvoiceDetails = async (invoiceId) => {
  loadingDetails.value = invoiceId
  
  try {
    const response = await axios.get(`/user/personal-invoices/${invoiceId}/orders`)
    invoiceDetails.value[invoiceId] = response.data
  } catch (error) {
    console.error('Hiba a számla részleteinek betöltésekor:', error)
    showAlert('Hiba történt a rendelések betöltése során', 'danger')
    invoiceDetails.value[invoiceId] = []
  } finally {
    loadingDetails.value = null
  }
}

// Számla előnézet
const viewInvoice = async (invoice) => {
  if (invoice.invoiceStatus === 'Függőben lévő') {
    showAlert('Csak generált számla tekinthető meg', 'warning')
    return
  }
  
  selectedInvoice.value = invoice
  previewLoading.value = true
  
  try {
    // Inline PDF megjelenítés vagy HTML preview
    previewUrl.value = `/user/personal-invoices/${invoice.id}/preview`
    
    // Modal megjelenítése
    const modalElement = document.getElementById('invoicePreviewModal')
    if (modalElement) {
      const modal = new bootstrap.Modal(modalElement)
      modal.show()
    }
  } catch (error) {
    console.error('Hiba a számla előnézete betöltésekor:', error)
    showAlert('Hiba történt a számla előnézetének betöltése során', 'danger')
  } finally {
    previewLoading.value = false
  }
}

// Számla letöltése PDF-ként
const downloadInvoice = async (invoice) => {
  if (invoice.invoiceStatus === 'Függőben lévő') {
    showAlert('Csak generált számla tölthető le', 'warning')
    return
  }
  
  try {
    const response = await axios.get(`/api/user/invoices/${invoice.id}/download`, {
      responseType: 'blob'
    })
    
    // Blob létrehozása és letöltés
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `szamla_${invoice.invoiceNumber}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    
    showAlert('Számla letöltése elkezdődött', 'success')
  } catch (error) {
    console.error('Hiba a számla letöltésekor:', error)
    showAlert('Hiba történt a számla letöltése során', 'danger')
  }
}

// Akkordion kezelése
const toggleInvoiceDetails = (invoiceId) => {
  if (expandedInvoice.value === invoiceId) {
    expandedInvoice.value = null
  } else {
    expandedInvoice.value = invoiceId
    // Ha még nincsenek betöltve a részletek, betöltjük
    if (!invoiceDetails.value[invoiceId]) {
      loadInvoiceDetails(invoiceId)
    }
  }
}

// Oldalváltás
const changePage = (page) => {
  if (page < 1 || page > pagination.value.lastPage || page === pagination.value.currentPage) {
    return
  }
  
  pagination.value.currentPage = page
  loadInvoices()
  // Görgessen a tetejére
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Formátumok
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('hu-HU')
}

const formatMonth = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('hu-HU', { year: 'numeric', month: 'long' })
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleDateString('hu-HU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('hu-HU', {
    style: 'currency',
    currency: 'HUF',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

// Badge osztályok
const getStatusBadgeClass = (status) => {
  const classes = {
    'Fizetve': 'bg-success',
    'Generálva': 'bg-primary',
    'Függőben lévő': 'bg-warning text-dark',
    'Lejárt': 'bg-danger'
  }
  return classes[status] || 'bg-secondary'
}

const getOrderStatusBadgeClass = (status) => {
  const classes = {
    'Fizetve': 'bg-success',
    'Rendelve': 'bg-info',
    'Lemondva': 'bg-secondary'
  }
  return classes[status] || 'bg-light text-dark'
}

const getOptionBadgeClass = (option) => {
  const classes = {
    'A': 'bg-primary',
    'B': 'bg-info text-dark'
  }
  return classes[option] || 'bg-light text-dark'
}

// Komponens betöltésekor
onMounted(() => {
  loadInvoices()
})

// Év változás figyelése
watch(() => filters.value.year, () => {
  pagination.value.currentPage = 1
})
</script>

<style scoped>
.personal-invoices {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  margin-bottom: 30px;
}



.header p {
  color: #7f8c8d;
  font-size: 1.1rem;
}

.filters {
  background-color: #f8f9fa;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 30px;
}

.invoice-card {
  border-left: 4px solid #0d6efd;
  transition: transform 0.2s, box-shadow 0.2s;
}

.invoice-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.invoice-details {
  font-size: 0.9rem;
}

.badge {
  font-size: 0.8rem;
  padding: 0.35em 0.65em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.empty-state {
  background-color: #f8f9fa;
  border: 2px dashed #dee2e6;
}

.table th {
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  color: #6c757d;
}

.table td {
  font-size: 0.9rem;
}

.pagination-container {
  margin-top: 30px;
}

.page-link {
  cursor: pointer;
}

.invoice-preview {
  min-height: 300px;
  background-color: #f8f9fa;
  border-radius: 5px;
  overflow: hidden;
}

@media (max-width: 768px) {
  .personal-invoices {
    padding: 15px;
  }
  
  .filters .row {
    margin-bottom: -15px;
  }
  
  .filters .col-md-4 {
    margin-bottom: 15px;
  }
  
  .invoice-card .card-body .row > div {
    margin-bottom: 15px;
  }
}
</style>