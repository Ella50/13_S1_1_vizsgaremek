<!-- Navigation.vue - egyszerűbb verzió -->
<template>
  <nav>
    <template v-if="AuthService.isAuthenticated()">
      <router-link to="/dashboard">Főoldal</router-link>
      
      <!-- Csak admin -->
      <router-link v-if="AuthService.isAdmin()" to="/admin/users">
        Felhasználók
      </router-link>
      
      <!-- Menü -->
      <router-link v-if="AuthService.canViewMenu()" to="/menu/today">
        Mai Menü
      </router-link>
      
      <!-- Konyha -->
      <router-link v-if="AuthService.isKitchen()" to="/kitchen/meals">
        Ételek
      </router-link>
      
      <button @click="logout">Kijelentkezés</button>
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
  data(){
    return{
      AuthService
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
    logout() {
      AuthService.logout()
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
nav {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: #2c3e50;
}

nav a {
  color: white;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
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
</style>