import { createRouter, createWebHistory } from 'vue-router'
import apiService from '@/utils/api/api-service.ts'
import type { ApiLoginIdResponse } from '@/utils/api/api-interface.ts'
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
      component: () => import('@/views/SplashScreen.vue'),
    },
    {
      path: '/home',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/profile/followed',
      name: 'users-emotions-followed',
      component: () => import('@/views/UsersEmotionsFollowed.vue'),
    },
    {
      path: '/profile/',
      children: [
        { path: '', name: 'profile', component: () => import('@/views/ProfileView.vue') },
        {
          path: ':username',
          component: () => import('@/views/ProfileView.vue'),
          name: 'other-profile',
        },
      ],
    },
    {
      path: '/learning',
      name: 'learning',
      component: () => import('@/views/learning/LearningView.vue'),
      meta: { title: 'Impara' },
    },
    {
      path: '/learning/statistics',
      name: 'learning-statistics',
      component: () => import('@/views/learning/LearningStatisticsView.vue'),
      meta: { title: "Statistiche sull'apprendimento" },
    },
    {
      path: '/learning/emotion/',
      children: [
        {
          path: '',
          redirect: '/learning/',
        },
        {
          path: ':emotionId/',
          children: [
            {
              path: '',
              name: 'learning-emotion',
              component: () => import('@/views/learning/LearningEmotionView.vue'),
            },
            {
              path: 'pills',
              name: 'learning-emotion-pills',
              component: () => import('@/views/learning/LearningEmotionPillsView.vue'),
            },
            {
              path: 'path',
              name: 'learning-emotion-path',
              component: () => import('@/views/learning/LearningEmotionPathView.vue'),
            },
          ],
          meta: { title: "Impara l'emozione" },
        },
      ],
    },
    {
      path: '/account/login',
      name: 'login',
      component: () => import('@/views/account/LoginView.vue'),
      meta: { title: 'Accedi' },
    },
    {
      path: '/account/login/verify',
      name: 'login-verify',
      component: () => import('@/views/account/ConfirmLoginView.vue'),
      meta: { title: 'Verifica accesso' },
    },
    {
      path: '/account/signup',
      name: 'signup',
      component: () => import('@/views/account/SignupView.vue'),
      meta: { title: 'Nuovo account' },
    },
    {
      path: '/account/signup/verify',
      name: 'signup-verify',
      component: () => import('@/views/account/ConfirmSignupView.vue'),
      meta: { title: 'Verifica account' },
    },
    {
      path: '/account/reset-password',
      name: 'reset-password',
      component: () => import('@/views/account/ResetPasswordView.vue'),
      meta: { title: 'Ripristina password' },
    },
    {
      path: '/account/reset-password/verify',
      name: 'reset-password-verify',
      component: () => import('@/views/account/ConfirmResetPasswordView.vue'),
      meta: { title: 'Verifica ripristino password' },
    },
    {
      path: '/account/reset-password/set-new',
      name: 'reset-password-set-new',
      component: () => import('@/views/account/ResetPasswordNewPasswordView.vue'),
      meta: { title: 'Imposta nuova password' },
    },
    {
      path: '/notifications',
      name: 'notifications',
      component: () => import('@/views/NotificationsView.vue'),
      meta: { title: 'Notifiche' },
    },
    {
      path: '/search',
      name: 'search',
      component: () => import('@/views/SearchView.vue'),
      meta: { title: 'Ricerca' },
    },
    {
      path: '/no-internet-connection',
      name: 'no-internet-connection',
      component: () => import('@/views/NoInternetConnection.vue'),
      meta: { title: 'Nessuna connessione a Internet' },
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/SettingsView.vue'),
      meta: { title: 'Impostazioni' },
    },
    {
      path: '/new-post',
      name: 'create-post',
      component: () => import('@/views/NewPostView.vue'),
      meta: { title: 'Nuovo stato emotivo' },
    },
    {
      path: '/post/',
      children: [
        { path: '', redirect: '/home' },
        { path: ':postId', component: () => import('@/views/PostView.vue'), name: 'post' },
      ],
      meta: { title: 'Post' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/views/NotFoundView.vue'),
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
    to.name !== 'no-internet-connection' &&
    to.name !== 'post'
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
