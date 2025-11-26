import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import axios from 'axios'

// Bootstrap-Vue 3 import
import BootstrapVue3 from 'bootstrap-vue-3'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-3/dist/bootstrap-vue-3.css'

// Axios alapbeállítások
axios.defaults.baseURL = 'http://localhost:8000/api'

// Request interceptor - token hozzáadása
axios.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor - token lejárat kezelése
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

const app = createApp(App)

// Axios globális beállítása
app.config.globalProperties.$http = axios

// Bootstrap-Vue 3 használata
app.use(BootstrapVue3)
app.use(router)
app.mount('#app')