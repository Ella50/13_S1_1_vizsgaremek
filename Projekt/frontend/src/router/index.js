import { createRouter, createWebHistory } from 'vue-router'
import Home from '@/views/Home.vue'
import Contact from '@/views/Contact.vue'
import Users from '@/views/Users.vue'

const routes = [
  {
    path: '/',
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
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router