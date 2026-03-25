<template>
  <div class="orders-container">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Rendelések kezelése</h1>
        
        <div class="header-controls">
          <div class="filter-group" v-if="viewMode === 'daily'">
            <input type="date" v-model="selectedDate" class="date-input" @change="loadOrders" />
          </div>
          <button @click="loadOrders" class="btn-primary" :disabled="loading">
            {{ loading ? 'Betöltés...' : 'Frissítés' }}
          </button>
        </div>
      </div>

      <!-- Napi összesítés -->
      <div v-if="viewMode === 'daily' && dailyMealSummary" class="daily-summary-section">
        <div class="summary-header">
          <h3>Napi Összesítés - {{ formatDate(selectedDate) }}</h3>
        </div>
        
        <div class="summary-cards">
          <!-- Leves összesítés -->
          <div class="summary-card">
            <div class="card-icon soup-icon">
              <span>Leves</span>
            </div>
            <div class="card-info">
              <p class="meal-name">{{ dailyMealSummary.soup.name || 'Nincs adat' }}</p>
              <div class="meal-count">
                <span class="count-number">{{ dailyMealSummary.soup.count || 0 }}</span>
                <span class="count-label">db rendelés</span>
              </div>
            </div>
          </div>
          
          <!-- A opció összesítés -->
          <div class="summary-card">
            <div class="card-icon optionA-icon">
              <span>A</span>
            </div>
            <div class="card-info">
              <p class="meal-name">{{ dailyMealSummary.option_a.name || 'Nincs adat' }}</p>
              <div class="meal-count">
                <span class="count-number">{{ dailyMealSummary.option_a.count || 0 }}</span>
                <span class="count-label">db rendelés</span>
              </div>
            </div>
          </div>
          
          <!-- B opció összesítés -->
          <div class="summary-card">
            <div class="card-icon optionB-icon">
              <span>B</span>
            </div>
            <div class="card-info">
              <p class="meal-name">{{ dailyMealSummary.option_b.name || 'Nincs adat' }}</p>
              <div class="meal-count">
                <span class="count-number">{{ dailyMealSummary.option_b.count || 0 }}</span>
                <span class="count-label">db rendelés</span>
              </div>
            </div>
          </div>

          <!-- Cukormentes összesítés -->
          <div class="summary-card">
            <div class="card-icon diabetic-icon">
              <span>Cukor<br>mentes</span>
            </div>
            <div class="card-info">
              <p class="meal-name">{{ dailyMealSummary.diabetic.name || 'Nincs adat' }}</p>
              <div class="meal-count">
                <span class="count-number">{{ dailyMealSummary.diabetic.count || 0 }}</span>
                <span class="count-label">db rendelés</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Összegző táblázat -->
        <div class="summary-table-wrapper">
          <h4>Részletek</h4>
          <div class="table-wrapper">
            <table class="data-table">
              <thead>
                <tr>
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
                <tr class="diabetic-row">
                  <td><strong>Cukormentes (A opcióból)</strong></td>
                  <td>
                    {{ dailyMealSummary.diabetic.name || 'Cukormentes menü' }}
                    <span class="badge-diabetic">cukormentes</span>
                  </td>
                  <td><strong class="diabetic-count">{{ dailyMealSummary.diabetic.count || 0 }}</strong></td>
                  <td>{{ getPercentage(dailyMealSummary.diabetic.count, dailyMealSummary.total_by_option.total_soup) }}%</td>
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
                <tr class="total-row">
                  <td colspan="2"><strong>ÖSSZESEN</strong></td>
                  <td><strong>{{ dailyMealSummary.total_by_option.total_soup || 0 }}</strong></td>
                  <td><strong>100%</strong></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="dailyMealSummary.diabetic.count > 0" class="diabetic-summary">
            <div class="diabetic-info">
              <span class="diabetic-icon-small">🍎</span>
              <div>
                <h6>Cukorbeteg rendelések összesítése</h6>
                <p class="diabetic-text">
                  {{ dailyMealSummary.diabetic.count }} db cukorbeteg rendelés az A opcióból
                  (összes A opció: {{ dailyMealSummary.option_a.count }} db, 
                  ebből cukorbeteg: {{ ((dailyMealSummary.diabetic.count / dailyMealSummary.option_a.count) * 100).toFixed(1) }}%)
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Betöltés állapota -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Rendelések betöltése...</p>
      </div>

      <!-- Üres állapot -->
      <div v-else-if="!loading && orders.length === 0 && viewMode === 'daily'" class="empty-state">
        <p>Nincsenek rendelések ezen a napon</p>
      </div>
    </div>

    <!-- Részletek modal -->
    <div v-if="selectedOrder" class="modal-overlay" @click.self="selectedOrder = null">
      <div class="modal">
        <div class="modal-header">
          <h2>Rendelés részletei #{{ selectedOrder.id }}</h2>
          <button @click="selectedOrder = null" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div class="order-details">
            <div class="details-row">
              <div class="details-col">
                <h4>Felhasználó:</h4>
                <p>{{ selectedOrder.user?.email || 'Ismeretlen' }}</p>
                <p class="text-muted">{{ selectedOrder.user?.userType || '' }}</p>
              </div>
              <div class="details-col">
                <h4>Rendelés információk:</h4>
                <p>Dátum: {{ formatDate(selectedOrder.orderDate) }}</p>
                <p>Opció: 
                  <span class="badge" :class="getOptionBadgeClass(selectedOrder.selectedOption)">
                    {{ getOptionDisplay(selectedOrder.selectedOption) }}
                  </span>
                </p>
                <p>Ár: {{ formatCurrency(selectedOrder.price?.amount || selectedOrder.price || 0) }}</p>
              </div>
            </div>
            
            <div v-if="selectedOrder.menuItem" class="details-row">
              <div class="details-col">
                <h4>Leves:</h4>
                <p>{{ selectedOrder.menuItem.soupMeal?.mealName || '-' }}</p>
              </div>
              <div class="details-col">
                <h4>Főétel:</h4>
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
          <button @click="selectedOrder = null" class="btn-cancel">Bezárás</button>
        </div>
      </div>
    </div>

    <!-- Confirm Modal -->
    <div v-if="confirmVisible" class="modal-overlay" @click.self="confirmCancel">
      <div class="modal modal-small">
        <div class="modal-header">
          <h2>{{ confirmTitle || 'Megerősítés' }}</h2>
          <button @click="confirmCancel" class="modal-close">×</button>
        </div>
        <div class="modal-body text-center">
          <div class="confirm-icon">❓</div>
          <p class="confirm-message">{{ confirmMessage }}</p>
        </div>
        <div class="modal-footer">
          <button @click="confirmCancel" class="btn-cancel">Mégse</button>
          <button @click="confirmOk" class="btn-primary">Igen</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { addAlert } from '../../auth/AppAlert.vue'

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
    showConfirm({ message, title = "" }) {
      this.confirmMessage = message;
      this.confirmTitle = title;
      this.confirmVisible = true;
      
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
          addAlert({
            message: responseData.message || 'Hiba történt a rendelések betöltésekor',
            type: 'error'
          });
        }
        
      } catch (error) {
        console.error('Hiba a rendelések betöltésekor:', error);
        this.orders = [];
        this.totalItems = 0;
        this.totalPages = 1;
        addAlert({
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
      
      const diabeticOrders = ordersByOption['A'].filter(order => order.user && order.user.hasDiabetes === true);
      
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
        'diabetic': {
          'name': menu?.optionAMeal?.mealName || 'Cukormentes menü',
          'count': diabeticOrders.length,
          'orders': diabeticOrders.map(o => o.id),
          'percentage': ordersByOption['A'].length > 0 
            ? ((diabeticOrders.length / ordersByOption['A'].length) * 100).toFixed(1) 
            : 0
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
    
    viewOrderDetails(order) {
      this.selectedOrder = order;
      document.body.style.overflow = 'hidden';
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
        'A': 'badge-success',
        'B': 'badge-info',
        'soup': 'badge-primary',
        'other': 'badge-secondary'
      };
      return classes[option] || 'badge-secondary';
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

.date-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
  background: white;
}

.date-input:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
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

/* Napi összesítés */
.daily-summary-section {
  margin-bottom: 1.5rem;
}

.summary-header {
  margin-bottom: 1rem;
}

.summary-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #8a1212;
  font-weight: 600;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.summary-card {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid #eee;
  transition: transform 0.2s, box-shadow 0.2s;
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  font-weight: 600;
  color: white;
  flex-shrink: 0;
  text-align: center;
  line-height: 1.2;
}

.soup-icon { background: #8a1212; }
.optionA-icon { background: #6c757d; }
.optionB-icon { background: #1fa317; }

.diabetic-icon { 
  background: #17a2b8; 
  font-size: smaller;
}

.card-info {
  flex: 1;
}

.meal-name {
  margin: 0 0 0.5rem 0;
  font-size: 0.85rem;
  color: #666;
  font-weight: 500;
}

.meal-count {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.count-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #333;
}

.count-label {
  font-size: 0.7rem;
  color: #888;
}

/* Táblázat */
.summary-table-wrapper {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
}

.summary-table-wrapper h4 {
  margin: 0 0 1rem 0;
  font-size: 0.9rem;
  color: #8a1212;
  font-weight: 600;
  text-transform: uppercase;
}

.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #eee;
  background: white;
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
}

.data-table tr:hover {
  background: #fef9ef;
}

.diabetic-row {
  background: #e3f2fd;
}

.total-row {
  background: #f8f9fa;
  font-weight: 600;
}

.badge-diabetic {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  background: #17a2b8;
  color: white;
  border-radius: 20px;
  font-size: 0.75rem;
  margin-left: 0.5rem;
}

.diabetic-count {
  color: #17a2b8;
}

.diabetic-summary {
  margin-top: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  border-left: 4px solid #17a2b8;
}

.diabetic-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.diabetic-icon-small {
  font-size: 1.5rem;
}

.diabetic-info h6 {
  margin: 0 0 0.25rem 0;
  font-size: 0.85rem;
  color: #333;
}

.diabetic-text {
  margin: 0;
  font-size: 0.75rem;
  color: #666;
}

/* Badge stílusok */
.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-primary { background: #cfe2ff; color: #084298; }
.badge-secondary { background: #e9ecef; color: #495057; }

/* Betöltés állapota */
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

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  background: #f9f9f9;
  border-radius: 12px;
}

/* Modal stílusok */
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
  max-width: 700px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  animation: modalFadeIn 0.2s ease-out;
}

.modal-small {
  max-width: 450px;
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

.order-details {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.details-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.details-col h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.8rem;
  color: #666;
  text-transform: uppercase;
  font-weight: 600;
}

.details-col p {
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
  color: #333;
}

.text-muted {
  color: #888;
  font-size: 0.8rem;
}

/* Confirm modal */
.confirm-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.confirm-message {
  font-size: 1rem;
  color: #333;
  margin: 0;
}

.text-center {
  text-align: center;
}

/* Reszponzív */
@media (max-width: 768px) {
  .orders-container {
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

  .date-input {
    width: 100%;
  }

  .btn-primary {
    width: 100%;
    text-align: center;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .summary-card {
    padding: 0.75rem;
  }

  .card-icon {
    width: 50px;
    height: 50px;
    font-size: 0.8rem;
  }

  .count-number {
    font-size: 1.25rem;
  }

  .details-row {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .diabetic-info {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .data-table th,
  .data-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }

  .badge-diabetic {
    display: inline-block;
    margin-top: 0.25rem;
  }

  .diabetic-row td {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>