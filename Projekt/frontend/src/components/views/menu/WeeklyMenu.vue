<template>
  <div class="weekly-menu">
    <div class="content-card">
      <div class="header">
        <h1>Heti menü</h1>

        <div class="week-navigation">
          <button @click="prevWeek" class="nav-btn">Előző hét</button>
          <span class="week-range">{{ weekDisplay }}</span>
          <button @click="nextWeek" class="nav-btn">Következő hét</button>
        </div>
      </div>

      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Heti menü betöltése...</p>
      </div>

      <div v-else-if="error" class="error">
        <p>{{ error }}</p>
        <button @click="fetchWeeklyMenu" class="btn-retry">Újra próbál</button>
      </div>

      <div v-else class="weekly-schedule">
        <div v-for="d in visibleDays" :key="d.day" class="day-card">
          <div class="day-pill">
            <div class="day-name">{{ huDayName(d.day) }}</div>
            <div class="day-date">{{ shortDate(d.day) }}</div>
          </div>

          <div class="card">
            <div v-if="d.menu" class="menu-list">
              <div class="row">
                <div class="label">Leves:</div>
                <div class="txt">{{ d.menu.soup?.mealName || '—' }}</div>
              </div>

              <div class="divider"></div>

              <div class="row">
                <div class="label">A opció:</div>
                <div class="txt">{{ d.menu.optionA?.mealName || '—' }}</div>
              </div>

              <div class="divider"></div>

              <div class="row">
                <div class="label">B opció:</div>
                <div class="txt">{{ d.menu.optionB?.mealName || '—' }}</div>
              </div>

              <div v-if="d.menu.other" class="divider"></div>

              <div v-if="d.menu.other" class="row">
                <div class="label">Egyéb:</div>
                <div class="txt">{{ d.menu.other?.mealName || '—' }}</div>
              </div>
            </div>

            <div v-else class="no-menu">
              Nincs feltöltve étlap
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
  data() {
    return {
      loading: false,
      error: '',
      currentWeekStart: null,
      weekDays: []
    }
  },

  computed: {
    weekDisplay() {
      if (!this.currentWeekStart) return ''

      const start = this.parseYMDLocal(this.currentWeekStart)
      const end = new Date(start)
      end.setDate(end.getDate() + 6)

      const fmt = (d) => {
        const s = d.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' })
        return s.endsWith('.') ? s : (s + '.')
      }

      return `${fmt(start)} - ${fmt(end)}`
    },

    visibleDays() {
      return (this.weekDays || []).filter((d) => {
        const iso = this.isoDayOfWeek(d.day)
        if (iso >= 1 && iso <= 5) return true
        return !!d.menu
      })
    }
  },

  mounted() {
    this.setCurrentWeekStart()
    this.fetchWeeklyMenu()
  },

  methods: {
    toYMDLocal(dateObj) {
      const y = dateObj.getFullYear()
      const m = String(dateObj.getMonth() + 1).padStart(2, '0')
      const d = String(dateObj.getDate()).padStart(2, '0')
      return `${y}-${m}-${d}`
    },

    parseYMDLocal(ymd) {
      const [y, m, d] = String(ymd).slice(0, 10).split('-').map(Number)
      return new Date(y, m - 1, d)
    },

    normalizeDayKey(day) {
      if (!day) return day
      return String(day).slice(0, 10)
    },

    setCurrentWeekStart() {
      const today = new Date()
      const dow = today.getDay()
      const diff = dow === 0 ? -6 : 1 - dow
      const monday = new Date(today)
      monday.setDate(today.getDate() + diff)
      monday.setHours(0, 0, 0, 0)
      this.currentWeekStart = this.toYMDLocal(monday)
    },

    buildEmptyWeek(startDay) {
      const out = []
      const start = this.parseYMDLocal(startDay)
      for (let i = 0; i < 7; i++) {
        const d = new Date(start)
        d.setDate(start.getDate() + i)
        out.push({ day: this.toYMDLocal(d), menu: null })
      }
      return out
    },

    normalizeWeeklyResponse(data) {
      if (Array.isArray(data)) {
        return data.map((x) => ({
          day: this.normalizeDayKey(x.day),
          menu: x.menu ?? null
        }))
      }

      if (data && data.weekly_menu) {
        const wm = data.weekly_menu
        if (Array.isArray(wm)) {
          return wm.map((x) => ({
            day: this.normalizeDayKey(x.day),
            menu: x.menu ?? null
          }))
        }
        if (typeof wm === 'object') {
          return Object.keys(wm).map((day) => ({
            day: this.normalizeDayKey(day),
            menu: wm[day]?.menu ?? wm[day] ?? null
          }))
        }
      }

      if (data && typeof data === 'object') {
        return Object.keys(data).map((day) => ({
          day: this.normalizeDayKey(day),
          menu: data[day]?.menu ?? data[day] ?? null
        }))
      }

      return []
    },

    async fetchWeeklyMenu() {
      this.loading = true
      this.error = ''
      this.weekDays = this.buildEmptyWeek(this.currentWeekStart)

      try {
        const res = await AuthService.api.get('/menu/week', {
          params: { from: this.currentWeekStart }
        })

        const normalized = this.normalizeWeeklyResponse(res.data?.data ?? res.data)

        const map = new Map(normalized.map((x) => [x.day, x.menu ?? null]))
        this.weekDays = this.weekDays.map((d) => ({
          day: d.day,
          menu: map.has(d.day) ? map.get(d.day) : null
        }))
      } catch (e) {
        console.error('Heti menü betöltése sikertelen:', e)
        this.error = e?.response?.data?.message || 'Hiba történt a heti menü betöltésekor'
      } finally {
        this.loading = false
      }
    },

    prevWeek() {
      const d = this.parseYMDLocal(this.currentWeekStart)
      d.setDate(d.getDate() - 7)
      this.currentWeekStart = this.toYMDLocal(d)
      this.fetchWeeklyMenu()
    },

    nextWeek() {
      const d = this.parseYMDLocal(this.currentWeekStart)
      d.setDate(d.getDate() + 7)
      this.currentWeekStart = this.toYMDLocal(d)
      this.fetchWeeklyMenu()
    },

    isoDayOfWeek(dateString) {
      const d = this.parseYMDLocal(dateString)
      const day = d.getDay()
      return day === 0 ? 7 : day
    },

    huDayName(dateString) {
      const d = this.parseYMDLocal(dateString)
      const s = d.toLocaleDateString('hu-HU', { weekday: 'long' })
      return s.charAt(0).toUpperCase() + s.slice(1)
    },

    shortDate(dateString) {
      const d = this.parseYMDLocal(dateString)
      return d.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
button {
  width: 20%;
}

.weekly-menu {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
}

/* content-card a többi admin komponenshez hasonlóan */
.content-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 1.5rem;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0 0 1rem 0;
  color: #7b2c2c;
}

.week-navigation {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
}

.week-range {
  font-size: 1.125rem;
  color: #7b2c2c;
  min-width: 220px;
}

.nav-btn {
  padding: 0.6rem 1rem;
  background: #f0a24a;
  color: #7b2c2c;
  border: none;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.nav-btn:hover {
  background: #e5942c;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(229, 148, 44, 0.3);
}

.nav-btn:active {
  transform: translateY(0);
}

.weekly-schedule {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(5, 1fr);
}

.day-card {
  width: 100%;
  transition: transform 0.2s ease;
}

.day-card:hover {
  transform: translateY(-4px);
}

@media (max-width: 1200px) {
  .weekly-schedule {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .weekly-schedule {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 600px) {
  .weekly-schedule {
    grid-template-columns: 1fr;
  }
}

/* felül a "nap" */
.day-pill {
  background: #f7c77a;
  color: #7b2c2c;
  border-radius: 16px;
  text-align: center;
  padding: 0.6rem 0.8rem;
  margin: 0 auto 0.7rem;
  transition: all 0.2s ease;
}

.day-pill:hover {
  background: #f0a24a;
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(240, 162, 74, 0.3);
}

.day-name {
  font-size: 1.2rem;
  line-height: 1.1;
}

.day-date {
  font-size: 1rem;
  font-weight: 700;
  opacity: 0.9;
}

/* belső "füzet" card */
.card {
  background: #fff;
  border-radius: 16px;
  padding: 1rem;
  border: 2px dashed #d07a7a;
  box-shadow: 0 8px 0 rgba(255, 170, 0, 0.25);
  min-height: 280px;
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 20px rgba(255, 170, 0, 0.3);
  border-color: #f0a24a;
}

.menu-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.row {
  display: block;
  padding-bottom: 0.7rem;
  border-bottom: 1px dashed rgba(208, 122, 122, 0.35);
  transition: all 0.2s ease;
}

.row:hover {
  border-bottom-color: #f0a24a;
  padding-left: 0.5rem;
}

.row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.label {
  font-weight: 500;
  color: #a26b6b;
  transition: color 0.2s ease;
}

.row:hover .label {
  color: #f0a24a;
}

.txt {
  color: #7b2c2c;
  font-weight: 700;
  transition: color 0.2s ease;
}

.row:hover .txt {
  color: #9d3a3a;
}

.divider {
  height: 1px;
  background: rgba(208, 122, 122, 0.2);
  margin: 0.25rem 0;
  transition: background 0.2s ease;
}

.row:hover + .divider,
.divider:hover {
  background: rgba(240, 162, 74, 0.4);
}

.no-menu {
  height: 100%;
  display: grid;
  place-items: center;
  text-align: center;
  color: #a26b6b;
  padding: 1rem;
  transition: all 0.2s ease;
}

.card:hover .no-menu {
  color: #7b2c2c;
  background: rgba(247, 199, 122, 0.1);
  border-radius: 12px;
}

.loading {
  text-align: center;
  padding: 3rem;
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
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.error {
  text-align: center;
  padding: 3rem;
  color: #e74c3c;
}

.btn-retry {
  margin-top: 1rem;
  padding: 0.6rem 1rem;
  background: #f0a24a;
  color: #7b2c2c;
  border: none;
  border-radius: 999px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-retry:hover {
  background: #e5942c;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(229, 148, 44, 0.3);
}

.btn-retry:active {
  transform: translateY(0);
}

/* mobilon is scroll marad, csak kisebb kártyák */
@media (max-width: 768px) {
  .content-card {
    padding: 1rem;
  }
  
  .header h1 {
    font-size: 1.8rem;
  }
  
  .week-navigation {
    flex-wrap: wrap;
  }
  
  .week-range {
    order: -1;
    width: 100%;
    text-align: center;
  }
  
  .nav-btn {
    flex: 1;
  }
  
  .day-card {
    flex: 0 0 240px;
  }
}

@media (max-width: 480px) {
  .weekly-menu {
    padding: 1rem;
  }
  
  .header h1 {
    font-size: 1.5rem;
  }
  
  .week-range {
    font-size: 0.9rem;
    min-width: auto;
  }
  
  .nav-btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
  
  .day-name {
    font-size: 1rem;
  }
  
  .day-date {
    font-size: 0.8rem;
  }
  
  .card {
    padding: 0.75rem;
    min-height: 240px;
  }
  
  .label, .txt {
    font-size: 0.85rem;
  }
}
</style>