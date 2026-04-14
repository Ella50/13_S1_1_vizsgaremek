<template>
  <div class="orders-container">
    <div class="header-section">
      <h1>Rendelések Kezelése</h1>
    </div>

    <div class="filters-section card">
      <div class="filter-row">
        <div class="filter-group" v-if="viewMode === 'daily'">
          <label for="datePicker">Dátum:</label>
          <input type="date" id="datePicker" v-model="selectedDate" class="form-control" @change="loadOrders" />
        </div>

        <div class="filter-actions">
          <button @click="loadOrders" class="datepicker-btn" :disabled="loading">
            {{ loading ? 'Betöltés...' : 'Frissítés' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Napi összesítés -->
    <div v-if="viewMode === 'daily' && dailyMealSummary" class="daily-summary-section card mt-4">
      <div class="card-header">
        <h3>Napi Összesítés - {{ formatDate(selectedDate) }}</h3>
      </div>
      
      <div class="card-body">
        <div class="row">
          <div class="col-md-3">
            <div class="meal-summary-card">
              <div class="meal-icon" id="leves-opcio">
                <span>Leves</span>
              </div>
              <div class="meal-info">
                <p class="meal-name">{{ dailyMealSummary.soup.name || 'Nincs adat' }}</p>
                <div class="meal-count">
                  <span class="count-number">{{ dailyMealSummary.soup.count || 0 }}</span>
                  <span class="count-label">db rendelés</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="meal-summary-card">
              <div class="meal-icon" id="A-opcio">
                <span>A</span>
              </div>
              <div class="meal-info">
                <p class="meal-name">{{ dailyMealSummary.option_a.name || 'Nincs adat' }}</p>
                <div class="meal-count">
                  <span class="count-number">{{ dailyMealSummary.option_a.count || 0 }}</span>
                  <span class="count-label">db rendelés</span>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-3">
            <div class="meal-summary-card">
              <div class="meal-icon" id="B-opcio">
                <span>B</span>
              </div>
              <div class="meal-info">
                <p class="meal-name">{{ dailyMealSummary.option_b.name || 'Nincs adat' }}</p>
                <div class="meal-count">
                  <span class="count-number">{{ dailyMealSummary.option_b.count || 0 }}</span>
                  <span class="count-label">db rendelés</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3">
            <div class="meal-summary-card">
              <div class="meal-icon" id="B-opcio">
                <span>CM</span>
              </div>
              <div class="meal-info">
                <p class="meal-name">{{ dailyMealSummary.option_b.name || 'Nincs adat' }}</p>
                <div class="meal-count">
                  <span class="count-number">{{ dailyMealSummary.option_b.count || 0 }}</span>
                  <span class="count-label">db rendelés</span>
                </div>
              </div>
            </div>
          </div>
        </div>
 
        <div class="summary-table mt-4">
          <h5>Részletes eloszlás</h5>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr class="table-light">
                  <th>Étel típusa</th>
                  <th>Étel neve</th>
                  <th>Darabszám</th>
                  <th>% az összesből</th>
                 </tr>
              </thead>
              <tbody>
                <tr>
                  <td><strong>Leves</strong></td>
                  <td>{{ dailyMealSummary.soup.name || 'Nincs adat' }}</td>
                  <td>{{ dailyMealSummary.soup.count || 0 }}</td>
                  <td>{{ getPercentage(dailyMealSummary.soup.count, dailyMealSummary.total_by_option.total_soup) }}%</td>
                </tr>
                <tr>
                  <td><strong>A opció</strong></td>
                  <td>{{ dailyMealSummary.option_a.name || 'Nincs adat' }}</td>
                  <td>{{ dailyMealSummary.option_a.count || 0 }}</td>
                  <td>{{ getPercentage(dailyMealSummary.option_a.count, dailyMealSummary.total_by_option.A) }}%</td>
                </tr>
                <tr>
                  <td><strong>B opció</strong></td>
                  <td>{{ dailyMealSummary.option_b.name || 'Nincs adat' }}</td>
                  <td>{{ dailyMealSummary.option_b.count || 0 }}</td>
                  <td>{{ getPercentage(dailyMealSummary.option_b.count, dailyMealSummary.total_by_option.B) }}%</td>
                </tr>
                <tr v-if="dailyMealSummary.other && dailyMealSummary.other.count > 0">
                  <td><strong>Egyéb</strong></td>
                  <td>{{ dailyMealSummary.other.name || 'Nincs adat' }}</td>
                  <td>{{ dailyMealSummary.other.count || 0 }}</td>
                  <td>{{ getPercentage(dailyMealSummary.other.count, dailyMealSummary.total_by_option.other) }}%</td>
                </tr>
                <tr class="table-secondary">
                  <td colspan="2"><strong>ÖSSZESEN</strong></td>
                  <td><strong>{{ dailyMealSummary.total_by_option.total_soup || 0 }}</strong></td>
                  <td><strong>100%</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Részletek modal -->
    <div v-if="selectedOrder" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Rendelés részletei #{{ selectedOrder.id }}</h5>
            <button type="button" class="btn-close" @click="selectedOrder = null"></button>
          </div>
          <div class="modal-body">
            <div class="order-details-simple">
              <div class="row mb-3">
                <div class="col-md-6">
                  <h6>Felhasználó:</h6>
                  <p>{{ selectedOrder.user?.email || 'Ismeretlen' }}</p>
                  <p class="text-muted small">{{ selectedOrder.user?.userType || '' }}</p>
                </div>
                <div class="col-md-6">
                  <h6>Rendelés információk:</h6>
                  <p>Dátum: {{ formatDate(selectedOrder.orderDate) }}</p>
                  <p>Opció: 
                    <span class="badge" :class="getOptionBadgeClass(selectedOrder.selectedOption)">
                      {{ getOptionDisplay(selectedOrder.selectedOption) }}
                    </span>
                  </p>
                  <p>Ár: {{ formatCurrency(selectedOrder.price?.amount || selectedOrder.price || 0) }}</p>
                </div>
              </div>
              
              <div class="row mb-3" v-if="selectedOrder.menuItem">
                <div class="col-md-6">
                  <h6>Leves:</h6>
                  <p>{{ selectedOrder.menuItem.soupMeal?.mealName || '-' }}</p>
                </div>
                <div class="col-md-6">
                  <h6>Főétel:</h6>
                  <p v-if="selectedOrder.selectedOption === 'A'">
                    {{ selectedOrder.menuItem.optionAMeal?.mealName || '-' }}
                  </p>
                  <p v-else-if="selectedOrder.selectedOption === 'B'">
                    {{ selectedOrder.menuItem.optionBMeal?.mealName || '-' }}
                  </p>
                  <p v-else-if="selectedOrder.selectedOption === 'other'">
                    {{ selectedOrder.menuItem.otherMeal?.mealName || '-' }}
                  </p>
                  <p v-else>-</p>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="selectedOrder = null">
              Bezárás
            </button>
          </div>
        </div>
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
import axios from 'axios';

export default {
  name: 'Orders',

  data() {
    return {
      viewMode: 'daily',
      selectedDate: new Date().toISOString().split('T')[0],
      selectedWeek: this.getCurrentWeek(),
      selectedMonth: new Date().toISOString().slice(0, 7),

      orders: [],
      loading: false,
      monthlyLoading: false,
      
      currentPage: 1,
      totalPages: 1,
      pageSize: 25,
      totalItems: 0,
      
      stats: {
        totalOrders: 0,
        activeOrders: 0,
        cancelledOrders: 0,
        totalRevenue: 0
      },
      
      selectedOrder: null,
      apiBaseUrl: '/kitchen/orders',
      dailyMealSummary: null,
      
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
    };
  },
  
  computed: {
    paginationInfo() {
      const start = ((this.currentPage - 1) * this.pageSize) + 1;
      const end = Math.min(this.currentPage * this.pageSize, this.totalItems);
      return `${start}-${end} / ${this.totalItems} rendelés`;
    },
    
    visiblePages() {
      const pages = [];
      const maxVisible = 5;
      let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
      let end = Math.min(this.totalPages, start + maxVisible - 1);
      
      if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1);
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    },
    
    selectedYear() {
      return this.selectedMonth ? this.selectedMonth.split('-')[0] : new Date().getFullYear();
    },
    
    selectedMonthNumber() {
      return this.selectedMonth ? this.selectedMonth.split('-')[1] : new Date().getMonth() + 1;
    }
  },
  
  mounted() {
    this.loadOrders();
  },
  
  watch: {
    viewMode() {
      this.currentPage = 1;
      this.loadOrders();
    },
    
    selectedDate() {
      this.currentPage = 1;
      this.loadOrders();
    },
    
    selectedWeek() {
      this.currentPage = 1;
      this.loadOrders();
    },
    
    selectedMonth() {
      this.currentPage = 1;
      this.loadOrders();
    },
  },
  
  methods: {
    // Alert metódusok
    showAlert({ message, type = 'success', title = '' }) {
      if (this.alertTimeout) {
        clearTimeout(this.alertTimeout);
      }
      
      this.alertMessage = message;
      this.alertType = type;
      this.alertTitle = title;
      this.alertVisible = true;
      
      this.alertTimeout = setTimeout(() => {
        this.alertVisible = false;
      }, 3000);
    },
    
    // Confirm metódusok
    showConfirm({ message, title = '' }) {
      console.log('showConfirm called - setting confirmVisible to true');
      this.confirmMessage = message;
      this.confirmTitle = title;
      this.confirmVisible = true;
      console.log('confirmVisible is now:', this.confirmVisible);
      
      return new Promise((resolve) => {
        this.confirmResolver = resolve;
      });
    },
    
    confirmOk() {
      this.confirmVisible = false;
      if (this.confirmResolver) {
        this.confirmResolver(true);
        this.confirmResolver = null;
      }
    },
    
    confirmCancel() {
      this.confirmVisible = false;
      if (this.confirmResolver) {
        this.confirmResolver(false);
        this.confirmResolver = null;
      }
    },
    
    async loadOrders() {
      this.loading = true;
      this.dailyMealSummary = null;
      
      try {
        let url = '';
        let params = {};
        
        switch (this.viewMode) {
          case 'daily':
            url = `${this.apiBaseUrl}/date/${this.selectedDate}`;
            break;
            
          case 'weekly':
            const [year, week] = this.selectedWeek.split('-W');
            const firstDay = this.getDateOfISOWeek(parseInt(week), parseInt(year));
            const lastDay = new Date(firstDay);
            lastDay.setDate(firstDay.getDate() + 6);
            
            params.start_date = firstDay.toISOString().split('T')[0];
            params.end_date = lastDay.toISOString().split('T')[0];
            url = this.apiBaseUrl;
            break;
            
          case 'monthly':
            params.year = this.selectedYear;
            params.month = this.selectedMonthNumber;
            url = this.apiBaseUrl;
            break;
            
          default:
            url = this.apiBaseUrl;
        }
        
        const token = localStorage.getItem('token') || this.getAuthToken();
        const response = await axios.get(url, { 
          params,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });
        
        let responseData = response.data;
        if (typeof responseData === 'string') {
          responseData = responseData.replace(/^\uFEFF/, '');
          try {
            responseData = JSON.parse(responseData);
          } catch (e) {
            console.error('JSON parse hiba:', e);
            throw new Error('Érvénytelen JSON válasz');
          }
        }
        
        if (responseData.success === true) {
          const data = responseData.data || responseData;
          
          this.orders = data.orders || [];
          this.totalItems = this.orders.length;
          this.totalPages = 1;
          
          if (this.viewMode === 'daily') {
            if (data.meal_summary) {
              this.dailyMealSummary = data.meal_summary;
            } else if (data.summary) {
              this.createDailyMealSummaryFromData(data);
            }
            
            if (data.summary) {
              this.updateStatsFromResponse(data.summary);
            }
          }
          
        } else {
          this.orders = [];
          this.totalItems = 0;
          this.totalPages = 1;
          this.showAlert({
            message: responseData.message || 'Hiba történt a rendelések betöltésekor',
            type: 'error'
          });
        }
        
      } catch (error) {
        console.error('Hiba a rendelések betöltésekor:', error);
        this.orders = [];
        this.totalItems = 0;
        this.totalPages = 1;
        this.showAlert({
          message: error.response?.data?.message || 'Hiba történt a rendelések betöltésekor',
          type: 'error'
        });
      } finally {
        this.loading = false;
      }
    },
    
    createDailyMealSummaryFromData(data) {
      if (!this.orders || this.orders.length === 0) {
        this.dailyMealSummary = null;
        return;
      }
      
      const activeOrders = this.orders.filter(order => order.orderStatus === 'Rendelve');
      
      const ordersByOption = {
        'A': activeOrders.filter(o => o.selectedOption === 'A'),
        'B': activeOrders.filter(o => o.selectedOption === 'B'),
        'soup': activeOrders.filter(o => o.selectedOption === 'soup'),
        'other': activeOrders.filter(o => o.selectedOption === 'other')
      };
      
      let menu = null;
      if (data.menu) {
        menu = data.menu;
      } else if (this.orders[0]?.menuItem) {
        menu = this.orders[0].menuItem;
      }
      
      this.dailyMealSummary = {
        'soup': {
          'name': menu?.soupMeal?.mealName || 'Leves',
          'count': activeOrders.length,
          'orders': activeOrders.map(o => o.id)
        },
        'option_a': {
          'name': menu?.optionAMeal?.mealName || 'A opció',
          'count': ordersByOption['A'].length,
          'orders': ordersByOption['A'].map(o => o.id)
        },
        'option_b': {
          'name': menu?.optionBMeal?.mealName || 'B opció',
          'count': ordersByOption['B'].length,
          'orders': ordersByOption['B'].map(o => o.id)
        },
        'other': {
          'name': menu?.otherMeal?.mealName || 'Egyéb',
          'count': ordersByOption['other'].length,
          'orders': ordersByOption['other'].map(o => o.id)
        },
        'total_by_option': {
          'A': ordersByOption['A'].length,
          'B': ordersByOption['B'].length,
          'soup': ordersByOption['soup'].length,
          'other': ordersByOption['other'].length,
          'total_soup': activeOrders.length
        }
      };
    },
    
    updateStatsFromResponse(summary) {
      this.stats = {
        totalOrders: summary.total_orders || summary.total || 0,
        activeOrders: summary.active_orders || 0,
        cancelledOrders: summary.cancelled_orders || 0,
        totalRevenue: summary.total_revenue || 0
      };
    },
    
    viewModeChanged() {
      this.dailyMealSummary = null;
      this.loadOrders();
    },
    
    viewOrderDetails(order) {
      this.selectedOrder = order;
    },
    
    async cancelOrder(order) {
  console.log('Lemondás gombra kattintottál, order ID:', order.id);
  
  const confirmed = confirm('Biztosan lemondja ezt a rendelést?');
  console.log('Confirm eredménye:', confirmed);
  
  if (!confirmed) return;
  
  try {
    const token = localStorage.getItem('token') || this.getAuthToken();
    const response = await axios.delete(`http://localhost:8000/api/user/personal-orders/${order.id}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    if (response.data.success) {
      alert('Rendelés sikeresen lemondva');
      this.loadOrders();
    } else {
      alert(response.data.message || 'Hiba a rendelés lemondásakor');
    }
  } catch (error) {
    console.error('Hiba a rendelés lemondásakor:', error);
    alert(error.response?.data?.message || 'Hiba a rendelés lemondásakor');
  }
},
    
    async restoreOrder(order) {
  const confirmed = confirm('Biztosan visszaállítja ezt a rendelést?');
  
  if (!confirmed) return;
  
  try {
    order.orderStatus = 'Rendelve';
    alert('Rendelés helyileg visszaállítva');
  } catch (error) {
    console.error('Hiba a rendelés visszaállításakor:', error);
    alert('Hiba a rendelés visszaállításakor');
  }
},
    
    getPercentage(count, total) {
      if (!total || total === 0) return '0.0';
      return ((count / total) * 100).toFixed(1);
    },
    
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      return d.toLocaleDateString('hu-HU');
    },
    
    formatDateTime(datetime) {
      if (!datetime) return '';
      const d = new Date(datetime);
      return d.toLocaleString('hu-HU');
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('hu-HU', {
        style: 'currency',
        currency: 'HUF',
        minimumFractionDigits: 0
      }).format(amount);
    },
    
    getOptionDisplay(option) {
      const options = {
        'A': 'A opció',
        'B': 'B opció',
        'soup': 'Csak leves',
        'other': 'Egyéb'
      };
      return options[option] || option;
    },
    
    getOptionBadgeClass(option) {
      const classes = {
        'A': 'bg-success',
        'B': 'bg-info',
        'soup': 'bg-primary',
        'other': 'bg-secondary'
      };
      return classes[option] || 'bg-secondary';
    },
    
    getStatusBadgeClass(status) {
      const classes = {
        'Rendelve': 'bg-success',
        'Lemondva': 'bg-warning',
        'Fizetve': 'bg-info',
        'active': 'bg-success',
        'inactive': 'bg-warning',
        'suspended': 'bg-danger'
      };
      return classes[status] || 'bg-secondary';
    },
    
    getAuthToken() {
      return localStorage.getItem('auth_token') || 
             localStorage.getItem('sanctum-token') ||
             sessionStorage.getItem('token') ||
             '';
    },
    
    getCurrentWeek() {
      const now = new Date();
      const oneJan = new Date(now.getFullYear(), 0, 1);
      const weekNumber = Math.ceil(((now - oneJan) / 86400000 + oneJan.getDay() + 1) / 7);
      return `${now.getFullYear()}-W${weekNumber.toString().padStart(2, '0')}`;
    },
    
    getDateOfISOWeek(week, year) {
      const simple = new Date(year, 0, 1 + (week - 1) * 7);
      const dow = simple.getDay();
      const ISOweekStart = simple;
      if (dow <= 4) {
        ISOweekStart.setDate(simple.getDate() - simple.getDay() + 1);
      } else {
        ISOweekStart.setDate(simple.getDate() + 8 - simple.getDay());
      }
      return ISOweekStart;
    },
  }
};
</script>

<style scoped>
.orders-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  min-height: 100vh;
  background: var(--content-card);
}

.header-section {
  margin-bottom: 30px;
}

.datepicker-btn {
  background-color: var(--zold, #28a745);
  color: white;
  font-weight: 500;
  height: 40px;
  padding: 3px 20px;
  border-radius: 3px;
  border: none;
  cursor: pointer;
}

.filters-section {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-bottom: 25px;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  align-items: flex-end;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
  color: #495057;
}

.filter-actions {
  display: flex;
  gap: 10px;
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
/* Confirm stílusok - FELÜLÍRT, ERŐSEBB VERZIÓ */
.confirm-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
  background: rgba(0, 0, 0, 0.5) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 99999 !important;
}

.confirm-box {
  background: white !important;
  padding: 24px !important;
  border-radius: 14px !important;
  min-width: 320px !important;
  text-align: center !important;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25) !important;
  z-index: 100000 !important;
  position: relative !important;
}

.confirm-title {
  margin-bottom: 10px !important;
  font-size: 1.2rem !important;
  font-weight: bold !important;
}

.confirm-message {
  font-weight: bold !important;
  text-align: center !important;
  margin: 10px 0 20px 0 !important;
}

.confirm-actions {
  display: flex !important;
  justify-content: center !important;
  gap: 15px !important;
}

.confirm-actions .btn-cancel {
  background: #e74c3c !important;
  color: white !important;
  border: none !important;
  padding: 8px 18px !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  font-weight: 500 !important;
}

.confirm-actions .btn-ok {
  background: #2ecc71 !important;
  color: white !important;
  border: none !important;
  padding: 8px 18px !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  font-weight: 500 !important;
}

/* Napi összesítés stílusok */
.daily-summary-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border: 2px solid #dee2e6;
  border-radius: 10px;
  margin-top: 20px;
}

.meal-summary-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 3px 10px rgba(0,0,0,0.1);
  height: 100%;
  transition: transform 0.3s;
}

.meal-summary-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.meal-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #007bff;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  margin-right: 15px;
  flex-shrink: 0;
}

#leves-opcio {
  background: #dc3545;
}

#A-opcio {
  background: #6c757d;
}

#B-opcio {
  background: #28a745;
}

.meal-info h4 {
  margin: 0 0 5px 0;
  color: #2c3e50;
  font-size: 1.1rem;
}

.meal-name {
  margin: 0 0 10px 0;
  color: #6c757d;
  font-size: larger;
  font-weight: 500;
}

.meal-count {
  display: flex;
  align-items: baseline;
}

.count-number {
  font-size: 32px;
  font-weight: 700;
  color: #2c3e50;
  margin-right: 8px;
}

.count-label {
  font-size: 0.85rem;
  color: #6c757d;
}

.summary-table {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.summary-table h5 {
  color: #495057;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 2px solid #dee2e6;
}

.summary-table th {
  font-weight: 600;
  color: #495057;
  background: #f8f9fa;
}

.summary-table td {
  vertical-align: middle;
}

.table-secondary {
  background-color: #f8f9fa !important;
}

.card {
  background: white;
  border-radius: 10px;
  border: none;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.card-header {
  background: white;
  border-bottom: 1px solid #eee;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.3rem;
}

.card-body {
  padding: 20px;
}

@media (max-width: 768px) {
  .filter-row {
    flex-direction: column;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .meal-summary-card {
    flex-direction: column;
    text-align: center;
    padding: 15px;
  }
  
  .meal-icon {
    margin-right: 0;
    margin-bottom: 10px;
  }
  
  .meal-count {
    justify-content: center;
  }
  
  .table-responsive {
    font-size: 0.9rem;
  }
}
</style>