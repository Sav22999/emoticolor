import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import LoginView from '@/views/account/LoginView.vue'
import NotFoundView from '@/views/NotFoundView.vue'
import SignupView from '@/views/account/SignupView.vue'

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
      meta: { title: 'Accesso' },
    },
    {
      path: '/account/signup',
      name: 'signup',
      component: SignupView,
      meta: { title: 'Registrazione' },
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
