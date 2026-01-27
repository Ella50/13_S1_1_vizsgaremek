<template>
  <div class="menu-maker">
    <h1>Menük kezelése</h1>

    <div class="actions">
      <button @click="openCreateModal">+ Új menü hozzáadása</button>
      <button @click="openEditModal">✏️ Menü szerkesztése</button>
    </div>
    <!--<pre>{{ meals }}</pre>-->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal">
        <div v-if="step === 1">
          <h2>Dátum kiválasztása</h2>
          <select v-model="selectedDate" @change="onDateSelected">
            <option disabled value="">Válassz dátumot</option>
            <option v-for="date in availableDates" :key="date" :value="date">
              {{ date }}
            </option>
          </select>
          <button :disabled="!selectedDate" @click="step = 2" style="background-color: green;">Tovább</button>
          <button class="secondary" @click="closeModal" style="background-color: lightcoral;">Mégse</button>
        </div>

        <div v-else class="step-2">

        <h2 style="text-align: center; margin-top: 5px;">Ételek kiválasztása - {{ selectedDate }}</h2>
        <div class="meal-types">
          <button
            v-for="type in mealTypes"
            :key="type.slot"
            :class="{ active: activeSlot === type.slot }"
            @click="
              activeSlot = type.slot;
              activeCategory = type.category
            "
          >
            {{ type.label }}
          </button>
        </div>

        <input
          v-model="mealSearch"
          class="meal-search"
          placeholder="Keresés étel név szerint..."
        />

        <div class="meals-scroll">
          <div
            v-for="meal in filteredMeals"
            :key="meal.id"
            class="meal-item"
          >
            <span class="meal-name">{{ meal.mealName }}</span>
            <button class="meal-btn" @click="addMeal(meal)">Hozzáad</button>
          </div>
        </div>



        <div class="modal-footer">
          <div class="selected-meals-fixed">
            <strong>Kiválasztott ételek</strong>
            <ul>
              <li>Leves: {{ selectedMeals.soup?.mealName || '—' }}</li>
              <li>A opció: {{ selectedMeals.optionA?.mealName || '—' }}</li>
              <li>B opció: {{ selectedMeals.optionB?.mealName || '—' }}</li>
              <li>Egyéb: {{ selectedMeals.other?.mealName || '—' }}</li>
            </ul>
          </div>
          <button @click="saveMenu" :disabled="!canSave" style="background-color: green;">Mentés</button>
          <button class="secondary" @click="closeModal" style="background-color: lightcoral;">Mégse</button>
        </div>
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
    showModal: false,
    step: 1,
    isEdit: false,
    mealSearch: '',


    availableDates: [],
    selectedDate: '',

    meals: [],

    mealTypes: [
      { slot: 'soup', category: 'Leves', label: 'Leves' },
      { slot: 'optionA', category: 'Főétel', label: 'A opció' },
      { slot: 'optionB', category: 'Főétel', label: 'B opció' },
      { slot: 'other', category: 'Egyéb', label: 'Egyéb' }
    ],

    activeSlot: 'soup',
    activeCategory: 'Leves',

    editingMenuId: null, 
    selectedMeals: {
      soup: null,
      optionA: null,
      optionB: null,
      other: null
    }
  }
},


  computed: {
    filteredMeals() {
    const search = this.mealSearch.toLowerCase()

    // ha A-ban van már főétel, azt B-ben ne lehessen és fordítva
    const forbiddenIds = new Set()
    if (this.activeSlot === 'optionA' && this.selectedMeals.optionB) {
      forbiddenIds.add(this.selectedMeals.optionB.id)
    }
    if (this.activeSlot === 'optionB' && this.selectedMeals.optionA) {
      forbiddenIds.add(this.selectedMeals.optionA.id)
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
        this.selectedDate &&
        this.selectedMeals.soup &&
        this.selectedMeals.optionA &&
        this.selectedMeals.optionB
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
      this.isEdit = false
      this.editingMenuId = null
      this.resetMealsOnly()
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
      this.resetMealsOnly()

      try {
        const res = await AuthService.api.get(`/menu/${this.selectedDate}`)

        if (res.data) {
          this.isEdit = true
          this.editingMenuId = res.data.id

          this.selectedMeals.soup = this.meals.find(m => m.id === res.data.soup)
          this.selectedMeals.optionA = this.meals.find(m => m.id === res.data.optionA)
          this.selectedMeals.optionB = this.meals.find(m => m.id === res.data.optionB)
        }
      } catch (e) {
        this.isEdit = false
        this.editingMenuId = null
      }
    },


resetMealsOnly() {
  this.selectedMeals = {
    soup: null,
    optionA: null,
    optionB: null,
    other: null
  }
},

   addMeal(meal) {
      this.selectedMeals[this.activeSlot] = meal
    },



    removeMeal(meal) {
      if (confirm(`Biztosan eltávolítod: ${meal.mealName}?`)) {
        this.selectedMeals = this.selectedMeals.filter(m => m.id !== meal.id)
      }
    },

   


   async saveMenu() {
      if (!this.canSave) return

      const payload = {
        day: this.selectedDate,
        soup: this.selectedMeals.soup.id,
        optionA: this.selectedMeals.optionA.id,
        optionB: this.selectedMeals.optionB.id
      }

      try {
        if (this.isEdit && this.editingMenuId) {
          await AuthService.api.put(`/menu/${this.editingMenuId}`, payload)
          alertbox.render({
            alertIcon: 'success',
            title: 'Sikeres szerkesztés',
            message: 'A menü módosítása sikeresen el lett mentve.',
            btnTitle: 'Rendben',
            themeColor: '#000000',
            btnColor: '#4CAF50',
            border: true
          })

        } else {
          await AuthService.api.post('/menu', payload)
          alertbox.render({
            alertIcon: 'success',
            title: 'Sikeres mentés',
            message: 'Az új menü sikeresen létre lett hozva.',
            btnTitle: 'Rendben',
            themeColor: '#000000',
            btnColor: '#7CFC00',
            border: true
          })
        }

        this.closeModal()

      } catch (e) {
        console.error('Mentés sikertelen', e)

        alertbox.render({
          alertIcon: 'error',
          title: 'Hiba',
          message: 'A mentés/szerkesztés sikertelen volt. Próbálja újra!',
          btnTitle: 'Ok',
          themeColor: '#000000',
          btnColor: '#ff4d4f',
          border: true
        })
      }
    }


  }
}
</script>

<style scoped>

button{
  width: 20%;
}

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
  /*display: block !important;*/

  display: flex;
  flex-direction: column;
}

.step-2{
  padding: 0 1.5rem;
}

.modal-footer {
  
  position: sticky;
  bottom: 0;
  background: #fff;
  padding: 1rem 1.5rem;
  border-top: 1px solid #ddd;
  display: block;
  justify-content: flex-end;
  gap: 1rem;
}


.meal-types {
  display: flex;
  gap: 0.5rem;
  margin: 1rem 1.5rem 1rem;
}

.meal-types button {
  flex: 1;
  padding: 1.5rem 1rem;
}

.meals {
  margin: 0 1.5rem;
}

.meal-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.4rem;
  background: #f6f6f6;
  border-radius: 6px;
}

.meal-name {
  padding-left: 0.5rem;
  font-weight: 500;
}

.meal-btn {
  width: 10rem;
  padding: 0.4rem 0.6rem;
  border-radius: 6px;
  background: green;
  color: white;
  font-size: 1.1rem;
}

.meals-scroll {
  max-height: calc(90vh - 300px);
  overflow-y: auto;
  margin-bottom: 1rem;
}

.meal-search {
  width: 100%;
  padding: 0.5rem;
  margin: 1rem 0;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.selected-meals-fixed {
  position: sticky;
  bottom: 70px;
  background: #fafafa;
  padding: 0.75rem 1.5rem;
  border-top: 1px solid #ddd;
}



</style>
