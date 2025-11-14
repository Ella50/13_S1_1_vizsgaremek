<template>
  <div class="data-display">
    <div v-if="loading" class="loading">Adatok betöltése...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else class="data-content">
      <div v-if="data.length === 0" class="data-placeholder">
        <p>adatbázisból lekérdezett adatok</p>
      </div>
      <div v-else class="data-list">
        <div v-for="item in data" :key="item.id" class="data-item">
          <h3>{{ item.title }}</h3>
          <p>{{ item.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DataDisplay',
  data() {
    return {
      loading: false,
      error: null,
      data: []
    }
  },
  async mounted() {
    // Később itt lesz az API hívás
    // await this.fetchData()
  },
  methods: {
    async fetchData() {
      this.loading = true
      this.error = null
      
      try {
        // Példa API hívás - később aktiválandó
        // const response = await this.$api.get('/data')
        // this.data = response.data
      } catch (err) {
        this.error = 'Hiba történt az adatok betöltése során'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.data-display {
  min-height: 200px;
}

.loading, .error {
  text-align: center;
  padding: 2rem;
  font-size: 1.1rem;
}

.error {
  color: #e74c3c;
}

.data-placeholder {
  text-align: center;
  padding: 3rem;
  background: #f8f9fa;
  border-radius: 8px;
  color: #7f8c8d;
}

.data-list {
  display: grid;
  gap: 1rem;
}

.data-item {
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #6a11cb;
}

.data-item h3 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.data-item p {
  color: #7f8c8d;
}
</style>