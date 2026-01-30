<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import InputGeneric from '@/components/input/input-generic.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import TextParagraph from '@/components/text/text-paragraph.vue'
import router from '@/router'
import apiService from '@/utils/api/api-service.ts'

const email = ref<string>('')
const sent = ref<boolean>(false)

function emailChanged(value: string) {
  email.value = value
}

function validateEmail(email: string): boolean {
  return usefulFunctions.checkEmailValidity(email) || email.length === 0
}

function doRequest() {
  if (!validateEmail(email.value) || email.value.length === 0 || sent.value) {
    return
  }
  sent.value = true
  apiService.resetPassword(email.value).then(
    (response) => {
      console.log('>>>', response)
      if (response.status === 200) {
        //check if response is ApiLoginIdResponse
        if (response.data['login-id']) {
          usefulFunctions.saveToLocalStorage('login-id', response.data['login-id'])
          router.push({ name: 'reset-password-verify' })
        }
      } else {
        //usefulFunctions.showToast('Errore durante il login: ' + response.message, 'error')
      }
      sent.value = false
    },
    (error) => {
      console.error('Error', error)
      sent.value = false
    },
  )
}

function goBack() {
  router.push({ name: 'login' })
}

onMounted(() => {})
</script>

<template>
  <topbar
    variant="standard"
    title="Ripristina password"
    :show-back-button="true"
    @onback="goBack"
  ></topbar>
  <main>
    <div class="content">
      <div class="container">
        <text-paragraph align="start">
          Inserisci l’indirizzo email associato all’account del quale si vuole ripristinare la
          password
        </text-paragraph>
        <div class="textboxes">
          <input-generic
            icon="email"
            @input="emailChanged($event)"
            placeholder="indirizzo email"
            chars-disallowed=" "
            :error-status="!validateEmail(email)"
            :text="email"
            @onenter="doRequest"
          ></input-generic>
        </div>
        <button-generic
          @action="doRequest"
          icon="forward"
          variant="cta"
          text="Prosegui"
          align="center"
          iconPosition="end"
          :disabled="!validateEmail(email) || email.length === 0 || sent"
        />
      </div>
    </div>
  </main>
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
