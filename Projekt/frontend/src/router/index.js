import { createRouter, createWebHistory } from 'vue-router'
import Login from '../components/auth/Login.vue'
import Register from '../components/auth/Register.vue'
import Dashboard from '../components/views/Dashboard.vue'
import Users from '../components/views/admin/Users.vue'
import TodayMenu from '../components/views/menu/TodayMenu.vue'
import WeeklyMenu from '../components/views/menu/WeeklyMenu.vue'
import Meals from '../components/views/kitchen/Meals.vue'
import ResetPassword from '../components/auth/ResetPassword.vue'
import PasswordResetForm from '../components/auth/PasswordResetForm.vue'
import MenuMaker from '../components/views/kitchen/MenuMaker.vue'
import Ingredients from '../components/views/kitchen/Ingredients.vue'



const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { requiresAuth: false }
    },
    {
        path: '/register',
        name: 'register',
        component: Register,
        meta: { requiresAuth: false }
    },
   /* {
        path: '/forgotpassword',
        name: 'forgotpassword',
        component: ResetPassword,
        meta: { guestOnly: true }
    },
    {
        path: '/resetpassword/:token?',
        name: 'resetpassword',
        component: PasswordResetForm,
        meta: { guestOnly: true }
    },*/
    {
        path: '/dashboard',
        name: 'dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },

    {
         path: '/admin/users',
        name: 'AdminUsers',
        component: Users,
        meta: { requiresAuth: true }
    },
    {
        path: '/menu/today',
        name: 'TodayMenu',
        component: TodayMenu,
        meta: { requiresAuth: true }
    },
    {
        path: '/menu/week',
        name: 'WeeklyMenu',
        component: WeeklyMenu,
        meta: { requiresAuth: true }
    },
    {
        path: '/kitchen/meals',
        name: 'KitchenMeals',
        component: Meals,
        meta: { requiresAuth: true }
    },
    {
        path: '/kitchen/menu-maker',
        name: 'MenuMaker',
        component: MenuMaker,
        meta: { requiresAuth: true }
    },
    {
        path: '/kitchen/ingredients',
        name: 'Ingredients',
        component: Ingredients,
        meta: { requiresAuth: true }
    },
    
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('auth_token')
  
  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
  } else if (!to.meta.requiresAuth && isAuthenticated && 
             (to.path === '/login' || to.path === '/register')) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router