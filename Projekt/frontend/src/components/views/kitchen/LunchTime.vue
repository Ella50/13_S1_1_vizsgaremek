<template>
  <div class="lunchtime">
    <div class="content-card">
      <div class="card-header">
        <h1 class="title">Ebéd kiadás</h1>
        <div class="status-indicator" :class="{ active: !error }">
          <span class="indicator-dot"></span>
          <span>{{ error ? 'Hiba' : 'Olvasásra kész' }}</span>
        </div>
      </div>

      <div class="reader-panel">
        <div class="reader-icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="12" x2="16" y2="12"></line>
            <line x1="12" y1="8" x2="12" y2="16"></line>
          </svg>
        </div>
        <div class="hint">{{ error ? 'Hiba történt' : 'Érintsd a kártyát az olvasóhoz…' }}</div>
        
        <!--<div v-if="error" class="error-message">
          <span class="error-icon">⚠️</span>
          {{ error }}
        </div>
        -->
      </div>

      <div class="history-section">
        <div class="history-header">
          <h3>Leolvasási előzmények</h3>
          <span class="history-count">{{ history.length }} / 20</span>
        </div>
        
        <div v-if="history.length === 0" class="empty-state">
          <p>Még nem volt leolvasás</p>
          <p class="empty-hint">Érintsd a kártyát a kezdéshez</p>
        </div>
        
        <div v-else class="history-list">
          <div 
            v-for="(item, index) in history" 
            :key="item._key" 
            class="entry" 
            :class="[item.canEat ? 'entry-success' : 'entry-error', { 'entry-new': index === 0 }]"
          >
            <div class="entry-header">
              <div class="user-info">
                <div class="user-name">{{ item.fullName || 'Ismeretlen felhasználó' }}</div>
                <div class="user-badge">
                  <span class="badge-role">{{ getUserRoleDisplay(item.userType) }}</span>
                  <span v-if="item.hasDiabetes" class="badge-diabetic"> Cukorbeteg</span>
                </div>
              </div>
              <div class="entry-time">{{ formatTime(item._scannedAt) }}</div>
            </div>
            
            <div class="entry-details">
              <div class="detail-row">
                <span class="detail-label">Menü opció:</span>
                <span class="detail-value" :class="getOptionClass(item.selectedOption)">
                  {{ getOptionDisplay(item.selectedOption) }}
                </span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Státusz:</span>
                <span class="detail-value status-text" :class="item.canEat ? 'status-success' : 'status-error'">
                  {{ item.message || (item.canEat ? '✅ Ebéd kiadható' : '❌ Nem kiadható') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../../services/authService'

export default {
  name: 'LunchTime',
  data() {
    return {
      since: new Date().toISOString(),
      timer: null,
      lastUid: null,
      error: '',
      history: [],
    }
  },
  mounted() {
    this.timer = setInterval(this.tick, 1000)
  },
  beforeUnmount() {
    if (this.timer) clearInterval(this.timer)
  },
  methods: {
    async tick() {
      try {
        this.error = ''
        const res = await AuthService.api.get('/kitchen/rfid/latest-scan', {
          params: { since: this.since }
        })

        if (!res.data?.success) return

        const scan = res.data.data
        if (!scan?.uid) return

        this.since = scan.scanned_at || new Date().toISOString()

        if (scan.uid === this.lastUid) return
        this.lastUid = scan.uid

        await this.verifyAndPush(scan.uid, scan.scanned_at)
      } catch (e) {
        this.error = e.response?.data?.message || 'Hiba az RFID beolvasásnál.'
      }
    },

    async verifyAndPush(uid, scannedAt) {
      const res = await AuthService.api.post('/kitchen/lunchtime/verify', { uid })
      const data = res.data?.data || {
        status: 'ERROR',
        message: 'Ismeretlen hiba'
      }

      const entry = {
        ...data,
        _scannedAt: scannedAt ? new Date(scannedAt) : new Date(),
        _key: `${uid}-${Date.now()}-${Math.random()}`,
      }

      this.history.unshift(entry)

      if (this.history.length > 20) {
        this.history.length = 20
      }
    },

    formatTime(d) {
      try {
        const date = (d instanceof Date) ? d : new Date(d)
        return date.toLocaleTimeString('hu-HU', { 
          hour: '2-digit', 
          minute: '2-digit', 
          second: '2-digit' 
        })
      } catch {
        return ''
      }
    },

    getUserRoleDisplay(role) {
      const roles = {
        'Admin': 'Rendszergazda',
        'Konyha': 'Konyhai dolgozó',
        'Tanuló': 'Tanuló',
        'Tanár': 'Tanár',
        'Dolgozó': 'Dolgozó'
      }
      return roles[role] || role || 'Ismeretlen'
    },

    getOptionDisplay(option) {
      const options = {
        'A': 'A opció',
        'B': 'B opció',
        'soup': 'Csak leves',
        'other': 'Egyéb'
      }
      return options[option] || option || 'Nincs kiválasztva'
    },

    getOptionClass(option) {
      const classes = {
        'A': 'option-a',
        'B': 'option-b',
        'soup': 'option-soup',
        'other': 'option-other'
      }
      return classes[option] || ''
    },

    getErrorMessage(item) {
      if (item.message) return item.message
      if (!item.canEat) return 'A felhasználó nem jogosult ebédre'
      return 'Ismeretlen hiba'
    }
  }
}
</script>

<style scoped>
.lunchtime {
  padding: 2rem;
  max-width: 1000px;
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

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  background: #f8f9fa;
  border-radius: 30px;
  font-size: 0.8rem;
  color: #666;
}

.status-indicator.active {
  background: #e8f5e9;
  color: #2e7d32;
}

.indicator-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ccc;
  animation: pulse 1.5s infinite;
}

.status-indicator.active .indicator-dot {
  background: #2e7d32;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 0.5; transform: scale(0.8); }
  100% { opacity: 1; transform: scale(1.2); }
  100% { opacity: 0.5; transform: scale(0.8); }
}

/* Reader panel */
.reader-panel {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 20px;
  padding: 2rem;
  text-align: center;
  margin-bottom: 2rem;
  border: 2px dashed #dee2e6;
  transition: all 0.2s;
}

.reader-icon {
  margin-bottom: 1rem;
  color: #f0a24a;
}

.hint {
  font-size: 1.2rem;
  font-weight: 500;
  color: #8a1212;
  margin-bottom: 0.5rem;
}

.error-message {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #ffebee;
  border-radius: 12px;
  color: #c62828;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.error-icon {
  font-size: 1.2rem;
}

/* History section */
.history-section {
  margin-top: 1rem;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.history-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #8a1212;
}

.history-count {
  font-size: 0.75rem;
  color: #888;
  background: #f8f9fa;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #666;
  background: #f9f9f9;
  border-radius: 12px;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.6;
}

.empty-hint {
  font-size: 0.8rem;
  color: #888;
  margin-top: 0.5rem;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-height: 500px;
  overflow-y: auto;
}

.entry {
  background: white;
  border: 1px solid #eee;
  border-radius: 16px;
  padding: 1.25rem;
  transition: all 0.2s;
  animation: slideIn 0.3s ease-out;
}

.entry-new {
  animation: slideIn 0.3s ease-out;
  border-left: 4px solid #f0a24a;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.entry-success {
  background: linear-gradient(135deg, #fff 0%, #f0fdf4 100%);
  border-color: #c8e6c9;
}

.entry-error {
  background: linear-gradient(135deg, #fff 0%, #fef5f5 100%);
  border-color: #ffcdd2;
}

.entry:hover {
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.entry-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.user-info {
  flex: 1;
}

.user-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 0.25rem;
}

.user-badge {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge-role {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  background: #f0a24a;
  color: #7b2c2c;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.badge-diabetic {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  background: #e3f2fd;
  color: #1565c0;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 500;
}

.entry-time {
  font-family: monospace;
  font-size: 0.75rem;
  color: #888;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  white-space: nowrap;
}

.entry-details {
  margin-bottom: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f0f0f0;
}

.detail-row {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #888;
  text-transform: uppercase;
  min-width: 70px;
}

.detail-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: #333;
}

.option-a {
  color: #1fa317;
  background: #e8f5e9;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
}

.option-b {
  color: #1976d2;
  background: #e3f2fd;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
}

.option-soup {
  color: #ed6c02;
  background: #fff3e0;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
}

.option-other {
  color: #9c27b0;
  background: #f3e5f5;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
}

.status-text {
  font-weight: 700;
  font-size: 0.9rem;
}

.status-success {
  color: #2e7d32;
}

.status-error {
  color: #c62828;
}

/* Meal info */
.meal-info {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 0.75rem;
  margin-top: 0.5rem;
}

.meal-row {
  display: flex;
  gap: 0.75rem;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.meal-row:last-child {
  margin-bottom: 0;
}

.meal-label {
  font-weight: 500;
  color: #666;
  min-width: 60px;
}

.meal-value {
  color: #333;
  font-weight: 500;
}

.error-reason {
  margin-top: 0.75rem;
  padding: 0.6rem;
  background: #ffebee;
  border-radius: 10px;
  color: #c62828;
  font-size: 0.8rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.error-icon-small {
  font-size: 1rem;
}

/* Scrollbar */
.history-list::-webkit-scrollbar {
  width: 6px;
}

.history-list::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.history-list::-webkit-scrollbar-thumb {
  background: #f0a24a;
  border-radius: 3px;
}

.history-list::-webkit-scrollbar-thumb:hover {
  background: #e5942c;
}

/* Responsive */
@media (max-width: 768px) {
  .lunchtime {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .title {
    font-size: 1.25rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .reader-panel {
    padding: 1.5rem;
  }

  .hint {
    font-size: 1rem;
  }

  .entry-header {
    flex-direction: column;
  }

  .entry-time {
    align-self: flex-start;
  }

  .user-name {
    font-size: 1rem;
  }

  .detail-row {
    flex-direction: column;
    gap: 0.25rem;
  }

  .detail-label {
    min-width: auto;
  }

  .meal-row {
    flex-direction: column;
    gap: 0.25rem;
  }

  .meal-label {
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .user-badge {
    flex-direction: column;
    gap: 0.25rem;
  }

  .badge-role,
  .badge-diabetic {
    display: inline-block;
    width: fit-content;
  }

  .entry-details {
    font-size: 0.8rem;
  }
}
</style>