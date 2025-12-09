<template>
    <div class="login-container">
        <div class="card">
            <h2>Bejelentkezés</h2>
            <form @submit.prevent="handleLogin">
                <div class="form-group">
                    <label>Email cím</label>
                    <input type="email" v-model="form.email" required>
                </div>
                <div class="form-group">
                    <label>Jelszó</label>
                    <input type="password" v-model="form.password" required>
                </div>
                <button type="submit" :disabled="loading">
                    {{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}
                </button>
                <p v-if="error" class="error">{{ error }}</p>
            </form>
            <p>
                Nincs fiókod?
                <router-link to="/register">Regisztrálj</router-link>
            </p>
        </div>
    </div>
</template>

<script>
import axios from '../../axios'


export default {
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
                
                console.log('Login response:', response.data) // DEBUG
                
                // 1. PRÓBÁLD KI EZEKET A TOKEN NEVEKET:
                const token = response.data.token || 
                            response.data.access_token || 
                            response.data.accessToken
                
                if (!token) {
                    console.error('No token in response:', response.data)
                    throw new Error('No token received from server')
                }
                
                // Token mentése
                localStorage.setItem('auth_token', token)
                console.log('Token saved:', token.substring(0, 20) + '...')
                
                // User adatok mentése
                if (response.data.user) {
                    localStorage.setItem('user', JSON.stringify(response.data.user))
                } else {
                    // Ha nincs user objektum, mentheted a teljes response-t
                    localStorage.setItem('auth_data', JSON.stringify(response.data))
                }
                
                // Átirányítás dashboardra
                this.$router.push('/dashboard')
                
            } catch (error) {
                console.error('Login error details:', {
                message: error.message,
                response: error.response?.data,
                status: error.response?.status
                })
                this.error = error.response?.data?.message || 'Bejelentkezési hiba'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.card {
    width: 400px;
    padding: 2rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-group input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

button {
    width: 100%;
    padding: 0.75rem;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.error {
    color: #dc3545;
    margin-top: 1rem;
}
</style>