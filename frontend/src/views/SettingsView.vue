<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import ButtonGeneric from '@/components/button/button-generic.vue'
import apiService from '@/utils/api/api-service.ts'
import usefulFunctions from '@/utils/useful-functions.ts'
import ActionSheet from '@/components/modal/action-sheet.vue'
import { ref } from 'vue'

const editBioActionSheetRef = ref(false)
const editingBio = ref(false)

function doAction(name: string) {
  console.log('Action:', name)
}

function goToHome() {
  // Navigate to home view
  router.push({ name: 'home' })
}

function goBack() {
  router.back()
}

function goToLogin() {
  // Navigate to login view
  router.push({ name: 'login' })
}

function editBio() {
  console.log('Edit bio clicked')
}

function logout() {
  apiService.logout().then((response) => {
    if (response.status === 204) {
      //delete login-id and token-id from local storage
      usefulFunctions.removeFromLocalStorage('login-id')
      usefulFunctions.removeFromLocalStorage('token-id')
    } else {
      console.error('Logout failed')
    }
    goToLogin()
  })
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
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
            :disabled="true"
            @action="
              () => {
                editBioActionSheetRef = true
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
            icon="edit"
            text="Rivedi"
            :disabled="true"
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
            variant="primary"
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
      <div class="settings-card">
        <div class="text">Esci (disconnettiti) dal tuo account su questo dispositivo</div>
        <div class="button">
          <button-generic
            variant="primary"
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
    </div>
  </main>

  <action-sheet
    v-if="editBioActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Modifica la bio del profilo"
    button1-text="Chiudi"
    @onclose="editBioActionSheetRef = false"
    :height="70"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
  >
  </action-sheet>
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
</style>
