<template>
  <div v-if="visible" class="confirm-overlay">
    <div class="confirm-box">
      
      <h3 v-if="title" class="confirm-title">{{ title }}</h3>
      
      <p class="confirm-message">{{ message }}</p>

      <div class="confirm-actions">
        <button class="btn-cancel" @click="cancel">Mégse</button>
        <button class="btn-ok" @click="ok">OK</button>
      </div>

    </div>
  </div>
</template>

<script>
import { ref } from "vue";

const visible = ref(false);
const message = ref("");
const title = ref("");
let resolver = null;

export function showConfirm({ message: msg, title: t = "" }) {
  message.value = msg;
  title.value = t;
  visible.value = true;

  return new Promise((resolve) => {
    resolver = resolve;
  });
}

function ok() {
  visible.value = false;
  resolver(true);
}

function cancel() {
  visible.value = false;
  resolver(false);
}

export default {
  setup() {
    return { visible, message, title, ok, cancel };
  }
};
</script>

<style>
.confirm-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

/* 🔥 ANIMÁCIÓ */
.confirm-box {
  background: white;
  padding: 24px;
  border-radius: 14px;
  min-width: 320px;
  text-align: center;
  box-shadow: 0 15px 40px rgba(0,0,0,0.25);

  animation: scaleIn 0.25s ease;
}

@keyframes scaleIn {
  from {
    transform: scale(0.6);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

/* ✨ SZÖVEG */
.confirm-title {
  margin-bottom: 10px;
}

.confirm-message {
  font-weight: bold;
  text-align: center;
  margin: 10px 0 20px 0;
}

/* 🎯 GOMBOK KÖZÉPRE */
.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
}

/* 🟥 MÉGSE */
.btn-cancel {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-cancel:hover {
  background: #c0392b;
}

/* 🟩 OK */
.btn-ok {
  background: #2ecc71;
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-ok:hover {
  background: #27ae60;
}
</style>