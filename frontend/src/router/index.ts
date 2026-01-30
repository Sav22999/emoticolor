import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import LoginView from '@/views/account/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import SignupView from '@/views/account/SignupView.vue'
import ConfirmLoginView from '@/views/account/ConfirmLoginView.vue'
import ConfirmSignupView from '@/views/account/ConfirmSignupView.vue'
import ResetPasswordView from '@/views/account/ResetPasswordView.vue'
import ConfirmResetPasswordView from '@/views/account/ConfirmResetPasswordView.vue'
import ResetPasswordNewPasswordView from '@/views/account/ResetPasswordNewPasswordView.vue'
import apiService from '@/utils/api/api-service.ts'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    /*{
      path: '/',
      redirect: '/splash',
    },*/
    {
      path: '/home',
      name: 'home',
      component: HomeView,
      meta: { title: '' },
    },
    {
      path: '/account/login',
      name: 'login',
      component: LoginView,
      meta: { title: 'Accedi' },
    },
    {
      path: '/account/login/verify',
      name: 'login-verify',
      component: ConfirmLoginView,
      meta: { title: 'Verifica accesso' },
    },
    {
      path: '/account/signup',
      name: 'signup',
      component: SignupView,
      meta: { title: 'Nuovo account' },
    },
    {
      path: '/account/signup/verify',
      name: 'signup-verify',
      component: ConfirmSignupView,
      meta: { title: 'Verifica account' },
    },
    {
      path: '/account/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { title: 'Ripristina password' },
    },
    {
      path: '/account/reset-password/verify',
      name: 'reset-password-verify',
      component: ConfirmResetPasswordView,
      meta: { title: 'Verifica ripristino password' },
    },
    {
      path: '/account/reset-password/set-new',
      name: 'reset-password-set-new',
      component: ResetPasswordNewPasswordView,
      meta: { title: 'Imposta nuova password' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: NotFoundView,
      meta: { title: 'Page Not Found – 404' },
    },
  ],
})

router.beforeEach((to, from, next) => {
  if (
    to.name !== 'login' &&
    to.name !== 'login-verify' &&
    to.name !== 'signup' &&
    to.name !== 'signup-verify' &&
    to.name !== 'reset-password' &&
    to.name !== 'reset-password-verify' &&
    to.name !== 'reset-password-set-new' &&
    to.name !== 'not-found'
  ) {
    const loginId = localStorage.getItem('login-id')
    const refreshId = localStorage.getItem('token-id')
    if (loginId && refreshId) {
      apiService
        .checkLoginIdValid(loginId)
        .then((isLoggedIn) => {
          console.log(isLoggedIn)
          if (isLoggedIn && (isLoggedIn.status === 204 || isLoggedIn.status === 200)) {
            next()
          } else if (isLoggedIn && isLoggedIn.status === 440) {
            // Session expired, try to refresh
            console.log('Login ID expired, refreshing...', loginId)
            return apiService.refreshLoginId(loginId, refreshId).then((refreshResponse) => {
              if (refreshResponse && refreshResponse.status === 200 && refreshResponse.data) {
                // Save new login-id and token-id
                localStorage.setItem('login-id', refreshResponse.data['login-id'])
                console.log('Login ID refreshed', refreshResponse.data['login-id'])
                next()
              } else {
                // Refresh failed, redirect to login and clear storage
                console.log('Login ID refresh failed')
                localStorage.removeItem('login-id')
                localStorage.removeItem('token-id')
                next({ name: 'login', query: { 'session-expired': 'true' } })
              }
            })
          } else {
            next({ name: 'login' })
          }
        })
        .catch((error) => {
          console.error('Error checking login ID validity:', error)
          next({ name: 'login' })
        })
    } else {
      next({ name: 'login' })
    }
  } else {
    next()
  }
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - Emoticolor` : 'Emoticolor'
})

export default router
