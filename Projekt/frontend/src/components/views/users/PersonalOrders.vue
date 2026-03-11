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

            <!-- Művelet cella - a reorder részt kicseréljük -->
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
                    v-if="canCancelOrder(date.date)"
                    @click="cancelOrder(date.order_id)"
                    class="action-btn cancel"
                    :title="'Lemondás (határidő: ' + getCancelDeadlineInfo(date.date) + ')'"
                  >
                    ✗
                  </button>
                  <span v-else class="disabled-action" :title="'Határidő lejárt: ' + getDeadlineInfo(date.date)">
                    Lejárt
                  </span>
                </template>
                
                <template v-else>
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
        <span>Rendelés/módosítás: előző munkanap 10:00-ig | Lemondás: aznap 8:00-ig</span>
      </div>
      <div class="info-item">
        <span class="info-icon">⚠️</span>
        <span>Allergén figyelmeztetés (piros szöveg)</span>
      </div>
      <div v-if="userInfo?.hasDiabetes" class="info-item">
        <span class="info-icon">🩺</span>
        <span>Cukorbeteg mód: automatikusan A opció</span>
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
    }
  },
  
  computed: {
    availableMonths() {
      const months = []
      const currentYear = new Date().getFullYear()
      
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
      this.tempSelections = {}
      this.loadOrders()
    },
    
    isCurrentMonth(month) {
      return this.selectedMonth?.year === month.year && 
             this.selectedMonth?.month === month.month
    },
    
    // Dátum formázás
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
  
  // Előző munkanap 10:00
  let deadline = this.getPreviousWorkingDay(orderDate)
  deadline.setHours(10, 0, 0, 0)
  
  return now <= deadline
},

canCancelOrder(dateString) {
  return this.canModifyOrder(dateString)
},

getPreviousWorkingDay(date) {
  const result = new Date(date)
  const dayOfWeek = date.getDay() // 0 = vasárnap, 1 = hétfő, ..., 6 = szombat
  
  if (dayOfWeek === 1) { // Hétfő
    // Péntek (3 nappal korábban)
    result.setDate(date.getDate() - 3)
  } else if (dayOfWeek === 0) { // Vasárnap
    // Péntek (2 nappal korábban)
    result.setDate(date.getDate() - 2)
  } else { // Kedd - Szombat
    // Előző nap
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
      // Aktív rendelésnél a kiválasztott opciót mutatjuk
      if (date.order_status === 'Rendelve') {
        return date.selected_option === option
      }
      // Lemondott vagy nincs rendelés: ideiglenes választást mutatjuk
      return this.tempSelections[date.date] === option
    },
    
    isOptionDisabled(date, option) {
      // Cukorbetegek nem kattinthatnak
      if (this.userInfo?.hasDiabetes) return true
      
      // Ha van aktív rendelés és nem módosítható, akkor tiltott
      if (date.order_status === 'Rendelve' && !this.canModifyOrder(date.date)) return true
      
      // Ha nincs rendelés (vagy le van mondva) és lejárt a határidő, akkor tiltott
      if ((!date.has_order || date.order_status === 'Lemondva') && !this.canModifyOrder(date.date)) return true
      
      return false
    },
    
    handleOptionClick(date, option) {
        // Ha van aktív rendelés és módosítható
        if (date.order_status === 'Rendelve' && this.canModifyOrder(date.date)) {
          this.changeOrderOption(date, option)
        } 
        // Ha nincs rendelés VAGY le van mondva, és módosítható
        else if ((!date.has_order || date.order_status === 'Lemondva') && this.canModifyOrder(date.date)) {
          this.selectOptionForNewOrder(date, option)
        }
    },
      
  canPlaceOrder(date) {
    // Cukorbetegeknek automatikusan mehet
    if (this.userInfo?.hasDiabetes) return true
    // Normál felhasználóknak kell opció választás
    return !!this.tempSelections[date.date]
  },
    
    getDiabeticChoice(date) {
      return 'A'
    },
    
    // Allergén figyelmeztetések
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
      
      if (date.getDay() === 1) {
        deadline.setDate(date.getDate() - 3)
      }
      
      return deadline.toLocaleDateString('hu-HU') + ' 10:00'
    },
    
    getCancelDeadlineInfo(dateString) {
      const date = new Date(dateString)
      const deadline = new Date(date)
      deadline.setHours(8, 0, 0, 0)
      return deadline.toLocaleDateString('hu-HU') + ' 8:00'
    },
    
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
    let response
    
    console.log('Rendelési adatok:', {
      has_order: date.has_order,
      order_status: date.order_status,
      order_id: date.order_id,
      selectedOption
    })
    
    // Ha van már lemondott rendelés erre a napra, akkor újraaktiváljuk
    if (date.has_order && date.order_status === 'Lemondva' && date.order_id) {
      console.log('Lemondott rendelés újraaktiválása:', date.order_id)
      response = await AuthService.api.post(`/user/personal-orders/${date.order_id}/reactivate`, {
        selectedOption
      })
    } else {
      console.log('Új rendelés létrehozása')
      response = await AuthService.api.post('/user/personal-orders', {
        date: date.date,
        menuitems_id: date.menu_item_id,
        selectedOption
      })
    }
    
    console.log('Válasz:', response.data)
    
    if (response.data?.success) {
      alert('Rendelés sikeresen leadva!')
      delete this.tempSelections[date.date]
      await this.loadOrders()
    } else {
      alert(response.data?.message || 'Ismeretlen hiba történt')
    }
  } catch (err) {
    console.error('Rendelési hiba:', err)
    console.error('Hiba részletei:', err.response?.data)
    alert(err.response?.data?.message || 'Hiba történt a rendelés során')
  }
},
    
    async changeOrderOption(date, newOption) {
      if (!date.has_order || !date.order_id) return
      
      if (date.selected_option === newOption) return
      
      if (!this.canModifyOrder(date.date)) {
        alert('A módosítási határidő lejárt!')
        return
      }
      
      if (!confirm(`Biztosan módosítani szeretnéd a ${newOption} opcióra?`)) {
        return
      }
      
      try {
        const response = await AuthService.api.patch(`/user/personal-orders/${date.order_id}/update-option`, {
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
  
  if (!this.canCancelOrder(date.date)) {
    alert('A lemondási határidő lejárt! (aznap 8:00-ig lehet lemondani)')
    return
  }
  
  if (!confirm('Biztosan le szeretnéd mondani ezt a rendelést?')) {
    return
  }
  
  try {
    const response = await AuthService.api.delete(`/user/personal-orders/${orderId}/cancel`)
    
    if (response.data?.success) {
      alert('Rendelés sikeresen lemondva!')
      // Ideiglenes választás törlése erre a napra, ha lenne
      if (this.tempSelections[date.date]) {
        delete this.tempSelections[date.date]
      }
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
      
      const selectedOption = date.selected_option || 'A'
      
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
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

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

/* Allergén figyelmeztetés piros szöveg */
.meal-name.allergen-meal {
  color: #e74c3c;
  font-weight: bold;
}

.meal-price {
  font-size: 0.85rem;
  color: #27ae60;
  font-weight: 600;
  margin-top: 0.25rem;
}

/* Allergén ikon */
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

.status-badge.cancelled { /*le lett mondva, de még rendelhető*/
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.status-badge.deadline-passed {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeeba;
}

.status-badge.available { /*rendelhető*/
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