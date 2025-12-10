<template>
    <AuthLayout title="Jelszó visszaállítása">
        <form @submit.prevent="handleResetPassword" class="auth-form">
        <div class="form-group">
            <label for="email">Email cím</label>
            <input
            type="email"
            id="email"
            v-model="form.email"
            required
            placeholder="Írd be az email címed"
            />
        </div>
    
        <button type="submit" :disabled="loading">
            {{ loading ? 'Feldolgozás...' : 'Jelszó visszaállítása' }}
        </button>
    
        <p v-if="error" class="error-message">{{ error }}</p>
        <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
        </form>
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
                email: ''
            },
            loading: false,
            error: '',
            successMessage: ''
        }
    },
    methods: {
        async handleResetPassword() {
            // Alap validáció
            if (!this.form.email) {
                this.error = 'Kérjük, add meg az email címed!'
                return
            }
            
            if (!this.form.email.includes('@')) {
                this.error = 'Érvényes email címet adj meg!'
                return
            }
            
            this.loading = true
            this.error = ''
            this.successMessage = ''

            try {
                console.log('Küldés...', this.form.email)
                
                // API hívás
                const response = await axios.post('/api/reset-password', {
                    email: this.form.email
                })
                
                console.log('Válasz:', response.data)
                
                // Sikeres válasz kezelése
                if (response.data.success) {
                    this.successMessage = response.data.message
                    this.form.email = '' // Form reset
                    
                    // Opcionális: timeout után átirányítás
                    setTimeout(() => {
                        this.$router.push('/login')
                    }, 3000)
                }
                
            } catch (err) {
                console.error('Hiba:', err.response?.data || err.message)
                
                // Hiba kezelés
                if (err.response?.status === 422) {
                    // Validációs hiba
                    this.error = err.response.data.message || 'Érvénytelen email cím.'
                } else if (err.response?.status === 500) {
                    // Szerver hiba
                    this.error = 'Szerver hiba. Kérjük, próbáld újra később.'
                } else {
                    // Egyéb hiba
                    this.error = 'Hiba történt. Kérjük, próbáld újra.'
                }
            } finally {
                this.loading = false
            }
        }
    }
}
</script>