<template>
  <div class="container mt-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h1 class="mb-0">Dokumentumok kezelése</h1>
        <div class="d-flex gap-3">
          <!-- Szűrő az aktív dokumentumokra -->
          <div class="form-check form-switch d-flex align-items-center">
            <input 
              class="form-check-input me-2" 
              type="checkbox" 
              id="showActiveOnly" 
              v-model="showActiveOnly"
            >
            <label class="form-check-label" for="showActiveOnly">Csak aktív dokumentumok</label>
          </div>
          
          <!-- Statisztika -->
          <span class="badge bg-info d-flex align-items-center">
            Összesen: {{ filteredDocuments.length }} db
          </span>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="isLoading" class="card-body text-center py-5 loading">
        <div class="spinner" role="status"></div>
        <p>Dokumentumok betöltése...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="card-body">
        <div class="alert alert-danger">
          <h4 class="alert-heading">Hiba</h4>
          <p>{{ error }}</p>
          <button @click="loadDocuments" class="btn btn-outline-danger">
            Újra
          </button>
        </div>
      </div>
      
      <!-- Content -->
      <div v-else class="card-body">
        <!-- Sikeres művelet üzenet -->
        <transition name="fade">
          <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ successMessage }}
            <button type="button" class="btn-close" @click="successMessage = ''"></button>
          </div>
        </transition>
        
        <!-- Dokumentumok táblázata -->
        <div class="table-responsive">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>Felhasználó</th>
                <th>Típus</th>
                <th>Dokumentum neve</th>
                <th>Feltöltve</th>
                <th>Státusz</th>
                <th>Elfogadva</th>
                <th>Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredDocuments.length === 0">
                <td colspan="9" class="text-center py-4 text-muted">
                 
                  Nincsenek megjeleníthető dokumentumok
                </td>
              </tr>
              <tr v-for="doc in filteredDocuments" :key="doc.id" :class="{ 'table-secondary': !doc.isActive }">
                <td>
                  <div class="d-flex flex-column">
                    <strong>{{ doc.user?.firstName }} {{ doc.user?.lastName }}</strong>
                    <small class="text-muted">{{ doc.user?.email }}</small>
                  </div>
                </td>
                <td>
                  <span :class="getTypeBadgeClass(doc.type)">
                    {{ getTypeDisplayName(doc.type) }}
                  </span>
                </td>
                <td>
                  <div class="d-flex align-items-center">
                    <i :class="getFileIcon(doc.mimeType)" class="me-2 fs-5"></i>
                    <span>{{ doc.fileName }}</span>
                  </div>
                </td>

                <td>{{ formatDate(doc.created_at) }}</td>
                <td>
                  <span :class="getStatusBadgeClass(doc)">
                    {{ getStatusText(doc) }}
                  </span>
                </td>
                <td>
                  <span v-if="doc.isAccepted" class="badge bg-success">
                    Elfogadva
                  </span>
                  <span v-else class="badge bg-warning text-dark">
                    Függőben
                  </span>
                </td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <!-- Letöltés -->
                    <button 
                      @click="downloadDocument(doc)" 
                      class="btn btn-outline-primary" 
                      title="Letöltés"
                    >
                      ➜]
                    </button>
                    
                    <!-- Elfogadás (csak ha nem elfogadott) -->
                    <button 
                      v-if="!doc.isAccepted && doc.isActive"
                      @click="acceptDocument(doc)" 
                      class="btn btn-outline-success" 
                      title="Elfogadás"
                    >
                      ✓
                    </button>
                    
                    <!-- Elutasítás (csak ha nem elfogadott) -->
                    <button 
                      v-if="!doc.isAccepted && doc.isActive"
                      @click="rejectDocument(doc)" 
                      class="btn btn-outline-warning" 
                      title="Elutasítás"
                    >
                      ✘
                    </button>
                    
                    <!-- Inaktívvá tétel 
                    <button 
                      v-if="doc.isActive && !doc.isAccepted"
                      @click="confirmDeactivate(doc)" 
                      class="btn btn-outline-secondary" 
                      title="Inaktívvá tétel"
                    >
                      
                    </button>-->
                    
                    <!-- Végleges törlés (admin) -->
                    <button 
                      @click="confirmPermanentDelete(doc)" 
                      class="btn btn-outline-danger" 
                      title="Törlés"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Documents',
  data() {
    return {
      documents: [],
      isLoading: true,
      error: '',
      successMessage: '',
      showActiveOnly: false
    };
  },
  
  created() {
    this.loadDocuments();
  },
  
  methods: {
    async loadDocuments() {
      this.isLoading = true;
      this.error = '';
      
      try {
        const response = await axios.get('/admin/documents');
        console.log('Raw response:', response);
        console.log('Response data:', response.data);
        
        let data = response.data;
        
        // BOM kezelés
        if (typeof data === 'string') {
          console.log('Response is string, removing BOM...');
          if (data.charCodeAt(0) === 0xFEFF || data.charCodeAt(0) === 65279) {
            console.log('BOM detected and removed');
            data = data.slice(1);
          }
          
          try {
            data = JSON.parse(data);
            console.log('Parsed JSON:', data);
          } catch (parseError) {
            console.error('JSON parse error:', parseError);
            this.error = 'Hiba történt az adatok feldolgozása során.';
            return;
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
        
        // Feldolgozzuk a dokumentumokat
        this.documents = loadedDocs.map(doc => ({
          id: doc.id,
          type: doc.type,
          fileName: doc.fileName || doc.fileName,
          created_at: doc.created_at,
          formatted_size: doc.formatted_size || doc.formattedSize,
          filePath: doc.filePath,
          fileName: doc.fileName,
          mimeType: doc.mimeType,
          isActive: doc.isActive,
          isAccepted: doc.isAccepted || false,
          user: doc.user
        }));
        
        console.log('Processed documents:', this.documents);
        
      } catch (error) {
        console.error('Error loading documents:', error);
        this.error = 'Hiba történt a dokumentumok betöltése során.';
      } finally {
        this.isLoading = false;
      }
    },
    
    // Dokumentum letöltése
    async downloadDocument(doc) {
      try {
        const response = await axios.get(`/admin/documents/${doc.id}/download`, {
          responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', doc.fileName);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
        
      } catch (error) {
        console.error('Download error:', error);
        this.showError('Hiba történt a letöltés során.');
      }
    },
    
    // Dokumentum elfogadása
    async acceptDocument(doc) {
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      
      if (confirm(`Biztosan elfogadod ezt a ${typeText} dokumentumot?`)) {
        try {
          const response = await axios.post(`/admin/documents/${doc.id}/accept`);
          
          this.successMessage = 'Dokumentum sikeresen elfogadva!';
          this.loadDocuments(); // Újratöltés
          
          setTimeout(() => {
            this.successMessage = '';
          }, 3000);
          
        } catch (error) {
          console.error('Accept error:', error);
          this.showError('Hiba történt az elfogadás során.');
        }
      }
    },
    
    // Dokumentum elutasítása
    async rejectDocument(doc) {
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      
      if (confirm(`Biztosan elutasítod ezt a ${typeText} dokumentumot?`)) {
        try {
          const response = await axios.post(`/admin/documents/${doc.id}/reject`);
          
          this.successMessage = 'Dokumentum elutasítva!';
          this.loadDocuments(); // Újratöltés
          
          setTimeout(() => {
            this.successMessage = '';
          }, 3000);
          
        } catch (error) {
          console.error('Reject error:', error);
          this.showError('Hiba történt az elutasítás során.');
        }
      }
    },
    
    // Inaktívvá tétel megerősítése
    confirmDeactivate(doc) {
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      
      if (confirm(`Biztosan inaktívvá szeretnéd tenni ezt a ${typeText} dokumentumot?`)) {
        this.deactivateDocument(doc);
      }
    },
    
    // Dokumentum inaktívvá tétele
    async deactivateDocument(doc) {
      try {
        await axios.delete(`/user/documents/${doc.id}`);
        
        this.successMessage = 'Dokumentum inaktívvá téve!';
        this.loadDocuments();
        
        setTimeout(() => {
          this.successMessage = '';
        }, 3000);
        
      } catch (error) {
        console.error('Deactivate error:', error);
        this.showError('Hiba történt az inaktívvá tétel során.');
      }
    },
    
    // Végleges törlés megerősítése
    confirmPermanentDelete(doc) {
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      
      if (confirm(`FIGYELEM! Véglegesen törölni szeretnéd ezt a ${typeText} dokumentumot? Ez a művelet nem visszavonható!`)) {
        this.permanentDeleteDocument(doc);
      }
    },
    
    // Dokumentum végleges törlése
    async permanentDeleteDocument(doc) {
      try {
        await axios.delete(`/admin/documents/${doc.id}/force`);
        
        this.successMessage = 'Dokumentum véglegesen törölve!';
        this.loadDocuments();
        
        setTimeout(() => {
          this.successMessage = '';
        }, 3000);
        
      } catch (error) {
        console.error('Permanent delete error:', error);
        this.showError('Hiba történt a végleges törlés során.');
      }
    },
    
    // Segédfüggvények
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    },
    
    getFileIcon(mimeType) {
      if (mimeType?.includes('pdf')) return 'fas fa-file-pdf text-danger';
      if (mimeType?.includes('word') || mimeType?.includes('doc')) return 'fas fa-file-word text-primary';
      if (mimeType?.includes('image')) return 'fas fa-file-image text-success';
      return 'fas fa-file text-secondary';
    },
    
    getTypeBadgeClass(type) {
      return type === 'discount' ? 'badge bg-success' : 'badge bg-danger';
    },
    
    getTypeDisplayName(type) {
      return type === 'discount' ? 'Kedvezmény' : 'Cukorbetegség';
    },
    
    getStatusBadgeClass(doc) {
      if (!doc.isActive) return 'badge bg-secondary';
      return doc.isAccepted ? 'badge bg-success' : 'badge bg-warning text-dark';
    },
    
    getStatusText(doc) {
      if (!doc.isActive) return 'Inaktív';
      return doc.isAccepted ? 'Aktív/Elfogadott' : 'Aktív/Függőben';
    },
    
    showError(message) {
      this.error = message;
      setTimeout(() => {
        this.error = '';
      }, 5000);
    }
  },
  
  computed: {
    filteredDocuments() {
      let filtered = this.documents;
      
      if (this.showActiveOnly) {
        filtered = filtered.filter(doc => doc.isActive);
      }
      
      // Rendezés: először a függőben lévők, majd az elfogadottak, végül az inaktívak
      return filtered.sort((a, b) => {
        if (a.isActive && !a.isAccepted && (!b.isActive || b.isAccepted)) return -1;
        if (b.isActive && !b.isAccepted && (!a.isActive || a.isAccepted)) return 1;
        return new Date(b.created_at) - new Date(a.created_at);
      });
    }
  }
};
</script>

<style scoped>
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--barack, #ff8c69);
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

.table-secondary {
  background-color: #f8f9fa !important;
  opacity: 0.8;
}

.badge {
  font-size: 0.85em;
  padding: 0.5em 0.8em;
}

.btn-group .btn {
  margin: 0 2px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Reszponzív design */
@media (max-width: 768px) {
  .table {
    font-size: 0.9rem;
  }
  
  .btn-group {
    display: flex;
    flex-direction: column;
  }
  
  .btn-group .btn {
    margin: 2px 0;
  }
}
</style>