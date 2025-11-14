<template>
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-content">

        
        <div class="footer-section">
          <h4>Linkek</h4>
          <ul class="footer-links">
            <li><router-link to="/" class="footer-link">Főoldal</router-link></li>
            <li><router-link to="/contact" class="footer-link">Kapcsolat</router-link></li>
          </ul>
        </div>
        
        <div class="footer-section">
          <h4>Kapcsolat</h4>
          <p>email@example.com</p>
          <p>+36 1 234 5678</p>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; 2025 Minden jog fenntartva.</p>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'Footer'
}
</script>

<style scoped>
.footer {
  background-color: #ffd18f;
  color: #8f2527;
  margin-top: auto;
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
}

.footer-section p {
  color: #8f2527;
  line-height: 1.6;
}

.footer-links {
  list-style: none;
  padding: 0;
}

.footer-links li {
  margin-bottom: 0.5rem;
}

.footer-link {
  color: #8f2527;
  text-decoration: none;
  transition: color 0.3s;
}

.footer-link:hover {
  color: #3498db;
}

.footer-bottom {
  border-top: 1px solid #34495e;
  padding: 1.5rem 0;
  text-align: center;
  color: #8f2527;
}

@media (max-width: 768px) {
  .footer-content {
    grid-template-columns: 1fr;
    text-align: center;
  }
}
</style>