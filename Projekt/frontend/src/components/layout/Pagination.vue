<template>
  <div class="pagination-container">
    <div></div>

    <div class="pagination-controls">
      <button
        @click="emitPage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="btn-pagination"
      >
        Előző
      </button>

      <span class="pagination-info">
        {{ currentPage }} / {{ lastPage }} oldal
        <span v-if="total > 0">(összesen {{ total }} találat)</span>
      </span>

      <button
        @click="emitPage(currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="btn-pagination"
      >
        Következő
      </button>
    </div>

    <div v-if="showPerPageSelector" class="per-page-selector">
      <label>
        Találatok oldalanként:
        <select :value="perPage" @change="emitPerPage">
          <option v-for="option in perPageOptions" :key="option" :value="option">
            {{ option }}
          </option>
        </select>
      </label>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
  },
  lastPage: {
    type: Number,
    required: true,
  },
  total: {
    type: Number,
    default: 0,
  },
  perPage: {
    type: Number,
    default: 25,
    
  },
  perPageOptions: {
    type: Array,
    default: () => [10, 25, 50, 100],
  },
  showPerPageSelector: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:page', 'update:perPage']);

function emitPage(page) {
  if (page < 1 || page > props.lastPage) return;
  emit('update:page', page);
}

function emitPerPage(event) {
  const newPerPage = parseInt(event.target.value, 10);
  emit('update:perPage', newPerPage);
}
</script>

<style scoped>
.pagination-container {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination-controls {
  grid-column: 2;
  display: flex;
  align-items: center;
  gap: 2rem;
  justify-content: center;
}

.btn-pagination {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
}

.btn-pagination:hover:not(:disabled) {
  background: #f0a24a;
  color: white;
  border-color: #f0a24a;
}

.btn-pagination:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: #666;
  font-size: 0.85rem;
}

.per-page-selector {
  grid-column: 3;
  justify-self: end;
}

.per-page-selector label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #666;
  font-size: 0.8rem;
}

.per-page-selector select {
  padding: 0.25rem 0.5rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
  cursor: pointer;
}


@media (max-width: 768px) {
  .pagination-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .pagination-controls {
    order: 1;
  }

  .per-page-selector {
    order: 2;
    justify-self: auto;
  }
}
</style>