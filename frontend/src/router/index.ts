import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/account/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import SignupView from '@/views/account/SignupView.vue'
import ConfirmLoginView from '@/views/account/ConfirmLoginView.vue'
import ConfirmSignupView from '@/views/account/ConfirmSignupView.vue'
import ResetPasswordView from '@/views/account/ResetPasswordView.vue'
import ConfirmResetPasswordView from '@/views/account/ConfirmResetPasswordView.vue'
import ResetPasswordNewPasswordView from '@/views/account/ResetPasswordNewPasswordView.vue'
import apiService from '@/utils/api/api-service.ts'
import type { ApiLoginIdResponse } from '@/utils/api/api-interface.ts'
import NotificationsView from '@/views/NotificationsView.vue'
import SearchView from '@/views/SearchView.vue'
import ProfileView from '@/views/ProfileView.vue'
import LearningView from '@/views/LearningView.vue'
import SplashScreen from '@/views/SplashScreen.vue'
import usefulFunctions from '@/utils/useful-functions.ts'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/splash',
    },
    {
      path: '/splash',
      name: 'splash',
      component: SplashScreen,
    },
    {
      path: '/home',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/users-emotions-followed',
      name: 'users-emotions-followed',
      component: () => import('@/views/UsersEmotionsFollowed.vue'),
    },
    {
      path: '/profile/',
      children: [
        { path: '', name: 'profile', component: ProfileView },
        {
          path: ':username',
          component: ProfileView,
        },
      ],
    },
    {
      path: '/learning',
      name: 'learning',
      component: LearningView,
      meta: { title: 'Impara' },
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
      path: '/notifications',
      name: 'notifications',
      component: NotificationsView,
      meta: { title: 'Notifiche' },
    },
    {
      path: '/search',
      name: 'search',
      component: SearchView,
      meta: { title: 'Ricerca' },
    },
    {
      path: '/no-internet-connection',
      name: 'no-internet-connection',
      component: () => import('@/views/NoInternetConnection.vue'),
      meta: { title: 'Nessuna connessione a Internet' },
    },
    {
      path: '/new-post',
      name: 'create-post',
      component: () => import('@/views/NewPostView.vue'),
      meta: { title: 'Nuovo stato emotivo' },
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
  if (to.name === 'login' || to.name === 'signup' || to.name === 'splash') {
    const loginId = localStorage.getItem('login-id')
    const refreshId = localStorage.getItem('token-id')
    if (loginId && refreshId) {
      // Already logged in, redirect to home
      next({ name: 'home' })
      return
    } else {
      // remove login-id and token-id from local storage if they exist
      usefulFunctions.removeFromLocalStorage('login-id')
      usefulFunctions.removeFromLocalStorage('token-id')
      next()
    }
  }
  if (
    to.name !== 'login' &&
    to.name !== 'login-verify' &&
    to.name !== 'signup' &&
    to.name !== 'signup-verify' &&
    to.name !== 'reset-password' &&
    to.name !== 'reset-password-verify' &&
    to.name !== 'reset-password-set-new' &&
    to.name !== 'not-found' &&
    to.name !== 'splash' &&
    to.name !== 'no-internet-connection'
  ) {
    const loginId = localStorage.getItem('login-id')
    const refreshId = localStorage.getItem('token-id')
    if (loginId && refreshId) {
      apiService
        .checkLoginIdValid(loginId)
        .then((isLoggedIn) => {
          if (isLoggedIn && (isLoggedIn.status === 204 || isLoggedIn.status === 200)) {
            next()
          } else if (isLoggedIn && isLoggedIn.status === 440) {
            // Session expired, try to refresh
            return apiService.refreshLoginId(loginId, refreshId).then((refreshResponse) => {
              if (
                refreshResponse &&
                refreshResponse.status === 200 &&
                'data' in refreshResponse &&
                refreshResponse.data &&
                refreshResponse.data['login-id']
              ) {
                // Save new login-id
                const res = refreshResponse as ApiLoginIdResponse
                usefulFunctions.saveToLocalStorage('login-id', res.data['login-id'])
                next()
              } else {
                // Refresh failed, redirect to login and clear storage
                usefulFunctions.removeFromLocalStorage('login-id')
                usefulFunctions.removeFromLocalStorage('token-id')
                setTimeout(() => {
                  next({ name: 'login', query: { 'session-expired': 'true' } })
                }, 500)
              }
            })
          } else {
            next({ name: 'login' })
          }
        })
        .catch((error) => {
          if (error instanceof TypeError && error.message.includes('NetworkError')) {
            next({ name: 'no-internet-connection' })
          } else {
            console.error('Error checking login ID validity:', error)
            next({ name: 'login' })
          }
        })
    } else {
      // remove login-id and token-id from local storage if they exist
      usefulFunctions.removeFromLocalStorage('login-id')
      usefulFunctions.removeFromLocalStorage('token-id')
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
