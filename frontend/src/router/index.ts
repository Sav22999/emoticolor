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

/*router.beforeEach((to, from, next) => {
  //example of route guard (in this case, to prevent access to home if not signed up)
  /!*if (to.name === 'home' && localStorage.getItem('signup') !== 'true') {
    next({ name: 'login' })
  } else {
    next()
  }*!/
})*/

router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} - Emoticolor` : 'Emoticolor'
})

export default router
