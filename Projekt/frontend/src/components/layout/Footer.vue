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
            <li><router-link v-if="!AuthService.isAdmin() && !AuthService.isKitchen()" to="/profile" class="footer-link">Profil</router-link></li>
            <li><router-link v-if="!AuthService.isAdmin() && !AuthService.isKitchen()" to="/personal-orders" class="footer-link">Rendeléseim</router-link></li>
            <li><router-link v-if="!AuthService.isAdmin() && !AuthService.isKitchen()"  to="/personal-invoices" class="footer-link">Számláim</router-link></li>
          
            
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

.footer {
  background-color: #ffd18f;
  color: #8f2527;
  margin-top: auto; 
  width: 100%;
  flex-shrink: 0; 
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


@media (max-width: 768px) {
  .footer-content {
    grid-template-columns: 1fr;
    text-align: center;
  }
  
  .footer {
    margin-bottom: 0; 
  }
  
  .footer-link:hover {
    transform: translateY(-2px);
  }
  
  .footer-section-divider {
    text-align: center;
  }
}


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