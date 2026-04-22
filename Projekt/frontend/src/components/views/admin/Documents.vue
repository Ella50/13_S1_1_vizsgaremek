<template>
  <div class="admin-documents">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Dokumentumok kezelése</h1>
        
        <div class="header-actions">
          <div class="search-box">
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Keresés név vagy email alapján..."
              maxlength="255" 
              class="search-input"
            >
            <span class="search-icon">🔍</span>
          </div>
          
          <div class="filter-tabs">
            <button 
              :class="['filter-tab', { active: activeFilter === 'pending' }]"
              @click="setFilter('pending')"
            >
              Függőben lévő
            </button>
            <button 
              :class="['filter-tab', { active: activeFilter === 'accepted' }]"
              @click="setFilter('accepted')"
            >
              Elfogadva
            </button>
            <button 
              :class="['filter-tab', { active: activeFilter === 'all' }]"
              @click="setFilter('all')"
            >
              Összes
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <div class="spinner"></div>
        <p>Dokumentumok betöltése...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-message">
        <p>{{ error }}</p>
        <button @click="loadDocuments" class="btn-secondary">Újrapróbálkozás</button>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Success message -->
        <transition name="fade">
          <div v-if="successMessage" class="success-message">
            {{ successMessage }}
          </div>
        </transition>

        <!-- Documents table -->
        <div class="table-wrapper">
          <table class="data-table">
            <thead>
              <tr>
                <th>Felhasználó</th>
                <th>Típus</th>
                <th>Dokumentum neve</th>
                <th>Feltöltve</th>
                <th>Státusz</th>
                <th>Műveletek</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="documents.length === 0">
                <td colspan="6" class="empty-row">
                  Nincsenek megjeleníthető dokumentumok
                </td>
              </tr>
              <tr v-for="doc in documents" :key="doc.id">
                <td>
                  <div class="user-info">
                    <strong>{{ doc.user?.firstName }} {{ doc.user?.lastName }}</strong>
                    <small class="user-email">{{ doc.user?.email }}</small>
                  </div>
                </td>
                <td>
                  <span :class="['type-badge', doc.type === 'discount' ? 'type-discount' : 'type-diabetes']">
                    {{ getTypeDisplayName(doc.type) }}
                  </span>
                </td>
                <td>
                  <div class="file-info">
                    <span>{{ doc.fileName }}</span>
                  </div>
                </td>
                <td>{{ formatDate(doc.created_at) }}</td>
                <td>
                  <span :class="['status-badge', doc.isAccepted ? 'status-accepted' : 'status-pending']">
                    {{ doc.isAccepted ? 'Elfogadva' : 'Függőben' }}
                  </span>
                </td>
                <td class="actions-cell">
                  <div class="actions-group">
                    <button @click="downloadDocument(doc)" class="btn-action btn-download">
                      Letöltés
                    </button>
                    
                    <button 
                      v-if="!doc.isAccepted"
                      @click="acceptDocument(doc)" 
                      class="btn-action btn-accept" 
                      title="Elfogadás"
                    >
                      Elfogadás
                    </button>
                    
                    <button 
                      @click="confirmRejectDelete(doc)" 
                      class="btn-action btn-delete" 
                      title="Elutasítás / Törlés"
                    >
                      {{ doc.isAccepted ? 'Törlés' : 'Elutasítás' }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination
          :current-page="currentPage"
          :last-page="lastPage"
          :total="total"
          :per-page="perPage"
          :per-page-options="[10, 25, 50, 100]"
          @update:page="changePage"
          @update:perPage="changePerPage"
        />
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import Pagination from '../../layout/Pagination.vue';

export default {
  name: 'Documents',

  components: {
    Pagination
  },
  data() {
    return {
      documents: [],
      isLoading: true,
      error: '',
      successMessage: '',
      activeFilter: 'pending', // pending, accepted, all
      searchQuery: '' ,
      searchTimeout: null,

      currentPage: 1,
      lastPage: 1,
      total: 0,
      perPage: 25,
    };
  },

  created() {
    this.loadDocuments();
  },

  computed: {
    
  
  },



  methods: {
    async loadDocuments() {
  this.isLoading = true;
  this.error = '';

  try {
    const params = {
      page: this.currentPage,
      per_page: this.perPage,
      search: this.searchQuery,
      status: this.activeFilter,  // 'pending', 'accepted', 'all'
    };
    const response = await axios.get('/admin/documents', { params });
    
    let data = response.data;
    // BOM kezelés (ha szükséges)
    if (typeof data === 'string') {
      if (data.charCodeAt(0) === 0xFEFF || data.charCodeAt(0) === 65279) {
        data = data.slice(1);
      }
      data = JSON.parse(data);
    }

    if (data.success) {
      this.documents = data.data || [];
      this.currentPage = data.current_page || 1;
      this.lastPage = data.last_page || 1;
      this.total = data.total || 0;
      this.perPage = data.per_page || 25;
    } else {
      this.error = 'Hiba történt a dokumentumok betöltése során.';
    }
  } catch (error) {
    console.error('Error loading documents:', error);
    this.error = 'Hiba történt a dokumentumok betöltése során.';
  } finally {
    this.isLoading = false;
  }
},

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

    async acceptDocument(doc) {
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      if (confirm(`Biztosan elfogadod ezt a ${typeText} dokumentumot?`)) {
        try {
          await axios.post(`/admin/documents/${doc.id}/accept`);
          this.successMessage = 'Dokumentum sikeresen elfogadva!';
          this.loadDocuments();
          setTimeout(() => { this.successMessage = ''; }, 3000);
        } catch (error) {
          console.error('Accept error:', error);
          this.showError('Hiba történt az elfogadás során.');
        }
      }
    },

    // Egységes elutasítás/törlés funkció - véglegesen törli a dokumentumot
    confirmRejectDelete(doc) {
      const typeText = doc.type === 'discount' ? 'kedvezmény' : 'cukorbetegség';
      const actionText = doc.isAccepted ? 'véglegesen törölni' : 'elutasítani';
      
      if (confirm(`FIGYELEM! Biztosan ${actionText} szeretnéd ezt a ${typeText} dokumentumot? Ez a művelet nem visszavonható!`)) {
        this.rejectDeleteDocument(doc);
      }
    },

    async rejectDeleteDocument(doc) {
      try {
        // Végleges törlés - ugyanaz az endpoint, mint a force delete
        await axios.delete(`/admin/documents/${doc.id}/force`);
        const message = doc.isAccepted ? 'Dokumentum véglegesen törölve!' : 'Dokumentum elutasítva és törölve!';
        this.successMessage = message;
        this.loadDocuments();
        setTimeout(() => { this.successMessage = ''; }, 3000);
      } catch (error) {
        console.error('Delete error:', error);
        this.showError('Hiba történt a művelet során.');
      }
    },

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
      if (mimeType?.includes('pdf')) return '📄';
      if (mimeType?.includes('word') || mimeType?.includes('doc')) return '📝';
      if (mimeType?.includes('image')) return '🖼️';
      return '📎';
    },

    getTypeDisplayName(type) {
      return type === 'discount' ? 'Kedvezmény' : 'Cukorbetegség';
    },

    showError(message) {
      this.error = message;
      setTimeout(() => { this.error = ''; }, 5000);
    },
     changePage(page) {
    if (page < 1 || page > this.lastPage) return;
    this.currentPage = page;
    this.loadDocuments();
    window.scrollTo(0, 0);
  },

  changePerPage(newPerPage) {
    this.perPage = newPerPage;
    this.currentPage = 1;
    this.loadDocuments();
  },

  onSearchInput() {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
      this.currentPage = 1;
      this.loadDocuments();
    }, 300);
  },

  // Szűrő váltás
  setFilter(filter) {
    this.activeFilter = filter;
    this.currentPage = 1;
    this.loadDocuments();
  },
  }
};
</script>

<style scoped>
.admin-documents {
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

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.filter-tabs {
  display: flex;
  gap: 0.5rem;
  background: #f5f5f5;
  padding: 0.25rem;
  border-radius: 12px;
}

.filter-tab {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-tab:hover {
  background: #e9ecef;
  color: #333;
}

.filter-tab.active {
  background: #f0a24a;
  color: #7b2c2c;
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

.success-message {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  text-align: center;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
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

.user-info {
  display: flex;
  flex-direction: column;
}

.user-info strong {
  font-size: 0.85rem;
  color: #333;
}

.user-email {
  font-size: 0.7rem;
  color: #888;
  margin-top: 2px;
}

.type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.type-discount {
  background: #d2ecd0;
  color: #324436;
}

.type-diabetes {
  background: #beedf3;
  color: #0e3149;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}


.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.status-accepted {
  background: #d4edda;
  color: #155724;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
}

.actions-cell {
  white-space: nowrap;
}

.actions-group {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-action {
  padding: 0.4rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.btn-download {
  background: #e8eaf6;
  color: #3f51b5;
}

.btn-download:hover {
  background: #c5cae9;
}

.btn-accept {
  background: #e8f5e9;
  color: #2e7d32;
}

.btn-accept:hover {
  background: #c8e6c9;
}

.btn-delete {
  background: #ffebee;
  color: #c62828;
}

.btn-delete:hover {
  background: #ffcdd2;
}

.btn-secondary {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #e9ecef;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.85rem;
  transition: background 0.2s;
}

.btn-secondary:hover {
  background: #dee2e6;
}

.empty-row {
  text-align: center;
  color: #888;
  padding: 3rem !important;
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


@media (max-width: 768px) {
  .search-input {
    width: 100%;
  }
  
  .search-box {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .admin-documents {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .title {
    font-size: 1.25rem;
  }

  .filter-tabs {
    width: 100%;
  }

  .filter-tab {
    flex: 1;
    justify-content: center;
  }

  .actions-group {
    flex-wrap: wrap;
  }

  .btn-action {
    padding: 0.3rem 0.6rem;
    font-size: 0.7rem;
  }
}

@media (max-width: 480px) {
  .data-table th,
  .data-table td {
    padding: 0.5rem;
    font-size: 0.75rem;
  }

  .user-info strong {
    font-size: 0.75rem;
  }
  
  .btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.65rem;
  }
}
</style>