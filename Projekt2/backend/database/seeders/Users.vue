<template>
  <div class="users-page">
    <div class="container">
      <h1>Felhasználók Kezelése</h1>
      <UserDisplay />
    </div>
  </div>
</template>

<script>
import UserDisplay from '@/components/UserDisplay.vue'

export default {
  name: 'Users',
  components: {
    UserDisplay
  }
}
</script>

<style scoped>
.users-page {
  padding: 2rem 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

h1 {
  color: #2c3e50;
  margin-bottom: 2rem;
  text-align: center;
}
</style>