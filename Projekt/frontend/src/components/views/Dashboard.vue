<template>
  <div class="dashboard">
    <div class="content-card">
      <div class="welcome-section">
        <h1 class="title">Üdvözöljük, {{ userName }}!</h1>
        <p class="role-badge">{{ userRoleDisplay }}</p>
      </div>
      
      <div class="quick-links">
        <!-- Admin linkek -->
        <div v-if="isAdmin" class="links-section">
          <h2 class="section-title">Adminisztráció</h2>
          <div class="links-grid">
            <router-link to="/admin/users" class="link-card">
      
              <div class="link-content">
                <h3>Felhasználók kezelése</h3>
                <p>Felhasználók listázása, szerkesztése, státusz módosítása</p>
              </div>
            </router-link>
            <router-link to="/admin/invoices" class="link-card">
           
              <div class="link-content">
                <h3>Számlák kezelése</h3>
                <p>Számlák generálása, státusz módosítása, PDF letöltés</p>
              </div>
            </router-link>
            <router-link to="/admin/documents" class="link-card">
        
              <div class="link-content">
                <h3>Dokumentumok kezelése</h3>
                <p>Felhasználói dokumentumok jóváhagyása, kezelése</p>
              </div>
            </router-link>
          </div>
        </div>
        
        <!-- Konyha linkek -->
        <div v-if="isKitchen" class="links-section">
          <h2 class="section-title">Konyhai műveletek</h2>
          <div class="links-grid">
            <router-link to="/kitchen/meals" class="link-card">

              <div class="link-content">
                <h3>Ételek kezelése</h3>
                <p>Ételek létrehozása, szerkesztése, összetevők hozzáadása</p>
              </div>
            </router-link>
            <router-link to="/kitchen/ingredients" class="link-card">
           
              <div class="link-content">
                <h3>Összetevők kezelése</h3>
                <p>Hozzávalók listázása, tápértékek módosítása</p>
              </div>
            </router-link>
            <router-link to="/kitchen/menu-maker" class="link-card">
       
              <div class="link-content">
                <h3>Menü készítő</h3>
                <p>Napi menük összeállítása, ételek kiválasztása</p>
              </div>
            </router-link>
            <router-link to="/kitchen/orders" class="link-card">
        
              <div class="link-content">
                <h3>Rendelések kezelése</h3>
                <p>Napi rendelések megtekintése, összesítések</p>
              </div>
            </router-link>
            <router-link to="/kitchen/lunchtime" class="link-card">
     
              <div class="link-content">
                <h3>Ebédeltetés</h3>
                <p>Ebédelési státusz rögzítése</p>
              </div>
            </router-link>
          </div>
        </div>
        
        <!-- Általános linkek (minden felhasználónak) -->
        <div class="links-section">
          <h2 class="section-title">Menü és rendelések</h2>
          <div class="links-grid">
            <router-link to="/menu/today" class="link-card">

              <div class="link-content">
                <h3>Mai menü</h3>
                <p>Mai menü megtekintése</p>
              </div>
            </router-link>
            <router-link to="/menu/week" class="link-card">
   
              <div class="link-content">
                <h3>Heti menü</h3>
                <p>Heti menü áttekintése</p>
              </div>
            </router-link>
            <router-link to="/kitchen/meals" class="link-card">
   
              <div class="link-content">
                <h3>Ételek listája</h3>
                <p>Összes étel megtekintése, tápérték információk</p>
              </div>
            </router-link>
            <router-link to="/personal-orders" class="link-card">

              <div class="link-content">
                <h3>Rendeléseim</h3>
                <p>Saját rendelések kezelése, módosítás, lemondás</p>
              </div>
            </router-link>
            <router-link to="/personal-invoices" class="link-card">
   
              <div class="link-content">
                <h3>Számláim</h3>
                <p>Saját számlák megtekintése, PDF letöltés</p>
              </div>
            </router-link>
            <router-link to="/profile" class="link-card">
   
              <div class="link-content">
                <h3>Profilom</h3>
                <p>Személyes adatok, egészségügyi információk, jelszó módosítás</p>
              </div>
            </router-link>
          </div>
        </div>
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
    userRoleDisplay() {
      const roles = {
        'Admin': 'Rendszergazda',
        'Konyha': 'Konyhai dolgozó',
        'Tanuló': 'Tanuló',
        'Tanár': 'Tanár',
        'Dolgozó': 'Dolgozó'
      }
      return roles[this.userRole] || this.userRole
    },
    userName() {
      const user = this.user
      return user ? `${user.lastName} ${user.firstName}` : 'Vendég' //nincs vendég
    }
  },
  
  mounted() {
    if (!this.isAuthenticated) {
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: calc(100vh - 200px);
}

.content-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  padding: 1.5rem;
}

.welcome-section {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.title {
  font-size: 1.75rem;
  color: #8a1212;
  margin: 0 0 0.5rem 0;
  font-weight: 600;
}

.role-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f0a24a;
  color: #7b2c2c;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Links sections */
.links-section {
  margin-bottom: 2rem;
}

.links-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #8a1212;
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f0a24a;
  display: inline-block;
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}

.link-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  background: #f8f9fa;
  border: 1px solid #eee;
  border-radius: 12px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.link-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: #f0a24a;
  background: white;
}

.link-content {
  flex: 1;
}

.link-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #8a1212;
}

.link-content p {
  margin: 0;
  font-size: 0.75rem;
  color: #666;
  line-height: 1.4;
}

/* Empty state */
.no-links {
  text-align: center;
  padding: 3rem;
  color: #888;
  background: #f9f9f9;
  border-radius: 12px;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard {
    padding: 1rem;
  }

  .content-card {
    padding: 1rem;
  }

  .title {
    font-size: 1.25rem;
  }

  .links-grid {
    grid-template-columns: 1fr;
  }

  .link-card {
    padding: 0.75rem 1rem;
  }

  .link-icon {
    width: 40px;
    height: 40px;
    font-size: 1.25rem;
  }

  .link-content h3 {
    font-size: 0.9rem;
  }

  .link-content p {
    font-size: 0.7rem;
  }
}

@media (max-width: 480px) {
  .section-title {
    font-size: 0.9rem;
  }

  .link-card {
    flex-direction: column;
    text-align: center;
  }

  .link-icon {
    margin-bottom: 0.5rem;
  }
}
</style>