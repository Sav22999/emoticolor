<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import TextParagraph from '@/components/text/text-paragraph.vue'
import router from '@/router'
import InputPassword from '@/components/input/input-password.vue'
import TextInfo from '@/components/text/text-info.vue'
import apiService from '@/utils/api/api-service.ts'
import Toast from '@/components/modal/toast.vue'

const password = ref<string>('')
const repeatPassword = ref<string>('')
const loginId = ref<string>('')
const sent = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

function passwordChanged(value: string) {
  password.value = value
}

function repeatPasswordChanged(value: string) {
  repeatPassword.value = value
}

function validatePassword(password: string): boolean {
  return usefulFunctions.checkPasswordValidity(password)
}

function doChange() {
  if (
    password.value !== repeatPassword.value ||
    password.value.length === 0 ||
    repeatPassword.value.length === 0 ||
    sent.value
  ) {
    return
  }
  sent.value = true
  apiService.setNewPassword(loginId.value, password.value).then(
    (response) => {
      console.log('>>>', response)
      if (response.status === 204) {
        router.push({ name: 'login' })
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un error`
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

function goBack() {
  router.push({ name: 'reset-password' })
}

onMounted(() => {
  const loginIdTmp = usefulFunctions.loadFromLocalStorage('login-id')
  if (loginIdTmp && loginIdTmp !== '') {
    loginId.value = loginIdTmp
  } else {
    //redirect to reset password
    router.push({ name: 'reset-password' })
  }
})
</script>

<template>
  <topbar
    variant="standard"
    title="Imposta nuova password"
    :show-back-button="true"
    @onback="goBack"
  ></topbar>
  <main>
    <div class="content">
      <div class="container">
        <text-paragraph align="start"> Crea una nuova password.</text-paragraph>
        <div class="textboxes">
          <div class="info-box">
            <input-password
              @input="passwordChanged($event)"
              placeholder="nuova password"
              chars-disallowed=" "
              :text="password"
              :min-length="10"
              :error-status="!validatePassword(password)"
            ></input-password>
            <text-info>
              deve essere almeno di 10 caratteri e deve contenere almeno una lettera maiuscola, una
              lettera minuscola e un numero
            </text-info>
          </div>
          <input-password
            @input="repeatPasswordChanged($event)"
            placeholder="ripeti password"
            chars-disallowed=" "
            :text="repeatPassword"
            :min-length="10"
            :error-status="!validatePassword(password) || password !== repeatPassword"
            @onenter="doChange"
          ></input-password>
        </div>
        <button-generic
          @action="doChange"
          icon="forward"
          variant="cta"
          text="Prosegui"
          align="center"
          iconPosition="end"
          :disabled="
            password !== repeatPassword ||
            password.length === 0 ||
            repeatPassword.length === 0 ||
            sent
          "
        />
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
