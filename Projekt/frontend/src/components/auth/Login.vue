<template>
  <AuthLayout>
    <h2 class="title">Bejelentkezés</h2>

    <form @submit.prevent="handleLogin" class="login-form">
      <input type="email" v-model="form.email" placeholder="Email" required>
      <input type="password" v-model="form.password" placeholder="Jelszó" required> 
      <button type="submit" :disabled="loading">{{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}</button>

        <a class="forgot" href="#">Elfelejtette a jelszavát?</a>

      <p v-if="error" class="error">{{ error }}</p>
    </form>


    <p class="register-text">
      Még nincs fiókja? <router-link to="/register">Regisztráljon most</router-link>
    </p>
  </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLAyout.vue'
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
                
                console.log('Login response:', response.data)
                
                const token = response.data.token || 
                            response.data.access_token || 
                            response.data.accessToken
                
                if (!token) {
                    console.error('No token in response:', response.data)
                    throw new Error('A szerver nem küldött érvényes tokent')
                }
                
                localStorage.setItem('auth_token', token)
                console.log('Token saved:', token.substring(0, 20) + '...')
                
                if (response.data.user) {
                    localStorage.setItem('user', JSON.stringify(response.data.user))
                } else {
                    localStorage.setItem('auth_data', JSON.stringify(response.data))
                }
                
                // Átirányítás dashboardra
                this.$router.push('/dashboard')
                
            } 

            catch (error) {
                console.error('Login error details:', {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status
                })

                if (error.response?.status === 401) {
                  // Laravel alapértelmezett hibája
                  this.error = error.response?.data?.message || 'Hibás email vagy jelszó'
                } else if (error.response?.status === 422) {
                    // Validációs hiba
                    this.error = 'Érvénytelen adatok. Ellenőrizd a mezőket.'
                } else if (error.response?.status === 404) {
                    this.error = 'A bejelentkezési szolgáltatás nem elérhető'
                } else if (error.code === 'ERR_NETWORK') {
                    this.error = 'Hálózati hiba. Ellenőrizd az internetkapcsolatot.'
                } else if (error.message.includes('timeout')) {
                    this.error = 'Időtúllépés. Kérjük, próbáld újra.'
                } else {
                    this.error = error.response?.data?.message || 
                                error.message || 
                                'Ismeretlen bejelentkezési hiba'
                }
                
            } 

            finally {
                this.loading = false
            }
        }
    }
  }
</script>
