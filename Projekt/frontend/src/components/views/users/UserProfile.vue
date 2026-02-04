<template>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h2 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profilom</h2>
      </div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="card-body text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Betöltés...</span>
        </div>
        <p class="mt-3 text-muted">Adatok betöltése...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="card-body">
        <div class="alert alert-danger">
          <h4 class="alert-heading">Hiba!</h4>
          <p>{{ error }}</p>
          <button @click="retryLoading" class="btn btn-outline-danger">
            <i class="fas fa-redo me-2"></i>Újra próbálom
          </button>
        </div>
      </div>
      
      <!-- Content -->
      <div v-else class="card-body">
        <!-- Success/Error Messages -->
        <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-danger']">
          {{ message }}
        </div>
        
        <div class="row">
          <!-- Left Column - Personal Info -->
          <div class="col-md-6">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Személyes adatok</h5>
              </div>
              <div class="card-body">
                <form @submit.prevent="updateProfile">
                  <div class="mb-3">
                    <label class="form-label">Keresztnév *</label>
                    <input type="text" v-model="profileForm.firstName" class="form-control" required>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Vezetéknév *</label>
                    <input type="text" v-model="profileForm.lastName" class="form-control" required>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Középső név</label>
                    <input type="text" v-model="profileForm.thirdName" class="form-control">
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Email cím *</label>
                    <input type="email" v-model="profileForm.email" class="form-control" required>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Cím</label>
                    <input type="text" v-model="profileForm.address" class="form-control">
                  </div>
                  
                  <button type="submit" class="btn btn-primary" :disabled="isUpdating">
                    <i class="fas fa-save me-2"></i>
                    <span v-if="isUpdating">Mentés...</span>
                    <span v-else>Adatok mentése</span>
                  </button>
                </form>
              </div>
            </div>
            
            <!-- Password Change -->
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-key me-2"></i>Jelszó megváltoztatása</h5>
              </div>
              <div class="card-body">
                <form @submit.prevent="changePassword">
                  <div class="mb-3">
                    <label class="form-label">Jelenlegi jelszó *</label>
                    <input type="password" v-model="passwordForm.current_password" class="form-control" required>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Új jelszó *</label>
                    <input type="password" v-model="passwordForm.new_password" class="form-control" required>
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Új jelszó megerősítése *</label>
                    <input type="password" v-model="passwordForm.new_password_confirmation" class="form-control" required>
                  </div>
                  
                  <button type="submit" class="btn btn-warning" :disabled="isChangingPassword">
                    <i class="fas fa-key me-2"></i>
                    <span v-if="isChangingPassword">Mentés...</span>
                    <span v-else>Jelszó megváltoztatása</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
          
          <!-- Right Column - Health Info -->
          <div class="col-md-6">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>Egészségügyi információk</h5>
              </div>
              <div class="card-body">
                <!-- Diabetes -->
                <div class="mb-4">
                  <div class="form-check">
                    <input type="checkbox" v-model="hasDiabetes" @change="updateDiabetes" 
                           id="hasDiabetes" class="form-check-input" :disabled="isUpdatingDiabetes">
                    <label for="hasDiabetes" class="form-check-label fw-bold">
                      Cukorbeteg vagyok
                    </label>
                    <div class="form-text text-muted">
                      Ha cukorbeteg vagy, a konyha ezt figyelembe veszi az ételeid tervezésénél.
                    </div>
                  </div>
                </div>
                
                <!-- Allergens -->
                <div>
                  <h6 class="mb-3">Allergéneim</h6>
                  
                  <!-- Current Allergens -->
                  <div v-if="userAllergens.length > 0" class="mb-4">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <div v-for="allergen in userAllergens" :key="allergen.id" 
                           class="badge bg-info d-flex align-items-center gap-2">
                        <img v-if="allergen.icon" :src="getAllergenIcon(allergen.icon)" 
                             :alt="allergen.allergenName" class="allergen-icon">
                        <span>{{ allergen.allergenName }}</span>
                        <button @click="removeAllergen(allergen.id)" class="btn-close btn-close-white btn-sm ms-1"
                                title="Eltávolítás"></button>
                      </div>
                    </div>
                  </div>
                  <div v-else class="mb-4">
                    <p class="text-muted fst-italic">Nincsenek allergéneid beállítva.</p>
                  </div>
                  
                  <!-- Add Allergen -->
                  <div>
                    <label class="form-label">Új allergén hozzáadása</label>
                    <div class="input-group">
                      <select v-model="newAllergenId" class="form-select" :disabled="availableAllergens.length === 0">
                        <option value="">Válassz allergént...</option>
                        <option v-for="allergen in availableAllergens" 
                                :key="allergen.id" 
                                :value="allergen.id">
                          {{ allergen.allergenName }}
                        </option>
                      </select>
                      <button @click="addAllergen" 
                              class="btn btn-success" 
                              :disabled="!newAllergenId || isAddingAllergen">
                        <i class="fas fa-plus me-2"></i>
                        <span v-if="isAddingAllergen">Hozzáadás...</span>
                        <span v-else>Hozzáadás</span>
                      </button>
                    </div>
                    <div v-if="availableAllergens.length === 0" class="form-text text-muted">
                      Nincs elérhető allergén hozzáadásra
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- User Info Summary -->
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Egyéb információk</h5>
              </div>
              <div class="card-body">
                <dl class="row">
                  <dt class="col-sm-4">Felhasználó típus:</dt>
                  <dd class="col-sm-8">{{ userType }}</dd>
                  
                  <dt class="col-sm-4">Osztály:</dt>
                  <dd class="col-sm-8">{{ className || '-' }}</dd>
                  
                  <dt class="col-sm-4">Csoport:</dt>
                  <dd class="col-sm-8">{{ groupName || '-' }}</dd>
                  
                  <dt class="col-sm-4">Regisztráció dátuma:</dt>
                  <dd class="col-sm-8">{{ createdAt }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'UserProfile',
  data() {
    return {
      // User data
      user: null,
      
      // Profile form
      profileForm: {
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        address: ''
      },
      
      // Password form
      passwordForm: {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      },
      
      // Health data
      hasDiabetes: false,
      userAllergens: [],
      availableAllergens: [],
      newAllergenId: '',
      
      // UI states
      isLoading: true,
      isUpdating: false,
      isChangingPassword: false,
      isAddingAllergen: false,
      isUpdatingDiabetes: false,
      
      // Messages
      message: '',
      messageType: '',
      error: ''
    };
  },
  
  computed: {
    userType() {
      return this.user?.userType || 'Ismeretlen';
    },
    className() {
      return this.user?.studentClass?.className || '';
    },
    groupName() {
      return this.user?.group?.groupName || '';
    },
    createdAt() {
      if (!this.user?.created_at) return '';
      return new Date(this.user.created_at).toLocaleDateString('hu-HU');
    }
  },
  
  async created() {
    await this.loadUserData();
  },
  
  methods: {
    // Helper function to get allergen icon URL
    getAllergenIcon(iconPath) {
      if (!iconPath) return '';
      if (iconPath.startsWith('http')) return iconPath;
      return `/storage/${iconPath.replace(/^\/+/, '')}`;
    },
    
    // Load user data
    async loadUserData() {
      this.isLoading = true;
      this.error = '';
      
      try {
        // Try to get user data
        const userResponse = await axios.get('/user/me');

        // Ellenőrizd a válasz struktúrát
        console.log('User response:', userResponse.data);
    
        // Azt feltételezem, hogy a data.data struktúrában jön a user
        this.user = userResponse.data.data || userResponse.data.user || userResponse.data;
        
        // Fill profile form
        this.profileForm = {
          firstName: this.user.firstName || '',
          lastName: this.user.lastName || '',
          thirdName: this.user.thirdName || '',
          email: this.user.email || '',
          address: this.user.address || ''
        };
        
        // Load health data
        await this.loadHealthData();
        
        // Load available allergens
        await this.loadAvailableAllergens();
        
      } catch (error) {
        console.error('Error loading user data:', error);
        this.error = 'Hiba történt az adatok betöltése során. Ellenőrizd az internetkapcsolatot és jelentkezz be újra.';
      } finally {
        this.isLoading = false;
      }
    },
    
    // Load health data
async loadHealthData() {
  try {
    const response = await axios.get('/user/health');
    
    // Ellenőrizd a válasz struktúrát
    console.log('Health data response:', response.data);
    
    // Allergének kezelése
    let userAllergens = [];
    if (response.data.allergens && Array.isArray(response.data.allergens)) {
      userAllergens = response.data.allergens;
    } else if (Array.isArray(response.data)) {
      userAllergens = response.data;
    } else if (response.data.data && Array.isArray(response.data.data)) {
      userAllergens = response.data.data;
    }
    
    // Diabetes állapot kezelése
    let hasDiabetes = false;
    if (typeof response.data.has_diabetes !== 'undefined') {
      hasDiabetes = response.data.has_diabetes;
    } else if (typeof response.data.hasDiabetes !== 'undefined') {
      hasDiabetes = response.data.hasDiabetes;
    }
    
    this.userAllergens = userAllergens;
    this.hasDiabetes = hasDiabetes;
    
  } catch (error) {
    console.error('Error loading health data:', error);
    console.error('Error response:', error.response?.data);
    this.showMessage('Hiba történt az egészségügyi adatok betöltése során.', 'error');
  }
},
    
    // Load available allergens
async loadAvailableAllergens() {
  try {
    const response = await axios.get('/allergens');
    
    // Ellenőrizd a válasz struktúrát
    console.log('Allergens API response:', response.data);
    
    // Többféle lehetséges válasz struktúra kezelése
    let allAllergens = [];
    
    if (Array.isArray(response.data)) {
      // Ha a válasz maga egy tömb
      allAllergens = response.data;
    } else if (response.data.data && Array.isArray(response.data.data)) {
      // Ha van data mező, ami tömb
      allAllergens = response.data.data;
    } else if (response.data.allergens && Array.isArray(response.data.allergens)) {
      // Ha van allergens mező, ami tömb
      allAllergens = response.data.allergens;
    } else if (typeof response.data === 'object' && response.data !== null) {
      // Ha objektum, próbáljuk kinyerni belőle az értékeket
      allAllergens = Object.values(response.data);
    }
    
    // Ellenőrizd, hogy végül tömb-e
    if (!Array.isArray(allAllergens)) {
      console.warn('Allergens is not an array:', allAllergens);
      allAllergens = [];
    }
    
    // Filter out allergens already added by user
    const userAllergenIds = this.userAllergens.map(a => a.id);
    this.availableAllergens = allAllergens.filter(
      allergen => allergen && allergen.id && !userAllergenIds.includes(allergen.id)
    );
    
    // Debug információ
    console.log('Available allergens after filtering:', this.availableAllergens);
    
  } catch (error) {
    console.error('Error loading allergens:', error);
    console.error('Error response:', error.response?.data);
    this.availableAllergens = [];
  }
},
    
    // Update profile
    async updateProfile() {
      this.isUpdating = true;
      this.clearMessage();
      
      try {
        const response = await axios.put('/user/update', this.profileForm);
        
        this.user = response.data.data;
        this.showMessage('Profiladatok sikeresen frissítve!', 'success');
        
      } catch (error) {
        console.error('Error updating profile:', error);
        
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.showMessage(errors.join(', '), 'error');
        } else {
          this.showMessage(error.response?.data?.message || 'Hiba történt a profil frissítése során.', 'error');
        }
      } finally {
        this.isUpdating = false;
      }
    },
    
    // Change password
    async changePassword() {
      if (this.passwordForm.new_password !== this.passwordForm.new_password_confirmation) {
        this.showMessage('Az új jelszavak nem egyeznek!', 'error');
        return;
      }
      
      this.isChangingPassword = true;
      this.clearMessage();
      
      try {
        await axios.post('/auth/change-password', this.passwordForm);
        
        this.showMessage('Jelszó sikeresen megváltoztatva!', 'success');
        
        // Reset form
        this.passwordForm = {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        };
        
      } catch (error) {
        console.error('Error changing password:', error);
        
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.showMessage(errors.join(', '), 'error');
        } else {
          this.showMessage(error.response?.data?.message || 'Hiba történt a jelszó módosítása során.', 'error');
        }
      } finally {
        this.isChangingPassword = false;
      }
    },
    
    // Add allergen
    async addAllergen() {
  if (!this.newAllergenId) return;
  
  this.isAddingAllergen = true;
  this.clearMessage();
  
  try {
    await axios.post('/user/allergens', {
      allergen_id: this.newAllergenId
    });
    
    // Refresh data - várjunk egy kicsit, hogy biztosan frissüljön
    await Promise.all([
      this.loadHealthData(),
      this.loadAvailableAllergens()
    ]);
    
    this.newAllergenId = '';
    this.showMessage('Allergén sikeresen hozzáadva!', 'success');
    
  } catch (error) {
    console.error('Error adding allergen:', error);
    console.error('Error response:', error.response?.data);
    
    let errorMessage = 'Hiba történt az allergén hozzáadása során.';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat();
      errorMessage = errors.join(', ');
    }
    
    this.showMessage(errorMessage, 'error');
  } finally {
    this.isAddingAllergen = false;
  }
},
    
    // Remove allergen
    async removeAllergen(allergenId) {
      this.clearMessage();
      
      if (!confirm('Biztosan eltávolítod ezt az allergént?')) {
        return;
      }
      
      try {
        await axios.delete(`/user/allergens/${allergenId}`);
        
        // Refresh data
        await this.loadHealthData();
        await this.loadAvailableAllergens();
        
        this.showMessage('Allergén sikeresen eltávolítva!', 'success');
        
      } catch (error) {
        console.error('Error removing allergen:', error);
        this.showMessage(error.response?.data?.message || 'Hiba történt az allergén eltávolítása során.', 'error');
      }
    },
    
    // Update diabetes status
    async updateDiabetes() {
      this.isUpdatingDiabetes = true;
      this.clearMessage();
      
      try {
        await axios.put('/user/diabetes', {
          has_diabetes: this.hasDiabetes
        });
        
        this.showMessage('Cukorbetegség állapota frissítve!', 'success');
        
      } catch (error) {
        console.error('Error updating diabetes:', error);
        this.showMessage('Hiba történt a cukorbetegség állapotának frissítése során.', 'error');
        // Revert checkbox state on error
        this.hasDiabetes = !this.hasDiabetes;
      } finally {
        this.isUpdatingDiabetes = false;
      }
    },
    
    // Retry loading
    retryLoading() {
      this.loadUserData();
    },
    
    // Message helpers
    showMessage(text, type = 'success') {
      this.message = text;
      this.messageType = type;
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        this.clearMessage();
      }, 5000);
    },
    
    clearMessage() {
      this.message = '';
      this.messageType = '';
    }
  }
};
</script>

<style scoped>
.allergen-icon {
  width: 16px;
  height: 16px;
  object-fit: contain;
}

.badge {
  font-size: 0.9em;
  padding: 0.5em 0.8em;
}

.btn-close {
  filter: invert(1);
  opacity: 0.7;
}

.btn-close:hover {
  opacity: 1;
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.spinner-border {
  width: 3rem;
  height: 3rem;
}

dl {
  margin-bottom: 0;
}

dt {
  font-weight: 600;
}

dd {
  margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
  .card {
    margin-bottom: 1rem;
  }
}
</style>