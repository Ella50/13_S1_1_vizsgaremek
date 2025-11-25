/*import { createApp } from 'vue'
import App from './App.vue'
import router from './router'*/

import { createApp } from 'vue'
import Login from './views/StarterPage.vue'
import router from './router'

//boostraimport

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-3/dist/bootstrap-vue-3.css'

import BootstrapVue3 from 'bootstrap-vue-3'

const login = createApp(Login)
login.use(router)
login.mount('#login')

