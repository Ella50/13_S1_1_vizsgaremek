<template>
  <div class="invoices-wrap">
    <div class="invoices-header">
      <h1>Számláim</h1>
      
      <div class="filters">
        <div class="filter-group">
          <label>Év</label>
          <select v-model="filters.year">
            <option value="">Összes év</option>
            <option v-for="year in availableYears" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Státusz</label>
          <select v-model="filters.status">
            <option value="">Összes</option>
            <option value="Generálva">Generálva</option>
            <option value="Fizetve">Fizetve</option>
            <option value="Lejárt">Lejárt</option>
          </select>
        </div>
        
        <div class="filter-group search">
          <label>Keresés</label>
          <input 
            type="text" 
            v-model="filters.search" 
            placeholder="Számlaszám..."
            @keyup.enter="loadInvoices"
          />
        </div>
        
        <button class="btn-filter" @click="loadInvoices">
          Szűrés
        </button>
        <button class="btn-reset" @click="resetFilters" v-if="hasActiveFilters">
          Összes
        </button>
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Betöltés...</p>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="loadInvoices" class="btn-retry">Újrapróbálkozás</button>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="invoices.length === 0" class="empty-state">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <h3>Nincs számla</h3>
      <p>Még nem érkezett számla a rendszerbe.</p>
    </div>
    
    <!-- Invoices table -->
    <div v-else class="invoices-table-wrap">
      <table class="invoices-table">
        <thead>
          <tr>
            <th>Számlaszám</th>
            <th>Kelte</th>
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
            <td class="invoice-number">
              {{ invoice.invoiceNumber }}
            </td>
            <td>{{ formatDate(invoice.issueDate) }}</td>
            <td :class="{ 'overdue-date': isOverdue(invoice) }">
              {{ formatDate(invoice.dueDate) }}
            </td>
            <td class="amount">{{ formatAmount(invoice.totalAmount) }}</td>
            <td>
              <span class="status-badge" :data-status="invoice.invoiceStatus">
                {{ getStatusText(invoice.invoiceStatus) }}
              </span>
            </td>
            <td class="actions">
              <button 
                class="btn-view" 
                @click="openInvoice(invoice.id)"
                :disabled="loadingDetail === invoice.id"
              >
                {{ loadingDetail === invoice.id ? 'Betöltés...' : 'Részletek' }}
              </button>
              <button 
                class="btn-pdf" 
                @click="downloadPdf(invoice)"
                :disabled="downloading === invoice.id"
              >
                {{ downloading === invoice.id ? '...' : 'PDF' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
      <div class="pagination" v-if="pagination.last_page > 1">
        <button 
          @click="goToPage(pagination.current_page - 1)" 
          :disabled="pagination.current_page === 1"
          class="page-btn"
        >
          &laquo; Előző
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
          class="page-btn"
        >
          Következő &raquo;
        </button>
      </div>
    </div>
    
    <!-- Invoice Detail Modal -->
    <div v-if="selectedInvoice" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ selectedInvoice.invoiceNumber }}</h2>
          <button class="close-btn" @click="closeModal">×</button>
        </div>
        
        <div class="invoice-details">
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
              <span class="label">Fizetési mód:</span>
              <span class="value">{{ selectedInvoice.paymentMethod || 'Bankkártya' }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Státusz:</span>
              <span class="value">
                <span class="status-badge" :data-status="selectedInvoice.invoiceStatus">
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
            <div class="orders-table-wrap">
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
                    <td>{{ formatAmount(order.amount) }}</td>
                    <td>
                      <span class="order-status" :data-status="order.orderStatus">
                        {{ order.orderStatus }}
                      </span>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="total-row">
                    <td colspan="2"><strong>Összesen:</strong></td>
                    <td><strong>{{ formatAmount(selectedInvoice.totalAmount) }}</strong></td>
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
            {{ downloading === selectedInvoice.id ? 'Generálás...' : 'PDF letöltése' }}
          </button>
          <button class="btn-close" @click="closeModal">Bezárás</button>
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
    // Available years (max 5 év visszamenőleg)
    availableYears() {
      const currentYear = new Date().getFullYear()
      const years = []
      for (let i = currentYear; i >= currentYear - 5; i--) {
        years.push(i)
      }
      return years
    },
    
    // Check if any filter is active
    hasActiveFilters() {
      return this.filters.year || this.filters.status || this.filters.search
    },
    
    // Visible page numbers for pagination
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
    // Helper functions
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
      // A backend-en nettó összeg van, a felhasználónak bruttót (27% ÁFA) mutatunk
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
        
        console.log('API hívás paraméterekkel:', params)
        
        const response = await AuthService.api.get('/user/personal-invoices', { params })
        
        console.log('API válasz:', response.data)
        
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
        
        console.log('Számla részletek:', response.data)
        
        if (response.data.success) {
          const baseInvoice = this.invoices.find(i => i.id === invoiceId)
          this.selectedInvoice = {
            ...baseInvoice,
            orders: response.data.data
          }
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
        
        // Create blob and download
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `szamla_${invoice.invoiceNumber}.pdf`
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        console.log('PDF letöltve:', invoice.invoiceNumber)
      } catch (err) {
        console.error('Hiba a PDF letöltésekor:', err)
        
        if (err.response?.status === 401) {
          this.error = 'Nincs bejelentkezve. Kérlek, jelentkezz be újra.'
        } else if (err.response?.data instanceof Blob) {
          // Ha a hiba blob formátumban érkezik
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
    // Watch for filter changes (reset to page 1)
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
.invoices-wrap {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.invoices-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 24px;
}

.invoices-header h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.filters {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 12px;
  font-weight: 500;
  color: #666;
}

.filter-group label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  transition: all 0.2s;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.filter-group.search input {
  min-width: 180px;
}

.btn-filter,
.btn-reset {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-filter {
  background: #4299e1;
  color: white;
}

.btn-filter:hover {
  background: #3182ce;
}

.btn-reset {
  background: #edf2f7;
  color: #4a5568;
}

.btn-reset:hover {
  background: #e2e8f0;
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #666;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #4299e1;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error state */
.error-state {
  text-align: center;
  padding: 60px 20px;
  background: #fff5f5;
  border-radius: 12px;
  color: #c53030;
}

.error-state p {
  margin-bottom: 16px;
}

.btn-retry {
  padding: 8px 20px;
  background: #c53030;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.btn-retry:hover {
  background: #9b2c2c;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #a0aec0;
}

.empty-state svg {
  margin-bottom: 20px;
  color: #cbd5e0;
}

.empty-state h3 {
  font-size: 1.25rem;
  margin-bottom: 8px;
  color: #718096;
}

.empty-state p {
  color: #a0aec0;
}

/* Table styles */
.invoices-table-wrap {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow-x: auto;
}

.invoices-table {
  width: 100%;
  border-collapse: collapse;
}

.invoices-table th,
.invoices-table td {
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid #edf2f7;
}

.invoices-table th {
  background: #f8fafc;
  font-weight: 600;
  font-size: 13px;
  color: #4a5568;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.invoices-table tbody tr:hover {
  background: #fafafa;
}

.invoices-table .overdue-row {
  background: #fffaf0;
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

/* Status badge */
.status-badge {
  display: inline-flex;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge[data-status="Generálva"] {
  background: #fef3c7;
  color: #d97706;
}

.status-badge[data-status="Fizetve"] {
  background: #c6f6d5;
  color: #276749;
}

.status-badge[data-status="Lejárt"] {
  background: #fed7d7;
  color: #c53030;
}

/* Action buttons */
.actions {
  display: flex;
  gap: 8px;
  white-space: nowrap;
}

.btn-view,
.btn-pdf {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-view {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-view:hover:not(:disabled) {
  background: #cbd5e0;
}

.btn-pdf {
  background: #4299e1;
  color: white;
}

.btn-pdf:hover:not(:disabled) {
  background: #3182ce;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #edf2f7;
}

.page-btn {
  padding: 8px 16px;
  background: #edf2f7;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: #e2e8f0;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 6px;
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
  font-weight: 500;
  transition: all 0.2s;
}

.page-number:hover {
  background: #edf2f7;
}

.page-number.active {
  background: #4299e1;
  color: white;
}

.page-dots {
  padding: 0 4px;
  color: #a0aec0;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #edf2f7;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-family: monospace;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #a0aec0;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #4a5568;
}

.invoice-details {
  padding: 24px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item .label {
  font-size: 11px;
  text-transform: uppercase;
  color: #718096;
  letter-spacing: 0.5px;
}

.detail-item .value {
  font-size: 16px;
  font-weight: 500;
  color: #2d3748;
}

.detail-item .value.overdue {
  color: #e53e3e;
}

.detail-item .value.amount {
  font-size: 20px;
  font-weight: 700;
  color: #2c7a7b;
}

/* Orders table */
.orders-section h3 {
  margin: 0 0 16px 0;
  font-size: 1.125rem;
  color: #4a5568;
}

.orders-table-wrap {
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
}

.orders-table th,
.orders-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #edf2f7;
}

.orders-table th {
  background: #f8fafc;
  font-weight: 600;
  font-size: 12px;
  color: #4a5568;
}

.total-row td {
  border-top: 2px solid #e2e8f0;
  padding-top: 12px;
  font-weight: 600;
}

.order-status {
  display: inline-flex;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
}

.order-status[data-status="Rendelve"] {
  background: #e6f7ff;
  color: #0077b6;
}

.order-status[data-status="Fizetve"] {
  background: #c6f6d5;
  color: #276749;
}

.no-orders {
  text-align: center;
  padding: 40px;
  color: #a0aec0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 24px;
  border-top: 1px solid #edf2f7;
}

.btn-close {
  padding: 8px 20px;
  background: #edf2f7;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-close:hover {
  background: #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
  .invoices-wrap {
    padding: 16px;
  }
  
  .invoices-header {
    flex-direction: column;
  }
  
  .filters {
    width: 100%;
  }
  
  .filter-group {
    flex: 1;
  }
  
  .filter-group.search input {
    min-width: auto;
  }
  
  .invoices-table th,
  .invoices-table td {
    padding: 10px 12px;
  }
  
  .actions {
    flex-direction: column;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .modal {
    width: 95%;
    margin: 10px;
  }
}
</style>