<template>
  <div class="personal-orders">
    <!-- Fejléc -->
    <div class="orders-header">
      <h1>Személyes rendelések</h1>
      
      <div class="header-controls">
        <!-- Hónap választó -->
        <div class="month-selector">
          <button 
            @click="previousMonth" 
            class="month-nav"
            :disabled="!canGoToPreviousMonth"
          >
            ←
          </button>
          
          <button @click="showMonthPicker = true" class="current-month">
            {{ currentMonthDisplay }}
          </button>
          
          <button 
            @click="nextMonth" 
            class="month-nav"
            :disabled="!canGoToNextMonth"
          >
            →
          </button>
        </div>

        <!-- Frissítés gomb -->
        <button @click="refreshData" class="btn-refresh">
          ⟳ Frissítés
        </button>
      </div>
    </div>

    <!-- Hónap választó modal -->
    <div v-if="showMonthPicker" class="month-picker-modal" @click.self="showMonthPicker = false">
      <div class="month-picker-content">
        <div class="month-picker-header">
          <h3>Válassz hónapot</h3>
          <button @click="showMonthPicker = false" class="close-btn">×</button>
        </div>
        
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

    <!-- Töltés/hiba állapot -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Rendelések betöltése...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="loadOrders" class="retry-btn">Újrapróbálkozás</button>
    </div>

    <!-- Rendelések táblázat -->
    <div v-else-if="availableDates.length > 0" class="orders-table-wrapper">
      <table class="orders-table">
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
              <div class="meal-name">
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
              <div class="meal-name">
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
              <div class="meal-name">
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
              <!-- Cukorbeteg: fix opció -->
              <template v-if="userInfo?.hasDiabetes">
                <span class="fixed-choice">{{ getDiabeticChoice(date) }}</span>
              </template>
              
              <!-- Normál felhasználó: mindig látszanak a gombok -->
              <template v-else>
                <div class="choice-buttons">
                  <!-- A opció gomb -->
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
                  
                  <!-- B opció gomb -->
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

            <!-- Művelet -->
            <td class="action-cell">
              <div class="actions-inner">
                <!-- Aktív rendelés -->
                <template v-if="date.order_status === 'Rendelve'">
                  <button 
                    v-if="canModifyOrder(date.date)"
                    @click="changeOrderOption(date, date.selected_option === 'A' ? 'B' : 'A')"
                    class="action-btn edit"
                    :title="'Módosítás (határidő: ' + getDeadlineInfo(date.date) + ')'"
                  >
                    ✎
                  </button>
                  <button 
                    v-if="canModifyOrder(date.date)"
                    @click="cancelOrder(date.order_id)"
                    class="action-btn cancel"
                    :title="'Lemondás (határidő: ' + getDeadlineInfo(date.date) + ')'"
                  >
                    ✗
                  </button>
                  <span v-else class="disabled-action" :title="'Határidő lejárt: ' + getDeadlineInfo(date.date)">
                    Lejárt
                  </span>
                </template>
                
                <!-- Lemondott rendelés -->
                <template v-else-if="date.order_status === 'Lemondva'">
                  <button 
                    v-if="canModifyOrder(date.date)"
                    @click="reorderDate(date)"
                    class="action-btn reorder"
                    :title="'Újrarendelés (határidő: ' + getDeadlineInfo(date.date) + ')'"
                  >
                    ↻
                  </button>
                  <span v-else class="disabled-action">Lejárt</span>
                </template>
                
                <!-- Nincs rendelés -->
                <template v-else-if="!date.has_order">
                  <button 
                    v-if="canModifyOrder(date.date)"
                    @click="placeOrder(date)"
                    class="action-btn order"
                    :disabled="!canPlaceOrder(date)"
                    :title="'Rendelés (határidő: ' + getDeadlineInfo(date.date) + ')'"
                  >
                    Rendelés
                  </button>
                  <span v-else class="disabled-action">Lejárt</span>
                </template>
                
                <!-- Egyéb eset -->
                <template v-else>
                  <span class="disabled-action">-</span>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Nincs adat -->
    <div v-else class="no-data">
      <p>Nincsenek elérhető rendelések ebben a hónapban</p>
    </div>

    <!-- Információs sáv -->
    <div class="info-footer">
      <div class="info-item">
        <span class="info-icon">⏰</span>
        <span>Rendelés/módosítás/lemondás: előző munkanap 10:00-ig</span>
      </div>
      <div class="info-item">
        <span class="info-icon">⚠️</span>
        <span>Allergén figyelmeztetés</span>
      </div>
      <div v-if="userInfo?.hasDiabetes" class="info-item">
        <span class="info-icon">🩺</span>
        <span>Cukorbeteg mód: automatikusan cukormentes opció</span>
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
      orders: [],
      availableDates: [],
      selectedMonth: null,
      showMonthPicker: false,
      tempSelections: {}, // Ideiglenes választások új rendeléshez
    }
  },
  
  computed: {
    // Elérhető hónapok (szeptember - június)
    availableMonths() {
      const months = []
      const currentYear = new Date().getFullYear()
      
      // Aktuális tanév meghatározása (szeptembertől)
      const now = new Date()
      const startYear = now.getMonth() >= 8 ? now.getFullYear() : now.getFullYear() - 1
      
      for (let year = startYear - 1; year <= startYear + 1; year++) {
        // Szeptember - December
        for (let month = 9; month <= 12; month++) {
          months.push({
            year,
            month,
            display: this.getMonthNameFull(month) + ' ' + year
          })
        }
        // Január - Június
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
  
  methods: {
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
      
      // Ha nyári szünet van (július-augusztus), akkor szeptembert mutassuk
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
        // Elérhető dátumok betöltése a kiválasztott hónapra
        const response = await AuthService.api.get(
          `/user/personal-orders/month/${this.selectedMonth.year}/${this.selectedMonth.month}`
        )
        
        if (response.data?.success) {
          this.availableDates = response.data.data || []
        }
        
        // Rendelések betöltése
        const ordersResponse = await AuthService.api.get('/user/personal-orders')
        if (ordersResponse.data?.success) {
          this.orders = ordersResponse.data.data?.orders || []
        }
      } catch (err) {
        console.error('Hiba a rendelések betöltésekor:', err)
        this.error = 'Nem sikerült betölteni a rendeléseket'
      } finally {
        this.loading = false
      }
    },
    
    // Hónap navigáció
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
      this.tempSelections = {}
      this.loadOrders()
    },
    
    isCurrentMonth(month) {
      return this.selectedMonth?.year === month.year && 
             this.selectedMonth?.month === month.month
    },
    
    // Dátum segédfüggvények
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

    async modifyOrder(date) {
      const newOption = date.selected_option === 'A' ? 'B' : 'A'
      await this.changeOrderOption(date, newOption)
    },
    
    // Határidő ellenőrzés - EGYETLEN függvény minden művelethez!
    canModifyOrder(dateString) {
      const orderDate = new Date(dateString)
      const now = new Date()
      
      // Állítsuk be a határidőt: előző munkanap 10:00
      const deadline = new Date(orderDate)
      deadline.setDate(deadline.getDate() - 1) // előző nap
      deadline.setHours(10, 0, 0, 0) // 10:00
      
      // Ha szombat vagy vasárnap, akkor péntek 10:00 a határidő
      if (orderDate.getDay() === 1) { // Hétfő
        deadline.setDate(orderDate.getDate() - 3) // Péntek
      } else if (orderDate.getDay() === 0) { // Vasárnap (ritka, de lehet)
        deadline.setDate(orderDate.getDate() - 2) // Péntek
      }
      
      return now <= deadline
    },
    
    // Opciók megjelenítése/kezelése
    canShowOption(date, option) {
      if (option === 'A') return !!date.menu.optionA
      if (option === 'B') return !!date.menu.optionB
      return false
    },
    
    isOptionSelected(date, option) {
      // Ha van rendelés, a selected_option alapján
      if (date.has_order) {
        return date.selected_option === option
      }
      // Ha nincs rendelés, a tempSelections alapján
      return this.tempSelections[date.date] === option
    },
    
    isOptionDisabled(date, option) {
      // Cukorbetegek nem kattinthatnak
      if (this.userInfo?.hasDiabetes) return true
      
      // Ha nincs rendelés és lejárt a határidő
      if (!date.has_order && !this.canModifyOrder(date.date)) return true
      
      // Ha van rendelés és lejárt a határidő
      if (date.has_order && !this.canModifyOrder(date.date)) return true
      
      return false
    },
    
    handleOptionClick(date, option) {
      // Ha van rendelés, akkor módosítás
      if (date.has_order) {
        if (date.order_status === 'Rendelve' && this.canModifyOrder(date.date)) {
          this.changeOrderOption(date, option)
        }
      } 
      // Ha nincs rendelés, akkor ideiglenes kiválasztás
      else {
        if (this.canModifyOrder(date.date)) {
          this.selectOptionForNewOrder(date, option)
        }
      }
    },
    
    canPlaceOrder(date) {
      // Van-e választás? (cukorbetegeknek automatikus)
      if (this.userInfo?.hasDiabetes) return true
      return !!this.tempSelections[date.date]
    },
    
    getDiabeticChoice(date) {
      // Itt kellene egy mező, hogy melyik opció cukormentes
      return 'A'
    },
    
    // Allergén kezelés
    hasAllergenWarning(date, mealType) {
      return date.allergen_warnings?.some(w => w.meal === mealType) || false
    },
    
    getAllergenTooltip(date, mealType) {
      const warning = date.allergen_warnings?.find(w => w.meal === mealType)
      if (!warning) return ''
      return 'Allergént tartalmaz!'
    },
    
    getDeadlineInfo(dateString) {
      const date = new Date(dateString)
      const deadline = new Date(date)
      deadline.setDate(deadline.getDate() - 1)
      
      if (date.getDay() === 1) { // Hétfő
        deadline.setDate(date.getDate() - 3)
      }
      
      return deadline.toLocaleDateString('hu-HU') + ' 10:00'
    },
    
    // Rendelési műveletek
    selectOptionForNewOrder(date, option) {
      this.tempSelections[date.date] = option
    },
    
    async placeOrder(date) {
      let selectedOption = this.tempSelections[date.date]
      
      // Cukorbetegeknek automatikus
      if (this.userInfo?.hasDiabetes) {
        selectedOption = 'A'
      }
      
      if (!selectedOption) {
        alert('Kérlek válassz opciót!')
        return
      }
      
      if (!this.canModifyOrder(date.date)) {
        alert('A rendelési határidő lejárt!')
        return
      }
      
      if (!confirm(`Biztosan rendelni szeretnéd a ${selectedOption} opciót ${this.formatDate(date.date)}-ra?`)) {
        return
      }
      
      try {
        const response = await AuthService.api.post('/user/personal-orders', {
          date: date.date,
          menuitems_id: date.menu_item_id,
          selectedOption
        })
        
        if (response.data?.success) {
          alert('Rendelés sikeresen leadva!')
          delete this.tempSelections[date.date]
          await this.loadOrders()
        }
      } catch (err) {
        console.error('Rendelési hiba:', err)
        alert(err.response?.data?.message || 'Hiba történt a rendelés során')
      }
    },
    
    async changeOrderOption(date, newOption) {
      const order = this.getOrderForDate(date.date)
      
      if (!order || order.selectedOption === newOption) return
      
      if (!this.canModifyOrder(date.date)) {
        alert('A módosítási határidő lejárt!')
        return
      }
      
      if (!confirm(`Biztosan módosítani szeretnéd a ${newOption} opcióra?`)) {
        return
      }
      
      try {
        const response = await AuthService.api.patch(`/user/personal-orders/${order.id}/update-option`, {
          selectedOption: newOption
        })
        
        if (response.data?.success) {
          alert('Opció sikeresen módosítva!')
          await this.loadOrders()
        }
      } catch (err) {
        console.error('Módosítási hiba:', err)
        alert(err.response?.data?.message || 'Hiba történt a módosítás során')
      }
    },
    
    async cancelOrder(orderId) {
      const date = this.availableDates.find(d => d.order_id === orderId)
      if (!date) return
      
      if (!this.canModifyOrder(date.date)) {
        alert('A lemondási határidő lejárt!')
        return
      }
      
      if (!confirm('Biztosan le szeretnéd mondani ezt a rendelést?')) {
        return
      }
      
      try {
        const response = await AuthService.api.delete(`/user/personal-orders/${orderId}/cancel`)
        
        if (response.data?.success) {
          alert('Rendelés sikeresen lemondva!')
          await this.loadOrders()
        }
      } catch (err) {
        console.error('Lemondási hiba:', err)
        alert(err.response?.data?.message || 'Hiba történt a lemondás során')
      }
    },
    
    async reorderDate(date) {
      if (!this.canModifyOrder(date.date)) {
        alert('A rendelési határidő lejárt!')
        return
      }
      
      const order = this.getOrderForDate(date.date)
      const selectedOption = order?.selected_option || 'A'
      
      if (!confirm(`Biztosan újra szeretnéd rendelni a ${selectedOption} opciót?`)) {
        return
      }
      
      try {
        const response = await AuthService.api.post(`/user/personal-orders/${date.order_id}/reorder`, {
          selectedOption
        })
        
        if (response.data?.success) {
          alert('Újrarendelés sikeres!')
          await this.loadOrders()
        }
      } catch (err) {
        console.error('Újrarendelési hiba:', err)
        alert(err.response?.data?.message || 'Hiba történt az újrarendelés során')
      }
    },
    
    // Segédfüggvények
    getOrderForDate(dateString) {
      return this.orders.find(o => o.orderDate === dateString)
    },
    
    getStatusClass(date) {
      if (date.order_status === 'Rendelve') return 'ordered'
      if (date.order_status === 'Lemondva') return 'cancelled'
      if (!this.canModifyOrder(date.date)) return 'deadline-passed'
      return 'available'
    },
    
    getStatusText(date) {
      if (date.order_status === 'Rendelve') return 'Rendelve'
      if (date.order_status === 'Lemondva') return 'Lemondva'
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
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
}

/* Fejléc */
.orders-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #eef2f6;
}

.orders-header h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.8rem;
  font-weight: 600;
}

.header-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* Hónap választó */
.month-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 0.25rem;
}

.month-nav {
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #3498db;
  font-size: 1.2rem;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.month-nav:hover:not(:disabled) {
  background: #f0f7ff;
}

.month-nav:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.current-month {
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  color: #2c3e50;
  font-weight: 500;
  cursor: pointer;
  min-width: 160px;
  text-align: center;
}

.current-month:hover {
  color: #3498db;
}

.btn-refresh {
  padding: 0.5rem 1rem;
  background: #2ecc71;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-refresh:hover {
  background: #27ae60;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
}

/* Hónap választó modal */
.month-picker-modal {
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
}

.month-picker-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.month-picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.month-picker-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #95a5a6;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.close-btn:hover {
  background: #f8f9fa;
  color: #2c3e50;
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
  border-radius: 6px;
  color: #2c3e50;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.month-btn:hover {
  background: #eef2f6;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.month-btn.active {
  background: #3498db;
  border-color: #3498db;
  color: white;
}

/* Táblázat */
.orders-table-wrapper {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  margin-bottom: 2rem;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
}

.orders-table th {
  background: #f8f9fa;
  padding: 1rem;
  text-align: left;
  color: #2c3e50;
  font-weight: 600;
  font-size: 0.9rem;
  border-bottom: 2px solid #e0e0e0;
}

.orders-table td {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
  color: #2c3e50;
}

.orders-table tr:last-child td {
  border-bottom: none;
}

.orders-table tbody tr:hover {
  background: #f8f9fa;
}

/* Sor állapotok */
.orders-table tr.cancelled-order {
  background: #fef5f5;
  opacity: 0.8;
}

.orders-table tr.active-order {
  background: #f0fdf4;
}

.orders-table tr.today {
  background: #f0f9ff;
}

.orders-table tr.past-deadline {
  opacity: 0.7;
}

/* Dátum cella */
.date-cell {
  width: 100px;
}

.date-display {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.day-number {
  font-size: 1.6rem;
  font-weight: 600;
  line-height: 1.2;
  color: #2c3e50;
}

.month-name {
  font-size: 0.75rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Étel cella */
.meal-cell {
  min-width: 180px;
}

.meal-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  line-height: 1.4;
}

.meal-price {
  font-size: 0.85rem;
  color: #27ae60;
  font-weight: 600;
  margin-top: 0.25rem;
}

/* Allergén figyelmeztetés */
.allergen-warning {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  background: #fff3cd;
  border: 1px solid #ffeeba;
  border-radius: 50%;
  color: #856404;
  font-size: 0.8rem;
  cursor: help;
  transition: all 0.2s;
}

.allergen-warning:hover {
  transform: scale(1.1);
  background: #ffeeba;
}

/* Választás cella */
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
  width: 40px;
  height: 40px;
  border: 2px solid #e0e0e0;
  border-radius: 50%;
  background: white;
  color: #7f8c8d;
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
}

.choice-btn.active {
  background: var(--zold);
  border-color: var(--zold);
  color: white;
}

.choice-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.fixed-choice {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #27ae60;
  color: white;
  font-weight: 600;
}

.cancelled-choice {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #f8f9fa;
  color: #95a5a6;
  font-weight: 600;
  border: 2px dashed #e0e0e0;
}

.disabled-choice {
  color: #95a5a6;
  font-style: italic;
}

/* Státusz badge */
.status-cell {
  width: 120px;
}

.status-badge {
  display: inline-block;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-align: center;
  min-width: 90px;
}

.status-badge.ordered {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-badge.cancelled {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.status-badge.deadline-passed {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeeba;
}

.status-badge.available {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

/* Művelet cella */
.action-cell {
  width: 100px;
  text-align: center;
  vertical-align: middle;
}

.actions-inner {
  display: inline-flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
}

.action-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}

.action-btn:hover {
  opacity: 0.8;
}

.action-btn.order {
  background: #27ae60;
  color: white;
  width: auto;
  padding: 0 0.75rem;
  font-size: 0.85rem;
}

.action-btn.order:hover:not(:disabled) {
  background: #219150;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.action-btn.edit {
  background: #3498db;
  color: white;
}

.action-btn.cancel {
  background: #e74c3c;
  color: white;
}

.action-btn.reorder {
  background: #f39c12;
  color: white;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.disabled-action {
  display: block;
  color: #95a5a6;
  font-size: 0.85rem;
  font-style: italic;
  text-align: center;
  padding: 0.5rem;
}

/* Állapotok */
.loading-state,
.error-state,
.no-data {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  margin-bottom: 2rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f1f2f6;
  border-top-color: #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state p {
  color: #e74c3c;
  margin-bottom: 1rem;
}

.retry-btn {
  padding: 0.5rem 1.5rem;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.retry-btn:hover {
  background: #2980b9;
}

.no-data p {
  color: #95a5a6;
  margin: 0;
}

/* Információs sáv */
.info-footer {
  display: flex;
  gap: 2rem;
  padding: 1rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  color: #7f8c8d;
  font-size: 0.9rem;
  flex-wrap: wrap;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-icon {
  font-size: 1.1rem;
}

/* Reszponzív */
@media (max-width: 1200px) {
  .personal-orders {
    padding: 1rem;
  }
  
  .orders-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-controls {
    justify-content: space-between;
  }
  
  .orders-table {
    min-width: 1000px;
  }
  
  .orders-table-wrapper {
    overflow-x: auto;
  }
}

@media (max-width: 768px) {
  .info-footer {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .months-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.choice-btn.selected-order {
  background: #27ae60;
  border-color: #27ae60;
  color: white;
  opacity: 0.8;
  cursor: default;
}

.choice-btn.selected-order:hover {
  transform: none;
  box-shadow: none;
}

.no-option {
  width: 40px;
  height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #95a5a6;
  font-size: 0.9rem;
}

.deadline-passed {
  opacity: 0.7;
}
</style>