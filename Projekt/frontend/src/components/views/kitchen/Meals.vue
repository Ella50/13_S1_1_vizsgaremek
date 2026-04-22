<template>
  <div class="meals-management">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Ételek {{ canEdit ? 'kezelése' : 'listája' }}</h1>
        
        <div class="header-controls">
          <div class="search-box">
            <input 
              v-model="search" 
              @input="onSearchInput"
              placeholder="Keresés név alapján..."
              max="50"
              class="search-input"
            >
            <span class="search-icon">🔍</span>
          </div>

            <select v-model="selectedCategory" @change="fetchMeals" class="filter-select">
              <option value="">Összes kategória</option>
              <option value="Leves">Leves</option>
              <option value="Főétel">Főétel</option>
              <option value="Egyéb">Egyéb</option>
            </select>

          <button v-if="canEdit" @click="openCreateModal" class="btn-primary">
            + Új étel
          </button>
        </div>

    </div>

      

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Ételek betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">{{ error }}</div>

      <div v-else>
        <div v-if="meals.length === 0" class="empty-state">
          <p>Nincs találat</p>
          <button v-if="canEdit" @click="openCreateModal" class="btn-primary">
            + Adjon hozzá ételt
          </button>

        </div>

        <div v-else class="meals-grid">
          <div v-for="meal in meals" :key="meal?.id || 'unknown'" class="meal-card">
            <div class="meal-header">
              <h3>{{ meal?.mealName || 'Névtelen étel' }}</h3>
              <span class="meal-category">{{ meal?.category || 'Egyéb' }}</span>
            </div>
            
            <p class="meal-description">{{ meal?.description || 'Nincs leírás' }}</p>
            
            <!-- Összetevők előnézet -->
            <div v-if="meal?.ingredients && meal.ingredients.length" class="ingredients-preview">
              <strong>Összetevők:</strong>
              <div class="preview-items">
                <span 
                  v-for="(ingredient, index) in meal.ingredients.slice(0, 3)" 
                  :key="ingredient?.id || index" 
                  class="preview-item"
                >
                  {{ ingredient?.ingredientName || 'Ismeretlen' }}
                  <span v-if="ingredient?.pivot"> ({{ ingredient.pivot.amount }}{{ ingredient.pivot.unit }})</span>
                </span>
                <span v-if="meal.ingredients.length > 3" class="more-items">
                  +{{ meal.ingredients.length - 3 }} további
                </span>
              </div>
            </div>
            
            <!-- Allergének megjelenítése -->
            <div v-if="meal?.allergens && meal.allergens.length > 0" class="allergens-section">
              <div class="allergens-header">
                <strong>Allergének:</strong>
              </div>
              <div class="allergens-list">
                <template v-for="allergen in getUniqueAllergens(meal.allergens)" :key="allergen?.id || 'unknown'">
                  <span class="allergen-tag" :title="allergen?.allergenName || 'Ismeretlen allergén'">
                    <img 
                      v-if="allergen?.icon" 
                      :src="getAllergenIconUrl(allergen.icon)" 
                      :alt="allergen?.allergenName || 'Allergén'"
                      class="allergen-icon-img"
                      @error="handleImageError"
                    />
                    <span v-else class="allergen-icon-fallback">{{ getAllergenInitial(allergen?.allergenName) }}</span>
                  </span>
                </template>
              </div>
            </div>
            
            <div v-else class="allergens-section safe">
              <span class="safe-text">Allergénmentes</span>
            </div>
            
            <div class="meal-actions">
              <button v-if="canEdit" @click="editMeal(meal)" class="btn-action btn-edit">Szerkesztés</button>
              <button @click="viewIngredients(meal)" class="btn-action btn-view">
                {{ canEdit ? 'Összetevők' : 'Tápérték információk' }}
              </button>
              <button v-if="canEdit" @click="deleteMeal(meal?.id)" class="btn-action btn-delete">Törlés</button>
            </div>
          </div>
        </div>
          <Pagination
            :current-page="currentPage"
            :last-page="lastPage"
            :total="total"
            :per-page="perPage"
            :per-page-options="[6, 12, 24, 48]"
            @update:page="changePage"
            @update:perPage="changePerPage"
          />
      </div>
    </div>

    <!-- Új étel modal -->
    <div v-if="showAddModal && !editingMeal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Új étel hozzáadása</h2>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveMeal">
            <div class="form-group">
              <label>Étel neve *</label>
              <input v-model="mealForm.mealName" required maxlength="255" />
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
            
            <!-- Összetevők szerkesztése -->
            <div class="ingredients-section">
              <div class="section-header">
                <h3>Összetevők</h3>
              </div>
              
              <div class="add-ingredient-section">
                <div class="add-ingredient-form">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Hozzávaló *</label>
                      <div class="search-container">
                        <input 
                          v-model="newIngredient.search" 
                          @input="searchIngredients"
                          placeholder="Keresés"
                          type="search"
                          class="search-input-ingredient"
                        />
                        <div v-if="searchResults.length > 0" class="search-results">
                          <div 
                            v-for="result in searchResults" 
                            :key="result.id"
                            class="search-result-item"
                            @click="selectIngredient(result)"
                          >
                            <span class="result-name">{{ result.ingredientName }}</span>
                            <span class="result-type">{{ result.ingredientType }}</span>
                            <span class="result-info">{{ result.energy }} kcal</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <label>Mennyiség *</label>
                      <input 
                        v-model="newIngredient.amount" 
                        type="number" 
                        step="0.1" 
                        min="0"
                        placeholder="100"
                        class="amount-input"
                      />
                    </div>
                    
                    <div class="form-group">
                      <label>Mértékegység *</label>
                      <select v-model="newIngredient.unit" class="unit-select">
                        <option value="g">g</option>
                        <option value="kg">kg</option>
                        <option value="ml">ml</option>
                        <option value="l">l</option>
                        <option value="db">db</option>
                      </select>
                    </div>
                    
                    <div class="form-group">
                      <label>Új összetevő</label>
                      <button 
                        type="button" 
                        @click="addIngredient" 
                        class="btn-add-ingredient"
                        :disabled="!canAddIngredient"
                      >
                        Hozzáadás
                      </button>
                    </div>
                  </div>
                  
                  <div v-if="selectedIngredientForAdd" class="selected-ingredient-info">
                    <strong>Kiválasztott hozzávaló:</strong> {{ selectedIngredientForAdd.ingredientName }}
                    <span class="ingredient-details">
                      {{ selectedIngredientForAdd.energy }} kcal/100g | 
                      {{ selectedIngredientForAdd.protein }}g fehérje | 
                      {{ selectedIngredientForAdd.carbohydrate }}g szénhidrát
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="current-ingredients-section">
                <h4>Jelenlegi hozzávalók</h4>
                
                <div v-if="editIngredientsList.length === 0" class="no-current-ingredients">
                  <p>Még nincsenek hozzávalók hozzáadva.</p>
                </div>
                
                <div v-else class="current-ingredients-list">
                  <div class="current-ingredients-table-container">
                    <table class="current-ingredients-table">
                      <thead>
                        <tr>
                          <th>Hozzávaló</th>
                          <th>Mennyiség</th>
                          <th>Egység</th>
                          <th>Kalória</th>
                          <th>Törlés</th>
                         </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(ingredient, index) in editIngredientsList" :key="ingredient.id || ingredient.tempId">
                          <td>
                            <div class="ingredient-name">
                              {{ ingredient.ingredientName }}
                              <div class="ingredient-nutrition">
                                <small class="ingredient-type" :class="getIngredientTypeClass(ingredient.ingredientType)">
                                  {{ ingredient.ingredientType }}
                                </small>
                              </div>
                            </div>
                          </td>
                          <td>
                            <input 
                              v-model="ingredient.pivot.amount" 
                              type="number" 
                              step="0.1" 
                              min="0"
                              @change="updateIngredientAmount(ingredient)"
                              class="amount-input small"
                            />
                          </td>
                          <td>
                            <select 
                              v-model="ingredient.pivot.unit"
                              @change="updateIngredientUnit(ingredient)"
                              class="unit-select small"
                            >
                              <option value="g">g</option>
                              <option value="kg">kg</option>
                              <option value="ml">ml</option>
                              <option value="l">l</option>
                              <option value="db">db</option>
                            </select>
                          </td>
                          <td>
                            <span class="calories-display">
                              {{ calculateIngredientCalories(ingredient) }} kcal
                            </span>
                          </td>
                          <td>
                            <button 
                              @click="removeIngredientFromList(index)" 
                              class="btn-remove-ingredient"
                              title="Eltávolítás"
                            >
                              ✕
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  
                  <div class="edit-summary">
                    <div class="summary-item">
                      <div class="summary-label">Összes hozzávaló:</div>
                      <div class="summary-value">{{ editIngredientsList.length }} db</div>
                    </div>
                    <div class="summary-item">
                      <div class="summary-label">Teljes mennyiség:</div>
                      <div class="summary-value">{{ calculateEditTotalAmount() }}</div>
                    </div>
                    <div class="summary-item">
                      <div class="summary-label">Teljes kalória:</div>
                      <div class="summary-value">{{ calculateEditTotalCalories() }} kcal</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-cancel">Mégse</button>
              <button type="submit" class="btn-save" :disabled="savingMeal">
                {{ savingMeal ? 'Mentés...' : 'Mentés' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Ételszerkesztés modal -->
    <div v-if="showAddModal && editingMeal" class="modal-overlay" @click.self="closeModal">
      <div class="modal edit-meal-modal">
        <div class="modal-header">
          <h2>{{ editingMeal?.mealName || 'Étel' }} - Szerkesztése</h2>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveMeal">
            <div class="basic-info-section">
              <div class="form-row">
                <div class="form-group">
                  <label>Étel neve *</label>
                  <input v-model="mealForm.mealName" required maxlength="255" />
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
              </div>
              
              <div class="form-group">
                <label>Leírás</label>
                <textarea v-model="mealForm.description" rows="2"></textarea>
              </div>
            </div>
            
            <div class="ingredients-section">
              <div class="section-header">
                <h3>Összetevők</h3>

              </div>
              
              <div class="add-ingredient-section">
                <div class="add-ingredient-form">
                  <div class="form-row">
                    <div class="form-group">
                      <label>Hozzávaló *</label>
                      <div class="search-container">
                        <input 
                          v-model="newIngredient.search" 
                          @input="searchIngredients"
                          placeholder="Keresés"
                          type="search"
                          class="search-input-ingredient"
                        />
                        <div v-if="searchResults.length > 0" class="search-results">
                          <div 
                            v-for="result in searchResults" 
                            :key="result.id"
                            class="search-result-item"
                            @click="selectIngredient(result)"
                          >
                            <span class="result-name">{{ result.ingredientName }}</span>
                            <span class="result-type">{{ result.ingredientType }}</span>
                            <span class="result-info">{{ result.energy }} kcal</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <label>Mennyiség *</label>
                      <input 
                        v-model="newIngredient.amount" 
                        type="number" 
                        step="0.1" 
                        min="0"
                        placeholder="100"
                        class="amount-input"
                      />
                    </div>
                    
                    <div class="form-group">
                      <label>Mértékegység *</label>
                      <select v-model="newIngredient.unit" class="unit-select">
                        <option value="g">g</option>
                        <option value="kg">kg</option>
                        <option value="ml">ml</option>
                        <option value="l">l</option>
                        <option value="db">db</option>
                      </select>
                    </div>
                    
                    <div class="form-group">
                      <label>Új összetevő</label>
                      <button 
                        type="button" 
                        @click="addIngredient" 
                        class="btn-add-ingredient"
                        :disabled="!canAddIngredient"
                      >
                        Hozzáadás
                      </button>
                    </div>
                  </div>
                  
                  <div v-if="selectedIngredientForAdd" class="selected-ingredient-info">
                    <strong>Kiválasztott hozzávaló:</strong> {{ selectedIngredientForAdd.ingredientName }}
                    <span class="ingredient-details">
                      {{ selectedIngredientForAdd.energy }} kcal/100g | 
                      {{ selectedIngredientForAdd.protein }}g fehérje | 
                      {{ selectedIngredientForAdd.carbohydrate }}g szénhidrát
                    </span>
                  </div>
                </div>
              </div>
              
              <div class="current-ingredients-section">
                <h4>Jelenlegi hozzávalók</h4>
                
                <div v-if="editIngredientsList.length === 0" class="no-current-ingredients">
                  <p>Még nincsenek hozzávalók hozzáadva.</p>
                </div>
                
                <div v-else class="current-ingredients-list">
                  <div class="current-ingredients-table-container">
                    <table class="current-ingredients-table">
                      <thead>
                        <tr>
                          <th>Hozzávaló</th>
                          <th>Mennyiség</th>
                          <th>Egység</th>
                          <th>Kalória</th>
                          <th>Törlés</th>
                         </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(ingredient, index) in editIngredientsList" :key="ingredient.id || ingredient.tempId">
                          <td>
                            <div class="ingredient-name">
                              {{ ingredient.ingredientName }}
                              <div class="ingredient-nutrition">
                                <small class="ingredient-type" :class="getIngredientTypeClass(ingredient.ingredientType)">
                                  {{ ingredient.ingredientType }}
                                </small>
                              </div>
                            </div>
                          </td>
                          <td>
                            <input 
                              v-model="ingredient.pivot.amount" 
                              type="number" 
                              step="0.1" 
                              min="0"
                              @change="updateIngredientAmount(ingredient)"
                              class="amount-input small"
                            />
                          </td>
                          <td>
                            <select 
                              v-model="ingredient.pivot.unit"
                              @change="updateIngredientUnit(ingredient)"
                              class="unit-select small"
                            >
                              <option value="g">g</option>
                              <option value="kg">kg</option>
                              <option value="ml">ml</option>
                              <option value="l">l</option>
                              <option value="db">db</option>
                            </select>
                          </td>
                          <td>
                            <span class="calories-display">
                              {{ calculateIngredientCalories(ingredient) }} kcal
                            </span>
                          </td>
                          <td>
                            <button 
                              @click="removeIngredientFromList(index)" 
                              class="btn-remove-ingredient"
                              title="Eltávolítás"
                            >
                              ✕
                            </button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  
                  <div class="edit-summary">
                    <div class="summary-item">
                      <div class="summary-label">Összes hozzávaló:</div>
                      <div class="summary-value">{{ editIngredientsList.length }} db</div>
                    </div>
                    <div class="summary-item">
                      <div class="summary-label">Teljes mennyiség:</div>
                      <div class="summary-value">{{ calculateEditTotalAmount() }}</div>
                    </div>
                    <div class="summary-item">
                      <div class="summary-label">Teljes kalória:</div>
                      <div class="summary-value">{{ calculateEditTotalCalories() }} kcal</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-cancel">Mégse</button>
              <button type="submit" class="btn-save" :disabled="savingMeal">
                {{ savingMeal ? 'Mentés...' : 'Mentés' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    <!-- Összetevők megtekintése modal -->
    <div v-if="showIngredientsModal && selectedMeal" class="modal-overlay" @click.self="closeIngredientsModal">
      <div class="modal ingredients-modal">
        <div class="modal-header">
          <h2>{{ selectedMeal?.mealName || 'Étel' }} - {{ canEdit ? 'Összetevők' : 'Tápérték információk' }}</h2>
          <button @click="closeIngredientsModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <div v-if="loadingIngredients" class="loading-state">Betöltés...</div>
          <div v-else-if="ingredientsError" class="error-message">{{ ingredientsError }}</div>
          
          <div v-else>
            <div v-if="mealAllergens.length > 0" class="allergen-warning">
              <div class="warning-header">
                <h4>Allergének</h4>
              </div>
              <div class="allergens-detail-list">
                <div v-for="allergen in mealAllergens" :key="allergen.id" class="allergen-detail">
                  <img 
                    v-if="allergen.icon" 
                    :src="getAllergenIconUrl(allergen.icon)" 
                    :alt="allergen.allergenName"
                    class="allergen-detail-icon"
                  />
                  <span class="allergen-detail-name">{{ allergen.allergenName }}</span>
                </div>
              </div>
            </div>
            
            <div v-if="!selectedMeal?.ingredients || selectedMeal.ingredients.length === 0" class="no-ingredients">
              <p class="text-center">Ehhez az ételhez még nincsenek hozzávalók hozzáadva.</p>
            </div>
            
            <div v-else>
              <div class="ingredients-table-container">
                <table class="ingredients-table">
                  <thead>
                    <tr>
                      <th>Hozzávaló</th>
                      <th>Típus</th>
                      <th>Allergének</th>
                      <th>Mennyiség</th>
                      <th>Kalória</th>
                      <th>Fehérje</th>
                      <th>Szénhidrát</th>
                      <th>Zsír</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="ingredient in selectedMeal.ingredients" :key="ingredient.id">
                      <td>{{ ingredient.ingredientName }}</td>
                      <td>
                        <span class="ingredient-type" :class="getIngredientTypeClass(ingredient.ingredientType)">
                          {{ ingredient.ingredientType }}
                        </span>
                      </td>
                      <td>
                        <div v-if="ingredient.allergens && ingredient.allergens.length > 0" class="ingredient-allergens">
                          <span 
                            v-for="allergen in ingredient.allergens" 
                            :key="allergen.id"
                            class="allergen-chip"
                            :title="allergen.allergenName"
                          >
                            <img 
                              v-if="allergen.icon" 
                              :src="getAllergenIconUrl(allergen.icon)" 
                              :alt="allergen.allergenName"
                              class="allergen-chip-icon"
                            />
                          </span>
                        </div>
                        <span v-else class="no-allergens">-</span>
                      </td>
                      <td><strong>{{ ingredient.pivot?.amount || 0 }} {{ ingredient.pivot?.unit || 'g' }}</strong></td>
                      <td>{{ calculateIngredientCalories(ingredient) }} kcal</td>
                      <td>{{ calculateIngredientProtein(ingredient) }}g</td>
                      <td>{{ calculateIngredientCarbohydrate(ingredient) }}g</td>
                      <td>{{ calculateIngredientFat(ingredient) }}g</td>
                    </tr>
                  </tbody>
                  <tfoot v-if="selectedMeal.ingredients.length > 0">
                    <tr class="total-row">
                      <td colspan="3"><strong>Összesen (kb.)</strong></td>
                      <td>{{ calculateTotalAmount() }}</td>
                      <td><strong>{{ calculateTotalCalories() }} kcal</strong></td>
                      <td>{{ calculateTotalProtein() }}g</td>
                      <td>{{ calculateTotalCarbohydrate() }}g</td>
                      <td>{{ calculateTotalFat() }}g</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              
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
        </div>
        
        <div class="modal-footer">
          <button @click="closeIngredientsModal" class="btn-cancel">Bezárás</button>
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
import AuthService from '../../../services/authService'

import Pagination from '../../layout/Pagination.vue'

export default {

  components: { Pagination },
  data() {
    return {
      meals: [],
      loading: false,
      error: '',
      search: '',
      searchTimeout: null,
      selectedCategory: '',
      
      showAddModal: false,
      editingMeal: null,
      showIngredientsModal: false,
      selectedMeal: null,
      loadingIngredients: false,
      ingredientsError: '',
      savingMeal: false,
      
      editIngredientsList: [],
      
      newIngredient: {
        search: '',
        amount: '',
        unit: 'g'
      },
      searchResults: [],
      selectedIngredientForAdd: null,
      allIngredients: [],

      mealAllergens: [],
      
      mealForm: {
        mealName: '',
        category: '',
        description: ''
      },
      
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
      confirmResolver: null,

      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 12,
    }
  },
  
  computed: {
    canEdit() {
      return AuthService.isKitchen()
    },
    
    canAddIngredient() {
      return this.selectedIngredientForAdd && 
             this.newIngredient.amount && 
             parseFloat(this.newIngredient.amount) > 0
    }
  },
  
  mounted() {
    this.checkAuthAndFetch()
    if (this.canEdit) {
      this.loadAllIngredients()
    }
  },
  
  beforeDestroy() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout)
    if (this.alertTimeout) clearTimeout(this.alertTimeout)
    document.body.style.overflow = ''
  },
  
  methods: {
    // Alert metódusok
    showAlert({ message, type = 'success', title = '' }) {
      if (this.alertTimeout) {
        clearTimeout(this.alertTimeout)
      }
      
      this.alertMessage = message
      this.alertType = type
      this.alertTitle = title
      this.alertVisible = true
      
      this.alertTimeout = setTimeout(() => {
        this.alertVisible = false
      }, 3000)
    },
    
    // Confirm metódusok
    showConfirm({ message, title = '' }) {
      this.confirmMessage = message
      this.confirmTitle = title
      this.confirmVisible = true
      
      return new Promise((resolve) => {
        this.confirmResolver = resolve
      })
    },
    
    confirmOk() {
      this.confirmVisible = false
      if (this.confirmResolver) {
        this.confirmResolver(true)
        this.confirmResolver = null
      }
    },
    
    confirmCancel() {
      this.confirmVisible = false
      if (this.confirmResolver) {
        this.confirmResolver(false)
        this.confirmResolver = null
      }
    },

    onSearchInput() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.fetchMeals()
      }, 300)
    },

    checkAuthAndFetch() {
      if (!AuthService.isAuthenticated()) {
        this.$router.push('/login')
        return
      }
      this.fetchMeals()
    },

    openCreateModal() {
      this.showAddModal = true
      document.body.style.overflow = 'hidden'
    },

    closeModal() {
      this.showAddModal = false
      this.editingMeal = null
      this.editIngredientsList = []
      document.body.style.overflow = ''
      this.resetForm()
    },

    closeIngredientsModal() {
      this.showIngredientsModal = false
      this.selectedMeal = null
      this.ingredientsError = ''
      this.mealAllergens = []
      document.body.style.overflow = ''
    },

    resetForm() {
      this.mealForm = {
        mealName: '',
        category: '',
        description: ''
      }
    },

    async fetchMeals() {
      this.loading = true
      this.error = ''
      
      try {
        const params = {
          page: this.currentPage,
          per_page: this.perPage,
          withAllergens: true,
          _t: Date.now()
        }
        if (this.search) params.search = this.search
        if (this.selectedCategory) params.category = this.selectedCategory
        
        const response = await AuthService.api.get('/kitchen/meals', { params })
        
        if (response.data.success) {
          this.meals = response.data.data || []
          this.currentPage = response.data.current_page || 1
          this.lastPage = response.data.last_page || 1
          this.total = response.data.total || 0
        } else {
          this.error = response.data.message || 'Hiba történt'
        }
      } catch (error) {
        console.error('Ételek betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'Hiba történt az ételek betöltésekor'
        this.showAlert({ message: this.error, type: 'error' })
        
        if (error.response?.status === 401) {
          AuthService.clearAuth()
          this.$router.push('/login')
        }
      } finally {
        this.loading = false
      }
    },

    changePage(page) {
      if (page < 1 || page > this.lastPage) return
      this.currentPage = page
      this.fetchMeals()
      window.scrollTo(0, 0)
    },
    
    changePerPage(newPerPage) {
      this.perPage = newPerPage
      this.currentPage = 1
      this.fetchMeals()
    },

    async viewIngredients(meal) {
      if (!meal || !meal.id) return
      
      this.selectedMeal = meal
      this.showIngredientsModal = true
      document.body.style.overflow = 'hidden'
      this.loadingIngredients = true
      this.ingredientsError = ''
      this.mealAllergens = []
      
      try {
        const response = await AuthService.api.get(`/kitchen/meals/${meal.id}/ingredients?withAllergens=true`)
        
        if (response.data.success) {
          this.selectedMeal.ingredients = (response.data.ingredients || []).map(ingredient => ({
            id: ingredient.id || 0,
            ingredientName: ingredient.ingredientName || 'Ismeretlen',
            ingredientType: ingredient.ingredientType || 'Egyéb',
            energy: parseFloat(ingredient.energy) || 0,
            protein: parseFloat(ingredient.protein) || 0,
            carbohydrate: parseFloat(ingredient.carbohydrate) || 0,
            fat: parseFloat(ingredient.fat) || 0,
            allergens: ingredient.allergens || [],
            pivot: {
              amount: parseFloat(ingredient.pivot?.amount) || 0,
              unit: ingredient.pivot?.unit || 'g'
            }
          }))
          this.collectAllergens()
        } else {
          this.ingredientsError = response.data.message || 'Nem sikerült betölteni az összetevőket'
        }
      } catch (error) {
        console.error('Összetevők betöltése sikertelen:', error)
        this.ingredientsError = 'Nem sikerült betölteni az összetevőket'
      } finally {
        this.loadingIngredients = false
      }
    },

    getAllergenIconUrl(iconPath) {
      if (!iconPath) return 'https://via.placeholder.com/16x16/3498db/ffffff?text=❓'
      const cleanPath = iconPath.replace(/^\//, '')
      const baseUrl = 'http://localhost:8000'
      return `${baseUrl}/images/allergens/${cleanPath.split('/').pop()}`
    },

    handleImageError(event) {
      event.target.style.display = 'none'
      const parent = event.target.parentElement
      if (parent) {
        const fallback = parent.querySelector('.allergen-icon-fallback')
        if (fallback) fallback.style.display = 'flex'
      }
    },

    getUniqueAllergens(allergens) {
      if (!allergens || !Array.isArray(allergens)) return []
      const seen = new Set()
      return allergens.filter(allergen => {
        if (!allergen || !allergen.id) return false
        if (seen.has(allergen.id)) return false
        seen.add(allergen.id)
        return true
      })
    },

    getAllergenInitial(name) {
      if (!name) return '?'
      return name.charAt(0).toUpperCase()
    },

    collectAllergens() {
      if (!this.selectedMeal?.ingredients) return
      const allergenMap = new Map()
      this.selectedMeal.ingredients.forEach(ingredient => {
        if (ingredient.allergens && Array.isArray(ingredient.allergens)) {
          ingredient.allergens.forEach(allergen => {
            if (!allergenMap.has(allergen.id)) {
              allergenMap.set(allergen.id, allergen)
            }
          })
        }
      })
      this.mealAllergens = Array.from(allergenMap.values())
    },

    async editMeal(meal) {
      if (!meal || !meal.id) return
      
      this.editingMeal = meal
      this.showAddModal = true
      document.body.style.overflow = 'hidden'
      
      this.mealForm = { 
        mealName: meal.mealName || '',
        category: meal.category || '',
        description: meal.description || ''
      }
      
      this.editIngredientsList = []
      
      try {
        const response = await AuthService.api.get(`/kitchen/meals/${meal.id}/ingredients`)
        if (response.data.success && response.data.ingredients) {
          this.editIngredientsList = response.data.ingredients.map(ingredient => ({
            id: ingredient.id,
            ingredientName: ingredient.ingredientName,
            ingredientType: ingredient.ingredientType,
            energy: parseFloat(ingredient.energy) || 0,
            protein: parseFloat(ingredient.protein) || 0,
            carbohydrate: parseFloat(ingredient.carbohydrate) || 0,
            fat: parseFloat(ingredient.fat) || 0,
            pivot: {
              amount: parseFloat(ingredient.pivot?.amount) || 0,
              unit: ingredient.pivot?.unit || 'g'
            }
          }))
        }
      } catch (error) {
        console.error('Hozzávalók betöltése sikertelen:', error)
      }
    },

    async saveMeal() {
      if (!this.editingMeal) {
        try {
          this.savingMeal = true;
          
          const mealResponse = await AuthService.api.post('/kitchen/meals', {
            mealName: this.mealForm.mealName,
            category: this.mealForm.category,
            description: this.mealForm.description
          });
          
          if (!mealResponse.data.success) {
            this.showAlert({
              message: mealResponse.data.message || 'Hiba történt',
              type: 'error'
            });
            this.savingMeal = false;
            return;
          }
          
          const newMealId = mealResponse.data.meal.id;
          
          if (this.editIngredientsList.length > 0) {
            const ingredientsData = this.editIngredientsList.map(ingredient => {
              return {
                ingredient_id: ingredient.id,
                amount: parseFloat(ingredient.pivot?.amount) || 0,
                unit: ingredient.pivot?.unit || 'g'
              };
            });
            
            await AuthService.api.put(`/kitchen/meals/${newMealId}/ingredients`, {
              ingredients: ingredientsData
            });
          }
          
          await this.fetchMeals();
          
          this.showAlert({
            message: 'Étel sikeresen hozzáadva' + (this.editIngredientsList.length > 0 ? ` (${this.editIngredientsList.length} összetevővel)` : ''),
            type: 'success'
          });
          
          this.closeModal();
          
        } catch (error) {
          console.error('Étel mentése sikertelen:', error);
          this.showAlert({
            message: error.response?.data?.message || 'Hiba történt a mentés során',
            type: 'error'
          });
        } finally {
          this.savingMeal = false;
        }
      } else {
        await this.saveMealWithIngredients();
      }
    },

    async saveMealWithIngredients() {
      if (!this.editingMeal || !this.editingMeal.id) {
        console.error('No editing meal or missing ID')
        return
      }
      
      try {
        this.savingMeal = true
        
        const mealResponse = await AuthService.api.put(
          `/kitchen/meals/${this.editingMeal.id}`, 
          this.mealForm
        )
        
        if (!mealResponse.data.success) {
          this.showAlert({
            message: mealResponse.data.message || 'Hiba történt az étel mentése során',
            type: 'error'
          })
          return
        }
        
        const ingredientsData = this.editIngredientsList.map(ingredient => {
          return {
            ingredient_id: ingredient.id,
            amount: parseFloat(ingredient.pivot?.amount) || 0,
            unit: ingredient.pivot?.unit || 'g'
          }
        })
        
        if (ingredientsData.length === 0) {
          this.showAlert({
            message: 'Étel sikeresen mentve (nincsenek összetevők)',
            type: 'success'
          })
          this.closeModal()
          await this.fetchMeals()
          return
        }
        
        const ingredientsResponse = await AuthService.api.put(
          `/kitchen/meals/${this.editingMeal.id}/ingredients`,
          { ingredients: ingredientsData }
        )
        
        if (ingredientsResponse.data.success) {
          this.showAlert({
            message: 'Étel és összetevők sikeresen mentve',
            type: 'success'
          })
          this.closeModal()
          await this.fetchMeals()
        } else {
          this.showAlert({
            message: 'Étel mentve, de az összetevők mentése sikertelen: ' + ingredientsResponse.data.message,
            type: 'warning'
          })
        }
        
      } catch (error) {
        console.error('Error in saveMealWithIngredients:', error)
        
        if (error.response) {
          if (error.response.data?.errors) {
            const validationErrors = Object.values(error.response.data.errors).flat().join('\n')
            this.showAlert({
              message: `Validációs hibák: ${validationErrors}`,
              type: 'error'
            })
          } else if (error.response.data?.message) {
            this.showAlert({
              message: `Szerver hiba: ${error.response.data.message}`,
              type: 'error'
            })
          }
        } else if (error.request) {
          this.showAlert({
            message: 'Nem érkezett válasz a szervertől. Ellenőrizd a hálózati kapcsolatot!',
            type: 'error'
          })
        } else {
          this.showAlert({
            message: error.message || 'Ismeretlen hiba történt a mentés során',
            type: 'error'
          })
        }
        
      } finally {
        this.savingMeal = false
      }
    },
    
    async deleteMeal(mealId) {
      if (!mealId) return
      
      const confirmed = await this.showConfirm({ 
        title: 'Étel törlése',
        message: 'Biztosan törölni szeretnéd ezt az ételt?' 
      })
      
      if (!confirmed) return
      
      try {
        const response = await AuthService.api.delete(`/kitchen/meals/${mealId}`)
        if (response.data.success) {
          this.meals = this.meals.filter(meal => meal.id !== mealId)
          this.showAlert({ message: 'Étel sikeresen törölve', type: 'success' })
        } else {
          this.showAlert({ message: response.data.message || 'Hiba történt', type: 'error' })
        }
      } catch (error) {
        console.error('Étel törlése sikertelen:', error)
        this.showAlert({ message: error.response?.data?.message || 'Az étel a menün szerepel, nem törölhető', type: 'error' })
      }
    },

    async loadAllIngredients() {
      try {
        const response = await AuthService.api.get('/kitchen/ingredients', {
          params: { withAllergens: true, per_page: 1000 }
        })
        this.allIngredients = response.data.data || []
      } catch (error) {
        console.error('Error loading ingredients:', error)
      }
    },

    searchIngredients() {
      if (!this.newIngredient.search || this.newIngredient.search.length < 2) {
        this.searchResults = []
        return
      }
      const searchTerm = this.newIngredient.search.toLowerCase()
      this.searchResults = this.allIngredients
        .filter(ingredient => ingredient.ingredientName.toLowerCase().includes(searchTerm))
        .slice(0, 8)
    },

    selectIngredient(ingredient) {
      this.selectedIngredientForAdd = ingredient
      this.newIngredient.search = ingredient.ingredientName
      this.searchResults = []
    },

    addIngredient() {
      if (!this.selectedIngredientForAdd || !this.newIngredient.amount) return
      
      const alreadyExists = this.editIngredientsList.some(
        item => item.id === this.selectedIngredientForAdd.id
      )
      
      if (alreadyExists) {
        this.showAlert({ message: 'Ez a hozzávaló már hozzá van adva', type: 'warning' })
        return
      }
      
      this.editIngredientsList.push({
        ...this.selectedIngredientForAdd,
        pivot: {
          amount: parseFloat(this.newIngredient.amount),
          unit: this.newIngredient.unit
        },
        tempId: Date.now()
      })
      
      this.newIngredient = { search: '', amount: '', unit: 'g' }
      this.selectedIngredientForAdd = null
      this.searchResults = []
    },

    removeIngredientFromList(index) {
      this.editIngredientsList.splice(index, 1)
    },

    updateIngredientAmount(ingredient) {
      ingredient.pivot.amount = parseFloat(ingredient.pivot.amount) || 0
    },

    updateIngredientUnit(ingredient) {},

    calculateEditTotalAmount() {
      const total = this.editIngredientsList.reduce((sum, ingredient) => {
        const amount = ingredient.pivot?.amount || 0
        return sum + parseFloat(amount)
      }, 0)
      return total.toFixed(0) + ' g'
    },

    calculateEditTotalCalories() {
      const total = this.editIngredientsList.reduce((sum, ingredient) => {
        return sum + this.calculateIngredientCalories(ingredient)
      }, 0)
      return Math.round(total)
    },

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
      const amount = ingredient.pivot?.amount || 0
      return Math.round((amount / 100) * ingredient.energy)
    },

    calculateIngredientProtein(ingredient) {
      if (!ingredient || typeof ingredient.protein === 'undefined') return '0'
      const amount = ingredient.pivot?.amount || 0
      return ((amount / 100) * ingredient.protein).toFixed(1)
    },

    calculateIngredientCarbohydrate(ingredient) {
      if (!ingredient || typeof ingredient.carbohydrate === 'undefined') return '0'
      const amount = ingredient.pivot?.amount || 0
      return ((amount / 100) * ingredient.carbohydrate).toFixed(1)
    },

    calculateIngredientFat(ingredient) {
      if (!ingredient || typeof ingredient.fat === 'undefined') return '0'
      const amount = ingredient.pivot?.amount || 0
      return ((amount / 100) * ingredient.fat).toFixed(1)
    },

    calculateTotalAmount() {
      if (!this.selectedMeal?.ingredients) return '0 g'
      const total = this.selectedMeal.ingredients.reduce((sum, ing) => {
        return sum + (ing.pivot?.amount || 0)
      }, 0)
      return total.toFixed(0) + ' g'
    },

    calculateTotalCalories() {
      if (!this.selectedMeal?.ingredients) return 0
      return this.selectedMeal.ingredients.reduce((sum, ing) => {
        return sum + this.calculateIngredientCalories(ing)
      }, 0)
    },

    calculateTotalProtein() {
      if (!this.selectedMeal?.ingredients) return '0'
      const total = this.selectedMeal.ingredients.reduce((sum, ing) => {
        return sum + parseFloat(this.calculateIngredientProtein(ing))
      }, 0)
      return total.toFixed(1)
    },

    calculateTotalCarbohydrate() {
      if (!this.selectedMeal?.ingredients) return '0'
      const total = this.selectedMeal.ingredients.reduce((sum, ing) => {
        return sum + parseFloat(this.calculateIngredientCarbohydrate(ing))
      }, 0)
      return total.toFixed(1)
    },

    calculateTotalFat() {
      if (!this.selectedMeal?.ingredients) return '0'
      const total = this.selectedMeal.ingredients.reduce((sum, ing) => {
        return sum + parseFloat(this.calculateIngredientFat(ing))
      }, 0)
      return total.toFixed(1)
    },

    getCaloriesPercentage() {
      const total = this.calculateTotalCalories()
      return Math.min((total / 2000) * 100, 100)
    },

    getProteinPercentage() {
      const total = parseFloat(this.calculateTotalProtein())
      return Math.min((total / 50) * 100, 100)
    },

    getCarbPercentage() {
      const total = parseFloat(this.calculateTotalCarbohydrate())
      return Math.min((total / 300) * 100, 100)
    },

    getFatPercentage() {
      const total = parseFloat(this.calculateTotalFat())
      return Math.min((total / 70) * 100, 100)
    }
  }
}
</script>

<style scoped>
.meals-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
}

.content-card {
  background: var(--content-card);
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
  margin-bottom: 1rem;
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

.search-box {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.search-input {
  padding: 0.5rem 0.75rem 0.5rem 2rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  width: 250px;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
}

.search-icon {
  position: absolute;
  left: 0.6rem;
  font-size: 0.85rem;
  color: #888;
  pointer-events: none;
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

.btn-primary:hover {
  background: #158a0f;
}

.filters-section {
  margin-bottom: 1.5rem;
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  background: white;
  cursor: pointer;
  width: 200px;
}

.filter-select:focus {
  outline: none;
  border-color: #f0a24a;
}

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

.error-message {
  background: #ffebee;
  color: #c62828;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  background: #f9f9f9;
  border-radius: 12px;
}

.empty-state p {
  margin-bottom: 1rem;
}

.meals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.meal-card {
  background: white;
  border: 1px solid #eee;
  border-radius: 16px;
  padding: 1.25rem;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.meal-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border-color: #f0a24a;
}

.meal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.meal-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
}

.meal-category {
  background: #f0a24a;
  color: #7b2c2c;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.meal-description {
  color: #666;
  margin: 0 0 1rem 0;
  line-height: 1.4;
  font-size: 0.8rem;
}

.ingredients-preview {
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 10px;
}

.ingredients-preview strong {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.7rem;
  color: #8a1212;
  text-transform: uppercase;
}

.preview-items {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
}

.preview-item {
  background: white;
  padding: 0.2rem 0.5rem;
  border-radius: 6px;
  font-size: 0.7rem;
  border: 1px solid #e0e0e0;
}

.more-items {
  color: #888;
  font-size: 0.7rem;
  font-style: italic;
}

.allergens-section {
  margin: 0.5rem 0;
  padding: 0.5rem 0.75rem;
  border-radius: 10px;
  background: #f8f9fa;
}

.allergens-section.safe {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.allergens-header strong {
  font-size: 0.7rem;
  color: #8a1212;
  text-transform: uppercase;
}

.allergens-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin-top: 0.3rem;
}

.allergen-tag {
  display: inline-flex;
  align-items: center;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0.2rem;
  transition: all 0.2s;
}

.allergen-tag:hover {
  transform: translateY(-2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-color: #f0a24a;
}

.allergen-icon-img {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.allergen-icon-fallback {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f0a24a;
  color: #7b2c2c;
  border-radius: 6px;
  font-size: 0.7rem;
  font-weight: 600;
}

.safe-text {
  font-size: 0.75rem;
  color: #2e7d32;
  font-weight: 500;
}

.meal-actions {
  margin-top: auto;
  display: flex;
  gap: 0.5rem;
  padding-top: 0.75rem;
}

.btn-action {
  flex: 1;
  padding: 0.4rem 0.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.7rem;
  font-weight: 500;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
}

.btn-edit {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-edit:hover {
  background: #bbdef5;
  transform: translateY(-2px);
}

.btn-view {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-view:hover {
  background: #c8e6c9;
  transform: translateY(-2px);
}

.btn-delete {
  background: #ffebee;
  color: #c62828;
}

.btn-delete:hover {
  background: #ffcdd2;
  transform: translateY(-2px);
}

.btn-add-ingredient{
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
  max-width: 800px;
  max-height: 90vh;
  overflow: hidden;
  display: flex !important;
  position: relative !important;
  flex-direction: column;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  animation: modalFadeIn 0.2s ease-out;
}

.ingredients-modal {
  max-width: 1000px;
}

.edit-meal-modal {
  max-width: 1000px;
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

.btn-save {
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

.btn-save:hover:not(:disabled) {
  background: #158a0f;
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}


.alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10001;
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

.confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10002;
}

.confirm-box {
  background: white;
  padding: 24px;
  border-radius: 14px;
  min-width: 320px;
  text-align: center;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);
  animation: scaleIn 0.25s ease;
}

@keyframes scaleIn {
  from {
    transform: scale(0.6);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.confirm-title {
  margin-bottom: 10px;
}

.confirm-message {
  font-weight: bold;
  text-align: center;
  margin: 10px 0 20px 0;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.confirm-actions .btn-cancel {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.confirm-actions .btn-cancel:hover {
  background: #c0392b;
}

.confirm-actions .btn-ok {
  background: #2ecc71;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.confirm-actions .btn-ok:hover {
  background: #27ae60;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #666;
  text-transform: uppercase;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #f0a24a;
}

.form-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.form-row .form-group {
  flex: 1;
  min-width: 150px;
}


.allergen-warning {
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #fff7e6;
  border: 1px solid #f0a24a;
  border-radius: 12px;
}

.warning-header h4 {
  margin: 0 0 0.5rem 0;
  color: #8a1212;
  font-size: 0.9rem;
}

.allergens-detail-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.allergen-detail {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.5rem;
  background: white;
  border-radius: 20px;
  border: 1px solid #eee;
}

.allergen-detail-icon {
  width: 20px;
  height: 20px;
  object-fit: contain;
}

.allergen-detail-name {
  font-size: 0.75rem;
  color: #333;
}

.ingredient-allergens {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.allergen-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.125rem;
  background: #e9ecef;
  border: 1px solid #ced4da;
  border-radius: 3px;
  padding: 0.125rem 0.25rem;
  font-size: 0.7rem;
}

.allergen-chip-icon {
  width: 20px;
  height: 20px;
  object-fit: contain;
}

.no-allergens {
  color: #6c757d;
  font-style: italic;
}

.ingredients-table-container {
  overflow-x: auto;
  margin-bottom: 1rem;
}

.ingredients-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}

.ingredients-table th,
.ingredients-table td {
  padding: 0.6rem;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}

.ingredients-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #8a1212;
}

.ingredients-table tbody tr:hover {
  background: #fef9ef;
}

.total-row {
  background: #f8f9fa !important;
  font-weight: 600;
}

.nutrition-summary {
  margin-top: 1.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 12px;
}

.nutrition-bars {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.nutrition-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.bar-label {
  width: 80px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #666;
}

.bar-container {
  flex: 1;
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.bar {
  height: 100%;
  background: #f0a24a;
  transition: width 0.3s ease;
}

.bar.protein { background: #2ecc71; }
.bar.carb { background: #f39c12; }
.bar.fat { background: #e74c3c; }

.bar-value {
  width: 70px;
  text-align: right;
  font-size: 0.75rem;
  font-weight: 500;
  color: #333;
}

[class*="allergen-tag"][title*="Glutén"],
[class*="allergen-chip"][title*="Glutén"] {border-color: #6a8b0e50;background: #e7e3a4;}

[class*="allergen-tag"][title*="Tej"],
[class*="allergen-chip"][title*="Tej"]  { border-color: #3d5e6359; background: #60747775; }


[class*="allergen-tag"][title*="Tojás"],
[class*="allergen-chip"][title*="Tojás"]  { border-color: #6e42c160; background: #3a0e4e4f; }

[class*="allergen-tag"][title*="Hal"],
[class*="allergen-chip"][title*="Hal"]  { border-color: #dfc01350; background: #ffdb79c5; }


[class*="allergen-tag"][title*="Dió"],
[class*="allergen-chip"][title*="Dió"]  { border-color: #f87f1c6c; background: #ca8d5c7e; }

[class*="allergen-tag"][title*="Földimogyoró"],
[class*="allergen-chip"][title*="Földimogyoró"] { border-color: #df77227e; background: #f7c584b9; }

[class*="allergen-tag"][title*="Zeller"],
[class*="allergen-chip"][title*="Zeller"]  { border-color: #7849b644; background: #967ebec4; }

[class*="allergen-tag"][title*="Rákfélék"],
[class*="allergen-chip"][title*="Rákfélék"] { border-color: #2dc5be44; background: #6bafbbc2; }

[class*="allergen-tag"][title*="Mustár"],
[class*="allergen-chip"][title*="Mustár"]  { border-color: #17157262; background: #84889eb9; }

[class*="allergen-tag"][title*="Kukorica"],
[class*="allergen-chip"][title*="Kukorica"]  { border-color: #5a244262; background: #df6fa3b9; }

[class*="allergen-tag"][title*="Szójabab"],
[class*="allergen-chip"][title*="Szójabab"] { border-color: #5a244262; background: #ca5b8fb9; }


.ingredients-section {
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e0e0e0;
}

.section-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.2rem;
}

.section-subtitle {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.add-ingredient-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.add-ingredient-section h4 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1rem;
}

.add-ingredient-form {
  background: #fff;
  padding: 1.5rem;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.search-container {
  position: relative;
}

.search-input-ingredient {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 4px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  animation: slideDown 0.2s ease;
}

.search-result-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-result-item:hover {
  background: #f8f9fa;
}

.search-result-item:last-child {
  border-bottom: none;
}

.result-name {
  font-weight: 500;
  color: #2c3e50;
  flex: 1;
}

.result-type {
  font-size: 0.8rem;
  color: #7f8c8d;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  margin: 0 0.5rem;
}

.result-info {
  font-size: 0.8rem;
  color: #3498db;
  font-weight: 500;
}

.selected-ingredient-info {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #e8f5e9;
  border-radius: 4px;
  border-left: 4px solid #2ecc71;
  font-size: 0.9rem;
}

.ingredient-details {
  display: block;
  color: #555;
  margin-top: 0.25rem;
  font-size: 0.85rem;
}

.amount-input,
.unit-select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.95rem;
  width: 100%;
}

.amount-input.small,
.unit-select.small {
  padding: 0.5rem;
  font-size: 0.9rem;
}

.btn-add-ingredient {
  background: #3498db;
  color: #fff;
  border: 0;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  width: 100%;
  margin-top: auto;
  transition: background 0.3s ease;
}

.btn-add-ingredient:hover:not(:disabled) {
  background: #2980b9;
}

.btn-add-ingredient:disabled {
  background: #bdc3c7;
  cursor: not-allowed;
}

.current-ingredients-section {
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.current-ingredients-section h4 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1rem;
}

.no-current-ingredients {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  background: #fff;
  border-radius: 6px;
  border: 1px solid #e0e0e0;
}

.current-ingredients-table-container {
  overflow-x: auto;
  margin-bottom: 1.5rem;
}

.current-ingredients-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 6px;
  overflow: hidden;
  font-size: 0.9rem;
}

.current-ingredients-table th,
.current-ingredients-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
  vertical-align: middle;
}

.current-ingredients-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  white-space: nowrap;
  border-bottom: 2px solid #ddd;
}

.current-ingredients-table tbody tr:hover {
  background: #f8f9fa;
}

.ingredient-name {
  font-weight: 500;
  color: #2c3e50;
}

.ingredient-nutrition {
  margin-top: 0.25rem;
}

.ingredient-nutrition .ingredient-type {
  padding: 0.15rem 0.4rem;
  font-size: 0.7rem;
}

.calories-display {
  font-weight: 500;
  color: #e74c3c;
  font-size: 0.9rem;
}

.btn-remove-ingredient {
  background: #e74c3c;
  color: #fff;
  border: 0;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  transition: background 0.3s ease;
}

.btn-remove-ingredient:hover {
  background: #c0392b;
}

.edit-summary {
  display: flex;
  gap: 2rem;
  padding: 1rem;
  background: #fff;
  border-radius: 8px;
  border: 1px solid #e0e0e0;
}

.summary-item {
  flex: 1;
  text-align: center;
  padding: 0.5rem;
}

.summary-label {
  display: block;
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.summary-value {
  display: block;
  color: #2c3e50;
  font-weight: 600;
  font-size: 1.1rem;
}


.edit-meal-modal {
  max-width: 1000px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
}

.edit-meal-modal form {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 0;
}

.basic-info-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.basic-info-section h3 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1.2rem;
}


.ingredient-type {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  display: inline-block;
  white-space: nowrap;
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


@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}


@media (max-width: 768px) {
  .meals-management {
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

  .search-box {
    width: 100%;
  }

  .search-input {
    width: 100%;
  }

  .btn-primary {
    width: 100%;
    text-align: center;
  }

  .filter-select {
    width: 100%;
  }

  .meals-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    flex-direction: column;
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .nutrition-bar {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .bar-label,
  .bar-value {
    width: auto;
  }

  .bar-container {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .meal-header h3 {
    font-size: 0.95rem;
  }

  .meal-actions {
    flex-wrap: wrap;
  }

  .btn-action {
    font-size: 0.65rem;
    padding: 0.3rem 0.4rem;
  }
}
</style>