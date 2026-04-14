<template>
  <AuthLayout>
    <h2 class="title">Regisztráció</h2>

    <form @submit.prevent="handleRegister" class="register-form">
      <!-- Név mezők -->
      <div class="form-row">

        <input 
          type="text" 
          v-model="form.lastName" 
          placeholder="Vezetéknév *" 
          required 
          maxlength="50" 
          @input="validateNameField($event, 'lastName')"
          @blur="validateNameField($event, 'lastName')">
        <input 
          type="text" 
          v-model="form.firstName" 
          placeholder="Keresztnév *" 
          required 
          maxlength="50" 
          @input="validateNameField($event, 'firstName')"
          @blur="validateNameField($event, 'firstName')">
      </div>
      
      <input 
        type="text" 
        v-model="form.thirdName" 
        placeholder="Harmadik név" 
        maxlength="50" 
        @input="validateNameField($event, 'thirdName')"
        @blur="validateNameField($event, 'thirdName')">

       <!-- <input type="text" v-model="form.lastName" placeholder="Vezetéknév *" required maxlength="50">
        <input type="text" v-model="form.firstName" placeholder="Keresztnév *" required maxlength="50">
      </div>
      
      <input type="text" v-model="form.thirdName" placeholder="Harmadik név" maxlength="50"> -->


  
      <div class="form-row">
        <div class="form-group">
          <label>Vármegye *</label>
          <select style="line-height: 100px;" v-model="form.county_id" @change="loadCities" required>
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
        <label class="text-nowrap">Lakcím (utca, házszám) *</label>
        <input type="text" v-model="form.address" placeholder="pl. Kossuth utca 10." required maxlength="255">
      </div>

      <!-- Email és jelszó --> 
      <div class="form-group">
        <label>Email cím (iskolai) *</label>
        <input type="email" v-model="form.email" placeholder="pelda@iskola.hu" required maxlength="255">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label>Jelszó *</label>
          <PasswordInput 
            v-model="form.password"
            placeholder="Minimum 8 karakter"
            :required="true"
            :minlength="8"
            :min-width="500"
          />
        </div>
        <div class="form-group">
          <label class="text-nowrap">Jelszó megerősítése *</label>
          <PasswordInput 
            v-model="form.password_confirmation"
            placeholder="Jelszó újra"
            :required="true"
          />
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

      <button class="btn_auth" type="submit" :disabled="loading">
        {{ loading ? 'Regisztrálás...' : 'Regisztrálás' }}
      </button>

      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="error" class="error">{{ error }}</p>
    </form>

    <p class="register-text">
      Van már fiókja? <router-link to="/login">Jelentkezzen be</router-link>
    </p>

    <!-- Alert értesítés -->
    <div class="alert-container" v-if="alertVisible">
      <div :class="['alert', alertType]">
        <strong v-if="alertTitle">{{ alertTitle }}</strong>
        <div>{{ alertMessage }}</div>
      </div>
    </div>
  </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLayout.vue'
import PasswordInput from './PasswordInput.vue'
import axios from '../../axios'
import { addAlert } from '../auth/AppAlert.vue'

export default {
  components: { AuthLayout, PasswordInput },
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
      success: '',
      
      // Alert állapotok
      alertVisible: false,
      alertMessage: '',
      alertTitle: '',
      alertType: 'success',
      alertTimeout: null
    }
  },
  mounted() {
    this.loadCounties()
  },
  beforeUnmount() {
    if (this.alertTimeout) {
      clearTimeout(this.alertTimeout)
    }
  },
  methods: {
    // Név mező validáció: csak betűk (magyar ékezetesek is) és kötőjel
    validateNameField(event, fieldName) {
      let value = event.target.value
      // Csak betűk (angol, magyar ékezetes) és kötőjel maradhat
      const regex = /[^A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű\-]/g
      const filtered = value.replace(regex, '')
      
      if (value !== filtered) {
        this.form[fieldName] = filtered
        this.error = `A ${this.getFieldLabel(fieldName)} mezőben csak betűk és kötőjel használható!`
        setTimeout(() => {
          if (this.error === `A ${this.getFieldLabel(fieldName)} mezőben csak betűk és kötőjel használható!`) {
            this.error = ''
          }
        }, 3000)
      }
    },

    getFieldLabel(fieldName) {
      const labels = {
        lastName: 'vezetéknév',
        firstName: 'keresztnév',
        thirdName: 'harmadik név'
      }
      return labels[fieldName] || fieldName
    },

    async loadCounties() {
      try {
        console.log('Loading counties...')
        const response = await axios.get('/countiesReg')
        console.log('Raw counties response:', response.data)
        
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
    // Alert metódus
    showAlert({ message, type = 'success', title = '' }) {
      if (this.alertTimeout) {
        clearTimeout(this.alertTimeout)
      }
      
      this.alertMessage = message
      this.alertType = type
      this.alertTitle = title
      this.alertVisible = true
      
      this.alertTimeout = setTimeout(() => {
        this.alertVisible = false
      }, 3000)
    },
    
    async loadCounties() {
      try {
        console.log('Loading counties...')
        const response = await axios.get('/countiesReg')
        console.log('Raw counties response:', response.data)
        
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
        this.showAlert({
          message: 'Hiba történt a megyék betöltésekor',
          type: 'error'
        })
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
      // Név mezők validációja a beküldés előtt
      const nameFields = [
        { value: this.form.lastName, name: 'Vezetéknév' },
        { value: this.form.firstName, name: 'Keresztnév' }
      ]
      
      const nameRegex = /^[A-Za-zÁÉÍÓÖŐÚÜŰáéíóöőúüű\-]+$/
      
      for (const field of nameFields) {
        if (!field.value || !nameRegex.test(field.value)) {
          this.error = `${field.name} mezőben csak betűk és kötőjel használható!`
          return
      }

      try {
        console.log('Loading cities for county ID:', this.form.county_id)
        const response = await axios.get(`/cities/by-countyReg/${this.form.county_id}`)
        console.log('Raw cities response:', response.data)
        
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
        this.showAlert({
          message: 'Hiba történt a városok betöltésekor',
          type: 'error'
        })
      }
      
      // Harmadik név ellenőrzése (ha van kitöltve)
      if (this.form.thirdName && !nameRegex.test(this.form.thirdName)) {
        this.error = 'Harmadik név mezőben csak betűk és kötőjel használható!'
        return
      }

      // Email végződés ellenőrzés
      const emailRegex = /@iskola\.hu$/
      if (!emailRegex.test(this.form.email)) {
        this.showAlert({
          message: 'Csak iskolai email cím fogadható el!',
          type: 'error'
        })
        return
      }

      // Jelszó egyezés ellenőrzés
      if (this.form.password !== this.form.password_confirmation) {
        this.showAlert({
          message: 'A jelszavak nem egyeznek',
          type: 'error'
        })
        return
      }

      // Kötelező mezők ellenőrzése
      if (!this.form.county_id) {
        this.showAlert({
          message: 'Kérjük válasszon megyét!',
          type: 'error'
        })
        return
      }
      if (!this.form.city_id) {
        this.showAlert({
          message: 'Kérjük válasszon várost!',
          type: 'error'
        })
        return
      }
      if (!this.form.address.trim()) {
        this.showAlert({
          message: 'Kérjük adja meg a lakcímét!',
          type: 'error'
        })
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

        this.showAlert({
          message: 'Sikeres regisztráció!',
          type: 'success'
        })
        
        setTimeout(() => {
          this.$router.push('/login')
        }, 1500)
      } catch (err) {
        console.error('Registration error:', err)
        
        if (err.response?.data?.errors) {
          const errors = Object.values(err.response.data.errors).flat()
          this.showAlert({
            message: errors.join('\n'),
            type: 'error'
          })
        } else {
          this.showAlert({
            message: err.response?.data?.message || 'Regisztrációs hiba történt',
            type: 'error'
          })
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
}

.register-form input, .register-form select {
  width: 100%;
  padding: 0.8rem;
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
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

/* Alert stílusok */
.alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 10000;
}

.alert {
  margin-bottom: 10px;
  padding: 14px 18px;
  border-radius: 12px;
  color: white;
  min-width: 250px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  animation: slideIn 0.3s ease;
}

.alert.success {
  background: #4CAF50;
}

.alert.error {
  background: #ff4d4f;
}

.alert.warning {
  background: #ff9800;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@media (max-width: 480px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}

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

@media screen and (min-width: 769px) and (max-width: 1024px) {
  .register-form {
    max-width: 550px;
  }
}
</style>