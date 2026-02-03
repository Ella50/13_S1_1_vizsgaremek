<template>
  <nav>
    <template v-if="AuthService.isAuthenticated()">
      <router-link to="/dashboard">Főoldal</router-link>
      
      <!-- Admin -->
      <router-link v-if="AuthService.isAdmin()" to="/admin/users">
        Felhasználók
      </router-link>
      <router-link v-if="AuthService.canViewMenu()" to="/menu/today">
        Menü
      </router-link>
      <router-link v-if="AuthService.isAdmin()" to="/kitchen/ingredients">
        Összetevők
      </router-link>
      <router-link v-if="AuthService.isAdmin()" to="/kitchen/menu-maker">
        Menük
      </router-link>

      <router-link v-if="AuthService.isAdmin()" to="/kitchen/orders">
        Rendelések
      </router-link>
      

      <!-- Konyha -->

      <router-link v-if="AuthService.isKitchen()" to="/kitchen/ingredients">
        Összetevők
      </router-link>
      <router-link v-if="AuthService.isKitchen()" to="/kitchen/menu-maker">
        Menük
      </router-link>

      <router-link v-if="AuthService.isKitchen()" to="/kitchen/orders">
        Rendelések
      </router-link>

      <router-link v-if="AuthService.isKitchen()" to="/kitchen/lunchtime">
        Ebédeltetés
      </router-link>
      

      <!--Minden bejelentkezett-->
      <router-link v-if="AuthService.isAuthenticated()" to="/kitchen/meals">
        Ételek
      </router-link>

      <router-link v-if="AuthService.isAuthenticated()" to="/menu/week">
        Heti menü
      </router-link>

      <router-link v-if="AuthService.isAuthenticated()" to="/profile">
        Profil
      </router-link>
      
      <button 
        @click="logout" 
        id="logout"
        :disabled="loggingOut"
      >
        <span v-if="loggingOut">Kijelentkezés...</span>
        <span v-else>Kijelentkezés</span>
      </button>

    </template>
    
    <template v-else>
      <router-link to="/login">Bejelentkezés</router-link>
      <router-link to="/register">Regisztráció</router-link>
    </template>
  </nav>
</template>

<script>
import AuthService from '../services/authService'

export default {
  data() {
    return {
      AuthService,
      loggingOut: false
    }
  },
  computed: {
    isAuthenticated() {
      return AuthService.isAuthenticated()
    },
    isAdmin() {
      return AuthService.isAdmin()
    },
    isKitchen() {
      return AuthService.isKitchen()
    },
    canViewMenu() {
      return AuthService.canViewMenu()
    }
  },
  methods: {
    async logout() {
      // Már fut a kijelentkezés
      if (this.loggingOut) return
      
      this.loggingOut = true
      
      try {
        // 1. Először hívjuk meg a logout API-t
        await AuthService.logout()
        
        // 2. Kis várakozás a render frissítéséhez
        await new Promise(resolve => setTimeout(resolve, 100))
        
        // 3. Csak ezután navigáljunk
        this.$router.push('/login')
        
      } catch (error) {
        console.error('Logout error:', error)
        // Hiba esetén is navigáljunk
        this.$router.push('/login')
      } finally {
        this.loggingOut = false
      }
    }
  }
}
</script>

<style scoped>
nav {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #ffd294;
}

nav a {
  color: #8a1212;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: bold;
}

nav a:hover {
  background: rgba(255, 255, 255, 0.1);
}

nav a.router-link-active {
  background: rgba(255, 255, 255, 0.2);
}

button {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.guest-links {
  display: flex;
  gap: 1rem;
}

#logout{
  width: 10%;
  min-width: 130px;
  margin-left: auto;
  float: right;
}

</style>