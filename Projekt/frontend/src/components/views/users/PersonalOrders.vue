<template>
  <div class="personal-orders-container">

    <div class="page-header">
      <h1>Személyes rendelések</h1>
      <div class="header-actions">
        <!-- Tömeges műveletek -->
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
          ⟳ Frissítés
        </button>
      </div>
    </div>


<div v-if="showMonthPicker" class="month-picker-simple">
  <div class="month-picker-header">
    <h3>Hónap kiválasztása</h3>
    <button @click="showMonthPicker = false" class="btn-close">×</button>
  </div>
  
  <div class="month-selector">
    <select v-model="selectedMonthValue" class="month-select" @change="onMonthChange">
      <option value="" disabled selected>Válassz hónapot...</option>
      <option 
        v-for="month in availableSchoolMonths" 
        :key="`${month.year}-${month.month}`"
        :value="{ year: month.year, month: month.month }"
      >
        {{ month.display }}
      </option>
    </select>
  </div>
  
  <div class="month-navigation">
    <button @click="previousMonth" class="btn-month-nav" :disabled="!hasPreviousMonth">
      ← Előző hónap
    </button>
    <span class="current-month-display">{{ currentMonthDisplay }}</span>
    <button @click="nextMonth" class="btn-month-nav" :disabled="!hasNextMonth">
      Következő hónap →
    </button>
  </div>
</div>


    <div v-if="selectedDates.length > 0" class="bulk-status">
      <span class="bulk-count">{{ selectedDates.length }} dátum kijelölve</span>
      <button @click="deselectAllDates" class="btn-clear-selection">× Törlés</button>
    </div>


    <div v-if="loading" class="loading-container">
      <div class="loading-spinner"></div>
      <p>Rendelések betöltése...</p>
    </div>

 
    <div v-else-if="error" class="error-container">
      <div class="error-icon">x</div>
      <p>{{ error }}</p>
      <button @click="loadOrders" class="btn-retry">Újra próbál</button>
    </div>


    <div v-else-if="filteredOrders.length === 0 && availableDates.length === 0" class="no-orders">
      <p>Nincsenek elérhető rendelési napok a kiválasztott hónapban</p>
    </div>


    <div v-else class="orders-table-container">

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
        
              <td class="col-select">
                <input 
                  type="checkbox" 
                  :checked="isDateSelected(date.date)"
                  @change="toggleDateSelection(date.date)"
                  :disabled="!canOrderForDate(date.date) && !date.has_order"
                >
              </td>

        
              <td class="col-date">
                <div class="date-display">
                  <span class="date-number">{{ getDayNumber(date.date) }}</span>
                  <span class="date-month">{{ getMonthName(date.date) }}</span>
                </div>
              </td>

           
              <td class="col-soup">
                <div class="meal-info">
                  <div class="meal-name">{{ date.menu.soup?.mealName || 'Nincs adat' }}</div>

                  <div v-if="hasAllergenWarning(date, 'Leves')" class="allergen-warning">
                  <span class="warning-icon">⚠️</span>
                  <span class="warning-text">Allergén figyelmeztetés!</span>
                  </div>

                </div>
              </td>

             
              <td class="col-option option-a">
                <div class="meal-info">
                  <div class="meal-name">{{ date.menu.optionA?.mealName || 'Nincs adat' }}</div>
                  <div class="meal-price">{{ date.menu.optionA?.price || '' }}</div>

                  <div v-if="hasAllergenWarning(date, 'A opció')" class="allergen-warning">
                  <span class="warning-icon">⚠️</span>
                  <span class="warning-text">Allergén figyelmeztetés!</span>
                  </div>

                </div>
              </td>

          
              <td class="col-option option-b">
                <div class="meal-info">
                  <div class="meal-name">{{ date.menu.optionB?.mealName || 'Nincs adat' }}</div>
                  <div class="meal-price">{{ date.menu.optionB?.price || '' }}</div>

                  <div v-if="hasAllergenWarning(date, 'B opció')" class="allergen-warning">
                  <span class="warning-icon">⚠️</span>
                  <span class="warning-text">Allergén figyelmeztetés!</span>

                  </div>
                </div>
              </td>

             
              <td class="col-other">
                <div class="meal-info" v-if="date.menu.other">
                  <div class="meal-name">{{ date.menu.other?.mealName || 'Nincs adat' }}</div>
                </div>
                <span v-else class="no-other">-</span>
              </td>

              <!-- VÁLASZTÁS -->
              <td class="col-choice">
                <div class="choice-display">

                  <template v-if="date.has_order">

                    <template v-if="date.order_status === 'Rendelve'">
        
                      <div v-if="canModifyOrder(getOrderForDate(date.date))" class="editable-option">
                        <div class="option-buttons">
                          <button 
                            @click="changeOrderOption(date, 'A')" 
                            :class="{ active: date.selected_option === 'A' }"
                            class="option-btn edit"
                          >
                            A
                          </button>
                          <button 
                            @click="changeOrderOption(date, 'B')" 
                            :class="{ active: date.selected_option === 'B' }"
                            class="option-btn edit"
                          >
                            B
                          </button>
                        </div>
                      </div>

                      <div v-else class="fixed-option">
                        <span class="selected-option-display fixed">
                          {{ date.selected_option || 'A' }}
                        </span>
                      </div>
                    </template>
                    
                    <!-- Lemondott rendelés  -->
                    <template v-else-if="date.order_status === 'Lemondva'">
                      <span class="cancelled-option-display">
                        {{ date.selected_option || 'A' }}
                      </span>
                    </template>
                  </template>
                  
                  <template v-else-if="!date.has_order && canOrderForDate(date.date)">
                    <div class="option-buttons">
                      <button 
                        @click="selectOptionForNewOrder(date.date, 'A')" 
                        :class="{ active: (selectedOptions[date.date] || 'A') === 'A' }"
                        class="option-btn new"
                      >
                        A
                      </button>
                      <button 
                        @click="selectOptionForNewOrder(date.date, 'B')" 
                        :class="{ active: selectedOptions[date.date] === 'B' }"
                        class="option-btn new"
                      >
                        B
                      </button>
                    </div>
                  </template>
                  
      
                  <template v-else>
                    <span class="choice-disabled">
                      {{ isPastDeadline(date.date) ? 'Lejárt' : '-' }}
                    </span>
                  </template>
                </div>
              </td>

              <!-- STÁTUSZ -->
              <td class="col-status">
                <span v-if="date.order_status === 'Rendelve'" 
                      class="status-badge ordered">
                  Rendelve
                </span>
                <span v-else-if="date.order_status === 'Lemondva'" 
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

              <!-- MŰVELETEK  -->
              <td class="col-actions">
                <div class="action-buttons">

                  <template v-if="date.order_status === 'Rendelve'">
                    <!-- Még módosítható/lemondható -->
                    <button 
                      v-if="canModifyOrder(getOrderForDate(date.date))"
                      @click="cancelOrder(date.order_id)"
                      class="btn-action btn-cancel"
                      :title="getCancelDeadlineInfo(date.date)"
                    >
                      Lemondás
                    </button>
                    

                    <span v-else-if="!canModifyOrder(getOrderForDate(date.date)) && canCancelOrder(getOrderForDate(date.date))" 
                                class="info-text" 
                                :title="'Módosítási határidő lejárt, de még lemondható ma 8:00-ig'">
                            Csak lemondható
                    </span>
                    <span v-else class="info-text" :title="getCancelDeadlineInfo(date.date)">
                        Nem módosítható
                    </span>
                  </template>
                  
   
                  <template v-else-if="date.order_status === 'Lemondva'">
                    <!-- Csak akkor jelenjen meg az újrarendelés, ha még nem járt le a határidő -->
                    <button 
                      v-if="canOrderForDate(date.date)"
                      @click="reorderDate(date)"
                      class="btn-action btn-reorder"
                      title="Újrarendelés"
                    >
                      ↻ Újrarendel
                    </button>
                    <span v-else class="info-text">
                      Határidő lejárt
                    </span>
                  </template>
                  
             
                  <template v-else-if="!date.has_order && canOrderForDate(date.date)">
                    <button 
                      @click="placeOrder(date)"
                      class="btn-action btn-order"
                    >
                      Rendelés
                    </button>
                  </template>
                  
             
                   <template v-else>
                    <span class="info-text">
                      {{ isPastDeadline(date.date) ? 'Határidő lejárt' : '-' }}
                    </span>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

   
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
      loading: true,
      error: null,
      orders: [], // MINDEN rendelés (Rendelve és Lemondva is)
      availableDates: [],
      selectedMonth: null,
      selectedYear: new Date().getFullYear(),
      showMonthPicker: false,
      selectedOptions: {},
      orderDeadlineHour: 10,
      cancelDeadlineHour: 8,

      availableMonths: [],
    }
  },
  
  computed: {
    selectedMonthValue: {
      get() {
        return this.selectedMonth;
      },
      set(value) {
        this.selectedMonth = value;
      }
    },
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
    availableSchoolMonths() {
    const months = [];
    const currentYear = this.selectedYear;
    
    // Szeptembertől decemberig
    for (let month = 9; month <= 12; month++) {
      months.push({
        year: currentYear,
        month: month,
        display: this.getMonthNameFull(month) + ' ' + currentYear
      });
    }
    
    // Januártól júniusig (következő év)
    for (let month = 1; month <= 6; month++) {
      months.push({
        year: currentYear + 1,
        month: month,
        display: this.getMonthNameFull(month) + ' ' + (currentYear + 1)
      });
    }
    
    return months;
  },
  
  currentMonthDisplay() {
    if (!this.selectedMonth) return 'Nincs kiválasztva';
    return this.getMonthNameFull(this.selectedMonth.month) + ' ' + this.selectedMonth.year;
  },
  
  hasPreviousMonth() {
    if (!this.selectedMonth) return false;
    
    const currentIndex = this.availableSchoolMonths.findIndex(
      m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
    );
    
    return currentIndex > 0;
  },
  
  hasNextMonth() {
    if (!this.selectedMonth) return false;
    

    const currentIndex = this.availableSchoolMonths.findIndex(
      m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
    );
    
    return currentIndex < this.availableSchoolMonths.length - 1;
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
    const now = new Date()
    this.selectedMonth = {
      year: now.getFullYear(),
      month: now.getMonth() + 1
    };
    this.loadInitialData();
    this.loadUserInfo();
  },
  
  methods: {
    previousMonth() {
    if (!this.hasPreviousMonth) return;
    
    const currentIndex = this.availableSchoolMonths.findIndex(
      m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
    );
    
    if (currentIndex > 0) {
      const prevMonth = this.availableSchoolMonths[currentIndex - 1];
      this.selectMonth(prevMonth);
    }
  },
  
  // Következő hónap
  nextMonth() {
    if (!this.hasNextMonth) return;
    
    const currentIndex = this.availableSchoolMonths.findIndex(
      m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
    );
    
    if (currentIndex < this.availableSchoolMonths.length - 1) {
      const nextMonth = this.availableSchoolMonths[currentIndex + 1];
      this.selectMonth(nextMonth);
    }
  },
   onMonthChange() {
    if (this.selectedMonth) {
      this.selectMonth(this.selectedMonth);
    }
  },
  
  selectMonth(month) {
    this.selectedMonth = {
      year: month.year,
      month: month.month
    };
    this.showMonthPicker = false;
    this.deselectAllDates();
    this.loadOrdersForSelectedMonth();
  },
    onMonthChange() {
      this.selectMonth(this.selectedMonth);
    },

    getMonthNameFull(month) {
        const months = [
          'Január', 'Február', 'Március', 'Április', 'Május', 'Június',
          'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'
        ];
        return months[month - 1];
      },


    hasAllergenWarning(dateData, mealType) {
      if (!dateData.allergen_warnings || !Array.isArray(dateData.allergen_warnings)) {
        return false;
      }
      
      return dateData.allergen_warnings.some(warning => warning.meal === mealType);
    },

    getAllergenWarningDetails(dateData, mealType) {
      if (!dateData.allergen_warnings || !Array.isArray(dateData.allergen_warnings)) {
        return null;
      }
      
      return dateData.allergen_warnings.find(warning => warning.meal === mealType);
    },

    showAllergenDetails(dateData, mealType) {
      const warning = this.getAllergenWarningDetails(dateData, mealType);
      if (warning) {
        alert(`Figyelmeztetés: A(z) ${warning.meal_name} olyan allergént tartalmaz, amire Ön érzékeny!`);
      }
    },

async loadAvailableMonths() {
    if (!this.selectedMonth) {
      const now = new Date();
      const currentMonth = now.getMonth() + 1;
      const currentYear = now.getFullYear();
      

      if (currentMonth >= 7 && currentMonth <= 8) {
        this.selectedMonth = { year: currentYear, month: 9 };
      } else {
        this.selectedMonth = { year: currentYear, month: currentMonth };
      }
    }
  },
    async loadUserInfo() {
  this.userLoading = true;
  try {

    const response = await AuthService.api.get('/user/me');
    this.userInfo = response.data?.data || response.data;
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
    // Először töltsük be a hónapokat
    await this.loadAvailableMonths();
    
    // Aztán töltsük be az aktuális hónap adatait
    await this.loadOrdersForSelectedMonth();
    
    // És a rendeléseket is
    try {
      const ordersResponse = await AuthService.api.get('/user/personal-orders', {
        params: { with_price: true }
      });
      
      if (ordersResponse.data && ordersResponse.data.success === true) {
        this.orders = ordersResponse.data.data?.orders || [];
        this.loadSelectedOptions();
      }
    } catch (ordersError) {
      console.warn('Rendelések betöltése sikertelen:', ordersError);
      this.orders = [];
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
    
    // DÁTUM KI JELÖLÉS
    toggleDateSelection(date) {
      const index = this.selectedDates.indexOf(date);
      if (index === -1) {
        this.selectedDates.push(date);
      } else {
        this.selectedDates.splice(index, 1);
      }
    },
    
    isDateSelected(date) {
      return this.selectedDates.includes(date);
    },
    
    selectAllDates() {
      this.selectedDates = this.availableDates
        .filter(date => this.canOrderForDate(date.date) || date.has_order)
        .map(date => date.date);
    },
    
    deselectAllDates() {
      this.selectedDates = [];
    },
    
    toggleAllDatesSelection() {
      if (this.allDatesSelected) {
        this.deselectAllDates();
      } else {
        this.selectAllDates();
      }
    },
    
    // OPCIÓ VÁLASZTÁS
    selectOptionForNewOrder(date, option) {
      this.selectedOptions[date] = option;
    },
    
    async changeOrderOption(dateData, newOption) {
      const order = this.getOrderForDate(dateData.date);
      
      if (!order || order.selectedOption === newOption) return;
      
      if (!confirm(`Biztosan ${newOption} opcióra szeretné változtatni a ${this.formatDate(dateData.date)}-i rendelést?`)) {
        return;
      }
      
      try {
        const response = await AuthService.api.patch(`/user/personal-orders/${order.id}/update-option`, {
          selectedOption: newOption
        });
        
        if (response.data && response.data.success === true) {
          alert('Opció sikeresen módosítva!');
          const index = this.orders.findIndex(o => o.id === order.id);
          if (index !== -1) {
            this.orders[index].selectedOption = newOption;
          }
          await this.loadInitialData();
        } else {
          throw new Error(response.data?.message || 'Hiba a módosítás során');
        }
      } catch (err) {
        console.error('Hiba az opció módosításakor:', err);
        alert(err.response?.data?.message || 'Hiba történt a módosítás során.');
      }
    },
    
    // RENDELÉSI MŰVELETEK
    async placeOrder(dateData) {
      if (!dateData.menu_item_id) {
        alert('Hiba: A menü ID nem található.');
        return;
      }
      
      const selectedOption = this.selectedOptions[dateData.date] || 'A';
      
      if (dateData.has_order) {
        alert('Már van rendelése erre a napra.');
        return;
      }
      
      if (!dateData.can_order) {
        alert('A rendelési határidő már lejárt erre a napra.');
        return;
      }
      
      if (!confirm(`Biztosan rendel ${selectedOption} opciót ${this.formatDate(dateData.date)}-ra?`)) {
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
        alert(err.response?.data?.message || 'Hiba történt a rendelés leadásakor.');
      }
    },
    
    // LEMONDÁS - CSAK EGY METÓDUS LEGYEN!
    async cancelOrder(orderId) {
      if (!confirm('Biztosan le szeretnéd mondani ezt a rendelést?')) {
        return;
      }
      
      try {
        // Használjuk a /cancel végpontot
        const response = await AuthService.api.delete(`/user/personal-orders/${orderId}/cancel`);
        
        if (response.data && response.data.success === true) {
          alert('Rendelés sikeresen lemondva!');
          
          // Frissítjük a helyi adatokat
          await this.loadInitialData();
        } else {
          throw new Error(response.data?.message || 'Hiba a lemondás során');
        }
      } catch (err) {
        console.error('Hiba a lemondás során:', err);
        
        // Specifikus hibakezelés
        if (err.response?.status === 400) {
          alert(err.response?.data?.message || 'A lemondási határidő lejárt.');
        } else if (err.response?.status === 404) {
          alert('A rendelés nem található.');
        } else if (err.response?.status === 409) {
          alert('Csak aktív rendelés lemondható.');
        } else {
          alert(err.response?.data?.message || err.message || 'Hiba történt a lemondás során.');
        }
      }
    },
    

  async reorderDate(dateData) {
    if (!dateData.menu_item_id) {
      alert('Hiba: A menü ID nem található.');
      return;
    }
    
    const originalOrder = this.getOrderForDate(dateData.date);
    const selectedOption = originalOrder?.selected_option || 'A'; 
    
    if (!confirm(`Biztosan újrarendeli ${selectedOption} opciót ${this.formatDate(dateData.date)}-ra?`)) {
      return;
    }
    
    try {
      const response = await AuthService.api.post(`/user/personal-orders/${dateData.order_id}/reorder`, {
        selectedOption: selectedOption
      });
      
      if (response.data && response.data.success === true) {
        alert('Újrarendelés sikeres!' + (response.data.allergen_warnings?.length ? ' (Figyelem: allergén tartalom!)' : ''));
        await this.loadInitialData();
      } else {
        throw new Error(response.data?.message || 'Hiba az újrarendelésnél');
      }
    } catch (err) {
      console.error('Hiba az újrarendelésnél:', err);
      
      // Specifikus hibakezelés
      if (err.response?.status === 400) {
        if (err.response.data?.message?.includes('Csak lemondott rendelés')) {
          alert('Ez a rendelés már aktív, nem kell újrarendelni.');
        } else if (err.response.data?.message?.includes('határidő')) {
          alert('A rendelési határidő már lejárt erre a napra.');
        } else {
          alert(err.response.data?.message || 'Hiba az újrarendelésnél.');
        }
      } else if (err.response?.status === 409) {
        alert('Már van aktív rendelése erre a napra.');
      } else {
        alert(err.response?.data?.message || 'Hiba történt az újrarendelésnél.');
      }
    }
  },
    
    // SEGÉDFÜGGVÉNYEK
    loadSelectedOptions() {
      this.selectedOptions = {};
      this.orders.forEach(order => {
        if (order.orderStatus === 'Rendelve') {
          this.selectedOptions[order.orderDate] = order.selectedOption;
        }
      });
    },
    
    getDayNumber(dateString) {
      const date = new Date(dateString);
      return date.getDate();
    },
    
    getMonthName(dateString) {
      const date = new Date(dateString);
      const months = ['Jan', 'Feb', 'Már', 'Ápr', 'Máj', 'Jún', 'Júl', 'Aug', 'Szep', 'Okt', 'Nov', 'Dec'];
      return months[date.getMonth()];
    },
    
    isToday(dateString) {
      const today = new Date().toISOString().split('T')[0];
      return dateString === today;
    },
    
    isPastDate(dateString) {
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const date = new Date(dateString);
      return date < today;
    },
    
    isPastDeadline(dateString) {
      const orderDate = new Date(dateString);
      const deadlineDate = new Date(orderDate);
      deadlineDate.setDate(deadlineDate.getDate() - 1);
      deadlineDate.setHours(this.orderDeadlineHour, 0, 0, 0);
      return new Date() > deadlineDate;
    },
    
    canOrderForDate(dateString) {
      return !this.isPastDeadline(dateString) && !this.getOrderForDate(dateString);
    },
    
    // PersonalOrders.vue - javítsd a canModifyOrder metódust

    // Módosíthatóság ellenőrzése
    canModifyOrder(order) {
      if (!order || order.orderStatus !== 'Rendelve') return false;
      
      const orderDate = new Date(order.orderDate);
      const now = new Date();
      const modifyDeadline = new Date(orderDate);
      modifyDeadline.setDate(modifyDeadline.getDate() - 1); // előző nap
      modifyDeadline.setHours(10, 0, 0, 0); // 10:00-ig
      
      // Összehasonlításhoz normalizáljuk az időket
      now.setSeconds(0, 0);
      modifyDeadline.setSeconds(0, 0);
      
      const canModify = now <= modifyDeadline;
      
      console.log('Módosíthatóság ellenőrzése:', {
        orderDate: order.orderDate,
        now: now.toISOString(),
        deadline: modifyDeadline.toISOString(),
        canModify
      });
      
      return canModify;
    },

    // Lemondhatóság ellenőrzése
    canCancelOrder(order) {
      if (!order || order.orderStatus !== 'Rendelve') return false;
      
      const orderDate = new Date(order.orderDate);
      const now = new Date();
      const cancelDeadline = new Date(orderDate);
      cancelDeadline.setHours(8, 0, 0, 0); // aznap 8:00-ig
      
      // Összehasonlításhoz normalizáljuk az időket
      now.setSeconds(0, 0);
      cancelDeadline.setSeconds(0, 0);
      
      const canCancel = now <= cancelDeadline;
      
      console.log('Lemondhatóság ellenőrzése:', {
        orderDate: order.orderDate,
        now: now.toISOString(),
        deadline: cancelDeadline.toISOString(),
        canCancel
      });
      
      return canCancel;
},
    
    // Egy dátumhoz tartozó rendelés lekérése (bármilyen állapotban)
    getOrderForDate(dateString) {
      return this.orders.find(order => 
        order.orderDate === dateString
      );
    },
    
    // Egy dátumhoz tartozó AKTÍV rendelés lekérése
    getActiveOrderForDate(dateString) {
      return this.orders.find(order => 
        order.orderDate === dateString && order.orderStatus === 'Rendelve'
      );
    },

    getCancelDeadlineInfo(dateString) {
      const orderDate = new Date(dateString);
      
      // Módosítási határidő: előző nap 10:00
      const modifyDeadline = new Date(orderDate);
      modifyDeadline.setDate(modifyDeadline.getDate() - 1);
      modifyDeadline.setHours(10, 0, 0, 0);
      
      // Lemondási határidő: aznap 8:00
      const cancelDeadline = new Date(orderDate);
      cancelDeadline.setHours(8, 0, 0, 0);
      
      const formatTime = (date) => {
        return date.toLocaleTimeString('hu-HU', {
          hour: '2-digit',
          minute: '2-digit'
        });
      };
      
      return `Módosítási határidő: ${formatTime(modifyDeadline)}\n` +
             `Lemondási határidő: ${formatTime(cancelDeadline)}`;
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },
    
   selectMonth(month) {
      this.selectedMonth = {
        year: month.year,
        month: month.month
      };
      this.showMonthPicker = false;
      this.deselectAllDates();
      this.loadOrdersForSelectedMonth();
    },

    async loadOrdersForSelectedMonth() {
      if (!this.selectedMonth) return;
      
      this.loading = true;
      try {
        const response = await AuthService.api.get(
          `/user/personal-orders/month/${this.selectedMonth.year}/${this.selectedMonth.month}`
        );
        
        if (response.data && response.data.success === true) {
          this.availableDates = response.data.data || [];
        }
      } catch (error) {
        console.error('Hiba a havi rendelések betöltésekor:', error);
      } finally {
        this.loading = false;
      }
    },
    
    isMonthSelected(month) {
      return this.selectedMonth && 
             this.selectedMonth.year === month.year && 
             this.selectedMonth.month === month.month;
    },
    
    changeYear(delta) {
      this.selectedYear += delta;
    },
    
    refreshData() {
      this.loadInitialData();
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

/* FEJLÉC */
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
  color: #2c3e50;
  font-size: 1.8rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

/* GOMBOK */
.btn-month-picker,
.btn-refresh,
.btn-retry,
.btn-bulk {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease;
  font-weight: 500;
  width: 20%;

  min-width: 150px;
}

.btn-month-picker {
  background: #3498db;
  color: white;
  
}

.btn-month-picker:hover {
  background: #2980b9;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-refresh {
  background: #2ecc71;
  color: white;
}

.btn-refresh:hover {
  background: #27ae60;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
}

/* TÖMEGES GOMBOK */
.bulk-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-right: 1rem;

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

.btn-bulk:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

/* TÖMEGES STÁTUSZ */
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

/* HÓNAP VÁLASZTÓ */
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
  transition: all 0.2s ease;
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

/* BETÖLTÉS, HIBA, NINCS RENDELÉS */
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

.error-container { 
  color: #e74c3c; 
}

.no-orders { 
  color: #95a5a6; 
}

.btn-retry {
  background: #3498db;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  margin-top: 1rem;
}

/* TÁBLÁZAT KONTAINER */
.orders-table-container {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  margin-bottom: 1.5rem;
}

/* STATISZTIKA */
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
  font-weight: 600;
}

/* TÁBLÁZAT */
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
  padding: 1rem 0.75rem;
  text-align: left;
  color: #2c3e50;
  border-bottom: 2px solid #e0e0e0;
  font-size: 0.9rem;
  font-weight: 600;
}

.orders-table td {
  padding: 1rem 0.75rem;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
  color: #2c3e50;
}

.orders-table tbody tr:hover {
  background: #f8f9fa;
}

/* SOR STÁTUSZOK */
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

.orders-table tbody tr.selected {
  background: #eaf2fb !important;
}

/* OSZLOP SZÉLESSÉGEK */
.col-select { width: 50px; }
.col-date { width: 100px; }
.col-soup, .col-option, .col-other { width: 200px; }
.col-choice { width: 120px; }
.col-status { width: 120px; }
.col-actions { width: 140px; }

/* DÁTUM MEGJELENÍTÉS */
.date-display {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.date-number {
  font-size: 1.6rem;
  line-height: 1;
  font-weight: 600;
  color: #2c3e50;
}

.date-month {
  font-size: 0.75rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* ÉTEL INFO */
.meal-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.meal-name {
  color: #2c3e50;
  line-height: 1.35;
  font-weight: 500;
}

.meal-price {
  font-size: 0.9rem;
  color: #27ae60;
  font-weight: 600;
}

.no-other {
  color: #95a5a6;
  font-style: italic;
}

/* VÁLASZTÁS OPCIÓK */
.col-choice {
  text-align: center;
}

.choice-display {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 50px;
}

.option-buttons {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.option-btn {
  width: 40px;
  height: 40px;
  border: 2px solid #e0e0e0;
  border-radius: 50%;
  background: white;
  color: #7f8c8d;
  font-weight: bold;
  font-size: 1rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.option-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.option-btn.new.active {
  background: #27ae60;
  border-color: #27ae60;
  color: white;
  box-shadow: 0 2px 6px rgba(39, 174, 96, 0.3);
}

.option-btn.new:not(.active):hover {
  border-color: #27ae60;
  color: #27ae60;
}

.option-btn.edit.active {
  background: #3498db;
  border-color: #3498db;
  color: white;
  box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3);
}

.option-btn.edit:not(.active):hover {
  border-color: #3498db;
  color: #3498db;
}

/* RÖGZÍTETT OPCIÓ */
.selected-option-display.fixed {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #27ae60;
  color: white;
  font-weight: bold;
  font-size: 1rem;
  border: 2px solid #27ae60;
}

/* LE MONDOTT OPCIÓ */
.cancelled-option-display {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #f8f9fa;
  color: #95a5a6;
  font-weight: bold;
  font-size: 1rem;
  border: 2px dashed #e0e0e0;
  opacity: 0.7;
}

/* NEM RENDELHETŐ */
.choice-disabled {
  color: #95a5a6;
  font-size: 0.85rem;
  font-style: italic;
}

/* STÁTUSZ BADGE */
.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
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

.status-badge.deadline {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.status-badge.available {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

/* MŰVELET GOMBOK */
.col-actions {
  text-align: center;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
  min-width: 100px;
  width: 20%;
}

.btn-action {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.85rem;
  transition: all 0.2s ease;
  min-width: 100px;
  width: 20%;
}

.btn-order {
  background: #27ae60;
  color: white;
}

.btn-order:hover:not(:disabled) {
  background: #219150;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.btn-cancel {
  background: #e74c3c;
  color: white;

}

.btn-cancel:hover {
  background: #c0392b;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.btn-reorder {
  background: #f39c12;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
}

.btn-reorder:hover {
  background: #d68910;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

.info-text {
  color: #95a5a6;
  font-size: 0.85rem;
  font-style: italic;
  text-align: center;
  min-width: 100px;
}

/* INFORMÁCIÓK */
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
}

.delivery-info ul {
  margin: 0;
  padding-left: 1.25rem;
  color: #7f8c8d;
  line-height: 1.6;
}

.delivery-info li { 
  margin-bottom: 0.5rem; 
}

.delivery-info strong { 
  color: #2c3e50; 
}

/* RESZPONZÍV */
@media (max-width: 1200px) {
  .personal-orders-container { 
    padding: 1rem; 
  }
  
  .month-grid { 
    grid-template-columns: repeat(2, 1fr); 
  }
  
  .page-header { 
    flex-direction: column; 
    align-items: stretch; 
  }
  
  .header-actions { 
    justify-content: center; 
  }
  
  .bulk-actions { 
    justify-content: center; 
    margin-right: 0; 
    margin-bottom: 0.5rem; 
  }
}

@media (max-width: 768px) {
  .month-grid { 
    grid-template-columns: 1fr; 
  }
  
  .stats-bar { 
    flex-direction: column; 
    align-items: center; 
    gap: 0.5rem; 
  }
  
  .bulk-actions { 
    flex-direction: column; 
    width: 100%; 
  }
  
  .btn-bulk { 
    width: 100%; 
  }
  
  .option-btn {
    width: 35px;
    height: 35px;
    font-size: 0.9rem;
  }
  
  .selected-option-display.fixed,
  .cancelled-option-display {
    width: 35px;
    height: 35px;
    font-size: 0.9rem;
  }
  
  .status-badge {
    font-size: 0.75rem;
    min-width: 80px;
    padding: 0.3rem 0.6rem;
  }
  
  .btn-action {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    min-width: 85px;
  }
  
  .info-text {
    font-size: 0.8rem;
    min-width: 85px;
  }
}
/* PersonalOrders.vue - Add to style section */

.allergen-warning {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.25rem;
  padding: 0.15rem 0.25rem;
  background-color: #fff3cd;
  border: 1px solid #ffeeba;
  border-radius: 4px;
  color: #856404;
  font-size: 0.7rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.allergen-warning:hover {
  background-color: #ffeeba;
  transform: translateY(-1px);
}

.warning-icon {
  font-size: 0.9rem;
}

.warning-text {
  font-weight: 500;
}

/* PersonalOrders.vue - style */

.month-picker-simple {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.month-selector {
  margin: 1rem 0;
}

.month-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 1rem;
  color: #2c3e50;
  background-color: white;
  cursor: pointer;
}

.month-select:hover {
  border-color: #3498db;
}

.month-select:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.year-nav-simple {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-year-prev,
.btn-year-next {
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  color: #2c3e50;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-year-prev:hover,
.btn-year-next:hover {
  background: #eef2f6;
  transform: translateY(-1px);
}

.month-picker-simple {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 10px;
  padding: 1.5rem;
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

.month-selector {
  margin: 1rem 0;
}

.month-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  font-size: 1rem;
  color: #2c3e50;
  background-color: white;
  cursor: pointer;
}

.month-select:hover {
  border-color: #3498db;
}

.month-select:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.month-navigation {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-month-nav {
  padding: 0.5rem 1rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  color: #2c3e50;
  cursor: pointer;
  transition: all 0.2s ease;
  flex: 1;
}

.btn-month-nav:hover:not(:disabled) {
  background: #eef2f6;
  transform: translateY(-1px);
}

.btn-month-nav:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.current-month-display {
  font-weight: 600;
  color: #2c3e50;
  min-width: 150px;
  text-align: center;
}
</style>