<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'

function doAction(name: string) {
  console.log('Action:', name)
}

function goToHome() {
  // Navigate to home view
  router.push({ name: 'home' })
}

function changeView(index: number) {
  if (index === 0) {
    // Navigate to learning view
    router.push({ name: 'learning' })
  } else if (index === 1) {
    // Navigate to home view
    router.push({ name: 'home' })
  } else if (index === 2) {
    // Navigate to profile view
    router.push({ name: 'profile' })
  }
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar variant="standard" :show-back-button="true" @onback="goToHome" title="Notifiche"></topbar>
  <main>
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>
  <navbar @tab-change="changeView($event)" :selected-tab="-1"></navbar>
</template>

<style scoped lang="scss"></style>
