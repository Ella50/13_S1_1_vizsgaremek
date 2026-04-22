<template>
  <div class="user-profile">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Profilom</h1>
      </div>

      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Adatok betöltése...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="retryLoading" class="btn-secondary">Újrapróbálkozás</button>
      </div>

      <div v-else class="profile-content">
        <div class="profile-grid">
          <div class="profile-left">
            <!-- Személyes adatok -->
            <div class="info-card">
              <div class="info-card-header">
                <h3>Személyes adatok</h3>
              </div>
              <div class="info-card-body">
                <div class="form-group">
                  <label>Vezetéknév *</label>
                  <input type="text" v-model="profileForm.lastName" class="form-control" disabled>
                </div>

                <div class="form-group">
                  <label>Keresztnév *</label>
                  <input type="text" v-model="profileForm.firstName" class="form-control" disabled>
                </div>

                <div class="form-group">
                  <label>Középső név</label>
                  <input type="text" v-model="profileForm.thirdName" class="form-control" disabled>
                </div>

                <div class="form-group">
                  <label>Email cím *</label>
                  <input type="email" v-model="profileForm.email" class="form-control" disabled>
                </div>

                <form @submit.prevent="updateProfile">
                  <div class="form-group">
                    <label>Megye *</label>
                    <select v-model="selectedCountyId" @change="onCountyChange" class="form-control" required>
                      <option value="">Válassz megyét...</option>
                      <option v-for="county in counties" :key="county.id" :value="county.id">
                        {{ county.countyName }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Város *</label>
                    <select 
                      v-model="selectedCityId" 
                      @change="onCityChange" 
                      class="form-control" 
                      :disabled="!selectedCountyId || isLoadingCities" 
                      required
                    >
                      <option value="">
                        {{ isLoadingCities ? 'Városok betöltése...' : 'Válassz várost...' }}
                      </option>
                      <option v-for="city in cities" :key="city.id" :value="city.id">
                        {{ city.zipCode }} {{ city.cityName }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Cím</label>
                    <input type="text" v-model="profileForm.address" class="form-control" maxlength="255">
                  </div>

                  <button type="submit" class="btn-primary" :disabled="isUpdating">
                    {{ isUpdating ? 'Mentés...' : 'Cím mentése' }}
                  </button>
                </form>
              </div>
            </div>

            <!-- Jelszó megváltoztatása -->
            <div class="info-card">
              <div class="info-card">
                <div class="info-card-header">
                  <h3>Jelszó megváltoztatása</h3>
                </div>
                  <div class="info-card-body">
                    <form @submit.prevent="changePassword">
                      <div class="form-group">
                        <label>Jelenlegi jelszó *</label>
                        <PasswordInput 
                          v-model="passwordForm.current_password"
                          placeholder="Jelenlegi jelszó"
                          :required="true"
                          :maxlength="255"
                          custom-class="profile-style"
                        />
                      </div>

                      <div class="form-group">
                        <label>Új jelszó *</label>
                        <PasswordInput 
                          v-model="passwordForm.new_password"
                          placeholder="Új jelszó (minimum 8 karakter)"
                          :required="true"
                          :minlength="8"
                          :maxlength="255"
                          custom-class="profile-style"
                        />
                      </div>

                      <div class="form-group">
                        <label>Új jelszó megerősítése *</label>
                        <PasswordInput 
                          v-model="passwordForm.new_password_confirmation"
                          placeholder="Jelszó megerősítése"
                          :required="true"
                          :minlength="8"
                          :maxlength="255"
                          custom-class="profile-style"
                        />
                      </div>
                      <button type="submit" class="btn-primary" :disabled="isChangingPassword">
                        {{ isChangingPassword ? 'Mentés...' : 'Jelszó megváltoztatása' }}
                      </button>
                    </form>
                  </div>
                </div>
            </div>
          </div>


          <div class="profile-right">
            <!-- Egészségügyi információk -->
            <div class="info-card">
              <div class="info-card-header">
                <h3>Egészségügyi információk</h3>
              </div>
              <div class="info-card-body">
                <div class="checkbox-group">
                  <label class="checkbox-label">
                    <input type="checkbox" v-model="hasDiabetes" disabled>
                    <span>Cukorbeteg vagyok</span>
                  </label>
                </div>

                <div class="allergens-section">
                  <h4>Allergéneim</h4>
                  <div v-if="userAllergens.length > 0" class="allergens-list">
                    <div v-for="allergen in userAllergens" :key="allergen.id" class="allergen-tag" :title="allergen.allergenName">
                      <img v-if="allergen.icon" :src="getAllergenIconUrl(allergen.icon)" :alt="allergen.allergenName" class="allergen-icon">
                      <span>{{ allergen.allergenName }}</span>
                      <button @click="removeAllergen(allergen.id)" class="allergen-remove" title="Eltávolítás">×</button>
                    </div>
                  </div>
                  <div v-else class="empty-text">Nincsenek allergéneid beállítva.</div>

                  <div class="add-allergen">
                    <label>Új allergén hozzáadása</label>
                    <div class="add-allergen-group">
                      <select v-model="newAllergenId" class="form-control" :disabled="availableAllergens.length === 0">
                        <option value="">Válassz allergént...</option>
                        <option v-for="allergen in availableAllergens" :key="allergen.id" :value="allergen.id">
                          {{ allergen.allergenName }}
                        </option>
                      </select>
                      <button @click="addAllergen" class="btn-secondary" :disabled="!newAllergenId || isAddingAllergen">
                        {{ isAddingAllergen ? 'Hozzáadás...' : 'Hozzáadás' }}
                      </button>
                    </div>
                    <div v-if="availableAllergens.length === 0" class="form-hint">
                      Nincs elérhető allergén hozzáadásra
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Egyéb információk -->
            <div class="info-card">
              <div class="info-card-header">
                <h3>Egyéb információk</h3>
              </div>
              <div class="info-card-body">
                <div class="info-row">
                  <span class="info-label">Felhasználó típus:</span>
                  <span class="info-value">{{ userType }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Kedvezmény:</span>
                  <span class="info-value">{{ hasDiscount ? 'Nem' : 'Igen' }}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Regisztráció dátuma:</span>
                  <span class="info-value">{{ createdAt }}</span>
                </div>
              </div>
            </div>

            <!-- Dokumentumok -->
            <div class="info-card">
              <div class="info-card-header">
                <h3>Dokumentumok</h3>
                <span class="badge">Max 5MB</span>
              </div>
              <div class="info-card-body">
                <div class="doc-hint">Engedélyezett formátumok: PDF, DOC, DOCX, JPG, PNG</div>

                <!-- Kedvezmény dokumentum -->
                <div class="document-section">
                  <h4>Kedvezményre feljogosító dokumentum</h4>

                  <div v-if="discountDocument" class="existing-document">
                    <div class="doc-info">
                      <div class="doc-name">{{ discountDocument.fileName }}</div>
                      <div class="doc-meta">
                        Feltöltve: {{ formatDate(discountDocument.created_at) }} ({{ discountDocument.formatted_size }})
                      </div>
                      <div v-if="discountDocument.isAccepted" class="doc-status status-accepted">Elfogadva</div>
                      <div v-else-if="discountDocument.isActive" class="doc-status status-pending">Jóváhagyásra vár</div>
                      <div v-else class="doc-status status-inactive">Inaktív - El lett távolítva</div>
                    </div>
                    <div class="doc-actions">
                      <button v-if="!discountDocument.isAccepted && discountDocument.isActive" @click="downloadDocument(discountDocument)" class="btn-icon btn-download" title="Letöltés">Letöltés</button>
                      <button @click="confirmDelete(discountDocument)" class="btn-icon btn-delete" title="Törlés">Törlés</button>
                    </div>
                  </div>
                  <div v-else class="no-document">Még nincs feltöltve dokumentum</div>

                  <form v-if="!discountDocument?.isAccepted" @submit.prevent="uploadDocument('discount')" class="upload-form">
                    <div class="upload-group">
                      <input type="file" ref="discountFileInput" @change="handleFileChange('discount', $event)" class="file-input" :accept="allowedFileTypes" :disabled="isUploading.discount">
                      <button type="submit" class="btn-primary" :disabled="!selectedFiles.discount || isUploading.discount">
                        {{ isUploading.discount ? 'Feltöltés...' : 'Feltöltés' }}
                      </button>
                    </div>
                  </form>
                  <div v-else-if="discountDocument?.isAccepted" class="doc-alert success">Dokumentum elfogadva! A kedvezményed érvényes.</div>
                </div>

                <!-- Cukorbetegség dokumentum -->
                <div class="document-section">
                  <h4>Cukorbetegséget igazoló dokumentum</h4>

                  <div v-if="diabetesDocument" class="existing-document">
                    <div class="doc-info">
                      <div class="doc-name">{{ diabetesDocument.fileName }}</div>
                      <div class="doc-meta">
                        Feltöltve: {{ formatDate(diabetesDocument.created_at) }} ({{ diabetesDocument.formatted_size }})
                      </div>
                      <div v-if="diabetesDocument.isAccepted" class="doc-status status-accepted">Elfogadva</div>
                      <div v-else-if="diabetesDocument.isActive" class="doc-status status-pending">Jóváhagyásra vár</div>
                      <div v-else class="doc-status status-inactive">Inaktív - El lett távolítva</div>
                    </div>
                    <div class="doc-actions">
                      <button @click="downloadDocument(diabetesDocument)" class="btn-icon btn-download" title="Letöltés">Letöltés</button>
                      <button v-if="!diabetesDocument.isAccepted && diabetesDocument.isActive" @click="confirmDelete(diabetesDocument)" class="btn-icon btn-delete" title="Törlés">Törlés</button>
                    </div>
                  </div>
                  <div v-else class="no-document">Még nincs feltöltve dokumentum</div>

                  <form v-if="!diabetesDocument?.isAccepted" @submit.prevent="uploadDocument('diabetes')" class="upload-form">
                    <div class="upload-group">
                      <input type="file" ref="diabetesFileInput" @change="handleFileChange('diabetes', $event)" class="file-input" :accept="allowedFileTypes" :disabled="isUploading.diabetes">
                      <button type="submit" class="btn-primary" :disabled="!selectedFiles.diabetes || isUploading.diabetes">
                        {{ isUploading.diabetes ? 'Feltöltés...' : 'Feltöltés' }}
                      </button>
                    </div>
                    <small v-if="selectedFiles.diabetes" class="file-selected">Kiválasztva: {{ selectedFiles.diabetes.name }}</small>
                  </form>
                  <div v-else-if="diabetesDocument?.isAccepted" class="doc-alert success">✅ Dokumentum elfogadva! A cukorbetegséged igazolva van.</div>
                </div>

                <transition name="fade">
                  <div v-if="uploadSuccess" class="doc-alert success">{{ uploadSuccess }}</div>
                </transition>
                <transition name="fade">
                  <div v-if="uploadError" class="doc-alert error">{{ uploadError }}</div>
                </transition>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert értesítés -->
    <div class="alert-container" v-if="alertVisible">
      <div :class="['alert', alertType]">
        <strong v-if="alertTitle">{{ alertTitle }}</strong>
        <div>{{ alertMessage }}</div>
      </div>
    </div>

    <!-- Confirm modal -->
    <div v-if="confirmVisible" class="confirm-overlay">
      <div class="confirm-box">
        <h3 v-if="confirmTitle" class="confirm-title">{{ confirmTitle }}</h3>
        <p class="confirm-message">{{ confirmMessage }}</p>
        <div class="confirm-actions">
          <button class="btn-cancel" @click="confirmCancel">Mégse</button>
          <button class="btn-ok" @click="confirmOk">OK</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import PasswordInput from '../../auth/PasswordInput.vue';

export default {
  name: 'UserProfile',
  components: {
    PasswordInput 
  },
  data() {
    return {
      user: null,
      counties: [],
      cities: [],
      selectedCountyId: null,
      selectedCityId: null,
      isLoadingCities: false,

      profileForm: {
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        city_id: null,
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
      hasDiscount: false,

      isLoading: true,
      isUpdating: false,
      isChangingPassword: false,
      isAddingAllergen: false,

      message: '',
      messageType: '',
      error: '',

      selectedFiles: {
        discount: null,
        diabetes: null
      },
      isUploading: {
        discount: false,
        diabetes: false
      },
      uploadSuccess: '',
      uploadError: '',
      allowedFileTypes: '.pdf,.doc,.docx,.jpg,.jpeg,.png',
      documents: [],
      
      // Alert állapotok
      alertVisible: false,
      alertMessage: '',
      alertTitle: '',
      alertType: 'success',
      alertTimeout: null,
      
      // Confirm állapotok
      confirmVisible: false,
      confirmMessage: '',
      confirmTitle: '',
      confirmResolver: null
    };
  },

  computed: {
    discountDocument() {
      return this.documents.find(doc => doc.type === 'discount');
    },
    diabetesDocument() {
      return this.documents.find(doc => doc.type === 'diabetes');
    },
    userType() {
      return this.user?.userType || 'Ismeretlen';
    },
    hasDiscountValue() {
      return this.user?.hasDiscount !== undefined ? (this.user.hasDiscount ? 'Igen' : 'Nem') : '-';
    },
    className() {
      return this.user?.studentClass?.className || '';
    },
    createdAt() {
      if (!this.user?.created_at) return '';
      return new Date(this.user.created_at).toLocaleDateString('hu-HU');
    }
  },

  async created() {
    await this.loadUserData();
    await this.loadDocuments();
  },
  
  beforeUnmount() {
    if (this.alertTimeout) {
      clearTimeout(this.alertTimeout);
    }
    document.body.style.overflow = '';
  },

  methods: {
    // Alert metódusok
    showAlert({ message, type = 'success', title = '' }) {
      if (this.alertTimeout) {
        clearTimeout(this.alertTimeout);
      }
      
      this.alertMessage = message;
      this.alertType = type;
      this.alertTitle = title;
      this.alertVisible = true;
      
      this.alertTimeout = setTimeout(() => {
        this.alertVisible = false;
      }, 3000);
    },
    
    // Confirm metódusok
    showConfirm({ message, title = '' }) {
      this.confirmMessage = message;
      this.confirmTitle = title;
      this.confirmVisible = true;
      
      return new Promise((resolve) => {
        this.confirmResolver = resolve;
      });
    },
    
    confirmOk() {
      this.confirmVisible = false;
      if (this.confirmResolver) {
        this.confirmResolver(true);
        this.confirmResolver = null;
      }
    },
    
    confirmCancel() {
      this.confirmVisible = false;
      if (this.confirmResolver) {
        this.confirmResolver(false);
        this.confirmResolver = null;
      }
    },

    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },

    async loadDocuments() {
      try {
        const response = await axios.get('/user/documents');
        let data = response.data;

        if (typeof data === 'string') {
          if (data.charCodeAt(0) === 0xFEFF || data.charCodeAt(0) === 65279) {
            data = data.slice(1);
          }
          try {
            data = JSON.parse(data);
          } catch (parseError) {
            console.error('JSON parse error:', parseError);
          }
        }

        let loadedDocs = [];
        if (data && typeof data === 'object') {
          if (data.documents && Array.isArray(data.documents)) {
            loadedDocs = data.documents;
          } else if (Array.isArray(data)) {
            loadedDocs = data;
          }
        }

        this.documents = loadedDocs.map(doc => ({
          id: doc.id,
          type: doc.type,
          fileName: doc.fileName || doc.original_name,
          created_at: doc.created_at,
          formatted_size: doc.formatted_size,
          filePath: doc.filePath,
          mimeType: doc.mimeType,
          isActive: doc.isActive,
          isAccepted: doc.isAccepted || false
        }));
      } catch (error) {
        console.error('Error loading documents:', error);
      }
    },

    handleFileChange(type, event) {
      this.selectedFiles[type] = event.target.files[0];
      this.clearUploadMessages();
    },

    async uploadDocument(type) {
      const file = this.selectedFiles[type];
      if (!file) {
        this.showUploadError('Válassz ki egy fájlt!');
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        this.showUploadError('A fájl mérete nem lehet nagyobb 5MB-nál!');
        return;
      }

      this.isUploading[type] = true;
      this.clearUploadMessages();

      const formData = new FormData();
      formData.append('document', file);
      formData.append('documentType', type);

      try {
        const response = await axios.post('/user/documents', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        await this.loadDocuments();
        this.selectedFiles[type] = null;
        const fileInput = this.$refs[`${type}FileInput`];
        if (fileInput) fileInput.value = '';

        this.uploadSuccess = type === 'discount'
          ? 'Kedvezmény dokumentum sikeresen feltöltve!'
          : 'Cukorbetegség dokumentum sikeresen feltöltve!';

        setTimeout(() => { this.uploadSuccess = ''; }, 3000);
      } catch (error) {
        let errorMessage = 'Hiba történt a feltöltés során.';
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message;
        } else if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          const errorMessages = [];
          for (let field in errors) {
            errorMessages.push(`${field}: ${errors[field].join(', ')}`);
          }
          errorMessage = errorMessages.join(' | ');
        }
        this.showUploadError(errorMessage);
      } finally {
        this.isUploading[type] = false;
      }
    },

    async downloadDocument(fileDoc) {
      try {
        const response = await axios.get(`/user/documents/${fileDoc.id}/download`, {
          responseType: 'blob'
        });
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', fileDoc.fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Download error:', error);
        this.showUploadError('Hiba történt a letöltés során.');
      }
    },

    async confirmDelete(doc) {
      if (doc.isAccepted) {
        this.showAlert({
          message: 'Elfogadott dokumentumot nem törölhetsz!',
          type: 'error'
        });
        return;
      }
      
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      const confirmed = await this.showConfirm({
        title: 'Dokumentum törlése',
        message: `Biztosan törölni szeretnéd ezt a ${typeText} dokumentumot?`
      });
      
      if (confirmed) {
        this.deleteDocument(doc);
      }
    },

    async deleteDocument(doc) {
      try {
        await axios.delete(`/user/documents/${doc.id}`);
        this.documents = this.documents.filter(d => d.id !== doc.id);
        this.uploadSuccess = 'Dokumentum eltávolítva!';
        setTimeout(() => { this.uploadSuccess = ''; }, 3000);
      } catch (error) {
        console.error('Delete error:', error);
        this.showUploadError('Hiba történt a törlés során.');
      }
    },

    clearUploadMessages() {
      this.uploadSuccess = '';
      this.uploadError = '';
    },

    showUploadError(message) {
      this.uploadError = message;
      setTimeout(() => { this.uploadError = ''; }, 5000);
    },

    getAllergenIconUrl(iconPath) {
      if (!iconPath) return 'https://via.placeholder.com/16x16/3498db/ffffff?text=❓';
      const cleanPath = iconPath.replace(/^\//, '');
      const baseUrl = 'http://localhost:8000';
      return `${baseUrl}/images/allergens/${cleanPath.split('/').pop()}`;
    },

    async loadUserData() {
      this.isLoading = true;
      this.error = '';

      try {
        const userResponse = await axios.get('/user/me');
        let responseData = userResponse.data;

        if (typeof responseData === 'string') {
          if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
            responseData = responseData.slice(1);
          }
          try {
            responseData = JSON.parse(responseData);
          } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('A válasz nem érvényes JSON formátumú');
          }
        }

        let userData = null;
        if (responseData.user) userData = responseData.user;
        else if (responseData.data) userData = responseData.data;
        else userData = responseData;

        this.user = userData;

        if (this.user) {
          this.profileForm = {
            firstName: this.user.firstName || '',
            lastName: this.user.lastName || '',
            thirdName: this.user.thirdName || '',
            email: this.user.email || '',
            address: this.user.address || '',
            city_id: this.user.city_id || null
          };

          await this.loadCounties();

          if (this.user.city_id) {
            this.selectedCityId = this.user.city_id;
            if (this.user.city && this.user.city.county_id) {
              this.selectedCountyId = this.user.city.county_id;
              await this.loadCities(this.selectedCountyId);
            }
          }
        }

        await this.loadHealthData();
        await this.loadAvailableAllergens();
        await this.loadDocuments();
      } catch (error) {
        console.error('Error loading user data:', error);
        this.error = 'Hiba történt az adatok betöltése során: ' + error.message;
        this.showAlert({
          message: this.error,
          type: 'error'
        });
      } finally {
        this.isLoading = false;
      }
    },

    async loadCounties() {
      try {
        const response = await axios.get('/user/counties');
        let responseData = response.data;
        if (typeof responseData === 'string') {
          if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
            responseData = responseData.slice(1);
          }
          try {
            responseData = JSON.parse(responseData);
          } catch (parseError) {
            console.error('Counties JSON parse error:', parseError);
          }
        }
        if (responseData.data && Array.isArray(responseData.data)) {
          this.counties = responseData.data;
        } else if (Array.isArray(responseData)) {
          this.counties = responseData;
        }
      } catch (error) {
        console.error('Error loading counties:', error);
        this.showAlert({
          message: 'Hiba történt a megyék betöltésekor',
          type: 'error'
        });
      }
    },

    async loadCities(countyId) {
      if (!countyId) {
        this.cities = [];
        return;
      }
      this.isLoadingCities = true;
      try {
        const response = await axios.get(`/user/cities/${countyId}`);
        let responseData = response.data;
        if (typeof responseData === 'string') {
          if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
            responseData = responseData.slice(1);
          }
          try {
            responseData = JSON.parse(responseData);
          } catch (parseError) {
            console.error('Cities JSON parse error:', parseError);
          }
        }
        if (responseData.data && Array.isArray(responseData.data)) {
          this.cities = responseData.data;
        } else if (Array.isArray(responseData)) {
          this.cities = responseData;
        }
      } catch (error) {
        console.error('Error loading cities:', error);
        this.cities = [];
        this.showAlert({
          message: 'Hiba történt a városok betöltésekor',
          type: 'error'
        });
      } finally {
        this.isLoadingCities = false;
      }
    },

    async onCountyChange() {
      this.selectedCityId = null;
      this.profileForm.city_id = null;
      await this.loadCities(this.selectedCountyId);
    },

    onCityChange() {
      this.profileForm.city_id = this.selectedCityId;
    },

    async updateProfile() {
      this.clearMessage();
      this.isUpdating = true;

      try {
        const response = await axios.put('/user/update', this.profileForm);
        this.user = response.data.data || response.data.user || response.data;
        this.profileForm = {
          firstName: this.user.firstName || '',
          lastName: this.user.lastName || '',
          thirdName: this.user.thirdName || '',
          email: this.user.email || '',
          address: this.user.address || '',
          city_id: this.user.city_id || null
        };
        if (this.user.city_id) {
          this.selectedCityId = this.user.city_id;
          if (this.user.city && this.user.city.county_id) {
            this.selectedCountyId = this.user.city.county_id;
          }
        }
        this.showAlert({
          message: 'Profiladatok sikeresen frissítve!',
          type: 'success'
        });
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } catch (error) {
        console.error('Error updating profile:', error);
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.showAlert({
            message: errors.join(', '),
            type: 'error'
          });
        } else {
          this.showAlert({
            message: error.response?.data?.message || 'Hiba történt a profil frissítése során.',
            type: 'error'
          });
        }
      } finally {
        this.isUpdating = false;
      }
    },

    async changePassword() {
      if (this.passwordForm.new_password !== this.passwordForm.new_password_confirmation) {
        this.showAlert({
          message: 'Az új jelszavak nem egyeznek!',
          type: 'error'
        });
        return;
      }
      if (this.passwordForm.new_password.length < 8) {
        this.showAlert({
          message: 'Az új jelszónak legalább 8 karakter hosszúnak kell lennie!',
          type: 'error'
        });
        return;
      }

      this.isChangingPassword = true;
      this.clearMessage();

      try {
        await axios.post('/user/auth/change-password', this.passwordForm);
        this.showAlert({
          message: 'Jelszó sikeresen megváltoztatva!',
          type: 'success'
        });
        this.passwordForm = {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        };
      } catch (error) {
        console.error('Error changing password:', error);
        if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat();
          this.showAlert({
            message: errors.join(', '),
            type: 'error'
          });
        } else {
          this.showAlert({
            message: error.response?.data?.message || 'Hiba történt a jelszó módosítása során.',
            type: 'error'
          });
        }
      } finally {
        this.isChangingPassword = false;
      }
    },

    async loadHealthData() {
      try {
        const response = await axios.get('/user/health');
        let responseData = response.data;
        if (typeof responseData === 'string') {
          if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
            responseData = responseData.slice(1);
          }
          try {
            responseData = JSON.parse(responseData);
          } catch (parseError) {
            console.error('Health data JSON parse error:', parseError);
            throw parseError;
          }
        }

        let userAllergens = [];
        if (responseData.allergens && Array.isArray(responseData.allergens)) {
          userAllergens = responseData.allergens.map(a => ({
            id: a.id,
            allergenName: a.allergenName,
            icon: a.icon
          }));
        }
        this.userAllergens = userAllergens;
        this.hasDiabetes = responseData.has_diabetes || false;
      } catch (error) {
        console.error('Error loading health data:', error);
        this.showAlert({
          message: 'Hiba történt az egészségügyi adatok betöltése során.',
          type: 'error'
        });
      }
    },

    async loadAvailableAllergens() {
      try {
        const response = await axios.get('/allergens');
        let responseData = response.data;
        if (typeof responseData === 'string') {
          if (responseData.charCodeAt(0) === 0xFEFF || responseData.charCodeAt(0) === 65279) {
            responseData = responseData.slice(1);
          }
          try {
            responseData = JSON.parse(responseData);
          } catch (parseError) {
            console.error('Allergens JSON parse error:', parseError);
            throw parseError;
          }
        }

        let allAllergens = [];
        if (Array.isArray(responseData)) {
          allAllergens = responseData.map(a => ({
            id: a.id,
            allergenName: a.allergenName,
            icon: a.icon
          }));
        }

        const userAllergenIds = this.userAllergens.map(a => a.id);
        this.availableAllergens = allAllergens.filter(a => !userAllergenIds.includes(a.id));
      } catch (error) {
        console.error('Error loading allergens:', error);
        this.availableAllergens = [];
      }
    },

    async addAllergen() {
      if (!this.newAllergenId) return;
      this.isAddingAllergen = true;
      this.clearMessage();

      try {
        await axios.post('/user/allergens', { allergen_id: this.newAllergenId });
        await this.loadHealthData();
        await this.loadAvailableAllergens();
        this.newAllergenId = '';
        this.showAlert({
          message: 'Allergén sikeresen hozzáadva!',
          type: 'success'
        });
      } catch (error) {
        let errorMessage = 'Hiba történt az allergén hozzáadása során.';
        if (error.response?.data?.message) errorMessage = error.response.data.message;
        else if (error.response?.data?.errors) errorMessage = Object.values(error.response.data.errors).flat().join(', ');
        this.showAlert({
          message: errorMessage,
          type: 'error'
        });
      } finally {
        this.isAddingAllergen = false;
      }
    },

    async removeAllergen(allergenId) {
      const confirmed = await this.showConfirm({
        title: 'Allergén eltávolítása',
        message: 'Biztosan eltávolítod ezt az allergént?'
      });
      
      if (!confirmed) return;
      
      this.clearMessage();

      try {
        await axios.delete(`/user/allergens/${allergenId}`);
        await this.loadHealthData();
        await this.loadAvailableAllergens();
        this.showAlert({
          message: 'Allergén sikeresen eltávolítva!',
          type: 'success'
        });
      } catch (error) {
        console.error('Error removing allergen:', error);
        this.showAlert({
          message: error.response?.data?.message || 'Hiba történt az allergén eltávolítása során.',
          type: 'error'
        });
      }
    },

    retryLoading() {
      this.loadUserData();
    },

    showMessage(text, type = 'success') {
      this.showAlert({ message: text, type: type });
    },

    clearMessage() {
      // Üres, mert a showAlert kezeli
    }
  }
};
</script>

<style scoped>
.user-profile {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
}

.content-card {
  background: var(--content-card);
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


.profile-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.profile-left,
.profile-right {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card {
  background: white;
  border: 1px solid #eee;
  border-radius: 16px;
  overflow: hidden;
}

.info-card-header {
  padding: 1rem 1.25rem;
  background: #f8f9fa;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-card-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #8a1212;
}

.info-card-body {
  padding: 1.25rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #666;
  text-transform: uppercase;
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.85rem;
  transition: all 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
}

.form-control:disabled {
  background: #f8f9fa;
  color: #666;
}

.btn-primary {
  padding: 0.5rem 1rem;
  background: #1fa317;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 0.85rem;
}

.btn-primary:hover:not(:disabled) {
  background: #158a0f;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background: #e9ecef;
  color: #495057;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-secondary:hover:not(:disabled) {
  background: #dee2e6;
}

.btn-icon {
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: smaller;
}

.btn-download {
  background: #e3f2fd;
  color: #1976d2;
}

.btn-download:hover {
  background: #bbdef5;
}

.btn-delete {
  background: #ffebee;
  color: #c62828;
}

.btn-delete:hover {
  background: #ffcdd2;
}

.checkbox-group {
  margin-bottom: 1.25rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: not-allowed;
  font-size: 0.85rem;
}

.checkbox-label input {
  width: 18px;
  height: 18px;
  cursor: not-allowed;
}

.allergens-section {
  margin-top: 1rem;
}

.allergens-section h4 {
  margin: 0 0 0.75rem 0;
  font-size: 0.85rem;
  color: #333;
}

.allergens-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.allergen-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem 0.25rem 0.4rem;
  background: #f8f9fa;
  border: 1px solid #e0e0e0;
  border-radius: 20px;
  font-size: 0.75rem;
  transition: all 0.2s;
}

.allergen-tag:hover {
  border-color: #f0a24a;
  transform: translateY(-1px);
}

.allergen-icon {
  width: 18px;
  height: 18px;
  object-fit: contain;
}

.allergen-remove {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: #999;
  padding: 0 0.2rem;
  transition: color 0.2s;
}

.allergen-remove:hover {
  color: #c62828;
}

.empty-text {
  color: #888;
  font-size: 0.8rem;
  font-style: italic;
  margin-bottom: 1rem;
}

.add-allergen {
  margin-top: 0.5rem;
}

.add-allergen label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #666;
  text-transform: uppercase;
}

.add-allergen-group {
  display: flex;
  gap: 0.5rem;
}

.add-allergen-group .form-control {
  flex: 1;
}

.form-hint {
  font-size: 0.7rem;
  color: #888;
  margin-top: 0.25rem;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-row:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 500;
  color: #666;
  font-size: 0.8rem;
}

.info-value {
  color: #333;
  font-size: 0.8rem;
}


.doc-hint {
  font-size: 0.7rem;
  color: #888;
  margin-bottom: 1rem;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.document-section {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #eee;
}

.document-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.document-section h4 {
  margin: 0 0 0.75rem 0;
  font-size: 0.85rem;
  color: #333;
}

.existing-document {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.doc-info {
  flex: 1;
}

.doc-name {
  font-weight: 500;
  font-size: 0.8rem;
  margin-bottom: 0.25rem;
}

.doc-meta {
  font-size: 0.7rem;
  color: #888;
}

.doc-status {
  font-size: 0.7rem;
  margin-top: 0.25rem;
  font-weight: 500;
}

.status-accepted { color: #2e7d32; }
.status-pending { color: #ed6c02; }
.status-inactive { color: #9c27b0; }

.doc-actions {
  display: flex;
  gap: 0.5rem;
}

.no-document {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 0.75rem;
  text-align: center;
  color: #888;
  font-size: 0.75rem;
  margin-bottom: 0.75rem;
}

.upload-form {
  margin-top: 0.5rem;
}

.upload-group {
  display: flex;
  gap: 0.5rem;
}

.file-input {
  flex: 1;
  padding: 0.4rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.8rem;
  background: white;
}

.file-selected {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.7rem;
  color: #2e7d32;
}

.doc-alert {
  padding: 0.75rem;
  border-radius: 8px;
  margin-top: 0.75rem;
  font-size: 0.8rem;
}

.doc-alert.success {
  background: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #c8e6c9;
}

.doc-alert.error {
  background: #ffebee;
  color: #c62828;
  border: 1px solid #ffcdd2;
}

.badge {
  background: #f0a24a;
  color: #7b2c2c;
  padding: 0.2rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
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
  border-radius: 12px;
  text-align: center;
}

/* Alert stílusok */
.alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;
}

.alert {
  margin-bottom: 10px;
  padding: 14px 18px;
  border-radius: 12px;
  color: white;
  min-width: 250px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  animation: slideIn 0.3s ease;
}

.alert.success {
  background: #4CAF50;
}

.alert.error {
  background: #ff4d4f;
}

.alert.warning {
  background: #ff9800;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Confirm stílusok */
.confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10002;
}

.confirm-box {
  background: white;
  padding: 24px;
  border-radius: 14px;
  min-width: 320px;
  text-align: center;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);
  animation: scaleIn 0.25s ease;
}

@keyframes scaleIn {
  from {
    transform: scale(0.6);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.confirm-title {
  margin-bottom: 10px;
}

.confirm-message {
  font-weight: bold;
  text-align: center;
  margin: 10px 0 20px 0;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.confirm-actions .btn-cancel {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.confirm-actions .btn-cancel:hover {
  background: #c0392b;
}

.confirm-actions .btn-ok {
  background: #2ecc71;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.confirm-actions .btn-ok:hover {
  background: #27ae60;
}


.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}


@media (max-width: 768px) {
  .user-profile {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .title {
    font-size: 1.25rem;
  }

  .profile-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .add-allergen-group {
    flex-direction: column;
  }

  .upload-group {
    flex-direction: column;
  }

  .existing-document {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
  }

  .doc-actions {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .info-row {
    flex-direction: column;
    gap: 0.25rem;
  }

  .info-label {
    margin-bottom: 0.25rem;
  }
}
</style>