import { createRouter, createWebHistory } from 'vue-router'
import StarterPage from '@/views/StarterPage.vue'
import Home from '@/views/Home.vue'
import Contact from '@/views/Contact.vue'
import Users from '@/views/Users.vue'
import App from '@/App.vue'


const routes = [
  /*{
    path: '/',
    name: 'Login',
    component: Login
  },*/
  {
    path: '/app',
    name: 'App',
    component: App,
    meta: { requiresAuth: true }
  },
  {
    path: '/',
    name: 'StarterPage',
    component: StarterPage
  },
  {
    path: '/home',
    name: 'Home',
    component: Home,
     meta: { requiresAuth: true }
  },
  {
    path: '/contact',
    name: 'Contact',
    component: Contact
  },
    {
    path: '/users',
    name: 'Users',
    component: Users,
    meta: { requiresAuth: true }
  },
 
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Route guard - csak bejelentkezett felhasználók
router.beforeEach((to, from, next) => {
  const isAuthenticated = !!localStorage.getItem('authToken')
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/')
  } else {
    next()
  }
})
export default router