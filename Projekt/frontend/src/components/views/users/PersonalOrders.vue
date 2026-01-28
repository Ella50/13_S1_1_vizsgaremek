<template>
  <div class="personal-orders-container">

    <div class="page-header">
      <h1>Személyes rendelések</h1>
      <div class="header-actions">
                <!-- Tömeges műveletek gombok -->
        <div v-if="!loading && availableDates.length > 0" class="bulk-actions">
          <button 
            @click="selectAllDates" 
            class="btn-bulk btn-bulk-select"
            :disabled="selectedDates.length === availableDates.length"
          >
            Összes kijelölése
          </button>
          <button 
            @click="deselectAllDates" 
            class="btn-bulk btn-bulk-deselect"
            :disabled="selectedDates.length === 0"
          >
            Kijelölés törlése
          </button>
          <button 
            @click="bulkOrder('A')" 
            class="btn-bulk btn-bulk-order"
            :disabled="selectedDates.length === 0"
          >
            Kijelöltek megrendelése (A)
          </button>
          <button 
            @click="bulkCancel" 
            class="btn-bulk btn-bulk-cancel"
            :disabled="selectedDates.length === 0"
          >
            Kijelöltek lemondása
          </button>
        </div>

        <button @click="showMonthPicker = !showMonthPicker" class="btn-month-picker">
          {{ selectedMonthDisplay }}
        </button>
        <button @click="refreshData" class="btn-refresh">
          ⟳
          Frissítés
        </button>
      </div>
    </div>

    <!-- Hónap kiválasztó -->
    <div v-if="showMonthPicker" class="month-picker">
      <div class="month-picker-header">
        <h3>Hónap kiválasztása</h3>
        <button @click="showMonthPicker = false" class="btn-close">
          x
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
         ⪡
        </button>
        <span class="current-year">{{ currentYear }}</span>
        <button @click="changeYear(1)" class="btn-year-nav">
          ⪢
        </button>
      </div>
    </div>

        <!-- Tömeges műveletek státusz -->
    <div v-if="selectedDates.length > 0" class="bulk-status">
      <span class="bulk-count">{{ selectedDates.length }} dátum kijelölve</span>
      <button @click="deselectAllDates" class="btn-clear-selection">
        × Törlés
      </button>
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
        <div class="stat-item" v-if="userInfo">
          <span class="stat-label">Kedvezmény:</span>
          <span class="stat-value">{{ userInfo.hasDiscount ? 'Van' : 'Nincs' }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Kijelölve:</span>
          <span class="stat-value">{{ selectedDates.length }}</span>
        </div>
      </div>

      <!-- Táblázat -->
      <div class="table-responsive">
        <table class="orders-table">
          <thead>
            <tr>
              <th class="col-select" style="width: 50px;">
                <input 
                  type="checkbox" 
                  :checked="allDatesSelected"
                  @change="toggleAllDatesSelection"
                >
              </th>
              <th class="col-date">Dátum</th>
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
                'has-order': date.has_order,
                'selected': isDateSelected(date.date)
              }"
            >
              <!-- Kijelölés checkbox -->
              <td class="col-select">
                <input 
                  type="checkbox" 
                  :checked="isDateSelected(date.date)"
                  @change="toggleDateSelection(date.date)"
                  :disabled="!canOrderForDate(date.date) && !date.has_order"
                >
              </td>

              <!-- Dátum -->
              <td class="col-date">
                <div class="date-display">
                  <span class="date-number">{{ getDayNumber(date.date) }}</span>
                  <span class="date-month">{{ getMonthName(date.date) }}</span>
                </div>
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
                <div class="choice-options">
                  <!-- Aktív rendelésnél mindig mutatjuk a kiválasztott opciót -->
                  <span v-if="date.has_order && getOrderForDate(date.date)?.orderStatus === 'Rendelve'" 
                        class="selected-option-display">
                    {{ getOrderForDate(date.date)?.selectedOption || 'A' }}
                  </span>
                  
                  <!-- Rendelhető esetén rádió gombok -->
                  <div v-else-if="canOrderForDate(date.date)" class="radio-buttons">
                    <label class="radio-option">
                      <input 
                        type="radio" 
                        :name="'option-' + date.date" 
                        value="A"
                        :checked="selectedOptions[date.date] === 'A' || !selectedOptions[date.date]"
                        @change="selectOption(date.date, 'A')"
                      >
                      <span class="radio-label">A</span>
                    </label>
                    <label class="radio-option">
                      <input 
                        type="radio" 
                        :name="'option-' + date.date" 
                        value="B"
                        :checked="selectedOptions[date.date] === 'B'"
                        @change="selectOption(date.date, 'B')"
                      >
                      <span class="radio-label">B</span>
                    </label>
                  </div>
                  
                  <!-- Nem rendelhető -->
                  <span v-else class="choice-disabled">
                    <span v-if="isPastDeadline(date.date)" class="past-deadline">
                      Lejárt
                    </span>
                    <span v-else class="cannot-order">
                      Nem rendelhető
                    </span>
                  </span>
                </div>
              </td>

              <!-- Státusz -->
              <td class="col-status">
                <span v-if="date.has_order && getOrderForDate(date.date)?.orderStatus === 'Rendelve'" 
                      class="status-badge ordered">
                  Rendelve
                </span>
                <span v-else-if="date.has_order && getOrderForDate(date.date)?.orderStatus === 'Lemondva'" 
                      class="status-badge cancelled">
                  Lemondva
                </span>
                <span v-else-if="isPastDeadline(date.date)" class="status-badge deadline">
                  Lejárt
                </span>
                <span v-else class="status-badge available">
                  Rendelhető
                </span>
              </td>

              <!-- Műveletek -->
              <td class="col-actions">
                <div class="action-buttons">
   
                  <div v-if="date.has_order && getOrderForDate(date.date)?.orderStatus === 'Rendelve'">
                    <!-- Ha még módosítható (nem járt le a határidő) -->
                    <div v-if="canModifyOrder(getOrderForDate(date.date))" class="modify-buttons">
                      <!-- Új opció választása -->
                      <div class="modify-options">
                        <label class="modify-radio">
                          <input 
                            type="radio" 
                            :name="'modify-' + date.date" 
                            value="A"
                            :checked="getOrderForDate(date.date)?.selectedOption === 'A'"
                            @change="updateOrderOption(getOrderForDate(date.date).id, 'A')"
                          >
                          <span class="modify-label">A</span>
                        </label>
                        <label class="modify-radio">
                          <input 
                            type="radio" 
                            :name="'modify-' + date.date" 
                            value="B"
                            :checked="getOrderForDate(date.date)?.selectedOption === 'B'"
                            @change="updateOrderOption(getOrderForDate(date.date).id, 'B')"
                          >
                          <span class="modify-label">B</span>
                        </label>
                      </div>
                      <!-- Lemondás gomb -->
                      <button 
                        @click="cancelOrder(getOrderForDate(date.date).id)"
                        class="btn-action btn-cancel"
                        :title="getCancelDeadlineInfo(date.date)"
                      >
                        Lemondás
                      </button>
                    </div>
                    <!-- Ha már lejárt a módosítási határidő -->
                    <span v-else class="info-text" :title="getCancelDeadlineInfo(date.date)">
                      Nem módosítható
                    </span>
                  </div>
                  
                  <!-- Újrarendelés gomb (ha lemondta) -->
                  <div v-else-if="date.has_order && getOrderForDate(date.date)?.orderStatus === 'Lemondva'">
                    <button 
                      @click="reorderDate(date)"
                      class="btn-action btn-reorder"
                      title="Újrarendelés"
                    >
                      ↻ Újrarendel
                    </button>
                  </div>
                  
                  <!-- Rendelés gomb (ha nincs rendelés) -->
                  <button 
                    v-else-if="!date.has_order && canOrderForDate(date.date)"
                    @click="placeOrder(date)"
                    class="btn-action btn-order"
                  >
                    Rendelés
                  </button>
                  
                  <!-- Információ (ha nem rendelhető) -->
                  <span v-else class="no-action">
                    <span v-if="isPastDeadline(date.date)" class="info-text">
                      Határidő lejárt
                    </span>
                    <span v-else class="info-text">
                      -
                    </span>
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>


    </div>

    <!-- Kiszállítás információk -->
    <div class="delivery-info">
      <h3>Fontos információk</h3>
      <ul>
        <li>A rendelést az előző nap <strong>10:00-ig</strong> lehet leadni</li>
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
      userInfo: null,
      userLoading: false,


      selectedDates: [],
      bulkProcessing: false,

      modifyingOrderId: null, 


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
      if (!this.selectedMonth) return this.orders;
    
        return this.orders.filter(order => {
          const orderDate = new Date(order.orderDate);
          return orderDate.getFullYear() === this.selectedMonth.year && 
                orderDate.getMonth() + 1 === this.selectedMonth.month;
        });

    },
    
    activeOrdersCount() {
      return this.orders.filter(order => order.orderStatus === 'Rendelve').length;
    },
    
    totalCost() {
      return this.orders
        .filter(order => order.orderStatus === 'Rendelve')
        .reduce((sum, order) => {
          if (order.price && order.price.amount) {
            return sum + parseFloat(order.price.amount);
          }
          if (order.price_id) {
            return sum + 450;
          }
          return sum;
        }, 0);
    },

    
      avgCost() {
        return this.activeOrdersCount > 0 
          ? Math.round(this.totalCost / this.activeOrdersCount) 
          : 0;
      },
      allDatesSelected() {
      if (this.availableDates.length === 0) return false;
      const selectableDates = this.availableDates.filter(date => 
        this.canOrderForDate(date.date) || date.has_order
      );
      return selectableDates.length > 0 && 
             this.selectedDates.length === selectableDates.length;
    }
  
  },
  
  mounted() {
    this.loadInitialData()

    const now = new Date()
    this.selectedMonth = {
      year: now.getFullYear(),
      month: now.getMonth() + 1
    },
    this.loadUserInfo()
  },
  
  methods: {
      async loadUserInfo() {
        this.userLoading = true;
        try {
          const response = await AuthService.getUserInfo();
          this.userInfo = response.data || response;
        } catch (error) {
          console.error('Felhasználói adatok betöltése sikertelen:', error);
          this.userInfo = null;
        } finally {
          this.userLoading = false;
        }
      },
    async loadInitialData() {
      this.loading = true;
      this.error = null;
      
      try {
        // Először az elérhető dátumokat töltjük be
        const datesResponse = await AuthService.api.get('/user/personal-orders/available-dates');
        
        if (datesResponse.data && datesResponse.data.success === true) {
          this.availableDates = datesResponse.data.data || [];
          
          console.log('Elérhető dátumok betöltve:', this.availableDates);
          
          // Ha vannak dátumok, akkor betöltjük a rendeléseket
          if (this.availableDates.length > 0) {
            try {
              // FONTOS: Betöltjük a price relációt is
              const ordersResponse = await AuthService.api.get('/user/personal-orders', {
                params: {
                  with_price: true // Ha támogatja a backend
                }
              });
              
              if (ordersResponse.data && ordersResponse.data.success === true) {
                this.orders = ordersResponse.data.data?.orders || [];
                this.loadSelectedOptions();
                
                // Ellenőrizzük, hogy vannak-e ár adatok
                console.log('Rendelések betöltve:', this.orders.length);
                if (this.orders.length > 0) {
                  console.log('Első rendelés ár információ:', this.orders[0].price);
                }
              }
            } catch (ordersError) {
              console.warn('Rendelések betöltése sikertelen:', ordersError);
              this.orders = [];
            }
          }
          } else {
              // Ha nincs success, de van data
              if (datesResponse.data && Array.isArray(datesResponse.data)) {
                  this.availableDates = datesResponse.data;
              } else {
                  this.error = 'Hibás válaszformátum az API-tól';
              }
          }
          
      } catch (err) {
          console.error('Hiba az adatok betöltésekor:', err);
          
          if (err.response && err.response.status === 401) {
              this.error = 'Hitelesítési hiba. Kérlek jelentkezz be újra.';
          } else if (err.response && err.response.data) {
              this.error = err.response.data.message || 'Hiba történt az adatok betöltésekor.';
          } else {
              this.error = 'Hálózati hiba történt. Kérlek próbáld újra később.';
          }
      } finally {
          this.loading = false;
      }
  },

toggleDateSelection(date) {
      const index = this.selectedDates.indexOf(date);
      if (index === -1) {
        this.selectedDates.push(date);
      } else {
        this.selectedDates.splice(index, 1);
      }
    },
    
    // Dátum kijelölve-e
    isDateSelected(date) {
      return this.selectedDates.includes(date);
    },
    
    // Összes dátum kijelölése
    selectAllDates() {
      this.selectedDates = this.availableDates
        .filter(date => this.canOrderForDate(date.date) || date.has_order)
        .map(date => date.date);
    },
    
    // Összes dátum kijelölésének törlése
    deselectAllDates() {
      this.selectedDates = [];
    },
    
    // Összes dátum kijelölésének váltása
    toggleAllDatesSelection() {
      if (this.allDatesSelected) {
        this.deselectAllDates();
      } else {
        this.selectAllDates();
      }
    },
    
    // Tömeges rendelés
    async bulkOrder(defaultOption = 'A') {
      if (this.selectedDates.length === 0) {
        alert('Nincs dátum kijelölve!');
        return;
      }
      
      if (!confirm(`Biztosan szeretne rendelni ${this.selectedDates.length} napra?`)) {
        return;
      }
      
      this.bulkProcessing = true;
      let successCount = 0;
      let errorCount = 0;
      
      try {
        for (const dateStr of this.selectedDates) {
          const dateData = this.availableDates.find(d => d.date === dateStr);
          
          if (!dateData || dateData.has_order || !this.canOrderForDate(dateStr)) {
            errorCount++;
            continue;
          }
          
          try {
            const orderData = {
              date: dateStr,
              menuitems_id: dateData.menu_item_id,
              selectedOption: this.selectedOptions[dateStr] || defaultOption
            };
            
            const response = await AuthService.api.post('/user/personal-orders', orderData);
            
            if (response.data && response.data.success === true) {
              successCount++;
            } else {
              errorCount++;
            }
          } catch (err) {
            errorCount++;
            console.error(`Hiba a rendelésnél ${dateStr}:`, err);
          }
        }
        
        alert(`Tömeges rendelés eredménye:\nSikeres: ${successCount}\nSikertelen: ${errorCount}`);
        
        if (successCount > 0) {
          await this.loadInitialData();
          this.deselectAllDates();
        }
      } finally {
        this.bulkProcessing = false;
      }
    },
    
    // Tömeges lemondás
    async bulkCancel() {
      if (this.selectedDates.length === 0) {
        alert('Nincs dátum kijelölve!');
        return;
      }
      
      if (!confirm(`Biztosan szeretne lemondani ${this.selectedDates.length} rendelést?`)) {
        return;
      }
      
      this.bulkProcessing = true;
      let successCount = 0;
      let errorCount = 0;
      
      try {
        for (const dateStr of this.selectedDates) {
          const order = this.getOrderForDate(dateStr);
          
          if (!order || order.orderStatus !== 'Rendelve' || !this.canCancelOrder(order)) {
            errorCount++;
            continue;
          }
          
          try {
            const response = await AuthService.api.delete(`/user/personal-orders/${order.id}`);
            
            if (response.data && response.data.success === true) {
              successCount++;
            } else {
              errorCount++;
            }
          } catch (err) {
            errorCount++;
            console.error(`Hiba a lemondásnál ${dateStr}:`, err);
          }
        }
        
        alert(`Tömeges lemondás eredménye:\nSikeres: ${successCount}\nSikertelen: ${errorCount}`);
        
        if (successCount > 0) {
          await this.loadInitialData();
          this.deselectAllDates();
        }
      } finally {
        this.bulkProcessing = false;
      }
    },

async reorderDate(dateData) {
      if (!dateData.menu_item_id) {
        alert('Hiba: A menü ID nem található.');
        return;
      }
      
      const originalOrder = this.getOrderForDate(dateData.date);
      const selectedOption = originalOrder?.selectedOption || 'A';
      
      if (!confirm(`Biztosan újrarendeli ${selectedOption} opciót ${dateData.display_date}-ra?`)) {
        return;
      }
      
      try {
        const orderData = {
          date: dateData.date,
          menuitems_id: dateData.menu_item_id,
          selectedOption: selectedOption
        };
        
        const response = await AuthService.api.post('/user/personal-orders', orderData);
        
        if (response.data && response.data.success === true) {
          alert('Újrarendelés sikeres!');
          await this.loadInitialData();
        } else {
          throw new Error(response.data?.message || 'Hiba az újrarendelésnél');
        }
      } catch (err) {
        console.error('Hiba az újrarendelésnél:', err);
        alert(err.response?.data?.message || 'Hiba történt az újrarendelésnél.');
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
      this.selectedMonth = month;
      this.showMonthPicker = false;
      this.deselectAllDates();
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
      if (!dateData.menu_item_id) {
        alert('Hiba: A menü ID nem található.');
        return;
      }
      
      // Alapértelmezett A opció, ha nincs kiválasztva
      const selectedOption = this.selectedOptions[dateData.date] || 'A';
      
      if (dateData.has_order) {
        alert('Már van rendelése erre a napra.');
        return;
      }
      
      if (!dateData.can_order) {
        alert('A rendelési határidő már lejárt erre a napra.');
        return;
      }
      
      if (!confirm(`Biztosan rendel ${selectedOption} opciót ${dateData.display_date}-ra?`)) {
        return;
      }
      
      try {
        const orderData = {
          date: dateData.date,
          menuitems_id: dateData.menu_item_id,
          selectedOption: selectedOption
        };
        
        const response = await AuthService.api.post('/user/personal-orders', orderData);
        
        if (response.data && response.data.success === true) {
          alert('Rendelés sikeresen leadva!');
          await this.loadInitialData();
        } else {
          throw new Error(response.data?.message || 'Hiba a rendelés leadásakor');
        }
      } catch (err) {
        console.error('Hiba a rendelés leadásakor:', err);
        let errorMessage = 'Hiba történt a rendelés leadásakor.';
        
        if (err.response && err.response.data) {
          if (err.response.data.errors) {
            errorMessage = Object.values(err.response.data.errors).flat().join('\n');
          } else if (err.response.data.message) {
            errorMessage = err.response.data.message;
          }
        }
        
        alert(errorMessage);
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

    canModifyOrder(order) {
      if (!order || order.orderStatus !== 'Rendelve') return false
      
      const orderDate = new Date(order.orderDate)
      const modifyDeadline = new Date(orderDate)
      modifyDeadline.setDate(modifyDeadline.getDate() - 1) // előző nap
      modifyDeadline.setHours(10, 0, 0, 0) // 10:00-ig
      
      return new Date() <= modifyDeadline
    },

    async updateOrderOption(orderId, newOption) {
      if (!confirm(`Biztos, hogy ${newOption} opcióra szeretné változtatni?`)) {
        return
      }
      
      try {
        const response = await AuthService.api.patch(`/user/personal-orders/${orderId}/update-option`, {
          selectedOption: newOption
        })
        
        if (response.data && response.data.success === true) {
          alert('Opció sikeresen módosítva!')
          await this.loadInitialData()
        } else {
          throw new Error(response.data?.message || 'Hiba a módosítás során')
        }
      } catch (err) {
        console.error('Hiba az opció módosításakor:', err)
        alert(err.response?.data?.message || err.message || 'Hiba történt a módosítás során.')
      }
    },

    selectOption(date, option) {
      if (this.getOrderForDate(date)?.orderStatus === 'Rendelve') {
        // Ha már van rendelés, ne engedjük a változtatást itt
        return
      }
      this.selectedOptions[date] = option
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
      const modifyDeadline = new Date(orderDate)
      modifyDeadline.setDate(modifyDeadline.getDate() - 1) // előző nap
      modifyDeadline.setHours(10, 0, 0, 0) // 10:00
      
      const cancelDeadline = new Date(orderDate)
      cancelDeadline.setHours(8, 0, 0, 0) // aznap 8:00
      
      return `Módosítási határidő: ${modifyDeadline.toLocaleString('hu-HU')}\nLemondási határidő: ${cancelDeadline.toLocaleString('hu-HU')}`
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
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  background: transparent;
}

/* ===== FEJLÉC ===== */
.page-header {
  text-align: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f2f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.page-header h1 {
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}


.btn-month-picker,
.btn-refresh,
.btn-retry {
  padding: 0.55rem 1rem;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
}

.btn-month-picker:hover,
.btn-refresh:hover,
.btn-retry:hover {
  background: #2980b9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.btn-refresh {
  background: #2ecc71;
}
.btn-refresh:hover {
  background: #27ae60;
}

/* ===== HÓNAPVÁLASZTÓ (kártya) ===== */
.month-picker {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.month-picker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.month-picker-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.125rem;
}

.btn-close {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  color: #2c3e50;
  cursor: pointer;
  padding: 0.35rem 0.6rem;
  border-radius: 6px;
  transition: background 0.15s ease;
}
.btn-close:hover {
  background: #eef2f6;
}

.month-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.month-option {
  padding: 0.9rem 0.75rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  color: #2c3e50;
  transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease, border 0.12s ease;
}

.month-option:hover {
  background: #eef2f6;
  transform: translateY(-1px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}

.month-option.active {
  background: #3498db;
  border-color: #3498db;
  color: white;
}

.year-selector {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
}

.current-year {
  font-size: 1.15rem;
  color: #2c3e50;
  min-width: 80px;
  text-align: center;
}

.btn-year-nav {
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 0.45rem 0.8rem;
  cursor: pointer;
  color: #2c3e50;
  transition: background 0.15s ease;
}
.btn-year-nav:hover {
  background: #eef2f6;
}

/* ===== TÁBLÁZAT KÁRTYA ===== */
.orders-table-container {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  margin-bottom: 1.5rem;
}

/* stat sáv: egyszerű, nem gradient */
.stats-bar {
  display: flex;
  justify-content: space-around;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e0e0e0;
  flex-wrap: wrap;
}

.stat-item {
  text-align: center;
}

.stat-label {
  display: block;
  font-size: 0.85rem;
  color: #7f8c8d;
  margin-bottom: 0.25rem;

}

.stat-value {
  display: block;
  font-size: 1.35rem;
  color: #2c3e50;
}

/* table */
.table-responsive {
  overflow-x: auto;
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1200px;
}

.orders-table th {
  background: #f8f9fa;
  padding: 0.9rem 0.75rem;
  text-align: left;
  color: #2c3e50;
  border-bottom: 2px solid #f1f2f6;
  font-size: 0.85rem;
  letter-spacing: 0.02em;
}

.orders-table td {
  padding: 0.9rem 0.75rem;
  border-bottom: 1px solid #f1f2f6;
  vertical-align: top;
  color: #2c3e50;
}

.orders-table tbody tr:hover {
  background: #f8f9fa;
}

/* sor állapot */
.orders-table tbody tr.today {
  background: #f0f9ff;
}
.orders-table tbody tr.today td {
  border-left: 4px solid #3498db;
}
.orders-table tbody tr.past-date {
  opacity: 0.75;
}
.orders-table tbody tr.has-order {
  background: #f0fdf4;
}


.col-date { width: 100px; }
.col-day { width: 120px; }
.col-soup, .col-option, .col-other { width: 220px; }
.col-choice { width: 120px; }
.col-status { width: 150px; }
.col-actions { width: 170px; }


.date-display {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.date-number {
  font-size: 1.6rem;
  line-height: 1;
}
.date-month {
  font-size: 0.75rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.day-name {

  color: #2c3e50;
}

/* étel info */
.meal-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.meal-name {

  color: #2c3e50;
  line-height: 1.35;
}
.meal-allergens {
  font-size: 0.75rem;
  color: #e74c3c;
  background: #fdecea;
  border: 1px solid #fadbd8;
  padding: 0.15rem 0.45rem;
  border-radius: 6px;
  width: fit-content;
}
.meal-price {
  font-size: 0.85rem;
  color: #27ae60;

}

/* választás */
.choice-options {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}


.radio-label {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 2px solid #e0e0e0;
  border-radius: 999px;
  font-weight: 900;
  color: #2c3e50;
  background: #fff;
  transition: background 0.12s ease, border 0.12s ease, transform 0.12s ease;
}

.radio-option:hover .radio-label {
  transform: translateY(-1px);
}

.radio-option input[type="radio"]:checked + .radio-label {
  border-color: #3498db;
  background: #3498db;
  color: white;
}

.radio-option input[type="radio"]:disabled + .radio-label {
  opacity: 0.5;
  cursor: not-allowed;
}

.choice-disabled {
  text-align: center;
  font-size: 0.85rem;
  color: #7f8c8d;

}
.already-ordered { color: #27ae60; }
.past-deadline { color: #e74c3c; }

/* státusz badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.65rem;
  border-radius: 999px;
  font-size: 0.85rem;

  border: 1px solid transparent;
}
.status-badge.ordered {
  background: #eafaf1;
  color: #27ae60;
  border-color: #c9f2dc;
}
.status-badge.deadline {
  background: #fdecea;
  color: #e74c3c;
  border-color: #fadbd8;
}
.status-badge.available {
  background: #eaf2fb;
  color: #3498db;
  border-color: #d6eaf8;
}

/* action gombok */
.action-buttons {
  display: flex;
  justify-content: center;
}

.btn-action {
  padding: 0.45rem 0.8rem;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
}

.btn-action:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.btn-order {
  background: #27ae60;
  color: white;
}
.btn-order:hover:not(:disabled) {
  background: #219150;
}
.btn-order:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
  transform: none;
}

.btn-cancel {
  background: #f8f9fa;
  color: #e67e22;
  border: 1px solid #f1c40f;
}
.btn-cancel:hover {
  background: #fff7e6;
}

.no-action {
  color: #95a5a6;
  font-style: italic;
}

.table-info {
  display: flex;
  justify-content: center;
  gap: 2rem;
  padding: 1rem 1.25rem;
  background: #f8f9fa;
  border-top: 1px solid #e0e0e0;
  flex-wrap: wrap;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #7f8c8d;
  font-size: 0.9rem;

}

.info-icon {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}
.today-marker { background: #3498db; }
.deadline-marker { background: #e74c3c; }
.edit-marker { background: #f1c40f; }

.delivery-info {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 1.25rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.delivery-info h3 {
  margin-top: 0;
  margin-bottom: 0.9rem;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.delivery-info ul {
  margin: 0;
  padding-left: 1.25rem;
  color: #7f8c8d;
  line-height: 1.6;

}
.delivery-info li { margin-bottom: 0.5rem; }
.delivery-info strong { color: #2c3e50; }

.loading-container,
.error-container,
.no-orders {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f1f2f6;
  border-top-color: #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-container { color: #e74c3c; }
.error-icon, .no-orders-icon { font-size: 42px; margin-bottom: 0.75rem; }
.no-orders { color: #95a5a6; }


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

/* ===== RESZPONZÍV ===== */
@media (max-width: 1200px) {
  .personal-orders-container { padding: 1rem; }
  .month-grid { grid-template-columns: repeat(2, 1fr); }
  .page-header { flex-direction: column; align-items: stretch; }
  .header-actions { justify-content: center; }
  .bulk-actions { justify-content: center; margin-right: 0; margin-bottom: 0.5rem; }
}

@media (max-width: 768px) {
  .month-grid { grid-template-columns: 1fr; }
  .stats-bar { flex-direction: column; align-items: center; }
  .bulk-actions { flex-direction: column; }
  .btn-bulk { width: 100%; }
}

.bulk-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-right: 1rem;
}

.btn-bulk {
  padding: 0.4rem 0.8rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.15s ease;
}

.btn-bulk:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-bulk-select {
  background: #3498db;
  color: white;
}

.btn-bulk-select:hover:not(:disabled) {
  background: #2980b9;
}

.btn-bulk-deselect {
  background: #f8f9fa;
  color: #7f8c8d;
  border: 1px solid #e0e0e0;
}

.btn-bulk-deselect:hover:not(:disabled) {
  background: #eef2f6;
}

.btn-bulk-order {
  background: #27ae60;
  color: white;
}

.btn-bulk-order:hover:not(:disabled) {
  background: #219150;
}

.btn-bulk-cancel {
  background: #e74c3c;
  color: white;
}

.btn-bulk-cancel:hover:not(:disabled) {
  background: #c0392b;
}

.bulk-status {
  background: #eaf2fb;
  border: 1px solid #d6eaf8;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  margin: 1rem 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bulk-count {
  font-weight: 500;
  color: #2c3e50;
}

.btn-clear-selection {
  background: transparent;
  border: 1px solid #3498db;
  color: #3498db;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
}

.btn-clear-selection:hover {
  background: #3498db;
  color: white;
}

/* Kijelölt sor stílus */
.orders-table tbody tr.selected {
  background: #eaf2fb !important;
}

.orders-table tbody tr.selected td {
  border-left: 4px solid #3498db;
}

/* Újrarendelés gomb */
.btn-reorder {
  background: #f39c12;
  color: white;
}

.btn-reorder:hover {
  background: #d68910;
}

.info-text {
  color: #95a5a6;
  font-size: 0.85rem;
  font-style: italic;
}

/* Radio button kijelölés (alapértelmezett A opció) */
.radio-option input[type="radio"]:not(:checked) + .radio-label {
  opacity: 0.7;
}

.radio-option input[type="radio"]:checked + .radio-label {
  border-color: #3498db;
  background: #3498db;
  color: white;
}
.selected-option-display {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 2px solid #27ae60;
  border-radius: 999px;
  font-weight: 900;
  color: #27ae60;
  background: #eafaf1;
}

/* Módosítási gombok */
.modify-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}

.modify-options {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.modify-radio {
  cursor: pointer;
}

.modify-radio input[type="radio"] {
  display: none;
}

.modify-label {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 2px solid #3498db;
  border-radius: 999px;
  font-weight: 900;
  color: #3498db;
  background: #eaf2fb;
  transition: all 0.12s ease;
}

.modify-radio input[type="radio"]:checked + .modify-label {
  background: #3498db;
  color: white;
  border-color: #3498db;
}

.modify-label:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
}

/* Rendelés gomb */
.btn-order {
  background: #27ae60;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-order:hover:not(:disabled) {
  background: #219150;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

/* Lemondás gomb */
.btn-cancel {
  background: #e74c3c;
  color: white;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: #c0392b;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

/* Újrarendelés gomb */
.btn-reorder {
  background: #f39c12;
  color: white;
  padding: 0.5rem 0.9rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.3rem;
}

.btn-reorder:hover {
  background: #d68910;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

/* Státusz badge - Lemondva */
.status-badge.cancelled {
  background: #fdecea;
  color: #e74c3c;
  border-color: #fadbd8;
}

/* Információs szöveg */
.info-text {
  color: #95a5a6;
  font-size: 0.85rem;
  font-style: italic;
  cursor: help;
}

/* Reszponzív beállítások */
@media (max-width: 768px) {
  .modify-buttons {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .modify-options {
    order: 2;
    width: 100%;
    margin-top: 0.5rem;
  }
  
  .btn-cancel {
    order: 1;
  }
}
</style>