
<template>
  <div class="today-menu">
    <h1>Mai menü</h1>
    <p>{{ todayDate }}</p>
    
    <div v-if="loading" class="loading">Betöltés...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else class="menu-sections">
      <div v-for="(items, type) in groupedMenu" :key="type" class="menu-section">
        <h2>{{ getMealTypeName(type) }}</h2>
        
        <div v-if="items.length === 0" class="no-items">
          Nincs elérhető étel
        </div>
        
        <div v-else class="meal-list">
          <div v-for="item in items" :key="item.id" class="meal-card">
            <h3>{{ item.meal?.name || 'Étel' }}</h3>
            <p>{{ item.meal?.description || '' }}</p>
            <div class="meal-info">
            </div>
          </div>
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
      menuItems: [],
      loading: false,
      error: ''
    }
  },
  
  computed: {
    todayDate() {
      return new Date().toLocaleDateString('hu-HU', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    
    groupedMenu() {
      const grouped = {
        ebéd: [],
      }
      
      this.menuItems.forEach(item => {
        if (grouped[item.meal_type]) {
          grouped[item.meal_type].push(item)
        }
      })
      
      return grouped
    }
  },
  
  mounted() {
    this.fetchTodayMenu()
  },
  
  methods: {
    async fetchTodayMenu() {
      this.loading = true
      this.error = ''
      
      try {
        const response = await AuthService.api.get('/menu/today')
        this.menuItems = response.data.menu_items || []
      } catch (error) {
        console.error('Mai menü betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'Hiba'
      } finally {
        this.loading = false
      }
    },
    
    getMealTypeName(type) {
      const names = {
        'ebéd': '🍽️ Ebéd',
      }
      return names[type] || type
    }
  }
}
</script>

<style scoped>
.today-menu {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.menu-sections {
  margin-top: 2rem;
}

.menu-section {
  margin-bottom: 3rem;
}

.menu-section h2 {
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #eee;
  color: #2c3e50;
}

.meal-list {
  display: grid;
  gap: 1rem;
}

.meal-card {
  padding: 1.5rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
}

.meal-card h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.meal-card p {
  margin: 0 0 1rem 0;
  color: #7f8c8d;
  font-size: 0.875rem;
}

.meal-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.price {
  font-weight: bold;
  color: #27ae60;
  font-size: 1.125rem;
}

.veg-badge {
  background: #d4edda;
  color: #155724;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
}

.no-items {
  padding: 2rem;
  text-align: center;
  color: #95a5a6;
  background: #f8f9fa;
  border-radius: 8px;
  border: 2px dashed #dee2e6;
}

.loading, .error {
  text-align: center;
  padding: 3rem;
}

.error {
  color: #e74c3c;
}
</style>