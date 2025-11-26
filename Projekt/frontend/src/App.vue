<template>

 <div id="app">
    <!-- Bejelentkezett felhasználóknak: Header + Fő tartalom -->
    <div v-if="isAuthenticated" class="app-layout">
      <Header @logout="handleLogout" />
      <main class="main-content">
        <router-view />
      </main>
      <Footer />
    </div>
    
    <!-- Nem bejelentkezett felhasználóknak: Csak a StarterPage -->
    <div v-else>
      <router-view />
    </div>
  </div>






  <!--
  <div id="app">
    <Header />
    <main class="main-content">
      <router-view />
    </main>
    <Footer />
  </div>-->
</template>

<script>

import Header from '@/components/layout/Header.vue'
import Footer from '@/components/layout/Footer.vue'

export default {
  name: 'App',
  components: {
    Header,
    Footer
  },
  data() {
    return {
      isAuthenticated: false,
    }
  },
  created() {
    // Ellenőrizzük, hogy van-e token a localStorage-ban
    this.checkAuthentication()
    
    // Hallgatjuk a bejelentkezési eseményt
    this.$emitter.on('login', this.handleLogin)
    this.$emitter.on('logout', this.handleLogout)
  },
  methods: {
    checkAuthentication() {
      const token = localStorage.getItem('authToken')
      this.isAuthenticated = !!token
    },
    
    handleLogin() {
      this.isAuthenticated = true
    },
    
    handleLogout() {
      localStorage.removeItem('authToken')
      localStorage.removeItem('userData')
      this.isAuthenticated = false
      this.$router.push('/')
    }
  }
}


/*
import Header from './components/layout/Header.vue'
import Footer from './components/layout/Footer.vue'

export default {
  name: 'App',
  components: {
    Header,
    Footer
  }
}*/
</script>

<style>

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: #333;
  background-color: #f5f7fa;
}

#app {
  min-height: 100vh;
}

.app-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
  padding-top: 0; /* Header fixed, ezért nincs padding */
}

/* Reszponzív design */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Reszponzív grid */
.grid {
  display: grid;
  gap: 1.5rem;
}

.grid-2 {
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
}

.grid-3 {
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

/* Reszponzív táblázat */
.responsive-table {
  width: 100%;
  overflow-x: auto;
}

/* Media queries */
@media (max-width: 768px) {
  .container {
    padding: 0 0.5rem;
  }
  
  .grid-2,
  .grid-3 {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  body {
    font-size: 14px;
  }
}


/*
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  line-height: 1.6;
  color: #333;
  background-color: #f5f7fa;
}

#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.main-content {
  flex: 1;
}*/
</style>