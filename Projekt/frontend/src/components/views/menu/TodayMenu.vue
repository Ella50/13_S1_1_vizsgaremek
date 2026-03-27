<template>
  <div class="today-menu">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Mai menü</h1>
        <div class="date-badge">{{ todayDate }}</div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Menü betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="fetchTodayMenu" class="btn-secondary">Újrapróbálkozás</button>
      </div>

      <div v-else-if="hasMenu" class="menu-sections">
        <!-- Leves -->
        <div class="menu-section">
          <div class="section-header">
            <span class="section-label">Leves</span>
          </div>
          <MealCard v-if="menu?.soup" :meal="menu.soup" />
          <div v-else class="no-items">Nincs</div>
        </div>

        <!-- A opció -->
        <div class="menu-section">
          <div class="section-header">
            <span class="section-label">A opció</span>
          </div>
          <MealCard v-if="menu?.optionA" :meal="menu.optionA" />
          <div v-else class="no-items">Nincs</div>
        </div>

        <!-- B opció -->
        <div class="menu-section">
          <div class="section-header">
            <span class="section-label">B opció</span>
          </div>
          <MealCard v-if="menu?.optionB" :meal="menu.optionB" />
          <div v-else class="no-items">Nincs</div>
        </div>

        <!-- Egyéb -->
        <div class="menu-section">
          <div class="section-header">
            <span class="section-label">Egyéb</span>
          </div>
          <MealCard v-if="menu?.other" :meal="menu.other" />
          <div v-else class="no-items">Nincs</div>
        </div>
      </div>

      <div v-else class="empty-state">
        <div class="empty-icon">📋</div>
        <p>Nincs menü a mai napra</p>
        <p class="empty-hint">Kérjük, nézz vissza később</p>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'
import MealCard from '../menu/MealCard.vue'

export default {
  components: { MealCard },

  data() {
    return {
      menu: null,
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

    hasMenu() {
      return !!(
        this.menu &&
        (this.menu.soup || this.menu.optionA || this.menu.optionB || this.menu.other)
      )
    }
  },

  methods: {
    async fetchTodayMenu() {
      this.loading = true
      this.error = ''
      this.menu = null

      try {
        const res = await AuthService.api.get('/menu/today')
        this.menu = res.data?.data || null
      } catch (e) {
        this.error = e?.response?.data?.message || 'Hiba a menü betöltésekor'
      } finally {
        this.loading = false
      }
    }
  },

  mounted() {
    this.fetchTodayMenu()
  }
}
</script>

<style scoped>
.today-menu {
  padding: 2rem;
  max-width: 800px;
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

.date-badge {
  background: #fff7e6;
  padding: 0.5rem 1rem;
  border-radius: 30px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #8a1212;
  border: 1px solid #f0a24a;
}

/* Menu sections - egymás alatt */
.menu-sections {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.menu-section {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.section-header {
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f0a24a;
}

.section-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #8a1212;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.no-items {
  background: #f8f9fa;
  border: 2px dashed #e0e0e0;
  border-radius: 16px;
  padding: 1.5rem;
  text-align: center;
  color: #888;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.no-items:hover {
  background: #fef9ef;
  border-color: #f0a24a;
}

/* Loading state */
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

.loading-state p {
  color: #666;
  font-size: 0.85rem;
}

/* Error state */
.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 1rem;
  border-radius: 12px;
  text-align: center;
}

.error-message p {
  margin: 0 0 1rem 0;
}

.btn-secondary {
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

.btn-secondary:hover {
  background: #dee2e6;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  background: #f9f9f9;
  border-radius: 12px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.6;
}

.empty-state p {
  margin: 0;
}

.empty-hint {
  font-size: 0.8rem;
  color: #888;
  margin-top: 0.5rem !important;
}

/* Responsive */
@media (max-width: 768px) {
  .today-menu {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .card-header {
    flex-direction: column;
    text-align: center;
  }

  .title {
    font-size: 1.25rem;
  }

  .date-badge {
    font-size: 0.75rem;
  }

  .menu-sections {
    gap: 1rem;
  }

  .section-label {
    font-size: 0.85rem;
  }
}

@media (max-width: 480px) {
  .title {
    font-size: 1.1rem;
  }

  .section-label {
    font-size: 0.8rem;
  }

  .no-items {
    padding: 1rem;
    font-size: 0.75rem;
  }
}
</style>