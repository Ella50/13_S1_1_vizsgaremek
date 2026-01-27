<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Fejléc -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Havi Étrend Rendelés</h1>
      <p class="text-gray-600">
        Válassza ki a következő hónap menüjeit. Minden naphoz válasszon A vagy B opciót.
      </p>
    </div>

    <!-- Hónapválasztó -->
    <div class="mb-6 bg-white rounded-lg shadow-md p-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <label for="monthSelect" class="block text-sm font-medium text-gray-700 mb-2">
            Hónap kiválasztása
          </label>
          <div class="flex items-center space-x-4">
            <button
              @click="previousMonth"
              class="p-2 rounded-full hover:bg-gray-100"
              :disabled="isCurrentMonth"
            >
              <ChevronLeftIcon class="h-5 w-5 text-gray-600" />
            </button>
            
            <select
              id="monthSelect"
              v-model="selectedMonth"
              @change="loadMonthlyMenu"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
            
            <button
              @click="nextMonth"
              class="p-2 rounded-full hover:bg-gray-100"
            >
              <ChevronRightIcon class="h-5 w-5 text-gray-600" />
            </button>
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Állapot szűrők -->
          <div class="flex space-x-2">
            <button
              v-for="filter in statusFilters"
              :key="filter.id"
              @click="toggleStatusFilter(filter.id)"
              class="px-3 py-1 rounded-full text-sm transition-colors"
              :class="getStatusFilterClass(filter.id)"
            >
              {{ filter.label }}
            </button>
          </div>

          <!-- Gyorsválasztó gombok -->
          <div class="flex space-x-2">
            <button
              @click="selectAllA"
              class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg text-sm hover:bg-blue-200"
            >
              Minden A
            </button>
            <button
              @click="selectAllB"
              class="px-3 py-1 bg-green-100 text-green-700 rounded-lg text-sm hover:bg-green-200"
            >
              Minden B
            </button>
            <button
              @click="clearAll"
              class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200"
            >
              Töröl
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Figyelmeztetés ha nincs jogosultság -->
    <div v-if="!isEligibleToOrder" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
      <div class="flex">
        <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 flex-shrink-0" />
        <div class="ml-3">
          <p class="text-sm text-yellow-700">
            Ön nem jogosult rendelésre. Kérjük, vegye fel a kapcsolatot az adminisztrációval.
          </p>
        </div>
      </div>
    </div>

    <!-- Betöltés állapota -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      <p class="mt-4 text-gray-600">Havi menük betöltése...</p>
    </div>

    <!-- Üres állapot -->
    <div v-else-if="filteredDailyMenus.length === 0 && !loading" class="text-center py-12 bg-gray-50 rounded-lg">
      <CalendarDaysIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-lg font-medium text-gray-900">Nincs elérhető menü</h3>
      <p class="mt-2 text-sm text-gray-500">
        A kiválasztott hónapra nem áll rendelkezésre menü.
      </p>
    </div>

    <!-- Havi rendelési táblázat -->
    <div v-else class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Dátum
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nap
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Menü A
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Menü B
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">
                Választás
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Ár
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Állapot
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Műveletek
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="day in filteredDailyMenus" 
              :key="day.date"
              :class="getRowClass(day)"
            >
              <!-- Dátum -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ formatDate(day.date) }}
                </div>
                <div v-if="isHoliday(day.date)" class="text-xs text-red-600">
                  Ünnepnap
                </div>
                <div v-if="isWeekend(day.date)" class="text-xs text-blue-600">
                  Hétvége
                </div>
              </td>

              <!-- Nap neve -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getDayClass(day.date)">
                  {{ getDayName(day.date) }}
                </span>
              </td>

              <!-- Menü A -->
              <td class="px-6 py-4">
                <div v-if="day.menuA" class="space-y-1">
                  <div class="font-medium text-gray-900">{{ day.menuA.name }}</div>
                  <div class="text-sm text-gray-600">{{ day.menuA.description }}</div>
                  <div v-if="day.menuA.allergens && day.menuA.allergens.length > 0" class="mt-1">
                    <span class="text-xs text-red-600 font-medium">Allergének:</span>
                    <span class="text-xs text-gray-600 ml-1">{{ day.menuA.allergens.join(', ') }}</span>
                  </div>
                </div>
                <div v-else class="text-gray-400 text-sm italic">
                  Nincs menü
                </div>
              </td>

              <!-- Menü B -->
              <td class="px-6 py-4">
                <div v-if="day.menuB" class="space-y-1">
                  <div class="font-medium text-gray-900">{{ day.menuB.name }}</div>
                  <div class="text-sm text-gray-600">{{ day.menuB.description }}</div>
                  <div v-if="day.menuB.allergens && day.menuB.allergens.length > 0" class="mt-1">
                    <span class="text-xs text-red-600 font-medium">Allergének:</span>
                    <span class="text-xs text-gray-600 ml-1">{{ day.menuB.allergens.join(', ') }}</span>
                  </div>
                </div>
                <div v-else class="text-gray-400 text-sm italic">
                  Nincs menü
                </div>
              </td>

              <!-- Választás -->
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <div v-if="day.hasMenu" class="flex justify-center space-x-2">
                  <!-- A opció -->
                  <button
                    @click="selectOption(day.date, 'A')"
                    :disabled="!day.menuA || day.isPast || !isOrderable(day)"
                    class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors"
                    :class="getOptionButtonClass(day, 'A')"
                  >
                    <span class="font-semibold">A</span>
                  </button>

                  <!-- B opció -->
                  <button
                    @click="selectOption(day.date, 'B')"
                    :disabled="!day.menuB || day.isPast || !isOrderable(day)"
                    class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors"
                    :class="getOptionButtonClass(day, 'B')"
                  >
                    <span class="font-semibold">B</span>
                  </button>

                  <!-- Lemondás -->
                  <button
                    v-if="day.selectedOption"
                    @click="cancelOrder(day.date)"
                    :disabled="day.isPast"
                    class="w-10 h-10 rounded-full flex items-center justify-center border-2 border-red-300 hover:border-red-500 hover:bg-red-50"
                    title="Lemondás"
                  >
                    <XMarkIcon class="h-5 w-5 text-red-500" />
                  </button>
                </div>
                <div v-else class="text-gray-400 text-sm">
                  Nincs menü
                </div>
              </td>

              <!-- Ár -->
              <td class="px-6 py-4 whitespace-nowrap">
                <div v-if="day.selectedOption && day.hasMenu" class="text-right">
                  <div class="font-bold text-gray-900">
                    {{ getPrice(day) }} Ft
                  </div>
                  <div class="text-xs text-gray-500">
                    {{ getUserPriceLabel() }}
                  </div>
                </div>
                <div v-else class="text-gray-400">
                  -
                </div>
              </td>

              <!-- Állapot -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClass(day)">
                  {{ getStatusText(day) }}
                </span>
              </td>

              <!-- Műveletek -->
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="flex space-x-2">
                  <button
                    v-if="day.menuA"
                    @click="showMenuDetails(day.menuA)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </button>
                  <button
                    v-if="day.menuB"
                    @click="showMenuDetails(day.menuB)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Összesítő panel -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Havi rendelés összesítése</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-blue-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-blue-800">Összes nap</div>
          <div class="text-2xl font-bold text-blue-900">{{ totalDays }}</div>
        </div>
        
        <div class="bg-green-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-green-800">Rendelt napok</div>
          <div class="text-2xl font-bold text-green-900">{{ selectedDaysCount }}</div>
        </div>
        
        <div class="bg-purple-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-purple-800">A opció</div>
          <div class="text-2xl font-bold text-purple-900">{{ optionACount }}</div>
        </div>
        
        <div class="bg-yellow-50 p-4 rounded-lg">
          <div class="text-sm font-medium text-yellow-800">B opció</div>
          <div class="text-2xl font-bold text-yellow-900">{{ optionBCount }}</div>
        </div>
      </div>

      <div class="mt-6 pt-6 border-t">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-600">Kiválasztott hónap:</p>
            <p class="font-medium">{{ formatMonth(selectedMonth) }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Becsült havi összeg:</p>
            <p class="text-2xl font-bold text-blue-600">{{ totalAmount }} Ft</p>
          </div>
        </div>
      </div>

      <!-- Megjegyzés -->
      <div class="mt-6">
        <label for="monthlyNote" class="block text-sm font-medium text-gray-700 mb-2">
          Havi megjegyzések (pl.: allergia, speciális diéta)
        </label>
        <textarea
          id="monthlyNote"
          v-model="monthlyNote"
          rows="2"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          placeholder="Speciális igények..."
        ></textarea>
      </div>

      <!-- Művelet gombok -->
      <div class="mt-8 flex flex-col sm:flex-row gap-4">
        <button
          @click="saveDraft"
          :disabled="loading"
          class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Piszkozat mentése
        </button>
        <button
          @click="submitMonthlyOrder"
          :disabled="selectedDaysCount === 0 || loading"
          class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          Havi rendelés leadása
        </button>
        <button
          @click="clearAll"
          class="flex-1 bg-red-100 text-red-700 px-6 py-3 rounded-lg font-medium hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
        >
          Minden törlése
        </button>
      </div>
    </div>

    <!-- Menü részletek modal -->
    <div v-if="selectedMenu" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">{{ selectedMenu.name }}</h3>
            <p class="text-gray-600">{{ selectedMenu.description }}</p>
          </div>
          <button @click="selectedMenu = null" class="text-gray-400 hover:text-gray-600">
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <div class="space-y-6">
          <div v-if="selectedMenu.allergens && selectedMenu.allergens.length > 0">
            <h4 class="font-medium text-gray-900 mb-2">Allergének:</h4>
            <div class="flex flex-wrap gap-2">
              <span v-for="allergen in selectedMenu.allergens" :key="allergen"
                    class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                {{ allergen }}
              </span>
            </div>
          </div>

          <div v-if="selectedMenu.ingredients">
            <h4 class="font-medium text-gray-900 mb-2">Összetevők:</h4>
            <p class="text-gray-700">{{ selectedMenu.ingredients }}</p>
          </div>

          <div v-if="selectedMenu.nutritionalInfo">
            <h4 class="font-medium text-gray-900 mb-2">Tápanyag információ:</h4>
            <pre class="text-gray-700 whitespace-pre-wrap">{{ selectedMenu.nutritionalInfo }}</pre>
          </div>
        </div>

        <div class="mt-8 pt-6 border-t">
          <button
            @click="selectedMenu = null"
            class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700"
          >
            Bezárás
          </button>
        </div>
      </div>
    </div>

    <!-- Sikeresség visszajelzés -->
    <div v-if="orderSuccess" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <CheckIcon class="h-6 w-6 text-green-600" />
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Havi rendelés sikeres!</h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ selectedDaysCount }} napra leadtad a rendelésedet.
          </p>
          <p class="mt-2 text-sm font-medium text-blue-600">
            Összeg: {{ totalAmount }} Ft
          </p>
          <div class="mt-6 space-y-3">
            <button
              @click="orderSuccess = false"
              class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700"
            >
              Rendben
            </button>
            <button
              @click="viewInvoice"
              class="w-full bg-gray-100 text-gray-800 px-4 py-2 rounded-lg font-medium hover:bg-gray-200"
            >
              Számla megtekintése
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'
import axios from 'axios'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  CalendarDaysIcon,
  ExclamationTriangleIcon,
  CheckIcon,
  EyeIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const store = useStore()
const router = useRouter()

// Reaktív állapotok
const selectedMonth = ref('')
const dailyMenus = ref([])
const monthlyNote = ref('')
const loading = ref(false)
const orderSuccess = ref(false)
const selectedMenu = ref(null)
const activeStatusFilters = ref(['available', 'ordered', 'past'])

// Felhasználó adatai
const currentUser = computed(() => store.state.auth.user)
const userType = computed(() => currentUser.value?.userType || 'Tanuló')

// Hónapok generálása
const availableMonths = computed(() => {
  const months = []
  const today = new Date()
  const currentYear = today.getFullYear()
  
  // Következő 6 hónap
  for (let i = 0; i < 6; i++) {
    const date = new Date(currentYear, today.getMonth() + i, 1)
    const monthValue = date.toISOString().slice(0, 7) // YYYY-MM
    const monthLabel = date.toLocaleDateString('hu-HU', { year: 'numeric', month: 'long' })
    
    months.push({ value: monthValue, label: monthLabel })
  }
  
  return months
})

const isCurrentMonth = computed(() => {
  const currentMonth = new Date().toISOString().slice(0, 7)
  return selectedMonth.value === currentMonth
})

// Állapot szűrők
const statusFilters = [
  { id: 'available', label: 'Rendelhető' },
  { id: 'ordered', label: 'Rendelt' },
  { id: 'past', label: 'Múlt' },
  { id: 'holiday', label: 'Ünnepnap' },
  { id: 'weekend', label: 'Hétvége' }
]

// Váltózók
const isEligibleToOrder = computed(() => {
  const eligibleTypes = ['Tanuló', 'Tanár', 'Dolgozó', 'Külsős']
  return eligibleTypes.includes(userType.value)
})

const filteredDailyMenus = computed(() => {
  return dailyMenus.value.filter(day => {
    // Állapot szűrők
    if (!activeStatusFilters.value.includes('past') && day.isPast) return false
    if (!activeStatusFilters.value.includes('ordered') && day.selectedOption) return false
    if (!activeStatusFilters.value.includes('available') && day.hasMenu && !day.isPast && !day.selectedOption) return false
    if (!activeStatusFilters.value.includes('holiday') && isHoliday(day.date)) return false
    if (!activeStatusFilters.value.includes('weekend') && isWeekend(day.date)) return false
    
    return true
  })
})

const selectedDaysCount = computed(() => {
  return dailyMenus.value.filter(day => day.selectedOption).length
})

const optionACount = computed(() => {
  return dailyMenus.value.filter(day => day.selectedOption === 'A').length
})

const optionBCount = computed(() => {
  return dailyMenus.value.filter(day => day.selectedOption === 'B').length
})

const totalDays = computed(() => {
  return dailyMenus.value.filter(day => day.hasMenu).length
})

const totalAmount = computed(() => {
  return dailyMenus.value.reduce((sum, day) => {
    if (day.selectedOption && day.hasMenu && !day.isPast) {
      return sum + getPrice(day)
    }
    return sum
  }, 0)
})

// Inicializálás
onMounted(() => {
  const today = new Date()
  const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1)
  selectedMonth.value = nextMonth.toISOString().slice(0, 7)
  loadMonthlyMenu()
})

// Függvények
async function loadMonthlyMenu() {
  if (!selectedMonth.value) return
  
  loading.value = true
  try {
    const response = await axios.get('/api/monthly-menu', {
      params: { 
        month: selectedMonth.value,
        userId: currentUser.value?.id 
      }
    })
    
    // Napi struktúra létrehozása
    dailyMenus.value = createDailyMenuStructure(response.data)
    
    // Korábbi rendelések betöltése
    await loadExistingOrders()
  } catch (error) {
    console.error('Hiba a havi menük betöltésekor:', error)
    dailyMenus.value = []
  } finally {
    loading.value = false
  }
}

function createDailyMenuStructure(menuItems) {
  const year = parseInt(selectedMonth.value.split('-')[0])
  const month = parseInt(selectedMonth.value.split('-')[1]) - 1
  
  // A hónap napjainak számolása
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  const dailyMenus = []
  
  for (let day = 1; day <= daysInMonth; day++) {
    const date = new Date(year, month, day)
    const dateString = date.toISOString().split('T')[0]
    const dayOfWeek = date.getDay()
    
    // Az adott nap menüinek szűrése
    const dayMenus = menuItems.filter(item => {
      const itemDate = new Date(item.available_date)
      return itemDate.toISOString().split('T')[0] === dateString
    })
    
    // A és B opciók szétválasztása (feltételezve, hogy van egy 'option_type' mező)
    const menuA = dayMenus.find(item => item.option_type === 'A')
    const menuB = dayMenus.find(item => item.option_type === 'B')
    
    const hasMenu = menuA || menuB
    const isPast = date < new Date(new Date().setHours(0, 0, 0, 0))
    const isHoliday = checkIfHoliday(date)
    const isWeekend = dayOfWeek === 0 || dayOfWeek === 6
    
    dailyMenus.push({
      date: dateString,
      menuA,
      menuB,
      hasMenu,
      isPast,
      isHoliday,
      isWeekend,
      selectedOption: null,
      orderStatus: null,
      orderId: null
    })
  }
  
  return dailyMenus
}

async function loadExistingOrders() {
  try {
    const response = await axios.get('/api/user-orders', {
      params: {
        userId: currentUser.value?.id,
        month: selectedMonth.value
      }
    })
    
    // Meglévő rendelések alkalmazása
    response.data.forEach(order => {
      const day = dailyMenus.value.find(d => d.date === order.orderDate)
      if (day) {
        day.selectedOption = order.selectedOption
        day.orderStatus = order.orderStatus
        day.orderId = order.id
      }
    })
  } catch (error) {
    console.error('Hiba a rendelések betöltésekor:', error)
  }
}

function selectOption(date, option) {
  const day = dailyMenus.value.find(d => d.date === date)
  if (day && !day.isPast && isOrderable(day)) {
    day.selectedOption = option
    day.orderStatus = 'Rendelve'
  }
}

function cancelOrder(date) {
  const day = dailyMenus.value.find(d => d.date === date)
  if (day && !day.isPast) {
    day.selectedOption = null
    day.orderStatus = null
    day.orderId = null
  }
}

function getPrice(day) {
  const menu = day.selectedOption === 'A' ? day.menuA : day.menuB
  if (!menu) return 0
  
  // Felhasználó típusának megfelelő ár
  const price = menu.prices?.find(p => p.userType === userType.value)
  return price?.amount || 0
}

function getOptionButtonClass(day, option) {
  const isSelected = day.selectedOption === option
  const menu = option === 'A' ? day.menuA : day.menuB
  
  if (!menu || day.isPast || !isOrderable(day)) {
    return 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
  }
  
  if (isSelected) {
    return option === 'A' 
      ? 'border-blue-500 bg-blue-100 text-blue-700' 
      : 'border-green-500 bg-green-100 text-green-700'
  }
  
  return 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
}

function getStatusClass(day) {
  if (day.isPast) {
    return 'bg-gray-100 text-gray-800'
  }
  
  if (day.selectedOption) {
    return day.orderStatus === 'Lemondva' 
      ? 'bg-red-100 text-red-800'
      : 'bg-green-100 text-green-800'
  }
  
  if (!day.hasMenu) {
    return 'bg-gray-100 text-gray-800'
  }
  
  return 'bg-blue-100 text-blue-800'
}

function getStatusText(day) {
  if (day.isPast) return 'Múlt'
  if (!day.hasMenu) return 'Nincs menü'
  if (day.selectedOption) {
    return day.orderStatus === 'Lemondva' ? 'Lemondva' : 'Rendelve'
  }
  return 'Rendelhető'
}

function getRowClass(day) {
  if (day.isPast) return 'bg-gray-50'
  if (!day.hasMenu) return 'bg-gray-50'
  if (day.isHoliday) return 'bg-red-50'
  if (day.isWeekend) return 'bg-blue-50'
  return ''
}

function getDayClass(date) {
  const day = new Date(date).getDay()
  if (day === 0 || day === 6) {
    return 'bg-blue-100 text-blue-800'
  }
  return 'bg-gray-100 text-gray-800'
}

function isOrderable(day) {
  if (!day.hasMenu) return false
  if (day.isPast) return false
  if (day.isHoliday) return false
  
  // További korlátozások, pl. rendelési határidő
  const today = new Date()
  const orderDate = new Date(day.date)
  const daysDiff = Math.floor((orderDate - today) / (1000 * 60 * 60 * 24))
  
  // Pl. maximum 14 nap előre lehet rendelni
  return daysDiff <= 14 && daysDiff >= 0
}

function checkIfHoliday(date) {
  // Ünnepnapok listája - valós implementációban adatbázisból kellene
  const holidays = [
    '01-01', // Újév
    '03-15', // 1848-as forradalom
    '05-01', // Munka ünnepe
    '08-20', // Szent István ünnepe
    '10-23', // 1956-os forradalom
    '11-01', // Mindenszentek
    '12-25', // Karácsony
    '12-26'  // Karácsony
  ]
  
  const monthDay = (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                   date.getDate().toString().padStart(2, '0')
  
  return holidays.includes(monthDay)
}

function isWeekend(dateString) {
  const date = new Date(dateString)
  return date.getDay() === 0 || date.getDay() === 6
}

function isHoliday(dateString) {
  const date = new Date(dateString)
  return checkIfHoliday(date)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('hu-HU')
}

function formatMonth(monthString) {
  const [year, month] = monthString.split('-')
  const date = new Date(year, month - 1, 1)
  return date.toLocaleDateString('hu-HU', { year: 'numeric', month: 'long' })
}

function getDayName(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('hu-HU', { weekday: 'short' })
}

function getUserPriceLabel() {
  const labels = {
    'Tanuló': 'Diák ár',
    'Tanár': 'Tanár ár',
    'Dolgozó': 'Dolgozó ár',
    'Külsős': 'Külsős ár'
  }
  return labels[userType.value] || 'Normál ár'
}

function toggleStatusFilter(filterId) {
  const index = activeStatusFilters.value.indexOf(filterId)
  if (index > -1) {
    activeStatusFilters.value.splice(index, 1)
  } else {
    activeStatusFilters.value.push(filterId)
  }
}

function getStatusFilterClass(filterId) {
  return activeStatusFilters.value.includes(filterId)
    ? 'bg-blue-100 text-blue-700'
    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
}

// Gyorsválasztók
function selectAllA() {
  dailyMenus.value.forEach(day => {
    if (day.menuA && !day.isPast && isOrderable(day) && !day.selectedOption) {
      day.selectedOption = 'A'
      day.orderStatus = 'Rendelve'
    }
  })
}

function selectAllB() {
  dailyMenus.value.forEach(day => {
    if (day.menuB && !day.isPast && isOrderable(day) && !day.selectedOption) {
      day.selectedOption = 'B'
      day.orderStatus = 'Rendelve'
    }
  })
}

function clearAll() {
  if (confirm('Biztosan törölni szeretné az összes kijelölést?')) {
    dailyMenus.value.forEach(day => {
      if (!day.isPast) {
        day.selectedOption = null
        day.orderStatus = null
      }
    })
  }
}

async function saveDraft() {
  loading.value = true
  try {
    const draftData = {
      month: selectedMonth.value,
      selections: dailyMenus.value
        .filter(day => day.selectedOption)
        .map(day => ({
          date: day.date,
          option: day.selectedOption,
          menuId: day.selectedOption === 'A' ? day.menuA?.id : day.menuB?.id
        })),
      note: monthlyNote.value
    }
    
    await axios.post('/api/order-drafts', draftData)
    alert('Piszkozat mentve!')
  } catch (error) {
    console.error('Hiba a piszkozat mentésekor:', error)
    alert('Hiba történt a mentés során.')
  } finally {
    loading.value = false
  }
}

async function submitMonthlyOrder() {
  if (selectedDaysCount.value === 0) {
    alert('Kérjük, válasszon ki legalább egy napot!')
    return
  }
  
  if (!confirm('Biztosan leadja a havi rendelést?')) {
    return
  }
  
  loading.value = true
  try {
    const orderData = {
      month: selectedMonth.value,
      orders: dailyMenus.value
        .filter(day => day.selectedOption && !day.isPast)
        .map(day => ({
          date: day.date,
          menuId: day.selectedOption === 'A' ? day.menuA?.id : day.menuB?.id,
          selectedOption: day.selectedOption,
          price: getPrice(day)
        })),
      note: monthlyNote.value,
      userId: currentUser.value?.id
    }
    
    const response = await axios.post('/api/monthly-orders', orderData)
    
    if (response.data.success) {
      orderSuccess.value = true
      // Frissítjük a rendelési állapotokat
      await loadExistingOrders()
    }
  } catch (error) {
    console.error('Hiba a rendelés leadásakor:', error)
    alert('Hiba történt a rendelés leadása során.')
  } finally {
    loading.value = false
  }
}

function showMenuDetails(menu) {
  selectedMenu.value = menu
}

function previousMonth() {
  if (!isCurrentMonth.value) {
    const [year, month] = selectedMonth.value.split('-').map(Number)
    const date = new Date(year, month - 2, 1)
    selectedMonth.value = date.toISOString().slice(0, 7)
    loadMonthlyMenu()
  }
}

function nextMonth() {
  const [year, month] = selectedMonth.value.split('-').map(Number)
  const date = new Date(year, month, 1)
  selectedMonth.value = date.toISOString().slice(0, 7)
  loadMonthlyMenu()
}

function viewInvoice() {
  router.push('/invoice-preview')
}
</script>