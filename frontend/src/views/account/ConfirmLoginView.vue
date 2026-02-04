<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import InputGeneric from '@/components/input/input-generic.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import separator from '@/components/separator.vue'
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import TextInfo from '@/components/text/text-info.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import router from '@/router'
import apiService from '@/utils/api/api-service.ts'
import type { ApiLoginIdRefreshIdResponse } from '@/utils/api/api-interface.ts'
import Toast from '@/components/modal/toast.vue'

const otp = ref<string>('')
const loginId = ref<string>('')

const timeNewOtpCanBeSent = ref<number>(30) //seconds
const sent = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

function otpChange(value: string) {
  otp.value = value
}

function doVerify() {
  if (otp.value.length === 0 || sent.value) {
    return
  }
  sent.value = true
  apiService.verifyOtpCode(loginId.value, otp.value).then(
    (response) => {
      console.log('>>>', response)
      if (response.status === 200 && 'data' in response) {
        const res = response as ApiLoginIdRefreshIdResponse
        if (res.data['login-id'] && res.data['token-id']) {
          usefulFunctions.editToLocalStorage('login-id', res.data['login-id'])
          usefulFunctions.saveToLocalStorage('token-id', res.data['token-id'])
          router.push({ name: 'home' })
        }
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore`
        errorMessageToastRef.value = true
      }
      sent.value = false
    },
    (error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore: ${error.message}`
      errorMessageToastRef.value = true
      sent.value = false
    },
  )
}

function onNewOtpCode() {
  sent.value = true
  apiService.newOtpCode(loginId.value).then(
    (response) => {
      console.log('>>>', response)
      if (response.status === 204) {
        //new code sent
      } else {
        //usefulFunctions.showToast('Errore durante il login: ' + response.message, 'error')
      }
      sent.value = false
      timeNewOtpCanBeSent.value = 30
    },
    (error) => {
      console.error('Error', error)
      sent.value = false
      timeNewOtpCanBeSent.value = 30
    },
  )
}

function decreaseOtpTimer() {
  if (!sent.value) {
    if (timeNewOtpCanBeSent.value > 0) {
      timeNewOtpCanBeSent.value -= 1
    }
    setTimeout(decreaseOtpTimer, 1000)
  }
}

function goBack() {
  router.push({ name: 'login' })
}

onMounted(() => {
  /*const urlParams = new URLSearchParams(window.location.search)
  otp.value = urlParams.get('otp') || ''
  loginid.value = urlParams.get('loginid') || ''*/
  const loginIdTmp = usefulFunctions.loadFromLocalStorage('login-id')
  if (loginIdTmp && loginIdTmp !== '') {
    loginId.value = loginIdTmp
  } else {
    //redirect to login
    router.push({ name: 'login' })
  }
  setTimeout(decreaseOtpTimer, 2000)
})
</script>

<template>
  <topbar
    variant="standard"
    title="Verifica accesso"
    :show-back-button="true"
    @onback="goBack"
  ></topbar>
  <main>
    <div class="content">
      <div class="container">
        <text-paragraph align="start">
          Inserisci il codice monouso che è stato inviato sull’indirizzo email specificato, così da
          poter verificare l'accesso.
          <br />
          Verifica anche nella cartella della posta indesiderata o spam.
        </text-paragraph>
        <div class="textboxes">
          <input-generic
            icon="code"
            :class="{ 'all-uppercase': otp.length > 0 }"
            @input="otpChange($event)"
            placeholder="codice OTP"
            chars-allowed="0123456789"
            :text="otp"
            :debounce-time="0"
            :min-length="0"
            :max-length="8"
            @onenter="doVerify"
          ></input-generic>
        </div>
        <button-generic
          @action="doVerify"
          icon="forward"
          variant="cta"
          text="Prosegui"
          align="center"
          iconPosition="end"
          :disabled="otp.length === 0 || sent"
        />
      </div>
      <separator variant="primary" />
      <text-paragraph align="start">
        Se non hai ricevuto l’email, puoi richiedere un nuovo codice
      </text-paragraph>
      <div class="info-box">
        <button-generic
          @action="onNewOtpCode"
          icon="code"
          variant="primary"
          text="Ottieni un nuovo codice"
          align="center"
          iconPosition="end"
          :disabled="timeNewOtpCanBeSent > 0 || sent"
        />
        <text-info v-if="timeNewOtpCanBeSent > 0 && !sent">
          Puoi chiedere un nuovo codice tra <b>{{ timeNewOtpCanBeSent }}</b> secondi
        </text-info>
      </div>
    </div>
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

      .all-uppercase {
        text-transform: uppercase;
      }
    }

    .info-box {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-4);
    }
  }
}
</style>
