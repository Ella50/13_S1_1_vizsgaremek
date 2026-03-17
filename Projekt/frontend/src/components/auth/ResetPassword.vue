<template>
    <AuthLayout>
        <div >
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Jelszó visszaállítása</h2>
            
            <!-- Sikeres üzenet -->
            <div v-if="successMessage" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                ✅ {{ successMessage }}
            </div>
            
            <!-- Hiba üzenet -->
            <div v-if="error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                ❌ {{ error }}
            </div>
            
            <!-- Email form (csak ha még nem küldtük el) -->
            <div v-if="!emailSent">
                <p class="mb-4 text-gray-600">Add meg az email címed, és küldünk egy visszaállítási linket.</p>
                
                <form @submit.prevent="handleResetPassword">
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 mb-2">Email cím</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="email@pelda.hu"
                            :disabled="loading"
                        >
                    </div>
                    
                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="loading">Küldés...</span>
                        <span v-else>Link küldése</span>
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <router-link to="/login" class="text-blue-600 hover:underline">
                        Vissza a bejelentkezéshez
                    </router-link>
                </div>
            </div>
            
            <!-- Ha már elküldtük -->
            <div v-else class="text-center py-8">
                <div class="text-green-600 text-5xl mb-4">✓</div>
                <h3 class="text-xl font-semibold mb-2">Email elküldve!</h3>
                <p class="text-gray-600 mb-4">Ellenőrizd az emailed a további utasításokért.</p>
                <p class="text-sm text-gray-500 mb-6">
                    Ha nem találod az emailt, nézd meg a spam mappában is.
                </p>
                <button
                    @click="resetForm"
                    class="bg-blue-600 text-white py-2 px-6 rounded hover:bg-blue-700 transition"
                >
                    Új email küldése
                </button>
            </div>
        </div>
    </AuthLayout>
</template>

<script>
import AuthLayout from './AuthLayout.vue'
import axios from 'axios'

export default {
    components: { AuthLayout },
    data() {
        return {
            form: {
                email: ''
            },
            loading: false,
            error: '',
            successMessage: '',
            emailSent: false
        }
    },
    methods: {
        async handleResetPassword() {
    if (!this.form.email) {
        this.error = 'Kérjük, add meg az email címed!'
        return
    }
    
    this.loading = true
    this.error = ''
    
    try {
        const response = await axios.post('/forgot-password', { 
            email: this.form.email
        })
        
        // BOM kezelés
        let data = response.data
        if (typeof data === 'string') {
            if (data.charCodeAt(0) === 0xFEFF || data.charCodeAt(0) === 65279) {
                data = data.slice(1)
                data = JSON.parse(data)
            }
        }
        
        if (data.success) {
            this.successMessage = data.message
            this.emailSent = true
        } else {
            this.error = data.message || 'Ismeretlen hiba'
        }
        
    } catch (err) {
        console.error('Hiba:', err)
        this.error = err.response?.data?.message || 'Hálózati hiba'
    } finally {
        this.loading = false
    }
}
    }
}
</script>