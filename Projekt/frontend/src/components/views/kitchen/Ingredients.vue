<template>
  <div class="ingredients">
    <div class="header">
      <h1>Hozzávalók kezelése</h1>

      <button @click="openCreateModal" class="btn-add">
          + Új hozzávaló
      </button>
    </div>

    
      <div class="header-actions">
        <!-- Keresés -->
        <div class="search-container">
          <input 
            type="text"
            v-model="filters.search"
            @input="debouncedLoadIngredients"
            placeholder="Keresés"
          />
        </div>

        <!-- Típus szűrő -->
        <div class="filter-container">
          <select v-model="filters.type" @change="loadIngredients">
            <option value="all">Összes típus</option>
            <option value="Egyéb">Egyéb</option>
            <option value="Hús">Hús</option>
            <option value="Hal">Hal</option>
            <option value="Tejtermék">Tejtermék</option>
            <option value="Zöldség">Zöldség</option>
            <option value="Gyümölcs">Gyümölcs</option>
            <option value="Fűszer">Fűszer</option>
          </select>
        </div>

        <!-- Elérhetőség szűrő -->
        <div class="filter-container">
          <select v-model="filters.availability" @change="loadIngredients">
            <option value="all">Összes állapot</option>
            <option value="available">Elérhető</option>
            <option value="unavailable">Nem elérhető</option>
          </select>
        </div>

        <!-- Rendezés 
        <div class="sort-container">
          <select v-model="filters.sort_by" @change="loadIngredients">
            <option value="ingredientName">Név</option>
            <option value="ingredientType">Típus</option>
            <option value="energy">Energia</option>
            <option value="created_at">Létrehozás dátuma</option>
          </select>
          <button 
            @click="toggleSortOrder"
            class="sort-btn"
            :title="filters.sort_order === 'asc' ? 'Növekvő' : 'Csökkenő'"
          >
            {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
          </button>
        </div>-->


      </div>

      <!-- Tömeges műveletek -->
      <div v-if="selectedIngredients.length > 0" class="bulk-actions">
        <div class="bulk-header">
          <span class="selected-count">{{ selectedIngredients.length }} hozzávaló kiválasztva</span>
          <div class="bulk-buttons">
            <button
              @click="bulkUpdateAvailability(true)"
              class="btn-bulk btn-bulk-available"
            >
              Elérhetővé tesz
            </button>
            <button
              @click="bulkUpdateAvailability(false)"
              class="btn-bulk btn-bulk-unavailable"
            >
              Nem elérhetővé tesz
            </button>
            <button
              @click="clearSelection"
              class="btn-bulk btn-bulk-clear"
            >
              Kijelölés törlése
            </button>
          </div>
        </div>
      </div>


    <!-- Betöltés állapota -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Hozzávalók betöltése...</p>
    </div>

    <!-- Üres állapot -->
    <div v-else-if="!loading && ingredients.length === 0" class="empty-state">
      <h3>Nincsenek hozzávalók</h3>
      <button
        @click="openCreateModal"
        class="btn-add-empty"
      >
        <span>+</span>Új hozzávaló létrehozása
      </button>
    </div>

    <!-- Hozzávalók táblázata -->
    <div v-else>
      <table class="ingredients-table">
        <thead>
          <tr>
            <th class="checkbox-col">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
                class="select-all"
              />
            </th>
            <th>Név</th>
            <th>Típus</th>
            <th>Tápértékek (100g)</th>
            <th>Állapot</th>
            <th class="actions-col">Műveletek</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="ingredient in ingredients" :key="ingredient.id">
            <!-- Fő sor -->
            <tr>
              <!-- Kijelölés -->
              <td class="checkbox-col">
                <input
                  type="checkbox"
                  :value="ingredient.id"
                  v-model="selectedIngredients"
                  class="select-item"
                />
              </td>

              <!-- Név -->
              <td>
                <div class="ingredient-name">
                  <div class="name">{{ ingredient.ingredientName }}</div>
                </div>
              </td>

              <!-- Típus -->
              <td>
                <span class="type-badge" :class="getTypeClass(ingredient.ingredientType)">
                  {{ ingredient.ingredientType }}
                </span>
              </td>

              <!-- Tápértékek -->
              <td>
                <div class="nutrition-info">
                  <div v-if="ingredient.energy" class="nutrition-item">
                    <span class="label">Energia:</span>
                    <span class="value">{{ ingredient.energy }} kcal</span>
                  </div>
                  <div v-if="ingredient.protein" class="nutrition-item">
                    <span class="label">Fehérje:</span>
                    <span class="value">{{ ingredient.protein }}g</span>
                  </div>
                  <div v-if="ingredient.carbohydrate" class="nutrition-item">
                    <span class="label">Szénhidrát:</span>
                    <span class="value">{{ ingredient.carbohydrate }}g</span>
                  </div>
                  <div v-if="ingredient.fat" class="nutrition-item">
                    <span class="label">Zsír:</span>
                    <span class="value">{{ ingredient.fat }}g</span>
                  </div>
                  <div v-if="!ingredient.energy && !ingredient.protein && !ingredient.carbohydrate && !ingredient.fat" 
                       class="no-nutrition">
                    Nincs megadva
                  </div>
                </div>
              </td>

              <!-- Állapot -->
              <td>
                <span class="status-badge" :class="ingredient.isAvailable ? 'available' : 'unavailable'">
                  <span class="status-dot" :class="ingredient.isAvailable ? 'available' : 'unavailable'"></span>
                  {{ ingredient.isAvailable ? 'Elérhető' : 'Nem elérhető' }}
                </span>
              </td>

              <!-- Műveletek -->
              <td class="actions">
                <div class="actions-inner">
                  <!-- Szerkesztés gomb -->
                  <button
                    @click="toggleEdit(ingredient.id)"
                    class="btn-edit"
                    :title="expandedIngredient === ingredient.id ? 'Bezárás' : 'Szerkesztés'"
                  >
                    <span v-if="expandedIngredient === ingredient.id">↑</span>
                    <span v-else>✎</span>
                  </button>

                  <!-- Elérhetőség váltó gomb -->
                  <button
                    @click="toggleAvailability(ingredient)"
                    :class="ingredient.isAvailable ? 'btn-deactivate' : 'btn-activate'"
                    :title="ingredient.isAvailable ? 'Nem elérhetővé tesz' : 'Elérhetővé tesz'"
                  >
                    <span v-if="ingredient.isAvailable" alt="Nem elérhetővé tesz">❚❚</span>
                    <span v-else alt="Elérhetővé tesz">✓</span>
                  </button>

                  <!-- Törlés gomb -->
                  <button
                    @click="confirmDelete(ingredient)"
                    class="btn-delete"
                    title="Törlés"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>

            <!-- Lenyíló szerkesztő rész -->
            <tr v-if="expandedIngredient === ingredient.id" class="edit-row">
              <td colspan="6">
                <div class="edit-container">
                  <div class="edit-header">
                    <h3>
                      Hozzávaló szerkesztése
                    </h3>
                    <p>
                      Módosítsd a(z) {{ ingredient.ingredientName }} hozzávaló adatait
                    </p>
                  </div>
                  
                  <form @submit.prevent="saveIngredient(ingredient)">
                    <div class="form-grid">
                      <!-- Alap információk -->
                      <div class="form-section">
                        <h4>Alap információk</h4>
                        <div class="form-row">
                          <!-- Név -->
                          <div class="form-group">
                            <label>Hozzávaló neve *</label>
                            <input
                              type="text"
                              v-model="editForm.ingredientName"
                              required
                              placeholder="Pl.: Paradicsom"
                            />
                          </div>

                          <!-- Típus -->
                          <div class="form-group">
                            <label>Típus *</label>
                            <select
                              v-model="editForm.ingredientType"
                              required
                            >
                              <option value="Egyéb">Egyéb</option>
                              <option value="Hús">Hús</option>
                              <option value="Hal">Hal</option>
                              <option value="Tejtermék">Tejtermék</option>
                              <option value="Zöldség">Zöldség</option>
                              <option value="Gyümölcs">Gyümölcs</option>
                              <option value="Fűszer">Fűszer</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- Tápértékek -->
                      <div class="form-section">
                        <h4>Tápértékek (100g-ra)</h4>

                        
                        <div class="form-row nutrition-grid">
                          <!-- Energia -->
                          <div class="form-group">
                            <label>Energia (kcal)</label>
                            <input
                              type="number"
                              v-model.number="editForm.energy"
                              min="0"
                              step="1"
                            />
                          </div>

                          <!-- Fehérje -->
                          <div class="form-group">
                            <label>Fehérje (g)</label>
                            <input
                              type="number"
                              v-model.number="editForm.protein"
                              min="0"
                              step="1"
                            />
                          </div>

                          <!-- Szénhidrát -->
                          <div class="form-group">
                            <label>Szénhidrát (g)</label>
                            <input
                              type="number"
                              v-model.number="editForm.carbohydrate"
                              min="0"
                              step="1"
                            />
                          </div>

                          <!-- Zsír -->
                          <div class="form-group">
                            <label>Zsír (g)</label>
                            <input
                              type="number"
                              v-model.number="editForm.fat"
                              min="0"
                              step="1"
                            />
                          </div>

                          <!-- Cukor -->
                          <div class="form-group">
                            <label>Cukor (g)</label>
                            <input
                              type="number"
                              v-model.number="editForm.sugar"
                              min="0"
                              step="1"
                            />
                          </div>

                          <!-- Rost -->
                          <div class="form-group">
                            <label>Rost (g)</label>
                            <input
                              type="number"
                              v-model.number="editForm.fiber"
                              min="0"
                              step="1"
                            />
                          </div>

                          <!-- Nátrium -->
                          <div class="form-group">
                            <label>Nátrium (mg)</label>
                            <input
                              type="number"
                              v-model.number="editForm.sodium"
                              min="0"
                              step="1"
                            />
                          </div>
                        </div>
                      </div>
                      <div class="form-section">
  <h4>Allergének</h4>
  <p class="section-description">Válaszd ki az összetevőhöz tartozó allergéneket</p>
  
  <div class="allergens-grid">
    <div 
      v-for="allergen in allergens" 
      :key="allergen.id"
      class="allergen-checkbox-item"
      :class="{ selected: editForm.allergen_ids.includes(allergen.id) }"
      @click="toggleAllergen(editForm, allergen.id)"
    >
      <input
        type="checkbox"
        :id="'edit-allergen-' + allergen.id"
        :value="allergen.id"
        v-model="editForm.allergen_ids"
        class="allergen-checkbox"
        @click.stop
      />
      <label :for="'edit-allergen-' + allergen.id" class="allergen-label" @click.stop>
        <img 
          v-if="allergen.icon_url" 
          :src="allergen.icon_url" 
          :alt="allergen.allergenName"
          class="allergen-icon"
        />
        <span class="allergen-name">{{ allergen.allergenName }}</span>
      </label>
    </div>
    
    <div v-if="allergens.length === 0" class="no-allergens">
      <p>Nincsenek allergének az adatbázisban.</p>
    </div>
  </div>
</div>

                      <!-- Elérhetőség -->
                      <div class="form-section">
                        <div class="checkbox-group">
                          <label for="isAvailable" class="checkbox-label">
                            Elérhető a felhasználásra
                          </label>
                          <input
                            type="checkbox"
                            id="isAvailable"
                            v-model="editForm.isAvailable"
                            class="checkbox-input"
                          />
                          
                        </div>
                      </div>
                    </div>

                    <!-- Művelet gombok -->
                    <div class="form-actions">
                      <button
                        type="button"
                        @click="cancelEdit"
                        class="btn-cancel"
                      >
                        Mégse
                      </button>
                      <button
                        type="submit"
                        :disabled="saving"
                        class="btn-save"
                      >
                        <span v-if="saving">Mentés...</span>
                        <span v-else>Mentés</span>
                      </button>
                    </div>
                  </form>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
            <!-- Pagination -->
      <div v-if="ingredients.length > 0" class="pagination">
        <button 
          @click="changePage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          Előző
        </button>
        
        <div class="pagination-info">
          <span>
            Oldal {{ currentPage }} / {{ lastPage }}
          </span>
          <span class="pagination-total">
            (Összesen: {{ totalItems }} hozzávaló)
          </span>
        </div>
        
        <button 
          @click="changePage(currentPage + 1)"
          :disabled="currentPage === lastPage"
          class="pagination-btn"
        >
          Következő
        </button>
      </div>

      <!-- Items per page selector -->
      <div v-if="ingredients.length > 0" class="per-page-selector">
        <label>
          Találatok oldalanként:
          <select v-model="perPage" @change="changePerPage">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </label>
      </div>
    </div>

    <div v-if="showCreateModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>Új hozzávaló</h2>
          <button @click="showCreateModal = false" class="btn-close">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveNewIngredient">
            <div class="form-grid">
              <!-- Alap információk -->
              <div class="form-section">
                <h4>Alap információk</h4>
                <div class="form-row">
                  <!-- Név -->
                  <div class="form-group">
                    <label>Hozzávaló neve *</label>
                    <input
                      type="text"
                      v-model="newForm.ingredientName"
                      required
                      placeholder="Pl.: Paradicsom"
                    />
                  </div>

                  <!-- Típus -->
                  <div class="form-group">
                    <label>Típus *</label>
                    <select
                      v-model="newForm.ingredientType"
                      required
                    >
                      <option value="">Válassz típust...</option>
                      <option value="Egyéb">Egyéb</option>
                      <option value="Hús">Hús</option>
                      <option value="Hal">Hal</option>
                      <option value="Tejtermék">Tejtermék</option>
                      <option value="Zöldség">Zöldség</option>
                      <option value="Gyümölcs">Gyümölcs</option>
                      <option value="Fűszer">Fűszer</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Tápértékek -->
              <div class="form-section">
                <h4>Tápértékek (100g-ra)</h4>
                <div class="form-row nutrition-grid">
                  <!-- Energia -->
                  <div class="form-group">
                    <label>Energia (kcal)</label>
                    <input
                      type="number"
                      v-model.number="newForm.energy"
                      min="0"
                      step="1"
                    />
                  </div>

                  <!-- Fehérje -->
                  <div class="form-group">
                    <label>Fehérje (g)</label>
                    <input
                      type="number"
                      v-model.number="newForm.protein"
                      min="0"
                      step="1"
                    />
                  </div>

                  <!-- Szénhidrát -->
                  <div class="form-group">
                    <label>Szénhidrát (g)</label>
                    <input
                      type="number"
                      v-model.number="newForm.carbohydrate"
                      min="0"
                      step="1"
                    />
                  </div>

                  <!-- Zsír -->
                  <div class="form-group">
                    <label>Zsír (g)</label>
                    <input
                      type="number"
                      v-model.number="newForm.fat"
                      min="0"
                      step="1"
                    />
                  </div>

                  <!-- Cukor -->
                  <div class="form-group">
                    <label>Cukor (g)</label>
                    <input
                      type="number"
                      v-model.number="newForm.sugar"
                      min="0"
                      step="1"
                    />
                  </div>

                  <!-- Rost -->
                  <div class="form-group">
                    <label>Rost (g)</label>
                    <input
                      type="number"
                      v-model.number="newForm.fiber"
                      min="0"
                      step="1"
                    />
                  </div>

                  <!-- Nátrium -->
                  <div class="form-group">
                    <label>Nátrium (mg)</label>
                    <input
                      type="number"
                      v-model.number="newForm.sodium"
                      min="0"
                      step="1"
                    />
                  </div>
                </div>
              </div>

              <!-- Allergének kezelése -->
            <div class="form-section">
              <h4>Allergének</h4>
              <p class="section-description">Válaszd ki az összetevőhöz tartozó allergéneket</p>
              
              <div class="allergens-grid">
                <div 
                  v-for="allergen in allergens" 
                  :key="allergen.id"
                  class="allergen-checkbox-item"
                  :class="{ selected: newForm.allergen_ids.includes(allergen.id) }"
                  @click="toggleAllergen(newForm, allergen.id)"
                >
                  <input
                    type="checkbox"
                    :id="'new-allergen-' + allergen.id"
                    :value="allergen.id"
                    v-model="newForm.allergen_ids"
                    class="allergen-checkbox"
                    @click.stop
                  />
                  <label :for="'new-allergen-' + allergen.id" class="allergen-label" @click.stop>
                    <img 
                      v-if="allergen.icon_url" 
                      :src="allergen.icon_url" 
                      :alt="allergen.allergenName"
                      class="allergen-icon"
                    />
                    <span class="allergen-name">{{ allergen.allergenName }}</span>
                  </label>
                </div>
                
                <div v-if="allergens.length === 0" class="no-allergens">
                  <p>Nincsenek allergének az adatbázisban.</p>
                </div>
              </div>
            </div>

              <!-- Elérhetőség -->
              <div class="form-section">
                <div class="checkbox-group">
                  <input
                    type="checkbox"
                    id="newIsAvailable"
                    v-model="newForm.isAvailable"
                    class="checkbox-input"
                  />
                  <label for="newIsAvailable" class="checkbox-label">
                    Hozzávaló elérhető a felhasználásra
                  </label>
                </div>
              </div>
            </div>

            <!-- Művelet gombok -->
            <div class="modal-footer">
              <button
                type="button"
                @click="showCreateModal = false"
                class="btn-cancel"
              >
                Mégse
              </button>
              <button
                type="submit"
                :disabled="saving"
                class="btn-save"
              >
                <span v-if="saving">Mentés...</span>
                <span v-else>Létrehozás</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Törlés megerősítés modal -->
    <div v-if="showDeleteModal" class="modal-overlay delete-modal">
        <div class="modal modal-sm">
          <div class="modal-header">
            <h2>Hozzávaló törlése</h2>
            <button @click="showDeleteModal = false" class="btn-close">×</button>
          </div>
          
          <div class="modal-body text-center">
            <div class="delete-icon">
              <span>⚠️</span>
            </div>
            
            <h3 class="delete-title">Biztosan törölni szeretnéd?</h3>
            <p class="delete-message">
              A(z) <strong>{{ ingredientToDelete?.ingredientName }}</strong> 
              hozzávalót nem lehet visszaállítani.
            </p>
            
            <!-- Konfliktus hiba üzenet -->
            <div v-if="deleteError" class="error-message-container">
              <div class="error-icon">❌</div>
              <div class="error-content">
                <p class="error-title">Nem törölhető!</p>
                <p class="error-message">{{ deleteError }}</p>
                <div v-if="deleteError.includes('használatban') || deleteError.includes('ételekben')" 
                    class="error-suggestions">
                  <p><strong>Lehetséges megoldások:</strong></p>
                  <ul>
                    <li>Először távolítsd el ebből az összetevőből az összes ételt</li>
                    <li>Vagy módosítsd az összetevő nevét és jelöld "Nem elérhető"-nek</li>
                    <li>Vagy töröld azokat az ételeket, amelyek ezt az összetevőt tartalmazzák</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button
                @click="showDeleteModal = false"
                class="btn-cancel"
              >
                Mégse
              </button>
              <button
                @click="deleteIngredient"
                :disabled="deleting"
                class="btn-delete-confirm"
                v-if="!deleteError || !deleteError.includes('használatban')"
              >
                <span v-if="deleting">Törlés...</span>
                <span v-else>Törlés</span>
              </button>
              <button
                @click="showDeleteModal = false"
                class="btn-close-error"
                v-if="deleteError && deleteError.includes('használatban')"
              >
                Bezárás
              </button>
            </div>
          </div>
        </div>
  </div>

</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import AuthService from '../../../services/AuthService'

// Reaktív állapotok
const ingredients = ref([])
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const selectedIngredients = ref([])
const ingredientToDelete = ref(null)
const deleteError = ref('')
const expandedIngredient = ref(null)

const allergens = ref([])
const selectedAllergens = ref([])

const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const perPage = ref(25)


// Szűrők
const filters = ref({
  search: '',
  type: 'all',
  availability: 'all',
  sort_by: 'ingredientName',
  sort_order: 'asc',
})

// Szerkesztés form
const editForm = ref({
  ingredientName: '',
  ingredientType: 'Egyéb',
  energy: null,
  protein: null,
  carbohydrate: null,
  fat: null,
  sodium: null,
  sugar: null,
  fiber: null,
  isAvailable: true,
  allergen_ids: []
})

// Új hozzávaló form
const newForm = ref({
  ingredientName: '',
  ingredientType: 'Egyéb',
  energy: null,
  protein: null,
  carbohydrate: null,
  fat: null,
  sodium: null,
  sugar: null,
  fiber: null,
  isAvailable: true,
   allergen_ids: []
})

// Computed properties
const allSelected = computed(() => {
  return ingredients.value.length > 0 && 
         ingredients.value.every(ingredient => selectedIngredients.value.includes(ingredient.id))
})

// Debounced keresés
const debouncedLoadIngredients = debounce(() => {
  loadIngredients()
}, 500)

// Életciklus
onMounted(() => {
  loadIngredients()
  loadAllergens()
})


  // Allergének betöltése
  async function loadAllergens() {
    try {
      const response = await AuthService.api.get('/kitchen/allergens')
      allergens.value = response.data.allergens || []
    } catch (error) {
      console.error('Hiba az allergének betöltésekor:', error)
    }
  }


async function loadIngredients() {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      ...filters.value,
    }
    
    // Távolítsuk el az 'all' értékeket és üres keresést
    if (params.type === 'all') delete params.type
    if (params.availability === 'all') delete params.availability
    if (params.search === '') delete params.search
    
    const response = await axios.get('/kitchen/ingredients', { params })
    
    // JSON parse ha szükséges
    let data
    if (typeof response.data === 'string') {
      try {
        const cleanData = response.data.replace(/^\uFEFF/, '')
        data = JSON.parse(cleanData)
      } catch (parseError) {
        console.error('JSON parse hiba:', parseError)
        throw new Error('Érvénytelen JSON válasz')
      }
    } else {
      data = response.data
    }
    
    if (data && data.success) {
      ingredients.value = data.data || []
      currentPage.value = data.current_page || 1
      lastPage.value = data.last_page || 1
      totalItems.value = data.total || 0
    } else if (Array.isArray(data)) {
      // Fallback régi formátumra
      ingredients.value = data
      currentPage.value = 1
      lastPage.value = 1
    } else {
      ingredients.value = []
    }
    
  } catch (error) {
    console.error('Hiba a hozzávalók betöltésekor:', error)
    ingredients.value = []
  } finally {
    loading.value = false
  }
}

// Add page change function
function changePage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  loadIngredients()
  window.scrollTo(0, 0)
}

// Add per page change function
function changePerPage() {
  currentPage.value = 1
  loadIngredients()
}

function getTypeClass(type) {
  const classes = {
    'Hús': 'type-meat',
    'Hal': 'type-fish',
    'Tejtermék': 'type-dairy',
    'Zöldség': 'type-vegetable',
    'Gyümölcs': 'type-fruit',
    'Fűszer': 'type-spice',
    'Egyéb': 'type-other'
  }
  
  return classes[type] || 'type-other'
}

function toggleAllergen(form, allergenId) {
  const index = form.allergen_ids.indexOf(allergenId)
  if (index === -1) {
    form.allergen_ids.push(allergenId)
  } else {
    form.allergen_ids.splice(index, 1)
  }
}

function toggleEdit(ingredientId) {
  if (expandedIngredient.value === ingredientId) {
    expandedIngredient.value = null
    resetEditForm()
  } else {
    const ingredient = ingredients.value.find(i => i.id === ingredientId)
    if (ingredient) {
      expandedIngredient.value = ingredientId
      editForm.value = {
        ingredientName: ingredient.ingredientName,
        ingredientType: ingredient.ingredientType,
        energy: ingredient.energy || null,
        protein: ingredient.protein || null,
        carbohydrate: ingredient.carbohydrate || null,
        fat: ingredient.fat || null,
        sodium: ingredient.sodium || null,
        sugar: ingredient.sugar || null,
        fiber: ingredient.fiber || null,
        isAvailable: Boolean(ingredient.isAvailable),
        allergen_ids: ingredient.allergens ? ingredient.allergens.map(a => a.id) : []
      }
      selectedAllergens.value = editForm.value.allergen_ids
    }
  }
}

function cancelEdit() {
  expandedIngredient.value = null
  resetEditForm()
}

function resetEditForm() {
  editForm.value = {
    ingredientName: '',
    ingredientType: 'Egyéb',
    energy: null,
    protein: null,
    carbohydrate: null,
    fat: null,
    sodium: null,
    sugar: null,
    fiber: null,
    isAvailable: true,
    allergen_ids: []
  }
}

function openCreateModal() {
  resetNewForm()
  showCreateModal.value = true
}

function resetNewForm() {
  newForm.value = {
    ingredientName: '',
    ingredientType: 'Egyéb',
    energy: null,
    protein: null,
    carbohydrate: null,
    fat: null,
    sodium: null,
    sugar: null,
    fiber: null,
    isAvailable: true,
     allergen_ids: []
  }
}

async function saveIngredient(ingredient) {
  saving.value = true
  
  try {
    const data = { ...editForm.value }
    // Távolítsuk el az üres értékeket
    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === '') {
        delete data[key]
      }
    })

    // Először az alapadatokat mentjük
    await axios.put(`/kitchen/ingredients/${ingredient.id}`, data)
    
    // Utána az allergéneket
    await axios.put(`/kitchen/ingredients/${ingredient.id}/allergens`, {
      allergen_ids: editForm.value.allergen_ids
    })
    
    await loadIngredients()
    
    expandedIngredient.value = null
    resetEditForm()
    
  } catch (error) {
    console.error('Hiba a mentés során:', error)
    let errorMessage = 'Hiba történt a mentés során'
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.response?.data?.errors) {
      errorMessage = Object.values(error.response.data.errors).flat().join(', ')
    }
    
    alert(errorMessage)
  } finally {
    saving.value = false
  }
}

async function saveNewIngredient() {
  saving.value = true
  
  try {
    const data = { ...newForm.value }
    
    // Különválasztjuk az allergéneket
    const allergenIds = [...data.allergen_ids]
    delete data.allergen_ids
    
    // Alapadatok mentése
    const response = await axios.post('/kitchen/ingredients', data)
    
    // Allergének mentése (ha vannak)
    if (allergenIds.length > 0 && response.data.data?.id) {
      await axios.put(`/kitchen/ingredients/${response.data.data.id}/allergens`, {
        allergen_ids: allergenIds
      })
    }
    
    await loadIngredients()
    
    showCreateModal.value = false
    resetNewForm()
    

  } catch (error) {
    console.error('Hiba a mentés során:', error)
    let errorMessage = 'Hiba történt a mentés során'
    
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    } else if (error.response?.data?.errors) {
      errorMessage = Object.values(error.response.data.errors).flat().join(', ')
    }
    
    alert(errorMessage)
  } finally {
    saving.value = false
  }
}

function confirmDelete(ingredient) {
  ingredientToDelete.value = ingredient
  deleteError.value = ''
  showDeleteModal.value = true
}

  async function deleteIngredient() {
      deleting.value = true
      deleteError.value = ''
      
      try {
        const response = await axios.delete(`/kitchen/ingredients/${ingredientToDelete.value.id}`)
        
        if (response.data.success) {
          await loadIngredients()
          showDeleteModal.value = false
          ingredientToDelete.value = null
          alert('Hozzávaló sikeresen törölve!')
        } else {
          deleteError.value = response.data.message || 'Ismeretlen hiba történt'
        }
        
      } catch (error) {
        console.error('Hiba a törlés során:', error)
        
        if (error.response?.status === 409) {
          // Conflict - a hozzávaló használatban van
          const errorMessage = error.response.data?.message || 
                              'Ez a hozzávaló nem törölhető, mert már használatban van ételekben.'
          deleteError.value = errorMessage
          
          // Extra információ a felhasználónak
          if (error.response.data?.used_in_meals) {
            const mealCount = error.response.data.used_in_meals.length
            deleteError.value += ` ${mealCount} ételben használják.`
          }
        } else if (error.response?.data?.message) {
          deleteError.value = error.response.data.message
        } else {
          deleteError.value = 'Hiba történt a törlés során'
        }
      } finally {
        deleting.value = false
      }
    }

async function toggleAvailability(ingredient) {
  try {
    await axios.put(`/kitchen/ingredients/${ingredient.id}`, {
      isAvailable: !ingredient.isAvailable
    })
    await loadIngredients()
    
  } catch (error) {
    console.error('Hiba az állapot változtatás során:', error)
    alert('Hiba történt az állapot változtatása során')
  }
}

async function bulkUpdateAvailability(isAvailable) {
  if (selectedIngredients.value.length === 0) return
  
  if (!confirm(`${selectedIngredients.value.length} hozzávaló ${isAvailable ? 'elérhetővé' : 'nem elérhetővé'} tétele?`)) {
    return
  }
  
  try {
    await axios.post('/kitchen/ingredients/bulk-availability', {
      ingredient_ids: selectedIngredients.value,
      isAvailable
    })
    await loadIngredients()
    clearSelection()
    
  } catch (error) {
    console.error('Hiba a tömeges frissítés során:', error)
    alert('Hiba történt a tömeges frissítés során')
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedIngredients.value = []
  } else {
    selectedIngredients.value = ingredients.value.map(ingredient => ingredient.id)
  }
}

function clearSelection() {
  selectedIngredients.value = []
}

function toggleSortOrder() {
  filters.value.sort_order = filters.value.sort_order === 'asc' ? 'desc' : 'asc'
  loadIngredients()
}
</script>

<style scoped>
/* Fő konténer */
.ingredients {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* Fejléc */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0;

}



.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.header-actions input,
.header-actions select {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.search-container {
  flex: 1;
  min-width: 300px;
}

.search-container input {
  width: 100%;
}

.filter-container,
.sort-container {
  min-width: 150px;
}

.filter-container select {
  width: 100%;
}

.sort-container {
  display: flex;
  gap: 0.5rem;
}

.sort-btn {
  padding: 0.5rem;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  min-width: 40px;
}

.sort-btn:hover {
  background: #e9ecef;
}

button{
  width: 20%;

}

.btn-add {
  background: var(--zold);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  min-width: 150px;
  transition: background 0.3s ease;
}
.btn-add:hover {
  background: #219653;
}

/* Tömeges műveletek */
.bulk-actions {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  margin-top: 1rem;
}

.bulk-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.selected-count {
  font-weight: 500;
  color: #2c3e50;
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-bulk {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.875rem;
}

.btn-bulk-available {
  background: #d4edda;
  color: #155724;
}

.btn-bulk-available:hover {
  background: #c3e6cb;
}

.btn-bulk-unavailable {
  background: #fff3cd;
  color: #856404;
}

.btn-bulk-unavailable:hover {
  background: #ffeaa7;
}

.btn-bulk-clear {
  background: #f8f9fa;
  color: #6c757d;
  border: 1px solid #dee2e6;
}

.btn-bulk-clear:hover {
  background: #e9ecef;
}

/* Betöltés állapota */
.loading {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--barack);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Üres állapot */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: #f8f9fa;
  border-radius: 8px;
}


.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 1.5rem;
}

.empty-state p {
  margin: 0 0 1.5rem 0;
  color: #7f8c8d;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

.btn-add-empty {
  padding: 0.75rem 1.5rem;
  background: #069642;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-add-empty:hover {
  background: #058d3e;
}

/* Táblázat */
.ingredients-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.ingredients-table th {
  background: #f0a24a;
  color: #7b2c2c;
  text-align: center;
  padding: 1rem;
  font-weight: 500;
  border-bottom: 2px solid #ffc875;
}

.checkbox-col {
  width: 40px;
}

.actions-col {
  width: 180px;
}

.ingredients-table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  text-align: left;
  vertical-align: top;
}

.ingredients-table tbody tr:hover {
  background: #f8f9fa;
}

.checkbox-col {
  text-align: center;
}

.select-all,
.select-item {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

/* Hozzávaló név */
.ingredient-name {
  display: flex;
  align-items: center;
}

.ingredient-name .name {
  font-weight: 500;
  color: #2c3e50;
}

/* Típus badge */
.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.type-meat {
  background: #f8d7da;
  color: #721c24;
}

.type-fish {
  background: #d1ecf1;
  color: #0c5460;
}

.type-dairy {
  background: #fff3cd;
  color: #856404;
}

.type-vegetable {
  background: #d4edda;
  color: #155724;
}

.type-fruit {
  background: #e2d9f3;
  color: #4a3f69;
}

.type-spice {
  background: #f8d7da;
  color: #721c24;
}

.type-other {
  background: #f8f9fa;
  color: #6c757d;
}

/* Tápértékek */
.nutrition-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.nutrition-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.nutrition-item .label {
  color: #6c757d;
  font-weight: 500;
}

.nutrition-item .value {
  color: #2c3e50;
  font-weight: 500;
}

.no-nutrition {
  font-size: 0.875rem;
  color: #adb5bd;
  font-style: italic;
}

/* Státusz badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.available {
  background: #d4edda;
  color: #155724;
}

.status-badge.unavailable {
  background: #f8d7da;
  color: #721c24;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-dot.available {
  background: #28a745;
}

.status-dot.unavailable {
  background: #dc3545;
}

/* Műveletek */
.actions {
  text-align: center;
}

.actions-inner {
  display: inline-flex;
  gap: 0.5rem;
  justify-content: center;
  align-items: center;
}

.actions button {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}

.actions button:hover {
  opacity: 0.8;
}

.btn-edit {
  background: #a1bacc;
  color: white;
}

.btn-activate {
  background: #d4edda;
  color: #155724;
}

.btn-deactivate {
  background: #fff3cd;
  color: #856404;
}

.btn-delete {
  background: #f8d7da;
  color: #721c24;
}

/* Lenyíló szerkesztő sor */
.edit-row {
  background: #f8f9fa !important;
}

.edit-row td {
  padding: 0;
  border-bottom: 2px solid #dee2e6;
}

.edit-container {
  padding: 1.5rem;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  margin: 0.5rem;
}

.edit-header {
  margin-bottom: 1.5rem;
}

.edit-header h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.edit-header p {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.875rem;
}

/* Form stílusok */
.form-section {
  margin-bottom: 1.5rem;
}

.form-section h4 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1rem;
  font-weight: 600;
}



.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.nutrition-grid {
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #476079;
  font-size: 0.875rem;
}

.form-group input,
.form-group select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

.checkbox-group {

  flex-direction: column;
  gap: 0.5rem;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  margin-right: 0.5rem;
  margin-left: 0.5rem;

}

.checkbox-label {

  font-weight: 500;
  color: #476079;
  cursor: pointer;
}



/* Form művelet gombok */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
  margin-top: 1rem;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: #95a5a6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  min-width: 100px;
}

.btn-cancel:hover {
  background: #7f8c8d;
}

.btn-save {
  padding: 0.75rem 1.5rem;
  background: #069642;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-save:hover:not(:disabled) {
  background: #6fce97;
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
}

.modal-sm {
  max-width: 500px;
  height: 50%;
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

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #7f8c8d;
  padding: 0.5rem;
}

.btn-close:hover {
  color: #e74c3c;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
  margin-top: 1rem;
}

/* Törlés modal specifikus */
.delete-modal .modal-body {
  text-align: center;
}

.delete-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.delete-title {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1.5rem;
}

.delete-message {
  margin: 0 0 1.5rem 0;
  color: #7f8c8d;
  line-height: 1.5;
}

.delete-message strong {
  color: #2c3e50;
}

/* Hiba üzenet stílusok */
.error-message-container {
  text-align: left;
  background: #fff3f3;
  border: 1px solid #ffcdd2;
  border-radius: 8px;
  padding: 1rem;
  margin: 1rem 0;
}

.error-icon {
  font-size: 2rem;
  color: #dc3545;
  text-align: center;
  margin-bottom: 0.5rem;
}

.error-content {
  text-align: left;
}

.error-title {
  font-weight: bold;
  color: #721c24;
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
}

.error-message {
  color: #856404;
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

.error-suggestions {
  background: white;
  padding: 1rem;
  border-radius: 6px;
  border-left: 4px solid #ffc107;
  margin-top: 1rem;
}

.error-suggestions p {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-weight: 500;
}

.error-suggestions ul {
  margin: 0;
  padding-left: 1.5rem;
}

.error-suggestions li {
  margin-bottom: 0.25rem;
  color: #6c757d;
}

.btn-close-error {
  padding: 0.75rem 1.5rem;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-close-error:hover {
  background: #5a6268;
}

.btn-delete-confirm {
  padding: 0.75rem 1.5rem;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-delete-confirm:hover:not(:disabled) {
  background: #c82333;
}

.btn-delete-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Reszponzív design */
@media (max-width: 768px) {
  .ingredients {
    padding: 1rem;
  }
  
  .header-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-container,
  .filter-container,
  .sort-container {
    min-width: unset;
    width: 100%;
  }
  
  .bulk-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .bulk-buttons {
    justify-content: stretch;
  }
  
  .btn-bulk {
    flex: 1;
    min-width: unset;
  }
  
  .ingredients-table {
    display: block;
    overflow-x: auto;
  }
  
  .form-row,
  .nutrition-grid {
    grid-template-columns: 1fr;
  }
  
  .modal {
    width: 95%;
    margin: 1rem;
  }
}

@media (max-width: 480px) {
  .header h1 {
    font-size: 1.5rem;
  }
  
  .bulk-buttons {
    flex-direction: column;
  }
  
  .actions-inner {
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .actions button {
    width: 100%;
    min-width: 32px;
  }
}
/* Allergének rács */
.allergens-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.allergen-checkbox-item {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  background: white;
}

.allergen-checkbox-item:hover {
  border-color: #3498db;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.allergen-checkbox-item.selected {
  background: #e3f2fd;
  border-color: #2196f3;
}

.allergen-checkbox {
  margin-right: 0.5rem;
  width: 18px;
  height: 18px;
}

.allergen-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  flex: 1;
}

.allergen-icon {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.allergen-name {
  font-size: 0.875rem;
  color: #2c3e50;
}

.no-allergens {
  grid-column: 1 / -1;
  padding: 2rem;
  text-align: center;
  background: #f8f9fa;
  border-radius: 8px;
  color: #7f8c8d;
}

.section-description {
  color: #7f8c8d;
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

/* Reszponzív */
@media (max-width: 768px) {
  .allergens-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
  
  .allergen-icon {
    width: 20px;
    height: 20px;
  }
  
  .allergen-name {
    font-size: 0.8rem;
  }
}
/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 2rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #999;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: #7f8c8d;
  font-size: 0.875rem;
}

.pagination-total {
  margin-left: 0.5rem;
  color: #2c3e50;
  font-weight: 500;
}

/* Per page selector */
.per-page-selector {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
  margin-bottom: 1rem;
}

.per-page-selector label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #7f8c8d;
  font-size: 0.875rem;
}

.per-page-selector select {
  padding: 0.25rem 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: white;
  cursor: pointer;
}

.per-page-selector select:hover {
  border-color: #999;
}

/* Responsive */
@media (max-width: 768px) {
  .pagination {
    flex-direction: column;
    gap: 1rem;
  }
  
  .per-page-selector {
    justify-content: center;
  }
}
</style>