<template>
  <div class="menu-maker">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Menük kezelése</h1>
        
        <div class="header-controls">
          <div class="month-selector">
            <button 
              @click="previousMonth" 
              class="nav-btn"
              :disabled="!canGoToPreviousMonth"
            >
              ←
            </button>
            
            <button @click="showMonthPicker = true" class="current-month">
              {{ currentMonthDisplay }}
            </button>
            
            <button 
              @click="nextMonth" 
              class="nav-btn"
              :disabled="!canGoToNextMonth"
            >
              →
            </button>
          </div>

          <button @click="fetchMenus" class="btn-primary" :disabled="loading">
            {{ loading ? 'Betöltés...' : 'Frissítés' }}
          </button>
          
          <button @click="openCreateModal" class="btn-primary">
            + Új menü
          </button>
        </div>
      </div>

      <!-- Hónap választó modal -->
      <div v-if="showMonthPicker" class="modal-overlay" @click.self="showMonthPicker = false">
        <div class="modal modal-small">
          <div class="modal-header">
            <h2>Válassz hónapot</h2>
            <button @click="showMonthPicker = false" class="modal-close">×</button>
          </div>
          <div class="modal-body">
            <div class="months-grid">
              <button
                v-for="month in availableMonths"
                :key="`${month.year}-${month.month}`"
                @click="selectMonth(month)"
                :class="{ active: isCurrentMonth(month) }"
                class="month-btn"
              >
                {{ month.display }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Menük betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="fetchMenus" class="btn-secondary">Újrapróbálkozás</button>
      </div>

      <div v-else>
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Dátum</th>
                <th>Leves</th>
                <th>A opció</th>
                <th>B opció</th>
                <th>Egyéb</th>
                <th>Műveletek</th>
              </tr>
            </thead>
            <tbody>
               <tr 
                  v-for="menu in menus.data" 
                  :key="menu.id"
                  :class="{ 'past-menu': isPastDate(menu.day) }"
                >
                <td class="date-cell">{{ formatDate(menu.day) }}</td>
                <td>{{ menu.soup?.mealName || '—' }}</td>
                <td>{{ menu.optionA?.mealName || '—' }}</td>
                <td>{{ menu.optionB?.mealName || '—' }}</td>
                <td>{{ menu.other?.mealName || '—' }}</td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button 
                      @click="openEditModal(menu)" 
                      class="btn-icon btn-edit" 
                      title="Szerkesztés"
                      :disabled="!canEditMenu(menu)"
                    >
                      ✎
                    </button>
                    <button 
                      @click="deleteMenu(menu.id)" 
                      class="btn-delete" 
                      title="Törlés"
                      :disabled="!canDeleteMenu(menu)"
                    >
                      Törlés
                    </button>
                  </div>
                  <span v-if="!canEditMenu(menu)" class="past-menu-badge">
                    Lejárt
                  </span>
              </td>
              </tr>
              <tr v-if="menus.data && menus.data.length === 0">
                <td colspan="6" class="empty-row">Nincsenek menük ebben a hónapban</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="menus.data && menus.data.length > 0 && menus.last_page > 1" class="pagination">
          <button @click="changePage(menus.current_page - 1)" :disabled="menus.current_page === 1" class="btn-pagination">
            Előző
          </button>
          <span class="pagination-info">
            {{ menus.current_page }} / {{ menus.last_page }} oldal
            ({{ menus.total }} menü)
          </span>
          <button @click="changePage(menus.current_page + 1)" :disabled="menus.current_page === menus.last_page" class="btn-pagination">
            Következő
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal (ugyanaz marad) -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ isEditMode ? 'Menü szerkesztése' : 'Új menü létrehozása' }}</h2>
          <button @click="closeModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <div v-if="modalLoading" class="loading-state-small">
            <div class="spinner-small"></div>
            <p>Adatok betöltése...</p>
          </div>
          
          <form v-else @submit.prevent="saveMenu" class="edit-form">
            <div class="form-group">
              <label>Dátum *</label>
              
              <!-- Új menü létrehozásakor: legördülő lista -->
              <select 
                v-if="!isEditMode"
                v-model="formData.day" 
                required 
                class="form-control"
              >
                <option disabled value="">Válassz dátumot</option>
                <option v-for="date in availableDates" :key="date" :value="date">
                  {{ formatDate(date) }}
                </option>
              </select>
              
              <input 
                v-else
                :value="formatDate(formData.day)" 
                type="text" 
                class="form-control" 
                disabled
              >
              
              <small v-if="isEditMode" class="form-hint">Szerkesztésnél a dátum nem módosítható</small>
            </div>

            <div class="meal-selection">
              <div class="meal-types">
                <button
                  v-for="type in mealTypes"
                  :key="type.slot"
                  :class="['meal-type-btn', { active: activeSlot === type.slot }]"
                  @click="activeSlot = type.slot; activeCategory = type.category"
                  type="button"
                >
                  {{ type.label }}
                </button>
              </div>

              <div class="search-box">
                <input
                  v-model="mealSearch"
                  class="search-input"
                  placeholder="Keresés étel név szerint..."
                  type="text"
                  maxlength="255" 
                >
                <span class="search-icon">🔍</span>
              </div>

              <div class="meals-scroll">
                <div
                  v-for="meal in filteredMeals"
                  :key="meal.id"
                  class="meal-item"
                >
                  <span class="meal-name">{{ meal.mealName }}</span>
                  <button type="button" class="btn-action btn-add" @click="addMeal(meal)">Hozzáad</button>
                </div>
                
                <div v-if="filteredMeals.length === 0" class="empty-state-small">
                  Nincs találat a keresésre
                </div>
              </div>

              <div class="selected-meals">
                <div class="selected-header">
                  <strong>Kiválasztott ételek</strong>
                </div>
                <div class="selected-list">
                  <div class="selected-item" :class="{ empty: !selectedMeals.soup }">
                    <span class="selected-label">Leves:</span>
                    <span class="selected-value">{{ selectedMeals.soup?.mealName || '—' }}</span>
                    <button v-if="selectedMeals.soup" type="button" class="btn-remove" @click="removeMeal('soup')">✕</button>
                  </div>
                  <div class="selected-item" :class="{ empty: !selectedMeals.optionA }">
                    <span class="selected-label">A opció:</span>
                    <span class="selected-value">{{ selectedMeals.optionA?.mealName || '—' }}</span>
                    <button v-if="selectedMeals.optionA" type="button" class="btn-remove" @click="removeMeal('optionA')">✕</button>
                  </div>
                  <div class="selected-item" :class="{ empty: !selectedMeals.optionB }">
                    <span class="selected-label">B opció:</span>
                    <span class="selected-value">{{ selectedMeals.optionB?.mealName || '—' }}</span>
                    <button v-if="selectedMeals.optionB" type="button" class="btn-remove" @click="removeMeal('optionB')">✕</button>
                  </div>
                  <div class="selected-item" :class="{ empty: !selectedMeals.other }">
                    <span class="selected-label">Egyéb:</span>
                    <span class="selected-value">{{ selectedMeals.other?.mealName || '—' }}</span>
                    <button v-if="selectedMeals.other" type="button" class="btn-remove" @click="removeMeal('other')">✕</button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="modal-footer">
              <button type="button" @click="closeModal" class="btn-cancel">Mégse</button>
              <button type="submit" :disabled="!canSave || saving" class="btn-save">
                {{ saving ? "Mentés..." : (isEditMode ? "Frissítés" : "Létrehozás") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'
import { addAlert } from '../../auth/AppAlert.vue'
import { showConfirm } from '../../auth/AppConfirm.vue'

export default {
  data() {
    return {
      // Táblázatos nézet adatai
      menus: { data: [] },
      loading: false,
      error: '',
      currentPage: 1,
      
      // Hónap választó
      selectedMonth: null,
      showMonthPicker: false,
      
      // Modal állapotok
      showModal: false,
      isEditMode: false,
      modalLoading: false,
      saving: false,
      editingMenuId: null,
      
      // Dátum választás
      availableDates: [],
      
      // Ételek listája
      meals: [],
      mealSearch: '',
      mealTypes: [
        { slot: 'soup', category: 'Leves', label: 'Leves' },
        { slot: 'optionA', category: 'Főétel', label: 'A opció' },
        { slot: 'optionB', category: 'Főétel', label: 'B opció' },
        { slot: 'other', category: 'Egyéb', label: 'Egyéb' }
      ],
      activeSlot: 'soup',
      activeCategory: 'Leves',
      
      // Form adatok
      formData: {
        day: '',
        soup: null,
        optionA: null,
        optionB: null,
        other: null
      }
    }
  },

  computed: {
    selectedMeals() {
      return {
        soup: this.formData.soup,
        optionA: this.formData.optionA,
        optionB: this.formData.optionB,
        other: this.formData.other
      }
    },

    filteredMeals() {
      const search = this.mealSearch.toLowerCase()
      const forbiddenIds = new Set()
      
      if (this.activeSlot === 'optionA' && this.formData.optionB) {
        forbiddenIds.add(this.formData.optionB.id)
      }
      if (this.activeSlot === 'optionB' && this.formData.optionA) {
        forbiddenIds.add(this.formData.optionA.id)
      }

      return this.meals
        .filter(meal =>
          meal.category === this.activeCategory &&
          meal.mealName.toLowerCase().includes(search) &&
          !forbiddenIds.has(meal.id)
        )
        .sort((a, b) => a.mealName.localeCompare(b.mealName))
    },

    canSave() {
      return (
        this.formData.day &&
        this.formData.soup &&
        this.formData.optionA &&
        this.formData.optionB
      )
    },

    availableMonths() {
      const months = []
      const currentYear = new Date().getFullYear()
      
      for (let year = currentYear - 1; year <= currentYear + 1; year++) {
        for (let month = 1; month <= 12; month++) {
          months.push({
            year,
            month,
            display: this.getMonthNameFull(month) + ' ' + year
          })
        }
      }
      
      return months
    },
    
    currentMonthDisplay() {
      if (!this.selectedMonth) return 'Válassz hónapot'
      return this.getMonthNameFull(this.selectedMonth.month) + ' ' + this.selectedMonth.year
    },
    
    canGoToPreviousMonth() {
      if (!this.selectedMonth) return false
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      return index > 0
    },
    
    canGoToNextMonth() {
      if (!this.selectedMonth) return false
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      return index < this.availableMonths.length - 1
    }
  },

  watch: {
    showModal(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    },
    showMonthPicker(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden'
      } else {
        document.body.style.overflow = ''
      }
    }
  },

  mounted() {
    this.initMonth()
    this.fetchMenus()
  },

  methods: {
    initMonth() {
      const now = new Date()
      this.selectedMonth = { year: now.getFullYear(), month: now.getMonth() + 1 }
    },

    async fetchMenus() {
      if (!this.selectedMonth) return
      
      this.loading = true
      this.error = ''
      
      try {
        const params = {
          page: this.currentPage,
          year: this.selectedMonth.year,
          month: this.selectedMonth.month
        }
        
        const response = await AuthService.api.get('/menu/list', { params })
        
        let data = response.data
        if (typeof data === 'string') {
          data = data.replace(/^\uFEFF/, '')
          data = JSON.parse(data)
        }
        
        if (data.success && data.data) {
          // Biztosítsuk, hogy a dátum string formátumú legyen
          if (data.data.data) {
            data.data.data = data.data.data.map(menu => ({
              ...menu,
              day: menu.day ? (typeof menu.day === 'object' ? menu.day.toISOString?.().split('T')[0] : menu.day) : null
            }))
          }
          this.menus = data.data
        } else {
          this.error = 'Érvénytelen válasz formátum'
          this.menus = { data: [] }
        }
      } catch (error) {
        console.error('Hiba:', error)
        this.error = error.response?.data?.message || 'Hiba történt a menük betöltése során'
        this.menus = { data: [] }
      } finally {
        this.loading = false
      }
    },

    async fetchAvailableDates() {
      try {
        const res = await AuthService.api.get('/menu/available-dates')
        let data = res.data
        if (typeof data === 'string') {
          data = data.replace(/^\uFEFF/, '')
          data = JSON.parse(data)
        }
        
        // Csak a mai és jövőbeli dátumokat mutassuk
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        
        this.availableDates = Array.isArray(data) 
          ? data.filter(date => {
              const dateObj = new Date(date)
              dateObj.setHours(0, 0, 0, 0)
              return dateObj >= today
            })
          : []
          
      } catch (e) {
        console.error('Dátumok betöltése sikertelen', e)
        this.availableDates = []
      }
    },

    async fetchMeals() {
      try {
        const res = await AuthService.api.get('/kitchen/meals')
        let data = res.data
        if (typeof data === 'string') {
          data = data.replace(/^\uFEFF/, '')
          data = JSON.parse(data)
        }
        this.meals = Array.isArray(data) ? data : data.meals || []
      } catch (e) {
        console.error('Ételek betöltése sikertelen', e)
        addAlert({ message: 'Hiba az ételek betöltésekor', type: 'error' })
        this.meals = []
      }
    },

    resetForm() {
      this.formData = {
        day: '',
        soup: null,
        optionA: null,
        optionB: null,
        other: null
      }
      this.mealSearch = ''
      this.activeSlot = 'soup'
      this.activeCategory = 'Leves'
      this.editingMenuId = null
      this.isEditMode = false
    },

    async openCreateModal() {
      this.isEditMode = false
      this.editingMenuId = null
      this.resetForm()
      this.modalLoading = true
      this.showModal = true
      
      await Promise.all([
        this.fetchAvailableDates(),
        this.fetchMeals()
      ])
      
      this.modalLoading = false
    },

    async openEditModal(menu) {
      console.log('Szerkesztendő menü:', menu)
      
      this.isEditMode = true
      this.editingMenuId = menu.id
      this.modalLoading = true
      this.showModal = true
      
      await this.fetchMeals()
      
      // A dátumot egyszerűen átvesszük, nem kell formázni
      this.formData = {
        day: menu.day,
        soup: menu.soup || null,
        optionA: menu.optionA || null,
        optionB: menu.optionB || null,
        other: menu.other || null
      }
      
      console.log('Beállított formData:', this.formData)
      console.log('formData.day értéke:', this.formData.day)
      console.log('formData.day típusa:', typeof this.formData.day)
      
      this.modalLoading = false
    },
    closeModal() {
      this.showModal = false
      this.resetForm()
    },

    addMeal(meal) {
      this.formData[this.activeSlot] = meal
    },

    removeMeal(slot) {
      this.formData[slot] = null
    },

    async saveMenu() {
      if (!this.canSave) return

      if (this.isEditMode && this.isPastDate(this.formData.day)) {
        addAlert({ 
          message: 'Múltbeli menü nem módosítható!', 
          type: 'error' 
        })
        return
      }

      const confirmed = await showConfirm({ 
        message: this.isEditMode ? 'Biztosan módosítja a menüt?' : 'Biztosan létrehozza az új menüt?' 
      })
      if (!confirmed) return
      
      this.saving = true

      const payload = {
        day: this.formData.day,
        soup: this.formData.soup.id,
        optionA: this.formData.optionA.id,
        optionB: this.formData.optionB.id,
        other: this.formData.other?.id || null
      }

      try {
        if (this.isEditMode && this.editingMenuId) {
          await AuthService.api.put(`/menu/${this.editingMenuId}`, payload)
          addAlert({ message: 'A menü módosítása sikeresen el lett mentve.', type: 'success' })
        } else {
          await AuthService.api.post('/menu', payload)
          addAlert({ message: 'Az új menü sikeresen létre lett hozva.', type: 'success' })
        }

        this.closeModal()
        await this.fetchMenus()
      } catch (e) {
        console.error('Mentés sikertelen', e)
        addAlert({ message: 'A mentés/szerkesztés sikertelen volt. Próbálja újra!', type: 'error' })
      } finally {
        this.saving = false
      }
    },

    async deleteMenu(menuId) {
      // Először keresd meg a menüt az adatok között
      const menu = this.menus.data.find(m => m.id === menuId)
      
      if (!menu) {
        addAlert({ message: 'Menü nem található!', type: 'error' })
        return
      }
      
      // Ellenőrizzük, hogy nem múltbeli-e
      if (this.isPastDate(menu.day)) {
        addAlert({ 
          message: 'Múltbeli menü nem törölhető!', 
          type: 'error' 
        })
        return
      }
      
      const confirmed = await showConfirm({ 
        message: 'Biztosan törölni szeretné ezt a menüt?',
        title: 'Menü törlése'
      })
      if (!confirmed) return
      
      try {
        await AuthService.api.delete(`/menu/${menuId}`)
        addAlert({ message: 'Menü sikeresen törölve!', type: 'success' })
        await this.fetchMenus()
      } catch (e) {
        console.error('Törlés sikertelen', e)
        addAlert({ message: 'A törlés sikertelen volt.', type: 'error' })
      }
    },
    changePage(page) {
      if (page < 1 || page > this.menus.last_page) return
      this.currentPage = page
      this.fetchMenus()
      window.scrollTo(0, 0)
    },

    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('hu-HU')
    },

    getMonthNameFull(month) {
      const months = [
        'Január', 'Február', 'Március', 'Április', 'Május', 'Június',
        'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'
      ]
      return months[month - 1]
    },

    previousMonth() {
      if (!this.canGoToPreviousMonth) return
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      this.selectMonth(this.availableMonths[index - 1])
    },
    
    nextMonth() {
      if (!this.canGoToNextMonth) return
      const index = this.availableMonths.findIndex(
        m => m.year === this.selectedMonth.year && m.month === this.selectedMonth.month
      )
      this.selectMonth(this.availableMonths[index + 1])
    },
    
    selectMonth(month) {
      this.selectedMonth = { year: month.year, month: month.month }
      this.showMonthPicker = false
      this.currentPage = 1
      this.fetchMenus()
    },
    
    isCurrentMonth(month) {
      return this.selectedMonth?.year === month.year && 
             this.selectedMonth?.month === month.month
    },

    isPastDate(dateString) {
      if (!dateString) return false
      const today = new Date()
      today.setHours(0, 0, 0, 0)
      const menuDate = new Date(dateString)
      menuDate.setHours(0, 0, 0, 0)
      return menuDate < today
    },
    
    canEditMenu(menu) {
      return !this.isPastDate(menu.day)
    },
    
    canDeleteMenu(menu) {
      return !this.isPastDate(menu.day)
    },
  }
}
</script>

<style scoped>
/* A meglévő stílusok + a monthpicker stílusok */
.menu-maker {
  padding: 2rem;
  max-width: 1400px;
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

.header-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Month selector stílusok */
.month-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 30px;
  padding: 0.25rem;
}

.nav-btn {
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  color: #8a1212;
  font-size: 1.2rem;
  cursor: pointer;
  border-radius: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.nav-btn:hover:not(:disabled) {
  background: #f0a24a;
  color: white;
}

.nav-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.current-month {
  padding: 0.5rem 1rem;
  background: transparent;
  border: none;
  color: #333;
  font-weight: 500;
  cursor: pointer;
  min-width: 160px;
  text-align: center;
  font-size: 0.85rem;
}

.current-month:hover {
  color: #f0a24a;
}

/* Months grid a modalban */
.months-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.month-btn {
  padding: 0.75rem 0.5rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  color: #333;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
  font-size: 0.85rem;
}

.month-btn:hover {
  background: #fef9ef;
  border-color: #f0a24a;
  transform: translateY(-1px);
}

.month-btn.active {
  background: #f0a24a;
  border-color: #f0a24a;
  color: #7b2c2c;
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

.btn-primary:hover:not(:disabled) {
  background: #158a0f;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.menu-maker {
  padding: 2rem;
  max-width: 1400px;
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

.date-cell {
  font-weight: 500;
  white-space: nowrap;
}

.actions-cell {
  white-space: nowrap;
}

.actions-group {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-edit {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-edit:hover {
  background: #bbdef5;
}

.btn-delete {
    background: #f8d7da;
  color: #a1656b;
  border: none;
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-delete:hover {
  background: #ffcdd2;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 2rem;
  margin-top: 2rem;
  padding: 1rem;
}

.btn-pagination {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-pagination:hover:not(:disabled) {
  background: #f0a24a;
  color: white;
  border-color: #f0a24a;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: #666;
  font-size: 0.85rem;
}

.empty-row {
  text-align: center;
  color: #888;
  padding: 3rem !important;
}

/* Modal styles */
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
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
  margin-top: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.form-group label {
  font-weight: 500;
  font-size: 0.8rem;
  color: #666;
  text-transform: uppercase;
}

.form-control {
  padding: 0.6rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
}

.form-control:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.form-hint {
  font-size: 0.7rem;
  color: #888;
}

.meal-selection {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.meal-types {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.meal-type-btn {
  flex: 1;
  padding: 0.6rem 1rem;
  background: #f5f5f5;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.meal-type-btn:hover {
  background: #e9ecef;
}

.meal-type-btn.active {
  background: #f0a24a;
  color: #7b2c2c;
}

.meals-scroll {
  max-height: 250px;
  overflow-y: auto;
  border: 1px solid #eee;
  border-radius: 12px;
  padding: 0.5rem;
}

.meal-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.6rem 0.75rem;
  margin-bottom: 0.25rem;
  background: #f8f9fa;
  border-radius: 8px;
  transition: background 0.2s;
}

.meal-item:hover {
  background: #fef9ef;
}

.meal-name {
  font-weight: 500;
  color: #333;
  font-size: 0.85rem;
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

.btn-add {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-add:hover {
  background: #c8e6c9;
}

.empty-state-small {
  text-align: center;
  padding: 2rem;
  color: #888;
  font-size: 0.8rem;
}

.selected-meals {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1rem;
}

.selected-header {
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
  color: #8a1212;
  border-bottom: 1px solid #eee;
  padding-bottom: 0.5rem;
}

.selected-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.5rem;
}

.selected-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.4rem 0.6rem;
  background: white;
  border-radius: 6px;
  font-size: 0.8rem;
}

.selected-item.empty {
  opacity: 0.6;
  background: #f5f5f5;
}

.selected-label {
  font-weight: 500;
  color: #666;
}

.selected-value {
  color: #333;
  font-weight: 500;
}

.btn-remove {
  background: none;
  border: none;
  color: #c62828;
  cursor: pointer;
  font-size: 0.8rem;
  padding: 0 0.25rem;
  opacity: 0.6;
}

.btn-remove:hover {
  opacity: 1;
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

.loading-state-small {
  text-align: center;
  padding: 2rem;
}

.spinner-small {
  width: 30px;
  height: 30px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #1fa317;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
/* Múltbeli menük stílusa */
.data-table tr.past-menu {
  opacity: 0.7;
  background-color: #f5f5f5;
}

.data-table tr.past-menu:hover {
  background-color: #eeeeee;
}

.past-menu-badge {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  background: #e9ecef;
  color: #6c757d;
  border-radius: 20px;
  font-size: 0.65rem;
  font-weight: 500;
  margin-left: 0.5rem;
}

.btn-icon:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-icon:disabled:hover {
  transform: none;
  background: inherit;
}
/* Responsive */
@media (max-width: 768px) {
  .menu-maker {
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

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .meal-types {
    flex-direction: column;
  }

  .meal-type-btn {
    width: 100%;
  }

  .selected-list {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-wrap: wrap;
  }

  .modal-footer button {
    flex: 1;
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

  .meal-item {
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
  }

  .btn-action {
    width: 100%;
  }

  .selected-item {
    flex-direction: column;
    text-align: center;
    gap: 0.25rem;
  }

  .btn-icon {
    width: 28px;
    height: 28px;
    font-size: 0.85rem;
  }
}



</style>