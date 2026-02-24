<template>
  <AuthLayout>
    <h2 class="title">Regisztráció</h2>

    <form @submit.prevent="handleRegister" class="register-form">
      <!-- Név mezők -->
      <div class="form-row">
        <input type="text" v-model="form.lastName" placeholder="Vezetéknév *" required>
        <input type="text" v-model="form.firstName" placeholder="Keresztnév *" required>
      </div>
      
      <input type="text" v-model="form.thirdName" placeholder="Harmadik név (opcionális)">

      <!-- Cím adatok -->
      <div class="form-row">
        <div class="form-group">
          <label>Vármegye *</label>
          <select  style="line-height: 100px;" v-model="form.county_id" @change="loadCities" required>
            <option value="">Válassz vármegyét</option>
            <option v-for="county in counties" :key="county.id" :value="county.id">
              {{ county.countyName }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Város *</label>
          <select v-model="form.city_id" required :disabled="!form.county_id">
            <option value="">Válassz várost</option>
            <option v-for="city in cities" :key="city.id" :value="city.id">
              {{ city.zipCode }} - {{ city.cityName }}
            </option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Lakcím (utca, házszám) *</label>
        <input type="text" v-model="form.address" placeholder="pl. Kossuth utca 10." required>
      </div>

      <!-- Email és jelszó -->
      <div class="form-group">
        <label>Email cím (iskolai) *</label>
        <input type="email" v-model="form.email" placeholder="pelda@iskola.hu" required>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Jelszó *</label>
          <input type="password" v-model="form.password" placeholder="Minimum 8 karakter" required minlength="8">
        </div>
        <div class="form-group">
          <label>Jelszó megerősítése *</label>
          <input type="password" v-model="form.password_confirmation" placeholder="Jelszó újra" required>
        </div>
      </div>

      <!-- Felhasználó típus -->
      <div class="form-group">
        <label>Felhasználó típus *</label>
        <select v-model="form.userType" required>
          <option value="Tanuló">Tanuló</option>
          <option value="Tanár">Tanár</option>
          <option value="Dolgozó">Dolgozó</option>
        </select>
      </div>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Regisztrálás...' : 'Regisztrálás' }}
      </button>

      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="error" class="error">{{ error }}</p>
    </form>

    <p class="register-text">
      Van már fiókja? <router-link to="/login">Jelentkezzen be</router-link>
    </p>
  </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLayout.vue'
import axios from '../../axios'

export default {
  components: { AuthLayout },
  data() {
    return {
      form: {
        firstName: '',
        lastName: '',
        thirdName: '',
        email: '',
        password: '',
        password_confirmation: '',
        userType: 'Tanuló',
        county_id: '',
        city_id: '',
        address: ''
      },
      counties: [],
      cities: [],
      loading: false,
      error: '',
      success: ''
    }
  },
  mounted() {
    this.loadCounties()
  },
  methods: {
    async loadCounties() {
  try {
    console.log('Loading counties...')
    const response = await axios.get('/countiesReg')
    console.log('Raw counties response:', response.data)
    
    // BOM eltávolítása ha stringként jön
    let responseData = response.data
    
    if (typeof responseData === 'string') {
      console.log('Response is string, cleaning BOM...')
      responseData = responseData.replace(/^\uFEFF/, '')
      responseData = JSON.parse(responseData)
    } else if (typeof responseData === 'object') {
      // Ha object, akkor is lehet BOM a stringesített verzióban
      console.log('Response is object, checking for BOM in stringified version...')
      const jsonString = JSON.stringify(responseData)
      if (jsonString.charCodeAt(0) === 0xFEFF) {
        console.log('BOM found in object, cleaning...')
        const cleanString = jsonString.replace(/^\uFEFF/, '')
        responseData = JSON.parse(cleanString)
      }
    }
    
    console.log('Cleaned response data:', responseData)
    
    if (responseData.success) {
      this.counties = responseData.data
      console.log('Counties loaded successfully:', this.counties.length)
    } else {
      console.error('Counties API returned success=false:', responseData.message)
    }
  } catch (error) {
    console.error('Megye betöltési hiba:', error)
    if (error.response) {
      console.error('Response status:', error.response.status)
      console.error('Response data:', error.response.data)
    }
  }
},

    async loadCities() {
  if (!this.form.county_id) {
    this.cities = []
    this.form.city_id = null
    return
  }

  try {
    console.log('Loading cities for county ID:', this.form.county_id)
    const response = await axios.get(`/cities/by-countyReg/${this.form.county_id}`)
    console.log('Raw cities response:', response.data)
    
    // BOM eltávolítása
    let responseData = response.data
    
    if (typeof responseData === 'string') {
      console.log('Response is string, cleaning BOM...')
      responseData = responseData.replace(/^\uFEFF/, '')
      responseData = JSON.parse(responseData)
    } else if (typeof responseData === 'object') {
      const jsonString = JSON.stringify(responseData)
      if (jsonString.charCodeAt(0) === 0xFEFF) {
        console.log('BOM found in object, cleaning...')
        const cleanString = jsonString.replace(/^\uFEFF/, '')
        responseData = JSON.parse(cleanString)
      }
    }
    
    console.log('Cleaned response data:', responseData)
    
    if (responseData.success) {
      this.cities = responseData.data
      console.log('Cities loaded successfully:', this.cities.length)
      
      if (this.form.city_id) {
        const cityExists = this.cities.some(city => city.id == this.form.city_id)
        if (!cityExists) {
          this.form.city_id = null
        }
      }
    } else {
      console.error('Cities API returned success=false:', responseData.message)
      this.cities = []
    }
  } catch (error) {
    console.error('Város betöltési hiba:', error)
    if (error.response) {
      console.error('Response status:', error.response.status)
      console.error('Response data:', error.response.data)
    }
    this.cities = []
  }
},

    async handleRegister() {
      // Email végződés ellenőrzés
      const emailRegex = /@iskola\.hu$/
      if (!emailRegex.test(this.form.email)) {
        this.error = 'Csak iskolai email cím fogadható el!'
        return
      }

      // Jelszó egyezés ellenőrzés
      if (this.form.password !== this.form.password_confirmation) {
        this.error = 'A jelszavak nem egyeznek'
        return
      }

      // Kötelező mezők ellenőrzése
      if (!this.form.county_id) {
        this.error = 'Kérjük válasszon megyét!'
        return
      }
      if (!this.form.city_id) {
        this.error = 'Kérjük válasszon várost!'
        return
      }
      if (!this.form.address.trim()) {
        this.error = 'Kérjük adja meg a lakcímét!'
        return
      }

      this.loading = true
      this.error = ''
      this.success = ''

      try {
        console.log('Sending registration data:', this.form)
        const response = await axios.post('/register', this.form)
        console.log('Registration response:', response.data)
        
        this.success = response.data.message || 'Sikeres regisztráció!'
        
        // Űrlap alaphelyzetbe állítása
        this.form = {
          firstName: '', lastName: '', thirdName: '', email: '',
          password: '', password_confirmation: '', userType: 'Tanuló',
          county_id: '', city_id: '', address: ''
        }
        this.cities = []

        alert('Sikeres regisztráció!')
        this.$router.push('/login')
      } catch (err) {
        console.error('Registration error:', err)
        
        if (err.response?.data?.errors) {
          const errors = Object.values(err.response.data.errors).flat()
          this.error = errors.join('\n')
        } else {
          this.error = err.response?.data?.message || 'Regisztrációs hiba történt'
        }
      } finally { 
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.register-form {
  width: 70%;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  /*
    display: flex;
  flex-direction: column;
  gap: 1.2rem;
  width: 100%;
  max-width: 600px;
  margin: 0 auto;
  */
}

.register-form input, .register-form select {
  width: 100%;
  padding: 0.8rem;
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
  /*
    padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
  width: 100%;
  box-sizing: border-box;
  */
}


.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
/* Reszponzív töréspontok */
@media screen and (max-width: 768px) {
  .register-form {
    max-width: 100%;
    padding: 0 1.5rem;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .title {
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
  }
}

@media screen and (max-width: 480px) {
  .register-form {
    padding: 0 1rem;
  }

  .register-form input,
  .register-form select,
  .register-form button {
    padding: 0.7rem 1rem;
    font-size: 0.95rem;
  }

  .title {
    font-size: 1.5rem;
    margin-bottom: 1.2rem;
  }

  .form-group label {
    font-size: 0.85rem;
  }

  .success, .error {
    padding: 0.8rem;
    font-size: 0.9rem;
  }
}

@media screen and (max-width: 360px) {
  .register-form {
    padding: 0 0.8rem;
  }

  .register-form input,
  .register-form select,
  .register-form button {
    padding: 0.6rem 0.8rem;
    font-size: 0.9rem;
  }

  .title {
    font-size: 1.3rem;
  }
}

/* Táblagép nézet (landscape) */
@media screen and (min-width: 769px) and (max-width: 1024px) {
  .register-form {
    max-width: 550px;
  }
}

</style>