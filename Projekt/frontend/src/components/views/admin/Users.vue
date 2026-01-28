<template>
  <div class="admin-users">
    <div class="header">
      <h1>Felhasználók kezelése</h1>
      <div class="header-actions">
        <input 
          v-model="search" 
          type="text" 
          placeholder="Keresés"
          @input="fetchUsers">
        
        <select v-model="filters.userType" @change="fetchUsers">
          <option value="">Összes szerepkör</option>
          <option value="Tanuló">Tanuló</option>
          <option value="Tanár">Tanár</option>
          <option value="Admin">Admin</option>
          <option value="Konyha">Konyha</option>
          <option value="Dolgozó">Dolgozó</option>
        </select>
        
        <select v-model="filters.userStatus" @change="fetchUsers">
          <option value="">Összes státusz</option>
          <option value="active">Aktív</option>
          <option value="inactive">Inaktív</option>
          <option value="suspended">Felfüggesztett</option>
        </select>
      </div>
    </div>


    <div v-if="showEditModal" class="modal-overlay">
      <div class="modal">
        <div class="modal-header">
          <h2>Felhasználó szerkesztése</h2>
          <button @click="closeEditModal" class="btn-close">×</button>
        </div>
        
        <div class="modal-body">
          <div v-if="editLoading" class="loading-small">
            <div class="spinner-small"></div>
            <p>Adatok betöltése...</p>
          </div>
          
          <form v-else @submit.prevent="saveUserChanges" class="edit-form">
            <div class="form-grid">

              <div class="form-group">
                <label>Keresztnév *</label>
                <input v-model="editUser.firstName" type="text" required>
              </div>
              
              <div class="form-group">
                <label>Vezetéknév *</label>
                <input v-model="editUser.lastName" type="text" required>
              </div>
              
              <div class="form-group">
                <label>Középső név</label>
                <input v-model="editUser.thirdName" type="text">
              </div>
              
              <div class="form-group">
                <label>Email *</label>
                <input v-model="editUser.email" type="email" required>
              </div>
              
              <div class="form-group">
                <label>Szerepkör *</label>
                <select v-model="editUser.userType" required>
                  <option value="Tanuló">Tanuló</option>
                  <option value="Tanár">Tanár</option>
                  <option value="Admin">Admin</option>
                  <option value="Konyha">Konyha</option>
                  <option value="Dolgozó">Dolgozó</option>
                </select>
              </div>
              
              <div class="form-group">
                <label>Státusz *</label>
                <select v-model="editUser.userStatus" required>
                  <option value="active">Aktív</option>
                  <option value="inactive">Inaktív</option>
                  <option value="suspended">Felfüggesztett</option>
                </select>
              </div>

              <div class="form-group">
                <label>Vármegye *</label>
                <!--<input v-model="editUser.county_id" @change="loadCitiesForCounty" type="text">-->
                <select v-model="editUser.county_id" @change="loadCitiesForCounty" required>
                  <option value="">Válassz megyét</option>
                  <option v-for="county in counties" :key="county.id" :value="county.id">
                    {{ county.countyName }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label>Város *</label>
                <!--<input v-model="editUser.city" type="text">-->
                <select v-model="editUser.city_id" required :disabled="!editUser.county_id">
                  <option value="">Válassz várost</option>
                  <option v-for="city in citiesForCounty" :key="city.id" :value="city.id">
                    {{ city.zipCode }} - {{ city.cityName }}
                  </option>
                </select>
                <small v-if="!editUser.county_id" class="text-muted">
                  Először válassz megyét
                </small>
              </div>
              
              
              <div class="form-group">
                <label>Lakcím *</label>
                <input v-model="editUser.address" type="text">
              </div>
              
              <div class="form-group">
                <label class="checkbox-label">
                  <input v-model="editUser.hasDiscount" type="checkbox">
                  <span>Kedvezményes</span>
                </label>
              </div>
            </div>
            
            
            <div class="modal-footer">
              <button type="button" @click="closeEditModal" class="btn-cancel">
                Mégse
              </button>
              <button type="submit" :disabled="saving" class="btn-save">
                <span v-if="saving">Mentés...</span>
                <span v-else>Mentés</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    
          <!-- Tömeges műveletek -->
      <div v-if="selectedUsers.length > 0" class="bulk-actions">
        <div class="bulk-header">
          <span class="selected-count">{{ selectedUsers.length }} felhasználó kiválasztva</span>
          <div class="bulk-buttons">
            <button
              @click="bulkUpdateAvailability(true)"
              class="btn-bulk btn-bulk-available"
            >
              Aktiválás
            </button>
            <button
              @click="bulkUpdateAvailability(false)"
              class="btn-bulk btn-bulk-unavailable"
            >
              Deaktiválás
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
    
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Felhasználók betöltése...</p>
    </div>
    
    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="fetchUsers">Újra próbál</button>
    </div>
    
    <div v-else>
      <table class="users-table">
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
            <th>Email</th>
            <th>Szerepkör</th>
            <th>Státusz</th>
            <th>Regisztráció</th>
            <th>Műveletek</th>
            <th>Felfüggesztés</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users.data" :key="user.id">
           <!-- Kijelölés -->
            <td class="checkbox-col">
                <input
                  type="checkbox"
                  :value="user.id"
                  v-model="selectedUsers"
                  class="select-item"
                />
            </td>
            <td>{{ user.firstName }} {{ user.lastName }} {{ user.thirdName }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span :class="`role-badge ${user.userType.toLowerCase()}`">
                {{ user.userType }}
              </span>
            </td>
            <td>
              <span  :class="`status-badge ${user.userStatus}`">
                {{ user.userStatus }}
              </span>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td class="actions">
              <div class="actions-inner">
                <button 
                  @click="openEditModal(user.id)"
                  class="btn-edit"
                  title="Szerkesztés">
                  ✎
                </button>

                <button 
                  v-if="user.userStatus === 'inactive'"
                  @click="updateStatus(user.id, 'active')"
                  class="btn-activate"
                  title="Aktiválás">
                  ✓
                </button>
                
                <button 
                  v-if="user.userStatus === 'active'"
                  @click="updateStatus(user.id, 'inactive')"
                  class="btn-deactivate"
                  title="Deaktiválás">
                  ❚❚
                </button>
              </div>
            </td>
            <td>
              <button 
                v-if="user.userStatus !== 'suspended'"
                @click="updateStatus(user.id, 'suspended')"
                class="btn-suspend"
                title="Felfüggesztés">
                Felfüggesztés
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <div v-if="users.data && users.data.length > 0" class="pagination">
        <button 
          @click="changePage(users.current_page - 1)"
          :disabled="users.current_page === 1"
        >
          Előző
        </button>
        
        <span>
          Oldal {{ users.current_page }} / {{ users.last_page }}
          (Összesen: {{ users.total }} felhasználó)
        </span>
        
        <button 
          @click="changePage(users.current_page + 1)"
          :disabled="users.current_page === users.last_page"
        >
          Következő
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';
import AuthService from '../../../services/authService'

export default {
  
  data() {
    return {
      users: { data: [] },
      selectedUsers: [],
      search: '',
      filters: {
        userType: '',
        userStatus: ''
      },
      loading: false,
      error: '',
      currentPage: 1,

      counties: [],
      citiesForCounty: [],

      showEditModal: false,
      editUser: {
        id: null,
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        userType: 'Tanuló',
        userStatus: 'active',
        address: '',
        hasDiscount: false,
      },
      editLoading: false,
      saving: false
    }
  },
  
  computed: {
    currentUser() {
      return AuthService.getUser()
    },
    allSelected() {
      return this.users.data && 
            this.users.data.length > 0 &&
            this.users.data.every(user => this.selectedUsers.includes(user.id))
    }
  },
  
  
  mounted() {
    this.fetchUsers()
    this.loadCounties()
  },
  
  methods: {


    async fetchUsers() {
      this.loading = true
      this.error = ''
      
      try {
        const params = {
          page: this.currentPage,
          search: this.search,
          ...this.filters
        }

        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })

        console.log('Fetching users with params:', params)

        const response = await AuthService.api.get('/admin/users', { params })
        console.log('Raw API response:', response.data)
        
        // BOM ELTÁVOLÍTÁS
        let responseData = response.data
        
        if (typeof responseData === 'string') {
          console.log('Response is string, cleaning BOM...')
          responseData = responseData.replace(/^\uFEFF/, '')
          responseData = JSON.parse(responseData)
        }

        else if (typeof responseData === 'object') {
          console.log('Response is object, checking for BOM in stringified version...')
          const jsonString = JSON.stringify(responseData)
          if (jsonString.charCodeAt(0) === 0xFEFF) {
            console.log('BOM found in object, cleaning...')
            const cleanString = jsonString.replace(/^\uFEFF/, '')
            responseData = JSON.parse(cleanString)
          }
        }
        
        console.log('Cleaned response data:', responseData)
        
        if (responseData.success && responseData.data) {
          this.users = responseData
        } else {
          this.error = 'Érvénytelen válasz formátum'
        }
        
      } catch (error) {
        console.error('Felhasználók betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'php artisan serve - Hiba történt a felhasználók betöltése során'
      } finally {
        this.loading = false
      }
    },

    async loadCounties() {
      try {
        const response = await AuthService.api.get('/admin/counties');
        
        if (response.data.success) {
          this.counties = response.data.data;
        } else {
          console.error('API error:', response.data.message);
 
        }
      } catch (error) {
        console.error('Counties betöltése failed:', error);
        
        // Hiba? 
        if (error.response) {
          console.error('Response status:', error.response.status);
          console.error('Response data:', error.response.data);
        }
        
      }
    },

    async loadCitiesForCounty() {
      if (!this.editUser.county_id) {
        this.citiesForCounty = [];
        this.editUser.city_id = null;
        return;
      }
      
      try {
        const response = await AuthService.api.get(`/admin/cities/by-county/${this.editUser.county_id}`);
        
        if (response.data.success) {
          this.citiesForCounty = response.data.data;
          
          if (this.editUser.city_id) {
            const cityExists = this.citiesForCounty.some(city => city.id == this.editUser.city_id);
            if (!cityExists) {
              this.editUser.city_id = null;
            }
          }
        } else {
          console.error('Cities API error:', response.data.message);
          this.citiesForCounty = [];
        }
      } catch (error) {
        console.error('Cities betöltése failed:', error);
        this.citiesForCounty = [];
      }
    },


    async openEditModal(userId) {
      
      this.showEditModal = true
      this.editLoading = true
      this.citiesForCounty = []
      
      try {
        const userFromList = this.users.data.find(u => u.id == userId)
        
        if (userFromList) {
          this.editUser = {
            id: userFromList.id,
            firstName: userFromList.firstName || '',
            lastName: userFromList.lastName || '',
            thirdName: userFromList.thirdName || '',
            email: userFromList.email || '',
            userType: userFromList.userType || 'Tanuló',
            userStatus: userFromList.userStatus || 'active',
            county_id: userFromList.county_id || null,
            city_id: userFromList.city_id || null,
            address: '', // Ezek nincsenek a listában
            hasDiscount: userFromList.hasDiscount || false,
          }
        }

        const response = await AuthService.api.get(`/admin/users/${userId}`)

        
        if (response.data.success) {
          const userData = response.data.data
          
          this.editUser = {
            ...this.editUser,
            address: userData.address || this.editUser.address,
            thirdName: userData.thirdName || this.editUser.thirdName,
            hasDiscount: userData.hasDiscount || this.editUser.hasDiscount,
            county_id: userData.county_id || this.editUser.county_id,
            city_id: userData.city_id || this.editUser.city_id
          }
          if (this.editUser.county_id) {
            await this.loadCitiesForCounty()
          }
          console.log('Full user data loaded successfully')
        }
      } 
      catch (error) {
        console.error('Failed to load full user details:', error.message)
        console.log('Using basic data from list')
        
        if (!this.editUser.id) {
          alert('Felhasználó nem található')
          this.closeEditModal()
          return
        }
      } 
      finally {
        this.editLoading = false
      }
    },
    //Segédfüggvény
    fallbackToLocalData(userId) {
          console.log('DEBUG: Falling back to local data for user ID:', userId)
          console.log('DEBUG: Available users:', this.users.data)
          
          const user = this.users.data.find(u => u.id == userId)
          
          if (user) {
            console.log('DEBUG: Found user in local data:', user)
            this.editUser = {
              id: user.id,
              firstName: user.firstName || '',
              lastName: user.lastName || '',
              thirdName: user.thirdName || '',
              email: user.email || '',
              userType: user.userType || 'Tanuló',
              userStatus: user.userStatus || 'active',
              county_id: userData.county_id || this.editUser.county_id,
              city_id: userData.city_id || this.editUser.city_id,
              address: user.address || '',
              hasDiscount: user.hasDiscount || false,

            }
          } else {
            console.error('DEBUG: User not found in local data either')
            alert('Felhasználó nem található')
            this.closeEditModal()
          }
      },
        
        closeEditModal() {
          this.showEditModal = false
          this.citiesForCounty = [] 
          this.editUser = {
            id: null,
            firstName: '',
            lastName: '',
            thirdName: '',
            email: '',
            userType: 'Tanuló',
            userStatus: 'active',
            address: '',
            hasDiscount: false,
          }
        },
    
    async saveUserChanges() {
      if (!confirm('Biztosan menti a módosításokat?')) {
        return
      }
      
      this.saving = true
      
      try {
        const updateData = {
          firstName: this.editUser.firstName,
          lastName: this.editUser.lastName,
          thirdName: this.editUser.thirdName,
          email: this.editUser.email,
          userType: this.editUser.userType,
          userStatus: this.editUser.userStatus,
          address: this.editUser.address,
          hasDiscount: this.editUser.hasDiscount
        }
        
        
        console.log('DEBUG: Sending update to /admin/users/' + this.editUser.id)
        console.log('DEBUG: Update data:', updateData)
        
        const response = await AuthService.api.put(`/admin/users/${this.editUser.id}`, updateData)
        
        console.log('DEBUG: Update response:', response.data)
        
        if (response.data.success) {
          const userIndex = this.users.data.findIndex(u => u.id === this.editUser.id)
          if (userIndex !== -1) {
            Object.keys(updateData).forEach(key => {
              if (updateData[key] !== undefined) {
                this.users.data[userIndex][key] = updateData[key]
              }
            })
          }
          
          alert('Felhasználó sikeresen frissítve!')
          this.closeEditModal()
        } else {
          alert(response.data.message || 'Ismeretlen hiba történt')
        }
      } catch (error) {
        console.error('DEBUG: Update failed:', error)

        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat()
          alert('Validációs hibák:\n' + errors.join('\n'))
        } else if (error.response?.data?.message) {
          alert('Hiba: ' + error.response.data.message)
        } else {
          alert('Hiba történt a mentés során')
        }
      } finally {
        this.saving = false
      }
    },

    async updateStatus(userId, newUserStatus) {
      if (!confirm(`Biztosan ${newUserStatus === 'active' ? 'aktiválod' : 'deaktiválod'} a felhasználót?`)) {
        return
      }
      
      try {
        await AuthService.api.put(`/admin/users/${userId}/status`, {
          userStatus: newUserStatus
        })
        
        const userIndex = this.users.data.findIndex(u => u.id === userId)
        if (userIndex !== -1) {
          this.users.data[userIndex].userStatus = newUserStatus
        }
        
        alert(`Felhasználó státusza sikeresen frissítve: ${newUserStatus}`)
      } catch (error) {
        console.error('Státusz frissítés sikertelen:', error)
        alert(error.response?.data?.message || 'Hiba történt a státusz frissítése során')
      }
    },
    
    changePage(page) {
      if (page < 1 || page > this.users.last_page) return
      this.currentPage = page
      this.fetchUsers()
      window.scrollTo(0, 0)
    },
    
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('hu-HU')
    },

    async bulkUpdateAvailability(isAvailable) {
      if (this.selectedUsers.length === 0) return
      
      const action = isAvailable ? 'elérhetővé' : 'nem elérhetővé'
      if (!confirm(`${this.selectedUsers.length} felhasználó ${action} tétele?`)) {
        return
      }
      
      try {
        await AuthService.api.post('/admin/users/bulk-status', {
          user_ids: this.selectedUsers,
          userStatus: isAvailable ? 'active' : 'inactive'
        })
        await this.fetchUsers()
        this.clearSelection()
        
      } catch (error) {
        console.error('Hiba a tömeges frissítés során:', error)
        alert('Hiba történt a tömeges frissítés során')
      }
    },

    toggleSelectAll() {
      if (this.allSelected) {
        this.selectedUsers = []
      } else {
        this.selectedUsers = this.users.data.map(user => user.id)
      }
    },

    clearSelection() {
      this.selectedUsers = []
    }
  }


}
</script>

<style scoped>
.admin-users {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.header-actions input,
.header-actions select {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.header-actions input {
  flex: 1;
  min-width: 300px;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.users-table th {
  background: #f0a24a;
  color: #7b2c2c;
  text-align: center;
  padding: 1rem;
  font-weight: 500;
}

.users-table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  text-align: center;
}

.users-table tr:hover {
  background: #f8f9fa;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.role-badge.admin {
  background: #e74c3c;
  color: white;
}

.role-badge.konyha {
  background: #e67e22;
  color: white;
}

.role-badge.tanuló {
  background: #3498db;
  color: white;
}

.role-badge.tanár {
  background: #2ecc71;
  color: white;
}

.role-badge.dolgozó {
  background: #9b59b6;
  color: white;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.active {
  background: #d4edda;
  color: #155724;
}

.status-badge.inactive {
  background: #fff3cd;
  color: #856404;
}

.status-badge.suspended {
  background: #f8d7da;
  color: #721c24;
}

.actions {
  text-align: center;
  vertical-align: middle;
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
  margin: auto;
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

.btn-suspend {
  background: #f8d7da;
  color: #721c24;
  border-radius: 8px;
}


.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 2rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination button {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination span {
  color: #7f8c8d;
}


.loading {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error {
  text-align: center;
  padding: 3rem;
  color: #e74c3c;
}

.error button {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}


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

.edit-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 500;
  color: #476079;
}

.form-group input,
.form-group select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  margin-top: 1rem;
}

.checkbox-label input[type="checkbox"] {
  width: auto;
}

.modal-footer {
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

.loading-small {
  text-align: center;
  padding: 2rem;
}

.spinner-small {
  width: 30px;
  height: 30px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #a0d3f5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

/* Responsive */
@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .modal {
    width: 95%;
    margin: 1rem;
  }
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
  border: 1px solid #c3e6cb;
}

.btn-bulk-available:hover {
  background: #c3e6cb;
}

.btn-bulk-unavailable {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
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

/* Checkbox oszlop */
.checkbox-col {
  width: 40px;
  text-align: center;
}

.select-all,
.select-item {
  width: 18px;
  height: 18px;
  cursor: pointer;
}
</style>