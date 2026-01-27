<template>
  <div class="weekly-menu">
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
              <div class="icon">🥣</div>
              <div class="txt">{{ d.menu.soup?.mealName || '—' }}</div>
            </div>

            <div class="row">
              <div class="icon">🍽️</div>
              <div class="txt">{{ d.menu.optionA?.mealName || '—' }}</div>
            </div>

            <div class="row">
              <div class="icon">🍽️</div>
              <div class="txt">{{ d.menu.optionB?.mealName || '—' }}</div>
            </div>

            <div v-if="d.menu.other" class="row">
              <div class="icon">🧁</div>
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
</template>

<script>
import AuthService from '../../../services/authService'

export default {
  data() {
    return {
      loading: false,
      error: '',
      currentWeekStart: null, // YYYY-MM-DD (hétfő)
      weekDays: [] // mindig 7 nap: [{ day, menu }]
    }
  },

  computed: {
    weekDisplay() {
      if (!this.currentWeekStart) return ''

      const start = new Date(this.currentWeekStart + 'T00:00:00')
      const end = new Date(start)
      end.setDate(end.getDate() + 4) // H–P tartományt mutatunk

      const fmt = (d) =>
        d.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric' })

      return `${fmt(start)} - ${fmt(end)}`
    },

    visibleDays() {
      // H–P mindig, Sz–V csak ha van menü
      return (this.weekDays || []).filter((d) => {
        const iso = this.isoDayOfWeek(d.day) // 1..7
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
    setCurrentWeekStart() {
      const today = new Date()
      const dow = today.getDay() // 0 vasárnap, 1 hétfő...
      const diff = dow === 0 ? -6 : 1 - dow
      const monday = new Date(today)
      monday.setDate(today.getDate() + diff)
      monday.setHours(0, 0, 0, 0)
      this.currentWeekStart = monday.toISOString().slice(0, 10)
    },

    buildEmptyWeek(startDay) {
      const out = []
      const start = new Date(startDay + 'T00:00:00')
      for (let i = 0; i < 7; i++) {
        const d = new Date(start)
        d.setDate(start.getDate() + i)
        out.push({ day: d.toISOString().slice(0, 10), menu: null })
      }
      return out
    },

    // támogat többféle backend választ
    normalizeWeeklyResponse(data) {
      // 1) tömb: [{day, menu}]
      if (Array.isArray(data)) return data

      // 2) { weekly_menu: ... }
      if (data && data.weekly_menu) {
        const wm = data.weekly_menu
        if (Array.isArray(wm)) return wm
        if (typeof wm === 'object') {
          return Object.keys(wm).map((day) => ({
            day,
            menu: wm[day]?.menu ?? wm[day] ?? null
          }))
        }
      }

      // 3) dátum-kulcsos objektum
      if (data && typeof data === 'object') {
        return Object.keys(data).map((day) => ({
          day,
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
        // nálad a backend most: /menu/week?from=YYYY-MM-DD
        const res = await AuthService.api.get('/menu/week', {
          params: { from: this.currentWeekStart }
        })

        const normalized = this.normalizeWeeklyResponse(res.data)

        // beillesztjük a fix 7 napba
        const map = new Map(normalized.map((x) => [x.day, x.menu ?? null]))
        this.weekDays = this.weekDays.map((d) => ({
          day: d.day,
          menu: map.has(d.day) ? map.get(d.day) : null
        }))
      } catch (e) {
        console.error('Heti menü betöltése sikertelen:', e)
        this.error =
          e?.response?.data?.message || 'Hiba történt a heti menü betöltésekor'
      } finally {
        this.loading = false
      }
    },

    prevWeek() {
      const d = new Date(this.currentWeekStart + 'T00:00:00')
      d.setDate(d.getDate() - 7)
      this.currentWeekStart = d.toISOString().slice(0, 10)
      this.fetchWeeklyMenu()
    },

    nextWeek() {
      const d = new Date(this.currentWeekStart + 'T00:00:00')
      d.setDate(d.getDate() + 7)
      this.currentWeekStart = d.toISOString().slice(0, 10)
      this.fetchWeeklyMenu()
    },

    isoDayOfWeek(dateString) {
      // ISO day: Mon=1..Sun=7
      const d = new Date(dateString + 'T00:00:00')
      const day = d.getDay() // Sun=0..Sat=6
      return day === 0 ? 7 : day
    },

    huDayName(dateString) {
      const d = new Date(dateString + 'T00:00:00')
      // nagybetűs kezdő
      const s = d.toLocaleDateString('hu-HU', { weekday: 'long' })
      return s.charAt(0).toUpperCase() + s.slice(1)
    },

    shortDate(dateString) {
      const d = new Date(dateString + 'T00:00:00')
      return d.toLocaleDateString('hu-HU', { year: 'numeric', month: '2-digit', day: '2-digit' })
    }
  }
}
</script>

<style scoped>

button{
  width: 20%;
}

.weekly-menu {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  text-align: center;
  margin-bottom: 2rem;
}

.header h1 {
  margin: 0 0 1rem 0;
  color: #7b2c2c;
  font-size: 3rem;
  font-weight: 800;
}

.week-navigation {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
}

.week-range {
  font-size: 1.125rem;
  font-weight: 600;
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
  font-weight: 700;
}
.nav-btn:hover { filter: brightness(0.95); }


.weekly-schedule {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(5, 1fr); /* alap: 5 oszlop */
}

/* kártya: ne legyen fix szélesség */
.day-card {
  width: 100%;
}

/* ====== TÖRDELÉSI LÉPCSŐK ====== */

/* 3-2 (összesen 3 oszlop, így 5 elem: 3 az első sorban, 2 a másodikban) */
@media (max-width: 1200px) {
  .weekly-schedule {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* 2-2-1 (2 oszlop, 5 elem: 2+2+1) */
@media (max-width: 900px) {
  .weekly-schedule {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* 1-1-1-1-1 (mobil: 1 oszlop) */
@media (max-width: 600px) {
  .weekly-schedule {
    grid-template-columns: 1fr;
  }
}



/* felül a "nap" pill */
.day-pill {
  background: #f7c77a;
  color: #7b2c2c;
  border-radius: 16px;
  text-align: center;
  padding: 0.6rem 0.8rem;
  font-weight: 800;
  margin: 0 auto 0.7rem;
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
}

.menu-list {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.row {
  display: grid;
  grid-template-columns: 34px 1fr;
  align-items: center;
  gap: 0.6rem;
  padding-bottom: 0.7rem;
  border-bottom: 1px dashed rgba(208, 122, 122, 0.35);
}
.row:last-child { border-bottom: none; padding-bottom: 0; }

.icon { font-size: 1.25rem; }
.txt {
  color: #7b2c2c;
  font-weight: 700;
}

.no-menu {
  height: 100%;
  display: grid;
  place-items: center;
  text-align: center;
  color: #a26b6b;
  font-weight: 700;
  padding: 1rem;
}

.loading {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #f0a24a;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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
  font-weight: 800;
}
.btn-retry:hover { filter: brightness(0.95); }

/* mobilon is scroll marad, csak kisebb kártyák */
@media (max-width: 768px) {
  .header h1 { font-size: 2.2rem; }
  .day-card { flex: 0 0 240px; }
}
</style>
