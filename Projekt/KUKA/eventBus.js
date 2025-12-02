import { ref } from 'vue'

const events = ref({})

const eventBus = {
  on(event, callback) {
    if (!events.value[event]) {
      events.value[event] = []
    }
    events.value[event].push(callback)
  },
  
  emit(event, data) {
    if (events.value[event]) {
      events.value[event].forEach(callback => callback(data))
    }
  },
  
  off(event, callback) {
    if (events.value[event]) {
      const index = events.value[event].indexOf(callback)
      if (index > -1) {
        events.value[event].splice(index, 1)
      }
    }
  }
}

export default eventBus