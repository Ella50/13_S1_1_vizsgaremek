<template>
  <div class="password-wrapper" :class="customClass">
    <input 
      :type="showPassword ? 'text' : 'password'" 
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :required="required"
      :minlength="minlength"
      :maxlength="maxlength"
      :disabled="disabled"
      class="password-input"
    >
    <button 
      type="button" 
      class="password-toggle" 
      @click="togglePassword"
      :title="showPassword ? 'Jelszó elrejtése' : 'Jelszó megjelenítése'"
    >
      <svg 
        v-if="showPassword"
        class="toggle-icon"
        xmlns="http://www.w3.org/2000/svg" 
        viewBox="0 0 24 24" 
        fill="none" 
        stroke="currentColor" 
        stroke-width="2" 
        stroke-linecap="round" 
        stroke-linejoin="round"
      >
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
        <circle cx="12" cy="12" r="3"></circle>
      </svg>
      <svg 
        v-else
        class="toggle-icon"
        xmlns="http://www.w3.org/2000/svg" 
        viewBox="0 0 24 24" 
        fill="none" 
        stroke="currentColor" 
        stroke-width="2" 
        stroke-linecap="round" 
        stroke-linejoin="round"
      >
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
        <line x1="1" y1="1" x2="23" y2="23"></line>
      </svg>
    </button>
  </div>
</template>

<script>
export default {
  name: 'PasswordInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: 'Jelszó'
    },
    required: {
      type: Boolean,
      default: false
    },
    minlength: {
      type: Number,
      default: null
    },
    maxlength: {
      type: Number,
      default: null
    },
    disabled: {
      type: Boolean,
      default: false
    },
    customClass: {
      type: String,
      default: ''
    },
    error: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      showPassword: false
    }
  },
  methods: {
    togglePassword() {
      this.showPassword = !this.showPassword
    }
  }
}
</script>

<style scoped>
/* Alap stílus (auth oldalak) */
.password-wrapper {
  position: relative;
  width: 100%;
}

.password-wrapper .password-input {
  width: 100%;
  padding: 0.8rem;
  padding-right: 45px;
  border: 2px solid #555;
  border-radius: 25px;
  font-size: 1rem;
  box-sizing: border-box;
  transition: all 0.2s;
}

.password-wrapper.profile-style .password-input:focus {
  outline: none;
  border-color: #f0a24a;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2);
}

.password-wrapper.profile-style .password-input {
  padding: 0.6rem 0.75rem !important;
  padding-right: 40px !important;
  border: 1px solid #ddd !important;
  border-radius: 8px !important;
  font-size: 0.85rem !important;
  background: white !important;
}

.password-wrapper.profile-style .password-input:focus {
  border-color: #f0a24a !important;
  box-shadow: 0 0 0 2px rgba(240, 162, 74, 0.2) !important;
}

.password-wrapper.profile-style .password-input:disabled {
  background: #f8f9fa !important;
  color: #666 !important;
}

.password-wrapper.profile-style .password-toggle {
  right: 8px;
}

.password-wrapper.profile-style .toggle-icon {
  width: 18px;
  height: 18px;
}

.password-input:disabled {
  background: #f8f9fa;
  color: #666;
  cursor: not-allowed;
}

/* Böngésző beépített ikonjainak elrejtése */
.password-input[type="password"]::-ms-reveal,
.password-input[type="password"]::-ms-clear,
.password-input[type="password"]::-webkit-contacts-auto-fill-button,
.password-input[type="password"]::-webkit-credentials-auto-fill-button {
  display: none !important;
  visibility: hidden !important;
  opacity: 0 !important;
  pointer-events: none !important;
  width: 0 !important;
  height: 0 !important;
}

.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  margin: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  color: #666;
  opacity: 0.7;
  transition: all 0.2s;
  outline: none;
}

.password-toggle:hover {
  opacity: 1;
  color: #333;
}

.password-toggle:focus {
  outline: none;
}

.toggle-icon {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  fill: none;
  transition: all 0.2s;
  display: block;
  flex-shrink: 0;
}
</style>