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
      

      <!-- Konyha -->
      <router-link v-if="AuthService.isKitchen()" to="/kitchen/meals">
        Ételek
      </router-link>
      
      <button @click="logout" id="logout">Kijelentkezés</button>
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