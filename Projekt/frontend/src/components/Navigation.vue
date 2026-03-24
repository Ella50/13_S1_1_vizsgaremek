<template>
  <nav>
    <!-- Desktop Navigation -->
    <div class="desktop-nav">
      <template v-if="AuthService.isAuthenticated()">
        <router-link to="/dashboard" class="logo">
          <img src="/public/eMenza_kisebb.png" alt="" class="logo-image">
        </router-link>
        
        <div class="nav-links">
          <!--<router-link to="/dashboard">Főoldal</router-link>-->
          
          <!-- Admin -->
          <template v-if="AuthService.isAdmin()">
            <div class="dropdown">
              <button class="dropdown-btn">Admin ▾</button>
              <div class="dropdown-content">
                <router-link to="/admin/users">Felhasználók</router-link>
                <router-link to="/admin/invoices">Számlák</router-link>
                <router-link to="/admin/documents">Dokumentumok</router-link>
              </div>
            </div>
          </template>

          <!-- Konyha -->
          <template v-if="AuthService.isKitchen()">
            <div class="dropdown">
              <button class="dropdown-btn">Konyha ▾</button>
              <div class="dropdown-content">
                <router-link to="/kitchen/ingredients">Hozzávalók</router-link>
                <router-link to="/kitchen/menu-maker">Menük</router-link>
                <router-link to="/kitchen/orders">Rendelések</router-link>
                <router-link to="/kitchen/lunchtime">Ebédeltetés</router-link>
              </div>
            </div>
          </template>

          <!-- Közös linkek -->

          <router-link v-if="AuthService.canViewMenu()" to="/menu/today">Mai menü</router-link>
          <router-link to="/menu/week">Heti menü</router-link>
          <router-link to="/kitchen/meals">Ételek</router-link>
          <router-link to="/profile">Profil</router-link>
          <router-link v-if="!AuthService.isKitchen() && !AuthService.isAdmin()" to="/personal-orders">Rendelések</router-link>
          <router-link v-if="!AuthService.isAdmin() && !AuthService.isKitchen()" to="/personal-invoices">Számlák</router-link>
        </div>
        
        <button 
          @click="logout" 
          class="logout-btn"
          :disabled="loggingOut"
        >
          <span v-if="loggingOut">Kijelentkezés...</span>
          <span v-else>Kijelentkezés</span>
        </button>
      </template>
      
      <template v-else>
        <div class="nav-links">
          <router-link to="/login">Bejelentkezés</router-link>
          <router-link to="/register" class="register-btn">Regisztráció</router-link>
        </div>
      </template>
    </div>

    <!-- Telefonos navigáció -->
    <div class="mobile-bottom-nav" v-if="AuthService.isAuthenticated()">
      <!-- <router-link to="/dashboard" class="mobile-nav-item" :class="{ active: $route.path === '/dashboard' }">
        <span class="icon">🏠</span>
        <span class="label">Főoldal</span>
      </router-link>-->

      <router-link to="/menu/today" class="mobile-nav-item" :class="{ active: $route.path.includes('/menu') }">
        <span class="icon">𓌉◯𓇋</span>
        <span class="label">Mai menü</span>
      </router-link>


      <router-link to="/kitchen/meals" class="mobile-nav-item" :class="{ active: $route.path === '/kitchen/meals' }">
        <span class="icon">☷</span>
        <span class="label">Ételek</span>
      </router-link>

      <router-link to="/profile" class="mobile-nav-item" :class="{ active: $route.path === '/profile' }">
        <span class="icon">⚙︎</span>
        <span class="label">Profil</span>
      </router-link>

      <div class="mobile-nav-item" @click="openActionSheet">
        <span class="icon">☰</span>
        <span class="label">Több</span>
      </div>

    </div>

    <div class="mobile-bottom-nav" v-else>
      <router-link to="/login" class="mobile-nav-item">
        <span class="icon">🔑</span>
        <span class="label">Belépés</span>
      </router-link>
      <router-link to="/register" class="mobile-nav-item">
        <span class="icon">📝</span>
        <span class="label">Regisztráció</span>
      </router-link>
    </div>

    <!-- Action Sheet for More Options -->
    <transition name="slide-up">
      <div v-if="showActionSheet" class="action-sheet-overlay" @click="closeActionSheet">
        <div class="action-sheet" @click.stop>
          <div class="action-sheet-header">
            <h3>Menü</h3>
            <button class="close-sheet" @click="closeActionSheet">✕</button>
          </div>
          
          <div class="action-sheet-content">
            <!-- Admin szekció -->
            <template v-if="AuthService.isAdmin()">
              <div class="action-section">
                <div class="section-title">Admin</div>
                <button @click="navigateTo('/admin/users')">Felhasználók</button>
                <button @click="navigateTo('/admin/invoices')">Számlák</button>
                <button @click="navigateTo('/admin/documents')">Dokumentumok</button>
              </div>
            </template>

            <!-- Konyha szekció -->
            <template v-if="AuthService.isKitchen()">
              <div class="action-section">
                <div class="section-title">Konyha</div>
                <button @click="navigateTo('/kitchen/ingredients')">Hozzávalók</button>
                <button @click="navigateTo('/kitchen/menu-maker')">Menük</button>
                <button @click="navigateTo('/kitchen/orders')">Rendelések</button>
                <button @click="navigateTo('/kitchen/lunchtime')">Ebédeltetés</button>
              </div>
            </template>


            <!-- Általános szekció -->
      
              <div class="action-section">
                <div class="section-title">Általános</div>
                <!--<button v-if="AuthService.canViewMenu()" @click="navigateTo('/menu/today')">📅 Mai menü</button>-->
                <button @click="navigateTo('/menu/week')">Heti menü</button>
                <button @click="navigateTo('/kitchen/meals')">Ételek</button>
                <button 
                  v-if="!AuthService.isAdmin() && !AuthService.isKitchen()" 
                  @click="navigateTo('/personal-orders')"
                >
                  Rendelések
                </button>
                <button 
                  v-if="!AuthService.isAdmin() && !AuthService.isKitchen()" 
                  @click="navigateTo('/personal-invoices')"
                >
                Számlák
                </button>

              </div>
  


            

            <!-- Kijelentkezés -->
            <div class="action-section">
              <button @click="logout" class="logout-action" :disabled="loggingOut">
                <span v-if="loggingOut">Kijelentkezés...</span>
                <span v-else>Kijelentkezés</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </nav>
</template>

<script>
import AuthService from '../services/authService'

export default {
  data() {
    return {
      AuthService,
      loggingOut: false,
      showActionSheet: false
    }
  },
  methods: {
    openActionSheet() {
      this.showActionSheet = true
      document.body.style.overflow = 'hidden'
    },
    closeActionSheet() {
      this.showActionSheet = false
      document.body.style.overflow = ''
    },
    navigateTo(path) {
      this.$router.push(path)
      this.closeActionSheet()
    },
    async logout() {
      if (this.loggingOut) return
      
      this.loggingOut = true
      this.closeActionSheet()
      
      try {
        await AuthService.logout()
        await new Promise(resolve => setTimeout(resolve, 100))
        this.$router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
        this.$router.push('/login')
      } finally {
        this.loggingOut = false
      }
    }
  },
  watch: {
    $route() {
      this.closeActionSheet()
    }
  }
}
</script>

<style scoped>
nav {
  background: #ffd294;
  position: relative;
  width: 100%;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

/* Desktop Navigation */
.desktop-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.8rem 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.logo {
  font-size: 1.3rem;
  font-weight: bold;
  color: #8a1212;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.logo-image{
  width: 180px;
}

.nav-links {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.nav-links a, .dropdown-btn {
  color: #8a1212;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}

.nav-links a:hover, .dropdown-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.nav-links a.router-link-active {
  background: rgba(255, 255, 255, 0.4);
  font-weight: 600;
}

.register-btn {
  background: #8a1212 !important;
  color: white !important;
}

.register-btn:hover {
  background: #a51616 !important;
}

/* Dropdown */
.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-content {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  background: white;
  min-width: 200px;
  border-radius: 8px;
  box-shadow: 0 8px 16px rgba(0,0,0,0.1);
  z-index: 1001;
  padding: 0.5rem 0;
}

.dropdown:hover .dropdown-content {
  display: block;
}

.dropdown-content a {
  display: block;
  padding: 0.8rem 1rem;
  color: #333;
  border-radius: 0;
}

.dropdown-content a:hover {
  background: #ffd294;
  color: #8a1212;
}

.logout-btn {
  /*background: #8a1212;
  color: rgb(207, 181, 181);
  width: auto;
  height: auto;

  cursor: pointer;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;*/
  transition: all 0.2s;
    background: var(--piros);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  width: 10%;
  min-width: 130px;
  font-weight: bold;
}

.logout-btn:hover:not(:disabled) {
  background: #8a1212;
  color: rgb(255, 255, 255);
}

/* Mobile Bottom Navigation */
.mobile-bottom-nav {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
  padding: 0.5rem 0;
  justify-content: space-around;
  align-items: center;
  z-index: 999;
}

.mobile-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: #888;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
  flex: 1;
  cursor: pointer;
}

.mobile-nav-item .icon {
  font-size: 1.3rem;
  margin-bottom: 0.2rem;
}

.mobile-nav-item .label {
  font-size: 0.7rem;
  font-weight: 500;
}

.mobile-nav-item.active {
  color: #8a1212;
}

.mobile-nav-item.active .icon {
  transform: scale(1.1);
}

.menu-trigger {
  background: #ffd294;
  color: #8a1212 !important;
  border-radius: 30px;
  transform: translateY(-10px);
  box-shadow: 0 4px 10px rgba(138, 18, 18, 0.2);
}

/* Action Sheet */
.action-sheet-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: flex-end;
}

.action-sheet {
  background: white;
  width: 100%;
  border-top-left-radius: 20px;
  border-top-right-radius: 20px;
  padding: 1rem;
  max-height: 80vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease-out;
}

.action-sheet-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0 1rem;
  border-bottom: 1px solid #eee;
  margin-bottom: 1rem;
}

.action-sheet-header h3 {
  margin: 0;
  color: #333;
  font-size: 1.2rem;
}

.close-sheet {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #888;
  cursor: pointer;
  padding: 0.5rem;
  text-align: right;

}

.close-sheet:hover {
  color: #ca1616;
  background: transparent !important;
  border: none;

}

.action-section {
  margin-bottom: 1.5rem;
}

.section-title {
  color: #8a1212;
  font-weight: 600;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.5rem;
  padding-left: 0.5rem;
}

.action-sheet button {
  width: 100%;
  padding: 1rem;
  text-align: left;
  background: transparent;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  color: #333;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.action-sheet button:hover {
  background: #f5f5f5;
}

.logout-action {
  color: #dc3545 !important;
  font-weight: 600;
}

.logout-action:hover {
  background: #ffebee !important;
}

/* Animations */
@keyframes slideUp {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.slide-up-enter-active {
  animation: slideUp 0.3s ease-out;
}

.slide-up-leave-active {
  animation: slideUp 0.3s reverse;
}

/* Responsive Breakpoints */
@media (max-width: 768px) {
  .desktop-nav {
    padding: 0.8rem 1rem;
  }
  
  .nav-links, .logout-btn {
    display: none;
  }
  
  .mobile-bottom-nav {
    display: flex;
  }
  
  /* Add padding to body for fixed bottom nav */
  body {
    padding-bottom: 70px;
  }
}

/* Small screens */
@media (max-width: 480px) {
  .mobile-nav-item .label {
    font-size: 0.65rem;
  }
  
  .mobile-nav-item .icon {
    font-size: 1.2rem;
  }
}
</style>