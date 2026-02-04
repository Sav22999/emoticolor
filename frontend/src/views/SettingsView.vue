<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'

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
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>
</template>

<style scoped lang="scss"></style>
