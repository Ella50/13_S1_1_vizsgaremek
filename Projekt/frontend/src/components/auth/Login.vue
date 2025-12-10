<template>
  <AuthLayout>
    <h2 class="title">Bejelentkezés</h2>

    <form @submit.prevent="handleLogin" class="login-form">
      <input type="email" v-model="form.email" placeholder="Email" required>
      <input type="password" v-model="form.password" placeholder="Jelszó" required> 
      <button type="submit" :disabled="loading">{{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}</button>

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
            error: ''
        }
    },
    methods: {
        async handleLogin() {
    this.loading = true
    this.error = ''
    
    try {
        const response = await axios.post('/login', this.form)
        
        console.log('Raw response data:', response.data)
        console.log('Response data type:', typeof response.data)
        console.log('Response keys:', Object.keys(response.data))
        
        // 1. Tisztítsd meg a választ BOM karaktertől
        let responseData = response.data
        
        // Ha string a válasz, parse-oljuk JSON-né
        if (typeof responseData === 'string') {
            // Távolítsuk el a BOM karaktert
            responseData = responseData.replace(/^\uFEFF/, '')
            try {
                responseData = JSON.parse(responseData)
            } catch (parseError) {
                console.error('JSON parse error:', parseError)
                throw new Error('Érvénytelen válasz formátum')
            }
        }
        
        console.log('Cleaned response:', responseData)
        
        // 2. Token keresése - most már biztosan objektum
        const token = responseData.token || 
                     responseData.access_token || 
                     responseData.accessToken
        
        console.log('Token found:', token)
        
        if (!token) {
            console.error('No token in cleaned response:', responseData)
            this.error = 'A szerver nem küldött érvényes tokent'
            return
        }
        
        // 3. Token mentése
        localStorage.setItem('auth_token', token)
        console.log('Token saved to localStorage')
        
        // 4. User adatok mentése
        if (responseData.user) {
            localStorage.setItem('user', JSON.stringify(responseData.user))
            console.log('User saved:', responseData.user)
        }
        
        // 5. Axios header beállítása
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
        console.log('Axios header set')
        
        // 6. Redirect
        this.$router.push('/dashboard')
        
    } catch (error) {
        console.error('Login error:', {
            message: error.message,
            response: error.response?.data,
            status: error.response?.status
        })
        
        if (error.response?.status === 401) {
            this.error = error.response?.data?.message || 'Hibás email vagy jelszó'
        } else {
            this.error = error.message || 'Bejelentkezési hiba'
        }
        if (error.response?.status === 403) {
            this.error = error.response?.data?.message || 'Inaktív felhasználó'
        } else {
            this.error = error.message || 'Bejelentkezési hiba'
        }
    } finally {
        this.loading = false
    }
}
    }
  }
</script>
