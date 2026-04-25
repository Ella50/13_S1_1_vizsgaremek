<template>
  <div class="personal-orders">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Személyes rendelések</h1>
        
        <div class="header-controls">
          <div class="month-selector">
            <button 
              @click="previousMonth" 
              class="nav-btn"
              :disabled="!canGoToPreviousMonth"
            >
              ←
            </button>
            
            <button @click="showMonthPicker = true" class="current-month">
              {{ currentMonthDisplay }}
            </button>
            
            <button 
              @click="nextMonth" 
              class="nav-btn"
              :disabled="!canGoToNextMonth"
            >
              →
            </button>
          </div>

          <button @click="refreshData" class="btn-primary" :disabled="loading">
            {{ loading ? 'Betöltés...' : 'Frissítés' }}
          </button>
        </div>
      </div>

      <!-- Hónap választó modal -->
      <div v-if="showMonthPicker" class="modal-overlay" @click.self="showMonthPicker = false">
        <div class="modal modal-small">
          <div class="modal-header">
            <h2>Válassz hónapot</h2>
            <button @click="showMonthPicker = false" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <div class="months-grid">
              <button
                v-for="month in availableMonths"
                :key="`${month.year}-${month.month}`"
                @click="selectMonth(month)"
                :class="{ active: isCurrentMonth(month) }"
                class="month-btn"
              >
                {{ month.display }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Rendelések betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="loadOrders" class="btn-secondary">Újrapróbálkozás</button>
      </div>

      <!-- Rendelések táblázat -->
      <div v-else-if="availableDates.length > 0" class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>Dátum</th>
              <th>Leves</th>
              <th>A opció</th>
              <th>B opció</th>
              <th>Választás</th>
              <th>Státusz</th>
              <th>Művelet</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="date in availableDates" 
              :key="date.date"
              :class="{
                'cancelled-order': date.order_status === 'Lemondva',
                'active-order': date.order_status === 'Rendelve',
                'today': isToday(date.date),
                'deadline-passed': !canModifyOrder(date.date) && date.has_order
              }"
            >
              <!-- Dátum -->
              <td class="date-cell">
                <div class="date-display">
                  <span class="day-number">{{ getDayNumber(date.date) }}</span>
                  <span class="month-name">{{ getMonthName(date.date) }}</span>
                </div>
              </td>

              <!-- Leves -->
              <td class="meal-cell">
                <div class="meal-name" :class="{ 'allergen-meal': hasAllergenWarning(date, 'Leves') }">
                  {{ date.menu.soup?.mealName || 'Nincs leves' }}
                  <span 
                    v-if="hasAllergenWarning(date, 'Leves')"
                    class="allergen-warning"
                    :title="getAllergenTooltip(date, 'Leves')"
                  >
                    ⚠️
                  </span>
                </div>
              </td>

              <!-- A opció -->
              <td class="meal-cell">
                <div class="meal-name" :class="{ 'allergen-meal': hasAllergenWarning(date, 'A opció') }">
                  {{ date.menu.optionA?.mealName || 'Nincs A opció' }}
                  <span 
                    v-if="hasAllergenWarning(date, 'A opció')"
                    class="allergen-warning"
                    :title="getAllergenTooltip(date, 'A opció')"
                  >
                    ⚠️
                  </span>
                </div>
                <div class="meal-price" v-if="date.menu.optionA?.price">
                  {{ date.menu.optionA.price }} Ft
                </div>
              </td>

              <!-- B opció -->
              <td class="meal-cell">
                <div class="meal-name" :class="{ 'allergen-meal': hasAllergenWarning(date, 'B opció') }">
                  {{ date.menu.optionB?.mealName || 'Nincs B opció' }}
                  <span 
                    v-if="hasAllergenWarning(date, 'B opció')"
                    class="allergen-warning"
                    :title="getAllergenTooltip(date, 'B opció')"
                  >
                    ⚠️
                  </span>
                </div>
                <div class="meal-price" v-if="date.menu.optionB?.price">
                  {{ date.menu.optionB.price }} Ft
                </div>
              </td>

              <!-- Választás -->
              <td class="choice-cell">
                <template v-if="userInfo?.hasDiabetes">
                  <span class="fixed-choice">A</span>
                </template>
                <template v-else>
                  <div class="choice-buttons">
                    <button 
                      v-if="canShowOption(date, 'A')"
                      @click="handleOptionClick(date, 'A')"
                      :class="{
                        active: isOptionSelected(date, 'A'),
                        'selected-order': date.order_status === 'Rendelve' && date.selected_option === 'A' && !canModifyOrder(date.date)
                      }"
                      class="choice-btn"
                      :disabled="isOptionDisabled(date, 'A')"
                    >
                      A
                    </button>
                    <span v-else class="no-option">-</span>
                    
                    <button 
                      v-if="canShowOption(date, 'B')"
                      @click="handleOptionClick(date, 'B')"
                      :class="{
                        active: isOptionSelected(date, 'B'),
                        'selected-order': date.order_status === 'Rendelve' && date.selected_option === 'B' && !canModifyOrder(date.date)
                      }"
                      class="choice-btn"
                      :disabled="isOptionDisabled(date, 'B')"
                    >
                      B
                    </button>
                    <span v-else class="no-option">-</span>
                  </div>
                </template>
              </td>

              <!-- Státusz -->
              <td class="status-cell">
                <span :class="['status-badge', getStatusClass(date)]">
                  {{ getStatusText(date) }}
                </span>
              </td>

              <!-- Művelet cella -->
              <td class="action-cell">
                <div class="actions-inner">
                  <template v-if="date.order_status === 'Rendelve'">
                    <button 
                      v-if="canModifyOrder(date.date)"
                      @click="changeOrderOption(date, date.selected_option === 'A' ? 'B' : 'A')"
                      class="btn-action btn-edit"
                      :title="'Módosítás (határidő: ' + getDeadlineInfo(date.date) + ')'"
                    >
                      Módosít
                    </button>
                    <button 
                      v-if="canCancelOrder(date.date)"
                      @click="cancelOrder(date.order_id)"
                      class="btn-action btn-cancel"
                      :title="'Lemondás (határidő: ' + getCancelDeadlineInfo(date.date) + ')'"
                    >
                      Lemond
                    </button>
                    <span v-else class="disabled-action">Lejárt</span>
                  </template>
                  
                  <template v-else>
                    <button 
                      v-if="canModifyOrder(date.date)"
                      @click="placeOrder(date)"
                      class="btn-action btn-order"
                      :disabled="!canPlaceOrder(date)"
                      :title="'Rendelés (határidő: ' + getDeadlineInfo(date.date) + ')'"
                    >
                      Rendelés
                    </button>
                    <span v-else class="disabled-action">Lejárt</span>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Nincs adat -->
      <div v-else class="empty-state">
        <p>Nincsenek elérhető rendelések ebben a hónapban</p>
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
  </div>
</template>

<script>
import AuthService from '@/services/authService'

export default {
  name: 'PersonalOrders',
  
  data() {
    return {
      userInfo: null,
      loading: true,
      error: null,
      availableDates: [],
      selectedMonth: null,
      showMonthPicker: false,
      tempSelections: {},
      
      // Alert állapotok
      alertVisible: false,
      alertMessage: '',
      alertTitle: '',
      alertType: 'success',
      alertTimeout: null,
      
      // Confirm állapotok
      confirmVisible: false,
      confirmMessage: '',
      confirmTitle: '',
      confirmResolver: null
    }
  },
  
  watch: {
    showMonthPicker(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    }
  },
  
  computed: {
    availableMonths() {
      const months = []
      const currentYear = new Date().getFullYear()
      
      const now = new Date()
      const startYear = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1
      
      for (let year = startYear - 1; year <= startYear + 1; year++) {
        for (let month = 9; month <= 12; month++) {
          months.push({
            year,
            month,
            display: this.getMonthNameFull(month) + ' ' + year
          })
        }
        for (let month = 1; month <= 6; month++) {
          months.push({
            year: year + 1,
            month,
            display: this.getMonthNameFull(month) + ' ' + (year + 1)
          })
        }
      }
      
      return months
    },
    
    currentMonthDisplay() {
      if (!this.selectedMonth) return 'Válassz hónapot'
      return this.getMonthNameFull(this.selectedMonth.month) + ' ' + this.selectedMonth.year
    },
    
    canGoToPreviousMonth() {
      if (!this.selectedMonth) return false
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      return index > 0
    },
    
    canGoToNextMonth() {
      if (!this.selectedMonth) return false
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      return index < this.availableMonths.length - 1
    },
  },
  
  mounted() {
    this.init()
  },
  
  beforeUnmount() {
    if (this.alertTimeout) {
      clearTimeout(this.alertTimeout)
    }
    document.body.style.overflow = ''
  },
  
  methods: {
    // Alert metódusok
    showAlert({ message, type = 'success', title = '' }) {
      if (this.alertTimeout) {
        clearTimeout(this.alertTimeout)
      }
      
      this.alertMessage = message
      this.alertType = type
      this.alertTitle = title
      this.alertVisible = true
      
      this.alertTimeout = setTimeout(() => {
        this.alertVisible = false
      }, 3000)
    },
    
    // Confirm metódusok
    showConfirm({ message, title = '' }) {
      this.confirmMessage = message
      this.confirmTitle = title
      this.confirmVisible = true
      
      return new Promise((resolve) => {
        this.confirmResolver = resolve
      })
    },
    
    confirmOk() {
      this.confirmVisible = false
      if (this.confirmResolver) {
        this.confirmResolver(true)
        this.confirmResolver = null
      }
    },
    
    confirmCancel() {
      this.confirmVisible = false
      if (this.confirmResolver) {
        this.confirmResolver(false)
        this.confirmResolver = null
      }
    },
    
    async init() {
      await Promise.all([
        this.loadUserInfo(),
        this.initMonth()
      ])
      await this.loadOrders()
    },
    
    async loadUserInfo() {
      try {
        const response = await AuthService.api.get('/user/me')
        this.userInfo = response.data?.data || response.data
      } catch (error) {
        console.error('Felhasználói adatok betöltése sikertelen:', error)
        this.userInfo = null
      }
    },
    
    initMonth() {
      const now = new Date()
      const currentMonth = now.getMonth() + 1
      const currentYear = now.getFullYear()
      
      if (currentMonth >= 7 && currentMonth <= 8) {
        this.selectedMonth = { year: currentYear, month: 9 }
      } else {
        this.selectedMonth = { year: currentYear, month: currentMonth }
      }
    },
    
    async loadOrders() {
      if (!this.selectedMonth) return
      
      this.loading = true
      this.error = null
      
      try {
        const response = await AuthService.api.get(
          `/user/personal-orders/month/${this.selectedMonth.year}/${this.selectedMonth.month}`
        )
        
        if (response.data?.success) {
          this.availableDates = response.data.data || []
        }
      } catch (err) {
        console.error('Hiba a rendelések betöltésekor:', err)
        this.error = 'Nem sikerült betölteni a rendeléseket'
        this.showAlert({
          message: this.error,
          type: 'error'
        })
      } finally {
        this.loading = false
      }
    },
    
    previousMonth() {
      if (!this.canGoToPreviousMonth) return
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      this.selectMonth(this.availableMonths[index - 1])
    },
    
    nextMonth() {
      if (!this.canGoToNextMonth) return
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      this.selectMonth(this.availableMonths[index + 1])
    },
    
    selectMonth(month) {
      this.selectedMonth = { year: month.year, month: month.month }
      this.showMonthPicker = false

      document.body.style.overflow = ''
      this.tempSelections = {}
      this.loadOrders()
    },
    
    isCurrentMonth(month) {
      return this.selectedMonth?.year === month.year && 
             this.selectedMonth?.month === month.month
    },
    
    getDayNumber(dateString) {
      return new Date(dateString).getDate()
    },
    
    getMonthName(dateString) {
      const months = ['Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún', 'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec']
      return months[new Date(dateString).getMonth()]
    },
    
    getMonthNameFull(month) {
      const months = [
        'Január', 'Február', 'Március', 'Április', 'Május', 'Június',
        'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'
      ]
      return months[month - 1]
    },
    
    isToday(dateString) {
      const today = new Date().toISOString().split('T')[0]
      return dateString === today
    },
    
    canModifyOrder(dateString) {
      const orderDate = new Date(dateString)
      const now = new Date()
      let deadline = this.getPreviousWorkingDay(orderDate)
      deadline.setHours(10, 0, 0, 0)
      return now <= deadline
    },
    
    canCancelOrder(dateString) {
      return this.canModifyOrder(dateString)
    },
    
    getPreviousWorkingDay(date) {
      const result = new Date(date)
      const dayOfWeek = date.getDay()
      
      if (dayOfWeek === 1) {
        result.setDate(date.getDate() - 3)
      } else if (dayOfWeek === 0) {
        result.setDate(date.getDate() - 2)
      } else {
        result.setDate(date.getDate() - 1)
      }
      return result
    },
    
    getDeadlineInfo(dateString) {
      const date = new Date(dateString)
      const deadline = this.getPreviousWorkingDay(date)
      deadline.setHours(10, 0, 0, 0)
      return deadline.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }) + ' óra'
    },
    
    getCancelDeadlineInfo(dateString) {
      return this.getDeadlineInfo(dateString)
    },
    
    canShowOption(date, option) {
      if (option === 'A') return !!date.menu.optionA
      if (option === 'B') return !!date.menu.optionB
      return false
    },
    
    isOptionSelected(date, option) {
      if (date.order_status === 'Rendelve') {
        return date.selected_option === option
      }
      return this.tempSelections[date.date] === option
    },
    
    isOptionDisabled(date, option) {
      if (this.userInfo?.hasDiabetes) return true
      if (date.order_status === 'Rendelve' && !this.canModifyOrder(date.date)) return true
      if ((!date.has_order || date.order_status === 'Lemondva') && !this.canModifyOrder(date.date)) return true
      return false
    },
    
    handleOptionClick(date, option) {
      if (date.order_status === 'Rendelve' && this.canModifyOrder(date.date)) {
        this.changeOrderOption(date, option)
      } else if ((!date.has_order || date.order_status === 'Lemondva') && this.canModifyOrder(date.date)) {
        this.selectOptionForNewOrder(date, option)
      }
    },
    
    canPlaceOrder(date) {
      if (this.userInfo?.hasDiabetes) return true
      return !!this.tempSelections[date.date]
    },
    
    hasAllergenWarning(date, mealType) {
      return date.allergen_warnings?.some(w => w.meal === mealType) || false
    },
    
    getAllergenTooltip(date, mealType) {
      const warning = date.allergen_warnings?.find(w => w.meal === mealType)
      if (!warning) return ''
      return 'Allergén figyelmeztetés!'
    },
    
    selectOptionForNewOrder(date, option) {
      this.tempSelections[date.date] = option
    },
    
    async placeOrder(date) {
      let selectedOption = this.tempSelections[date.date]
      
      if (this.userInfo?.hasDiabetes) {
        selectedOption = 'A'
      }
      
      if (!selectedOption) {
        this.showAlert({
          message: 'Kérlek válassz opciót!',
          type: 'warning'
        })
        return
      }
      
      if (!this.canModifyOrder(date.date)) {
        this.showAlert({
          message: 'A rendelési határidő lejárt!',
          type: 'error'
        })
        return
      }
      
      const confirmed = await this.showConfirm({
        title: 'Rendelés leadása',
        message: `Biztosan rendelni szeretnéd a(z) ${selectedOption} opciót ${this.formatDate(date.date)}-ra?`
      })
      
      if (!confirmed) return
      
      try {
        let response
        
        if (date.has_order && date.order_status === 'Lemondva' && date.order_id) {
          response = await AuthService.api.post(`/user/personal-orders/${date.order_id}/reactivate`, {
            selectedOption
          })
        } else {
          response = await AuthService.api.post('/user/personal-orders', {
            date: date.date,
            menuitems_id: date.menu_item_id,
            selectedOption
          })
        }
        
        if (response.data?.success) {
          this.showAlert({
            message: 'Rendelés sikeresen leadva!',
            type: 'success'
          })
          delete this.tempSelections[date.date]
          await this.loadOrders()
        } else {
          this.showAlert({
            message: response.data?.message || 'Ismeretlen hiba történt',
            type: 'error'
          })
        }
      } catch (err) {
        console.error('Rendelési hiba:', err)
        this.showAlert({
          message: err.response?.data?.message || 'Hiba történt a rendelés során',
          type: 'error'
        })
      }
    },
    
    async changeOrderOption(date, newOption) {
      if (!date.has_order || !date.order_id) return
      if (date.selected_option === newOption) return
      if (!this.canModifyOrder(date.date)) {
        this.showAlert({
          message: 'A módosítási határidő lejárt!',
          type: 'error'
        })
        return
      }
      
      const confirmed = await this.showConfirm({
        title: 'Opció módosítása',
        message: `Biztosan módosítani szeretnéd a(z) ${newOption} opcióra?`
      })
      
      if (!confirmed) return
      
      try {
        const response = await AuthService.api.patch(`/user/personal-orders/${date.order_id}/update-option`, {
          selectedOption: newOption
        })
        
        if (response.data?.success) {
          this.showAlert({
            message: 'Opció sikeresen módosítva!',
            type: 'success'
          })
          await this.loadOrders()
        } else {
          this.showAlert({
            message: response.data?.message || 'Hiba történt a módosítás során',
            type: 'error'
          })
        }
      } catch (err) {
        console.error('Módosítási hiba:', err)
        this.showAlert({
          message: err.response?.data?.message || 'Hiba történt a módosítás során',
          type: 'error'
        })
      }
    },
    
    async cancelOrder(orderId) {
      const date = this.availableDates.find(d => d.order_id === orderId)
      if (!date) return
      
      if (!this.canCancelOrder(date.date)) {
        this.showAlert({
          message: 'A lemondási határidő lejárt! (aznap 8:00-ig lehet lemondani)',
          type: 'error'
        })
        return
      }
      
      const confirmed = await this.showConfirm({
        title: 'Rendelés lemondása',
        message: 'Biztosan le szeretnéd mondani ezt a rendelést?'
      })
      
      if (!confirmed) return
      
      try {
        const response = await AuthService.api.delete(`/user/personal-orders/${orderId}/cancel`)
        
        if (response.data?.success) {
          this.showAlert({
            message: 'Rendelés sikeresen lemondva!',
            type: 'success'
          })
          if (this.tempSelections[date.date]) {
            delete this.tempSelections[date.date]
          }
          await this.loadOrders()
        } else {
          this.showAlert({
            message: response.data?.message || 'Hiba történt a lemondás során',
            type: 'error'
          })
        }
      } catch (err) {
        console.error('Lemondási hiba:', err)
        this.showAlert({
          message: err.response?.data?.message || 'Hiba történt a lemondás során',
          type: 'error'
        })
      }
    },
    
    getStatusClass(date) {
      if (date.order_status === 'Rendelve') return 'ordered'
      if (date.order_status === 'Lemondva') return 'cancelled'
      if (!this.canModifyOrder(date.date)) return 'deadline-passed'
      return 'available'
    },
    
    getStatusText(date) {
      if (date.order_status === 'Rendelve') return 'Rendelve'
      if (date.order_status === 'Lemondva') return 'Rendelhető'
      if (!this.canModifyOrder(date.date)) return 'Lejárt'
      return 'Rendelhető'
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    
    refreshData() {
      this.loadOrders()
    }
  }
}
</script>

<style scoped>
.personal-orders {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
  position: relative;
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

.month-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 30px;
  padding: 0.25rem;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #8a1212;
  font-size: 1.2rem;
  cursor: pointer;
  border-radius: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.nav-btn:hover:not(:disabled) {
  background: #f0a24a;
  color: white;
}

.nav-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.current-month {
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  color: #333;
  font-weight: 500;
  cursor: pointer;
  min-width: 160px;
  text-align: center;
  font-size: 0.85rem;
}

.current-month:hover {
  color: #f0a24a;
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

.btn-primary:hover:not(:disabled) {
  background: #158a0f;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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
  max-width: 500px;
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

.months-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.month-btn {
  padding: 0.75rem 0.5rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  color: #333;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
  font-size: 0.85rem;
}

.month-btn:hover {
  background: #fef9ef;
  border-color: #f0a24a;
  transform: translateY(-1px);
}

.month-btn.active {
  background: #f0a24a;
  border-color: #f0a24a;
  color: #7b2c2c;
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
  margin-bottom: 1.5rem;
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


.data-table tr.active-order {
  background: #e8f5e9;
}

.data-table tr.today {
  background: #fff7e6;
}


.date-cell {
  width: 100px;
}

.date-display {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.day-number {
  font-size: 1.2rem;
  font-weight: 700;
  line-height: 1.2;
  color: #8a1212;
}

.month-name {
  font-size: 0.7rem;
  color: #888;
  text-transform: uppercase;
}


.meal-cell {
  min-width: 180px;
}

.meal-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  line-height: 1.4;
  color: #333;
}

.meal-name.allergen-meal {
  color: #e74c3c;
  font-weight: bold;
}

.meal-price {
  font-size: 0.7rem;
  color: #1fa317;
  font-weight: 600;
  margin-top: 0.25rem;
}

.allergen-warning {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  background: #fff3cd;
  border-radius: 50%;
  color: #856404;
  font-size: 0.7rem;
  cursor: help;
  padding-bottom: 2px;
}


.choice-cell {
  width: 120px;
  text-align: center;
}

.choice-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.choice-btn {
  width: 36px;
  height: 36px;
  border: 2px solid #e0e0e0;
  border-radius: 50%;
  background: white;
  color: #888;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.choice-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-color: #f0a24a;
  color: #f0a24a;
}

.choice-btn.active {
  background: #1fa317;
  border-color: #1fa317;
  color: white;
}

.choice-btn.selected-order {
  background: #27ae60;
  border-color: #27ae60;
  color: white;
  opacity: 0.8;
  cursor: default;
}

.choice-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.fixed-choice {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #1fa317;
  color: white;
  font-weight: 600;
}

.no-option {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #ccc;
  font-size: 0.8rem;
}


.status-cell {
  width: 100px;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
  text-align: center;
  min-width: 80px;
}

.status-badge.ordered {
  background: #d4edda;
  color: #155724;
}

.status-badge.cancelled {
  background: #d1ecf1;
  color: #0c5460;
}

.status-badge.deadline-passed {
  background: #fff3cd;
  color: #856404;
}

.status-badge.available {
  background: #d1ecf1;
  color: #0c5460;
}


.action-cell {
  width: 140px;
  text-align: center;
}

.actions-inner {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
}

.btn-action {
  padding: 0.4rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.7rem;
  font-weight: 500;
  transition: all 0.2s;
  white-space: nowrap;
}

.btn-order {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-order:hover:not(:disabled) {
  background: #c8e6c9;
  transform: translateY(-1px);
}

.btn-edit {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-edit:hover {
  background: #bbdef5;
  transform: translateY(-1px);
}

.btn-cancel {
  background: #ffebee;
  color: #c62828;
}

.btn-cancel:hover {
  background: #ffcdd2;
  transform: translateY(-1px);
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.disabled-action {
  color: #bbb;
  font-size: 0.7rem;
  font-style: italic;
}

/* Alert stílusok */
.alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;
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
  background: rgba(0, 0, 0, 0.5);
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

/* Reszponzív */
@media (max-width: 768px) {
  .personal-orders {
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
    justify-content: space-between;
  }

  .month-selector {
    flex: 1;
    justify-content: center;
  }

  .months-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .actions-inner {
    flex-direction: column;
  }

  .btn-action {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .choice-buttons {
    flex-direction: column;
    align-items: center;
  }

  .date-display {
    text-align: center;
  }

  .day-number {
    font-size: 1rem;
  }
}

.deadline-passed {
  opacity: 0.6;
}
</style>