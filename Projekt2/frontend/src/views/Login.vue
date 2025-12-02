<template>
<div class="starter-page">
    <div class="login-container">
      <h1>Bejelentkezés</h1>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label>Email:</label>
          <input v-model="loginForm.email" type="email" placeholder="pelda@iskola.hu" required>
        </div>
        <div class="form-group">
          <label>Jelszó:</label>
          <input v-model="loginForm.password" type="password" placeholder="diak123" required>
        </div>
        <button type="submit" class="login-btn">Bejelentkezés</button>
      </form>
      
      <div v-if="error" class="error">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

axios.defaults.withCredentials = true;
axios.defaults.baseURL = "http://localhost:8000";

export async function login(email, password) {
  await axios.get('/sanctum/csrf-cookie');

  const response = await axios.post('/api/login', {
    email,
    password
  });

  localStorage.setItem('token', response.data.token);

  axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;

  return response.data.user;
}

</script>

<style scoped>

</style>