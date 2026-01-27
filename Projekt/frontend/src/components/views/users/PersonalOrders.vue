<template>
  <div class="personal-orders-container">
    <!-- Fejléc -->
    <div class="page-header">
      <h1>Személyes rendeléseim</h1>
      <div class="header-actions">
        <button @click="showMonthPicker = !showMonthPicker" class="btn-month-picker">
          <i class="icon-calendar"></i>
          {{ selectedMonthDisplay }}
        </button>
        <button @click="refreshData" class="btn-refresh">
          <i class="icon-refresh"></i> Frissítés
        </button>
      </div>
    </div>

    <!-- Hónap kiválasztó -->
    <div v-if="showMonthPicker" class="month-picker">
      <div class="month-picker-header">
        <h3>Hónap kiválasztása</h3>
        <button @click="showMonthPicker = false" class="btn-close">
          <i class="icon-close"></i>
        </button>
      </div>
      <div class="month-grid">
        <button 
          v-for="(month, index) in availableMonths" 
          :key="index"
          @click="selectMonth(month)"
          :class="{ active: isMonthSelected(month) }"
          class="month-option"
        >
          {{ month.display }}
        </button>
      </div>
      <div class="year-selector">
        <button @click="changeYear(-1)" class="btn-year-nav">
          <i class="icon-chevron-left"></i>
        </button>
        <span class="current-year">{{ currentYear }}</span>
        <button @click="changeYear(1)" class="btn-year-nav">
          <i class="icon-chevron-right"></i>
        </button>
      </div>
    </div>

    <!-- Betöltés állapota -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Rendelések betöltése...</p>
    </div>

    <!-- Hibaüzenet -->
    <div v-else-if="error" class="error-container">
      <div class="error-icon">
        <i class="icon-error"></i>
      </div>
      <p>{{ error }}</p>
      <button @click="loadOrders" class="btn-retry">Újra próbál</button>
    </div>

    <!-- Nincs rendelés -->
    <div v-else-if="filteredOrders.length === 0 && availableDates.length === 0" class="no-orders">
      <div class="no-orders-icon">
        <i class="icon-empty"></i>
      </div>
      <p>Nincsenek elérhető rendelési napok a kiválasztott hónapban</p>
    </div>

    <!-- Rendelési táblázat -->
    <div v-else class="orders-table-container">
      <!-- Statisztika sáv -->
      <div class="stats-bar">
        <div class="stat-item">
          <span class="stat-label">Aktív rendelések:</span>
          <span class="stat-value">{{ activeOrdersCount }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Összes költség:</span>
          <span class="stat-value">{{ totalCost.toLocaleString('hu-HU') }} Ft</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Átlagos ár:</span>
          <span class="stat-value">{{ avgCost.toLocaleString('hu-HU') }} Ft</span>
        </div>
      </div>

      <!-- Táblázat -->
      <div class="table-responsive">
        <table class="orders-table">
          <thead>
            <tr>
              <th class="col-date">Dátum</th>
              <th class="col-day">Nap</th>
              <th class="col-soup">Leves</th>
              <th class="col-option">A opció</th>
              <th class="col-option">B opció</th>
              <th class="col-other">Egyéb</th>
              <th class="col-choice">Választás</th>
              <th class="col-status">Státusz</th>
              <th class="col-actions">Műveletek</th>
            </tr>
          </thead>
          <tbody>
            <!-- Rendelhető napok -->
            <tr 
              v-for="date in availableDates" 
              :key="'available-' + date.date"
              :class="{ 
                'today': isToday(date.date),
                'past-date': isPastDate(date.date),
                'has-order': date.has_order 
              }"
            >
              <!-- Dátum -->
              <td class="col-date">
                <div class="date-display">
                  <span class="date-number">{{ getDayNumber(date.date) }}</span>
                  <span class="date-month">{{ getMonthName(date.date) }}</span>
                </div>
              </td>

              <!-- Nap -->
              <td class="col-day">
                <span class="day-name">{{ date.day_name }}</span>
              </td>

              <!-- Leves -->
              <td class="col-soup">
                <div class="meal-info">
                  <div class="meal-name">{{ date.menu.soup?.mealName || 'Nincs adat' }}</div>
                  <div v-if="date.menu.soup?.allergens" class="meal-allergens">
                    {{ date.menu.soup?.allergens }}
                  </div>
                </div>
              </td>

              <!-- A opció -->
              <td class="col-option option-a">
                <div class="meal-info">
                  <div class="meal-name">{{ date.menu.optionA?.mealName || 'Nincs adat' }}</div>
                  <div v-if="date.menu.optionA?.allergens" class="meal-allergens">
                    {{ date.menu.optionA?.allergens }}
                  </div>
                  <div class="meal-price">{{ date.menu.optionA?.price || '' }}</div>
                </div>
              </td>

              <!-- B opció -->
              <td class="col-option option-b">
                <div class="meal-info">
                  <div class="meal-name">{{ date.menu.optionB?.mealName || 'Nincs adat' }}</div>
                  <div v-if="date.menu.optionB?.allergens" class="meal-allergens">
                    {{ date.menu.optionB?.allergens }}
                  </div>
                  <div class="meal-price">{{ date.menu.optionB?.price || '' }}</div>
                </div>
              </td>

              <!-- Egyéb -->
              <td class="col-other">
                <div class="meal-info" v-if="date.menu.other">
                  <div class="meal-name">{{ date.menu.other?.mealName || 'Nincs adat' }}</div>
                  <div v-if="date.menu.other?.allergens" class="meal-allergens">
                    {{ date.menu.other?.allergens }}
                  </div>
                </div>
                <span v-else class="no-other">-</span>
              </td>

              <!-- Választás -->
              <td class="col-choice">
                <div v-if="canOrderForDate(date.date)" class="choice-options">
                  <label class="radio-option">
                    <input 
                      type="radio" 
                      :name="'option-' + date.date" 
                      value="A"
                      :checked="date.has_order && getOrderForDate(date.date)?.selectedOption === 'A'"
                      :disabled="date.has_order || isPastDeadline(date.date)"
                      @change="selectOption(date.date, 'A')"
                    >
                    <span class="radio-label">A</span>
                  </label>
                  <label class="radio-option">
                    <input 
                      type="radio" 
                      :name="'option-' + date.date" 
                      value="B"
                      :checked="date.has_order && getOrderForDate(date.date)?.selectedOption === 'B'"
                      :disabled="date.has_order || isPastDeadline(date.date)"
                      @change="selectOption(date.date, 'B')"
                    >
                    <span class="radio-label">B</span>
                  </label>
                </div>
                <div v-else class="choice-disabled">
                  <span v-if="date.has_order" class="already-ordered">
                    <i class="icon-check"></i> Rendelve
                  </span>
                  <span v-else-if="isPastDeadline(date.date)" class="past-deadline">
                    <i class="icon-clock"></i> Határidő lejárt
                  </span>
                  <span v-else class="cannot-order">
                    Nem rendelhető
                  </span>
                </div>
              </td>

              <!-- Státusz -->
              <td class="col-status">
                <span v-if="date.has_order" class="status-badge ordered">
                  <i class="icon-check-circle"></i> Rendelve
                </span>
                <span v-else-if="isPastDeadline(date.date)" class="status-badge deadline">
                  <i class="icon-alert"></i> Lejárt
                </span>
                <span v-else class="status-badge available">
                  <i class="icon-available"></i> Rendelhető
                </span>
              </td>

              <!-- Műveletek -->
              <td class="col-actions">
                <div class="action-buttons">
                  <button 
                    v-if="date.has_order && canCancelOrder(getOrderForDate(date.date))"
                    @click="cancelOrder(getOrderForDate(date.date).id)"
                    class="btn-action btn-cancel"
                    :title="getCancelDeadlineInfo(date.date)"
                  >
                    <i class="icon-cancel"></i> Lemondás
                  </button>
                  <button 
                    v-else-if="!date.has_order && canOrderForDate(date.date)"
                    @click="placeOrder(date)"
                    class="btn-action btn-order"
                    :disabled="!selectedOptions[date.date]"
                  >
                    <i class="icon-order"></i> Rendelés
                  </button>
                  <span v-else class="no-action">-</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Tájékoztató -->
      <div class="table-info">
        <div class="info-item">
          <span class="info-icon today-marker"></span>
          <span>Mai nap</span>
        </div>
        <div class="info-item">
          <span class="info-icon deadline-marker"></span>
          <span>Rendelési határidő: előző nap 10:00-ig</span>
        </div>
        <div class="info-item">
          <span class="info-icon edit-marker"></span>
          <span>Módosítható lemondási határidő: aznap 8:00-ig</span>
        </div>
      </div>
    </div>

    <!-- Kiszállítás információk -->
    <div class="delivery-info">
      <h3><i class="icon-info"></i> Fontos információk</h3>
      <ul>
        <li>A rendelést az előző nap <strong>10:00-ig</strong> lehet leadni</li>
        <li>A rendelést az adott nap <strong>8:00-ig</strong> lehet lemondani</li>
        <li>A menü ára tartalmazza a levest és a választott főételt</li>
        <li>A számlázás hónap végén történik</li>
        <li>Kérdés esetén keresd a konyha személyzetét</li>
      </ul>
    </div>
  </div>
</template>

<script>
import AuthService from '@/services/authService'

export default {
  name: 'PersonalOrders',
  
  data() {
    return {
      loading: true,
      error: null,
      orders: [],
      availableDates: [],
      selectedMonth: null,
      selectedYear: new Date().getFullYear(),
      showMonthPicker: false,
      selectedOptions: {}, // { '2024-01-15': 'A', '2024-01-16': 'B' }
      orderDeadlineHour: 10, // előző nap 10:00-ig
      cancelDeadlineHour: 8, // aznap 8:00-ig
      
      // Teszt adatok a design-hoz
      availableMonths: [
        { year: 2024, month: 1, display: 'Január 2024' },
        { year: 2024, month: 2, display: 'Február 2024' },
        { year: 2024, month: 3, display: 'Március 2024' },
        { year: 2024, month: 4, display: 'Április 2024' },
        { year: 2024, month: 5, display: 'Május 2024' },
        { year: 2024, month: 6, display: 'Június 2024' },
      ]
    }
  },
  
  computed: {
    selectedMonthDisplay() {
      if (this.selectedMonth) {
        return `${this.getMonthName(this.selectedMonth.month)} ${this.selectedMonth.year}`
      }
      const now = new Date()
      return `${this.getMonthName(now.getMonth() + 1)} ${now.getFullYear()}`
    },
    
    currentYear() {
      return this.selectedYear
    },
    
    filteredOrders() {
      if (!this.selectedMonth) return this.orders
      
      return this.orders.filter(order => {
        const orderDate = new Date(order.orderDate)
        return orderDate.getFullYear() === this.selectedMonth.year && 
               orderDate.getMonth() + 1 === this.selectedMonth.month
      })
    },
    
    activeOrdersCount() {
      return this.filteredOrders.filter(order => order.orderStatus === 'Rendelve').length
    },
    
    totalCost() {
      return this.filteredOrders
        .filter(order => order.orderStatus === 'Rendelve')
        .reduce((sum, order) => sum + (order.price || 0), 0)
    },
    
    avgCost() {
      return this.activeOrdersCount > 0 
        ? Math.round(this.totalCost / this.activeOrdersCount) 
        : 0
    }
  },
  
  mounted() {
    this.loadInitialData()
    // Alapértelmezett: aktuális hónap
    const now = new Date()
    this.selectedMonth = {
      year: now.getFullYear(),
      month: now.getMonth() + 1
    }
  },
  
  methods: {
    async loadInitialData() {
      this.loading = true
      this.error = null
      
      try {
        // Párhuzamosan töltjük be az adatokat
        const [ordersResponse, datesResponse] = await Promise.all([
          AuthService.api.get('/user/personal-orders'),
          AuthService.api.get('/user/personal-orders/available-dates')
        ])
        
        this.orders = ordersResponse.data.data?.orders || []
        this.availableDates = datesResponse.data.data || []
        
        // Betöltjük a már kiválasztott opciókat
        this.loadSelectedOptions()
        
      } catch (err) {
        console.error('Hiba az adatok betöltésekor:', err)
        this.error = 'Hiba történt az adatok betöltésekor.'
      } finally {
        this.loading = false
      }
    },
    
    async loadOrders() {
      this.loading = true
      try {
        const response = await AuthService.api.get('/user/personal-orders')
        this.orders = response.data.data?.orders || []
        this.loadSelectedOptions()
      } catch (err) {
        console.error('Hiba a rendelések betöltésekor:', err)
      } finally {
        this.loading = false
      }
    },
    
    async loadAvailableDates() {
      try {
        const response = await AuthService.api.get('/user/personal-orders/available-dates')
        this.availableDates = response.data.data || []
      } catch (err) {
        console.error('Hiba az elérhető dátumok betöltésekor:', err)
      }
    },
    
    loadSelectedOptions() {
      // Betöltjük a már meglévő rendelések opcióit
      this.selectedOptions = {}
      this.orders.forEach(order => {
        if (order.orderStatus === 'Rendelve') {
          this.selectedOptions[order.orderDate] = order.selectedOption
        }
      })
    },
    
    selectMonth(month) {
      this.selectedMonth = month
      this.showMonthPicker = false
      // Itt lehetne hónap szerinti szűrés az API-n keresztül
      // this.loadOrdersForMonth(month.year, month.month)
    },
    
    isMonthSelected(month) {
      return this.selectedMonth && 
             this.selectedMonth.year === month.year && 
             this.selectedMonth.month === month.month
    },
    
    changeYear(delta) {
      this.selectedYear += delta
      // Itt frissíthetnénk a hónapokat az új évre
    },
    
    refreshData() {
      this.loadInitialData()
    },
    
    selectOption(date, option) {
      this.selectedOptions[date] = option
    },
    
    async placeOrder(dateData) {
      const selectedOption = this.selectedOptions[dateData.date]
      if (!selectedOption) {
        alert('Kérjük válasszon opciót!')
        return
      }
      
      if (!this.canOrderForDate(dateData.date)) {
        alert('A rendelési határidő már lejárt erre a napra.')
        return
      }
      
      if (!confirm(`Biztosan rendel ${selectedOption} opciót ${dateData.display_date}-ra?`)) {
        return
      }
      
      try {
        // Először megkeressük a menü ID-ját
        const menuItem = await this.findMenuItemForDate(dateData.date)
        if (!menuItem) {
          throw new Error('Nem található menü ehhez a dátumhoz')
        }
        
        const orderData = {
          date: dateData.date,
          menuitems_id: menuItem.id,
          selectedOption: selectedOption
        }
        
        const response = await AuthService.api.post('/user/personal-orders', orderData)
        
        if (response.data.success) {
          alert('Rendelés sikeresen leadva!')
          this.loadInitialData()
        } else {
          throw new Error(response.data.message || 'Hiba a rendelés leadásakor')
        }
      } catch (err) {
        console.error('Hiba a rendelés leadásakor:', err)
        alert(err.response?.data?.message || err.message || 'Hiba történt a rendelés leadásakor.')
      }
    },
    
    async cancelOrder(orderId) {
      if (!confirm('Biztosan le szeretnéd mondani ezt a rendelést?')) {
        return
      }
      
      try {
        const response = await AuthService.api.delete(`/user/personal-orders/${orderId}`)
        
        if (response.data.success) {
          alert('Rendelés sikeresen lemondva!')
          this.loadInitialData()
        } else {
          throw new Error(response.data.message || 'Hiba a lemondás során')
        }
      } catch (err) {
        console.error('Hiba a lemondás során:', err)
        alert(err.response?.data?.message || err.message || 'Hiba történt a lemondás során.')
      }
    },
    
    async findMenuItemForDate(date) {
      // Megkeressük a dátumhoz tartozó menüt
      const dateItem = this.availableDates.find(d => d.date === date)
      if (dateItem && dateItem.menuItemId) {
        return { id: dateItem.menuItemId }
      }
      
      // Ha nincs ID, akkor API hívás
      try {
        const response = await AuthService.api.get(`/user/personal-orders/date/${date}`)
        return response.data.data?.menuItem
      } catch (err) {
        console.error('Hiba a menü keresésekor:', err)
        return null
      }
    },
    
    // Segédfüggvények
    getDayNumber(dateString) {
      const date = new Date(dateString)
      return date.getDate()
    },
    
    getMonthName(dateString) {
      const date = new Date(dateString)
      const months = [
        'Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún',
        'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec'
      ]
      return months[date.getMonth()]
    },
    
    getMonthName(monthNumber) {
      const months = [
        'Január', 'Február', 'Március', 'Április', 'Május', 'Június',
        'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'
      ]
      return months[monthNumber - 1] || ''
    },
    
    isToday(dateString) {
      const today = new Date().toISOString().split('T')[0]
      return dateString === today
    },
    
    isPastDate(dateString) {
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const date = new Date(dateString)
      return date < today
    },
    
    isPastDeadline(dateString) {
      const orderDate = new Date(dateString)
      const deadlineDate = new Date(orderDate)
      deadlineDate.setDate(deadlineDate.getDate() - 1) // előző nap
      deadlineDate.setHours(this.orderDeadlineHour, 0, 0, 0) // 10:00
      
      return new Date() > deadlineDate
    },
    
    canOrderForDate(dateString) {
      return !this.isPastDeadline(dateString) && !this.getOrderForDate(dateString)
    },
    
    canCancelOrder(order) {
      if (!order || order.orderStatus !== 'Rendelve') return false
      
      const orderDate = new Date(order.orderDate)
      const cancelDeadline = new Date(orderDate)
      cancelDeadline.setHours(this.cancelDeadlineHour, 0, 0, 0) // aznap 8:00
      
      return new Date() <= cancelDeadline
    },
    
    getOrderForDate(dateString) {
      return this.orders.find(order => 
        order.orderDate === dateString && order.orderStatus === 'Rendelve'
      )
    },
    
    getCancelDeadlineInfo(dateString) {
      const orderDate = new Date(dateString)
      const cancelDeadline = new Date(orderDate)
      cancelDeadline.setHours(this.cancelDeadlineHour, 0, 0, 0)
      
      return `Lemondási határidő: ${cancelDeadline.toLocaleString('hu-HU')}`
    },
    
    getOptionName(option) {
      const options = {
        'A': 'A opció',
        'B': 'B opció',
        'soup': 'Leves',
        'other': 'Egyéb'
      }
      return options[option] || option
    },
    
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      })
    }
  }
}
</script>

<style scoped>
.personal-orders-container {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  background: #f8fafc;
  min-height: 100vh;
}

/* Fejléc */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 2px solid #e2e8f0;
}

.page-header h1 {
  margin: 0;
  color: #2d3748;
  font-size: 28px;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 12px;
}

.btn-month-picker, .btn-refresh {
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  border: none;
  transition: all 0.2s;
}

.btn-month-picker {
  background: #4f46e5;
  color: white;
}

.btn-month-picker:hover {
  background: #4338ca;
}

.btn-refresh {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-refresh:hover {
  background: #e2e8f0;
}

/* Hónap kiválasztó */
.month-picker {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  border: 1px solid #e2e8f0;
}

.month-picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.month-picker-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 18px;
}

.btn-close {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
}

.btn-close:hover {
  background: #f1f5f9;
}

.month-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.month-option {
  padding: 16px 12px;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  font-weight: 500;
  color: #475569;
  transition: all 0.2s;
}

.month-option:hover {
  border-color: #cbd5e1;
  background: #f1f5f9;
}

.month-option.active {
  background: #4f46e5;
  color: white;
  border-color: #4f46e5;
}

.year-selector {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
}

.current-year {
  font-size: 20px;
  font-weight: 600;
  color: #2d3748;
  min-width: 80px;
  text-align: center;
}

.btn-year-nav {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 10px 16px;
  cursor: pointer;
  color: #475569;
  transition: all 0.2s;
}

.btn-year-nav:hover {
  background: #e2e8f0;
}

/* Táblázat */
.orders-table-container {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
}

.stats-bar {
  display: flex;
  justify-content: space-around;
  padding: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-item {
  text-align: center;
}

.stat-label {
  display: block;
  font-size: 14px;
  opacity: 0.9;
  margin-bottom: 4px;
}

.stat-value {
  display: block;
  font-size: 24px;
  font-weight: 700;
}

.table-responsive {
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1200px;
}

.orders-table th {
  background: #f8fafc;
  padding: 16px 12px;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.orders-table td {
  padding: 16px 12px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: top;
}

.orders-table tbody tr:hover {
  background: #f8fafc;
}

.orders-table tbody tr.today {
  background: #f0f9ff;
}

.orders-table tbody tr.today td {
  border-left: 4px solid #3b82f6;
}

.orders-table tbody tr.past-date {
  opacity: 0.7;
}

.orders-table tbody tr.has-order {
  background: #f0fdf4;
}

/* Oszlopok */
.col-date {
  width: 100px;
}

.col-day {
  width: 120px;
}

.col-soup, .col-option, .col-other {
  width: 200px;
}

.col-choice {
  width: 120px;
}

.col-status {
  width: 140px;
}

.col-actions {
  width: 150px;
}

/* Dátum megjelenítés */
.date-display {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.date-number {
  font-size: 24px;
  font-weight: 700;
  color: #2d3748;
  line-height: 1;
}

.date-month {
  font-size: 12px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.day-name {
  font-weight: 500;
  color: #475569;
}

/* Étel információk */
.meal-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meal-name {
  font-weight: 500;
  color: #2d3748;
  line-height: 1.4;
}

.meal-allergens {
  font-size: 11px;
  color: #ef4444;
  background: #fef2f2;
  padding: 2px 6px;
  border-radius: 4px;
  display: inline-block;
}

.meal-price {
  font-size: 12px;
  color: #10b981;
  font-weight: 600;
}

/* Opció választás */
.choice-options {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.radio-option {
  display: flex;
  align-items: center;
  cursor: pointer;
}

.radio-option input[type="radio"] {
  display: none;
}

.radio-label {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 2px solid #cbd5e1;
  border-radius: 50%;
  font-weight: 600;
  color: #64748b;
  transition: all 0.2s;
}

.radio-option input[type="radio"]:checked + .radio-label {
  border-color: #4f46e5;
  background: #4f46e5;
  color: white;
}

.radio-option input[type="radio"]:disabled + .radio-label {
  opacity: 0.5;
  cursor: not-allowed;
}

.choice-disabled {
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
}

.already-ordered {
  color: #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

.past-deadline {
  color: #ef4444;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
}

/* Státusz badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 500;
}

.status-badge.ordered {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.deadline {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.available {
  background: #dbeafe;
  color: #1e40af;
}

/* Műveletek gombok */
.action-buttons {
  display: flex;
  justify-content: center;
}

.btn-action {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-order {
  background: #10b981;
  color: white;
}

.btn-order:hover:not(:disabled) {
  background: #0da271;
}

.btn-order:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-cancel {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fbbf24;
}

.btn-cancel:hover {
  background: #fde68a;
}

.no-action {
  color: #94a3b8;
  font-style: italic;
}

/* Tájékoztató */
.table-info {
  display: flex;
  justify-content: center;
  gap: 32px;
  padding: 20px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  font-size: 14px;
}

.info-icon {
  width: 12px;
  height: 12px;
  border-radius: 50%;
}

.today-marker {
  background: #3b82f6;
}

.deadline-marker {
  background: #ef4444;
}

.edit-marker {
  background: #f59e0b;
}

/* Kiszállítás információk */
.delivery-info {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.delivery-info h3 {
  margin-top: 0;
  margin-bottom: 16px;
  color: #2d3748;
  display: flex;
  align-items: center;
  gap: 8px;
}

.delivery-info ul {
  margin: 0;
  padding-left: 20px;
  color: #475569;
  line-height: 1.6;
}

.delivery-info li {
  margin-bottom: 8px;
}

.delivery-info strong {
  color: #2d3748;
}

/* Betöltés, hiba, üres állapotok */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  background: white;
  border-radius: 12px;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-container, .no-orders {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  background: white;
  border-radius: 12px;
  text-align: center;
}

.error-icon, .no-orders-icon {
  font-size: 48px;
  color: #ef4444;
  margin-bottom: 16px;
}

.no-orders-icon {
  color: #94a3b8;
}

.btn-retry {
  margin-top: 16px;
  padding: 10px 24px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-retry:hover {
  background: #4338ca;
}

/* Ikonok (helyettesítés) */
.icon-calendar:before { content: "📅"; }
.icon-refresh:before { content: "🔄"; }
.icon-close:before { content: "✕"; }
.icon-chevron-left:before { content: "‹"; }
.icon-chevron-right:before { content: "›"; }
.icon-check:before { content: "✓"; }
.icon-clock:before { content: "⏰"; }
.icon-check-circle:before { content: "✅"; }
.icon-alert:before { content: "⚠️"; }
.icon-available:before { content: "📋"; }
.icon-cancel:before { content: "❌"; }
.icon-order:before { content: "📝"; }
.icon-info:before { content: "ℹ️"; }
.icon-error:before { content: "❌"; }
.icon-empty:before { content: "📭"; }

/* Reszponzív */
@media (max-width: 1200px) {
  .personal-orders-container {
    padding: 16px;
  }
  
  .month-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .table-info {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: space-between;
  }
  
  .stats-bar {
    flex-direction: column;
    gap: 20px;
  }
  
  .month-grid {
    grid-template-columns: 1fr;
  }
}
</style>