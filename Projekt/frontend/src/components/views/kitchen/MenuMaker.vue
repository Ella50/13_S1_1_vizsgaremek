<template>
  <div class="menu-maker">
    <h1>Menük kezelése</h1>

    <div class="actions">
      <button @click="openCreateModal">➕ Új menü hozzáadása</button>
      <button @click="openEditModal">✏️ Menü szerkesztése</button>
    </div>

    <!-- MODAL -->
     <pre>{{ meals }}</pre>
    <div v-if="showModal" class="modal-overlay">
      <div class="modal">
        <!-- STEP 1: Dátum -->
        <div v-if="step === 1">
          <h2>Dátum kiválasztása</h2>
          <select v-model="selectedDate" @change="onDateSelected">
            <option disabled value="">Válassz dátumot</option>
            <option v-for="date in availableDates" :key="date" :value="date">
              {{ date }}
            </option>
          </select>
          <button :disabled="!selectedDate" @click="step = 2">Tovább</button>
        </div>

        <!-- STEP 2: Ételek -->
        <div v-else>
          <h2>Ételek kiválasztása</h2>

          <div class="meal-types">
            <button
              v-for="type in mealTypes"
              :key="type"
              :class="{ active: activeMealType === type }"
              @click="activeMealType = type"
            >
              {{ type }}
            </button>
          </div>

          <div class="meals">
            <div v-for="meal in filteredMeals" :key="meal.id" class="meal-item">
              <span>{{ meal.name }}</span>
              <button @click="addMeal(meal)">✔</button>
            </div>
          </div>

          <h3>Kiválasztott ételek (húzd a sorrendhez)</h3>
          <ul class="sortable">
            <li
              v-for="(m, index) in selectedMeals"
              :key="m.id"
              draggable="true"
              @dragstart="onDragStart(index)"
              @dragover.prevent
              @drop="onDrop(index)"
            >
              ☰ {{ m.name }} ({{ m.mealType }})
              <button @click="removeMeal(m)">❌</button>
            </li>
          </ul>

          <button @click="saveMenu" :disabled="!canSave">Mentés</button>
          <button class="secondary" @click="closeModal">Mégse</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'

import api from '@/services/api'


export default {
  data() {
    return {
      dragIndex: null,
      showModal: false,
      step: 1,
      isEdit: false,
      availableDates: [],
      selectedDate: '',
      meals: [],
      mealTypes: ['Leves', 'Főétel', 'Egyéb'],
      activeMealType: 'Leves',
      selectedMeals: {
        soup: null,
        optionA: null,
        optionB: null
      },
      activeCategory: 'Leves', 
      meals: [],
      selectedDate: null,
      
    }
  },

  computed: {
    filteredMeals() {
    return this.meals.filter(
      meal => meal.meal_type === this.activeCategory
    )
  }


  },

  methods: {
    onDragStart(index) {
      this.dragIndex = index
    },

    onDrop(index) {
      const movedItem = this.selectedMeals.splice(this.dragIndex, 1)[0]
      this.selectedMeals.splice(index, 0, movedItem)
      this.dragIndex = null
    },
    async openCreateModal() {
      this.isEdit = false
      await this.fetchAvailableDates()
      await this.fetchMeals()
      this.resetState()
      this.showModal = true
    },

    async openEditModal() {
      this.isEdit = true
      await this.fetchExistingDates()
      await this.fetchMeals()
      this.resetState()
      this.showModal = true
    },

    resetState() {
      this.step = 1
      this.selectedDate = ''
      this.selectedMeals = []
      this.activeMealType = 'Leves'
    },

    closeModal() {
      this.showModal = false
      this.resetState()
    },

    async fetchAvailableDates() {
      const res = await AuthService.api.get('/menu/available-dates')
      this.availableDates = res.data
    },

    async fetchExistingDates() {
      const res = await AuthService.api.get('/menu/existing-dates')
      this.availableDates = res.data
    },

    async fetchMeals() {
      try {
        const res = await AuthService.api.get('/kitchen/meals')
        this.meals = Array.isArray(res.data) ? res.data : res.data.meals
      } catch (e) {
        console.error('Ételek betöltése sikertelen', e)
      }
    },

    async onDateSelected() {
      if (this.isEdit && this.selectedDate) {
        const res = await AuthService.api.get(`/menu/${this.selectedDate}`)
        this.selectedMeals = res.data.menu_items.map(i => i.meal)
      }
    },

    addMeal(meal) {
      if (!this.selectedMeals.find(m => m.id === meal.id)) {
        this.selectedMeals.push(meal)
      }
    },

    removeMeal(meal) {
      if (confirm(`Biztosan eltávolítod: ${meal.name}?`)) {
        this.selectedMeals = this.selectedMeals.filter(m => m.id !== meal.id)
      }
    },

    canSave() {
      return (
    this.selectedDate &&
    this.selectedSoup &&
    this.selectedOptionA &&
    this.selectedOptionB
  )
},


    async saveMenu() {
      if (!this.canSave) return

      try {
        await api.post('/menu', {
          day: this.selectedDate,
          soup: this.selectedSoup.id,
          optionA: this.selectedOptionA.id,
          optionB: this.selectedOptionB.id
        })

        this.closeModal()
        await this.fetchMenus()
      } catch (e) {
        console.error('Mentés sikertelen', e)
      }
    }

  }
}
</script>

<style scoped>
.sortable { list-style: none; padding: 0; }
.sortable li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  margin-bottom: 0.5rem;
  background: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 6px;
  cursor: grab;
}
.sortable li:active { cursor: grabbing; }

.menu-maker { padding: 2rem; }
.actions { display: flex; gap: 1rem; margin-bottom: 2rem; }
button { padding: 0.5rem 1rem; }
button.active { background: #3498db; color: white; }
.secondary { background: #eee; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
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

.meal-types { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.meal-item { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
</style>
