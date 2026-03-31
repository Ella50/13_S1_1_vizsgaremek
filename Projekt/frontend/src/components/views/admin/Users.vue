<template>
  <div class="admin-users">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Felhasználók kezelése</h1>
        
        <div class="header-controls">
          <div class="search-box">
            <input 
              v-model="search" 
              type="text" 
              placeholder="Keresés név vagy email alapján..."
              class="search-input"
              @input="onSearchInput"
            >
            <span class="search-icon">🔍</span>
          </div>
          
          <select v-model="filters.userType" @change="fetchUsers" class="filter-select">
            <option value="">Összes szerepkör</option>
            <option value="Tanuló">Tanuló</option>
            <option value="Tanár">Tanár</option>
            <option value="Admin">Admin</option>
            <option value="Konyha">Konyha</option>
            <option value="Dolgozó">Dolgozó</option>
          </select>
          
          <select v-model="filters.userStatus" @change="fetchUsers" class="filter-select">
            <option value="">Összes státusz</option>
            <option value="Aktív">Aktív</option>
            <option value="Inaktív">Inaktív</option>
            <option value="Felfüggesztett">Felfüggesztett</option>
          </select>
        </div>
      </div>

      <!-- Tömeges műveletek -->
      <div v-if="selectedUsers.length > 0" class="bulk-actions">
        <div class="bulk-header">
          <span class="selected-count">{{ selectedUsers.length }} felhasználó kiválasztva</span>
          <div class="bulk-buttons">
            <button @click="bulkUpdateAvailability(true)" class="btn-bulk btn-bulk-available">
              Aktiválás
            </button>
            <button @click="bulkUpdateAvailability(false)" class="btn-bulk btn-bulk-unavailable">
              Deaktiválás
            </button>
            <button @click="clearSelection" class="btn-bulk btn-bulk-clear">
              Kijelölés törlése
            </button>
          </div>
        </div>
      </div>

      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Felhasználók betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="fetchUsers" class="btn-secondary">Újrapróbálkozás</button>
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
                <td class="checkbox-col">
                  <input type="checkbox" :value="user.id" v-model="selectedUsers" class="select-item">
                </td>
                <td class="user-name-cell">{{ user.firstName }} {{ user.lastName }} {{ user.thirdName }}</td>
                <td>{{ user.email }}</td>
                <td>
                  <span :class="['role-badge', getRoleClass(user.userType)]">
                    {{ user.userType }}
                  </span>
                </td>
                <td>
                  <span :class="['status-badge', getStatusClass(user.userStatus)]">
                    {{ user.userStatus }}
                  </span>
                </td>
                <td>{{ formatDate(user.created_at) }}</td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button @click="openEditModal(user.id)" class="btn-icon btn-edit" title="Szerkesztés">
                      ✎
                    </button>
                    <button 
                      v-if="user.userStatus === 'Inaktív'"
                      @click="updateStatus(user.id, 'Aktív')"
                      class="btn-icon btn-activate"
                      title="Aktiválás">
                      ✓
                    </button>
                    <button 
                      v-if="user.userStatus === 'Aktív'"
                      @click="updateStatus(user.id, 'Inaktív')"
                      class="btn-icon btn-deactivate"
                      title="Deaktiválás">
                      ❚❚
                    </button>
                  </div>
                </td>
                <td class="suspend-cell">
                  <button 
                    v-if="user.userStatus !== 'Felfüggesztett'"
                    @click="updateStatus(user.id, 'Felfüggesztett')"
                    class="btn-suspend"
                    title="Felfüggesztés">
                    Felfüggesztés
                  </button>
                </td>
              </tr>
              <tr v-if="users.data && users.data.length === 0">
                <td colspan="8" class="empty-row">Nincsenek felhasználók</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="users.data && users.data.length > 0" class="pagination">
          <button @click="changePage(users.current_page - 1)" :disabled="users.current_page === 1" class="btn-pagination">
            Előző
          </button>
          <span class="pagination-info">
            {{ users.current_page }} / {{ users.last_page }} oldal
            ({{ users.total }} felhasználó)
          </span>
          <button @click="changePage(users.current_page + 1)" :disabled="users.current_page === users.last_page" class="btn-pagination">
            Következő
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click.self="closeEditModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Felhasználó szerkesztése</h2>
          <button @click="closeEditModal" class="modal-close">×</button>
        </div>
        
        <div class="modal-body">
          <div v-if="editLoading" class="loading-state-small">
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
                  <option value="Aktív">Aktív</option>
                  <option value="Inaktív">Inaktív</option>
                  <option value="Felfüggesztett">Felfüggesztett</option>
                </select>
              </div>

              <div class="form-group">
                <label>Vármegye *</label>
                <select v-model="editUser.county_id" @change="loadCitiesForCounty" required>
                  <option value="">Válassz megyét</option>
                  <option v-for="county in counties" :key="county.id" :value="county.id">
                    {{ county.countyName }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label>Város *</label>
                <select v-model="editUser.city_id" required :disabled="!editUser.county_id">
                  <option value="">Válassz várost</option>
                  <option v-for="city in citiesForCounty" :key="city.id" :value="city.id">
                    {{ city.zipCode }} - {{ city.cityName }}
                  </option>
                </select>
                <small v-if="!editUser.county_id" class="form-hint">Először válassz megyét</small>
              </div>
              
              <div class="form-group">
                <label>Lakcím *</label>
                <input v-model="editUser.address" type="text">
              </div>
              
              <div class="form-group checkbox-group">
                <label class="checkbox-label">
                  <input v-model="editUser.hasDiscount" type="checkbox">
                  <span>Kedvezményes</span>
                </label>
              </div>
              <div class="form-group checkbox-group">
                <label class="checkbox-label">
                  <input v-model="editUser.hasDiabetes" type="checkbox">
                  <span>Cukorbeteg</span>
                </label>
              </div>
            </div>

            <div class="rfid-section">
              <div class="rfid-row">
                <div>
                    <div>
                      <strong>RFID kártya: </strong>
                      <span v-if="editUser.rfid_card?.cardNumber">
                        {{ editUser.rfid_card.cardNumber }}
                      </span>
                      <span v-else class="text-muted">Nincs hozzárendelve</span>
                    </div>
                  </div>
                  <button type="button" class="btn-rfid" @click="openCardAssignModal">
                    Kártya hozzáadása
                </button>
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

    <!-- RFID Modal -->
    <div v-if="showRfidModal" class="modal-overlay" @click.self="closeRfidModal">
      <div class="modal modal-small">
        <div class="modal-header">
          <h2>Kártya hozzárendelése</h2>
          <button @click="closeRfidModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <p>Érintsd a kártyát az olvasóhoz…</p>
          <div v-if="rfidState === 'waiting'" class="rfid-status rfid-status--waiting">
            Várakozás beolvasásra…
          </div>
          <div v-else-if="rfidState === 'success'" class="rfid-status rfid-status--success">
            ✅ Sikeres hozzárendelés: <strong>{{ lastUid }}</strong>
          </div>
          <div v-else-if="rfidState === 'busy'" class="rfid-status rfid-status--busy">
            ⚠️ A kártya foglalt.
          </div>
          <div v-else-if="rfidState === 'error'" class="rfid-status rfid-status--error">
            ❌ Hiba: {{ rfidErrorMessage }}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="closeRfidModal">Bezárás</button>
          </div>
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
      users: { data: [] },
      selectedUsers: [],
      search: '',
      searchTimeout: null,
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
        userStatus: 'Aktív',
        address: '',
        hasDiscount: false,
        hasDiabetes: false,
        rfid_card: null, // Changed from rfid_uid to rfid_card
        county_id: null,
        city_id: null,
      },
      editLoading: false,
      saving: false,
      showRfidModal: false,
      rfidState: 'waiting',
      rfidErrorMessage: '',
      lastUid: '',
      rfidPollTimer: null,
      rfidSince: null,
    }
  },

  watch: {
    showEditModal(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    },
    showRfidModal(newVal) {
      if (newVal) {
        document.body.style.overflow = 'hidden';
      } else if (!this.showEditModal) {
        document.body.style.overflow = '';
      }
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
  
  beforeDestroy() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout)
    this.stopRfidPolling()
  },
  
  methods: {
    onSearchInput() {
      if (this.searchTimeout) clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.fetchUsers()
      }, 300)
    },

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
          if (params[key] === '' || params[key] === null) delete params[key]
        })

        const response = await AuthService.api.get('/admin/users', { params })
        let responseData = response.data
        
        if (typeof responseData === 'string') {
          responseData = responseData.replace(/^\uFEFF/, '')
          responseData = JSON.parse(responseData)
        }
        
        if (responseData.success && responseData.data) {
          this.users = responseData
        } else {
          this.error = 'Érvénytelen válasz formátum'
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Hiba történt a felhasználók betöltése során'
      } finally {
        this.loading = false
      }
    },

    getRoleClass(role) {
      const classes = {
        'Admin': 'admin',
        'Konyha': 'kitchen',
        'Tanuló': 'student',
        'Tanár': 'teacher',
        'Dolgozó': 'worker'
      }
      return classes[role] || role.toLowerCase()
    },

    getStatusClass(status) {
      const classes = {
        'Aktív': 'active',
        'Inaktív': 'inactive',
        'Felfüggesztett': 'suspended'
      }
      return classes[status] || status.toLowerCase()
    },

    async loadCounties() {
      try {
        const response = await AuthService.api.get('/admin/counties')
        if (response.data.success) this.counties = response.data.data
      } catch (error) {
        console.error(error)
      }
    },

    async loadCitiesForCounty() {
      if (!this.editUser.county_id) {
        this.citiesForCounty = []
        this.editUser.city_id = null
        return
      }
      try {
        const response = await AuthService.api.get(`/admin/cities/by-county/${this.editUser.county_id}`)
        if (response.data.success) {
          this.citiesForCounty = response.data.data
          if (this.editUser.city_id && !this.citiesForCounty.some(city => city.id == this.editUser.city_id)) {
            this.editUser.city_id = null
          }
        }
      } catch (error) {
        this.citiesForCounty = []
      }
    },

    async openEditModal(userId) {
      this.showEditModal = true;
      this.editLoading = true;
      this.citiesForCounty = [];

      try {
        const response = await AuthService.api.get(`/admin/users/${userId}`);

        if (!response.data.success) {
          addAlert({ message: 'Felhasználó nem található', type: 'error' });
          this.closeEditModal();
          return;
        }

        const userData = response.data.data;

        this.editUser = {
          id: userData.id,
          firstName: userData.firstName || '',
          lastName: userData.lastName || '',
          thirdName: userData.thirdName || '',
          email: userData.email || '',
          userType: userData.userType || 'Tanuló',
          userStatus: userData.userStatus || 'Aktív',
          address: userData.address || '',
          hasDiscount: userData.hasDiscount || false,
          hasDiabetes: userData.hasDiabetes || false,
          county_id: userData.city?.county?.id || null,
          city_id: userData.city_id || null,
          rfid_card: userData.rfid_card || null  // Store the entire rfid_card object
        };

        if (this.editUser.county_id) {
          await this.loadCitiesForCounty();
        }
      } catch (error) {
        addAlert({ message: 'Hiba történt a betöltés során', type: 'error' });
        this.closeEditModal();
      } finally {
        this.editLoading = false;
      }
    },

    closeEditModal() {
      this.showEditModal = false;
      this.citiesForCounty = [];
      if (!this.showRfidModal) {
        document.body.style.overflow = '';
      }
      this.editUser = {
        id: null, 
        firstName: '', 
        lastName: '', 
        thirdName: '', 
        email: '',
        userType: 'Tanuló', 
        userStatus: 'Aktív', 
        address: '', 
        hasDiscount: false, 
        hasDiabetes: false,
        rfid_card: null  // Reset to null instead of empty string
      };
    },

    closeRfidModal() {
      this.showRfidModal = false;
      this.stopRfidPolling();
      if (!this.showEditModal) {
        document.body.style.overflow = '';
      }
    },

    async saveUserChanges() {
      const confirmed = await showConfirm({ message: 'Biztosan menti a változtatásokat?' })
      if (!confirmed) return
  
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
          hasDiscount: this.editUser.hasDiscount,
          hasDiabetes: this.editUser.hasDiabetes,
          city_id: this.editUser.city_id
        }
        
        const response = await AuthService.api.put(`/admin/users/${this.editUser.id}`, updateData)
        if (response.data.success) {
          const userIndex = this.users.data.findIndex(u => u.id === this.editUser.id)
          if (userIndex !== -1) {
            Object.keys(updateData).forEach(key => {
              this.users.data[userIndex][key] = updateData[key]
            })
          }
          addAlert({ message: 'Felhasználó sikeresen frissítve!', type: 'success' })
          this.closeEditModal()
        } else {
          addAlert({ message: response.data.message || 'Ismeretlen hiba történt', type: 'error' })
        }
      } catch (error) {
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat()
          addAlert({ title: 'Validációs hibák', message: errors.join(', '), type: 'error' })
        } else {
          addAlert({ message: error.response?.data?.message || 'Hiba történt a mentés során', type: 'error' })
        }
      } finally {
        this.saving = false
      }
    },

    async updateStatus(userId, newUserStatus) {
      const confirmed = await showConfirm({ message: 'Biztos?' })
      if (!confirmed) return
      
      try {
        await AuthService.api.put(`/admin/users/${userId}/status`, { userStatus: newUserStatus })
        const userIndex = this.users.data.findIndex(u => u.id === userId)
        if (userIndex !== -1) this.users.data[userIndex].userStatus = newUserStatus
        addAlert({ message: `Felhasználó státusza frissítve: ${newUserStatus}`, type: 'success' })
      } catch (error) {
        addAlert({ message: error.response?.data?.message || 'Hiba történt a státusz frissítése során', type: 'error' })
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
      const confirmed = await showConfirm({ message: 'Biztos?' })
      if (!confirmed) return
      
      try {
        await AuthService.api.post('/admin/users/bulk-status', {
          user_ids: this.selectedUsers,
          userStatus: isAvailable ? 'Aktív' : 'Inaktív'
        })
        await this.fetchUsers()
        this.clearSelection()
      } catch (error) {
        addAlert({ message: 'Hiba történt a tömeges frissítés során', type: 'error' })
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
    },

    openCardAssignModal() {
      if (!this.editUser?.id) return
      this.showRfidModal = true
      this.rfidState = 'waiting'
      this.rfidErrorMessage = ''
      this.lastUid = ''
      this.rfidSince = new Date().toISOString()
      this.startRfidPolling()
    },

    closeRfidModal() {
      this.showRfidModal = false
      this.stopRfidPolling()
    },

    startRfidPolling() {
      this.stopRfidPolling()
      this.rfidPollTimer = setInterval(() => this.checkLatestRfid(), 1000)
    },

    stopRfidPolling() {
      if (this.rfidPollTimer) {
        clearInterval(this.rfidPollTimer)
        this.rfidPollTimer = null
      }
    },

    async checkLatestRfid() {
      if (!this.showRfidModal || this.rfidState !== 'waiting') return
      try {
        const res = await AuthService.api.get('/admin/rfid/latest-scan', { params: { since: this.rfidSince } })
        if (!res.data?.success) return
        const scan = res.data.data
        if (!scan?.uid) return
        this.rfidSince = scan.scanned_at || new Date().toISOString()
        await this.assignRfidToUser(scan.uid)
      } catch (e) {
        this.rfidState = 'error'
        this.rfidErrorMessage = e.response?.data?.message || 'Nem sikerült lekérni az RFID-t.'
        this.stopRfidPolling()
      }
    },

    async assignRfidToUser(uid) {
      try {
        const res = await AuthService.api.post(`/admin/users/${this.editUser.id}/rfid/assign`, { uid })
        if (res.data?.success) {
          this.lastUid = uid
          this.rfidState = 'success'
          // Update the rfid_card object properly
          this.editUser.rfid_card = {
            id: res.data.data?.cardId || null,
            cardNumber: uid,
            user_id: this.editUser.id
          }
          this.stopRfidPolling()
          return
        }
        if (res.data?.code === 'CARD_BUSY') {
          this.rfidState = 'busy'
        } else {
          this.rfidState = 'error'
          this.rfidErrorMessage = res.data?.message || 'Ismeretlen hiba.'
        }
        this.stopRfidPolling()
      } catch (e) {
        if (e.response?.data?.code === 'CARD_BUSY') {
          this.rfidState = 'busy'
        } else {
          this.rfidState = 'error'
          this.rfidErrorMessage = e.response?.data?.message || 'Hiba a hozzárendelés során.'
        }
        this.stopRfidPolling()
      }
    }
  }
}
</script>

<style scoped>
.admin-users {
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

.checkbox-col {
  width: 40px;
  text-align: center;
}

.select-all, .select-item {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.user-name-cell {
  font-weight: 500;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.role-badge.admin { background: #e74c3c; color: white; }
.role-badge.kitchen { background: #e67e22; color: white; }
.role-badge.student { background: #3498db; color: white; }
.role-badge.teacher { background: #2ecc71; color: white; }
.role-badge.worker { background: #9b59b6; color: white; }

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.status-badge.active { background: #d4edda; color: #155724; }
.status-badge.inactive { background: #fff3cd; color: #856404; }
.status-badge.suspended { background: #f8d7da; color: #721c24; }

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

.btn-edit { background: #e3f2fd; color: #1976d2; }
.btn-edit:hover { background: #bbdef5; }

.btn-activate { background: #e8f5e9; color: #2e7d32; }
.btn-activate:hover { background: #c8e6c9; }

.btn-deactivate { background: #fff3cd; color: #856404; }
.btn-deactivate:hover { background: #ffeaa7; }

.btn-suspend {
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

.btn-suspend:hover {
  background: #f0a9af;
  color: #522a2e;
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

/* Bulk actions */
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

.btn-secondary {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #e9ecef;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
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
  padding-top: 1.5rem;
  border-top: 1px solid #eee;
  margin-top: 1rem;
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
  font-size: 0.8rem;
  color: #666;
  text-transform: uppercase;
}

.form-group input,
.form-group select {
  padding: 0.6rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #f0a24a;
}

.checkbox-group {
  flex-direction: row;
  align-items: center;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-weight: normal;
  text-transform: none;
}

.form-hint {
  font-size: 0.7rem;
  color: #888;
  margin-top: 0.25rem;
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

.rfid-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed #ddd;
}

.rfid-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn-rfid {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  background: #f0a24a;
  color: #7b2c2c;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-rfid:hover {
  background: #e5942c;
}

.rfid-status {
  margin-top: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  text-align: center;
}

.rfid-status--waiting { background: #eef3ff; color: #1976d2; }
.rfid-status--success { background: #d4edda; color: #155724; }
.rfid-status--busy { background: #fff3cd; color: #856404; }
.rfid-status--error { background: #f8d7da; color: #c62828; }

.empty-row {
  text-align: center;
  color: #888;
  padding: 3rem !important;
}

.text-muted {
  color: #888;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-users {
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

  .form-grid {
    grid-template-columns: 1fr;
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .actions-group {
    flex-wrap: wrap;
    justify-content: center;
  }

  .bulk-header {
    flex-direction: column;
    text-align: center;
  }

  .bulk-buttons {
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

  .btn-icon {
    width: 28px;
    height: 28px;
    font-size: 0.85rem;
  }

  .btn-suspend {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
  }
}
</style>