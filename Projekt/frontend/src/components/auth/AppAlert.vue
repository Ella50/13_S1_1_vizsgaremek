<template>
  <div class="alert-container">
    <div
      v-for="(a, index) in alerts"
      :key="index"
      :class="['alert', a.type]"
    >
      <strong v-if="a.title">{{ a.title }}</strong>
      <div>{{ a.message }}</div>
    </div>
  </div>
</template>

<script>
import { reactive } from "vue";


const alerts = reactive([]);

export function addAlert({ message, type = "success", title = "" }) {
  const alert = { message, type, title };
  alerts.push(alert);

  setTimeout(() => {
    const i = alerts.indexOf(alert);
    if (i !== -1) alerts.splice(i, 1);
  }, 3000);
}
</script>

<style>
.alert-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
}

.alert {
  margin-bottom: 10px;
  padding: 14px 18px;
  border-radius: 12px;
  color: white;
  min-width: 250px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  animation: slideIn 0.3s ease;
}

.alert.success {
  background: #4CAF50;
}

.alert.error {
  background: #ff4d4f;
}

.alert.warning {
  background: #ff9800;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>