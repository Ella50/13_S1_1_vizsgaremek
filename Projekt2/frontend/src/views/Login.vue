<template>

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