<template>
  <div class="personal-orders">
    <h1>Személyes rendeléseim</h1>
    
    <div v-if="loading" class="loading">
      <p>Rendelés betöltése...</p>
    </div>
    
    <div v-else-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="loadOrders">Újra próbál</button>
    </div>
    
    <div v-else>
      <h2>Rendeléseim</h2>
      
      <div v-if="orders.length === 0" class="no-orders">
        <p>Nincs még rendelésed.</p>
      </div>
      
      <div v-else class="orders-list">
        <div v-for="order in orders" :key="order.id" class="order-item">
          <div class="order-header">
            <span class="order-date">{{ formatDate(order.orderDate) }}</span>
            <span :class="['order-status', order.orderStatus.toLowerCase()]">
              {{ order.orderStatus }}
            </span>
          </div>
          <div class="order-details">
            <div class="order-option">
              <strong>Opció:</strong> {{ getOptionName(order.selectedOption) }}
            </div>
            <div class="order-price">
              <strong>Ár:</strong> {{ order.price }} Ft
            </div>
            <div v-if="order.menuItem" class="order-menu">
              <strong>Menü:</strong> 
              <span v-if="order.selectedOption === 'A'">{{ order.menuItem.optionA?.mealName || '-' }}</span>
              <span v-if="order.selectedOption === 'B'">{{ order.menuItem.optionB?.mealName || '-' }}</span>
              <span v-if="order.selectedOption === 'soup'">{{ order.menuItem.soup?.mealName || '-' }}</span>
            </div>
          </div>
          <div v-if="order.orderStatus === 'Rendelve'" class="order-actions">
            <button @click="cancelOrder(order.id)" class="btn-cancel">Lemondás</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import AuthService from '@/services/authService'

export default {
  name: 'PersonalOrders',
  
  data() {
    return {
      loading: true,
      error: null,
      orders: []
    }
  },
  
  mounted() {
    this.loadOrders()
  },
  
  methods: {
    async loadOrders() {
      this.loading = true
      this.error = null
      
      try {
        const response = await AuthService.api.get('/user/personal-orders')
        this.orders = response.data.data?.orders || []
      } catch (err) {
        console.error('Hiba a rendelések betöltésekor:', err)
        this.error = 'Hiba történt a rendelések betöltésekor.'
      } finally {
        this.loading = false
      }
    },
    
    async cancelOrder(orderId) {
      if (!confirm('Biztosan le szeretnéd mondani ezt a rendelést?')) {
        return
      }
      
      try {
        await AuthService.api.delete(`/user/personal-orders/${orderId}`)
        alert('Rendelés sikeresen lemondva!')
        this.loadOrders() // Frissítjük a listát
      } catch (err) {
        console.error('Hiba a lemondás során:', err)
        alert('Hiba történt a lemondás során.')
      }
    },
    
    formatDate(dateString) {
      const date = new Date(dateString)
      return date.toLocaleDateString('hu-HU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        weekday: 'long'
      })
    },
    
    getOptionName(option) {
      const options = {
        'A': 'A opció',
        'B': 'B opció',
        'soup': 'Leves',
        'other': 'Egyéb'
      }
      return options[option] || option
    }
  }
}
</script>

<style scoped>
.personal-orders {
  padding: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.loading, .error {
  text-align: center;
  padding: 40px;
}

.error {
  color: #e74c3c;
}

.no-orders {
  text-align: center;
  padding: 40px;
  color: #7f8c8d;
}

.orders-list {
  margin-top: 20px;
}

.order-item {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  background: #f9f9f9;
}

.order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
}

.order-date {
  font-weight: bold;
  color: #2c3e50;
}

.order-status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.order-status.rendelve {
  background: #d4edda;
  color: #155724;
}

.order-status.lemondva {
  background: #f8d7da;
  color: #721c24;
}

.order-status.fizetve {
  background: #d1ecf1;
  color: #0c5460;
}

.order-details {
  margin-bottom: 15px;
}

.order-details div {
  margin-bottom: 5px;
}

.order-actions {
  text-align: right;
}

.btn-cancel {
  padding: 8px 16px;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-cancel:hover {
  background: #c0392b;
}
</style>