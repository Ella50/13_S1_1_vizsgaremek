<template>
  <AuthLayout>
    <h2 class="title">Regisztráció</h2>

    <form @submit.prevent="handleRegister" class="login-form">
      <input type="text" v-model="form.firstName" placeholder="Keresztnév" required>
      <input type="text" v-model="form.lastName" placeholder="Vezetéknév" required>
      <input type="email" v-model="form.email" placeholder="Email cím (iskolai)" required>
      <input type="password" v-model="form.password" placeholder="Jelszó (minimum 8 karakter)" required minlength="8">
      <input type="password" v-model="form.password_confirmation" placeholder="Jelszó megerősítése" required>

      <select v-model="form.userType" required>
          <option value="Tanuló">Tanuló</option>
          <option value="Tanár">Tanár</option>
          <option value="Dolgozó">Dolgozó</option>
      </select>

      <button type="submit" :disabled="loading">{{ loading ? 'Regisztrálás...' : 'Regisztrálás' }}</button>

      <p v-if="success" class="success">{{ success }}</p>
      <p v-if="error" class="error">{{ error }}</p>
    </form>

    <p class="register-text">
      Van már fiókod? <router-link to="/login">Jelentkezz be</router-link>
    </p>
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
        firstName: '', lastName: '', email: '',
        password: '', password_confirmation: '', userType: 'Tanuló'
      },
      loading: false, error: '', success: ''
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
        this.form = { firstName:'', lastName:'', email:'', password:'', password_confirmation:'', userType:'Tanuló' }
      } catch (err) {
        this.error = err.response?.data?.message || err.response?.data?.errors || 'Regisztrációs hiba'
      } finally { this.loading = false }
    }
  }
}
</script>
