<template>
  <div class="admin-prices">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Árak kezelése</h1>
        
        <div class="header-controls">
          <div class="filters-group">
            <select v-model="filters.userType" @change="fetchPrices" class="filter-select">
              <option value="">Összes felhasználó típus</option>
              <option value="Tanuló">Tanuló</option>
              <option value="Tanár">Tanár</option>
              <option value="Dolgozó">Dolgozó</option>
            </select>
            
            <select v-model="filters.priceCategory" @change="fetchPrices" class="filter-select">
              <option value="">Összes kategória</option>
              <option value="Normál">Normál</option>
              <option value="Kedvezményes">Kedvezményes</option>
            </select>
          </div>
          
          <button @click="openCreateModal" class="btn-primary">
            + Új ár
          </button>
        </div>
      </div>

      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Árak betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="fetchPrices" class="btn-secondary">Újra</button>
      </div>

      <div v-else>
        <transition name="fade">
          <div v-if="successMessage" class="success-message">
            {{ successMessage }}
          </div>
        </transition>

        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Felhasználó típus</th>
                <th>Ár kategória</th>
                <th>Összeg</th>
                <th>Érvényes innen</th>
                <th>Érvényes eddig</th>
                <th>Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="prices.length === 0">
                <td colspan="6" class="empty-row">
                  Nincsenek megjeleníthető árak
                </td>
              </tr>
              <tr v-for="price in prices" :key="price.id">
                <td>
                  <span :class="['type-badge', getTypeClass(price.userType)]">
                    {{ price.userType }}
                  </span>
                </td>
                <td>
                  <span :class="['category-badge', price.priceCategory === 'Kedvezményes' ? 'category-discount' : 'category-normal']">
                    {{ price.priceCategory }}
                  </span>
                </td>
                <td class="amount-cell">
                  <strong>{{ formatAmount(price.amount) }}</strong>
                </td>
                <td>{{ formatDate(price.validFrom) }}</td>
                <td>{{ price.validTo ? formatDate(price.validTo) : 'Nincs megadva' }}</td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button @click="openEditModal(price)" class="btn-action btn-edit" title="Szerkesztés">
                      ✎
                    </button>
                    <button 
                      @click="confirmDelete(price)" 
                      class="btn-action btn-delete" 
                      title="Törlés"
                    >
                      Törlés
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modalok -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ isEditing ? 'Ár szerkesztése' : 'Új ár létrehozása' }}</h2>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="savePrice" class="edit-form">
            <div class="form-grid">
              <div class="form-group">
                <label>Felhasználó típus *</label>
                <select v-model="form.userType" required>
                  <option value="">Válassz típust</option>
                  <option value="Tanuló">Tanuló</option>
                  <option value="Tanár">Tanár</option>
                  <option value="Admin">Admin</option>
                  <option value="Konyha">Konyha</option>
                  <option value="Dolgozó">Dolgozó</option>
                  <option value="Külsős">Külsős</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Ár kategória *</label>
                <select v-model="form.priceCategory" required>
                  <option value="">Válassz kategóriát</option>
                  <option value="Normál">Normál ár</option>
                  <option value="Kedvezményes">Kedvezményes ár</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Összeg (Ft) *</label>
                <input 
                  v-model.number="form.amount" 
                  type="number" 
                  min="0" 
                  max="1000000" 
                  step="10"
                  required
                  placeholder="Például: 890"
                >
              </div>
              
              <div class="form-group">
                <label>Érvényes innen *</label>
                <input v-model="form.validFrom" type="date" required>
              </div>
              
              <div class="form-group">
                <label>Érvényes eddig</label>
                <input v-model="form.validTo" type="date">
                <small class="form-hint">Üresen hagyva határozatlan ideig érvényes</small>
              </div>
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-cancel">Mégse</button>
              <button type="submit" :disabled="saving" class="btn-save">
                {{ saving ? 'Mentés...' : 'Mentés' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Törlés megerősítő modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal modal-small">
        <div class="modal-header">
          <h2>Ár törlése</h2>
          <button @click="closeDeleteModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body text-center">
          <h3 class="delete-title">Biztosan törölni szeretné?</h3>
          <p class="delete-message">
            A(z) <strong>{{ priceToDelete?.userType }} - {{ priceToDelete?.priceCategory }}</strong> 
            ({{ formatAmount(priceToDelete?.amount) }}) ár nem lesz visszaállítható.
          </p>
          
          <div v-if="deleteError" class="error-message-container">
            <div class="error-content">
              <p class="error-title">Nem törölhető!</p>
              <p class="error-message">{{ deleteError }}</p>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeDeleteModal" class="btn-cancel">Mégse</button>
            <button 
              v-if="!deleteError" 
              @click="deletePrice" 
              :disabled="deleting" 
              class="btn-delete-confirm"
            >
              {{ deleting ? "Törlés..." : "Törlés" }}
            </button>
            <button v-else @click="closeDeleteModal" class="btn-close-error">Bezárás</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'
import { addAlert } from '../../auth/AppAlert.vue'

export default {
  name: 'Prices',
  
  data() {
    return {
      prices: [],
      isLoading: true,
      error: '',
      successMessage: '',
      deleting: false,
      deleteError: '',
      priceToDelete: null,
      showDeleteModal: false,
      
      filters: {
        userType: '',
        priceCategory: '',
        active: ''
      },
      
      showModal: false,
      isEditing: false,
      saving: false,
      form: {
        id: null,
        userType: '',
        priceCategory: '',
        amount: null,
        validFrom: '',
        validTo: ''
      }
    }
  },
  
  mounted() {
    this.fetchPrices()
  },

  beforeDestroy() {
    document.body.style.overflow = ''
  },
  
  watch: {
    showModal(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden'
      } else if (!this.showDeleteModal) {
        document.body.style.overflow = ''
      }
    },
    showDeleteModal(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden'
      } else if (!this.showModal) {
        document.body.style.overflow = ''
      }
    }
  },
  
  methods: {
    async fetchPrices() {
      this.isLoading = true
      this.error = ''
      
      try {
        const params = {}
        if (this.filters.userType) params.userType = this.filters.userType
        if (this.filters.priceCategory) params.priceCategory = this.filters.priceCategory
        
        const response = await AuthService.api.get('/admin/prices', { params })
        
        if (response.data.success) {
          const now = new Date().toISOString().split('T')[0]
          this.prices = response.data.data.map(price => ({
            ...price,
            isActive: price.validFrom <= now && (!price.validTo || price.validTo >= now)
          }))
        } else {
          this.error = 'Hiba történt az árak betöltése során'
        }
      } catch (error) {
        console.error('Error fetching prices:', error)
        this.error = error.response?.data?.message || 'Hiba történt az árak betöltése során'
      } finally {
        this.isLoading = false
      }
    },
    
    openCreateModal() {
      this.isEditing = false
      this.form = {
        id: null,
        userType: '',
        priceCategory: '',
        amount: null,
        validFrom: new Date().toISOString().split('T')[0],
        validTo: ''
      }
      this.showModal = true
    },
    
    openEditModal(price) {
      this.isEditing = true
      this.form = {
        id: price.id,
        userType: price.userType,
        priceCategory: price.priceCategory,
        amount: price.amount,
        validFrom: price.validFrom,
        validTo: price.validTo || ''
      }
      this.showModal = true
    },
    
    closeModal() {
      this.showModal = false
      this.form = {
        id: null,
        userType: '',
        priceCategory: '',
        amount: null,
        validFrom: '',
        validTo: ''
      }
    },
    
    async savePrice() {
      if (!this.form.userType || !this.form.priceCategory || !this.form.amount || !this.form.validFrom) {
        addAlert({ message: 'Kérlek töltsd ki az összes kötelező mezőt!', type: 'error' })
        return
      }
      
      this.saving = true
      
      try {
        let response
        if (this.isEditing) {
          response = await AuthService.api.put(`/admin/prices/${this.form.id}`, this.form)
        } else {
          response = await AuthService.api.post('/admin/prices', this.form)
        }
        
        if (response.data.success) {
          addAlert({ 
            message: this.isEditing ? 'Ár sikeresen frissítve!' : 'Új ár sikeresen létrehozva!', 
            type: 'success' 
          })
          this.closeModal()
          this.fetchPrices()
        }
      } catch (error) {
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat()
          addAlert({ message: errors.join(', '), type: 'error' })
        } else {
          addAlert({ message: error.response?.data?.message || 'Hiba történt a mentés során', type: 'error' })
        }
      } finally {
        this.saving = false
      }
    },
    
    confirmDelete(price) {
      this.priceToDelete = price
      this.deleteError = ''
      this.showDeleteModal = true
    },
    
    closeDeleteModal() {
      this.showDeleteModal = false
      this.priceToDelete = null
      this.deleteError = ''
      this.deleting = false
    },
    
    async deletePrice() {
      this.deleting = true
      this.deleteError = ''
      
      try {
        const response = await AuthService.api.delete(`/admin/prices/${this.priceToDelete.id}`)
        
        if (response.data.success) {
          addAlert({ message: 'Ár sikeresen törölve!', type: 'success' })
          this.closeDeleteModal()
          this.fetchPrices()
        }
      } catch (error) {
        console.error('Delete error:', error)
        
        if (error.response?.status === 400 && error.response?.data?.code === 'HAS_ORDERS') {
          this.deleteError = error.response.data.message
        } else if (error.response?.status === 404) {
          this.deleteError = 'Az ár már nem létezik.'
          setTimeout(() => {
            this.closeDeleteModal()
            this.fetchPrices()
          }, 1500)
        } else {
          this.deleteError = error.response?.data?.message || 'Ez az ár nem törölhető, mert már vannak hozzá kapcsolódó rendelések.'
        }
      } finally {
        this.deleting = false
      }
    },
    
    formatAmount(amount) {
      return new Intl.NumberFormat('hu-HU', { 
        style: 'currency', 
        currency: 'HUF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount)
    },
    
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('hu-HU')
    },
    
    getTypeClass(userType) {
      const classes = {
        'Tanuló': 'type-student',
        'Tanár': 'type-teacher',
        'Admin': 'type-admin',
        'Konyha': 'type-kitchen',
        'Dolgozó': 'type-worker',
        'Külsős': 'type-external'
      }
      return classes[userType] || 'type-default'
    }
  }
}
</script>

<style scoped>
.admin-prices {
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

.filters-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  background: white;
  cursor: pointer;
  min-width: 180px;
}

.filter-select:focus {
  outline: none;
  border-color: #f0a24a;
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

.success-message {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
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

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
}

.type-student { background: #3498db; color: white; }
.type-teacher { background: #2ecc71; color: white; }
.type-admin { background: #e74c3c; color: white; }
.type-kitchen { background: #e67e22; color: white; }
.type-worker { background: #9b59b6; color: white; }
.type-external { background: #95a5a6; color: white; }
.type-default { background: #bdc3c7; color: white; }

.category-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.category-normal {
  background: #d4edda;
  color: #155724;
}

.category-discount {
  background: #fff3cd;
  color: #856404;
}

.amount-cell {
  font-weight: 600;
  color: #2c3e50;
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
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.btn-edit {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-edit:hover {
  background: #bbdef5;
}

.btn-delete {
  background: #ffebee;
  color: #c62828;
}

.btn-delete:hover {
  background: #ffcdd2;
}

.empty-row {
  text-align: center;
  color: #888;
  padding: 3rem !important;
}


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

.modal-small {
  max-width: 520px;
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

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  font-size: 0.8rem;
  color: #666;
  text-transform: uppercase;
}

.form-group input,
.form-group select {
  padding: 0.6rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #f0a24a;
}

.form-hint {
  font-size: 0.7rem;
  color: #888;
  margin-top: 0.25rem;
}

.btn-cancel {
  padding: 0.6rem 1.25rem;
  background: #e9ecef;
  color: #495057;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-cancel:hover {
  background: #dee2e6;
}

.btn-save {
  padding: 0.6rem 1.25rem;
  background: #1fa317;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-save:hover:not(:disabled) {
  background: #158a0f;
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-delete-confirm {
  padding: 0.6rem 1.25rem;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-delete-confirm:hover:not(:disabled) {
  background: #c82333;
}

.btn-delete-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-close-error {
  padding: 0.6rem 1.25rem;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-close-error:hover {
  background: #5a6268;
}



.delete-title {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.delete-message {
  margin: 0 0 1.5rem 0;
  color: #666;
  line-height: 1.5;
}

.error-message-container {
  text-align: left;
  background: #fff3f3;
  border: 1px solid #ffcdd2;
  border-radius: 12px;
  padding: 1rem;
  margin: 1rem 0;
}


.error-title {
  font-weight: bold;
  color: #721c24;
  margin: 0 0 0.5rem 0;
}

.error-message {
  color: #856404;
  margin: 0;
  line-height: 1.5;
}

.text-center {
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-prices {
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
  
  .filters-group {
    flex-direction: column;
  }
  
  .filter-select {
    width: 100%;
  }
  
  .btn-primary {
    width: 100%;
    text-align: center;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .modal {
    width: 95%;
    margin: 1rem;
  }
  
  .actions-group {
    flex-wrap: wrap;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .data-table th,
  .data-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }
  
  .btn-action {
    padding: 0.3rem 0.5rem;
    font-size: 0.7rem;
  }
}
</style>