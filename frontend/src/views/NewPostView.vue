<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { ref } from 'vue'
import ActionSheet from '@/components/modal/action-sheet.vue'

const confirmationGoBack = ref<boolean>(false)
const contentEdited = ref<boolean>(false)

function doAction(name: string) {
  console.log('Action:', name)
}

function goToHome() {
  // Navigate to home view
  router.push({ name: 'home' })
}

function goBackWithConfirmation() {
  if (contentEdited.value) {
    confirmationGoBack.value = true
  } else {
    goToHome()
  }
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goBackWithConfirmation"
    title="Nuovo stato emotivo"
  ></topbar>
  <main>
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>

  <action-sheet
    v-if="confirmationGoBack"
    :hidden-by-default="false"
    variant="warning"
    title="Sei sicuro di voler uscire?"
    button1-text="Annulla"
    button2-text="Esci"
    button2-icon="trash"
    button2-style="warning"
    @action-button2="goToHome"
    :height="50"
    @onclose="confirmationGoBack = false"
  >
    Il contenuto che stavi creando verrà perso se esci senza salvare.
  </action-sheet>
</template>

<style scoped lang="scss"></style>
