import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import HomePage from '../views/HomPage.vue'

const routes = [
  {
    path: '/',
    name: 'login',
    component: Login
  },
  {
    path: '/home',
    name: 'home',
    component: HomePage
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
