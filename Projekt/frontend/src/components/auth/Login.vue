<template>
  <AuthLayout>
    <h2 class="title">Bejelentkezés</h2>

    <form @submit.prevent="handleLogin" class="login-form">
      <input type="email" v-model="form.email" placeholder="Email" required>
      
      <div class="password-wrapper">
        <input 
          :type="showPassword ? 'text' : 'password'" 
          v-model="form.password" 
          placeholder="Jelszó" 
          required
          class="password-input"
        >
        <button 
          type="button" 
          class="password-toggle" 
          @click="showPassword = !showPassword"
          :title="showPassword ? 'Jelszó elrejtése' : 'Jelszó megjelenítése'"
        >
          <!-- Nyitott szem SVG (jelszó látható) -->
          <svg 
            v-if="showPassword"
            class="toggle-icon"
            xmlns="http://www.w3.org/2000/svg" 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            stroke-width="2" 
            stroke-linecap="round" 
            stroke-linejoin="round"
          >
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
          </svg>
          
          <!-- Zárt szem SVG (jelszó rejtve) -->
          <svg 
            v-else
            class="toggle-icon"
            xmlns="http://www.w3.org/2000/svg" 
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            stroke-width="2" 
            stroke-linecap="round" 
            stroke-linejoin="round"
          >
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
          </svg>
        </button>
      </div>
      
      <button class="btn_auth" type="submit" :disabled="loading">
        {{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}
      </button>

      <a class="forgot" href="/resetpassword">Elfelejtette a jelszavát?</a>

      <p v-if="error" class="error">{{ error }}</p>
    </form>

    <p class="register-text">
      Még nincs fiókja? <router-link to="/register">Regisztráljon most</router-link>
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
            email: '',
            password: ''
        },
        loading: false,
        error: '',
        showPassword: false
        }
    },
    methods: {
        async handleLogin() {
    this.loading = true
    this.error = ''
    
    try {
        const response = await axios.post('/login', this.form)

        // BOM karakter leszedése
        let responseData = response.data
        
        if (typeof responseData === 'string') {
            responseData = responseData.replace(/^\uFEFF/, '')
            try {
                responseData = JSON.parse(responseData)
            } catch (parseError) {
                console.error('JSON parse error:', parseError)
                throw new Error('Érvénytelen formátum')
            }
        }
        
        const token = responseData.token || 
                     responseData.access_token || 
                     responseData.accessToken
        
        
        if (!token) {
            this.error = 'A szerver nem küldött érvényes tokent'
            return
        }

        localStorage.setItem('auth_token', token)
        
        if (responseData.user) {
            localStorage.setItem('user', JSON.stringify(responseData.user))
        }
        
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        
        this.$router.push('/dashboard')
        
    } catch (error) {
        if (error.response?.status === 403) {
            this.error = 'Nem aktív felhasználó'
        } else {
            this.error = 'Hibás email vagy jelszó'
        }
    } finally {
        this.loading = false
    }
}
    }
  }
</script>

<style scoped>

.password-wrapper {
  position: relative;
  width: 100%;
}

.password-wrapper input {
  width: 100%;
  padding: 0.8rem;
  padding-right: 45px;
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
  box-sizing: border-box; 
}

.password-wrapper input[type="password"]::-ms-reveal,
.password-wrapper input[type="password"]::-ms-clear,
.password-wrapper input[type="password"]::-webkit-contacts-auto-fill-button,
.password-wrapper input[type="password"]::-webkit-credentials-auto-fill-button {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  pointer-events: none !important;
  width: 0 !important;
  height: 0 !important;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  margin: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  color: #666;
  opacity: 0.7;
  transition: all 0.2s;

  outline: none;
}

.password-toggle:hover {
  opacity: 1;
  color: #333;
}

.password-toggle:focus {
  outline: none;
}

.toggle-icon {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  fill: none;
  transition: all 0.2s;
  display: block;
  flex-shrink: 0;
}


</style>