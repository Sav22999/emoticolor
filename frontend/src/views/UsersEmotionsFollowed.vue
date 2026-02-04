<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'

function doAction(name: string) {
  console.log('Action:', name)
}

function backNavigation() {
  router.back()
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="backNavigation()"
    title="Utenti ed emozioni seguiti"
  ></topbar>
  <main>
    <div class="font-subtitle">Utenti seguiti</div>
    <div class="font-subtitle">Emozioni seguite</div>
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>
</template>

<style scoped lang="scss"></style>
