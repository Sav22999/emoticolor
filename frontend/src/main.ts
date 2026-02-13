import './assets/styles/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

const app = createApp(App)

app.use(router)

app.mount('#app')

// Check internet connection periodically and on events
const checkConnection = () => {
  if (!navigator.onLine) {
    if (router.currentRoute.value.name !== 'no-internet-connection') {
      router.push('/no-internet-connection')
    }
  } else {
    if (router.currentRoute.value.name === 'no-internet-connection') {
      router.go(-1) // Go back to previous route
    }
  }
}

// Listen for online/offline events
window.addEventListener('online', checkConnection)
window.addEventListener('offline', checkConnection)

// Periodic check every 5 seconds
setInterval(checkConnection, 5000)
