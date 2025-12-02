<template>
  <div class="user-display">
    <div class="header-actions">
      <h2>Felhasználók</h2>
      <div class="action-buttons">
        <button @click="showModal = true" class="add-btn">
          Új felhasználó
        </button>
        <button @click="fetchUsers" class="refresh-btn" :disabled="loading">
          {{ loading ? 'Frissítés...' : 'Frissítés' }}
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
      <p><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M10.9453 20.5C11.3327 21.0667 11.8027 21.5725 12.338 22H6.75C5.50736 22 4.5 20.9926 4.5 19.75V9.62105C4.5 9.02455 4.73686 8.45247 5.15851 8.03055L10.5262 2.65951C10.9482 2.23725 11.5207 2 12.1177 2H17.25C18.4926 2 19.5 3.00736 19.5 4.25V10.3782C19.0266 10.1599 18.5241 9.99391 18 9.88753V4.25C18 3.83579 17.6642 3.5 17.25 3.5H12.248L12.2509 7.4984C12.2518 8.74166 11.2442 9.75 10.0009 9.75H6V19.75C6 20.1642 6.33579 20.5 6.75 20.5H10.9453ZM10.7488 4.55876L7.05986 8.25H10.0009C10.4153 8.25 10.7512 7.91389 10.7509 7.49947L10.7488 4.55876Z" fill="#323544"/>
<path d="M15.8751 14.9999C15.8751 14.5857 16.2109 14.2499 16.6251 14.2499C17.0393 14.2499 17.3751 14.5857 17.3751 14.9999V15.8749H18.2502C18.6644 15.8749 19.0002 16.2107 19.0002 16.6249C19.0002 17.0391 18.6644 17.3749 18.2502 17.3749H17.3751V18.25C17.3751 18.6643 17.0393 19 16.6251 19C16.2109 19 15.8751 18.6643 15.8751 18.25V17.3749H15C14.5858 17.3749 14.25 17.0391 14.25 16.6249C14.25 16.2107 14.5858 15.8749 15 15.8749H15.8751V14.9999Z" fill="#323544"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M11.25 16.625C11.25 13.6565 13.6565 11.25 16.625 11.25C19.5935 11.25 22 13.6565 22 16.625C22 19.5935 19.5935 22 16.625 22C13.6565 22 11.25 19.5935 11.25 16.625ZM16.625 12.75C14.4849 12.75 12.75 14.4849 12.75 16.625C12.75 18.7651 14.4849 20.5 16.625 20.5C18.7651 20.5 20.5 18.7651 20.5 16.625C20.5 14.4849 18.7651 12.75 16.625 12.75Z" fill="#323544"/>
</svg>
 {{ error }}</p>
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
                  <span v-if="user.hasDiscount" class="discount-yes"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.5455 6.4965C19.9848 6.93584 19.9848 7.64815 19.5455 8.08749L10.1286 17.5043C9.6893 17.9437 8.97699 17.9437 8.53765 17.5043L4.45451 13.4212C4.01517 12.9819 4.01516 12.2695 4.4545 11.8302C4.89384 11.3909 5.60616 11.3909 6.0455 11.8302L9.33315 15.1179L17.9545 6.4965C18.3938 6.05716 19.1062 6.05716 19.5455 6.4965Z" fill="#323544"/>
</svg>
</span>
                  <span v-else class="discount-no"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M5.9545 5.95548C6.39384 5.51614 7.10616 5.51614 7.5455 5.95548L11.999 10.409L16.4524 5.95561C16.8918 5.51627 17.6041 5.51627 18.0434 5.95561C18.4827 6.39495 18.4827 7.10726 18.0434 7.5466L13.59 12L18.0434 16.4534C18.4827 16.8927 18.4827 17.605 18.0434 18.0444C17.6041 18.4837 16.8918 18.4837 16.4524 18.0444L11.999 13.591L7.5455 18.0445C7.10616 18.4839 6.39384 18.4839 5.9545 18.0445C5.51517 17.6052 5.51516 16.8929 5.9545 16.4535L10.408 12L5.9545 7.54647C5.51516 7.10713 5.51517 6.39482 5.9545 5.95548Z" fill="#323544"/>
</svg>
</span>
                </td>
                <td class="actions">
                  <button @click="editUser(user)" class="edit-btn" title="Szerkesztés">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M19.3028 3.7801C18.4241 2.90142 16.9995 2.90142 16.1208 3.7801L14.3498 5.5511C14.3442 5.55633 14.3387 5.56166 14.3333 5.5671C14.3279 5.57253 14.3225 5.57803 14.3173 5.58359L5.83373 14.0672C5.57259 14.3283 5.37974 14.6497 5.27221 15.003L4.05205 19.0121C3.9714 19.2771 4.04336 19.565 4.23922 19.7608C4.43508 19.9567 4.72294 20.0287 4.98792 19.948L8.99703 18.7279C9.35035 18.6203 9.67176 18.4275 9.93291 18.1663L20.22 7.87928C21.0986 7.0006 21.0986 5.57598 20.22 4.6973L19.3028 3.7801ZM14.8639 7.15833L6.89439 15.1278C6.80735 15.2149 6.74306 15.322 6.70722 15.4398L5.8965 18.1036L8.56029 17.2928C8.67806 17.257 8.7852 17.1927 8.87225 17.1057L16.8417 9.13619L14.8639 7.15833ZM17.9024 8.07553L19.1593 6.81862C19.4522 6.52572 19.4522 6.05085 19.1593 5.75796L18.2421 4.84076C17.9492 4.54787 17.4743 4.54787 17.1814 4.84076L15.9245 6.09767L17.9024 8.07553Z" fill="#323544"/>
</svg>


                  </button>
                  <button @click="deleteUser(user.id)" class="delete-btn" title="Törlés">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.248 7.25C19.248 6.83579 18.9123 6.5 18.498 6.5H5.5C5.08579 6.5 4.75 6.83579 4.75 7.25C4.75 7.66421 5.08579 8 5.5 8H18.498C18.9123 8 19.248 7.66421 19.248 7.25ZM7.03809 19.7949L7.06055 19.9385C7.14453 20.2632 7.43977 20.5 7.78711 20.5H16.2109C16.6077 20.5 16.9362 20.191 16.96 19.7949L17.5771 9.5H6.4209L7.03809 19.7949ZM14.499 4.25C14.499 3.83579 14.1632 3.5 13.749 3.5H10.249C9.83481 3.5 9.49902 3.83579 9.49902 4.25V5H14.499V4.25ZM15.999 5H18.498C19.7407 5 20.748 6.00736 20.748 7.25C20.748 8.28926 20.0425 9.16046 19.085 9.41895L18.457 19.8848C18.3857 21.0729 17.4012 22 16.2109 22H7.78711C6.67125 22 5.73576 21.1853 5.56445 20.1045L5.54102 19.8848L4.91211 9.41895C3.95502 9.16016 3.25 8.28893 3.25 7.25C3.25 6.00736 4.25736 5 5.5 5H7.99902V4.25C7.99902 3.00736 9.00638 2 10.249 2H13.749C14.9917 2 15.999 3.00736 15.999 4.25V5Z" fill="#323544"/>
<g opacity="0.4">
<path d="M9.98779 11.9716C10.3756 11.9525 10.7091 12.2316 10.7661 12.6074L10.7739 12.6835L10.9985 17.2402V17.3173C10.9787 17.6969 10.6735 18.0072 10.2856 18.0263C9.8979 18.0453 9.56425 17.7663 9.50732 17.3906L9.49951 17.3144L9.27588 12.7578V12.6806C9.29571 12.3011 9.6001 11.9908 9.98779 11.9716Z" fill="#323544"/>
<path d="M14.4985 17.3144C14.4781 17.7279 14.126 18.0466 13.7124 18.0263C13.2987 18.006 12.9792 17.6539 12.9995 17.2402L13.2241 12.6835L13.2319 12.6074C13.2889 12.2315 13.6224 11.9525 14.0103 11.9716C14.398 11.9908 14.7023 12.3011 14.7222 12.6806V12.7578L14.4985 17.3144Z" fill="#323544"/>
</g>
</svg>


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
.discount-no { color: #dc3545; font-weight: bold;}


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