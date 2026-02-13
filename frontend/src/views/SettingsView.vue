<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import ButtonGeneric from '@/components/button/button-generic.vue'
import apiService from '@/utils/api/api-service.ts'
import usefulFunctions from '@/utils/useful-functions.ts'
import ActionSheet from '@/components/modal/action-sheet.vue'
import { onMounted, ref } from 'vue'
import InputMultiline from '@/components/input/input-multiline.vue'
import Toast from '@/components/modal/toast.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import TextInfo from '@/components/text/text-info.vue'

const editBioActionSheetRef = ref(false)
const editProfileImageActionSheetRef = ref(false)

const textBio = ref('')
const textBioOriginal = ref('')

const appVersion = __APP_VERSION__

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

onMounted(() => {
  loadBio()
})

function loadBio() {
  apiService.getBio().then((response) => {
    if (response.status === 200) {
      const bio = response.data?.bio as string
      //console.log('Bio loaded:', bio)
      if (bio) {
        textBio.value = bio
        textBioOriginal.value = bio
      } else {
        //console.warn('Bio is empty')
        textBio.value = ''
        textBioOriginal.value = ''
      }
    } else {
      console.error('Failed to load bio')
    }
  })
}

function updateBio(newBio: string) {
  apiService.updateBio(newBio).then((response) => {
    if (response.status === 204) {
      //console.log('Bio updated successfully')
      textBio.value = newBio
    } else {
      console.error('Failed to update bio')
      errorMessageToastRef.value = true
      errorMessageToastText.value = "Errore durante l'aggiornamento della bio"
    }
  })
}

function removeBio() {
  updateBio('')
  textBio.value = ''
  textBioOriginal.value = ''
}

function onInput(newValue: string) {
  textBio.value = newValue
}

function goBack() {
  router.back()
}

function goToLogin() {
  // Navigate to login view
  router.push({ name: 'login' })
}

function goToInitialTutorial() {
  // Navigate to initial tutorial view
  router.push({ name: 'initial-tutorial' })
}

function logout() {
  apiService.logout().then((response) => {
    if (response.status === 204) {
      //delete login-id and token-id from local storage
      usefulFunctions.removeFromLocalStorage('login-id')
      usefulFunctions.removeFromLocalStorage('token-id')
      setTimeout(() => {
        goToLogin()
      }, 500)
    } else {
      console.error('Logout failed')

      errorMessageToastRef.value = true
      errorMessageToastText.value = 'Errore durante il logout'
    }
  })
}

function openGravatar() {
  window.open('https://it.gravatar.com/', '_blank')
}

function sendEmail() {
  const email = 'saverio.morelli@ik.me'
  window.open('mailto:' + email, '_blank')
}
</script>

<template>
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goBack"
    title="Impostazioni"
  ></topbar>
  <main>
    <div class="top">
      <div class="settings-card">
        <div class="text">Modifica la bio del profilo</div>
        <div class="button">
          <button-generic
            variant="primary"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="edit"
            text="Cambia"
            :disabled="false"
            @action="
              () => {
                editBioActionSheetRef = true
              }
            "
          ></button-generic>
        </div>
      </div>
      <div class="settings-card">
        <div class="text">Modifica l'immagine profilo</div>
        <div class="button">
          <button-generic
            variant="primary"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="edit"
            text="Cambia"
            :disabled="false"
            @action="
              () => {
                editProfileImageActionSheetRef = true
              }
            "
          ></button-generic>
        </div>
      </div>
      <div class="settings-card">
        <div class="text">Rivedi nuovamente il tutorial iniziale</div>
        <div class="button">
          <button-generic
            variant="primary"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="refresh"
            text="Rivedi"
            :disabled="false"
            @action="
              () => {
                usefulFunctions.removeFromLocalStorage('initial-tutorial-seen')
                goToInitialTutorial()
              }
            "
          ></button-generic>
        </div>
      </div>
      <div class="settings-card">
        <div class="text">Cambia la password dell’account</div>
        <div class="button">
          <button-generic
            variant="primary"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="password"
            text="Cambia"
            :disabled="true"
          ></button-generic>
        </div>
      </div>
      <div class="settings-card">
        <div class="text">Elimina definitivamente il tuo account</div>
        <div class="button">
          <button-generic
            variant="warning"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="delete"
            text="Elimina"
            :disabled="true"
          ></button-generic>
        </div>
      </div>
    </div>
    <div class="bottom">
      <div class="settings-card version-card">
        <div class="text">Versione app</div>
        <div class="version-number">{{ appVersion }}</div>
      </div>
      <div class="settings-card">
        <div class="text">Contatta l'assistenza</div>
        <div class="button">
          <button-generic
            variant="primary"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="email"
            text="Contatta"
            @action="sendEmail"
            :disabled="false"
          ></button-generic>
        </div>
      </div>
      <div class="settings-card">
        <div class="text">Esci (disconnettiti) dal tuo account su questo dispositivo</div>
        <div class="button">
          <button-generic
            variant="warning"
            :full-width="true"
            align="space"
            icon-position="end"
            :small="true"
            icon="logout"
            text="Esci"
            @action="logout"
            :disabled="false"
          ></button-generic>
        </div>
      </div>
      <text-info :show-icon="false">{{ appVersion }}</text-info>
    </div>
  </main>

  <action-sheet
    v-if="editBioActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Modifica la bio del profilo"
    @onclose="editBioActionSheetRef = false"
    :height="50"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
    :button1-text="textBio !== textBioOriginal || textBio.length > 0 ? 'Elimina' : ''"
    :button1-style="textBio !== textBioOriginal || textBio.length > 0 ? 'warning' : 'primary'"
    :button1-icon="textBio !== textBioOriginal || textBio.length > 0 ? 'delete' : ''"
    @action-button1="
      () => {
        if (textBio !== textBioOriginal || textBio.length > 0) {
          removeBio()
        }
        editBioActionSheetRef = false
      }
    "
    :button2-text="textBio !== textBioOriginal ? 'Salva' : 'Chiudi'"
    :button2-style="textBio !== textBioOriginal ? 'cta' : 'primary'"
    :button2-icon="textBio !== textBioOriginal ? 'mark-yes' : 'chevron-down'"
    @action-button2="
      () => {
        if (textBio !== textBioOriginal) {
          updateBio(textBio)
          editBioActionSheetRef = false
        } else {
          editBioActionSheetRef = false
        }
      }
    "
  >
    <div class="bio-textarea">
      <input-multiline
        placeholder="Inserisci la tua biografia…"
        :text="textBio"
        @input="onInput($event)"
        :max-length="500"
      ></input-multiline>
    </div>
  </action-sheet>

  <action-sheet
    v-if="editProfileImageActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Modifica la bio del profilo"
    @onclose="editProfileImageActionSheetRef = false"
    :height="50"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
    button1-text="Chiudi"
    button1-icon="chevron-down"
    button2-text="Vai su Gravatar"
    button2-icon="external"
    @action-button2="openGravatar"
  >
    <div class="text-box">
      <text-paragraph align="start">
        Per cambiare l'immagine profilo andare su Gravatar, effettuare l'accesso con lo stesso
        indirizzo email utilizzato per il tuo account Emoticolor, e impostare l'immagine profilo
        come pubblica.
      </text-paragraph>
    </div>
  </action-sheet>

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
main {
  display: flex;
  flex-direction: column;
  flex: 1;
  align-items: center;
  justify-content: space-between;

  padding: var(--padding);
  gap: var(--spacing-16);

  .top,
  .bottom {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-16);
    width: 100%;
  }

  .settings-card {
    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);
    background-color: var(--color-blue-10);
    padding: var(--spacing-8);
    border-radius: var(--border-radius);

    .text {
      flex: 1;
      color: var(--color-black);
      word-break: break-word;
      font: var(--font-paragraph);
      padding: var(--padding-8);
    }
    .button {
      display: flex;
      align-items: center;
      justify-content: center;
      min-width: 120px;
    }
  }
}

.bio-textarea,
.text-box {
  padding: var(--padding);
}
.bottom {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding-32);
}
.version-card {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
  justify-content: center !important;
  opacity: 0.6;
  .text {
    font-size: var(--font-size-12) !important;
  }
  .version-number {
    font-size: var(--font-size-12);
    font-weight: bold;
  }
}
</style>
