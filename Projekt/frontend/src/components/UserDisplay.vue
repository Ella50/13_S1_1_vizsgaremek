<template>
  <div class="user-display">
    <div class="header-actions">
      <h2>Felhasználók</h2>
      <div class="action-buttons">
        <button @click="showModal = true" class="add-btn">
          ➕ Új felhasználó
        </button>
        <button @click="fetchUsers" class="refresh-btn" :disabled="loading">
          🔄 {{ loading ? 'Frissítés...' : 'Frissítés' }}
        </button>
      </div>
    </div>

    <!-- Betöltés -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Adatok betöltése...</p>
    </div>

    <!-- Hiba -->
    <div v-else-if="error" class="error">
      <p>❌ {{ error }}</p>
      <button @click="fetchUsers" class="retry-btn">Újra próbál</button>
    </div>

    <!-- Adatok -->
    <div v-else class="data-content">
      <div v-if="users.length === 0" class="empty-state">
        <p>📝 Nincsenek felhasználók.</p>
        <button @click="showModal = true" class="add-first-btn">
          Első felhasználó hozzáadása
        </button>
      </div>

      <div v-else class="users-container">
        <!-- Szűrők -->
        <div class="filters">
          <select v-model="filters.userType" @change="applyFilters" class="filter-select">
            <option value="">Minden típus</option>
            <option value="Tanuló">Tanuló</option>
            <option value="Tanár">Tanár</option>
            <option value="Szülő">Szülő</option>
            <option value="Adminisztrátor">Adminisztrátor</option>
          </select>

          <select v-model="filters.status" @change="applyFilters" class="filter-select">
            <option value="">Minden státusz</option>
            <option value="active">Aktív</option>
            <option value="inactive">Inaktív</option>
            <option value="suspended">Felfüggesztett</option>
          </select>

          <input 
            v-model="filters.search" 
            @input="applyFilters"
            type="text" 
            placeholder="Keresés név vagy email alapján..." 
            class="search-input"
          >
        </div>

        <!-- Eredmények száma -->
        <div class="results-info">
          {{ filteredUsers.length }} felhasználó
        </div>

        <!-- Táblázat -->
        <div class="table-container">
          <table class="users-table">
            <thead>
              <tr>
                <th>Név</th>
                <th>Email</th>
                <th>Típus</th>
                <th>Státusz</th>
                <th>Város</th>
                <th>Osztály</th>
                <th>Kedvezmény</th>
                <th>Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in filteredUsers" :key="user.id">
                <td>
                  <strong>{{ user.lastName }} {{ user.firstName }}</strong>
                  <small v-if="user.thirdName">{{ user.thirdName }}</small>
                </td>
                <td>{{ user.email }}</td>
                <td>
                  <span class="badge" :class="getUserTypeClass(user.userType)">
                    {{ getUserTypeText(user.userType) }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="getStatusClass(user.status)">
                    {{ getStatusText(user.status) }}
                  </span>
                </td>
                <td>{{ user.city?.cityName || '-' }}</td>
                <td>{{ user.student_class?.className || '-' }}</td>
                <td>
                  <span v-if="user.hasDiscount" class="discount-yes">✅</span>
                  <span v-else class="discount-no">❌</span>
                </td>
                <td class="actions">
                  <button @click="editUser(user)" class="edit-btn" title="Szerkesztés">
                    ✏️
                  </button>
                  <button @click="deleteUser(user.id)" class="delete-btn" title="Törlés">
                    🗑️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal: Új/Szerkesztés -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingUser ? 'Felhasználó szerkesztése' : 'Új felhasználó' }}</h3>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>

        <form @submit.prevent="saveUser" class="user-form">
          <div class="form-row">
            <div class="form-group">
              <label>Keresztnév *</label>
              <input v-model="form.firstName" type="text" required>
            </div>
            <div class="form-group">
              <label>Vezetéknév *</label>
              <input v-model="form.lastName" type="text" required>
            </div>
          </div>

          <div class="form-group">
            <label>Harmadik név</label>
            <input v-model="form.thirdName" type="text">
          </div>

          <div class="form-group">
            <label>Email *</label>
            <input v-model="form.email" type="email" required>
          </div>

          <div class="form-group">
            <label>Jelszó {{ editingUser ? '(csak módosításhoz)' : '*' }}</label>
            <input v-model="form.password" type="password" :required="!editingUser">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Felhasználó típus *</label>
              <select v-model="form.userType" required>
                <option value="Tanuló">Tanuló</option>
                <option value="Tanár">Tanár</option>
                <option value="Szülő">Szülő</option>
                <option value="Adminisztrátor">Adminisztrátor</option>
              </select>
            </div>
            <div class="form-group">
              <label>Státusz *</label>
              <select v-model="form.status" required>
                <option value="active">Aktív</option>
                <option value="inactive">Inaktív</option>
                <option value="suspended">Felfüggesztett</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Város *</label>
              <select v-model="form.city_id" required>
                <option value="">Válassz várost</option>
                <option v-for="city in cities" :key="city.id" :value="city.id">
                  {{ city.cityName }} ({{ city.zipCode }})
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Osztály</label>
              <select v-model="form.class_id">
                <option value="">Nincs osztály</option>
                <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">
                  {{ classItem.className }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Csoport</label>
              <select v-model="form.group_id">
                <option value="">Nincs csoport</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>RFID kártya *</label>
              <select v-model="form.rfidCard_id" required>
                <option value="">Válassz RFID kártyát</option>
                <option v-for="rfid in rfidCards" :key="rfid.id" :value="rfid.id">
                  {{ rfid.cardNumber }}
                </option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Cím</label>
            <input v-model="form.address" type="text">
          </div>

          <div class="form-group checkbox-group">
            <label>
              <input v-model="form.hasDiscount" type="checkbox">
              Kedvezményre jogosult
            </label>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeModal" class="cancel-btn">
              Mégse
            </button>
            <button type="submit" :disabled="saving" class="save-btn">
              {{ saving ? 'Mentés...' : 'Mentés' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { userService } from '@/services/userService.js'

export default {
  name: 'UserDisplay',
  data() {
    return {
      loading: false,
      error: null,
      users: [],
      filteredUsers: [],
      showModal: false,
      saving: false,
      editingUser: null,
      cities: [],
      classes: [],
      groups: [],
      rfidCards: [],
      filters: {
        userType: '',
        status: '',
        search: ''
      },
      form: {
        firstName: '',
        lastName: '',
        thirdName: '',
        city_id: '',
        address: '',
        email: '',
        password: '',
        userType: 'Tanuló',
        rfidCard_id: '',
        class_id: '',
        group_id: '',
        status: 'active',
        hasDiscount: false
      }
    }
  },
  async mounted() {
    await this.fetchUsers();
    await this.fetchDropdownData();
  },
  methods: {
    async fetchUsers() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await userService.getAll();
        this.users = response.data;
        this.filteredUsers = this.users;
      } catch (err) {
        this.error = err.message || 'Hiba történt az adatok betöltése során';
        console.error('Fetch error:', err);
      } finally {
        this.loading = false;
      }
    },

    async fetchDropdownData() {
      try {
        const [citiesRes, classesRes, groupsRes, rfidRes] = await Promise.all([
          fetch('http://localhost:8000/api/cities').then(r => r.json()),
          fetch('http://localhost:8000/api/classes').then(r => r.json()),
          fetch('http://localhost:8000/api/groups').then(r => r.json()),
          fetch('http://localhost:8000/api/rfidcards').then(r => r.json())
        ]);

        this.cities = citiesRes.data || [];
        this.classes = classesRes.data || [];
        this.groups = groupsRes.data || [];
        this.rfidCards = rfidRes.data || [];
      } catch (err) {
        console.error('Dropdown data error:', err);
        this.error = 'Hiba történt a legördülő listák betöltése során';
      }
    },

    applyFilters() {
      this.filteredUsers = this.users.filter(user => {
        const matchesType = !this.filters.userType || user.userType === this.filters.userType;
        const matchesStatus = !this.filters.status || user.status === this.filters.status;
        const matchesSearch = !this.filters.search || 
          user.firstName.toLowerCase().includes(this.filters.search.toLowerCase()) ||
          user.lastName.toLowerCase().includes(this.filters.search.toLowerCase()) ||
          user.email.toLowerCase().includes(this.filters.search.toLowerCase());

        return matchesType && matchesStatus && matchesSearch;
      });
    },

    editUser(user) {
      this.editingUser = user;
      this.form = { ...user, password: '' };
      this.showModal = true;
    },

    async saveUser() {
      this.saving = true;
      
      try {
        if (this.editingUser) {
          const updateData = { ...this.form };
          if (!updateData.password) {
            delete updateData.password;
          }
          await userService.update(this.editingUser.id, updateData);
        } else {
          await userService.create(this.form);
        }
        
        this.closeModal();
        await this.fetchUsers();
      } catch (err) {
        this.error = err.message || 'Hiba történt a mentés során';
      } finally {
        this.saving = false;
      }
    },

    async deleteUser(id) {
      if (confirm('Biztosan törölni szeretnéd ezt a felhasználót?')) {
        try {
          await userService.delete(id);
          await this.fetchUsers();
        } catch (err) {
          this.error = err.message || 'Hiba történt a törlés során';
        }
      }
    },

    closeModal() {
      this.showModal = false;
      this.editingUser = null;
      this.form = {
        firstName: '',
        lastName: '',
        thirdName: '',
        city_id: '',
        address: '',
        email: '',
        password: '',
        userType: 'Tanuló',
        rfidCard_id: '',
        class_id: '',
        group_id: '',
        status: 'active',
        hasDiscount: false
      };
    },

    getUserTypeText(userType) {
      return userType; // Már magyarul vannak
    },

    getUserTypeClass(userType) {
      const classes = {
        'Tanuló': 'type-student',
        'Tanár': 'type-teacher',
        'Szülő': 'type-parent',
        'Adminisztrátor': 'type-admin'
      };
      return classes[userType] || 'type-default';
    },

    getStatusText(status) {
      const statuses = {
        'active': 'Aktív',
        'inactive': 'Inaktív',
        'suspended': 'Felfüggesztett'
      };
      return statuses[status] || status;
    },

    getStatusClass(status) {
      const classes = {
        'active': 'status-active',
        'inactive': 'status-inactive',
        'suspended': 'status-suspended'
      };
      return classes[status] || 'status-default';
    }
  }
}
</script>

<style scoped>
.user-display {
  min-height: 500px;
}

.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.action-buttons {
  display: flex;
  gap: 1rem;
}

.add-btn, .refresh-btn, .retry-btn, .add-first-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s;
}

.add-btn, .add-first-btn {
  background: #28a745;
  color: white;
}

.add-btn:hover, .add-first-btn:hover {
  background: #218838;
}

.refresh-btn {
  background: #17a2b8;
  color: white;
}

.refresh-btn:hover:not(:disabled) {
  background: #138496;
}

.refresh-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.retry-btn {
  background: #dc3545;
  color: white;
}

.retry-btn:hover {
  background: #c82333;
}


.users-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

th {
  background: #f8f9fa;
  font-weight: 600;
}

.badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: bold;
}

.type-student { background: #e3f2fd; color: #1565c0; }
.type-teacher { background: #f3e5f5; color: #7b1fa2; }
.type-admin { background: #e8f5e8; color: #2e7d32; }
.type-parent { background: #fff3e0; color: #ef6c00; }

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #fff3cd; color: #856404; }
.status-suspended { background: #f8d7da; color: #721c24; }

.discount-yes { color: #28a745; font-weight: bold; }
.discount-no { color: #dc3545; font-weight: bold; }

.actions {
  display: flex;
  gap: 0.5rem;
}

.edit-btn {
  background: #17a2b8;
  color: white;
  border: none;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
}

.delete-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
}

.add-btn {
  background: #28a745;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.checkbox-group {
  margin: 1rem 0;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
</style>