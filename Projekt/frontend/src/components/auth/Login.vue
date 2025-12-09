<template>
  <AuthLayout>
    <h2 class="title">Bejelentkezés</h2>

    <form @submit.prevent="handleLogin" class="login-form">
      <input type="email" v-model="form.email" placeholder="Email" required>
      <input type="password" v-model="form.password" placeholder="Jelszó" required>

      <a class="forgot" href="#">Elfelejtett jelszó</a>

      <button type="submit" :disabled="loading">{{ loading ? 'Bejelentkezés...' : 'Bejelentkezés' }}</button>

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
      form: { email: '', password: '' },
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
        const token = response.data.token || response.data.access_token || response.data.accessToken
        if (!token) throw new Error('No token received')
        localStorage.setItem('auth_token', token)
        if (response.data.user) localStorage.setItem('user', JSON.stringify(response.data.user))
        this.$router.push('/dashboard')
      } catch (err) {
        this.error = err.response?.data?.message || 'Bejelentkezési hiba'
      } finally { this.loading = false }
    }
  }
}
</script>
