import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import '@/assets/styles/main.scss'
import errorLogger from '@/utils/errorLogger'
import { initResizeObserverFix } from '@/utils/resizeObserverFix'

// Initialize ResizeObserver error fix
initResizeObserverFix()

const app = createApp(App)

// Set up Vue error handling
app.config.errorHandler = (err, instance, info) => {
  console.error('Vue Error:', err)
  console.error('Component Info:', info)
  if (window.vueErrorHandler) {
    window.vueErrorHandler(err, instance, info)
  }
}

// Register Element Plus icons
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

app.use(store)
app.use(router)
app.use(ElementPlus)
app.use(Toast, {
  transition: 'Vue-Toastification__bounce',
  maxToasts: 20,
  newestOnTop: true
})

// Initialize authentication
store.dispatch('auth/initializeAuth')

// Log app initialization
errorLogger.log('info', 'Vue App Initializing', {
  router: !!router,
  store: !!store,
  elementPlus: !!ElementPlus
})

app.mount('#app')

// Log successful mount
errorLogger.log('info', 'Vue App Mounted Successfully')