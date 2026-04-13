<template>
  <div class="personal-invoices">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Számláim</h1>
        
        <div class="header-controls">
          <div class="filter-group search">
            <div class="search-box">
              <input 
                type="text" 
                v-model="filters.search" 
                placeholder="Keresés számlaszám alapján..."
                @keyup.enter="loadInvoices"
                maxlength="255" 
                class="search-input"
              >
              <span class="search-icon">🔍</span>
            </div>
          </div>

          <div class="filter-group">
  
            <select v-model="filters.year" class="filter-select">
              <option value="">Összes év</option>
              <option v-for="year in availableYears" :key="year" :value="year">
                {{ year }}
              </option>
            </select>
          </div>
          
          <div class="filter-group">
            <select v-model="filters.status" class="filter-select">
              <option value="">Összes státusz</option>
              <option value="Generálva">Generálva</option>
              <option value="Fizetve">Fizetve</option>
              <option value="Lejárt">Lejárt</option>
            </select>
          </div>
          

          
        <!-- <button class="btn-primary" @click="loadInvoices">
            Szűrés
          </button>--> 
        </div>
      </div>
      
      <!-- Loading state -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Betöltés...</p>
      </div>
      
      <!-- Error state -->
      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="loadInvoices" class="btn-secondary">Újrapróbálkozás</button>
      </div>
      
      <!-- Empty state -->
      <div v-else-if="invoices.length === 0" class="empty-state">
        <h3>Nincs számla</h3>
      </div>
      
      <!-- Invoices table -->
      <div v-else>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Számlaszám</th>
                <th>Keltezés</th>
                <th>Esedékesség</th>
                <th>Összeg</th>
                <th>Státusz</th>
                <th>Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="invoice in invoices" 
                :key="invoice.id"
                :class="{ 'overdue-row': isOverdue(invoice) }"
              >
                <td class="invoice-number">{{ invoice.invoiceNumber }}</td>
                <td>{{ formatDate(invoice.issueDate) }}</td>
                <td :class="{ 'overdue-date': isOverdue(invoice) }">
                  {{ formatDate(invoice.dueDate) }}
                </td>
                <td class="amount">{{ formatAmount(invoice.totalAmount) }}</td>
                <td>
                  <span class="status-badge" :class="getStatusClass(invoice.invoiceStatus)">
                    {{ getStatusText(invoice.invoiceStatus) }}
                  </span>
                </td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button 
                      class="btn-action btn-view" 
                      @click="openInvoice(invoice.id)"
                      :disabled="loadingDetail === invoice.id"
                    >
                      {{ loadingDetail === invoice.id ? 'Betöltés...' : 'Részletek' }}
                    </button>
                    <button 
                      class="btn-action btn-pdf" 
                      @click="downloadPdf(invoice)"
                      :disabled="downloading === invoice.id"
                    >
                      {{ downloading === invoice.id ? '...' : 'PDF' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div class="pagination" v-if="pagination.last_page > 1">
          <button 
            @click="goToPage(pagination.current_page - 1)" 
            :disabled="pagination.current_page === 1"
            class="btn-pagination"
          >
            ← Előző
          </button>
          
          <div class="page-numbers">
            <template v-for="page in visiblePages" :key="page">
              <button 
                v-if="page !== '...'"
                @click="goToPage(page)"
                :class="{ active: page === pagination.current_page }"
                class="page-number"
              >
                {{ page }}
              </button>
              <span v-else class="page-dots">...</span>
            </template>
          </div>
          
          <button 
            @click="goToPage(pagination.current_page + 1)" 
            :disabled="pagination.current_page === pagination.last_page"
            class="btn-pagination"
          >
            Következő →
          </button>
        </div>
      </div>
    </div>
    
    <!-- Invoice Detail Modal -->
    <div v-if="selectedInvoice" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ selectedInvoice.invoiceNumber }}</h2>
          <button class="modal-close" @click="closeModal">×</button>
        </div>
        
        <div class="modal-body">
          <div class="detail-grid">
            <div class="detail-item">
              <span class="label">Kibocsátás dátuma:</span>
              <span class="value">{{ formatDate(selectedInvoice.issueDate) }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Esedékesség:</span>
              <span class="value" :class="{ 'overdue': isOverdue(selectedInvoice) }">
                {{ formatDate(selectedInvoice.dueDate) }}
              </span>
            </div>
            <div class="detail-item">
              <span class="label">Státusz:</span>
              <span class="value">
                <span class="status-badge" :class="getStatusClass(selectedInvoice.invoiceStatus)">
                  {{ getStatusText(selectedInvoice.invoiceStatus) }}
                </span>
              </span>
            </div>
            <div class="detail-item">
              <span class="label">Számla összege:</span>
              <span class="value amount">{{ formatAmount(selectedInvoice.totalAmount) }}</span>
            </div>
          </div>
          
          <div class="orders-section" v-if="selectedInvoice.orders && selectedInvoice.orders.length">
            <h3>Rendelések</h3>
            <div class="table-wrapper">
              <table class="orders-table">
                <thead>
                  <tr>
                    <th>Dátum</th>
                    <th>Opció</th>
                    <th>Összeg</th>
                    <th>Státusz</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="order in selectedInvoice.orders" :key="order.id">
                    <td>{{ formatDate(order.orderDate) }}</td>
                    <td>{{ getOptionLabel(order.selectedOption) }}</td>
                    <td class="amount">{{ formatAmount(order.amount) }}</td>
                    <td>
                      <span class="order-status" :class="getOrderStatusClass(order.orderStatus)">
                        {{ order.orderStatus }}
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="total-row">
                    <td colspan="2"><strong>Összesen:</strong></td>
                    <td class="amount"><strong>{{ formatAmount(selectedInvoice.totalAmount) }}</strong></td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
          
          <div v-else class="no-orders">
            <p>Nincsenek rendelések ehhez a számlához.</p>
          </div>
        </div>
        
        <div class="modal-footer">
          <button class="btn-pdf" @click="downloadPdf(selectedInvoice)" :disabled="downloading === selectedInvoice.id">
            {{ downloading === selectedInvoice.id ? '...' : 'PDF letöltése' }}
          </button>
          <button class="btn-cancel" @click="closeModal">Bezárás</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '@/services/authService'

export default {
  name: 'PersonalInvoices',
  
  data() {
    return {
      loading: false,
      loadingDetail: null,
      downloading: null,
      error: null,
      invoices: [],
      selectedInvoice: null,
      
      filters: {
        year: '',
        status: '',
        search: ''
      },
      
      pagination: {
        current_page: 1,
        per_page: 10,
        total: 0,
        last_page: 1,
        from: null,
        to: null
      }
    }
  },
  
  computed: {

    beforeDestroy() {
      document.body.style.overflow = ''
    },
    availableYears() {
      const currentYear = new Date().getFullYear()
      const years = []
      for (let i = currentYear; i >= currentYear - 5; i--) {
        years.push(i)
      }
      return years
    },
    
    hasActiveFilters() {
      return this.filters.year || this.filters.status || this.filters.search
    },
    
    visiblePages() {
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      const delta = 2
      const range = []
      const rangeWithDots = []
      let l

      for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
          range.push(i)
        }
      }

      range.forEach((i) => {
        if (l) {
          if (i - l === 2) {
            rangeWithDots.push(l + 1)
          } else if (i - l !== 1) {
            rangeWithDots.push('...')
          }
        }
        rangeWithDots.push(i)
        l = i
      })

      return rangeWithDots
    }
  },
  
  mounted() {
    this.loadInvoices()
    
  },
  
  methods: {
    formatDate(dateStr) {
      if (!dateStr) return '—'
      const date = new Date(dateStr)
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      })
    },
    
    formatAmount(amount) {
      if (!amount && amount !== 0) return '0 Ft'
      const grossAmount = Math.round(amount * 1.27)
      return grossAmount.toLocaleString('hu-HU') + ' Ft'
    },
    
    getStatusText(status) {
      const statusMap = {
        'Generálva': 'Generálva',
        'Fizetve': 'Kifizetve',
        'Lejárt': 'Lejárt'
      }
      return statusMap[status] || status
    },
    
    getStatusClass(status) {
      const classes = {
        'Generálva': 'status-generated',
        'Fizetve': 'status-paid',
        'Lejárt': 'status-expired'
      }
      return classes[status] || 'status-default'
    },
    
    getOrderStatusClass(status) {
      const classes = {
        'Rendelve': 'order-active',
        'Fizetve': 'order-paid',
        'Lemondva': 'order-cancelled'
      }
      return classes[status] || 'order-default'
    },
    
    getOptionLabel(option) {
      const optionMap = {
        'A': 'A opció',
        'B': 'B opció',
        'soup': 'Leves',
        'other': 'Egyéb'
      }
      return optionMap[option] || option
    },
    
    isOverdue(invoice) {
      if (!invoice.dueDate) return false
      if (invoice.invoiceStatus === 'Fizetve') return false
      const today = new Date()
      const dueDate = new Date(invoice.dueDate)
      today.setHours(0, 0, 0, 0)
      dueDate.setHours(0, 0, 0, 0)
      return dueDate < today
    },
    
    async loadInvoices() {
      this.loading = true
      this.error = null
      
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: this.pagination.per_page
        }
        
        if (this.filters.year) params.year = this.filters.year
        if (this.filters.status) params.status = this.filters.status
        if (this.filters.search) params.search = this.filters.search
        
        const response = await AuthService.api.get('/user/personal-invoices', { params })
        
        if (response.data.success) {
          this.invoices = response.data.data
          this.pagination = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
            last_page: response.data.last_page,
            from: response.data.from,
            to: response.data.to
          }
        } else {
          throw new Error(response.data.message || 'Ismeretlen hiba')
        }
      } catch (err) {
        console.error('Hiba a számlák betöltésekor:', err)
        if (err.response?.status === 401) {
          this.error = 'Nincs bejelentkezve. Kérlek, jelentkezz be újra.'
        } else {
          this.error = err.response?.data?.message || err.message || 'Hiba történt a számlák betöltése során.'
        }
      } finally {
        this.loading = false
      }
    },
    
    async openInvoice(invoiceId) {
      this.loadingDetail = invoiceId
      this.error = null
      
      try {
        const response = await AuthService.api.get(`/user/personal-invoices/${invoiceId}/orders`)
        
        if (response.data.success) {
          const baseInvoice = this.invoices.find(i => i.id === invoiceId)
          this.selectedInvoice = {
            ...baseInvoice,
            orders: response.data.data
          }
          document.body.style.overflow = 'hidden'
        } else {
          throw new Error(response.data.message || 'Ismeretlen hiba')
        }
      } catch (err) {
        console.error('Hiba a számla betöltésekor:', err)
        this.error = err.response?.data?.message || err.message || 'Hiba történt a számla részleteinek betöltése során.'
      } finally {
        this.loadingDetail = null
      }
    },
    
    async downloadPdf(invoice) {
      this.downloading = invoice.id
      this.error = null
      
      try {
        const response = await AuthService.api.get(`/user/personal-invoices/${invoice.id}/download`, {
          responseType: 'blob'
        })
        
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `szamla_${invoice.invoiceNumber}.pdf`
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        console.error('Hiba a PDF letöltésekor:', err)
        if (err.response?.status === 401) {
          this.error = 'Nincs bejelentkezve. Kérlek, jelentkezz be újra.'
        } else if (err.response?.data instanceof Blob) {
          const text = await err.response.data.text()
          try {
            const errorData = JSON.parse(text)
            this.error = errorData.message || 'Hiba a PDF letöltésekor'
          } catch {
            this.error = 'Hiba a PDF letöltésekor'
          }
        } else {
          this.error = err.response?.data?.message || err.message || 'Hiba történt a PDF letöltése során.'
        }
      } finally {
        this.downloading = null
      }
    },
    
    goToPage(page) {
      if (page < 1 || page > this.pagination.last_page) return
      this.pagination.current_page = page
      this.loadInvoices()
    },
    
    resetFilters() {
      this.filters = {
        year: '',
        status: '',
        search: ''
      }
      this.pagination.current_page = 1
      this.loadInvoices()
    },
    
    closeModal() {
      this.selectedInvoice = null
    }
  },
  
  watch: {
    'filters.year'() {
      this.pagination.current_page = 1
      this.loadInvoices()
    },
    'filters.status'() {
      this.pagination.current_page = 1
      this.loadInvoices()
    }
  }
}
</script>

<style scoped>
.personal-invoices {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
}

.content-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
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
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-group label {
  font-size: 0.7rem;
  font-weight: 500;
  color: #666;
  text-transform: uppercase;
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  background: white;
  cursor: pointer;
  min-width: 120px;
}

.filter-select:focus {
  outline: none;
  border-color: #f0a24a;
}

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
  width: 200px;
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

.btn-primary {
  padding: 0.5rem 1rem;
  background: #1fa317;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 0.85rem;
}

.btn-primary:hover {
  background: #158a0f;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background: #e9ecef;
  color: #495057;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-secondary:hover {
  background: #dee2e6;
}

/* Loading state */
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

/* Error state */
.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  background: #f9f9f9;
  border-radius: 12px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.6;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  color: #333;
}

.empty-state p {
  margin: 0;
  font-size: 0.85rem;
}

/* Table styles */
.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #eee;
  margin-bottom: 1rem;
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

.data-table tbody tr:hover {
  background: #fef9ef;
}

.data-table .overdue-row {
  background: #fff7e6;
}

.invoice-number {
  font-family: monospace;
  font-weight: 500;
}

.amount {
  font-weight: 600;
  color: #2d3748;
}

.overdue-date {
  color: #e53e3e;
  font-weight: 500;
}

/* Status badges */
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.status-generated {
  background: #fff3cd;
  color: #856404;
}

.status-paid {
  background: #d4edda;
  color: #155724;
}

.status-expired {
  background: #f8d7da;
  color: #721c24;
}

.status-default {
  background: #e9ecef;
  color: #495057;
}

/* Action buttons */
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

.btn-view:hover:not(:disabled) {
  background: #bbdef5;
}

.btn-pdf {
  background: #e8eaf6;
  color: #3f51b5;
  padding: 0.4rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.btn-pdf:hover:not(:disabled) {
  background: #c5cae9;
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding: 1rem;
}

.btn-pagination {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-pagination:hover:not(:disabled) {
  background: #f0a24a;
  color: white;
  border-color: #f0a24a;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.page-number {
  min-width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.page-number:hover {
  background: #f0a24a;
  color: #7b2c2c;
}

.page-number.active {
  background: #f0a24a;
  color: #7b2c2c;
  font-weight: 600;
}

.page-dots {
  padding: 0 0.25rem;
  color: #aaa;
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
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
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
  font-family: monospace;
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
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #eee;
  background: #fafafa;
}

.btn-cancel {
  padding: 0.5rem 1rem;
  background: #e9ecef;
  color: #495057;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-cancel:hover {
  background: #dee2e6;
}

/* Detail grid */
.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 12px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item .label {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: #888;
  letter-spacing: 0.5px;
}

.detail-item .value {
  font-size: 0.9rem;
  font-weight: 500;
  color: #333;
}

.detail-item .value.overdue {
  color: #e53e3e;
}

.detail-item .value.amount {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1fa317;
}

/* Orders table */
.orders-section h3 {
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
  color: #8a1212;
  font-weight: 600;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.orders-table th,
.orders-table td {
  padding: 0.6rem;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}

.orders-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #666;
}

.total-row td {
  border-top: 2px solid #e0e0e0;
  padding-top: 0.6rem;
  font-weight: 600;
}

.order-status {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 20px;
  font-size: 0.65rem;
  font-weight: 500;
}

.order-active {
  background: #e3f2fd;
  color: #1976d2;
}

.order-paid {
  background: #d4edda;
  color: #155724;
}

.order-cancelled {
  background: #ffebee;
  color: #c62828;
}

.order-default {
  background: #e9ecef;
  color: #495057;
}

.no-orders {
  text-align: center;
  padding: 2rem;
  color: #888;
  background: #f8f9fa;
  border-radius: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .personal-invoices {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .title {
    font-size: 1.25rem;
  }

  .header-controls {
    width: 100%;
    flex-direction: column;
    align-items: stretch;
  }

  .filter-group {
    width: 100%;
  }

  .filter-select {
    width: 100%;
  }

  .search-box {
    width: 100%;
  }

  .search-input {
    width: 100%;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
    text-align: center;
  }

  .actions-group {
    flex-direction: column;
  }

  .pagination {
    flex-wrap: wrap;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }
}

@media (max-width: 480px) {
  .page-numbers {
    display: none;
  }

  .btn-pagination {
    flex: 1;
    text-align: center;
  }
}
</style>