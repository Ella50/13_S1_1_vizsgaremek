<template>
  <header class="header">
    <div class="header-container">
      <div class="logo">
        <img src="/public/eMenza.png" alt="Logo" class="logo-image" />
      </div>
      
      <nav class="navigation">
        <button class="mobile-menu-btn" @click="toggleMobileMenu">☰</button>
        <ul :class="['nav-list', { 'nav-list--open': mobileMenuOpen }]">
          <li><router-link to="/" class="nav-link">Főoldal</router-link></li>
          <li><router-link to="/users" class="nav-link">Felhasználók</router-link></li>
          <li><router-link to="/contact" class="nav-link">Kapcsolat</router-link></li>
          <li><button @click="logout" class="nav-link logout-btn">Kijelentkezés</button></li>
        </ul>
      </nav>
    </div>
  </header>
</template>

<script>
export default {
  name: 'Header',
  data() {
    return {
      mobileMenuOpen: false
    }
  },
  methods: {
    toggleMobileMenu() {
      this.mobileMenuOpen = !this.mobileMenuOpen
    },
    logout() {
      this.$emit('logout')
    }
  }
}
</script>

<style scoped>

.logout-btn {
  background: none;
  border: none;
  cursor: pointer;
  font: inherit;
}

.header {
  background-color: #ffd18f;
  color: #8f2127;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.5rem;
  font-weight: bold;
}

.logo-image {
  width: 140px;

}

.nav-list {
  display: flex;
  list-style: none;
  gap: 2rem;
}

.nav-link {
  color: #8f2127;
  text-decoration: none;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.nav-link:hover,
.nav-link.router-link-active {
  background-color: rgba(255, 255, 255, 0.2);
}

.mobile-menu-btn {
  display: none;
  background: none;
  border: none;
  color: #8f2127;
  font-size: 1.5rem;
  cursor: pointer;
}

@media (max-width: 768px) {
  .nav-list {
    display: none;
    flex-direction: column;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: #ffd18f;
    padding: 1rem;
    gap: 0;
  }

  .nav-list--open {
    display: flex;
  }

  .nav-link {
    display: block;
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .mobile-menu-btn {
    display: block;
  }
}
</style>