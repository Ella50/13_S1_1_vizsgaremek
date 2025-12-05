<template>
  <div class="login-container">
    <h1>Bejelentkezés</h1>
    
    <!-- A form submit eseménye a "login" metódust hívja meg -->
    <form @submit.prevent="login"> 
      <div class="input-group">
        <label for="email">Email</label>
        <!-- Visszakötve a "email" ref változóra -->
        <input
          id="email"
          type="email"
          v-model="email"
          placeholder="Add meg az email címed"
          required
        />
      </div>

      <div class="input-group">
        <label for="password">Jelszó</label>
        <!-- Visszakötve a "password" ref változóra -->
        <input
          id="password"
          type="password"
          v-model="password"
          placeholder="Add meg a jelszót"
          required
        />
      </div>

      <button type="submit">Bejelentkezés</button>
    </form>

    <!-- Hibaüzenet megjelenítése, ha van -->
    <div v-if="errorMessage" class="error">
      {{ errorMessage }}
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

// A router példányosítása a setup scope-on belül
const router = useRouter()

// Reakítv állapotok definiálása
const email = ref('')
const password = ref('')
const errorMessage = ref('') // Hozzáadva a hibaüzenet kezeléséhez

// A bejelentkezési logika egy függvényben
const login = async () => {
  try {
    // Használhatod a lokális hostot, ha szükséges, vagy relatív utat
    const response = await axios.post("http://localhost:8000/api/login", {
      email: email.value,
      password: password.value
    })

    // Token mentése és átirányítás
    localStorage.setItem("token", response.data.token)
    console.log("Login successful:", response.data)
    router.push('/dashboard') // Átirányítás a dashboard-ra

  } catch (error) {
    // Hiba esetén beállítjuk a hibaüzenetet
    console.error("Login failed:", error.response?.data ?? error)
    errorMessage.value = "Hibás email vagy jelszó! Kérlek ellenőrizd a bejelentkezési adatokat."
  }
}
</script>

<style scoped>

</style>