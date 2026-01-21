<template>
  <div class="meal-card">
    <h3>{{ meal?.mealName || 'Étel' }}</h3>
    <p>{{ meal?.description || '' }}</p>
  </div>
</template>

<script>
export default {
  props: {
    meal: { type: Object, default: null }
  }
}
</script>

<style scoped>
.meal-card {
  padding: 1.5rem;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
}
.meal-card h3 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
}
.meal-card p {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.875rem;
}
</style>
