
<template>
    <AuthLayout>
        <div>
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Új jelszó beállítása</h2>
            
            <!-- Sikeres üzenet -->
            <div v-if="successMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                ✅ {{ successMessage }}
            </div>
            
            <!-- Hiba üzenet -->
            <div v-if="error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                ❌ {{ error }}
            </div>
            
            <!-- Jelszó változtató form -->
            <div v-if="!successMessage">
                <p class="mb-4 text-gray-600">
                    Adj meg egy új jelszót a fiókodhoz.
                </p>
                
                <form @submit.prevent="handleResetPassword">
                    <input type="hidden" v-model="form.token">
                    <input type="hidden" v-model="form.email">
                    
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 mb-2">Új jelszó</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            minlength="8"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Új jelszó"
                            :disabled="loading"
                        >
                        <p class="text-sm text-gray-500 mt-1">Minimum 8 karakter</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-gray-700 mb-2">Jelszó megerősítése</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            minlength="8"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Jelszó megerősítése"
                            :disabled="loading"
                        >
                    </div>
                    
                    <button
                        type="submit"
                        :disabled="loading || !isFormValid"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading">Feldolgozás...</span>
                        <span v-else>Jelszó megváltoztatása</span>
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <router-link to="/login" class="text-blue-600 hover:underline">
                        Vissza a bejelentkezéshez
                    </router-link>
                </div>
            </div>
            
            <!-- Sikeres visszaállítás után -->
            <div v-else class="text-center py-4">
                <router-link 
                    to="/login" 
                    class="inline-block bg-blue-600 text-white py-2 px-6 rounded hover:bg-blue-700 transition"
                >
                    Tovább a bejelentkezéshez
                </router-link>
            </div>
        </div>
    </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLayout.vue'
import axios from '../../axios'

export default {
    name: 'ResetPasswordForm',
    components: { AuthLayout },
    props: {
        token: {
            type: String,
            required: true
        },
        email: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            form: {
                token: this.token,
                email: decodeURIComponent(this.email || ''),
                password: '',
                password_confirmation: ''
            },
            loading: false,
            error: '',
            successMessage: ''
        }
    },
    computed: {
        isFormValid() {
            return this.form.password.length >= 8 && 
                   this.form.password === this.form.password_confirmation
        }
    },
    methods: {
        async handleResetPassword() {
            // Validáció
            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'A jelszavak nem egyeznek!'
                return
            }
            
            if (this.form.password.length < 8) {
                this.error = 'A jelszónak legalább 8 karakter hosszúnak kell lennie!'
                return
            }
            
            this.loading = true
            this.error = ''
            
            try {
                console.log('🔑 Jelszó visszaállítás:', this.form.email)
                
                const response = await axios.post('/reset-password', this.form, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                
                console.log('✅ Backend válasz:', response.data)
                
                if (response.data.success) {
                    this.successMessage = response.data.message
                    
                    // Űrlap ürítése
                    this.form.password = ''
                    this.form.password_confirmation = ''
                } else {
                    this.error = response.data.message || 'Ismeretlen hiba'
                }
                
            } catch (err) {
                console.error('❌ Hiba:', err)
                
                if (err.response) {
                    const status = err.response.status
                    const data = err.response.data
                    
                    if (status === 422) {
                        // Validációs hiba
                        if (data.errors) {
                            const firstError = Object.values(data.errors)[0]
                            this.error = Array.isArray(firstError) ? firstError[0] : firstError
                        } else {
                            this.error = data.message || 'Érvénytelen adatok'
                        }
                    } else if (status === 400) {
                        this.error = data.message || 'Érvénytelen token vagy email'
                    } else {
                        this.error = data.message || `Hiba történt (${status})`
                    }
                } else if (err.request) {
                    this.error = 'Nincs kapcsolat a szerverrel. Kérjük, próbáld újra később.'
                } else {
                    this.error = 'Váratlan hiba történt.'
                }
            } finally {
                this.loading = false
            }
        }
    }
}
</script>