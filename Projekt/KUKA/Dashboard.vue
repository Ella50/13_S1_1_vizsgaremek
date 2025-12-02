<template>
  <div class="dashboard">
    <header class="dashboard-header">
      <h1>Üdvözöljük, {{ user.firstName }}!</h1>
      <button @click="handleLogout" class="logout-btn">Kijelentkezés</button>
    </header>
    
    <main class="dashboard-content">
      <div class="user-info">
        <h2>Felhasználói adatok</h2>
        <p><strong>Név:</strong> {{ user.lastName }} {{ user.firstName }}</p>
        <p><strong>Email:</strong> {{ user.email }}</p>
        <p><strong>Felhasználó típus:</strong> {{ user.userType }}</p>
        <p><strong>Státusz:</strong> {{ user.status }}</p>
      </div>
    </main>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthService from '@/services/AuthService'

export default {
  name: 'DashboardView',
  setup() {
    const router = useRouter()
    const user = ref({})

    const loadUserData = () => {
      const userData = AuthService.getUser()
      if (userData) {
        user.value = userData
      }
    }

    const handleLogout = async () => {
      try {
        await AuthService.logout()
        router.push('/login')
      } catch (error) {
        console.error('Logout error:', error)
      }
    }

    onMounted(() => {
      loadUserData()
    })

    return {
      user,
      handleLogout
    }
  }
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #f5f5f5;
}

.dashboard-header {
  background: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.dashboard-header h1 {
  margin: 0;
  color: #333;
}

.logout-btn {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 5px;
  cursor: pointer;
}

.logout-btn:hover {
  background: #c0392b;
}

.dashboard-content {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.user-info {
  background: white;
  padding: 2rem;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.user-info h2 {
  margin-top: 0;
  color: #333;
}

.user-info p {
  margin: 0.5rem 0;
  font-size: 1.1rem;
}
</style>