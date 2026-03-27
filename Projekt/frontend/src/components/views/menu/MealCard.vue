<template>
  <div class="meal-card">
    <div class="meal-header">
      <h3 class="meal-title">{{ meal?.mealName || 'Étel' }}</h3>
    </div>
    <p v-if="meal?.description" class="meal-description">{{ meal.description }}</p>
    <div v-if="meal?.allergens?.length" class="meal-allergens">
      <span class="allergen-label">Allergének:</span>
      <div class="allergen-icons">
        <img 
          v-for="allergen in meal.allergens.slice(0, 4)" 
          :key="allergen.id"
          :src="getAllergenIconUrl(allergen.icon)" 
          :alt="allergen.allergenName"
          class="allergen-icon"
          :title="allergen.allergenName"
          @error="handleImageError"
        />
        <span v-if="meal.allergens.length > 4" class="more-allergens">
          +{{ meal.allergens.length - 4 }}
        </span>
      </div>
    </div>
    <div v-else class="allergen-free">
      <span>✓ Allergénmentes</span>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    meal: { type: Object, default: null }
  },
  methods: {
    getAllergenIconUrl(iconPath) {
      if (!iconPath) return null;
      const cleanPath = iconPath.replace(/^\//, '');
      const baseUrl = 'http://localhost:8000';
      return `${baseUrl}/images/allergens/${cleanPath.split('/').pop()}`;
    },
    handleImageError(event) {
      event.target.style.display = 'none';
    }
  }
}
</script>

<style scoped>
.meal-card {
  background: white;
  border: 1px solid #eee;
  border-radius: 16px;
  padding: 1.25rem;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.meal-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
  border-color: #f0a24a;
}

.meal-header {
  margin-bottom: 0.75rem;
}

.meal-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #8a1212;
}

.meal-description {
  margin: 0 0 1rem 0;
  color: #666;
  font-size: 0.85rem;
  line-height: 1.4;
}

.meal-allergens {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #f0f0f0;
}

.allergen-label {
  font-size: 0.7rem;
  font-weight: 500;
  color: #888;
  text-transform: uppercase;
}

.allergen-icons {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.allergen-icon {
  width: 24px;
  height: 24px;
  object-fit: contain;
  transition: transform 0.2s;
  cursor: pointer;
}

.allergen-icon:hover {
  transform: scale(1.1);
}

.more-allergens {
  font-size: 0.7rem;
  color: #888;
  background: #f8f9fa;
  padding: 0.15rem 0.4rem;
  border-radius: 12px;
}

.allergen-free {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #f0f0f0;
  font-size: 0.7rem;
  color: #2e7d32;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

/* Responsive */
@media (max-width: 480px) {
  .meal-card {
    padding: 1rem;
  }
  
  .meal-title {
    font-size: 1rem;
  }
  
  .meal-description {
    font-size: 0.8rem;
  }
  
  .allergen-icon {
    width: 20px;
    height: 20px;
  }
}
</style>