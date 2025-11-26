/*import { createApp } from 'vue'
import App from './App.vue'
import router from './router'*/

import { createApp } from 'vue'
import StarterPage from './views/StarterPage.vue'
import router from './router'
import eventBus from './eventBus'

//boostraimport

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-3/dist/bootstrap-vue-3.css'

import BootstrapVue3 from 'bootstrap-vue-3'

app.provide('emitter', eventBus)
const login = createApp(StarterPage)
login.use(router)
login.mount('#login')

