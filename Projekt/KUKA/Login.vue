<template>
  <div class="login-container">
    <div class="login-form">
      <h2>Bejelentkezés</h2>
      
      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="email" class="form-label">Email cím</label>
          <input
            type="email"
            class="form-control"
            id="email"
            v-model="loginForm.email"
            required
            placeholder="email@example.com"
            :class="{ 'is-invalid': errors.email }"
          />
          <div v-if="errors.email" class="invalid-feedback">
            {{ errors.email }}
          </div>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Jelszó</label>
          <input
            type="password"
            class="form-control"
            id="password"
            v-model="loginForm.password"
            required
            placeholder="••••••••"
            :class="{ 'is-invalid': errors.password }"
          />
          <div v-if="errors.password" class="invalid-feedback">
            {{ errors.password }}
          </div>
        </div>

        <button 
          type="submit" 
          class="btn btn-primary w-100" 
          :disabled="loading"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          {{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}
        </button>

        <div v-if="message" class="alert mt-3" :class="messageType === 'success' ? 'alert-success' : 'alert-danger'">
          {{ message }}
        </div>
      </form>

      <div class="register-link mt-3 pt-3 border-top">
        <p class="text-center mb-0">
          Nincs még fiókja? <router-link to="/register" class="text-decoration-none">Regisztráljon itt!</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthService from '@/services/AuthService'

export default {
  name: 'LoginView',
  setup() {
    const router = useRouter()
    
    const loginForm = ref({
      email: '',
      password: ''
    })
    const loading = ref(false)
    const message = ref('')
    const messageType = ref('error')
    const errors = ref({})

    const handleLogin = async () => {
      loading.value = true
      message.value = ''
      errors.value = {}

      try {
        const response = await AuthService.login(loginForm.value)
        
        message.value = 'Sikeres bejelentkezés!'
        messageType.value = 'success'
        
        setTimeout(() => {
          router.push('/dashboard')
        }, 1000)
        
      } catch (error) {
        console.error('Login error:', error)
        
        if (error.errors) {
          errors.value = error.errors
        }
        
        message.value = error.message || 'Hiba történt a bejelentkezés során'
        messageType.value = 'error'
      } finally {
        loading.value = false
      }
    }

    onMounted(() => {
      if (AuthService.isAuthenticated()) {
        router.push('/dashboard')
      }
    })

    return {
      loginForm,
      loading,
      message,
      messageType,
      errors,
      handleLogin
    }
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.login-form {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
}
</style>