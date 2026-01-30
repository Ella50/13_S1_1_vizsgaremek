<template>
  <div class="orders-container">
    <!-- Fejléc -->
    <div class="header-section">
      <h1>Rendelések Kezelése</h1>
    </div>

    <!-- Szűrők és vezérlők -->
    <div class="filters-section card">
      <div class="filter-row">
        <div class="filter-group">
          <label for="viewMode">Nézet:</label>
          <select id="viewMode" v-model="viewMode" class="form-select">
            <option value="daily">Napi nézet</option>
            <option value="weekly">Heti nézet</option>
            <option value="monthly">Havi nézet</option>
          </select>
        </div>

        <!-- Dátum választó viewMode alapján -->
        <div class="filter-group" v-if="viewMode === 'daily'">
          <label for="datePicker">Dátum:</label>
          <input type="date" id="datePicker" v-model="selectedDate" class="form-control" />
        </div>

        <div class="filter-group" v-if="viewMode === 'weekly'">
          <label for="weekPicker">Hét:</label>
          <input type="week" id="weekPicker" v-model="selectedWeek" class="form-control" />
        </div>

        <div class="filter-group" v-if="viewMode === 'monthly'">
          <label for="monthPicker">Hónap:</label>
          <input type="month" id="monthPicker" v-model="selectedMonth" class="form-control" />
        </div>

        <div class="filter-group">
          <label for="statusFilter">Státusz:</label>
          <select id="statusFilter" v-model="statusFilter" class="form-select">
            <option value="all">Összes</option>
            <option value="Rendelve">Rendelve</option>
            <option value="Lemondva">Lemondva</option>
            <option value="Fizetve">Fizetve</option>
          </select>
        </div>

        <div class="filter-actions">
          <button @click="loadOrders" class="btn btn-primary">
            <i class="fas fa-sync-alt"></i> Frissítés
          </button>
          <!--<button @click="exportData" class="btn btn-success" :disabled="!orders.length">
            <i class="fas fa-file-export"></i> Export
          </button> -->
        </div>
      </div>
    </div>

    <!-- Statisztikák -->
    <div class="stats-section" v-if="stats.totalOrders > 0">
      <div class="row">
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-primary">
              <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
              <h3>{{ stats.totalOrders }}</h3>
              <p>Összes rendelés</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-success">
              <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
              <h3>{{ stats.activeOrders }}</h3>
              <p>Aktív rendelés</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-warning">
              <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
              <h3>{{ stats.cancelledOrders }}</h3>
              <p>Lemondva</p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card">
            <div class="stat-icon bg-info">
              <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
              <h3>{{ formatCurrency(stats.totalRevenue) }}</h3>
              <p>Bevétel</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fő tartalom -->
    <div class="main-content">
      <!-- Rendelések listája -->
      <div class="orders-list-section card">
        <div class="card-header">
          <h3><i class="fas fa-list"></i> Rendelések</h3>
          <div class="card-actions">
            <div class="pagination-info" v-if="orders.length > 0">
              {{ paginationInfo }}
            </div>
           <!-- <button @click="toggleFilters" class="btn btn-sm btn-outline-secondary">
              <i class="fas fa-filter"></i> Szűrők
            </button>-->
          </div>
        </div>
        
        <div class="card-body">
          <!-- Betöltés indikátor -->
          <div v-if="loading" class="loading-overlay">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Betöltés...</span>
            </div>
            <p>Rendelések betöltése...</p>
          </div>

          <!-- Üres állapot -->
          <div v-else-if="!orders.length" class="empty-state">
            <i class="fas fa-clipboard-list fa-3x"></i>
            <h4>Nincsenek rendelések</h4>
            <p v-if="viewMode === 'daily'">Nincs rendelés {{ selectedDate }} dátumra.</p>
            <p v-else-if="viewMode === 'weekly'">Nincs rendelés a kiválasztott héten.</p>
            <p v-else-if="viewMode === 'monthly'">Nincs rendelés a kiválasztott hónapban.</p>
            <p v-else>Válassz ki egy dátumot a rendelések megtekintéséhez.</p>
          </div>

          <!-- Rendelések táblázata -->
          <div v-else class="table-responsive">
            <table class="table table-hover orders-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Név</th>
                  <th>Dátum</th>
                  <th>Opció</th>
                  <th>Étel</th>
                  <th>Ár</th>
                  <th>Státusz</th>
                  <th>Rendelés ideje</th>
                  <th>Műveletek</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in orders" :key="order.id">
                  <td>{{ order.id }}</td>
                  <td>
                    <strong>{{ order.user?.email || 'Ismeretlen' }}</strong>
                    <small class="text-muted d-block">{{ order.user?.student_class || order.user?.userType }}</small>
                </td>
                  <td>{{ formatDate(order.orderDate) }}</td>
                  <td>
                    <span class="badge" :class="getOptionBadgeClass(order.selectedOption)">
                      {{ getOptionDisplay(order.selectedOption) }}
                    </span>
                  </td>
                  <td>
                    <div v-if="order.menuItem">
                      <small class="text-muted">Leves:</small> {{ order.menuItem.soupMeal?.mealName || '-' }}
                      <br>
                      <small class="text-muted">Főétel:</small> 
                      <span v-if="order.selectedOption === 'A'">
                        {{ order.menuItem.optionAMeal?.mealName || '-' }}
                      </span>
                      <span v-else-if="order.selectedOption === 'B'">
                        {{ order.menuItem.optionBMeal?.mealName || '-' }}
                      </span>
                      <span v-else>-</span>
                    </div>
                    <div v-else>-</div>
                  </td>
                  <td>
                    <strong>{{ formatCurrency(order.price || 0) }}</strong>
                  </td>
                  <td>
                    <span class="badge" :class="getStatusBadgeClass(order.orderStatus)">
                      {{ order.orderStatus }}
                    </span>
                  </td>
                  <td>
                    {{ formatDateTime(order.created_at) }}
                  </td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <button 
                        @click="viewOrderDetails(order)" 
                        class="btn btn-outline-info"
                        title="Részletek"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        v-if="order.orderStatus === 'Rendelve'"
                        @click="cancelOrder(order)" 
                        class="btn btn-outline-warning"
                        title="Lemondás"
                      >
                        <i class="fas fa-ban"></i>
                      </button>
                      <button 
                        v-if="order.orderStatus === 'Lemondva'"
                        @click="restoreOrder(order)" 
                        class="btn btn-outline-success"
                        title="Visszaállítás"
                      >
                        <i class="fas fa-undo"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Lapozás -->
          <div v-if="orders.length > 0 && totalPages > 1" class="pagination-section">
            <nav aria-label="Rendelések lapozása">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <button class="page-link" @click="changePage(currentPage - 1)">
                    <i class="fas fa-chevron-left"></i>
                  </button>
                </li>
                
                <li v-for="page in visiblePages" :key="page" 
                    class="page-item" :class="{ active: page === currentPage }">
                  <button class="page-link" @click="changePage(page)">
                    {{ page }}
                  </button>
                </li>
                
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <button class="page-link" @click="changePage(currentPage + 1)">
                    <i class="fas fa-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
            <div class="page-size-selector">
              <select v-model="pageSize" @change="changePageSize" class="form-select form-select-sm">
                <option value="10">10 / oldal</option>
                <option value="25">25 / oldal</option>
                <option value="50">50 / oldal</option>
                <option value="100">100 / oldal</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Havi előkészületi lista (csak havi nézetben) -->
      <div v-if="viewMode === 'monthly'" class="preparation-section card mt-4">
        <div class="card-header">
          <h3><i class="fas fa-clipboard-check"></i> Havi Előkészületi Lista</h3>
          <button @click="loadMonthlyPreparation" class="btn btn-primary btn-sm">
            <i class="fas fa-calculator"></i> Összesítés számítása
          </button>
        </div>
        
        <div class="card-body">
          <!-- Betöltés állapot -->
          <div v-if="monthlyLoading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Összesítés számítása...</span>
            </div>
            <p class="mt-2">Havi előkészületi lista generálása...</p>
          </div>

          <!-- Havi adatok -->
          <div v-else-if="monthlyData">
            <!-- Összegző információk -->
            <div class="monthly-summary mb-4">
              <div class="row">
                <div class="col-md-4">
                  <div class="summary-card">
                    <h5>Összes rendelés</h5>
                    <h2 class="text-primary">{{ monthlyData.total_orders }}</h2>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="summary-card">
                    <h5>A opció</h5>
                    <h2 class="text-success">{{ monthlyData.summary_by_option.A || 0 }}</h2>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="summary-card">
                    <h5>B opció</h5>
                    <h2 class="text-info">{{ monthlyData.summary_by_option.B || 0 }}</h2>
                  </div>
                </div>
              </div>
            </div>

            <!-- Ételenkénti összesítés -->
            <div class="meal-breakdown mb-5" v-if="monthlyData.meal_counts">
              <h4 class="mb-3"><i class="fas fa-utensil-spoon"></i> Ételenkénti Összesítés</h4>
              
              <!-- Levesek -->
              <div class="meal-category mb-4" v-if="monthlyData.meal_counts.soups && monthlyData.meal_counts.soups.length">
                <h5 class="text-primary">
                  <i class="fas fa-bowl-rice"></i> Levesek ({{ monthlyData.meal_counts.soups.length }} fajta)
                </h5>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Étel neve</th>
                        <th>Darabszám</th>
                        <th>%</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="soup in monthlyData.meal_counts.soups" :key="'soup-' + soup.id">
                        <td>{{ soup.name || `Leves #${soup.id}` }}</td>
                        <td>
                          <span class="badge bg-primary">{{ soup.count }}</span>
                        </td>
                        <td>
                          <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" 
                                 :style="{ width: (soup.count / monthlyData.total_orders * 100) + '%' }"></div>
                          </div>
                          <small>{{ ((soup.count / monthlyData.total_orders) * 100).toFixed(1) }}%</small>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- A opciók -->
              <div class="meal-category mb-4" v-if="monthlyData.meal_counts.optionA && monthlyData.meal_counts.optionA.length">
                <h5 class="text-success">
                  <i class="fas fa-drumstick-bite"></i> A Opció ételek ({{ monthlyData.meal_counts.optionA.length }} fajta)
                </h5>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Étel neve</th>
                        <th>Darabszám</th>
                        <th>%</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="meal in monthlyData.meal_counts.optionA" :key="'optionA-' + meal.id">
                        <td>{{ meal.name || `A opció #${meal.id}` }}</td>
                        <td>
                          <span class="badge bg-success">{{ meal.count }}</span>
                        </td>
                        <td>
                          <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" 
                                 :style="{ width: (meal.count / (monthlyData.summary_by_option.A || 1) * 100) + '%' }"></div>
                          </div>
                          <small>{{ ((meal.count / (monthlyData.summary_by_option.A || 1)) * 100).toFixed(1) }}%</small>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- B opciók -->
              <div class="meal-category mb-4" v-if="monthlyData.meal_counts.optionB && monthlyData.meal_counts.optionB.length">
                <h5 class="text-info">
                  <i class="fas fa-fish"></i> B Opció ételek ({{ monthlyData.meal_counts.optionB.length }} fajta)
                </h5>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Étel neve</th>
                        <th>Darabszám</th>
                        <th>%</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="meal in monthlyData.meal_counts.optionB" :key="'optionB-' + meal.id">
                        <td>{{ meal.name || `B opció #${meal.id}` }}</td>
                        <td>
                          <span class="badge bg-info">{{ meal.count }}</span>
                        </td>
                        <td>
                          <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" 
                                 :style="{ width: (meal.count / (monthlyData.summary_by_option.B || 1) * 100) + '%' }"></div>
                          </div>
                          <small>{{ ((meal.count / (monthlyData.summary_by_option.B || 1)) * 100).toFixed(1) }}%</small>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Export gomb 
            <div class="text-center mt-4">
              <button @click="exportMonthlyPreparation" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Havi előkészületi lista exportálása
              </button>
              <button @click="printMonthlyPreparation" class="btn btn-primary ms-2">
                <i class="fas fa-print"></i> Nyomtatás
              </button>
            </div>-->
          </div>

          <!-- Üres állapot havi előkészülethez -->
          <div v-else class="text-center py-5">
            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
            <h5>Nincs havi előkészületi lista</h5>
            <p class="text-muted">Kattints az "Összesítés számítása" gombra a havi adatok generálásához.</p>
          </div>
        </div>
      </div>

      <!-- Részletek modal -->
      <div v-if="selectedOrder" class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Rendelés részletei #{{ selectedOrder.id }}</h5>
              <button type="button" class="btn-close" @click="selectedOrder = null"></button>
            </div>
            <div class="modal-body">
              <!-- Egyszerű rendelés részletek -->
              <div class="order-details-simple">
                <div class="row mb-3">
                 <div class="col-md-6">
                    <h6>Felhasználó:</h6>
                    <p>{{ selectedOrder.user?.email || 'Ismeretlen' }}</p>
                    <p class="text-muted small">{{ selectedOrder.user?.userType }}</p>
                </div>
                  <div class="col-md-6">
                    <h6>Rendelés információk:</h6>
                    <p>Dátum: {{ formatDate(selectedOrder.orderDate) }}</p>
                    <p>Opció: 
                      <span class="badge" :class="getOptionBadgeClass(selectedOrder.selectedOption)">
                        {{ getOptionDisplay(selectedOrder.selectedOption) }}
                      </span>
                    </p>
                    <p>Ár: {{ formatCurrency(selectedOrder.price || 0) }}</p>
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
      <div v-if="selectedOrder" class="modal-backdrop fade show"></div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Orders',

  data() {
    return {
      // View settings
      viewMode: 'daily',
      selectedDate: new Date().toISOString().split('T')[0],
      selectedWeek: this.getCurrentWeek(),
      selectedMonth: new Date().toISOString().slice(0, 7),
      
      // Filters
      statusFilter: 'all',
      
      // Orders data
      orders: [],
      loading: false,
      monthlyLoading: false,
      
      // Pagination
      currentPage: 1,
      totalPages: 1,
      pageSize: 25,
      totalItems: 0,
      
      // Stats
      stats: {
        totalOrders: 0,
        activeOrders: 0,
        cancelledOrders: 0,
        totalRevenue: 0
      },
      
      // Monthly preparation data
      monthlyData: null,
      
      // Selected order for details
      selectedOrder: null,
      
      // API base URL - JAVÍTVA: nincs dupla /api
      apiBaseUrl: '/api/kitchen/orders'
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
    this.loadStats();
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
    
    statusFilter() {
      this.currentPage = 1;
      this.loadOrders();
    }
  },
  
  methods: {
    async loadOrders() {
  this.loading = true;
  try {
    let url = '';
    let params = {};
    
    // Add date filter based on view mode
    switch (this.viewMode) {
      case 'daily':
        url = `http://localhost:8000/api/kitchen/orders/date/${this.selectedDate}`;
        break;
      case 'weekly':
        const [year, week] = this.selectedWeek.split('-W');
        const firstDay = this.getDateOfISOWeek(week, year);
        const lastDay = new Date(firstDay);
        lastDay.setDate(firstDay.getDate() + 6);
        
        params.start_date = firstDay.toISOString().split('T')[0];
        params.end_date = lastDay.toISOString().split('T')[0];
        url = 'http://localhost:8000/api/kitchen/orders';
        break;
      case 'monthly':
        params.year = this.selectedYear;
        params.month = this.selectedMonthNumber;
        url = 'http://localhost:8000/api/kitchen/orders';
        break;
      default:
        url = 'http://localhost:8000/api/kitchen/orders';
    }
    
    console.log('API hívás:', { url, params });
    
    const token = localStorage.getItem('token') || this.getAuthToken();
    const response = await axios.get(url, { 
      params,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('Teljes API válasz:', response.data);
    
    // **FONTOS MÓDOSÍTÁS: Kezeljük a BOM karaktert**
    let responseData = response.data;
    
    // Ha a válasz string, próbáljuk megparse-olni
    if (typeof responseData === 'string') {
      try {
        // Távolítsuk el a BOM karaktert
        responseData = responseData.replace(/^\uFEFF/, '');
        responseData = JSON.parse(responseData);
      } catch (parseError) {
        console.error('JSON parse hiba:', parseError);
        throw new Error('Érvénytelen JSON válasz');
      }
    }
    
    // Ellenőrizzük a success mezőt
    if (responseData.success === true) {
      console.log('✅ API sikeres választ adott');
      
      // Napi nézet esetén külön kezeljük
      if (this.viewMode === 'daily') {
        // Az adatok response.data.data alatt vannak
        const dailyData = responseData.data || responseData;
        this.orders = dailyData.orders || [];
        this.totalItems = dailyData.total_count || this.orders.length;
        this.totalPages = 1;
        
        // Stats frissítése
        if (dailyData.summary) {
          this.updateStatsFromResponse(dailyData.summary);
        }
        
        console.log(`✅ Napi rendelések betöltve: ${this.orders.length} db`);
        
        // Debug: nézzük meg az első rendelést
        if (this.orders.length > 0) {
          console.log('Első rendelés:', this.orders[0]);
          console.log('User adatok:', this.orders[0].user);
          console.log('MenuItem adatok:', this.orders[0].menu_item);
        }
      } else {
        // Heti/havi nézet
        const ordersData = responseData.data || responseData;
        this.orders = ordersData.orders || [];
        
        // Pagination
        if (ordersData.pagination) {
          this.totalItems = ordersData.pagination.total || 0;
          this.totalPages = ordersData.pagination.last_page || 1;
        } else {
          this.totalItems = this.orders.length;
          this.totalPages = Math.ceil(this.totalItems / this.pageSize);
        }
        
        // Stats
        if (ordersData.summary) {
          this.updateStatsFromResponse(ordersData.summary);
        }
        
        console.log(`✅ Heti/havi rendelések betöltve: ${this.orders.length} db`);
      }
      
    } else {
      console.error('❌ API nem adott vissza success=true-t', responseData);
      this.orders = [];
      this.totalItems = 0;
      this.totalPages = 1;
    }
    
  } catch (error) {
    console.error('❌ Hiba a rendelések betöltésekor:', error);
    console.error('Error details:', error.response?.data);
    this.orders = [];
    this.totalItems = 0;
    this.totalPages = 1;
  } finally {
    this.loading = false;
  }
},


// Új helper metódus a statisztikák frissítéséhez
updateStatsFromResponse(summary) {
    this.stats = {
        totalOrders: summary.total_orders || summary.total || 0,
        activeOrders: summary.active_orders || 0,
        cancelledOrders: summary.cancelled_orders || 0,
        totalRevenue: summary.total_revenue || 0
    };
},
    
    async loadStats() {
  try {
    const token = localStorage.getItem('token') || this.getAuthToken();
    
    // Használjuk a napi összefoglaló endpoint-ot a statisztikákhoz
    const today = new Date().toISOString().split('T')[0];
    const response = await axios.get(`http://localhost:8000/api/kitchen/orders/date/${today}`, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });
    
    console.log('Stats API válasz:', response.data);
    
    if (response.data.success) {
      const summaryData = response.data.data?.summary || response.data.summary || {};
      this.updateStatsFromResponse(summaryData);
    }
  } catch (error) {
    console.error('Hiba a statisztikák betöltésekor:', error);
    // Ha hiba van, állítsunk alapértékeket
    this.stats = {
      totalOrders: 0,
      activeOrders: 0,
      cancelledOrders: 0,
      totalRevenue: 0
    };
  }
},
    
    async loadMonthlyPreparation() {
      this.monthlyLoading = true;
      try {
        const token = localStorage.getItem('token') || this.getAuthToken();
        const response = await axios.get(
          `http://localhost:8000/api/kitchen/orders/monthly-preparation/${this.selectedYear}/${this.selectedMonthNumber}`,
          {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Accept': 'application/json'
            }
          }
        );
        
        if (response.data.success) {
          this.monthlyData = response.data.data;
        }
      } catch (error) {
        console.error('Hiba a havi előkészület betöltésekor:', error);
        
        // Fallback: manuális számítás ha nincs endpoint
        if (error.response?.status === 404) {
          this.calculateMonthlyPreparationManually();
        }
      } finally {
        this.monthlyLoading = false;
      }
    },
    
    // Helper methods
    calculateMonthlyPreparationManually() {
      // Fallback számítás ha nincs backend endpoint
      const ordersThisMonth = this.orders.filter(order => {
        const orderDate = new Date(order.orderDate);
        return orderDate.getMonth() + 1 == this.selectedMonthNumber && 
               orderDate.getFullYear() == this.selectedYear;
      });
      
      const mealCounts = { soups: {}, optionA: {}, optionB: {} };
      
      ordersThisMonth.forEach(order => {
        if (order.menuItem?.soupMeal) {
          const soupId = order.menuItem.soup;
          mealCounts.soups[soupId] = (mealCounts.soups[soupId] || 0) + 1;
        }
        
        if (order.selectedOption === 'A' && order.menuItem?.optionAMeal) {
          const mealId = order.menuItem.optionA;
          mealCounts.optionA[mealId] = (mealCounts.optionA[mealId] || 0) + 1;
        }
        
        if (order.selectedOption === 'B' && order.menuItem?.optionBMeal) {
          const mealId = order.menuItem.optionB;
          mealCounts.optionB[mealId] = (mealCounts.optionB[mealId] || 0) + 1;
        }
      });
      
      this.monthlyData = {
        total_orders: ordersThisMonth.length,
        meal_counts: {
          soups: Object.entries(mealCounts.soups).map(([id, count]) => ({ 
            id: id,
            name: 'Leves ' + id,
            count: count 
          })),
          optionA: Object.entries(mealCounts.optionA).map(([id, count]) => ({ 
            id: id,
            name: 'A opció ' + id,
            count: count 
          })),
          optionB: Object.entries(mealCounts.optionB).map(([id, count]) => ({ 
            id: id,
            name: 'B opció ' + id,
            count: count 
          }))
        },
        summary_by_option: {
          A: ordersThisMonth.filter(o => o.selectedOption === 'A').length,
          B: ordersThisMonth.filter(o => o.selectedOption === 'B').length
        }
      };
    },
    
    // Order actions
    viewOrderDetails(order) {
      this.selectedOrder = order;
    },
    
    async cancelOrder(order) {
      if (!confirm('Biztosan lemondja ezt a rendelést?')) return;
      
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
          this.loadStats();
        }
      } catch (error) {
        console.error('Hiba a rendelés lemondásakor:', error);
        alert('Hiba a rendelés lemondásakor');
      }
    },
    
    async restoreOrder(order) {
      if (!confirm('Biztosan visszaállítja ezt a rendelést?')) return;
      
      try {
        // Ez egy admin endpoint lehet, de lehet még nincs
        // Most csak frissítjük a helyi állapotot
        order.orderStatus = 'Rendelve';
        alert('Rendelés helyileg visszaállítva');
      } catch (error) {
        console.error('Hiba a rendelés visszaállításakor:', error);
        alert('Hiba a rendelés visszaállításakor');
      }
    },
    
    // Token helper
    getAuthToken() {
      // Próbáld meg a különböző helyeken megtalálni a tokent
      return localStorage.getItem('auth_token') || 
             localStorage.getItem('sanctum-token') ||
             sessionStorage.getItem('token') ||
             '';
    },
    
    // Export methods
    exportData() {
      let exportData = [];
      
      if (this.viewMode === 'monthly' && this.monthlyData) {
        // Export monthly preparation data
        exportData = this.formatMonthlyDataForExport();
      } else {
        // Export order list
        exportData = this.orders.map(order => ({
          ID: order.id,
          Név: order.user?.name || '',
          Osztály: order.user?.student_class || '',
          Dátum: this.formatDate(order.orderDate),
          Opció: this.getOptionDisplay(order.selectedOption),
          Leves: order.menuItem?.soupMeal?.mealName || '',
          Főétel: order.selectedOption === 'A' 
            ? order.menuItem?.optionAMeal?.mealName || ''
            : order.menuItem?.optionBMeal?.mealName || '',
          Ár: order.price || 0,
          Státusz: order.orderStatus,
          'Rendelés ideje': this.formatDateTime(order.created_at)
        }));
      }
      
      this.downloadCSV(exportData, `rendelesek_${this.selectedDate || this.selectedMonth}.csv`);
    },
    
    formatMonthlyDataForExport() {
      const data = [];
      
      // Summary
      data.push(['Havi Összesítés', '', '', '']);
      data.push(['Hónap', this.selectedMonth, '', '']);
      data.push(['Összes rendelés', this.monthlyData.total_orders, '', '']);
      data.push(['A opció', this.monthlyData.summary_by_option.A || 0, '', '']);
      data.push(['B opció', this.monthlyData.summary_by_option.B || 0, '', '']);
      data.push(['', '', '', '']);
      
      // Soups
      data.push(['LEVESEK', 'Darabszám', '%', '']);
      this.monthlyData.meal_counts?.soups?.forEach(soup => {
        const percentage = ((soup.count / this.monthlyData.total_orders) * 100).toFixed(1);
        data.push([soup.name, soup.count, `${percentage}%`, '']);
      });
      data.push(['', '', '', '']);
      
      // Option A
      data.push(['A OPCIÓ ÉTELEK', 'Darabszám', '%', '']);
      this.monthlyData.meal_counts?.optionA?.forEach(meal => {
        const totalA = this.monthlyData.summary_by_option.A || 1;
        const percentage = ((meal.count / totalA) * 100).toFixed(1);
        data.push([meal.name, meal.count, `${percentage}%`, '']);
      });
      data.push(['', '', '', '']);
      
      // Option B
      data.push(['B OPCIÓ ÉTELEK', 'Darabszám', '%', '']);
      this.monthlyData.meal_counts?.optionB?.forEach(meal => {
        const totalB = this.monthlyData.summary_by_option.B || 1;
        const percentage = ((meal.count / totalB) * 100).toFixed(1);
        data.push([meal.name, meal.count, `${percentage}%`, '']);
      });
      
      return data;
    },
    
    exportMonthlyPreparation() {
      if (!this.monthlyData) {
        alert('Először generáld a havi adatokat!');
        return;
      }
      
      const exportData = this.formatMonthlyDataForExport();
      this.downloadCSV(exportData, `havi_elokeszület_${this.selectedMonth}.csv`);
    },
    
    printMonthlyPreparation() {
      window.print();
    },
    
    downloadCSV(data, filename) {
      // Tömb vagy objektum ellenőrzése
      let csvContent;
      
      if (Array.isArray(data[0]) && !Array.isArray(data[0][0])) {
        // Objektumok tömbje
        const headers = Object.keys(data[0]);
        csvContent = [
          headers.join(','),
          ...data.map(row => 
            headers.map(header => `"${row[header] || ''}"`).join(',')
          )
        ].join('\n');
      } else {
        // 2D tömb
        csvContent = data.map(row => 
          row.map(cell => `"${cell}"`).join(',')
        ).join('\n');
      }
      
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      link.click();
    },
    
    // UI helpers
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
        'Fizetve': 'bg-info'
      };
      return classes[status] || 'bg-secondary';
    },
    
    // Pagination
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page;
        this.loadOrders();
      }
    },
    
    changePageSize() {
      this.currentPage = 1;
      this.loadOrders();
    },
    
    // Date helpers
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
    
    toggleFilters() {
      console.log('Toggle filters');
    }
  }
};
</script>

<style scoped>
/* A stílusok maradnak ugyanazok, csak rövidebb */
.orders-container {
  padding: 20px;
  background-color: #f8f9fa;
  min-height: 100vh;
}

.header-section {
  margin-bottom: 30px;

}



.subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
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

.stats-section {
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  display: flex;
  align-items: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
  transition: transform 0.3s;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  color: white;
  font-size: 24px;
}

.stat-info h3 {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #2c3e50;
}

.stat-info p {
  margin: 5px 0 0 0;
  color: #7f8c8d;
  font-size: 0.9rem;
}

.card {
  background: white;
  border-radius: 10px;
  border: none;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-bottom: 25px;
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

.card-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.card-body {
  padding: 20px;
}

.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 50px;
  color: #6c757d;
}

.empty-state {
  text-align: center;
  padding: 50px;
  color: #6c757d;
}

.empty-state i {
  color: #dee2e6;
  margin-bottom: 20px;
}

.orders-table th {
  font-weight: 600;
  color: #495057;
  border-top: none;
  background-color: #f8f9fa;
}

.orders-table td {
  vertical-align: middle;
}

.badge {
  font-size: 0.85em;
  padding: 5px 10px;
  border-radius: 20px;
}

.pagination-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #eee;
}

.page-size-selector {
  width: 120px;
}

.preparation-section .meal-category {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
}

.preparation-section h4, .preparation-section h5 {
  color: #2c3e50;
  font-weight: 600;
}

.summary-card {
  text-align: center;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 10px;
}

.summary-card h5 {
  color: #6c757d;
  margin-bottom: 10px;
}

.summary-card h2 {
  margin: 0;
  font-weight: 700;
}

.ingredient-type {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 15px;
}

.modal-backdrop {
  opacity: 0.5;
}

.order-details-simple h6 {
  color: #495057;
  font-weight: 600;
  margin-bottom: 5px;
}

.order-details-simple p {
  margin-bottom: 10px;
}

@media print {
  .filters-section,
  .stats-section,
  .card-header .btn,
  .pagination-section,
  .btn {
    display: none !important;
  }
  
  .card {
    box-shadow: none !important;
    border: 1px solid #dee2e6 !important;
  }
  
  .table {
    border: 1px solid #dee2e6;
  }
}

@media (max-width: 768px) {
  .filter-row {
    flex-direction: column;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .stat-card {
    margin-bottom: 15px;
  }
  
  .card-actions {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .pagination-section {
    flex-direction: column;
    gap: 15px;
  }
}
</style>