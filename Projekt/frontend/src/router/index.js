import { createRouter, createWebHistory } from 'vue-router'
import Login from '@/views/StarterPage.vue'
import Home from '@/views/Home.vue'
import Contact from '@/views/Contact.vue'
import Users from '@/views/Users.vue'
import App from '@/App.vue'


const routes = [
  {
    path: '/',
    name: 'Login',
    component: Login
  },
  {
    path: '/app',
    name: 'App',
    component: App
  },
  {
    path: '/home',
    name: 'Home',
    component: Home
  },
  {
    path: '/contact',
    name: 'Contact',
    component: Contact
  },
    {
    path: '/users',
    name: 'Users',
    component: Users 
  },
 
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router