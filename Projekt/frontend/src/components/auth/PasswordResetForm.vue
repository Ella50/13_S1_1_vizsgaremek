<template>
    <AuthLayout title="Új jelszó beállítása">
        <div class="max-w-md mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Új jelszó</h2>
            
            <!-- Hiba üzenet -->
            <div v-if="error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                ❌ {{ error }}
            </div>
            
            <!-- Sikeres üzenet -->
            <div v-if="successMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                ✅ {{ successMessage }}
                <p class="mt-2">
                    <router-link to="/login" class="text-green-800 font-semibold hover:underline">
                        Jelentkezz be az új jelszavaddal
                    </router-link>
                </p>
            </div>
            
            <!-- Jelszó form (csak ha még nem sikerült) -->
            <form v-if="!successMessage" @submit.prevent="handleResetPassword">
                <input type="hidden" v-model="form.token">
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 mb-2">Email cím</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        readonly
                        class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100"
                    >
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 mb-2">Új jelszó</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        minlength="8"
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Minimum 8 karakter"
                        :disabled="loading"
                    >
                    <p class="text-sm text-gray-500 mt-1">Minimum 8 karakter</p>
                </div>
                
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-gray-700 mb-2">Jelszó megerősítése</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ismételd meg a jelszót"
                        :disabled="loading"
                    >
                </div>
                
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="loading">Feldolgozás...</span>
                    <span v-else>Jelszó mentése</span>
                </button>
            </form>
        </div>
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
                token: '',
                email: '',
                password: '',
                password_confirmation: ''
            },
            loading: false,
            error: '',
            successMessage: ''
        }
    },
    mounted() {
        // Token és email kinyerése URL-ből
        const urlParams = new URLSearchParams(window.location.search)
        this.form.token = urlParams.get('token') || this.$route.params.token
        this.form.email = urlParams.get('email') || ''
        
        if (!this.form.token) {
            this.error = 'Érvénytelen vagy hiányzó token.'
        }
    },
    methods: {
        async handleResetPassword() {
            // Validáció
            if (!this.form.password || this.form.password.length < 8) {
                this.error = 'A jelszónak minimum 8 karakter hosszúnak kell lennie.'
                return
            }
            
            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'A két jelszó nem egyezik.'
                return
            }
            
            this.loading = true
            this.error = ''

            try {
                console.log('🔐 Jelszó reset:', this.form.email)
                
                const response = await axios.post('/api/reset-password/confirm', this.form, {
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                
                console.log('✅ Backend válasz:', response.data)
                
                if (response.data.success) {
                    this.successMessage = response.data.message
                } else {
                    this.error = response.data.message || 'Ismeretlen hiba'
                }
                
            } catch (err) {
                console.error('❌ Hiba:', err)
                
                if (err.response) {
                    const status = err.response.status
                    const data = err.response.data
                    
                    if (status === 400 || status === 422) {
                        this.error = data.message || 'Érvénytelen adatok'
                    } else {
                        this.error = data.message || `Hiba (${status})`
                    }
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