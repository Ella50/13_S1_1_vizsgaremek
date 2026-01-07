<template>
  <div class="meals-management">
    <div class="header">
      <h1>Ételek kezelése</h1>
      <button @click="showAddModal = true" class="btn-add" v-if="canEdit">
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
        <option value="Egyéb">Egyéb</option>
      </select>
    </div>
    
    <div v-if="loading" class="loading">Betöltés...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="meals-container">
      <div v-if="meals.length === 0" class="no-meals">
        <p>Nincs még étel hozzáadva.</p>
        <button @click="showAddModal = true" class="btn-add-first" v-if="canEdit">
          Adj hozzá első ételed!
        </button>
      </div>
      
      <div v-else class="meals-grid">
        <div v-for="meal in meals" :key="meal.id" class="meal-card">
          <div class="meal-header">
            <h3>{{ meal.name }}</h3>
            <span class="meal-category">{{ meal.category }}</span>
          </div>
          
          <p class="meal-description">{{ meal.description || 'Nincs leírás' }}</p>
          
          <!-- Összetevők előnézet -->
          <div v-if="meal.ingredients && meal.ingredients.length > 0" class="ingredients-preview">
            <strong>Összetevők:</strong>
            <div class="preview-items">
              <span v-for="(ingredient, index) in meal.ingredients.slice(0, 3)" :key="ingredient.id" class="preview-item">
                {{ ingredient.name }}<span v-if="ingredient.pivot"> ({{ ingredient.pivot.amount }}{{ ingredient.pivot.unit }})</span>
              </span>
              <span v-if="meal.ingredients.length > 3" class="more-items">
                +{{ meal.ingredients.length - 3 }} további
              </span>
            </div>
          </div>
          
          <div class="meal-actions">
            <button @click="editMeal(meal)" class="btn-edit" v-if="canEdit">Szerkesztés</button>
            <button @click="viewIngredients(meal)" class="btn-view">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              Összetevők
            </button>
            <button @click="deleteMeal(meal.id)" class="btn-delete" v-if="canEdit">Törlés</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Új étel / Szerkesztés modal -->
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
              <option value="Egyéb">Egyéb</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Leírás</label>
            <textarea v-model="mealForm.description" rows="3"></textarea>
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
    
    <!-- Összetevők modal -->
    <div v-if="showIngredientsModal" class="modal-overlay">
      <div class="modal ingredients-modal">
        <div class="modal-header">
          <h2>{{ selectedMeal?.name }} - Összetevők</h2>
          <button @click="closeIngredientsModal" class="close-btn">&times;</button>
        </div>
        
        <div v-if="loadingIngredients" class="loading">Összetevők betöltése...</div>
        
        <div v-else-if="ingredientsError" class="error">{{ ingredientsError }}</div>
        
        <div v-else class="ingredients-content">
          <div v-if="!selectedMeal?.ingredients || selectedMeal.ingredients.length === 0" class="no-ingredients">
            <p>Ehhez az ételhez még nincsenek hozzávalók hozzáadva.</p>
          </div>
          
          <div v-else class="ingredients-list">
            <div class="ingredients-summary">
              <p><strong>Összesen {{ selectedMeal.ingredients.length }} hozzávaló</strong></p>
            </div>
            
            <div class="ingredients-table-container">
              <table class="ingredients-table">
                <thead>
                  <tr>
                    <th>Hozzávaló</th>
                    <th>Típus</th>
                    <th>Mennyiség</th>
                    <th>Kalória (össz)</th>
                    <th>Fehérje</th>
                    <th>Szénhidrát</th>
                    <th>Zsír</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ingredient in selectedMeal.ingredients" :key="ingredient.id">
                    <td>{{ ingredient.name }}</td>
                    <td>
                      <span class="ingredient-type" :class="getIngredientTypeClass(ingredient.ingredientType)">
                        {{ ingredient.ingredientType }}
                      </span>
                    </td>
                    <td>
                      <strong>{{ ingredient.pivot?.amount || 0 }} {{ ingredient.pivot?.unit || 'g' }}</strong>
                    </td>
                    <td>{{ calculateIngredientCalories(ingredient) }} kcal</td>
                    <td>{{ calculateIngredientProtein(ingredient) }}g</td>
                    <td>{{ calculateIngredientCarbohydrate(ingredient) }}g</td>
                    <td>{{ calculateIngredientFat(ingredient) }}g</td>
                  </tr>
                </tbody>
                <tfoot v-if="selectedMeal.ingredients.length > 0">
                  <tr class="total-row">
                    <td colspan="2"><strong>Összesen (kb.)</strong></td>
                    <td>{{ calculateTotalAmount() }}</td>
                    <td><strong>{{ calculateTotalCalories() }} kcal</strong></td>
                    <td>{{ calculateTotalProtein() }}g</td>
                    <td>{{ calculateTotalCarbohydrate() }}g</td>
                    <td>{{ calculateTotalFat() }}g</td>
                  </tr>
                </tfoot>
              </table>
            </div>
            
            <!-- Tápanyag összegzés -->
            <div class="nutrition-summary">
              <h4>Tápanyag összegzés (hozzávetőleges)</h4>
              <div class="nutrition-bars">
                <div class="nutrition-bar">
                  <div class="bar-label">Kalória</div>
                  <div class="bar-container">
                    <div class="bar" :style="{ width: getCaloriesPercentage() + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ calculateTotalCalories() }} kcal</div>
                </div>
                
                <div class="nutrition-bar">
                  <div class="bar-label">Fehérje</div>
                  <div class="bar-container">
                    <div class="bar protein" :style="{ width: getProteinPercentage() + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ calculateTotalProtein() }}g</div>
                </div>
                
                <div class="nutrition-bar">
                  <div class="bar-label">Szénhidrát</div>
                  <div class="bar-container">
                    <div class="bar carb" :style="{ width: getCarbPercentage() + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ calculateTotalCarbohydrate() }}g</div>
                </div>
                
                <div class="nutrition-bar">
                  <div class="bar-label">Zsír</div>
                  <div class="bar-container">
                    <div class="bar fat" :style="{ width: getFatPercentage() + '%' }"></div>
                  </div>
                  <div class="bar-value">{{ calculateTotalFat() }}g</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-actions">
          <button @click="closeIngredientsModal" class="btn-cancel">
            Bezárás
          </button>
          <button v-if="canEdit" @click="editIngredients(selectedMeal)" class="btn-edit">
            Összetevők szerkesztése
          </button>
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
      meals: [],
      loading: false,
      error: '',
      search: '',
      selectedCategory: '',
      
      showAddModal: false,
      editingMeal: null,
      showIngredientsModal: false,
      selectedMeal: null,
      loadingIngredients: false,
      ingredientsError: '',
      
      mealForm: {
        name: '',
        category: '',
        description: ''
      }
    }
  },
  
  computed: {
    // Computed property a jogosultságok ellenőrzésére
    canEdit() {
      return AuthService.isKitchen() || AuthService.isAdmin()
    }
  },
  
  mounted() {
    this.checkAuthAndFetch()
  },
  
  methods: {
    checkAuthAndFetch() {
      if (!AuthService.isAuthenticated()) {
        this.$router.push('/login')
        return
      }
      
      if (!this.canEdit) {
        this.error = 'Nincs megfelelő jogosultságod az ételek kezeléséhez'
        this.$router.push('/dashboard')
        return
      }
      
      this.fetchMeals()
    },
    
    async fetchMeals() {
      this.loading = true
      this.error = ''
      
      try {
        const params = {}
        if (this.search) params.search = this.search
        if (this.selectedCategory) params.category = this.selectedCategory
        
        console.log('Fetching meals with AuthService:', {
          isAuthenticated: AuthService.isAuthenticated(),
          userRole: AuthService.getUserRole(),
          tokenExists: !!AuthService.getToken()
        })
        
        const response = await AuthService.api.get('/kitchen/meals', { params })
        
        console.log('Meals response:', response.data)
        
        if (response.data.success) {
          this.meals = response.data.meals || []
        } else {
          this.error = response.data.message || 'Hiba történt'
        }
        
      } catch (error) {
        console.error('Ételek betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'Hiba történt az ételek betöltésekor'
        
        if (error.response?.status === 401) {
          AuthService.clearAuth()
          this.$router.push('/login')
        } else if (error.response?.status === 403) {
          this.$router.push('/dashboard')
        }
      } finally {
        this.loading = false
      }
    },
    
     async viewIngredients(meal) {
      this.selectedMeal = meal
      this.showIngredientsModal = true
      this.loadingIngredients = true
      this.ingredientsError = ''
      
      try {
        console.log('Loading ingredients for meal:', {
          id: meal.id,
          name: meal.name,
          hasIngredients: meal.ingredients && meal.ingredients.length > 0
        })
        
        // Ha nincsenek betöltve az összetevők, betöltjük őket
        if (!meal.ingredients || meal.ingredients.length === 0) {
          console.log('Fetching ingredients from API...')
          
          const response = await AuthService.api.get(`/kitchen/meals/${meal.id}/ingredients`)
          
          console.log('API Response:', {
            success: response.data.success,
            ingredientsCount: response.data.ingredients?.length || 0
          })
          
          if (response.data.success) {
            // Ellenőrizzük és formázzuk az adatokat
            this.selectedMeal.ingredients = (response.data.ingredients || []).map(ingredient => {
              // Biztosítjuk, hogy minden mező létezik
              return {
                id: ingredient.id || 0,
                name: ingredient.name || 'Ismeretlen',
                ingredientType: ingredient.ingredientType || 'Egyéb',
                energy: parseFloat(ingredient.energy) || 0,
                protein: parseFloat(ingredient.protein) || 0,
                carbohydrate: parseFloat(ingredient.carbohydrate) || 0,
                fat: parseFloat(ingredient.fat) || 0,
                pivot: {
                  amount: parseFloat(ingredient.pivot?.amount) || 0,
                  unit: ingredient.pivot?.unit || 'g'
                }
              }
            })
            
            console.log('Formatted ingredients:', this.selectedMeal.ingredients)
          } else {
            this.ingredientsError = response.data.message || 'Nem sikerült betölteni az összetevőket'
          }
        } else {
          console.log('Using existing ingredients:', meal.ingredients.length)
          // Ellenőrizzük a meglévő adatokat
          this.selectedMeal.ingredients = meal.ingredients.map(ingredient => ({
            ...ingredient,
            pivot: {
              amount: parseFloat(ingredient.pivot?.amount) || 0,
              unit: ingredient.pivot?.unit || 'g'
            }
          }))
        }
      } catch (error) {
        console.error('Összetevők betöltése sikertelen:', {
          message: error.message,
          status: error.response?.status,
          data: error.response?.data
        })
        
        this.ingredientsError = 'Nem sikerült betölteni az összetevőket'
        

        this.selectedMeal.ingredients = [
          {
            id: 1,
            name: 'Teszt összetevő 1',
            ingredientType: 'Zöldség',
            energy: 50,
            protein: 2,
            carbohydrate: 10,
            fat: 1,
            pivot: { amount: 100, unit: 'g' }
          },
          {
            id: 2,
            name: 'Teszt összetevő 2',
            ingredientType: 'Hús',
            energy: 200,
            protein: 25,
            carbohydrate: 0,
            fat: 12,
            pivot: { amount: 150, unit: 'g' }
          }
        ]
        
        console.log('Tesztadatok')
      } finally {
        this.loadingIngredients = false
      }
    },
    
    closeIngredientsModal() {
      this.showIngredientsModal = false
      this.selectedMeal = null
      this.ingredientsError = ''
    },
    
    editMeal(meal) {
      this.editingMeal = meal
      this.mealForm = { 
        name: meal.name,
        category: meal.category,
        description: meal.description
      }
      this.showAddModal = true
    },
    
    async saveMeal() {
      try {
        if (this.editingMeal) {
          const response = await AuthService.api.put(`/kitchen/meals/${this.editingMeal.id}`, this.mealForm)
          if (response.data.success) {
            this.$toast?.success('Étel sikeresen frissítve')
            this.closeModal()
            this.fetchMeals()
          } else {
            alert(response.data.message)
          }
        } else {
          const response = await AuthService.api.post('/kitchen/meals', this.mealForm)
          if (response.data.success) {
            this.$toast?.success('Étel sikeresen hozzáadva')
            this.closeModal()
            this.fetchMeals()
          } else {
            alert(response.data.message)
          }
        }
      } catch (error) {
        console.error('Étel mentése sikertelen:', error)
        const errorMessage = error.response?.data?.message || 
                           error.response?.data?.errors ? 
                           Object.values(error.response.data.errors).flat().join(', ') : 
                           'Hiba történt a mentés során'
        alert(errorMessage)
      }
    },
    
    async deleteMeal(mealId) {
      if (!confirm('Biztosan törölni szeretné ezt az ételt?')) {
        return
      }
      
      try {
        const response = await AuthService.api.delete(`/kitchen/meals/${mealId}`)
        
        if (response.data.success) {
          this.meals = this.meals.filter(meal => meal.id !== mealId)
          this.$toast?.success('Étel sikeresen törölve')
        } else {
          alert(response.data.message)
        }
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
        description: ''
      }
    },
    
    editIngredients(meal) {
      alert(`Összetevők szerkesztése: ${meal.name} - Ez a funkció hamarosan elérhető lesz.`)
    },
    
    // Segédfüggvények
    getIngredientTypeClass(type) {
      if (!type) return 'type-other'
      
      const classes = {
        'Hús': 'type-meat',
        'Tejtermék': 'type-dairy',
        'Zöldség': 'type-vegetable',
        'Egyéb': 'type-other'
      }
      return classes[type] || 'type-other'
    },
    
    calculateIngredientCalories(ingredient) {
      if (!ingredient || typeof ingredient.energy === 'undefined') return 0
      
      const amount = ingredient.pivot?.amount
      const caloriesPer100g = ingredient.energy || 0
      
      // Konvertáljuk számmá
      const numericAmount = parseFloat(amount) || 0
      const numericCalories = parseFloat(caloriesPer100g) || 0
      
      return Math.round((numericAmount / 100) * numericCalories)
    },
    
    calculateIngredientProtein(ingredient) {
      if (!ingredient || typeof ingredient.protein === 'undefined') return '0'
      
      const amount = ingredient.pivot?.amount
      const proteinPer100g = ingredient.protein || 0
      
      const numericAmount = parseFloat(amount) || 0
      const numericProtein = parseFloat(proteinPer100g) || 0
      
      return ((numericAmount / 100) * numericProtein).toFixed(1)
    },
    
    calculateIngredientCarbohydrate(ingredient) {
      if (!ingredient || typeof ingredient.carbohydrate === 'undefined') return '0'
      
      const amount = ingredient.pivot?.amount
      const carbPer100g = ingredient.carbohydrate || 0
      
      const numericAmount = parseFloat(amount) || 0
      const numericCarb = parseFloat(carbPer100g) || 0
      
      return ((numericAmount / 100) * numericCarb).toFixed(1)
    },
    
    calculateIngredientFat(ingredient) {
      if (!ingredient || typeof ingredient.fat === 'undefined') return '0'
      
      const amount = ingredient.pivot?.amount
      const fatPer100g = ingredient.fat || 0
      
      const numericAmount = parseFloat(amount) || 0
      const numericFat = parseFloat(fatPer100g) || 0
      
      return ((numericAmount / 100) * numericFat).toFixed(1)
    },
    
    calculateTotalAmount() {
      if (!this.selectedMeal?.ingredients) return '0 g'
      
      const totalAmount = this.selectedMeal.ingredients.reduce((sum, ingredient) => {
        const amount = ingredient.pivot?.amount
        // Konvertáljuk számmá, ha string
        const numericAmount = parseFloat(amount) || 0
        return sum + numericAmount
      }, 0)
      
      // Ellenőrizzük, hogy szám-e
      if (typeof totalAmount !== 'number' || isNaN(totalAmount)) {
        return '0 g'
      }
      
      return totalAmount.toFixed(0) + ' g'
    },
    
    calculateTotalCalories() {
      if (!this.selectedMeal?.ingredients) return 0
      
      const totalCalories = this.selectedMeal.ingredients.reduce((sum, ingredient) => {
        const calories = this.calculateIngredientCalories(ingredient)
        return sum + (parseFloat(calories) || 0)
      }, 0)
      
      return Math.round(totalCalories)
    },
    
    calculateTotalProtein() {
      if (!this.selectedMeal?.ingredients) return '0'
      
      const totalProtein = this.selectedMeal.ingredients.reduce((sum, ingredient) => {
        const protein = this.calculateIngredientProtein(ingredient)
        return sum + (parseFloat(protein) || 0)
      }, 0)
      
      return parseFloat(totalProtein).toFixed(1)
    },
    
    calculateTotalCarbohydrate() {
      if (!this.selectedMeal?.ingredients) return '0'
      
      const totalCarb = this.selectedMeal.ingredients.reduce((sum, ingredient) => {
        const carb = this.calculateIngredientCarbohydrate(ingredient)
        return sum + (parseFloat(carb) || 0)
      }, 0)
      
      return parseFloat(totalCarb).toFixed(1)
    },
    
    calculateTotalFat() {
      if (!this.selectedMeal?.ingredients) return '0'
      
      const totalFat = this.selectedMeal.ingredients.reduce((sum, ingredient) => {
        const fat = this.calculateIngredientFat(ingredient)
        return sum + (parseFloat(fat) || 0)
      }, 0)
      
      return parseFloat(totalFat).toFixed(1)
    },
    
    getCaloriesPercentage() {
      const total = this.calculateTotalCalories()
      if (typeof total !== 'number' || isNaN(total)) return 0
      return Math.min((total / 2000) * 100, 100)
    },
    
    getProteinPercentage() {
      const total = parseFloat(this.calculateTotalProtein() || 0)
      if (isNaN(total)) return 0
      return Math.min((total / 50) * 100, 100)
    },
    
    getCarbPercentage() {
      const total = parseFloat(this.calculateTotalCarbohydrate() || 0)
      if (isNaN(total)) return 0
      return Math.min((total / 300) * 100, 100)
    },
    
    getFatPercentage() {
      const total = parseFloat(this.calculateTotalFat() || 0)
      if (isNaN(total)) return 0
      return Math.min((total / 70) * 100, 100)
    },
  }
}
</script>


<style scoped>
button{
  width: 20%;
}

/* Alap stílusok */
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

/* Étel kártyák */
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

/* Összetevők előnézet */
.ingredients-preview {
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 4px;
}

.ingredients-preview strong {
  display: block;
  margin-bottom: 0.5rem;
  color: #2c3e50;
}

.preview-items {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.preview-item {
  background: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  border: 1px solid #e0e0e0;
}

.more-items {
  color: #6c757d;
  font-size: 0.75rem;
  font-style: italic;
}

/* Gombok */
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
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.btn-edit {
  background: #3498db;
  color: white;
}

.btn-edit:hover {
  background: #2980b9;
}

.btn-view {
  background: #2ecc71;
  color: white;
}

.btn-view:hover {
  background: #27ae60;
}

.btn-delete {
  background: #e74c3c;
  color: white;
}

.btn-delete:hover {
  background: #c0392b;
}

/* Modal stílusok */
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
}

.modal {
  padding: 20px;
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
}

.ingredients-modal {
  max-width: 1000px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  margin: 0;
  color: #2c3e50;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #7f8c8d;
  width: 20px;
}

.close-btn:hover {
  color: #e74c3c;
}

/* Form stílusok */
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

/* Összetevők táblázat */
.ingredients-content {
  margin: 1.5rem 0;
}

.no-ingredients {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
}

.ingredients-summary {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.ingredients-table-container {
  overflow-x: auto;
  margin-bottom: 2rem;
}

.ingredients-table {
  width: 100%;
  border-collapse: collapse;
}

.ingredients-table th,
.ingredients-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.ingredients-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.ingredients-table tbody tr:hover {
  background: #f8f9fa;
}

.total-row {
  background: #f8f9fa !important;
  font-weight: 600;
}

.ingredient-type {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.type-meat {
  background: #ffebee;
  color: #c62828;
}

.type-dairy {
  background: #e3f2fd;
  color: #1565c0;
}

.type-vegetable {
  background: #e8f5e9;
  color: #2e7d32;
}

.type-other {
  background: #f5f5f5;
  color: #616161;
}

/* Tápanyag összegzés */
.nutrition-summary {
  margin-top: 2rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.nutrition-summary h4 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
}

.nutrition-bars {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.nutrition-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.bar-label {
  width: 100px;
  font-weight: 500;
  color: #2c3e50;
}

.bar-container {
  flex: 1;
  height: 10px;
  background: #e0e0e0;
  border-radius: 5px;
  overflow: hidden;
}

.bar {
  height: 100%;
  background: #3498db;
  transition: width 0.3s ease;
}

.bar.protein {
  background: #2ecc71;
}

.bar.carb {
  background: #f39c12;
}

.bar.fat {
  background: #e74c3c;
}

.bar-value {
  width: 80px;
  text-align: right;
  font-weight: 500;
  color: #2c3e50;
}

/* Általános stílusok */
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