<template>
  <div class="admin-users">
    <div class="header">
      <h1>Felhasználók kezelése</h1>
      <div class="header-actions">
        <input 
          v-model="search" 
          type="text" 
          placeholder="Keresés"
          @input="fetchUsers"
        >
        
        <select v-model="filters.userType" @change="fetchUsers">
          <option value="">Összes szerepkör</option>
          <option value="Tanuló">Tanuló</option>
          <option value="Tanár">Tanár</option>
          <option value="Admin">Admin</option>
          <option value="Konyha">Konyha</option>
          <option value="Dolgozó">Dolgozó</option>
          <option value="Külsős">Külsős</option>
        </select>
        
        <select v-model="filters.userStatus" @change="fetchUsers">
          <option value="">Összes státusz</option>
          <option value="active">Aktív</option>
          <option value="inactive">Inaktív</option>
          <option value="suspended">Felfüggesztett</option>
        </select>
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
            <th>ID</th>
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
            <td>{{ user.id }}</td>
            <td>{{ user.firstName }} {{ user.lastName }}</td>
            <td>{{ user.email }}</td>
            <td>
              <span :class="`role-badge ${user.userType.toLowerCase()}`">
                {{ user.userType }}
              </span>
            </td>
            <td>
              <span :class="`status-badge ${user.userStatus}`">
                {{ user.userStatus }}
              </span>
            </td>
            <td>{{ formatDate(user.created_at) }}</td>
            <td class="actions">
              <button 
                v-if="user.userStatus === 'inactive'"
                @click="updateStatus(user.id, 'active')"
                class="btn-activate"
                title="Aktiválás">
                ✅
              </button>
              
              <button 
                v-if="user.userStatus === 'active'"
                @click="updateStatus(user.id, 'inactive')"
                class="btn-deactivate"
                title="Deaktiválás">
                ⏸️
              </button>

              <button 
                  @click="openEditModal(user.id)"
                  class="btn-edit"
                  title="Szerkesztés">
                  XX
              </button>
              

              
              <!--<button 
                v-if="user.id !== currentUser.id"
                @click="deleteUser(user.id)"
                class="btn-delete"
                title="Törlés"
              >
                🗑️
              </button>-->
            </td>
            <td>
              <button 
                v-if="user.userStatus !== 'suspended'"
                @click="updateStatus(user.id, 'suspended')"
                class="btn-suspend"
                title="Felfüggesztés">
                ⚠️
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Pagination -->
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
import AuthService from '../../../services/authService'

export default {
  data() {
    return {
      users: { data: [] },
      search: '',
      filters: {
        userType: '',
        userStatus: ''
      },
      loading: false,
      error: '',
      currentPage: 1,

      //Szerkesztéshez:
      showEditModal: false,
      editUser: {
        id: null,
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        userType: 'Tanuló',
        userStatus: 'active',
        phone: '',
        address: '',
        hasDiscount: false,
        newPassword: '',
        confirmPassword: ''
      },
      editLoading: false,
      saving: false
    }
  },
  
  computed: {
    currentUser() {
      return AuthService.getUser()
    }
  },
  
  mounted() {
    this.fetchUsers()
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

        //Üres paraméterek törlése

        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })

        console.log('Fetching users with params:', params)  // DEBUG
    
        const response = await AuthService.api.get('/admin/users', { params })
        console.log('API response:', response.data)  // DEBUG

        this.users = response.data
      } 

      catch (error) {
        console.error('Felhasználók betöltése sikertelen:', error)
        this.error = error.response?.data?.message || 'Hiba történt a felhasználók betöltése során'
      } 

      finally {
        this.loading = false
      }
    },

// Modal kezelés (Szerkesztés)
    async openEditModal(userId) {
      this.showEditModal = true
      this.editLoading = true
      
      try {
        const response = await AuthService.api.get(`/admin/users/${userId}`)
        if (response.data.success) {
          const userData = response.data.data
          
          this.editUser = {
            id: userData.id,
            firstName: userData.firstName || '',
            lastName: userData.lastName || '',
            thirdName: userData.thirdName || '',
            email: userData.email || '',
            userType: userData.userType || 'Tanuló',
            userStatus: userData.userStatus || 'active',
            phone: userData.phone || '',
            address: userData.address || '',
            hasDiscount: userData.hasDiscount || false,
            newPassword: '',
            confirmPassword: ''
          }
        }
      } catch (error) {
        console.error('Felhasználó adatok betöltése sikertelen:', error)
        alert('Nem sikerült betölteni a felhasználó adatait')
        this.closeEditModal()
      } finally {
        this.editLoading = false
      }
    },
    
    closeEditModal() {
      this.showEditModal = false
      this.editUser = {
        id: null,
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        userType: 'Tanuló',
        userStatus: 'active',
        phone: '',
        address: '',
        hasDiscount: false,
        newPassword: '',
        confirmPassword: ''
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
          phone: this.editUser.phone,
          address: this.editUser.address,
          hasDiscount: this.editUser.hasDiscount
        }
        
        const response = await AuthService.api.put(`/admin/users/${this.editUser.id}`, updateData)
        
        if (response.data.success) {
          // Frissítjük a listában a felhasználót
          const userIndex = this.users.data.findIndex(u => u.id === this.editUser.id)
          if (userIndex !== -1) {
            this.users.data[userIndex] = {
              ...this.users.data[userIndex],
              ...updateData
            }
          }
          
          alert('Felhasználó sikeresen frissítve!')
          this.closeEditModal()
        }
      } catch (error) {
        console.error('Felhasználó frissítése sikertelen:', error)
        alert(error.response?.data?.message || 'Hiba történt a mentés során')
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
        
        /* this.fetchUsers()*/
        
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
    
    async deleteUser(userId) {
      if (!confirm('Biztosan törölni szeretné ezt a felhasználót? Ez a művelet nem visszavonható!')) {
        return
      }
      
      try {
        await AuthService.api.delete(`/admin/users/${userId}`)
        
        // Távolítsd el a listából
        this.users.data = this.users.data.filter(u => u.id !== userId)
        this.users.total -= 1
        
        alert('Felhasználó sikeresen törölve')
      } catch (error) {
        console.error('Felhasználó törlése sikertelen:', error)
        alert(error.response?.data?.message || 'Hiba történt a törlés során')
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

/* Table */
.users-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.users-table th {
  background: #2c3e50;
  color: white;
  text-align: left;
  padding: 1rem;
  font-weight: 500;
}

.users-table td {
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.users-table tr:hover {
  background: #f8f9fa;
}

/* Badges */
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

.role-badge.külsős {
  background: #95a5a6;
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

/* Actions */
.actions {
  display: flex;
  gap: 0.5rem;
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
}

.btn-delete {
  background: #f8d7da;
  color: #721c24;
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

/* Loading & Error */
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
</style>