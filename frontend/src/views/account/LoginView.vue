<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import InputGeneric from '@/components/input/input-generic.vue'
import InputPassword from '@/components/input/input-password.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import separator from '@/components/separator.vue'
import textLink from '@/components/text/text-link.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import apiService from '@/utils/api/api-service.ts'
import Toast from '@/components/modal/toast.vue'

const email = ref<string>('')
const password = ref<string>('')
const sent = ref<boolean>(false)
const showSessionExpiredToast = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

function emailChanged(value: string) {
  email.value = value
}
function passwordChanged(value: string) {
  password.value = value
}

function validateEmail(email: string): boolean {
  return usefulFunctions.checkEmailValidity(email) || email.length === 0
}

function validatePassword(password: string): boolean {
  return usefulFunctions.checkPasswordValidity(password)
}

function openSignup() {
  router.push({ name: 'signup' })
}

function openForgotPassword() {
  router.push({ name: 'reset-password' })
}

function doLogin() {
  if (
    !validateEmail(email.value) ||
    !validatePassword(password.value) ||
    email.value.length === 0 ||
    sent.value
  ) {
    return
  }
  sent.value = true
  apiService.login(email.value, password.value).then(
    (response) => {
      if (response.status === 200 && response.data) {
        usefulFunctions.saveToLocalStorage('login-id', response.data['login-id'])
        router.push({ name: 'login-verify' })
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore ${response.message}`
        errorMessageToastRef.value = true
      }
      sent.value = false
    },
    (error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore ${error.message}`
      errorMessageToastRef.value = true
      sent.value = false
    },
  )
}

onMounted(() => {
  // Check in the URL if there's the "session-expired" parameter, and if it's set to "true"
  const urlParams = new URLSearchParams(window.location.search)
  const sessionExpired = urlParams.get('session-expired')
  if (sessionExpired === 'true') {
    showSessionExpiredToast.value = true
  }
})
</script>

<template>
  <topbar variant="simple-big" title="Accedi"></topbar>
  <main>
    <div class="content">
      <div class="container">
        <div class="textboxes">
          <input-generic
            icon="email"
            @input="emailChanged($event)"
            placeholder="indirizzo email"
            chars-disallowed=" "
            :error-status="!validateEmail(email)"
            :text="email"
            :min-length="10"
          ></input-generic>
          <input-password
            @input="passwordChanged($event)"
            placeholder="password"
            chars-disallowed=" "
            :text="password"
            :min-length="10"
            @onenter="doLogin"
          ></input-password>
        </div>
        <button-generic
          @action="doLogin"
          icon="login"
          variant="cta"
          text="Accedi"
          align="center"
          iconPosition="end"
          :disabled="
            !validateEmail(email) || !validatePassword(password) || email.length === 0 || sent
          "
        />
      </div>
      <text-link
        text="Hai dimenticato la password?"
        @action="openForgotPassword"
        :disabled="sent"
      />
      <separator variant="primary" />
      <button-generic
        @action="openSignup"
        icon="plus-circle"
        variant="primary"
        text="Crea un account"
        align="center"
        iconPosition="end"
        :disabled="sent"
      />
    </div>

    <toast
      v-if="showSessionExpiredToast"
      variant="warning"
      :show-button="false"
      :life-seconds="0"
      position="bottom"
    >
      La sessione è scaduta, ed è necessario effettuare nuovamente il login.
    </toast>
  </main>

  <toast
    v-if="errorMessageToastRef"
    :life-seconds="20"
    @onclose="
      () => {
        errorMessageToastRef = false
      }
    "
  >
    {{ errorMessageToastText }}
  </toast>
</template>

<style scoped lang="scss">
.fullwidth {
  width: 100%;
  display: flex;
  justify-content: start;
}
.content {
  display: flex;
  flex-direction: column;
  padding: var(--padding-32);
  gap: var(--spacing-16);
  height: auto;

  .container {
    display: flex;
    flex-direction: column;
    gap: var(--padding-32);

    .textboxes {
      display: flex;
      flex-direction: column;
      gap: var(--padding-16);
    }
  }
}
</style>
