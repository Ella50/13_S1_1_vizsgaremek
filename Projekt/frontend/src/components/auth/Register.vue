<template>
    <div class="register-container">
        <div class="card">
            <h2>Regisztráció</h2>
            <form @submit.prevent="handleRegister">
                <div class="form-group">
                    <label>Keresztnév</label>
                    <input type="text" v-model="form.firstName" required>
                </div>
                <div class="form-group">
                    <label>Vezetéknév</label>
                    <input type="text" v-model="form.lastName" required>
                </div>
                <div class="form-group">
                    <label>Email cím (iskolai)</label>
                    <input type="email" v-model="form.email" required>
                </div>
                <div class="form-group">
                    <label>Jelszó (minimum 8 karakter)</label>
                    <input type="password" v-model="form.password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Jelszó megerősítése</label>
                    <input type="password" v-model="form.password_confirmation" required>
                </div>
                <div class="form-group">
                    <label>Felhasználó típusa</label>
                    <select v-model="form.user_type" required>
                        <option value="Tanuló">Tanuló</option>
                        <option value="Tanár">Tanár</option>
                        <option value="Dolgozó">Dolgozó</option>
                        <option value="Külsős">Külsős</option>
                    </select>
                </div>
                <button type="submit" :disabled="loading">
                    {{ loading ? 'Regisztrálás...' : 'Regisztrálás' }}
                </button>
                <p v-if="success" class="success">{{ success }}</p>
                <p v-if="error" class="error">{{ error }}</p>
            </form>
            <p>
                Van már fiókod?
                <router-link to="/login">Jelentkezz be</router-link>
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
                firstName: '',
                lastName: '',
                email: '',
                password: '',
                password_confirmation: '',
                userType: 'Tanuló'
            },
            loading: false,
            error: '',
            success: ''
        }
    },
    methods: {
        async handleRegister() {
            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'A jelszavak nem egyeznek'
                return
            }
            
            this.loading = true
            this.error = ''
            this.success = ''
            
            try {
                const response = await axios.post('/register', this.form)
                
                this.success = response.data.message
                this.form = {
                    firstName: '',
                    lastName: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    userType: 'Tanuló'
                }
                
            } catch (error) {
                this.error = error.response?.data?.message || 
                           error.response?.data?.errors || 
                           'Regisztrációs hiba'
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
/* Stílusok hasonlóak a Login komponenshez */
.success {
    color: #28a745;
    margin-top: 1rem;
}
</style>