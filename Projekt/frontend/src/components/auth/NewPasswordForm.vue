<template>
    <div class="container">
        <h2>Új jelszó beállítása</h2>
        
        <div v-if="successMessage" class="success">
            ✅ {{ successMessage }}
        </div>
        
        <div v-if="error" class="error">
            ❌ {{ error }}
        </div>
        
        <form @submit.prevent="handleResetPassword">
            <input type="hidden" v-model="form.token">
            <input type="hidden" v-model="form.email">
            
            <div class="form-group">
                <label>Új jelszó</label>
                <input 
                    type="password" 
                    v-model="form.password" 
                    required 
                    minlength="8"
                    class="form-control"
                >
            </div>
            
            <div class="form-group">
                <label>Jelszó megerősítése</label>
                <input 
                    type="password" 
                    v-model="form.password_confirmation" 
                    required 
                    minlength="8"
                    class="form-control"
                >
            </div>
            
            <button type="submit" :disabled="loading" class="btn-primary">
                {{ loading ? 'Feldolgozás...' : 'Jelszó megváltoztatása' }}
            </button>
        </form>
    </div>
</template>

<script>
import axios from 'axios'

export default {
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
    
    methods: {
        async handleResetPassword() {
            // 1. Először validáljunk frontenden
            if (!this.form.password || !this.form.password_confirmation) {
                this.error = 'Minden mező kitöltése kötelező!'
                return
            }
            
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
                console.log('Küldött adatok:', this.form)
                
                const response = await axios.post('/reset-password', {
                    token: this.form.token,
                    email: this.form.email,
                    password: this.form.password,
                    password_confirmation: this.form.password_confirmation
                })
                
                console.log('Válasz:', response.data)
                
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
                    // 3 másodperc múlva átirányítás
                    setTimeout(() => {
                        window.location.href = '/login'
                    }, 3000)
                } else {
                    this.error = data.message || 'Hiba történt'
                }
                
            } catch (err) {
                console.error('Reset password error:', err)
                
                // Validációs hibák részletes megjelenítése
                if (err.response?.status === 422) {
                    const errors = err.response.data.errors
                    if (errors) {
                        // Összegyűjtjük az összes hibaüzenetet
                        const errorMessages = []
                        for (let field in errors) {
                            errorMessages.push(errors[field][0])
                        }
                        this.error = errorMessages.join(', ')
                    } else {
                        this.error = err.response.data.message || 'Validációs hiba'
                    }
                } else {
                    this.error = err.response?.data?.message || 'Hálózati hiba'
                }
            } finally {
                this.loading = false
            }
        }
    }
}
</script>

<style scoped>
.container {
    max-width: 500px;
    margin: 50px auto;
    padding: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-control:focus {
    outline: none;
    border-color: #4CAF50;
}

.btn-primary {
    width: 100%;
    padding: 12px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-primary:hover {
    background: #45a049;
}

.btn-primary:disabled {
    background: #cccccc;
    cursor: not-allowed;
}

.error {
    background: #ffebee;
    color: #c62828;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #ffcdd2;
}

.success {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #c8e6c9;
}
</style>