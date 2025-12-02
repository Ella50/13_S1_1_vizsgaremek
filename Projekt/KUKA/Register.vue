<template>
  <div class="register-container">
    <div class="register-form">
      <h2>Regisztráció</h2>
      
      <form @submit.prevent="handleRegister">
        <div class="form-row">
          <div class="form-group">
            <label for="firstName">Keresztnév *</label>
            <input
              type="text"
              id="firstName"
              v-model="registerForm.firstName"
              required
              placeholder="Keresztnév"
              :class="{ 'error': errors.firstName }"
            />
            <span v-if="errors.firstName" class="error-message">{{ errors.firstName[0] }}</span>
          </div>

          <div class="form-group">
            <label for="lastName">Vezetéknév *</label>
            <input
              type="text"
              id="lastName"
              v-model="registerForm.lastName"
              required
              placeholder="Vezetéknév"
              :class="{ 'error': errors.lastName }"
            />
            <span v-if="errors.lastName" class="error-message">{{ errors.lastName[0] }}</span>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email cím *</label>
          <input
            type="email"
            id="email"
            v-model="registerForm.email"
            required
            placeholder="email@example.com"
            :class="{ 'error': errors.email }"
          />
          <span v-if="errors.email" class="error-message">{{ errors.email[0] }}</span>
        </div>

        <div class="form-group">
          <label for="city_id">Város *</label>
          <select
            id="city_id"
            v-model="registerForm.city_id"
            required
            :class="{ 'error': errors.city_id }"
          >
            <option value="">Válassz várost</option>
            <option v-for="city in cities" :key="city.id" :value="city.id">
              {{ city.name }}
            </option>
          </select>
          <span v-if="errors.city_id" class="error-message">{{ errors.city_id[0] }}</span>
        </div>

        <div class="form-group">
          <label for="address">Cím *</label>
          <input
            type="text"
            id="address"
            v-model="registerForm.address"
            required
            placeholder="Teljes cím"
            :class="{ 'error': errors.address }"
          />
          <span v-if="errors.address" class="error-message">{{ errors.address[0] }}</span>
        </div>

        <div class="form-group">
          <label for="userType">Felhasználó típusa *</label>
          <select
            id="userType"
            v-model="registerForm.userType"
            required
            :class="{ 'error': errors.userType }"
          >
            <option value="">Válassz típust</option>
            <option value="Tanuló">Tanuló</option>
            <option value="Tanár">Tanár</option>
            <option value="Szülő">Szülő</option>
            <option value="Külsős">Külsős</option>
          </select>
          <span v-if="errors.userType" class="error-message">{{ errors.userType[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password">Jelszó *</label>
          <input
            type="password"
            id="password"
            v-model="registerForm.password"
            required
            placeholder="••••••••"
            :class="{ 'error': errors.password }"
          />
          <span v-if="errors.password" class="error-message">{{ errors.password[0] }}</span>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Jelszó megerősítése *</label>
          <input
            type="password"
            id="password_confirmation"
            v-model="registerForm.password_confirmation"
            required
            placeholder="••••••••"
          />
        </div>

        <button type="submit" :disabled="loading" class="register-btn">
          <span v-if="loading">Regisztráció...</span>
          <span v-else>Regisztráció</span>
        </button>

        <div v-if="message" class="message" :class="messageType">
          {{ message }}
        </div>
      </form>

      <div class="login-link">
        <p>Van már fiókja? <router-link to="/login">Jelentkezzen be itt!</router-link></p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthService from '@/services/AuthService'

export default {
  name: 'RegisterView',
  setup() {
    const router = useRouter()
    
    const registerForm = ref({
      firstName: '',
      lastName: '',
      email: '',
      password: '',
      password_confirmation: '',
      city_id: '',
      address: '',
      userType: ''
    })
    const cities = ref([])
    const loading = ref(false)
    const message = ref('')
    const messageType = ref('error')
    const errors = ref({})

    const loadCities = async () => {
      try {
        const response = await AuthService.api.get('/cities')
        cities.value = response.data.data
      } catch (error) {
        console.error('Error loading cities:', error)
      }
    }

    const handleRegister = async () => {
      loading.value = true
      message.value = ''
      errors.value = {}

      try {
        const response = await AuthService.register(registerForm.value)
        
        message.value = 'Sikeres regisztráció! Átirányítás...'
        messageType.value = 'success'
        
        setTimeout(() => {
          router.push('/dashboard')
        }, 2000)
        
      } catch (error) {
        console.error('Registration error:', error)
        
        if (error.errors) {
          errors.value = error.errors
        }
        
        message.value = error.message || 'Hiba történt a regisztráció során'
        messageType.value = 'error'
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      if (AuthService.isAuthenticated()) {
        router.push('/dashboard')
      }
      loadCities()
    })

    return {
      registerForm,
      cities,
      loading,
      message,
      messageType,
      errors,
      handleRegister
    }
  }
}
</script>

<style scoped>
.register-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.register-form {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.form-row .form-group {
  flex: 1;
}

.form-group {
  margin-bottom: 1rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
  font-weight: 500;
}

input, select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

input:focus, select:focus {
  outline: none;
  border-color: #667eea;
}

input.error, select.error {
  border-color: #e74c3c;
}

.error-message {
  color: #e74c3c;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.register-btn {
  width: 100%;
  padding: 0.75rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.3s;
  margin-top: 0.5rem;
}

.register-btn:hover:not(:disabled) {
  background: #5a6fd8;
}

.register-btn:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.message {
  margin-top: 1rem;
  padding: 0.75rem;
  border-radius: 5px;
  text-align: center;
}

.message.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.message.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.login-link {
  margin-top: 1.5rem;
  text-align: center;
  padding-top: 1rem;
  border-top: 1px solid #eee;
}

.login-link a {
  color: #667eea;
  text-decoration: none;
}

.login-link a:hover {
  text-decoration: underline;
}
</style>