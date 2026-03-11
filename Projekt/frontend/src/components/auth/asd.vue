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
        /*console.error('Login error:', {
            message: error.message,
            response: error.response?.data,
            status: error.response?.status
        })*/
        
        if (error.response?.status === 401) {
            this.error = 'Hibás email vagy jelszó'
        } else {
            this.error =  'Hibás email vagy jelszó'
        }
        if (error.response?.status === 403) {
            this.error = 'Inaktív felhasználó'
        } else {
            this.error = 'Bejelentkezési hiba'
        }
    } finally {
        this.loading = false
    }
}
    }
  }
</script>
