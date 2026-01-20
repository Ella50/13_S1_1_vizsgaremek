<template>
  <div class="meal-card">
    <h3>{{ meal?.name || 'Étel' }}</h3>
    <p>{{ meal?.description || '' }}</p>
  </div>
</template>

<script>
export default {
  props: {
    meal: {
      type: Object,
      default: null
    }
  }
}
</script>
