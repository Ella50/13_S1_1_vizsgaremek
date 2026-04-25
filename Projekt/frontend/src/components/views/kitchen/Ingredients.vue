<template>
  <div class="ingredients">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Hozzávalók kezelése</h1>
        
        <div class="header-controls">
          <div class="search-box">
            <input 
              type="text"
              v-model="filters.search"
              @input="onSearchInput"
              placeholder="Keresés név vagy típus alapján..."
              maxlength="255" 
              class="search-input"
            >
            <span class="search-icon">🔍</span>
          </div>

          <select v-model="filters.type" @change="loadIngredients" class="filter-select">
            <option value="all">Összes típus</option>
            <option value="Egyéb">Egyéb</option>
            <option value="Hús">Hús</option>
            <option value="Hal">Hal</option>
            <option value="Tejtermék">Tejtermék</option>
            <option value="Zöldség">Zöldség</option>
            <option value="Gyümölcs">Gyümölcs</option>
            <option value="Fűszer">Fűszer</option>
          </select>

          <select v-model="filters.availability" @change="loadIngredients" class="filter-select">
            <option value="all">Összes állapot</option>
            <option value="available">Elérhető</option>
            <option value="unavailable">Nem elérhető</option>
          </select>

          <button @click="openCreateModal" class="btn-primary">
            + Új hozzávaló
          </button>
        </div>
      </div>

      <div v-if="selectedIngredients.length > 0" class="bulk-actions">
        <div class="bulk-header">
          <span class="selected-count">{{ selectedIngredients.length }} hozzávaló kiválasztva</span>
          <div class="bulk-buttons">
            <button @click="bulkUpdateAvailability(true)" class="btn-bulk btn-bulk-available">
              Elérhetővé tesz
            </button>
            <button @click="bulkUpdateAvailability(false)" class="btn-bulk btn-bulk-unavailable">
              Nem elérhetővé tesz
            </button>
            <button @click="clearSelection" class="btn-bulk btn-bulk-clear">
              Kijelölés törlése
            </button>
          </div>
        </div>
      </div>

  
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Hozzávalók betöltése...</p>
      </div>


      <div v-else-if="!loading && ingredients.length === 0" class="empty-state">
        <p>Nincs ilyen hozzávaló</p>
      </div>

      <div v-else>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th class="checkbox-col">
                  <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" class="select-all">
                </th>
                <th>Név</th>
                <th>Típus</th>
                <th>Állapot</th>
                <th class="actions-col">Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ingredient in ingredients" :key="ingredient.id">
                <td class="checkbox-col">
                  <input type="checkbox" :value="ingredient.id" v-model="selectedIngredients" class="select-item">
                </td>
                <td class="ingredient-name">{{ ingredient.ingredientName }}</td>
                <td>
                  <span class="type-badge" :class="getTypeClass(ingredient.ingredientType)">
                    {{ ingredient.ingredientType }}
                  </span>
                </td>
                <td>
                  <span class="status-badge" :class="ingredient.isAvailable ? 'status-active' : 'status-inactive'">
                    {{ ingredient.isAvailable ? 'Elérhető' : 'Nem elérhető' }}
                  </span>
                </td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button @click="openEditModal(ingredient)" class="btn-action btn-edit" title="Szerkesztés">
                      ✎
                    </button>
                    <button @click="toggleAvailability(ingredient)" :class="ingredient.isAvailable ? 'btn-action btn-deactivate' : 'btn-action btn-activate'" :title="ingredient.isAvailable ? 'Nem elérhetővé tesz' : 'Elérhetővé tesz'">
                      {{ ingredient.isAvailable ? '❚❚' : '✓' }}
                    </button>
                    <button @click="confirmDelete(ingredient)" class="btn-action btn-delete" title="Törlés">
                      Törlés
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>


        <Pagination
          :current-page="currentPage"
          :last-page="lastPage"
          :total="total"
          :per-page="perPage"
          @update:page="changePage"
          @update:perPage="changePerPage"
        />
      </div>
    </div>

    <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Hozzávaló szerkesztése</h2>
          <button @click="closeEditModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveIngredient">
            <div class="form-grid">
      
              <div class="form-section">
                <h4>Alap információk</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label>Hozzávaló neve *</label>
                    <input type="text" v-model="editForm.ingredientName" required maxlength="255">
                  </div>
                  <div class="form-group">
                    <label>Típus *</label>
                    <select v-model="editForm.ingredientType" required>
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

            
              <div class="form-section">
                <h4>Tápértékek (100g-ra)</h4>
                <div class="form-row nutrition-grid">
                  <div class="form-group">
                    <label>Energia (kcal)</label>
                    <input type="number" v-model.number="editForm.energy" min="0" step="1">
                  </div>
                  <div class="form-group">
                    <label>Fehérje (g)</label>
                    <input type="number" v-model.number="editForm.protein" min="0" step="1">
                  </div>
                  <div class="form-group">
                    <label>Szénhidrát (g)</label>
                    <input type="number" v-model.number="editForm.carbohydrate" min="0" step="1">
                  </div>
                  <div class="form-group">
                    <label>Zsír (g)</label>
                    <input type="number" v-model.number="editForm.fat" min="0" step="1">
                  </div>
                  <div class="form-group">
                    <label>Cukor (g)</label>
                    <input type="number" v-model.number="editForm.sugar" min="0" step="1">
                  </div>
                  <div class="form-group">
                    <label>Rost (g)</label>
                    <input type="number" v-model.number="editForm.fiber" min="0" step="1">
                  </div>
                  <div class="form-group">
                    <label>Nátrium (mg)</label>
                    <input type="number" v-model.number="editForm.sodium" min="0" step="1">
                  </div>
                </div>
              </div>

    
              <div class="form-section">
                <h4>Allergének</h4>
                <p class="section-description">Válaszd ki az összetevőhöz tartozó allergéneket</p>
                <div class="allergens-grid">
                  <div v-for="allergen in allergens" :key="allergen.id" class="allergen-item" :class="{ selected: editForm.allergen_ids.includes(allergen.id) }" @click="toggleAllergen(editForm, allergen.id)">
                    <input type="checkbox" :id="'edit-allergen-' + allergen.id" :value="allergen.id" v-model="editForm.allergen_ids" class="allergen-checkbox" @click.stop>
                    <label :for="'edit-allergen-' + allergen.id" class="allergen-label" @click.stop>
                      <img v-if="allergen.icon_url" :src="getAllergenIconUrl(allergen.icon)" :alt="allergen.allergenName" class="allergen-icon">
                      <span class="allergen-name">{{ allergen.allergenName }}</span>
                    </label>
                  </div>
                  <div v-if="allergens.length === 0" class="no-allergens">Nincsenek allergének az adatbázisban.</div>
                </div>
              </div>

    
              <div class="form-section">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="editForm.isAvailable">
                  <span>Elérhető a felhasználásra</span>
                </label>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeEditModal" class="btn-cancel">Mégse</button>
              <button type="submit" :disabled="saving" class="btn-save">
                {{ saving ? "Mentés..." : "Mentés" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Új hozzávaló modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click.self="closeCreateModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Új hozzávaló</h2>
          <button @click="closeCreateModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveNewIngredient">
            <div class="form-grid">
              <div class="form-section">
                <h4>Alap információk</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label>Hozzávaló neve *</label>
                    <input type="text" v-model="newForm.ingredientName" required maxlength="255">
                  </div>
                  <div class="form-group">
                    <label>Típus *</label>
                    <select v-model="newForm.ingredientType" required>
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

              <div class="form-section">
                <h4>Tápértékek (100g-ra)</h4>
                <div class="form-row nutrition-grid">
                  <div class="form-group"><label>Energia (kcal)</label><input type="number" v-model.number="newForm.energy" min="0" step="1"></div>
                  <div class="form-group"><label>Fehérje (g)</label><input type="number" v-model.number="newForm.protein" min="0" step="1"></div>
                  <div class="form-group"><label>Szénhidrát (g)</label><input type="number" v-model.number="newForm.carbohydrate" min="0" step="1"></div>
                  <div class="form-group"><label>Zsír (g)</label><input type="number" v-model.number="newForm.fat" min="0" step="1"></div>
                  <div class="form-group"><label>Cukor (g)</label><input type="number" v-model.number="newForm.sugar" min="0" step="1"></div>
                  <div class="form-group"><label>Rost (g)</label><input type="number" v-model.number="newForm.fiber" min="0" step="1"></div>
                  <div class="form-group"><label>Nátrium (mg)</label><input type="number" v-model.number="newForm.sodium" min="0" step="1"></div>
                </div>
              </div>

              <div class="form-section">
                <h4>Allergének</h4>
                <p class="section-description">Válaszd ki az összetevőhöz tartozó allergéneket</p>
                <div class="allergens-grid">
                  <div v-for="allergen in allergens" :key="allergen.id" class="allergen-item" :class="{ selected: newForm.allergen_ids.includes(allergen.id) }" @click="toggleAllergen(newForm, allergen.id)">
                    <input type="checkbox" :id="'new-allergen-' + allergen.id" :value="allergen.id" v-model="newForm.allergen_ids" class="allergen-checkbox" @click.stop>
                    <label :for="'new-allergen-' + allergen.id" class="allergen-label" @click.stop>
                      <img v-if="allergen.icon_url" :src="getAllergenIconUrl(allergen.icon)" :alt="allergen.allergenName" class="allergen-icon">
                      <span class="allergen-name">{{ allergen.allergenName }}</span>
                    </label>
                  </div>
                  <div v-if="allergens.length === 0" class="no-allergens">Nincsenek allergének az adatbázisban.</div>
                </div>
              </div>

              <div class="form-section">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="newForm.isAvailable">
                  <span>Hozzávaló elérhető a felhasználásra</span>
                </label>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" @click="closeCreateModal" class="btn-cancel">Mégse</button>
              <button type="submit" :disabled="saving" class="btn-save">{{ saving ? "Mentés..." : "Létrehozás" }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Törlés megerősítés modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal modal-small">
        <div class="modal-header">
          <h2>Hozzávaló törlése</h2>
          <button @click="closeDeleteModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body text-center">
          <h3 class="delete-title">Biztosan törölni szeretnéd?</h3>
          <p class="delete-message">A(z) <strong>{{ ingredientToDelete?.ingredientName }}</strong> hozzávalót nem lehet visszaállítani.</p>
          
          <div v-if="deleteError" class="error-message-container">
            <div class="error-content">
              <p class="error-title">Nem törölhető!</p>
              <p class="error-message">{{ deleteError }}</p>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeDeleteModal" class="btn-cancel">Mégse</button>
            <button v-if="!deleteError || !deleteError.includes('használatban')" @click="deleteIngredient" :disabled="deleting" class="btn-delete-confirm">
              {{ deleting ? "Törlés..." : "Törlés" }}
            </button>
            <button v-if="deleteError && deleteError.includes('használatban')" @click="closeDeleteModal" class="btn-close-error">Bezárás</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import axios from 'axios'
import AuthService from '../../../services/AuthService'
import { addAlert } from '../../auth/AppAlert.vue'
import { showConfirm } from '../../auth/AppConfirm.vue'
import Pagination from '../../layout/Pagination.vue'


const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(0)
const perPage = ref(25)

// Reaktív állapotok
const ingredients = ref([])
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const selectedIngredients = ref([])
const ingredientToDelete = ref(null)
const ingredientToEdit = ref(null)
const deleteError = ref('')
const allergens = ref([])
const searchTimeout = ref(null)


const filters = ref({
  search: '',
  type: 'all',
  availability: 'all',
})


const editForm = ref({
  id: null,
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


const allSelected = computed(() => {
  return ingredients.value.length > 0 && 
         ingredients.value.every(ingredient => selectedIngredients.value.includes(ingredient.id))
})


watch(showCreateModal, (newVal) => {
  if (newVal) document.body.style.overflow = 'hidden'
  else if (!showEditModal.value && !showDeleteModal.value) document.body.style.overflow = ''
})

watch(showEditModal, (newVal) => {
  if (newVal) document.body.style.overflow = 'hidden'
  else if (!showCreateModal.value && !showDeleteModal.value) document.body.style.overflow = ''
})

watch(showDeleteModal, (newVal) => {
  if (newVal) document.body.style.overflow = 'hidden'
  else if (!showCreateModal.value && !showEditModal.value) document.body.style.overflow = ''
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
})


function onSearchInput() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    loadIngredients()
  }, 300)
}


onMounted(() => {
  loadIngredients()
  loadAllergens()
})

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
    
    if (params.type === 'all') delete params.type
    if (params.availability === 'all') delete params.availability
    if (params.search === '') delete params.search
    
    const response = await axios.get('/kitchen/ingredients', { params })
    
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
      total.value = data.total || 0
    } else if (Array.isArray(data)) {
      ingredients.value = data
    } else {
      ingredients.value = []
    }
  } catch (error) {
    console.error('Hiba a hozzávalók betöltésekor:', error)
    addAlert({ message: 'Hiba a hozzávalók betöltésekor', type: 'error' })
    ingredients.value = []
  } finally {
    loading.value = false
  }
}

function changePage(page) {
  if (page < 1 || page > lastPage.value) return
  currentPage.value = page
  loadIngredients()
  window.scrollTo(0, 0)
}

function changePerPage(newPerPage) {
  perPage.value = newPerPage
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
  if (index === -1) form.allergen_ids.push(allergenId)
  else form.allergen_ids.splice(index, 1)
}

function openEditModal(ingredient) {
  ingredientToEdit.value = ingredient
  editForm.value = {
    id: ingredient.id,
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
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
  ingredientToEdit.value = null
  resetEditForm()
}

function resetEditForm() {
  editForm.value = {
    id: null,
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

function closeCreateModal() {
  showCreateModal.value = false
  resetNewForm()
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

function closeDeleteModal() {
  showDeleteModal.value = false
  ingredientToDelete.value = null
  deleteError.value = ''
}

async function saveIngredient() {
  saving.value = true
  
  try {
    const data = { ...editForm.value }
    delete data.id
    delete data.allergen_ids
    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === '') delete data[key]
    })

    await axios.put(`/kitchen/ingredients/${editForm.value.id}`, data)
    await axios.put(`/kitchen/ingredients/${editForm.value.id}/allergens`, {
      allergen_ids: editForm.value.allergen_ids
    })
    
    await loadIngredients()
    closeEditModal()
    addAlert({ message: 'Hozzávaló sikeresen frissítve!', type: 'success' })
  } catch (error) {
    console.error('Hiba a mentés során:', error)
    let errorMessage = 'Hiba történt a mentés során'
    if (error.response?.data?.message) errorMessage = error.response.data.message
    else if (error.response?.data?.errors) errorMessage = Object.values(error.response.data.errors).flat().join(', ')
    addAlert({ message: errorMessage, type: 'error' })
  } finally {
    saving.value = false
  }
}

function getAllergenIconUrl(iconPath) {
  if (!iconPath) return 'https://via.placeholder.com/16x16/3498db/ffffff?text=❓'
  const cleanPath = iconPath.replace(/^\//, '')
  return `http://localhost:8000/images/allergens/${cleanPath.split('/').pop()}`
}

async function saveNewIngredient() {
  saving.value = true
  
  try {
    const data = { ...newForm.value }
    
    await axios.post('/kitchen/ingredients', data)
    
    await loadIngredients()
    closeCreateModal()
    addAlert({ message: 'Új hozzávaló sikeresen létrehozva!', type: 'success' })
  } catch (error) {
    console.error('Hiba a mentés során:', error)
    let errorMessage = 'Hiba történt a mentés során'
    if (error.response?.data?.message) errorMessage = error.response.data.message
    else if (error.response?.data?.errors) errorMessage = Object.values(error.response.data.errors).flat().join(', ')
    addAlert({ message: errorMessage, type: 'error' })
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
    await axios.delete(`/kitchen/ingredients/${ingredientToDelete.value.id}`)
    
    await loadIngredients()
    closeDeleteModal()
    addAlert({ message: 'Hozzávaló sikeresen törölve!', type: 'success' })
    
  } catch (error) {
    console.error('Hiba a törlés során:', error)
    
    if (error.response?.status === 404) {
      await loadIngredients()
      closeDeleteModal()
      addAlert({ message: 'Hozzávaló már nem létezik.', type: 'info' })
      return
    }
    
    if (error.response?.status === 409) {
      deleteError.value = error.response.data?.message || 'Ez a hozzávaló nem törölhető, mert használatban van.'
    } else {
      deleteError.value = error.response?.data?.message || 'Hiba történt a törlés során.'
    }
  } finally {
    deleting.value = false
  }
}

async function toggleAvailability(ingredient) {
  const confirmed = await showConfirm({
    message: `Biztosan ${ingredient.isAvailable ? 'nem elérhetővé' : 'elérhetővé'} szeretnéd tenni ezt a hozzávalót?`
  })
  if (!confirmed) return

  try {
    await axios.put(`/kitchen/ingredients/${ingredient.id}`, { isAvailable: !ingredient.isAvailable })
    await loadIngredients()
    addAlert({ message: `Hozzávaló ${!ingredient.isAvailable ? 'elérhetővé' : 'nem elérhetővé'} téve!`, type: 'success' })
  } catch (error) {
    console.error('Hiba az állapot változtatás során:', error)
    addAlert({ message: 'Hiba történt az állapot változtatása során', type: 'error' })
  }
}

async function bulkUpdateAvailability(isAvailable) {
  if (selectedIngredients.value.length === 0) return
  
  const confirmed = await showConfirm({
    message: `${selectedIngredients.value.length} hozzávaló ${isAvailable ? 'elérhetővé' : 'nem elérhetővé'} tétele?`
  })
  if (!confirmed) return
  
  try {
    await axios.post('/kitchen/ingredients/bulk-availability', {
      ingredient_ids: selectedIngredients.value,
      isAvailable
    })
    await loadIngredients()
    clearSelection()
    addAlert({ message: `${selectedIngredients.value.length} hozzávaló sikeresen ${isAvailable ? 'elérhetővé' : 'nem elérhetővé'} téve!`, type: 'success' })
  } catch (error) {
    console.error('Hiba a tömeges frissítés során:', error)
    addAlert({ message: 'Hiba történt a tömeges frissítés során', type: 'error' })
  }
}

function toggleSelectAll() {
  if (allSelected.value) selectedIngredients.value = []
  else selectedIngredients.value = ingredients.value.map(ingredient => ingredient.id)
}

function clearSelection() {
  selectedIngredients.value = []
}
</script>


<style scoped>

.ingredients {
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

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  background: white;
  cursor: pointer;
}

.filter-select:focus {
  outline: none;
  border-color: #f0a24a;
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

.bulk-actions {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
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
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  font-size: 0.8rem;
  transition: all 0.2s;
}

.btn-bulk-available { background: #d4edda; color: #155724; }
.btn-bulk-available:hover { background: #c3e6cb; }
.btn-bulk-unavailable { background: #fff3cd; color: #856404; }
.btn-bulk-unavailable:hover { background: #ffeaa7; }
.btn-bulk-clear { background: #e9ecef; color: #6c757d; }
.btn-bulk-clear:hover { background: #dee2e6; }


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


.table-wrapper {
  overflow-x: auto;
  border-radius: 12px;
  border: 1px solid #eee;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.data-table th {
  background: #f0a24a;
  color: #7b2c2c;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
}

.data-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

.data-table tr:hover {
  background: #fef9ef;
}

.checkbox-col {
  width: 40px;
  text-align: center;
}

.select-all, .select-item {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.ingredient-name {
  font-weight: 500;
  color: #333;
}

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}
.type-meat { background: #f8d7da; color: #721c24; }
.type-fish { background: #d1ecf1; color: #0c5460; }
.type-dairy { background: #fff3cd; color: #856404; }
.type-vegetable { background: #d4edda; color: #155724; }
.type-fruit { background: #e2d9f3; color: #4a3f69; }
.type-spice { background: #f8d7da; color: #721c24; }
.type-other { background: #e9ecef; color: #6c757d; }

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}
.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }

.actions-cell {
  white-space: nowrap;
}

.actions-group {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  padding: 0.4rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-edit {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-edit:hover {
  background: #bbdef5;
}

.btn-activate {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-activate:hover {
  background: #c8e6c9;
}

.btn-deactivate {
  background: #fff3cd;
  color: #856404;
}

.btn-deactivate:hover {
  background: #ffeaa7;
}

.btn-delete {
  background: #ffebee;
  color: #c62828;
}

.btn-delete:hover {
  background: #ffcdd2;
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

.modal-small {
  max-width: 520px;
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

.form-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section h4 {
  margin: 0 0 0.75rem 0;
  color: #333;
  font-size: 0.9rem;
  font-weight: 600;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #eee;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.nutrition-grid {
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  font-size: 0.75rem;
  color: #666;
  text-transform: uppercase;
}

.form-group input,
.form-group select {
  padding: 0.6rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #f0a24a;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: normal;
  font-size: 0.85rem;
}

.checkbox-label input {
  width: 18px;
  height: 18px;
  cursor: pointer;
}


.allergens-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.allergen-item {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  background: white;
}

.allergen-item:hover {
  border-color: #f0a24a;
}

.allergen-item.selected {
  background: #fff7e6;
  border-color: #f0a24a;
}

.allergen-checkbox {
  margin-right: 0.5rem;
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.allergen-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  flex: 1;
}

.allergen-icon {
  width: 20px;
  height: 20px;
  object-fit: contain;
}

.allergen-name {
  font-size: 0.8rem;
  color: #333;
}

.section-description {
  color: #888;
  font-size: 0.75rem;
  margin: 0 0 0.5rem 0;
}

.no-allergens {
  grid-column: 1 / -1;
  padding: 1rem;
  text-align: center;
  background: #f8f9fa;
  border-radius: 8px;
  color: #888;
  font-size: 0.8rem;
}

.btn-cancel {
  padding: 0.6rem 1.25rem;
  background: #e9ecef;
  color: #495057;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-cancel:hover {
  background: #dee2e6;
}

.btn-save {
  padding: 0.6rem 1.25rem;
  background: #1fa317;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.btn-save:hover:not(:disabled) {
  background: #158a0f;
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-delete-confirm {
  padding: 0.6rem 1.25rem;
  background: #dc3545;
  color: white;
  border: none;
  border-radius: 8px;
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

.btn-close-error {
  padding: 0.6rem 1.25rem;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

.btn-close-error:hover {
  background: #5a6268;
}



.delete-title {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  font-size: 1.25rem;
}

.delete-message {
  margin: 0 0 1.5rem 0;
  color: #666;
  line-height: 1.5;
}

.error-message-container {
  text-align: left;
  background: #fff3f3;
  border: 1px solid #ffcdd2;
  border-radius: 12px;
  padding: 1rem;
  margin: 1rem 0;
}



.error-title {
  font-weight: bold;
  color: #721c24;
  margin: 0 0 0.5rem 0;
}

.error-message {
  color: #856404;
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

.error-suggestions {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid #ffc107;
  margin-top: 1rem;
}

.error-suggestions p {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.error-suggestions ul {
  margin: 0;
  padding-left: 1.5rem;
}

.error-suggestions li {
  margin-bottom: 0.25rem;
  color: #6c757d;
  font-size: 0.8rem;
}

.text-center {
  text-align: center;
}


@media (max-width: 768px) {
  .ingredients {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
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

  .filter-select {
    width: 100%;
  }

  .btn-primary {
    width: 100%;
    text-align: center;
  }

  .bulk-header {
    flex-direction: column;
    text-align: center;
  }

  .bulk-buttons {
    justify-content: center;
  }

  .actions-group {
    flex-wrap: wrap;
    justify-content: center;
  }

  .form-row,
  .nutrition-grid {
    grid-template-columns: 1fr;
  }

  .allergens-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .pagination {
    flex-direction: column;
    gap: 1rem;
  }

  .per-page-selector {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .title {
    font-size: 1.25rem;
  }

  .data-table th,
  .data-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }

  .btn-action {
    padding: 0.3rem 0.5rem;
    font-size: 0.7rem;
  }

  .allergen-item {
    padding: 0.4rem;
  }

  .allergen-name {
    font-size: 0.7rem;
  }

  .allergen-icon {
    width: 16px;
    height: 16px;
  }
}
</style>