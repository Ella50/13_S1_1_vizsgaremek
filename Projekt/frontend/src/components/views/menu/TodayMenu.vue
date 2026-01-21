<template>
  <div class="today-menu">
    <h1>Mai menü</h1>
    <p>{{ todayDate }}</p>


    <div v-if="loading" class="loading">Betöltés...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else-if="hasMenu" class="menu-sections">
      <div class="menu-section">
        <h2>Leves</h2>
        <MealCard v-if="menu?.soup" :meal="menu.soup" />
        <div v-else class="no-items">Nincs leves</div>
      </div>

      <div class="menu-section">
        <h2>"A" opció</h2>
        <MealCard v-if="menu?.optionA" :meal="menu.optionA" />
        <div v-else class="no-items">Nincs A opció</div>
      </div>

      <div class="menu-section">
        <h2>"B" opció</h2>
        <MealCard v-if="menu?.optionB" :meal="menu.optionB" />
        <div v-else class="no-items">Nincs B opció</div>
      </div>

      <div class="menu-section">
        <h2>🧁 Egyéb</h2>
        <MealCard v-if="menu?.other" :meal="menu.other" />
        <div v-else class="no-items">Nincs egyéb</div>
      </div>

    </div>

    <div v-else class="no-items">
      Nincs menü a mai napra.
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
        this.menu = res.data || null
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

.no-items {
  padding: 1.25rem;
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
