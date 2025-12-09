<!-- src/views/Dashboard.vue -->
<template>
  <div class="dashboard">
    <h1>Üdvözöljük, {{ userName }}!</h1>
    <p>Szerepkör: {{ userRole }}</p>
    
    <div class="quick-links">
      <div v-if="isAdmin" class="admin-links">
        <router-link to="/admin/users" class="link-card">
          <h3>👑 Admin Panel</h3>
          <p>Felhasználók kezelése</p>
        </router-link>
      </div>
      
      <div v-if="canViewMenu" class="menu-links">
        <router-link to="/menu/today" class="link-card">
          <h3>📋 Mai menü</h3>
          <p>Mai étlap megtekintése</p>
        </router-link>
        <router-link to="/menu/week" class="link-card">
          <h3>📅 Heti menü</h3>
          <p>Heti étlap megtekintése</p>
        </router-link>
      </div>
      
      <div v-if="isKitchen" class="kitchen-links">
        <router-link to="/kitchen/meals" class="link-card">
          <h3>🍽️ Ételek</h3>
          <p>Ételek kezelése</p>
        </router-link>
        <router-link to="/kitchen/orders" class="link-card">
          <h3>📋 Rendelések</h3>
          <p>Mai rendelések</p>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '../../services/authService'

export default {
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
    },
    user() {
      return AuthService.getUser()
    },
    userRole() {
      return AuthService.getUserRole()
    },
    userName() {
      const user = this.user
      return user ? `${user.firstName} ${user.lastName}` : 'Vendég'
    }
  },
  
  mounted() {
    // Frissítsd a user adatokat ha van token
    /*if (this.isAuthenticated) {
      AuthService.getCurrentUser().catch(() => {
        // Ha hiba van, automatikus logout
      })
    }*/
  }
}
</script>

<style scoped>
.dashboard {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.quick-links {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.link-card {
  display: block;
  padding: 1.5rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  text-decoration: none;
  color: inherit;
  transition: transform 0.2s, box-shadow 0.2s;
}

.link-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-color: #3498db;
}

.link-card h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}

.link-card p {
  margin: 0;
  color: #7f8c8d;
}
</style>