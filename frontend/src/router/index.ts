import type { NavigationGuardNext, RouteLocationNormalized } from 'vue-router'
import { createRouter, createWebHistory } from 'vue-router'
import apiService from '@/utils/api/api-service.ts'
import type { ApiLoginIdResponse } from '@/utils/api/api-interface.ts'
import usefulFunctions from '@/utils/useful-functions.ts'

/**
 * Gestisce la guardia per il tutorial iniziale.
 * Se l'utente non ha visto il tutorial, viene reindirizzato ad esso.
 * Se ha già visto il tutorial e prova ad accedervi, viene reindirizzato allo splash.
 */
const handleTutorialGuard = (to: RouteLocationNormalized, next: NavigationGuardNext) => {
  const hasSeenTutorial = usefulFunctions.loadFromLocalStorage('initial-tutorial-seen')

  if (
    to.name !== 'initial-tutorial' &&
    to.name !== 'not-found' &&
    to.name !== 'splash' &&
    to.name !== 'no-internet-connection' &&
    to.name !== 'login' &&
    to.name !== 'signup'
  ) {
    if (!hasSeenTutorial) {
      next({ name: 'initial-tutorial' })
      return true
    }
  }

  if (to.name === 'initial-tutorial') {
    if (hasSeenTutorial) {
      next({ name: 'splash' })
      return true
    }
  }
  return false
}

/**
 * Gestisce la guardia per le pagine di autenticazione (login, signup, splash).
 * Se l'utente è già loggato, viene reindirizzato alla home.
 */
const handleAuthPagesGuard = (to: RouteLocationNormalized, next: NavigationGuardNext) => {
  if (to.name === 'login' || to.name === 'signup' || to.name === 'splash') {
    const loginId = localStorage.getItem('login-id')
    const refreshId = localStorage.getItem('token-id')
    if (loginId && refreshId) {
      if (to.name === 'splash') {
        // In splash, se siamo loggati, proseguiamo e lasciamo che SplashScreen.vue gestisca il redirect a home
        next()
      } else {
        // Se proviamo ad andare in login/signup essendo già loggati, andiamo in home
        next({ name: 'home' })
      }
      return true
    } else {
      // Se non siamo loggati, lasciamo andare alle pagine di auth/splash
      next()
      return true
    }
  }
  return false
}

/**
 * Gestisce la validazione della sessione per le rotte protette.
 */
const handleSessionGuard = (to: RouteLocationNormalized, next: NavigationGuardNext) => {
  const publicRoutes = [
    'login',
    'login-verify',
    'signup',
    'signup-verify',
    'reset-password',
    'reset-password-verify',
    'reset-password-set-new',
    'not-found',
    'splash',
    'no-internet-connection',
    'post',
    'initial-tutorial',
  ]

  if (!publicRoutes.includes(to.name as string)) {
    const loginId = localStorage.getItem('login-id')
    const refreshId = localStorage.getItem('token-id')

    if (loginId && refreshId) {
      apiService
        .checkLoginIdValid(loginId)
        .then((isLoggedIn) => {
          if (isLoggedIn && (isLoggedIn.status === 204 || isLoggedIn.status === 200)) {
            next()
          } else if (isLoggedIn && isLoggedIn.status === 440) {
            // Sessione scaduta, prova il refresh
            return apiService.refreshLoginId(loginId, refreshId).then((refreshResponse) => {
              if (
                refreshResponse &&
                refreshResponse.status === 200 &&
                'data' in refreshResponse &&
                refreshResponse.data &&
                refreshResponse.data['login-id']
              ) {
                const res = refreshResponse as ApiLoginIdResponse
                usefulFunctions.saveToLocalStorage('login-id', res.data['login-id'])
                next()
              } else {
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
      return true
    } else {
      usefulFunctions.removeFromLocalStorage('login-id')
      usefulFunctions.removeFromLocalStorage('token-id')
      next({ name: 'login' })
      return true
    }
  }
  return false
}

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
      path: '/initial-tutorial/',
      component: () => import('@/views/initial-tutorial/InitialTutorialView.vue'),
      name: 'initial-tutorial',
      meta: { title: 'Tutorial iniziale' },
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
  if (handleTutorialGuard(to, next)) return
  if (handleAuthPagesGuard(to, next)) return
  if (handleSessionGuard(to, next)) return

  next()
})

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - Emoticolor` : 'Emoticolor'
})

export default router
