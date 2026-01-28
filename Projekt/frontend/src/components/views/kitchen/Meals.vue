<template>
  <div class="meals-management">
    <div class="header">
      <h1>Ételek {{canEdit ? 'kezelése' : 'listája'}}</h1>
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
    
    <div v-if="loading" class="loading">Ételek betöltése...</div>
    <div v-else-if="error" class="error">{{ error }}</div>

    <div v-else class="meals-container">
        <div v-if="meals.length === 0" class="no-meals">
          <p>Nincs még étel hozzáadva.</p>
          <button 
            v-if="canEdit" 
            @click="showAddModal = true" 
            class="btn-add-first"
          >
            Adja hozzá az első ételt
          </button>
          <p v-else>Kérjük várjon, amíg a konyha hozzáadja az ételeket</p>
        </div>

      
      <div v-else class="meals-grid">

        <div v-for="meal in meals" :key="meal?.id || 'unknown'" class="meal-card">
          <div class="meal-header">
            <h3>{{ meal?.mealName || 'Névtelen étel' }}</h3>
            <span class="meal-category">{{ meal?.category || 'Egyéb' }}</span>
            
            
          </div>
          
          <p class="meal-description">{{ meal?.description || 'Nincs leírás' }}</p>
          
          <!-- Összetevők előnézet -->
          <div v-if="meal?.ingredients && meal.ingredients.length && canEdit> 0" class="ingredients-preview">
            <strong>Összetevők:</strong>
            <div class="preview-items">
              <span 
                v-for="(ingredient, index) in meal.ingredients.slice(0, 3)" 
                :key="ingredient?.id || index" 
                class="preview-item"
              >
                {{ ingredient?.ingredientName || 'Ismeretlen' }}<span v-if="ingredient?.pivot"> ({{ ingredient.pivot.amount }}{{ ingredient.pivot.unit }})</span>
              </span>
              <span v-if="meal.ingredients.length > 3" class="more-items">
                +{{ meal.ingredients.length - 3 }} további
              </span>
            </div>
          </div>
          
          <!-- ALLERGÉNEK MEGJELENÍTÉSE -->
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
                  <!--<span class="allergen-name">{{ getAllergenShortName(allergen?.allergenName) }}</span>-->
                </span>
              </template>
            </div>
          </div>
          
          <div v-else class="allergens-section safe">
            <span class="safe-badge"></span>
            <span class="safe-text">Allergénmentes</span>
          </div>
          
          <div class="meal-actions">
            <button @click="editMeal(meal)" class="btn-edit" v-if="canEdit">Szerkesztés</button>
            <button @click="viewIngredients(meal)" class="btn-view">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              {{ canEdit ? 'Összetevők' : 'Tápérték információk' }}

            </button>
            <button @click="deleteMeal(meal?.id)" class="btn-delete" v-if="canEdit">Törlés</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Új étel modal -->
    <div v-if="showAddModal && !editingMeal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>Új étel hozzáadása</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        
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
              <span class="section-subtitle">Összesen: {{ editIngredientsList.length }} hozzávaló</span>
            </div>
            
            <!-- Új hozzávaló hozzáadása -->
            <div class="add-ingredient-section">
              <h4>Új hozzávaló hozzáadása</h4>
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
                        class="search-input"
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
            
            <!-- Meglévő hozzávalók listája -->
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
                            <option value="tk">tk</option>
                            <option value="ek">ek</option>
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
                
                <!-- Összegzés -->
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
          
          <!-- Modal gombok -->
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-cancel">
              Mégse
            </button>
            <button type="submit" class="btn-save" :disabled="savingMeal">
              {{ savingMeal ? 'Mentés...' : 'Mentés' }}
            </button>
          </div>
        </form>

      </div>
    </div>
    
    <!-- Ételszerkesztés modal -->
    <div v-if="showAddModal && editingMeal" class="modal-overlay">
      <div class="modal edit-meal-modal">
        <div class="modal-header">
          <h2>{{ editingMeal?.mealName || 'Étel' }} szerkesztése</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        
        <form @submit.prevent="saveMeal">
          <!-- Alapadatok -->
          <div class="basic-info-section">
            <h3>Alapadatok</h3>
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
          
          <!-- Összetevők szerkesztése -->
          <div class="ingredients-section">
            <div class="section-header">
              <h3>Összetevők</h3>
              <span class="section-subtitle">Összesen: {{ editIngredientsList.length }} hozzávaló</span>
            </div>
            
            <!-- Új hozzávaló hozzáadása -->
            <div class="add-ingredient-section">
              <h4>Új hozzávaló hozzáadása</h4>
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
                        class="search-input"
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
                      <option value="tk">tk</option>
                      <option value="ek">ek</option>
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label>&nbsp;</label>
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
            
            <!-- Meglévő hozzávalók listája -->
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
                            <option value="tk">tk</option>
                            <option value="ek">ek</option>
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
                
                <!-- Összegzés -->
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
          
          <!-- Modal gombok -->
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn-cancel">
              Mégse
            </button>
            <button type="submit" class="btn-save" :disabled="savingMeal">
              {{ savingMeal ? 'Mentés...' : 'Mentés' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    
        <!-- Összetevők megtekintése modal -->
    <div v-if="showIngredientsModal && selectedMeal" class="modal-overlay">
      <div class="modal ingredients-modal">
        <div class="modal-header">
          <h2>{{ selectedMeal?.mealName || 'Étel' }} - {{ canEdit ? 'Összetevők' : 'Tápérték információk'}}</h2>
          <button @click="closeIngredientsModal" class="close-btn">&times;</button>
        </div>
        
        <div v-if="loadingIngredients " class="loading">{{ canEdit ? 'Összetevők' : 'Tápérték információk'}} betöltése...</div>
        
        <div v-else-if="ingredientsError" class="error">{{ ingredientsError }}</div>
        
        <div v-else class="ingredients-content">
          <!-- ALLERGÉN FIGYELMEZTETÉS - ÚJ -->
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
            <p>Ehhez az ételhez még nincsenek hozzávalók hozzáadva.</p>
          </div>
          
          <div v-else class="ingredients-list">
            <div v-if="canEdit" class="ingredients-summary">
              <p><strong>Összesen {{ selectedMeal.ingredients.length }} hozzávaló</strong></p>

            </div>
            
            <div v-if="canEdit" class="ingredients-table-container">
              <table class="ingredients-table">
                <thead>
                  <tr>
                    <th>Hozzávaló</th>
                    <th>Típus</th>
                    <th>Allergének</th>
                    <th>Mennyiség</th>
                    <th>Kalória (össz)</th>
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
                    <td >
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
                          <!--{{ getAllergenShortName(allergen.allergenName) }}-->
                        </span>
                      </div>
                      <span v-else class="no-allergens">-</span>
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
      savingMeal: false,
      
      // Összetevők szerkesztése
      editIngredientsList: [],
      
      // Új hozzávaló hozzáadása
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
      }
    }
  },
  
  computed: {
    canEdit() {
      return AuthService.isKitchen() || AuthService.isAdmin()
    },
    
    canAddIngredient() {
      return this.selectedIngredientForAdd && 
             this.newIngredient.amount && 
             parseFloat(this.newIngredient.amount) > 0
    },
    canView() {
      return AuthService.isAuthenticated()
    }
  },
  
  mounted() {
    this.checkAuthAndFetch()
    if (this.canEdit) {
      this.loadAllIngredients()
    } 
  },
  
  methods: {
    async checkAuthAndFetch() {
      if (!AuthService.isAuthenticated()) {
        this.$router.push('/login')
        return
      }
      
      if (!this.canEdit) {
        this.error = 'Nincs megfelelő jogosultságod az ételek kezeléséhez'
        this.$router.push('/dashboard')
        return
      }
      
      // Mindkét betöltést elindítjuk
      await Promise.all([
        this.fetchMeals(),
        this.loadAllIngredients()
      ])
    },
    
    async fetchMeals() {
  this.loading = true
  this.error = ''
  
  try {
    const params = {}
    if (this.search) params.search = this.search
    if (this.selectedCategory) params.category = this.selectedCategory
    
    params.withAllergens = true
    
    params._t = Date.now();
    
    const response = await AuthService.api.get('/kitchen/meals', { params })
    
    if (response.data.success) {
      this.meals = response.data.meals || []
      
      // Debug: allergén ikonok ellenőrzése
      console.log('=== ALLERGEN DEBUG INFO ===')
      this.meals.forEach((meal, index) => {
        if (meal.allergens && meal.allergens.length > 0) {
          console.log(`Meal ${index + 1}: ${meal.mealName}`)
          meal.allergens.forEach((allergen, aIndex) => {
            console.log(`  Allergen ${aIndex + 1}:`, {
              name: allergen.allergenName,
              icon: allergen.icon,
              iconUrl: this.getAllergenIconUrl(allergen.icon)
            })
          })
        }
      })
      console.log('=== END DEBUG ===')
      
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
    
    // Összetevők betöltése allergénekkel
    async viewIngredients(meal) {
      if (!meal || !meal.id) {
        console.error('Invalid meal passed to viewIngredients:', meal)
        return
      }
      
      this.selectedMeal = meal
      this.showIngredientsModal = true
      this.loadingIngredients = true
      this.ingredientsError = ''
      this.mealAllergens = []
      
      try {
        // Betöltjük az összetevőket allergénekkel
        const response = await AuthService.api.get(`/kitchen/meals/${meal.id}/ingredients?withAllergens=true`)
        
        if (response.data.success) {
          console.log('Ingredients response:', response.data)
          
          // Összetevők allergénekkel
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
          
          // Összegyűjtjük az étel összes allergénjét
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
    
    closeIngredientsModal() {
      this.showIngredientsModal = false
      this.selectedMeal = null
      this.ingredientsError = ''
      this.mealAllergens = []
    },


    getAllergenIconUrl(iconPath) {
  if (!iconPath) {
    return 'https://via.placeholder.com/16x16/3498db/ffffff?text=❓';
  }

  // Tisztítsd az útvonalat
  const cleanPath = iconPath.replace(/^\//, '');
  
  // Különböző lehetséges útvonalak
  const baseUrl = 'http://localhost:8000';
  const pathsToTry = [
    // 1. Próbáld a storage útvonalat
    `${baseUrl}/storage/${cleanPath}`,
    
    // 2. Ha a storage link nem működik, próbáld a public/images útvonalat
    `${baseUrl}/images/allergens/${cleanPath.split('/').pop()}`,
    
    // 3. Vagy csak a fájlnevét
    `${baseUrl}/${cleanPath.split('/').pop()}`
  ];

  // Debug információk
  console.log('Allergen icon debug:', {
    originalPath: iconPath,
    cleanPath: cleanPath,
    tryingPaths: pathsToTry
  });

  // Visszaadjuk az elsőt, vagy lehetővé tesszük a váltást
  return pathsToTry[1]; // Próbáld a public/images útvonalat
},
        
    handleImageError(event) {
    console.error('Image failed to load:', {
      src: event.target.src,
      alt: event.target.alt,
      element: event.target
    })
    
    event.target.style.display = 'none'
    const parent = event.target.parentElement
    if (parent) {
      const fallback = parent.querySelector('.allergen-icon-fallback')
      if (fallback) {
        fallback.style.display = 'flex'
      }
    }
  },

    // Egyedi allergének szűrése
    getUniqueAllergens(allergens) {
      if (!allergens || !Array.isArray(allergens)) return []
      
      const seen = new Set()
      return allergens.filter(allergen => {
        if (!allergen || !allergen.id) return false
        if (seen.has(allergen.id)) {
          return false
        }
        seen.add(allergen.id)
        return true
      })
    },
    /*
    // Allergén rövid neve
    getAllergenShortName(name) {
      if (!name) return ''
      if (name.length <= 3) return name
      return name.substring(0, 3)
    },
    */

    // Allergén kezdőbetűje
    getAllergenInitial(name) {
      if (!name) return '?'
      return name.charAt(0).toUpperCase()
    },
    
    // Allergének összegyűjtése az ételből
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
    
    // Allergénhez tartozó összetevők nevei
    getIngredientNamesForAllergen(allergenId) {
      if (!this.selectedMeal?.ingredients) return []
      
      return this.selectedMeal.ingredients
        .filter(ingredient => 
          ingredient.allergens?.some(a => a.id === allergenId)
        )
        .map(ingredient => ingredient.ingredientName)
        .slice(0, 3) // Max 3 nevet jelenítünk meg
    },
    
    // ÉTEL SZERKESZTÉSE
    async editMeal(meal) {
      if (!meal || !meal.id) {
        console.error('Invalid meal passed to editMeal:', meal)
        return
      }
      
      this.editingMeal = meal
      this.showAddModal = true
      
      // Alapadatok betöltése
      this.mealForm = { 
        mealName: meal.mealName || '',
        category: meal.category || '',
        description: meal.description || ''
      }
      
      // Összetevők betöltése szerkesztéshez
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
        console.error('Hozzávalók betöltése szerkesztéshez sikertelen:', error)
        if (meal.ingredients && meal.ingredients.length > 0) {
          this.editIngredientsList = meal.ingredients.map(ingredient => ({
            ...ingredient,
            pivot: {
              amount: parseFloat(ingredient.pivot?.amount) || 0,
              unit: ingredient.pivot?.unit || 'g'
            }
          }))
        }
      }
    },
    
    // ÉTEL MENTÉSE ÖSSZETEVŐKKEL EGYÜTT
    async saveMeal() {
  if (!this.editingMeal) {
    // Új étel létrehozása
    try {
      this.savingMeal = true;
      
      console.log('=== START saving new meal ===');
      console.log('Meal form:', this.mealForm);
      console.log('Ingredients to save:', this.editIngredientsList);
      
      // 1. Először létrehozzuk az ételt
      const mealResponse = await AuthService.api.post('/kitchen/meals', {
        mealName: this.mealForm.mealName,
        category: this.mealForm.category,
        description: this.mealForm.description
      });
      
      if (!mealResponse.data.success) {
        alert(mealResponse.data.message);
        this.savingMeal = false;
        return;
      }
      
      const newMealId = mealResponse.data.meal.id;
      console.log('New meal created with ID:', newMealId);
      
      // 2. Ha vannak összetevők, hozzáadjuk külön
      if (this.editIngredientsList.length > 0) {
        const ingredientsData = this.editIngredientsList.map(ingredient => {
          return {
            ingredient_id: ingredient.id,
            amount: parseFloat(ingredient.pivot?.amount) || 0,
            unit: ingredient.pivot?.unit || 'g'
          };
        });
        
        console.log('Sending ingredients data:', ingredientsData);
        
        await AuthService.api.put(`/kitchen/meals/${newMealId}/ingredients`, {
          ingredients: ingredientsData
        });
        
        console.log('Ingredients saved successfully');
      }
      
      // 3. Azonnal frissítjük az ételek listáját allergénekkel
      console.log('Refreshing meals list with allergens...');
      await this.fetchMeals();
      
      // 4. Keressük meg az új ételt a frissített listában és mutassuk meg az allergéneit
      setTimeout(() => {
        const newMeal = this.meals.find(m => m.id === newMealId);
        if (newMeal) {
          console.log('Found new meal in updated list:', newMeal);
          
          // Ha a newMeal nem tartalmaz allergéneket, akkor manuálisan betöltjük
          if (!newMeal.allergens || newMeal.allergens.length === 0) {
            this.loadAllergensForNewMeal(newMealId);
          }
        }
      }, 500);
      
      this.$toast?.success('Étel sikeresen hozzáadva' + 
        (this.editIngredientsList.length > 0 ? ` (${this.editIngredientsList.length} összetevővel)` : ''));
      
      this.closeModal();
      
    } catch (error) {
      console.error('Étel mentése sikertelen:', error);
      alert(error.response?.data?.message || 'Hiba történt a mentés során');
    } finally {
      this.savingMeal = false;
    }
  } else {
    // Meglévő étel frissítése
    await this.saveMealWithIngredients();
  }
},

async loadAllergensForNewMeal(mealId) {
  try {
    console.log('Loading allergens for new meal:', mealId);
    
    const response = await AuthService.api.get(`/kitchen/meals/${mealId}/ingredients?withAllergens=true`);
    
    if (response.data.success) {
      // Összegyűjtjük az allergéneket
      const allergens = new Map();
      
      response.data.ingredients.forEach(ingredient => {
        if (ingredient.allergens && Array.isArray(ingredient.allergens)) {
          ingredient.allergens.forEach(allergen => {
            if (!allergens.has(allergen.id)) {
              allergens.set(allergen.id, allergen);
            }
          });
        }
      });
      
      const uniqueAllergens = Array.from(allergens.values());
      
      // Frissítjük a meals listában az étel allergéneit
      const mealIndex = this.meals.findIndex(m => m.id === mealId);
      if (mealIndex !== -1) {
        this.meals[mealIndex].allergens = uniqueAllergens;
        console.log('Updated meal allergens in list:', uniqueAllergens);
      }
    }
  } catch (error) {
    console.error('Error loading allergens for new meal:', error);
  }
},
    
    async saveMealWithIngredients() {
      if (!this.editingMeal || !this.editingMeal.id) {
        console.error('No editing meal or missing ID')
        return
      }
      
      try {
        this.savingMeal = true
        
        console.log('=== START saveMealWithIngredients ===')
        console.log('Meal ID:', this.editingMeal.id)
        console.log('Meal form:', this.mealForm)
        console.log('Ingredients to save:', this.editIngredientsList)
        
        // 1. Alapadatok mentése
        console.log('1. Saving meal basic data...')
        const mealResponse = await AuthService.api.put(
          `/kitchen/meals/${this.editingMeal.id}`, 
          this.mealForm
        )
        
        if (!mealResponse.data.success) {
          console.error('Meal save failed:', mealResponse.data)
          alert(mealResponse.data.message || 'Hiba történt az étel mentése során')
          return
        }
        
        console.log('Meal saved successfully')
        
        // 2. Összetevők mentése
        console.log('2. Preparing ingredients data...')
        const ingredientsData = this.editIngredientsList.map(ingredient => {
          const amount = parseFloat(ingredient.pivot?.amount) || 0
          const unit = ingredient.pivot?.unit || 'g'
          const ingredientId = ingredient.id
          
          console.log(`Ingredient: id=${ingredientId}, amount=${amount}, unit=${unit}`)
          
          // **FONTOS**: ellenőrizd, hogy a kulcsok helyesek-e
          return {
            ingredient_id: ingredientId,  
            amount: amount,
            unit: unit
          }
        })
        
        console.log('Ingredients data to send:', {
          ingredients: ingredientsData,  // <-- a tömbnek ez a formája
          count: ingredientsData.length
        })
        
        // Küldés előtti ellenőrzés
        if (ingredientsData.length === 0) {
          console.log('No ingredients to save, skipping...')
          this.$toast?.success('Étel sikeresen mentve (nincsenek összetevők)')
          this.closeModal()
          await this.fetchMeals()
          return
        }
        
        console.log('3. Sending ingredients to API...')
        const ingredientsResponse = await AuthService.api.put(
          `/kitchen/meals/${this.editingMeal.id}/ingredients`,
          { ingredients: ingredientsData }
        )
        
        console.log('Ingredients API response:', ingredientsResponse.data)
        
        if (ingredientsResponse.data.success) {
          console.log('Ingredients saved successfully')
          this.$toast?.success('Étel és összetevők sikeresen mentve')
          this.closeModal()
          await this.fetchMeals()
        } else {
          console.error('Ingredients save failed:', ingredientsResponse.data)
          alert('Étel mentve, de az összetevők mentése sikertelen: ' + ingredientsResponse.data.message)
        }
        
      } catch (error) {
        console.error('=== ERROR in saveMealWithIngredients ===')
        console.error('Error:', error)
        
        // Hibakezelés
        if (error.response) {
          if (error.response.data?.errors) {
            const validationErrors = Object.values(error.response.data.errors).flat().join('\n')
            alert(`Validációs hibák:\n${validationErrors}`)
          } else if (error.response.data?.message) {
            alert(`Szerver hiba: ${error.response.data.message}`)
          }
        } else if (error.request) {
          alert('Nem érkezett válasz a szervertől. Ellenőrizd a hálózati kapcsolatot!')
        } else {
          alert(error.message || 'Ismeretlen hiba történt a mentés során')
        }
        
      } finally {
        console.log('=== END saveMealWithIngredients ===')
        this.savingMeal = false
      }
    },
    
    // HOZZÁVALÓK KERESÉSE ÉS KEZELÉSE
async loadAllIngredients() {
  try {
    console.log('Loading ingredients...');
    
    // Egyszerű verzió - csak a data mezőt használjuk
    const response = await AuthService.api.get('/kitchen/ingredients', {
      params: { 
        withAllergens: true,
        per_page: 1000 // Minden hozzávalót lekérünk
      }
    });
    
    console.log('Response structure:', response.data);
    console.log('Response keys:', Object.keys(response.data));
    
    // Mindenképpen a 'data' kulcs alatt vannak a hozzávalók
    this.allIngredients = response.data.data || [];
    
    console.log(`✅ ${this.allIngredients.length} hozzávaló betöltve`);
    
    // Debug: nézzük meg az első néhány hozzávalót
    if (this.allIngredients.length > 0) {
      console.log('First 3 ingredients:', this.allIngredients.slice(0, 3));
    }
    
  } catch (error) {
    console.error('Error loading ingredients:', error);
    
  }
  
  console.log('Total ingredients available for search:', this.allIngredients.length);
},


    
    searchIngredients() {
      if (!this.newIngredient.search || this.newIngredient.search.length < 2) {
        this.searchResults = []
        return
      }
      
      const searchTerm = this.newIngredient.search.toLowerCase()
      this.searchResults = this.allIngredients
        .filter(ingredient => 
          ingredient.ingredientName.toLowerCase().includes(searchTerm) ||
          (ingredient.ingredientType && ingredient.ingredientType.toLowerCase().includes(searchTerm))
        )
        .slice(0, 8)
    },
    
    selectIngredient(ingredient) {
      this.selectedIngredientForAdd = ingredient
      this.newIngredient.search = ingredient.ingredientName
      this.searchResults = []
    },
    
    addIngredient() {
      if (!this.selectedIngredientForAdd || !this.newIngredient.amount) return
      
      // Ellenőrizzük, hogy már hozzá van-e adva
      const alreadyExists = this.editIngredientsList.some(
        item => item.id === this.selectedIngredientForAdd.id
      )
      
      if (alreadyExists) {
        alert('Ez a hozzávaló már hozzá van adva')
        return
      }
      
      // Új hozzávaló hozzáadása
      const newIngredient = {
        ...this.selectedIngredientForAdd,
        pivot: {
          amount: parseFloat(this.newIngredient.amount),
          unit: this.newIngredient.unit
        },
        tempId: Date.now()
      }
      
      this.editIngredientsList.push(newIngredient)
      
      // Űrlap reset
      this.newIngredient = { search: '', amount: '', unit: 'g' }
      this.selectedIngredientForAdd = null
      this.searchResults = []
    },

    removeIngredientFromList(index) {
      if (confirm('Biztosan eltávolítod ezt a hozzávalót?')) {
        this.editIngredientsList.splice(index, 1)
      }
    },
    
    updateIngredientAmount(ingredient) {
      ingredient.pivot.amount = parseFloat(ingredient.pivot.amount) || 0
    },
    
    updateIngredientUnit(ingredient) {
      // Unit frissítése
    },
    
    // SZÁMOLÁSI SEGÉDFÜGGVÉNYEK
    calculateEditTotalAmount() {
      const total = this.editIngredientsList.reduce((sum, ingredient) => {
        const amount = ingredient.pivot?.amount
        const numericAmount = parseFloat(amount) || 0
        return sum + numericAmount
      }, 0)
      
      return total.toFixed(0) + ' g'
    },
    
    calculateEditTotalCalories() {
      const totalCalories = this.editIngredientsList.reduce((sum, ingredient) => {
        const calories = this.calculateIngredientCalories(ingredient)
        return sum + (parseFloat(calories) || 0)
      }, 0)
      
      return Math.round(totalCalories)
    },
    
    closeModal() {
      this.showAddModal = false
      this.editingMeal = null
      this.editIngredientsList = []
      this.resetForm()
    },
    
    resetForm() {
      this.mealForm = {
        mealName: '',
        category: '',
        description: ''
      }
    },
    
    async deleteMeal(mealId) {
      if (!mealId) return
      
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
        alert(error.response?.data?.message || 'Az étel a menün szerepel, hiba történt a törlés során')
      }
    },
    
    // MEGTEKINTÉSHEZ SZÜKSÉGES SEGÉDFÜGGVÉNYEK
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
        const numericAmount = parseFloat(amount) || 0
        return sum + numericAmount
      }, 0)
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
.meals-management{
  padding:2rem;
  max-width:1400px;
  margin:0 auto;
}

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:2rem;
}

.header h1{
  margin:0;
  color:#2c3e50;
  font-size:1.8rem;
}

.btn-add{
  background:#27ae60;
  color:#fff;
  border:0;
  padding:.75rem 1.5rem;
  border-radius:4px;
  cursor:pointer;
  font-weight:500;
  min-width:150px;
  transition:background .3s ease;
}

.btn-add:hover{background:#219653;}

.filters{
  display:flex;
  gap:1rem;
  margin-bottom:2rem;
}

.filters input,
.filters select{
  padding:.75rem 1rem;
  border:1px solid #ddd;
  border-radius:4px;
  font-size:1rem;
}

.filters input{flex:1;}
.filters select{min-width:200px;}

.meals-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(350px,1fr));
  gap:1.5rem;
  align-items:stretch;
}

.meal-card{
  background:#fff;
  border:1px solid #e0e0e0;
  border-radius:8px;
  padding:1.5rem;
  transition:transform .2s,box-shadow .2s;
  display:flex;
  flex-direction:column;
  height:100%;
}

.meal-card:hover{
  transform:translateY(-2px);
  box-shadow:0 4px 12px rgba(0,0,0,.1);
}

.meal-header{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  margin-bottom:1rem;
}

.meal-header h3{
  margin:0;
  color:#2c3e50;
  font-size:1.25rem;
}

.meal-category{
  background:#3498db;
  color:#fff;
  padding:.25rem .75rem;
  border-radius:20px;
  font-size:.75rem;
  font-weight:500;
}

.meal-description{
  color:#7f8c8d;
  margin:0 0 1rem 0;
  line-height:1.5;
  font-size:.875rem;
}

.ingredients-preview{
  margin-bottom:1rem;
  padding:.75rem;
  background:#f8f9fa;
  border-radius:4px;
  min-height:120px;
  display:flex;
  flex-direction:column;
  justify-content:flex-start;
}

.ingredients-preview strong{
  display:block;
  margin-bottom:.5rem;
  color:#2c3e50;
}

.preview-items{
  display:flex;
  flex-wrap:wrap;
  gap:.5rem;
}

.preview-item{
  background:#fff;
  padding:.25rem .5rem;
  border-radius:4px;
  font-size:.75rem;
  border:1px solid #e0e0e0;
}

.more-items{
  color:#6c757d;
  font-size:.75rem;
  font-style:italic;
}

.meal-actions{
  margin-top:auto;
  display:flex;
  gap:.5rem;
}

.meal-actions button{
  flex:1;
  padding:.5rem;
  border:0;
  border-radius:4px;
  cursor:pointer;
  font-weight:500;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:.25rem;
  font-size:.875rem;
  transition:background .3s ease;
  white-space:nowrap;
  min-height:42px;
}

.btn-edit{background:#3498db;color:#fff;}
.btn-edit:hover{background:#2980b9;}

.btn-view{background:#2ecc71;color:#fff;}
.btn-view:hover{background:#27ae60;}

.btn-delete{background:#e74c3c;color:#fff;}
.btn-delete:hover{background:#c0392b;}

.modal-overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.7);
  display:flex;
  align-items:center;
  justify-content:center;
  z-index:1000;
  padding:1rem;
}

.modal{
  padding:20px;
  background:#fff;
  border-radius:8px;
  width:90%;
  max-width:800px;
  max-height:90vh;
  overflow-y:auto;
  box-shadow:0 4px 20px rgba(0,0,0,.3);
  position:relative!important;
  z-index:1001;
  transform:translateY(0);
  opacity:1;
  visibility:visible!important;
  display:block!important;
  animation:fadeIn .3s ease;
}

.ingredients-modal{max-width:1000px;}

.modal-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:1.5rem;
  padding:1.5rem 1.5rem 0;
}

.modal-header h2{
  margin:0;
  color:#2c3e50;
  font-size:1.5rem;
}

.close-btn{
  background:none;
  border:0;
  font-size:2rem;
  cursor:pointer;
  color:#7f8c8d;
  width:30px;
  height:30px;
  display:flex;
  align-items:center;
  justify-content:center;
  line-height:1;
  padding:0;
}

.close-btn:hover{color:#e74c3c;}

form{padding:0 1.5rem;}

.form-group{margin: auto;}

.form-group label{
  display:block;
  margin-bottom:.5rem;
  font-weight:500;
  color:#2c3e50;
}

.form-group input,
.form-group select,
.form-group textarea{
  width:100%;
  padding:.75rem;
  border:1px solid #ddd;
  border-radius:4px;
  font-size:1rem;
  transition:border-color .3s ease, box-shadow .3s ease;
}

.form-group textarea{
  resize:vertical;
  min-height:100px;
}

.form-row{
  display:flex;
  gap:1rem;
  margin-bottom:1rem;
  flex-wrap:wrap;
}

.form-row .form-group{
  flex:1;
  min-width:150px;
}

.modal-actions{
  display:flex;
  justify-content:flex-end;
  gap:1rem;
  margin-top:2rem;
  padding:1.5rem;
  border-top:1px solid #eee;
}

.btn-cancel{
  padding:.75rem 1.5rem;
  background:#95a5a6;
  color:#fff;
  border:0;
  border-radius:4px;
  cursor:pointer;
  font-weight:500;
  transition:background .3s ease;
}

.btn-cancel:hover{background:#7f8c8d;}

.btn-save{
  padding:.75rem 1.5rem;
  background:#27ae60;
  color:#fff;
  border:0;
  border-radius:4px;
  cursor:pointer;
  font-weight:500;
  transition:background .3s ease;
}

.btn-save:hover{background:#219653;}
.btn-save:disabled{background:#bdc3c7;cursor:not-allowed;}

.loading,
.error,
.no-meals{
  text-align:center;
  padding:3rem;
}

.error{
  color:#e74c3c;
  background:#ffebee;
  padding:1rem;
  border-radius:4px;
  margin:1rem 0;
}

.no-meals{
  color:#7f8c8d;
}

.btn-add-first{
  margin-top:1rem;
  padding:.75rem 1.5rem;
  background:#3498db;
  color:#fff;
  border:0;
  border-radius:4px;
  cursor:pointer;
  transition:background .3s ease;
}

.btn-add-first:hover{background:#2980b9;}

.ingredients-content{margin:0 1.5rem;}

.no-ingredients{
  text-align:center;
  padding:2rem;
  color:#7f8c8d;
}

.ingredients-summary{
  margin-bottom:1rem;
  padding-bottom:1rem;
  border-bottom:1px solid #eee;
  color:#2c3e50;
}

.ingredients-table-container{
  overflow-x:auto;
  margin-bottom:2rem;
}

.ingredients-table{
  width:100%;
  border-collapse:collapse;
  font-size:.9rem;
}

.ingredients-table th,
.ingredients-table td{
  padding:.75rem;
  text-align:left;
  border-bottom:1px solid #eee;
}

.ingredients-table th{
  background:#f8f9fa;
  font-weight:600;
  color:#2c3e50;
  white-space:nowrap;
}

.ingredients-table tbody tr:hover{background:#f8f9fa;}

.total-row{
  background:#f8f9fa!important;
  font-weight:600;
}

.ingredient-type{
  padding:.25rem .5rem;
  border-radius:4px;
  font-size:.75rem;
  font-weight:500;
  display:inline-block;
  white-space:nowrap;
}

.type-meat{background:#ffebee;color:#c62828;}
.type-dairy{background:#e3f2fd;color:#1565c0;}
.type-vegetable{background:#e8f5e9;color:#2e7d32;}
.type-other{background:#f5f5f5;color:#616161;}

.nutrition-summary{
  margin-top:2rem;
  padding:1.5rem;
  background:#f8f9fa;
  border-radius:8px;
}

.nutrition-summary h4{
  margin:0 0 1rem 0;
  color:#2c3e50;
  font-size:1.1rem;
}

.nutrition-bars{
  display:flex;
  flex-direction:column;
  gap:1rem;
}

.nutrition-bar{
  display:flex;
  align-items:center;
  gap:1rem;
}

.bar-label{
  width:100px;
  font-weight:500;
  color:#2c3e50;
}

.bar-container{
  flex:1;
  height:10px;
  background:#e0e0e0;
  border-radius:5px;
  overflow:hidden;
}

.bar{
  height:100%;
  background:#3498db;
  transition:width .3s ease;
}

.bar.protein{background:#2ecc71;}
.bar.carb{background:#f39c12;}
.bar.fat{background:#e74c3c;}

.bar-value{
  width:80px;
  text-align:right;
  font-weight:500;
  color:#2c3e50;
}

.add-ingredient-section{
  margin-bottom:2rem;
  padding:1.5rem;
  background:#f8f9fa;
  border-radius:8px;
}

.add-ingredient-section h4{
  margin:0 0 1rem 0;
  color:#2c3e50;
  font-size:1rem;
}

.add-ingredient-form{
  background:#fff;
  padding:1.5rem;
  border-radius:6px;
  border:1px solid #e0e0e0;
}

.search-container{position:relative;}

.search-input{
  width:100%;
  padding:.75rem;
  border:1px solid #ddd;
  border-radius:4px;
  font-size:.95rem;
}

.search-results{
  position:absolute;
  top:100%;
  left:0;
  right:0;
  background:#fff;
  border:1px solid #ddd;
  border-radius:4px;
  max-height:200px;
  overflow-y:auto;
  z-index:10;
  box-shadow:0 4px 8px rgba(0,0,0,.1);
  animation:slideDown .2s ease;
}

.search-result-item{
  padding:.75rem 1rem;
  cursor:pointer;
  border-bottom:1px solid #eee;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.search-result-item:hover{background:#f8f9fa;}
.search-result-item:last-child{border-bottom:none;}

.result-name{
  font-weight:500;
  color:#2c3e50;
  flex:1;
}

.result-type{
  font-size:.8rem;
  color:#7f8c8d;
  background:#f8f9fa;
  padding:.25rem .5rem;
  border-radius:4px;
  margin:0 .5rem;
}

.result-info{
  font-size:.8rem;
  color:#3498db;
  font-weight:500;
}

.selected-ingredient-info{
  margin-top:1rem;
  padding:.75rem;
  background:#e8f5e9;
  border-radius:4px;
  border-left:4px solid #2ecc71;
  font-size:.9rem;
}

.ingredient-details{
  display:block;
  color:#555;
  margin-top:.25rem;
  font-size:.85rem;
}

.amount-input,
.unit-select{
  padding:.75rem;
  border:1px solid #ddd;
  border-radius:4px;
  font-size:.95rem;
  width:100%;
}

.amount-input.small,
.unit-select.small{
  padding:.5rem;
  font-size:.9rem;
}

.btn-add-ingredient{
  background:#3498db;
  color:#fff;
  border:0;
  padding:.75rem 1.5rem;
  border-radius:4px;
  cursor:pointer;
  font-weight:500;
  width:100%;
  margin-top: auto;
  transition: background .3s ease;
}

.btn-add-ingredient:hover:not(:disabled){background:#2980b9;}
.btn-add-ingredient:disabled{background:#bdc3c7;cursor:not-allowed;}

.current-ingredients-section{
  padding:1.5rem;
  background:#f8f9fa;
  border-radius:8px;
}

.current-ingredients-section h4{
  margin:0 0 1rem 0;
  color:#2c3e50;
  font-size:1rem;
}

.no-current-ingredients{
  text-align:center;
  padding:2rem;
  color:#7f8c8d;
  background:#fff;
  border-radius:6px;
  border:1px solid #e0e0e0;
}

.current-ingredients-table-container{
  overflow-x:auto;
  margin-bottom:1.5rem;
}

.current-ingredients-table{
  width:100%;
  border-collapse:collapse;
  background:#fff;
  border-radius:6px;
  overflow:hidden;
  font-size:.9rem;
}

.current-ingredients-table th,
.current-ingredients-table td{
  padding:.75rem;
  text-align:left;
  border-bottom:1px solid #eee;
  vertical-align:middle;
}

.current-ingredients-table th{
  background:#f8f9fa;
  font-weight:600;
  color:#2c3e50;
  white-space:nowrap;
  border-bottom:2px solid #ddd;
}

.current-ingredients-table tbody tr:hover{background:#f8f9fa;}

.ingredient-name{
  font-weight:500;
  color:#2c3e50;
}

.ingredient-nutrition{margin-top:.25rem;}

.ingredient-nutrition .ingredient-type{
  padding:.15rem .4rem;
  font-size:.7rem;
}

.calories-display{
  font-weight:500;
  color:#e74c3c;
  font-size:.9rem;
}

.btn-remove-ingredient{
  background:#e74c3c;
  color:#fff;
  border:0;
  width:28px;
  height:28px;
  border-radius:50%;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:.9rem;
  transition:background .3s ease;
}

.btn-remove-ingredient:hover{background:#c0392b;}

.edit-summary{
  display:flex;
  gap:2rem;
  padding:1rem;
  background:#fff;
  border-radius:8px;
  border:1px solid #e0e0e0;
}

.summary-item{
  flex:1;
  text-align:center;
  padding:.5rem;
}

.summary-label{
  display:block;
  color:#7f8c8d;
  font-size:.9rem;
  margin-bottom:.25rem;
}

.summary-value{
  display:block;
  color:#2c3e50;
  font-weight:600;
  font-size:1.1rem;
}

.edit-meal-modal{
  max-width:1000px;
  max-height:85vh;
  display:flex;
  flex-direction:column;
}

.edit-meal-modal form{
  flex:1;
  overflow-y:auto;
  padding-bottom:0;
}

.basic-info-section{
  margin-bottom:2rem;
  padding:1.5rem;
  background:#f8f9fa;
  border-radius:8px;
}

.basic-info-section h3{
  margin:0 0 1rem 0;
  color:#2c3e50;
  font-size:1.2rem;
}

.ingredients-section{margin-bottom:2rem;}

.section-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:1rem;
  padding-bottom:.75rem;
  border-bottom:2px solid #e0e0e0;
}

.section-header h3{
  margin:0;
  color:#2c3e50;
  font-size:1.2rem;
}

.section-subtitle{
  color:#7f8c8d;
  font-size:.9rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus,
.amount-input:focus,
.unit-select:focus{
  outline:none;
  border-color:#3498db;
  box-shadow:0 0 0 2px rgba(52,152,219,.2);
}

.allergens-section{
  margin:.75rem 0;
  padding:.75rem;
  border-radius:6px;
  background:#f8f9fa;
  min-height:70px;
  display:flex;
  flex-direction:column;
  justify-content:center;
}

.allergens-section.safe{
  background:#d4edda;
  border:1px solid #c3e6cb;
  color:#155724;
  min-height:70px;
  display:flex;
  align-items:center;
  justify-content:center;
}

.allergens-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:.5rem;
}

.allergens-header strong{color:#2c3e50;}

.allergens-list{
  display:flex;
  flex-wrap:nowrap;
  overflow:hidden;
  gap:.375rem;
}

.allergen-tag{
  display:inline-flex;
  align-items:center;
  gap:.25rem;
  background:#fff;
  border:1px solid #dee2e6;
  border-radius:4px;
  font-size:.75rem;
  cursor:default;
  transition:all .2s;
  max-width:80px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}

.allergen-tag:hover{
  background:#f8f9fa;
  border-color:#adb5bd;
  transform:translateY(-1px);
  box-shadow:0 2px 4px rgba(0,0,0,.1);
}

.allergen-icon-img{
  width:38px;
  height:38px;
  padding:5px;
  object-fit:contain;
  border-radius:2px;
}

.allergen-icon-fallback{
  width:16px;
  height:16px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:#6c757d;
  color:#fff;
  border-radius:2px;
  font-size:.7rem;
  font-weight:700;
}

.safe-badge{
  font-size:1rem;
  margin-right:.5rem;
}

.safe-text{
  font-weight:500;
  color:#155724;
}

.allergen-warning{
  margin-bottom:1.5rem;
  padding:1rem;
  background:#f1e9cd;
  border:1px solid #ddd2b1;
  border-radius:6px;
}

.warning-header{
  display:flex;
  align-items:center;
  gap:.5rem;
  margin-bottom:.75rem;
}

.warning-header h4{
  margin:0;
  color:#856404;
  font-size:1rem;
}

.allergens-detail-list{margin-top:.75rem;}

.allergen-detail{
  display:flex;
  align-items:center;
  gap:.5rem;
  padding:.5rem;
  background:rgba(255,255,255,.7);
  border-radius:4px;
  margin-bottom:.5rem;
}

.allergen-detail:last-child{margin-bottom:0;}

.allergen-detail-icon{
  width:20px;
  height:20px;
  object-fit:contain;
}

.allergen-detail-name{
  font-weight:500;
  color:#2c3e50;
  min-width:120px;
}

.ingredient-allergens{
  display:flex;
  flex-wrap:wrap;
  gap:.25rem;
  min-width:100px;
  align-items:center;
}

.allergen-chip{
  display:inline-flex;
  align-items:center;
  gap:.125rem;
  background:#e9ecef;
  border:1px solid #ced4da;
  border-radius:3px;
  padding:.125rem .25rem;
  font-size:.7rem;
  max-width:60px;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}

.allergen-chip-icon{
  width:25px;
  height:25px;
  object-fit:contain;
}

.no-allergens{
  color:#6c757d;
  font-style:italic;
}

.allergen-summary{
  color:#dc3545;
  font-weight:500;
  font-size:.9rem;
}

.allergen-tag[title*="Glutén"]{border-color:#6a8b0e50;background:#e7e3a4;}
.allergen-tag[title*="Tej"]{border-color:#3d5e6359;background:#60747775;}
.allergen-tag[title*="Tojás"]{border-color:#6e42c160;background:#3a0e4e4f;}
.allergen-tag[title*="Hal"]{border-color:#dfc01350;background:#ffdb79c5;}
.allergen-tag[title*="Dió"]{border-color:#f87f1c6c;background:#ca8d5c7e;}
.allergen-tag[title*="Földimogyoró"]{border-color:#df77227e;background:#f7c584b9;}
.allergen-tag[title*="Zeller"]{border-color:#7849b644;background:#967ebec4;}
.allergen-tag[title*="Rákfélék"]{border-color:#2dc5be44;background:#6bafbbc2;}
.allergen-tag[title*="Mustár"]{border-color:#17157262;background:#84889eb9;}

*{
  scrollbar-width:thin;
  scrollbar-color:#bdc3c7 #f8f9fa;
}

*::-webkit-scrollbar{
  width:8px;
  height:8px;
}

*::-webkit-scrollbar-track{
  background:#f8f9fa;
  border-radius:4px;
}

*::-webkit-scrollbar-thumb{
  background-color:#bdc3c7;
  border-radius:4px;
}

*::-webkit-scrollbar-thumb:hover{
  background-color:#95a5a6;
}

[title]{position:relative;}

[title]:hover::after{
  content:attr(title);
  position:absolute;
  bottom:100%;
  left:50%;
  transform:translateX(-50%);
  padding:.5rem;
  background:rgba(0,0,0,.8);
  color:#fff;
  border-radius:4px;
  font-size:.75rem;
  white-space:nowrap;
  z-index:1000;
  margin-bottom:.5rem;
}

@keyframes fadeIn{
  from{opacity:0;transform:translateY(-10px);}
  to{opacity:1;transform:translateY(0);}
}

@keyframes slideDown{
  from{opacity:0;transform:translateY(-10px);}
  to{opacity:1;transform:translateY(0);}
}

@media (max-width:768px){
  .meals-management{padding:1rem;}
  .header{flex-direction:column;gap:1rem;align-items:flex-start;}
  .header h1{font-size:1.5rem;}
  .meals-grid{grid-template-columns:1fr;}
  .filters{flex-direction:column;}
  .filters select{min-width:100%;}
  .form-row{flex-direction:column;}
  .form-row .form-group{min-width:100%;}
  .edit-summary{flex-direction:column;gap:1rem;}
  .ingredients-table,
  .current-ingredients-table{font-size:.8rem;}
  .ingredients-table th,
  .ingredients-table td,
  .current-ingredients-table th,
  .current-ingredients-table td{padding:.5rem;}
  .nutrition-bar{flex-direction:column;align-items:flex-start;gap:.5rem;}
  .bar-label,
  .bar-value{width:auto;}
  .bar-container{width:100%;}
  .edit-meal-modal{width:95%;max-height:95vh;}
  .section-header{flex-direction:column;align-items:flex-start;gap:.5rem;}
  .allergen-tag{max-width:60px;font-size:.7rem;padding:.125rem .25rem;}
  .allergen-detail{flex-direction:column;align-items:flex-start;gap:.25rem;}
  .allergen-detail-name{min-width:auto;}
  .allergen-chip{max-width:50px;font-size:.65rem;}
}

.btn-add{
  width: 20px;
}
</style>