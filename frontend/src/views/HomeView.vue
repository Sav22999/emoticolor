<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import generic from '@/components/input/generic.vue'
import password from '@/components/input/password.vue'

function doAction(name: string) {
  console.log('Action:', name)
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    @action="doAction($event)"
    :show-back-button="true"
    :show-search-button="false"
    :show-notifications-button="false"
    title="Provaaa"
  ></topbar>
  <main>
<!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>
  <navbar @tabChange="doAction($event)"></navbar>
</template>

<style scoped lang="scss"></style>
