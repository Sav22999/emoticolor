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

// Disable default browser pull-to-refresh behavior
let touchStartY = 0
window.addEventListener(
  'touchstart',
  (e) => {
    touchStartY = e.touches[0].clientY
  },
  { passive: true },
)

window.addEventListener(
  'touchmove',
  (e) => {
    const touchY = e.touches[0].clientY
    const touchDiff = touchY - touchStartY

    // If the user is at the top of the page and scrolling down
    if (window.scrollY === 0 && touchDiff > 0) {
      if (e.cancelable) {
        e.preventDefault()
      }
    }
  },
  { passive: false },
)
