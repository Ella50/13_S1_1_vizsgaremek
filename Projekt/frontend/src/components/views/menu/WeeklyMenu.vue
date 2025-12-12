<!-- src/views/menu/WeeklyMenu.vue -->
<template>
  <div class="weekly-menu">
    <div class="header">
      <h1>Heti menü</h1>
      <div class="week-navigation">
        <button @click="prevWeek" class="nav-btn">Előző hét</button>
        <span class="week-range">{{ weekDisplay }}</span>
        <button @click="nextWeek" class="nav-btn">Következő hét</button>
      </div>
    </div>
    
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Heti menü betöltése...</p>
    </div>
    
    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="fetchWeeklyMenu" class="btn-retry">Újra próbál</button>
    </div>
    
    <div v-else class="weekly-schedule">
      <div v-for="(dayData, date) in weeklyMenu" :key="date" class="day-card">
        <div class="day-header">
          <h2>{{ dayData.day }}</h2>
          <div class="date">{{ formatDate(date) }}</div>
        </div>
        
        <div v-if="dayData.menu && dayData.menu.length" class="day-menu">
          <div v-for="item in dayData.menu" :key="item.id" class="menu-item">
            <div class="item-header">
              <h3>{{ item.meal?.name || 'Étel' }}</h3>
              <span class="meal-type">{{ getMealTypeName(item.meal_type) }}</span>
            </div>
            
            <p class="item-description">{{ item.meal?.description || '' }}</p>
            
            <div class="item-details">
              <span class="item-price">{{ item.meal?.price || 0 }} Ft</span>
              
              
              <div v-if="item.meal?.allergens && item.meal.allergens.length" class="item-allergens">
                <small>Allergének: {{ item.meal.allergens.join(', ') }}</small>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="no-menu">
          <p>Nincs elérhető menü erre a napra</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'

export default {
  data() {
    return {
      weeklyMenu: {},
      loading: false,
      error: '',
      currentWeekStart: null
    }
  },
  
  computed: {
    weekDisplay() {
      if (!this.currentWeekStart) return ''
      
      const start = new Date(this.currentWeekStart)
      const end = new Date(start)
      end.setDate(end.getDate() + 6)
      
      const format = (date) => {
        return date.toLocaleDateString('hu-HU', {
          month: 'short',
          day: 'numeric'
        })
      }
      
      return `${format(start)} - ${format(end)}`
    }
  },
  
  mounted() {

    this.setCurrentWeekStart()
    this.fetchWeeklyMenu()
  },
  
  methods: {
    setCurrentWeekStart() {
      const today = new Date()
      const dayOfWeek = today.getDay() // 0 = vasárnap, 1 = hétfő
      
      const diff = dayOfWeek === 0 ? -6 : 1 - dayOfWeek
      const monday = new Date(today)
      monday.setDate(today.getDate() + diff)
      monday.setHours(0, 0, 0, 0)
      
      this.currentWeekStart = monday.toISOString().split('T')[0]
    },
    
    async fetchWeeklyMenu() {
      this.loading = true
      this.error = ''
      
      try {
        const params = {}
        if (this.currentWeekStart) {
          params.week_start = this.currentWeekStart
        }
        
        const response = await AuthService.api.get('/menu/week', { params })
        this.weeklyMenu = response.data.weekly_menu || {}
      } catch (error) {
        console.error('Heti menü betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'Hiba történt a heti menü betöltésekor'
      } finally {
        this.loading = false
      }
    },
    
    prevWeek() {
      if (!this.currentWeekStart) return
      
      const date = new Date(this.currentWeekStart)
      date.setDate(date.getDate() - 7)
      this.currentWeekStart = date.toISOString().split('T')[0]
      this.fetchWeeklyMenu()
    },
    
    nextWeek() {
      if (!this.currentWeekStart) return
      
      const date = new Date(this.currentWeekStart)
      date.setDate(date.getDate() + 7)
      this.currentWeekStart = date.toISOString().split('T')[0]
      this.fetchWeeklyMenu()
    },
    
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    
    getMealTypeName(type) {
      const names = {
        'ebéd': 'Ebéd',
      }
      return names[type] || type
    }
  }
}
</script>

<style scoped>
.weekly-menu {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 3rem;
}

.header h1 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 2rem;
}

.week-navigation {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 2rem;
}

.week-range {
  font-size: 1.125rem;
  font-weight: 500;
  color: #2c3e50;
  min-width: 200px;
}

.nav-btn {
  padding: 0.5rem 1rem;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.nav-btn:hover {
  background: #2980b9;
}

.weekly-schedule {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.day-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.day-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.day-header {
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f1f2f6;
}

.day-header h2 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 1.5rem;
}

.date {
  color: #7f8c8d;
  font-size: 0.875rem;
}

/* Menü elemek */
.day-menu {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.menu-item {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  border-left: 4px solid #3498db;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.item-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.125rem;
}

.meal-type {
  background: #e3f2fd;
  color: #1565c0;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.item-description {
  color: #7f8c8d;
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  line-height: 1.4;
}

.item-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.item-price {
  font-weight: bold;
  color: #27ae60;
  font-size: 1.125rem;
}

.item-tags {
  display: flex;
  gap: 0.5rem;
  margin: 0.25rem 0;
}

.tag {
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.tag.veg {
  background: #d4edda;
  color: #155724;
}

.tag.vegan {
  background: #d1ecf1;
  color: #0c5460;
}

.item-allergens small {
  color: #95a5a6;
}

.no-menu {
  padding: 2rem;
  text-align: center;
  color: #95a5a6;
  background: #f8f9fa;
  border-radius: 8px;
  border: 2px dashed #dee2e6;
}

.loading {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error {
  text-align: center;
  padding: 3rem;
  color: #e74c3c;
}

.btn-retry {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-retry:hover {
  background: #2980b9;
}


@media (max-width: 768px) {
  .weekly-schedule {
    grid-template-columns: 1fr;
  }
  
  .week-navigation {
    flex-direction: column;
    gap: 1rem;
  }
  
  .nav-btn {
    width: 100%;
  }
}
</style>