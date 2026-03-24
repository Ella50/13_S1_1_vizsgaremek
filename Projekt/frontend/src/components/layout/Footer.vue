<template>
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-content">
        <div class="footer-section">
          <h4>Linkek</h4>
          <ul class="footer-links">
            <!-- Minden felhasználó számára elérhető linkek -->
            <li><router-link to="/menu/today" class="footer-link">Mai menü</router-link></li>
            <li><router-link to="/menu/week" class="footer-link">Heti menü</router-link></li>
            <li><router-link to="/kitchen/meals" class="footer-link">Ételek</router-link></li>
            <li><router-link to="/profile" class="footer-link">Profil</router-link></li>
            <li><router-link v-if="!AuthService.isAdmin() && !AuthService.isKitchen()" to="/personal-orders" class="footer-link">Rendeléseim</router-link></li>
            <li><router-link v-if="!AuthService.isAdmin() && !AuthService.isKitchen()"  to="/personal-invoices" class="footer-link">Számláim</router-link></li>
            

            <!--
            <template v-if="AuthService && AuthService.isAdmin()">
              <li class="footer-section-divider"><span>Admin</span></li>
              <li><router-link to="/admin/users" class="footer-link">Felhasználók kezelése</router-link></li>
              <li><router-link to="/admin/invoices" class="footer-link">Számlák kezelése</router-link></li>
              <li><router-link to="/admin/documents" class="footer-link">Dokumentumok</router-link></li>
            </template>
            
   
            <template v-if="AuthService && AuthService.isKitchen()">
              <li class="footer-section-divider"><span>Konyha</span></li>
              <li><router-link to="/kitchen/ingredients" class="footer-link">Hozzávalók</router-link></li>
              <li><router-link to="/kitchen/menu-maker" class="footer-link">Menük készítése</router-link></li>
              <li><router-link to="/kitchen/orders" class="footer-link">Rendelések kezelése</router-link></li>
              <li><router-link to="/kitchen/lunchtime" class="footer-link">Ebédeltetés</router-link></li>
            </template>-->

            
            <!-- Nem authentikált felhasználóknak -->
            <template v-if="!AuthService || !AuthService.isAuthenticated()">
              <li class="footer-section-divider"><span>Belépés</span></li>
              <li><router-link to="/login" class="footer-link">Bejelentkezés</router-link></li>
              <li><router-link to="/register" class="footer-link">Regisztráció</router-link></li>
            </template>
          </ul>
        </div>
        
        <div class="footer-section">
          <h4>Kapcsolat</h4>
          <p>info@emenza.hu</p>
          <p>+36 1 234 5678</p>
          <p>1088 Budapest, Rákóczi út 1-3.</p>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; 2026 eMenza - Minden jog fenntartva.</p>
      </div>
    </div>
  </footer>
</template>

<script>
import AuthService from '@/services/authService'

export default {
  name: 'Footer',
  data() {
    return {
      AuthService
    }
  }
}
</script>

<style scoped>
/* Fontos: a footer mindig az oldal alján maradjon */
.footer {
  background-color: #ffd18f;
  color: #8f2527;
  margin-top: auto; /* Ez tolja le a footert az oldal aljára */
  width: 100%;
  flex-shrink: 0; /* Megakadályozza, hogy a footer összenyomódjon */
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  padding: 3rem 0 2rem;
}

.footer-section h3,
.footer-section h4 {
  margin-bottom: 1rem;
  color: #8f2527;
  font-size: 1.2rem;
  font-weight: 600;
}

.footer-section p {
  color: #8f2527;
  line-height: 1.6;
  margin-bottom: 0.5rem;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 0.5rem;
}

.footer-link {
  color: #8f2527;
  text-decoration: none;
  transition: all 0.3s;
  display: inline-block;
  font-size: 0.95rem;
}

.footer-link:hover {
  color: #a51616;
  transform: translateX(5px);
}

.footer-section-divider {
  margin-top: 1rem;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #8a1212;
  list-style: none;
}

.footer-section-divider span {
  border-bottom: 2px solid #8a1212;
  padding-bottom: 0.2rem;
}

.footer-bottom {
  border-top: 2px solid rgba(138, 18, 18, 0.2);
  padding: 1.5rem 0;
  text-align: center;
  color: #8f2527;
  font-size: 0.9rem;
}

/* Reszponzív design */
@media (max-width: 768px) {
  .footer-content {
    grid-template-columns: 1fr;
    text-align: center;
  }
  
  .footer {
    margin-bottom: 0; /* Mobilon ne legyen extra margin */
  }
  
  .footer-link:hover {
    transform: translateY(-2px);
  }
  
  .footer-section-divider {
    text-align: center;
  }
}

/* Kis mobilos nézet */
@media (max-width: 480px) {
  .footer-container {
    padding: 0 1rem;
  }
  
  .footer-content {
    padding: 2rem 0 1rem;
    gap: 1.5rem;
  }
  
  .footer-links li {
    margin-bottom: 0.4rem;
  }
  
  .footer-link {
    font-size: 0.9rem;
  }
}
</style>