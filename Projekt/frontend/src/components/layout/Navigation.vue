<template>
  <nav>
    <!-- Számítógépes nézet -->
    <div class="desktop-nav">
      <template v-if="AuthService.isAuthenticated()">
        <router-link to="/dashboard" class="logo">
          <img src="/public/eMenza_kisebb.png" alt="" class="logo-image">
        </router-link>
        
        <div class="nav-links">
          <!-- Admin -->
          <template v-if="AuthService.isAdmin()">
            <div class="dropdown">
              <button class="dropdown-btn">Admin ▾</button>
              <div class="dropdown-content">
                <router-link to="/admin/users">Felhasználók</router-link>
                <router-link to="/admin/invoices">Számlák</router-link>
                <router-link to="/admin/documents">Dokumentumok</router-link>
                <router-link to="/admin/prices">Árak</router-link>

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
          <router-link v-if="!AuthService.isKitchen() && !AuthService.isAdmin()" to="/profile">Profil</router-link>
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
        <span class="label">Belépés</span>
      </router-link>
      <router-link to="/register" class="mobile-nav-item">
        <span class="label">Regisztráció</span>
      </router-link>
    </div>


    <transition name="slide-up">
      <div v-if="showActionSheet" class="action-sheet-overlay" @click="closeActionSheet">
        <div class="action-sheet" @click.stop>
          <div class="action-sheet-header">
            <h3>Menü</h3>
            <button class="close-sheet" @click="closeActionSheet">✕</button>
          </div>
          
          <div class="action-sheet-content">
            <template v-if="AuthService.isAdmin()">
              <div class="action-section">
                <div class="section-title">Admin</div>
                <button @click="navigateTo('/admin/users')">Felhasználók</button>
                <button @click="navigateTo('/admin/invoices')">Számlák</button>
                <button @click="navigateTo('/admin/documents')">Dokumentumok</button>
                <button @click="navigateTo('/admin/prices')">Árak</button>

              </div>
            </template>

            <template v-if="AuthService.isKitchen()">
              <div class="action-section">
                <div class="section-title">Konyha</div>
                <button @click="navigateTo('/kitchen/ingredients')">Hozzávalók</button>
                <button @click="navigateTo('/kitchen/menu-maker')">Menük</button>
                <button @click="navigateTo('/kitchen/orders')">Rendelések</button>
                <button @click="navigateTo('/kitchen/lunchtime')">Ebédeltetés</button>
              </div>
            </template>

            <div class="action-section">
              <div class="section-title">Általános</div>
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
import AuthService from '../../services/authService'

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

}

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
  transition: transform 0.2s ease;
}

.logo:hover {
  transform: scale(1.02);
}

.logo-image {
  width: 180px;
  transition: filter 0.2s ease;
}

.logo:hover .logo-image {
  filter: brightness(0.95);
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
  transition: all 0.2s ease;
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  position: relative;
}

.nav-links a::after, .dropdown-btn::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  right: 0;
  width: auto;
  height: 2px;
  background: #8a1212;
  transform: scaleX(0);
  transform-origin: center;
  transition: transform 0.2s ease;
}

.nav-links a:hover::after, .dropdown-btn:hover::after {
  transform: scaleX(0.7);
}


.nav-links a.router-link-active {
  font-weight: 600;
}

.nav-links a.router-link-active::after {
  transform: scaleX(0.7);
}

.register-btn {
  background: #8a1212 !important;
  color: white !important;
  border-radius: 30px !important;
  padding: 0.5rem 1.2rem !important;
}

.register-btn::after {
  display: none;
}

.register-btn:hover {
  background: #a51616 !important;
  transform: translateY(-2px);

}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-content {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  background: rgb(255, 255, 255);
  min-width: 200px;
  border-radius: 12px;
  border: #333 1px dotted;
  z-index: 1001;
  padding: 0.5rem 0;
  animation: fadeInDown 0.2s ease;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown:hover .dropdown-content {
  display: block;
}

.dropdown-content a {
  display: block;
  padding: 0.8rem 1rem;
  color: #333;
  border-radius: 0;
  transition: all 0.2s ease;
}

.dropdown-content a::after {
  display: none;
}

.dropdown-content a:hover {
  background: #ffd294;
  color: #8a1212;
  padding-left: 1.5rem;
}

.logout-btn {
  transition: all 0.2s ease;
  background: #8a1212;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 30px;
  cursor: pointer;
  width: auto;
  min-width: 130px;
  font-weight: bold;
  font-size: 0.9rem;
}

.logout-btn:hover:not(:disabled) {
  background: #a51616;
}

.logout-btn:active {
  transform: translateY(0);
  background: #555151;
}


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
  
  body {
    padding-bottom: 70px;
  }
}

@media (max-width: 480px) {
  .mobile-nav-item .label {
    font-size: 0.65rem;
  }
  
  .mobile-nav-item .icon {
    font-size: 1.2rem;
  }
}
</style>