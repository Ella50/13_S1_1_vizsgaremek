<template>
  <div class="home">
    <!--<section class="hero">
      <h1>Üdvözöljük Vue Frontend Projektünkben</h1>
      <p>Ez egy modern, reszponzív webalkalmazás, amely Vue.js keretrendszerrel készült, és később Laravel backend-del lesz összekötve.</p>
      <button class="cta-button" @click="scrollToFeatures">Tudj meg többet</button>
    </section>-->
    
    <section class="menuk" ref="featuresSection"> <!--features volt alapból-->
      <div class="menu-card">
        <div class="feature-icon">11.13</div>
        <h3>Mai menü</h3>

      </div>
      
      <div class="menu-card">
        <div class="feature-icon">11.14</div>
        <h3>Holnapi menü</h3>

      </div>
      
      <div class="menu-card">
        <div class="feature-icon"></div>
        <h3></h3>

      </div>
    </section>
    
    <section class="data-section">
      <h2>Adatok az adatbázisból</h2>
      <DataDisplay />
    </section>
  </div>
</template>

<script>
import DataDisplay from '@/components/DataDisplay.vue'

export default {
  name: 'Home',
  components: {
    DataDisplay
  },
  methods: {
    scrollToFeatures() {
      this.$refs.featuresSection.scrollIntoView({ behavior: 'smooth' })
    }
  }
}
</script>

<style scoped>
.home {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.hero {
  text-align: center;
  padding: 3rem 1rem;
  margin-bottom: 2rem;
}

.hero h1 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  color: #2c3e50;
}

.hero p {
  font-size: 1.2rem;
  color: #7f8c8d;
  max-width: 600px;
  margin: 0 auto 2rem;
}

.cta-button {
  background-color: #ff914d;
  color: white;
  padding: 0.8rem 1.5rem;
  border: none;
  border-radius: 30px;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.3s, box-shadow 0.3s;
  box-shadow: 0 4px 15px rgba(106, 17, 203, 0.3);
}

.cta-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(106, 17, 203, 0.4);
}

.menuk {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-top: 3rem;
}

.menu-card {
  background: white;
  border-radius: 10px;
  padding: 2rem;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s, box-shadow 0.3s;
}

.menu-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.feature-icon {
  width: 60px;
  height: 60px;
  background-color: #ff914d;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  color: white;
  font-size: 1.5rem;
}

.menu-card h3 {
  margin-bottom: 1rem;
  color: #2c3e50;
}

.menu-card p {
  color: #7f8c8d;
}

.data-section {
  margin-top: 4rem;
  padding: 2rem;
  background: white;
  border-radius: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.data-section h2 {
  margin-bottom: 1.5rem;
  color: #2c3e50;
  text-align: center;
}

@media (max-width: 768px) {
  .hero h1 {
    font-size: 2rem;
  }
  
  .menuk {
    grid-template-columns: 1fr;
  }
}
</style>