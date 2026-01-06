<template>
  <div class="meals-management">
    <div class="header">
      <h1>Ételek kezelése</h1>
      <button @click="showAddModal = true" class="btn-add">
        + Új étel
      </button>
    </div>
    
    <div class="filters">
      <input 
        v-model="search" 
        placeholder="Keresés"
        @input="fetchMeals"
      />
      <select v-model="selectedCategory" @change="fetchMeals">
        <option value="">Összes kategória</option>
        <option value="Leves">Leves</option>
        <option value="Főétel">Főétel</option>
      </select>
    </div>
    
    <div v-if="loading" class="loading">Betöltés...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="meals-container">
      <div v-if="meals.length === 0" class="no-meals">
        <p>Nincs még étel hozzáadva.</p>
      </div>
      
      <div v-else class="meals-grid">
        <div v-for="meal in meals" :key="meal.id" class="meal-card">
          <div class="meal-header">
            <h3>{{ meal.name }}</h3>
            <span class="meal-category">{{ meal.category }}</span>
          </div>
          
          <p class="meal-description">{{ meal.description || 'Nincs leírás' }}</p>
          
          <div class="meal-details">
            
            
            <div v-if="meal.allergens && meal.allergens.length" class="allergens">
              <strong>Allergének:</strong> {{ meal.allergens.join(', ') }}
            </div>
            <!--
            <div v-if="meal.calories" class="calories">
              <strong>Kalória:</strong> {{ meal.calories }} kcal
            </div>-->
          </div>
          
          <div class="meal-actions">
            <button @click="editMeal(meal)" class="btn-edit">Szerkesztés</button>
            <button @click="deleteMeal(meal.id)" class="btn-delete">Törlés</button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showAddModal || editingMeal" class="modal-overlay">
      <div class="modal">
        <h2>{{ editingMeal ? 'Étel szerkesztése' : 'Új étel hozzáadása' }}</h2>
        
        <form @submit.prevent="saveMeal">
          <div class="form-group">
            <label>Étel neve *</label>
            <input v-model="mealForm.name" required maxlength="255" />
          </div>
          
          <div class="form-group">
            <label>Kategória *</label>
            <select v-model="mealForm.category" required>
              <option value="">Válassz kategóriát</option>
              <option value="Leves">Leves</option>
              <option value="Főétel">Főétel</option>
            </select>
          </div>
          
          
          <div class="form-group">
            <label>Leírás</label>
            <textarea v-model="mealForm.description" rows="3"></textarea>
          </div>
          
          <!--<div class="form-group">
            <label>Kalória (opcionális)</label>
            <input v-model.number="mealForm.calories" type="number" min="0" />
          </div>-->
          
          
          <div class="form-group">
            <label>Allergének</label>
            <!--<input v-model="allergensInput"/>-->
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-cancel">
              Mégse
            </button>
            <button type="submit" class="btn-save">
              {{ editingMeal ? 'Mentés' : 'Hozzáadás' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'

export default {
  data() {
    return {
      meals: [],
      loading: false,
      error: '',
      search: '',
      selectedCategory: '',
      showAddModal: false,
      editingMeal: null,
      
      mealForm: {
        name: '',
        category: '',
        description: '',
        calories: null,
        allergens: [],
      },
      
      allergensInput: ''
    }
  },
  
  mounted() {
    this.fetchMeals()
  },
  
  watch: {
    allergensInput(newValue) {
      
    }
  },
  
  methods: {
    async fetchMeals() {
      this.loading = true
      this.error = ''
      
      try {
        const params = {}
        if (this.search) params.search = this.search
        if (this.selectedCategory) params.category = this.selectedCategory
        
        const response = await AuthService.api.get('/kitchen/meals', { params })
        this.meals = response.data.meals || []
      } catch (error) {
        console.error('Ételek betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'Hiba történt'
        
        if (error.response?.status === 403) {
          this.$router.push('/dashboard')
        }
      } finally {
        this.loading = false
      }
    },
    
    editMeal(meal) {
      this.editingMeal = meal
      this.mealForm = { ...meal }
      this.allergensInput = meal.allergens?.join(', ') || ''
      this.showAddModal = true
    },
    
    async saveMeal() {
      try {
        if (this.editingMeal) {
          await AuthService.api.put(`/kitchen/meals/${this.editingMeal.id}`, this.mealForm)
          this.$toast?.success('Étel sikeresen frissítve')
          alert('Étel sikeresen frissítve')
        } else {
          await AuthService.api.post('/kitchen/meals', this.mealForm)
          this.$toast?.success('Étel sikeresen hozzáadva')
          alert('Étel sikeresen hozzáadva')
        }
        
        this.closeModal()
        this.fetchMeals()
      } catch (error) {
        console.error('Étel mentése sikertelen:', error)
        alert(error.response?.data?.message || 'Hiba történt a mentés során')
      }
    },
    
    async deleteMeal(mealId) {
      if (!confirm('Biztosan törölni szeretné ezt az ételt?')) {
        return
      }
      
      try {
        await AuthService.api.delete(`/kitchen/meals/${mealId}`)
        this.meals = this.meals.filter(meal => meal.id !== mealId)
        this.$toast?.success('Étel sikeresen törölve')
      } catch (error) {
        console.error('Étel törlése sikertelen:', error)
        alert(error.response?.data?.message || 'Hiba történt a törlés során')
      }
    },
    
    closeModal() {
      this.showAddModal = false
      this.editingMeal = null
      this.resetForm()
    },
    
    resetForm() {
      this.mealForm = {
        name: '',
        category: '',
        description: '',
        calories: null,
        allergens: [],
      }
      this.allergensInput = ''
    }
  }
}
</script>

<style scoped>
.meals-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0;
  color: #2c3e50;
}

.btn-add {
  background: #27ae60;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  width: 10%;
  min-width: 150px;
}

.btn-add:hover {
  background: #219653;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.filters input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.filters select {
  padding: 0.75rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  min-width: 200px;
}

.meals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.meal-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.meal-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.meal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.meal-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.meal-category {
  background: #ffd294;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
}

.meal-description {
  color: #7f8c8d;
  margin: 0 0 1rem 0;
  line-height: 1.5;
  font-size: 0.875rem;
}

.meal-details {
  margin-bottom: 1.5rem;
}

.meal-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.tag {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}


.tag.gf {
  background: #fff3cd;
  color: #856404;
}

.allergens, .calories {
  font-size: 0.875rem;
  color: #7f8c8d;
  margin-top: 0.25rem;
}

.meal-actions {
  display: flex;
  gap: 0.5rem;
}

.meal-actions button {
  flex: 1;
  padding: 0.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-edit {
  background: #3498db;
  color: white;
}

.btn-edit:hover {
  background: #2980b9;
}

.btn-delete {
  background: #e74c3c;
  color: white;
}

.btn-delete:hover {
  background: #c0392b;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  opacity: 1; 
  visibility: visible; 
}

.modal {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
  position: relative !important;
  z-index: 1001;
  transform: translateY(0);
  opacity: 1; 
  visibility: visible !important;
  display: block !important;
  padding: 35px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  color: #2c3e50;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #2c3e50;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group textarea {
  resize: vertical;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.form-row .form-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.form-row .form-group input[type="checkbox"] {
  width: auto;
}

small {
  display: block;
  margin-top: 0.25rem;
  color: #95a5a6;
  font-size: 0.75rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: #95a5a6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-cancel:hover {
  background: #7f8c8d;
}

.btn-save {
  padding: 0.75rem 1.5rem;
  background: #27ae60;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-save:hover {
  background: #219653;
}

.loading, .error, .no-meals {
  text-align: center;
  padding: 3rem;
}

.error {
  color: #e74c3c;
}

.no-meals {
  color: #7f8c8d;
}

.btn-add-first {
  margin-top: 1rem;
  padding: 0.75rem 1.5rem;
  background: #3498db;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-add-first:hover {
  background: #2980b9;
}
</style>