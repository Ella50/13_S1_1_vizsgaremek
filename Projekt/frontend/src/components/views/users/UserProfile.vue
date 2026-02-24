<template>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header">
        <h1 class="mb-0">Profilom</h1>
      </div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="card-body text-center py-5 loading">
        <div class="spinner" role="status"></div>
        <p>Adatok betöltése...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="card-body">
        <div class="alert alert-danger">
          <h4 class="alert-heading">Hiba</h4>
          <p>{{ error }}</p>
          <button @click="retryLoading" class="btn btn-outline-danger">
             Újra
          </button>
        </div>
      </div>
      
      <!-- Content -->
      <div v-else class="card-body">
        <!-- Success/Error Messages 
        <div v-if="message" :class="['alert', messageType === 'success' ? 'alert-success' : 'alert-danger']">
          {{ message }}
        </div>-->
        
        <div class="row">

          <div class="col-md-6">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0">Személyes adatok</h5>
              </div>
              <div class="card-body">
                <form @submit.prevent="updateProfile">
                  <div class="mb-3">
                    <label class="form-label">Vezetéknév*</label>
                    <input type="text" v-model="profileForm.firstName" class="form-control" disabled="">
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Keresztnév*</label>
                    
                    <input type="text" v-model="profileForm.lastName" class="form-control" disabled="">
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Középső név</label>
                    <input type="text" v-model="profileForm.thirdName" class="form-control" disabled="">
                  </div>
                  
                  <div class="mb-3">
                    <label class="form-label">Email cím *</label>
                    <input type="email" v-model="profileForm.email" class="form-control" disabled="">
                  </div>

                  
                  
                  <div class="mb-3">
                    <label class="form-label">Irányítószám, város</label>
                    <input type="text" v-model="profileForm.city" class="form-control" disabled="">
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Cím</label>
                    <input type="text" v-model="profileForm.address" class="form-control" disabled="">
                  </div>
                  
                </form>
              </div>
            </div>
            
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Jelszó megváltoztatása</h5>
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
                  
                  <button type="submit" class="changepassword-btn" :disabled="isChangingPassword">
                    <span v-if="isChangingPassword">Mentés...</span>
                    <span v-else>Jelszó megváltoztatása</span>
                  </button>
                </form>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="mb-0">Egészségügyi információk</h5>
              </div>
              <div class="card-body">
                <div class="mb-4">
                  <div class="form-check">
                    <input type="checkbox" disabled="" v-model="hasDiabetes" @change="updateDiabetes" 
                           id="hasDiabetes" class="form-check-input" :disabled="isUpdatingDiabetes">
                    <label for="hasDiabetes" class="form-check-label fw-bold">
                      Cukorbeteg vagyok
                    </label>
                  </div>
                </div>
                
                <div>
                  <h6 class="mb-3">Allergéneim</h6>
                  
                  <div v-if="userAllergens.length > 0" class="mb-4">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                      <div v-for="allergen in userAllergens" :key="allergen.id" 
                           class="badge d-flex align-items-center gap-2 allergen-tag" :title="allergen?.allergenName">
                        <img v-if="allergen?.icon" 
                            :src="getAllergenIconUrl(allergen.icon)" 
                            :alt="allergen.allergenName" 
                            class="allergen-icon">
                        <span > {{ allergen.allergenName }}</span>
                        <button @click="removeAllergen(allergen.id)" class="btn-close "
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
                              class="btn-add" 
                              :disabled="!newAllergenId || isAddingAllergen">
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
            

            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Egyéb információk</h5>
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


            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Dokumentumok</h5>
              </div>
              <div class="card-body">
                  
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
      user: null,
      

      profileForm: {
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        zipCode: '',
        city: '',
        address: ''
      },
      

      passwordForm: {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      },
      
      
      hasDiabetes: false,
      userAllergens: [],
      availableAllergens: [],
      newAllergenId: '',
      

      isLoading: true,
      isChangingPassword: false,
      isAddingAllergen: false,
      isUpdatingDiabetes: false,
      

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
getAllergenIconUrl(iconPath) {
      console.log('Meals.vue - iconPath:', iconPath);
  if (!iconPath) {
    return 'https://via.placeholder.com/16x16/3498db/ffffff?text=❓';
  }

  // Tisztítsd az útvonalat
  const cleanPath = iconPath.replace(/^\//, '');
  

  const baseUrl = 'http://localhost:8000';
  const pathsToTry = [
    `${baseUrl}/storage/${cleanPath}`,
    
    `${baseUrl}/images/allergens/${cleanPath.split('/').pop()}`,
    
    `${baseUrl}/${cleanPath.split('/').pop()}`
  ];

  // Debug információk
  console.log('Allergen icon debug:', {
    originalPath: iconPath,
    cleanPath: cleanPath,
    tryingPaths: pathsToTry
  });

  // Visszaadjuk az elsőt, vagy lehetővé tesszük a váltást
  return pathsToTry[1]; // Próbáld a public/images útvonalat
},


handleImageError(event) {
  console.error('Image failed to load:', {
    src: event.target.src,
    alt: event.target.alt
  });
  
  event.target.style.display = 'none';
  const parent = event.target.parentElement;
  if (parent) {
    const fallback = parent.querySelector('.allergen-icon-fallback');
    if (fallback) {
      fallback.style.display = 'flex';
    }
  }
},
    
    // Load user data

async loadUserData() {
  this.isLoading = true;
  this.error = '';
  
  try {
    const userResponse = await axios.get('/user/me');

    console.log('User response:', userResponse.data);
    
    // Ha string, akkor először eltávolítjuk a BOM karaktert, majd parse-oljuk
    let responseData = userResponse.data;
    if (typeof responseData === 'string') {
      console.log('Response is string, removing BOM and parsing JSON...');
      
      // BOM karakter eltávolítása (első karakter ha ﻿)
      if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
        console.log('BOM detected and removed');
        responseData = responseData.slice(1);
      }
      
      try {
        responseData = JSON.parse(responseData);
        console.log('Parsed response:', responseData);
      } catch (parseError) {
        console.error('JSON parse error:', parseError);
        console.error('Raw response (first 100 chars):', responseData.substring(0, 100));
        throw new Error('A válasz nem érvényes JSON formátumú');
      }
    }
    
    // A válaszban a user a 'user' mezőben van
    let userData = null;
    
    if (responseData.user) {
      userData = responseData.user;
      console.log('Using responseData.user');
    } else if (responseData.data) {
      userData = responseData.data;
      console.log('Using responseData.data');
    } else {
      userData = responseData;
      console.log('Using responseData directly');
    }
    
    this.user = userData;
    
    // Ha a user object helyes, akkor töltsük fel a formot
    if (this.user) {
      console.log('User data loaded:', {
        firstName: this.user.firstName,
        lastName: this.user.lastName,
        email: this.user.email,
        city_id: this.user.city_id,
        city: this.user.city // Itt jön a város adat
      });
      
      // Összeállítjuk a város megjelenítését
      let cityDisplay = '';
      if (this.user.city) {
        // Ha van zipCode és cityName
        const zipCode = this.user.city.zipCode || '';
        const cityName = this.user.city.cityName || '';
        cityDisplay = zipCode ? `${zipCode} ${cityName}`.trim() : cityName;
      }
      
      this.profileForm = {
        firstName: this.user.firstName || '',
        lastName: this.user.lastName || '',
        thirdName: this.user.thirdName || '',
        email: this.user.email || '',
        address: this.user.address || '',
        city: cityDisplay // Ezt használd a template-ben
      };
    } else {
      console.error('User object is null or undefined');
    }
    
    // Load health data
    await this.loadHealthData();
    
    // Load available allergens
    await this.loadAvailableAllergens();
    
  } catch (error) {
    console.error('Error loading user data:', error);
    this.error = 'Hiba történt az adatok betöltése során: ' + error.message;
  } finally {
    this.isLoading = false;
  }
},
    


// Load health data
// Load health data
async loadHealthData() {
  try {
    const response = await axios.get('/user/health');
    
    console.log('Health data response:', response.data);
    
    // Ha string, akkor először eltávolítjuk a BOM karaktert, majd parse-oljuk
    let responseData = response.data;
    if (typeof responseData === 'string') {
      console.log('Health response is string, removing BOM and parsing JSON...');
      
      // BOM karakter eltávolítása (első karakter ha ﻿)
      if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
        console.log('BOM detected and removed');
        responseData = responseData.slice(1);
      }
      
      try {
        responseData = JSON.parse(responseData);
        console.log('Parsed health data:', responseData);
      } catch (parseError) {
        console.error('Health data JSON parse error:', parseError);
        console.error('Raw health response (first 100 chars):', responseData.substring(0, 100));
        throw parseError;
      }
    }
    
    // Allergének kezelése
    let userAllergens = [];
    if (responseData.allergens && Array.isArray(responseData.allergens)) {
      for (let i = 0; i < responseData.allergens.length; i++) {
        userAllergens.push({
          id: responseData.allergens[i].id,
          allergenName: responseData.allergens[i].allergenName,
          icon: responseData.allergens[i].icon,
          icon_url: responseData.allergens[i].icon_url
        });
      }
    }
    
    // Diabetes állapot kezelése
    let hasDiabetes = false;
    if (typeof responseData.has_diabetes !== 'undefined') {
      hasDiabetes = responseData.has_diabetes;
    }
    
    this.userAllergens = userAllergens;
    this.hasDiabetes = hasDiabetes;
    
    console.log('User allergens after load:', this.userAllergens);
    
  } catch (error) {
    console.error('Error loading health data:', error);
    this.showMessage('Hiba történt az egészségügyi adatok betöltése során.', 'error');
  }
},

// Load available allergens
async loadAvailableAllergens() {
  try {
    const response = await axios.get('/allergens');
    
    console.log('Allergens API response:', response.data);
    
    // Ha string, akkor először eltávolítjuk a BOM karaktert, majd parse-oljuk
    let responseData = response.data;
    if (typeof responseData === 'string') {
      console.log('Allergens response is string, removing BOM and parsing JSON...');
      
      // BOM karakter eltávolítása (első karakter ha ﻿)
      if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
        console.log('BOM detected and removed');
        responseData = responseData.slice(1);
      }
      
      try {
        responseData = JSON.parse(responseData);
        console.log('Parsed allergens:', responseData);
      } catch (parseError) {
        console.error('Allergens JSON parse error:', parseError);
        console.error('Raw allergens response (first 100 chars):', responseData.substring(0, 100));
        throw parseError;
      }
    }
    
    // Ha a válasz közvetlenül egy tömb
    let allAllergens = [];
    if (Array.isArray(responseData)) {
      for (let i = 0; i < responseData.length; i++) {
        allAllergens.push({
          id: responseData[i].id,
          allergenName: responseData[i].allergenName,
          icon: responseData[i].icon,
          icon_url: responseData[i].icon_url
        });
      }
    }
    
    console.log('All allergens (processed):', allAllergens);
    console.log('User allergens (current):', this.userAllergens);
    
    // User allergen ID-k gyűjtése
    const userAllergenIds = [];
    for (let i = 0; i < this.userAllergens.length; i++) {
      if (this.userAllergens[i] && this.userAllergens[i].id) {
        userAllergenIds.push(this.userAllergens[i].id);
      }
    }
    
    console.log('User allergen IDs:', userAllergenIds);
    
    // Szűrés
    this.availableAllergens = [];
    for (let i = 0; i < allAllergens.length; i++) {
      const allergen = allAllergens[i];
      if (allergen && allergen.id && !userAllergenIds.includes(allergen.id)) {
        this.availableAllergens.push(allergen);
      }
    }
    
    console.log('Available allergens after filtering:', this.availableAllergens);
    
  } catch (error) {
    console.error('Error loading allergens:', error);
    this.availableAllergens = [];
  }
},



    // Update profile

async updateProfile() {
  this.clearMessage();
  
  try {
    const response = await axios.put('/user/update', this.profileForm);
    
    console.log('Update profile response:', response.data);
    
    // Ugyanazt a logikát használd, mint a loadUserData-ban
    this.user = response.data.data || response.data.user || response.data;
    
    // Frissítsd a profile formot is
    this.profileForm = {
      firstName: this.user.firstName || '',
      lastName: this.user.lastName || '',
      thirdName: this.user.thirdName || '',
      email: this.user.email || '',
      address: this.user.address || ''
    };
    
    // Töltsd újra az egészségügyi adatokat is
    await this.loadHealthData();
    await this.loadAvailableAllergens();
    
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
    // Add allergen
async addAllergen() {
  if (!this.newAllergenId) return;
  
  this.isAddingAllergen = true;
  this.clearMessage();
  
  try {
    await axios.post('/user/allergens', {
      allergen_id: this.newAllergenId
    });
    
    // Először töltsük újra a health adatokat
    await this.loadHealthData();
    
    // Majd a rendelkezésre álló allergéneket
    await this.loadAvailableAllergens();
    
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
    
    // Először töltsük újra a health adatokat
    await this.loadHealthData();
    
    // Majd a rendelkezésre álló allergéneket
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
  color: black;
  font-weight: normal;
}

.allergen-tag[title*="Glutén"]{border-color:#6a8b0e50;background:#e7e3a4;}
.allergen-tag[title*="Tej"]{border-color:#3d5e6359;background:#60747775;}
.allergen-tag[title*="Tojás"]{border-color:#6e42c160;background:#3a0e4e4f;}
.allergen-tag[title*="Hal"]{border-color:#dfc01350;background:#ffdb79c5;}
.allergen-tag[title*="Dió"]{border-color:#f87f1c6c;background:#ca8d5c7e;}
.allergen-tag[title*="Földimogyoró"]{border-color:#df77227e;background:#f7c584b9;}
.allergen-tag[title*="Zeller"]{border-color:#7849b644;background:#967ebec4;}
.allergen-tag[title*="Rákfélék"]{border-color:#2dc5be44;background:#6bafbbc2;}
.allergen-tag[title*="Mustár"]{border-color:#17157262;background:#84889eb9;}
.allergen-tag[title*="Kukorica"]{border-color:#5a244262;background:#df6fa3b9;}
.allergen-tag[title*="Szójabab"]{border-color:#5a244262;background:#e48da3b9;}


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
  border-bottom: 1px solid #dee2e6;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--barack);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading {
  text-align: center;
  padding: 3rem;
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


.changepassword-btn{
  background: #1fa317;;
}

.btn-add, .changepassword-btn{
  margin-top: 10px;
}
</style>