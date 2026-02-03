<template>
  <div class="user-profile-container">
    <div class="card">
      <div class="card-header">
        <h2>Profilom</h2>
        <p>Személyes adataid és egészségügyi információk</p>
      </div>
      
      <!-- Alerts -->
      <div v-if="successMessage" class="alert alert-success">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="alert alert-danger">
        {{ errorMessage }}
      </div>

      <div class="profile-content">
        <!-- User Info Section -->
        <div class="profile-section">
          <h3>Személyes adatok</h3>
          
          <form @submit.prevent="updateProfile">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Keresztnév *</label>
                  <input type="text" v-model="profileForm.firstName" class="form-control" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Vezetéknév *</label>
                  <input type="text" v-model="profileForm.lastName" class="form-control" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Középső név</label>
                  <input type="text" v-model="profileForm.thirdName" class="form-control">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Email cím *</label>
                  <input type="email" v-model="profileForm.email" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Település</label>
                  <input type="text" v-model="profileForm.city" class="form-control" disabled>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>Cím</label>
                  <input type="text" v-model="profileForm.address" class="form-control">
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="isUpdating">
                <span v-if="isUpdating">Mentés...</span>
                <span v-else>Adatok mentése</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Health Info Section -->
        <div class="profile-section">
          <div class="section-header">
            <h3>Egészségügyi információk</h3>
          </div>

          <!-- Allergens Management -->
          <div class="allergen-management">
            <h4>Allergéneim</h4>
            
            <!-- Current Allergens -->
            <div v-if="userAllergens.length > 0" class="current-allergens">
              <div class="allergen-list">
                <div v-for="allergen in userAllergens" :key="allergen.id" class="allergen-item">
                  <div class="allergen-info">
                    <img v-if="allergen.icon_url" :src="allergen.icon_url" :alt="allergen.allergenName" class="allergen-icon">
                    <span>{{ allergen.allergenName }}</span>
                  </div>
                  <button @click="removeAllergen(allergen.id)" class="btn btn-sm btn-danger" title="Eltávolítás">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="no-allergens">
              <p class="text-muted">Nincsenek allergéneid beállítva.</p>
            </div>

            <!-- Add Allergen -->
            <div class="add-allergen-form">
              <div class="row align-items-end">
                <div class="col-md-8">
                  <div class="form-group">
                    <label>Új allergén hozzáadása</label>
                    <select v-model="newAllergenId" class="form-control" :disabled="availableAllergens.length === 0">
                      <option value="">Válassz allergént...</option>
                      <option v-for="allergen in availableAllergens" 
                              :key="allergen.id" 
                              :value="allergen.id">
                        {{ allergen.allergenName }}
                      </option>
                    </select>
                    <small v-if="availableAllergens.length === 0" class="text-muted">
                      Nincs elérhető allergén hozzáadásra
                    </small>
                  </div>
                </div>
                <div class="col-md-4">
                  <button @click="addAllergen" 
                          class="btn btn-success btn-block" 
                          :disabled="!newAllergenId || isAddingAllergen">
                    <span v-if="isAddingAllergen">Hozzáadás...</span>
                    <span v-else>Hozzáadás</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Diabetes Checkbox -->
          <div class="diabetes-section">
            <div class="form-check">
              <input type="checkbox" v-model="hasDiabetes" @change="updateDiabetes" 
                     id="hasDiabetes" class="form-check-input">
              <label for="hasDiabetes" class="form-check-label">
                <strong>Cukorbeteg vagyok</strong>
              </label>
              <small class="form-text text-muted">
                Ha cukorbeteg vagy, a konyha ezt figyelembe veszi az ételeid tervezésénél.
              </small>
            </div>
          </div>
        </div>

        <!-- Password Change Section -->
        <div class="profile-section">
          <h3>Jelszó megváltoztatása</h3>
          
          <form @submit.prevent="changePassword">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Jelenlegi jelszó *</label>
                  <input type="password" v-model="passwordForm.current_password" class="form-control" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Új jelszó *</label>
                  <input type="password" v-model="passwordForm.new_password" class="form-control" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Új jelszó megerősítése *</label>
                  <input type="password" v-model="passwordForm.new_password_confirmation" class="form-control" required>
                </div>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="isChangingPassword">
                <span v-if="isChangingPassword">Mentés...</span>
                <span v-else>Jelszó megváltoztatása</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '../../../axios';

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
        address: '',
        city: ''
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
      isUpdating: false,
      isChangingPassword: false,
      isAddingAllergen: false,
      isLoading: true,
      
      // Messages
      successMessage: '',
      errorMessage: ''
    };
  },
  
  async created() {
    await this.loadUserData();
    await this.loadAllergens();
  },
  
  methods: {
    async loadUserData() {
      try {
        const response = await axios.get('/user/me');
        this.user = response.data.data;
        
        // Fill profile form
        this.profileForm = {
          firstName: this.user.firstName,
          lastName: this.user.lastName,
          thirdName: this.user.thirdName || '',
          email: this.user.email,
          address: this.user.address || '',
          city: this.user.city ? this.user.city.cityName : ''
        };
        
        // Load health data
        await this.loadHealthData();
        
      } catch (error) {
        console.error('Error loading user data:', error);
        this.showError('Hiba történt az adatok betöltése során.');
      } finally {
        this.isLoading = false;
      }
    },
    
    async loadHealthData() {
      try {
        const response = await axios.get('/user/health');
        this.userAllergens = response.data.allergens;
        this.hasDiabetes = response.data.has_diabetes;
      } catch (error) {
        console.error('Error loading health data:', error);
      }
    },
    
    async loadAllergens() {
      try {
        const response = await axios.get('/allergens');
        const allAllergens = response.data;
        
        // Filter out allergens already added by user
        const userAllergenIds = this.userAllergens.map(a => a.id);
        this.availableAllergens = allAllergens.filter(
          allergen => !userAllergenIds.includes(allergen.id)
        );
        
      } catch (error) {
        console.error('Error loading allergens:', error);
      }
    },
    
    async updateProfile() {
      this.isUpdating = true;
      this.clearMessages();
      
      try {
        const response = await axios.put(`/user/${this.user.id}`, this.profileForm);
        
        this.user = response.data.data;
        this.showSuccess('Profiladatok sikeresen frissítve!');
        
      } catch (error) {
        console.error('Error updating profile:', error);
        
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.showError(errors.join(', '));
        } else {
          this.showError(error.response?.data?.message || 'Hiba történt a profil frissítése során.');
        }
      } finally {
        this.isUpdating = false;
      }
    },
    
    async changePassword() {
      if (this.passwordForm.new_password !== this.passwordForm.new_password_confirmation) {
        this.showError('Az új jelszavak nem egyeznek!');
        return;
      }
      
      this.isChangingPassword = true;
      this.clearMessages();
      
      try {
        const response = await axios.post('/auth/change-password', this.passwordForm);
        
        this.showSuccess('Jelszó sikeresen megváltoztatva!');
        
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
          this.showError(errors.join(', '));
        } else {
          this.showError(error.response?.data?.message || 'Hiba történt a jelszó módosítása során.');
        }
      } finally {
        this.isChangingPassword = false;
      }
    },
    
    async addAllergen() {
      if (!this.newAllergenId) return;
      
      this.isAddingAllergen = true;
      this.clearMessages();
      
      try {
        await axios.post('/user/allergens', {
          allergen_id: this.newAllergenId
        });
        
        // Refresh data
        await this.loadHealthData();
        await this.loadAllergens();
        
        this.newAllergenId = '';
        this.showSuccess('Allergén sikeresen hozzáadva!');
        
      } catch (error) {
        console.error('Error adding allergen:', error);
        this.showError(error.response?.data?.message || 'Hiba történt az allergén hozzáadása során.');
      } finally {
        this.isAddingAllergen = false;
      }
    },
    
    async removeAllergen(allergenId) {
      this.clearMessages();
      
      if (!confirm('Biztosan eltávolítod ezt az allergént?')) {
        return;
      }
      
      try {
        await axios.delete(`/user/allergens/${allergenId}`);
        
        // Refresh data
        await this.loadHealthData();
        await this.loadAllergens();
        
        this.showSuccess('Allergén sikeresen eltávolítva!');
        
      } catch (error) {
        console.error('Error removing allergen:', error);
        this.showError(error.response?.data?.message || 'Hiba történt az allergén eltávolítása során.');
      }
    },
    
    async updateDiabetes() {
      try {
        await axios.put('/user/diabetes', {
          has_diabetes: this.hasDiabetes
        });
        
        this.showSuccess('Cukorbetegség állapota frissítve!');
        
      } catch (error) {
        console.error('Error updating diabetes:', error);
        this.showError('Hiba történt a cukorbetegség állapotának frissítése során.');
      }
    },
    
    showSuccess(message) {
      this.successMessage = message;
      this.errorMessage = '';
      
      // Auto-hide after 5 seconds
      setTimeout(() => {
        this.successMessage = '';
      }, 5000);
    },
    
    showError(message) {
      this.errorMessage = message;
      this.successMessage = '';
    },
    
    clearMessages() {
      this.successMessage = '';
      this.errorMessage = '';
    }
  }
};
</script>

<style scoped>
.user-profile-container {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-bottom: 30px;
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.card-header h2 {
  margin: 0;
  color: #333;
}

.card-header p {
  margin: 5px 0 0 0;
  color: #666;
}

.profile-content {
  padding: 20px;
}

.profile-section {
  margin-bottom: 40px;
  padding-bottom: 30px;
  border-bottom: 1px solid #eee;
}

.profile-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.profile-section h3 {
  margin-bottom: 20px;
  color: #444;
}

.form-group {
  margin-bottom: 20px;
}

.form-actions {
  margin-top: 30px;
  text-align: right;
}

.btn {
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary {
  background: #007bff;
  color: white;
  border: none;
}

.btn-primary:disabled {
  background: #6c757d;
  cursor: not-allowed;
}

.btn-success {
  background: #28a745;
  color: white;
  border: none;
}

.btn-danger {
  background: #dc3545;
  color: white;
  border: none;
  padding: 5px 10px;
}

.alert {
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-danger {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

/* Allergen Styles */
.allergen-management {
  margin-bottom: 30px;
}

.allergen-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 20px;
}

.allergen-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 8px 12px;
  min-width: 200px;
}

.allergen-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.allergen-icon {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

.no-allergens {
  padding: 20px;
  text-align: center;
  background: #f8f9fa;
  border-radius: 4px;
  margin-bottom: 20px;
}

.diabetes-section {
  padding: 20px;
  background: #f8f9fa;
  border-radius: 4px;
}

.form-check {
  display: flex;
  align-items: flex-start;
}

.form-check-input {
  margin-top: 0.3rem;
  margin-right: 10px;
}

.form-check-label {
  font-size: 16px;
}

.form-text {
  display: block;
  margin-top: 5px;
  color: #6c757d;
}

/* Responsive */
@media (max-width: 768px) {
  .user-profile-container {
    padding: 10px;
  }
  
  .profile-section {
    padding: 15px 0;
  }
  
  .allergen-list {
    flex-direction: column;
  }
  
  .allergen-item {
    width: 100%;
  }
}
</style>