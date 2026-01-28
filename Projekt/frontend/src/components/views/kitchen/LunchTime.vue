<template>
  <div class="lunchtime">
    <h1>Ebéd kiadás</h1>

    <div class="panel">
      <div class="hint">Érintsd a kártyát az olvasóhoz…</div>

      <div v-if="error" class="error">{{ error }}</div>

      <div v-if="history.length === 0" class="empty">
        Még nem volt leolvasás.
      </div>

      <div v-for="(item) in history" :key="item._key" class="entry" :class="item.canEat ? 'ok' : 'no'">
        <div class="top">
          <div class="name">{{ item.fullName || '—' }}</div>
          <div class="time">{{ formatTime(item._scannedAt) }}</div>
        </div>

        <div class="meta">
          <span><strong>Szerepkör:</strong> {{ item.userType || '—' }}</span>
          <span><strong>Menü:</strong> {{ item.selectedOption || '—' }}</span>
          <span><strong>Cukorbeteg:</strong> {{ item.hasDiabetes ? 'Igen' : 'Nem' }}</span>
        </div>

        <div class="msg">
          {{ item.message }}
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
      history: [], // új felülre
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

        // ugyanazt ne dolgozzuk fel
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

      // új beolvasás felülre
      this.history.unshift(entry)

      // limit (pl. 20)
      if (this.history.length > 20) {
        this.history.length = 20
      }
    },

    formatTime(d) {
      try {
        const date = (d instanceof Date) ? d : new Date(d)
        return date.toLocaleTimeString('hu-HU', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
      } catch {
        return ''
      }
    }
  }
}
</script>

<style scoped>
.lunchtime{padding:2rem;max-width:980px;margin:0 auto}
.panel{background:#fff;border-radius:12px;padding:1.5rem;box-shadow:0 2px 10px rgba(0,0,0,.08)}
.hint{margin-bottom:1rem;color:#374151}
.error{margin: .5rem 0 1rem; color:#b91c1c; font-weight:700}
.empty{color:#6b7280}

.entry{border-radius:12px;padding:1rem;margin:.75rem 0;border:1px solid #e5e7eb}
.entry.ok{background:#dcfce7}
.entry.no{background:#fee2e2}
.top{display:flex;justify-content:space-between;align-items:center;gap:1rem}
.name{font-size:1.1rem;font-weight:900}
.time{font-weight:800;color:#374151}
.meta{display:flex;gap:1rem;flex-wrap:wrap;margin-top:.5rem;color:#111827}
.msg{margin-top:.75rem;font-weight:900}
</style>
