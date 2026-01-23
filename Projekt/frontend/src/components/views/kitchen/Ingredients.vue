<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Fejléc -->
    <div class="mb-8">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">Hozzávalók Kezelése</h1>
          <p class="text-gray-600">Új hozzávalók felvétele és meglévők szerkesztése</p>
        </div>
        <button
          @click="openCreateModal"
          class="add-ingredient-btn inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
        >
          <span class="mr-2">+</span>Új hozzávaló
        </button>
      </div>
    </div>

    <!-- Szűrők és keresés -->
    <div class="mb-6 bg-white rounded-lg shadow p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Keresés -->
        <div>
          <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
            Keresés
          </label>
          <div class="relative">
            <input
              type="text"
              id="search"
              v-model="filters.search"
              @input="debouncedLoadIngredients"
              placeholder="Hozzávaló neve..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <!-- Típus szűrő -->
        <div>
          <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
            Típus
          </label>
          <select
            id="type"
            v-model="filters.type"
            @change="loadIngredients"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
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
        <div>
          <label for="availability" class="block text-sm font-medium text-gray-700 mb-2">
            Elérhetőség
          </label>
          <select
            id="availability"
            v-model="filters.availability"
            @change="loadIngredients"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="all">Összes</option>
            <option value="available">Elérhető</option>
            <option value="unavailable">Nem elérhető</option>
          </select>
        </div>

        <!-- Rendezés -->
        <div>
          <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-2">
            Rendezés
          </label>
          <div class="flex gap-2">
            <select
              id="sort_by"
              v-model="filters.sort_by"
              @change="loadIngredients"
              class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="ingredientName">Név</option>
              <option value="ingredientType">Típus</option>
              <option value="energy">Energia</option>
              <option value="created_at">Létrehozás dátuma</option>
            </select>
            <button
              @click="toggleSortOrder"
              class="sort-btn px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
              :title="filters.sort_order === 'asc' ? 'Növekvő' : 'Csökkenő'"
            >
              {{ filters.sort_order === 'asc' ? '↑' : '↓' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Tömeges műveletek -->
      <div v-if="selectedIngredients.length > 0" class="mt-6 pt-6 border-t">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <span class="font-medium">{{ selectedIngredients.length }} hozzávaló kiválasztva</span>
          </div>
          <div class="flex space-x-2">
            <button
              @click="bulkUpdateAvailability(true)"
              class="px-4 py-2 bg-green-100 text-green-700 rounded-lg font-medium hover:bg-green-200"
            >
              Elérhetővé tesz
            </button>
            <button
              @click="bulkUpdateAvailability(false)"
              class="px-4 py-2 bg-red-100 text-red-700 rounded-lg font-medium hover:bg-red-200"
            >
              Nem elérhetővé tesz
            </button>
            <button
              @click="clearSelection"
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200"
            >
              Kijelölés törlése
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Betöltés állapota -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
      <p class="mt-4 text-gray-600">Hozzávalók betöltése...</p>
    </div>

    <!-- Üres állapot -->
    <div v-else-if="!loading && ingredients.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <span class="text-4xl">📦</span>
      <h3 class="mt-4 text-lg font-medium text-gray-900">Nincsenek hozzávalók</h3>
      <p class="mt-2 text-sm text-gray-500">
        Még nem lettek felvéve hozzávalók, vagy a szűrőknek nem felel meg egyetlen hozzávaló sem.
      </p>
      <button
        @click="openCreateModal"
        class="mt-6 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700"
      >
        <span class="mr-2">+</span>Új hozzávaló létrehozása
      </button>
    </div>

    <!-- Hozzávalók táblázata -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden mb-8">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="h-4 w-4 text-blue-600 rounded"
                />
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Név
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Típus
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Tápértékek (100g)
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
            <template v-for="ingredient in ingredients" :key="ingredient.id">
              <!-- Fő sor -->
              <tr class="hover:bg-gray-50 transition-colors">
                <!-- Kijelölés -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <input
                    type="checkbox"
                    :value="ingredient.id"
                    v-model="selectedIngredients"
                    class="h-4 w-4 text-blue-600 rounded"
                  />
                </td>

                <!-- Név -->
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <div>
                      <div class="font-medium text-gray-900">{{ ingredient.ingredientName }}</div>

                    </div>
                  </div>
                </td>

                <!-- Típus -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                        :class="getTypeClass(ingredient.ingredientType)">
                    {{ ingredient.ingredientType }}
                  </span>
                </td>

                <!-- Tápértékek -->
                <td class="px-6 py-4">
                  <div class="space-y-1">
                    <div v-if="ingredient.energy" class="text-sm">
                      <span class="font-medium">Energia:</span>
                      <span class="ml-2">{{ ingredient.energy }} kcal</span>
                    </div>
                    <div v-if="ingredient.protein" class="text-sm">
                      <span class="font-medium">Fehérje:</span>
                      <span class="ml-2">{{ ingredient.protein }}g</span>
                    </div>
                    <div v-if="ingredient.carbohydrate" class="text-sm">
                      <span class="font-medium">Szénhidrát:</span>
                      <span class="ml-2">{{ ingredient.carbohydrate }}g</span>
                    </div>
                    <div v-if="ingredient.fat" class="text-sm">
                      <span class="font-medium">Zsír:</span>
                      <span class="ml-2">{{ ingredient.fat }}g</span>
                    </div>
                    <div v-if="!ingredient.energy && !ingredient.protein && !ingredient.carbohydrate && !ingredient.fat" 
                         class="text-sm text-gray-400 italic">
                      Nincs megadva
                    </div>
                  </div>
                </td>

                <!-- Állapot -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                        :class="ingredient.isAvailable 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-red-100 text-red-800'">
                    <span class="h-2 w-2 rounded-full mr-2"
                          :class="ingredient.isAvailable ? 'bg-green-500' : 'bg-red-500'">
                    </span>
                    {{ ingredient.isAvailable ? 'Elérhető' : 'Nem elérhető' }}
                  </span>
                </td>

                <!-- Műveletek -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                        <!-- Szerkesztés gomb -->
                        <button
                        @click="toggleEdit(ingredient.id)"
                        class="action-button edit-button"
                        :title="expandedIngredient === ingredient.id ? 'Bezárás' : 'Szerkesztés'"
                        >
                        <svg v-if="expandedIngredient === ingredient.id" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                        <svg v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>{{ expandedIngredient === ingredient.id ? 'Bezár' : 'Szerkeszt' }}</span>
                        </button>

                        <!-- Elérhetőség váltó gomb -->
                        <button
                        @click="toggleAvailability(ingredient)"
                        :class="ingredient.isAvailable 
                            ? 'action-button availability-button-available' 
                            : 'action-button availability-button-unavailable'"
                        :title="ingredient.isAvailable ? 'Nem elérhetővé tesz' : 'Elérhetővé tesz'"
                        >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>{{ ingredient.isAvailable ? 'Nem elérhető' : 'Elérhetővé tesz' }}</span>
                        </button>

                        <!-- Törlés gomb -->
                        <button
                        @click="confirmDelete(ingredient)"
                        class="action-button delete-button"
                        title="Törlés"
                        >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Törlés</span>
                        </button>
                    </div>
                    </td>
              </tr>

              <!-- Lenyíló szerkesztő rész -->
              <tr v-if="expandedIngredient === ingredient.id" class="bg-blue-50">
                <td colspan="6" class="px-6 py-6">
                  <div class="bg-white rounded-lg border border-blue-200 p-6">
                    <div class="flex justify-between items-start mb-6">
                      <div>
                        <h3 class="text-xl font-bold text-gray-900">
                          {{ editingIngredient ? 'Hozzávaló szerkesztése' : 'Új hozzávaló' }}
                        </h3>
                        <p class="text-gray-600 mt-1">
                          Módosítsd a(z) {{ ingredient.ingredientName }} hozzávaló adatait
                        </p>
                      </div>
                      <div class="flex gap-2">
                        <button
                          @click="cancelEdit"
                          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
                        >
                          Mégse
                        </button>
                        <button
                          @click="saveIngredient(ingredient)"
                          :disabled="saving"
                          class="px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                          <span v-if="saving" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Mentés...
                          </span>
                          <span v-else>Mentés</span>
                        </button>
                      </div>
                    </div>

                    <form @submit.prevent="saveIngredient(ingredient)">
                      <div class="space-y-6">
                        <!-- Alap információk -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <!-- Név -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                              Hozzávaló neve *
                            </label>
                            <input
                              type="text"
                              v-model="editForm.ingredientName"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              placeholder="Pl.: Paradicsom"
                            />
                          </div>

                          <!-- Típus -->
                          <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                              Típus *
                            </label>
                            <select
                              v-model="editForm.ingredientType"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
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

                        <!-- Tápértékek -->
                        <div class="border-t border-gray-200 pt-6">
                          <h4 class="text-lg font-medium text-gray-900 mb-4">Tápértékek (100g-ra)</h4>
                          <p class="text-sm text-gray-600 mb-4">
                            Opcionális adatok, de ajánlott kitölteni az étel tervezéshez
                          </p>
                          
                          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <!-- Energia -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Energia (kcal)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.energy"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>

                            <!-- Fehérje -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Fehérje (g)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.protein"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>

                            <!-- Szénhidrát -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Szénhidrát (g)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.carbohydrate"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>

                            <!-- Zsír -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Zsír (g)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.fat"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>

                            <!-- Cukor -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cukor (g)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.sugar"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>

                            <!-- Rost -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Rost (g)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.fiber"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>

                            <!-- Nátrium -->
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nátrium (mg)
                              </label>
                              <input
                                type="number"
                                v-model.number="editForm.sodium"
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                              />
                            </div>
                          </div>
                        </div>

                        <!-- Elérhetőség -->
                        <div class="border-t border-gray-200 pt-6">
                          <div class="flex items-center">
                            <input
                              type="checkbox"
                              id="isAvailable"
                              v-model="editForm.isAvailable"
                              class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                            />
                            <label for="isAvailable" class="ml-3 text-sm font-medium text-gray-700">
                              Hozzávaló elérhető a felhasználásra
                            </label>
                          </div>
                          <p class="text-sm text-gray-500 mt-2 ml-8">
                            Ha nincs bejelölve, a hozzávaló nem lesz elérhető az új ételekhez
                          </p>
                        </div>
                      </div>
                    </form>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Hozzávaló létrehozás modal (csak új létrehozáshoz) -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-gray-900">
              Új hozzávaló
            </h3>
            <p class="text-gray-600 mt-1">
              Töltsd ki az új hozzávaló adatait
            </p>
          </div>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 text-2xl">
            ✕
          </button>
        </div>

        <form @submit.prevent="saveNewIngredient">
          <div class="space-y-6">
            <!-- Alap információk -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Név -->
              <div>
                <label for="newIngredientName" class="block text-sm font-medium text-gray-700 mb-2">
                  Hozzávaló neve *
                </label>
                <input
                  type="text"
                  id="newIngredientName"
                  v-model="newForm.ingredientName"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  placeholder="Pl.: Paradicsom"
                />
              </div>

              <!-- Típus -->
              <div>
                <label for="newIngredientType" class="block text-sm font-medium text-gray-700 mb-2">
                  Típus *
                </label>
                <select
                  id="newIngredientType"
                  v-model="newForm.ingredientType"
                  required
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
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

            <!-- Tápértékek -->
            <div class="border-t border-gray-200 pt-6">
              <h4 class="text-lg font-medium text-gray-900 mb-4">Tápértékek (100g-ra)</h4>
              <p class="text-sm text-gray-600 mb-4">
                Opcionális adatok, de ajánlott kitölteni az étel tervezéshez
              </p>
              
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Energia -->
                <div>
                  <label for="newEnergy" class="block text-sm font-medium text-gray-700 mb-2">
                    Energia (kcal)
                  </label>
                  <input
                    type="number"
                    id="newEnergy"
                    v-model.number="newForm.energy"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>

                <!-- Fehérje -->
                <div>
                  <label for="newProtein" class="block text-sm font-medium text-gray-700 mb-2">
                    Fehérje (g)
                  </label>
                  <input
                    type="number"
                    id="newProtein"
                    v-model.number="newForm.protein"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>

                <!-- Szénhidrát -->
                <div>
                  <label for="newCarbohydrate" class="block text-sm font-medium text-gray-700 mb-2">
                    Szénhidrát (g)
                  </label>
                  <input
                    type="number"
                    id="newCarbohydrate"
                    v-model.number="newForm.carbohydrate"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>

                <!-- Zsír -->
                <div>
                  <label for="newFat" class="block text-sm font-medium text-gray-700 mb-2">
                    Zsír (g)
                  </label>
                  <input
                    type="number"
                    id="newFat"
                    v-model.number="newForm.fat"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>

                <!-- Cukor -->
                <div>
                  <label for="newSugar" class="block text-sm font-medium text-gray-700 mb-2">
                    Cukor (g)
                  </label>
                  <input
                    type="number"
                    id="newSugar"
                    v-model.number="newForm.sugar"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>

                <!-- Rost -->
                <div>
                  <label for="newFiber" class="block text-sm font-medium text-gray-700 mb-2">
                    Rost (g)
                  </label>
                  <input
                    type="number"
                    id="newFiber"
                    v-model.number="newForm.fiber"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>

                <!-- Nátrium -->
                <div>
                  <label for="newSodium" class="block text-sm font-medium text-gray-700 mb-2">
                    Nátrium (mg)
                  </label>
                  <input
                    type="number"
                    id="newSodium"
                    v-model.number="newForm.sodium"
                    min="0"
                    step="1"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                  />
                </div>
              </div>
            </div>

            <!-- Elérhetőség -->
            <div class="border-t border-gray-200 pt-6">
              <div class="flex items-center">
                <input
                  type="checkbox"
                  id="newIsAvailable"
                  v-model="newForm.isAvailable"
                  class="h-5 w-5 text-blue-600 rounded focus:ring-blue-500"
                />
                <label for="newIsAvailable" class="ml-3 text-sm font-medium text-gray-700">
                  Hozzávaló elérhető a felhasználásra
                </label>
              </div>
              <p class="text-sm text-gray-500 mt-2 ml-8">
                Ha nincs bejelölve, a hozzávaló nem lesz elérhető az új ételekhez
              </p>
            </div>
          </div>

          <!-- Művelet gombok -->
          <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end gap-3">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Mégse
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="saving" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Mentés...
              </span>
              <span v-else>Létrehozás</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Törlés megerősítés modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg p-8 max-w-md w-full">
        <div class="text-center">
          <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.768 0L4.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          
          <h3 class="text-xl font-bold text-gray-900 mb-2">Hozzávaló törlése</h3>
          <p class="text-gray-600 mb-6">
            Biztosan törölni szeretnéd a 
            <span class="font-semibold text-gray-900">{{ ingredientToDelete?.ingredientName }}</span> 
            hozzávalót?
          </p>
          
          <p v-if="deleteError" class="text-sm text-red-600 bg-red-50 p-3 rounded-lg mb-4">
            {{ deleteError }}
          </p>

          <div class="flex gap-3 justify-center">
            <button
              @click="showDeleteModal = false"
              class="px-5 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Mégse
            </button>
            <button
              @click="deleteIngredient"
              :disabled="deleting"
              class="px-5 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span v-if="deleting" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Törlés...
              </span>
              <span v-else>Törlés</span>
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

// Szűrők
const filters = ref({
  search: '',
  type: 'all',
  availability: 'all',
  sort_by: 'ingredientName',
  sort_order: 'asc',
  page: 1,
  per_page: 20
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
  isAvailable: true
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
  isAvailable: true
})

// Computed properties
const allSelected = computed(() => {
  return ingredients.value.length > 0 && 
         ingredients.value.every(ingredient => selectedIngredients.value.includes(ingredient.id))
})

// Debounced keresés
const debouncedLoadIngredients = debounce(() => {
  filters.value.page = 1
  loadIngredients()
}, 500)

// Életciklus
onMounted(() => {
  loadIngredients()
})

// Függvények
async function loadIngredients() {
  loading.value = true
  try {
    const params = {
      ...filters.value,
      page: filters.value.page
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
    
    if (data && data.data) {
      ingredients.value = data.data
    } else if (Array.isArray(data)) {
      ingredients.value = data
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

function getTypeClass(type) {
  const classes = {
    'Hús': 'bg-red-100 text-red-800',
    'Hal': 'bg-blue-100 text-blue-800',
    'Tejtermék': 'bg-yellow-100 text-yellow-800',
    'Zöldség': 'bg-green-100 text-green-800',
    'Gyümölcs': 'bg-orange-100 text-orange-800',
    'Fűszer': 'bg-purple-100 text-purple-800',
    'Egyéb': 'bg-gray-100 text-gray-800'
  }
  
  return classes[type] || 'bg-gray-100 text-gray-800'
}

function toggleEdit(ingredientId) {
  if (expandedIngredient.value === ingredientId) {
    // Ha már nyitva van, bezárjuk
    expandedIngredient.value = null
    resetEditForm()
  } else {
    // Ha másik van nyitva, azt bezárjuk és újat nyitunk
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
        isAvailable: Boolean(ingredient.isAvailable)
      }
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
    isAvailable: true
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
    isAvailable: true
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

    await axios.put(`/kitchen/ingredients/${ingredient.id}`, data)
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
    // Távolítsuk el az üres értékeket
    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === '') {
        delete data[key]
      }
    })

    await axios.post('/kitchen/ingredients', data)
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
    await axios.delete(`/kitchen/ingredients/${ingredientToDelete.value.id}`)
    await loadIngredients()
    
    showDeleteModal.value = false
    ingredientToDelete.value = null
    
  } catch (error) {
    console.error('Hiba a törlés során:', error)
    
    if (error.response?.status === 409) {
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
/* Táblázat alapstílusok */
table {
    margin: auto;
    padding: auto;
    border: 4px solid black;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

th, td {
    margin: auto;
    padding: 16px 12px;
    border: 1px solid #e5e7eb;
    vertical-align: middle;
}

/* Művelet gombok konténere */
td:last-child {
  min-width: 200px;
}

td:last-child > div {
  display: flex;
  flex-wrap: nowrap;
  justify-content: flex-start;
  gap: 8px;
}

/* Egyedi művelet gomb stílusok */
.action-button {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  border: none;
  cursor: pointer;
}

.action-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Szerkesztés gomb */
.edit-button {
  background-color: #eff6ff;
  color: #1d4ed8;
}

.edit-button:hover {
  background-color: #dbeafe;
}

/* Elérhetőség gomb - elérhető állapotban */
.availability-button-available {
  background-color: #ffedd5;
  color: #ea580c;
}

.availability-button-available:hover {
  background-color: #fed7aa;
}

/* Elérhetőség gomb - nem elérhető állapotban */
.availability-button-unavailable {
  background-color: #dcfce7;
  color: #16a34a;
}

.availability-button-unavailable:hover {
  background-color: #bbf7d0;
}

/* Törlés gomb */
.delete-button {
  background-color: #fee2e2;
  color: #dc2626;
}

.delete-button:hover {
  background-color: #fecaca;
}

/* Gomb ikonok */
.action-button svg {
  width: 16px;
  height: 16px;
  margin-right: 4px;
}

/* Reszponszív design */
@media (max-width: 1024px) {
  td:last-child > div {
    flex-wrap: wrap;
    gap: 6px;
  }
  
  .action-button {
    padding: 4px 8px;
    font-size: 0.75rem;
  }
  
  .action-button span:not(.sr-only) {
    display: none;
  }
  
  .action-button svg {
    margin-right: 0;
  }
}

@media (max-width: 768px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
    border-width: 2px;
  }
  
  th, td {
    padding: 12px 8px;
  }
  
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
  
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  /* Művelet gombok még kisebb képernyőn */
  .action-button {
    padding: 3px 6px;
  }
}

/* Animációk a lenyíló részhez */
tr[class*="bg-blue-50"] {
  animation: slideDown 0.3s ease-out;
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

/* Egyéb gombok */
.add-ingredient-btn {
  width: 15%;
  min-width: 150px;
}

.sort-btn {
  width: 5%;
  min-width: 40px;
}

</style>