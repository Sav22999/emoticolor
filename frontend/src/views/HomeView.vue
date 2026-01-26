<script setup lang="ts">
import Topbar from '@/components/header/Topbar.vue'
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <Topbar variant="standard" :show-back-button="false" :show-search-button="true" :show-notifications-button="true" title="Provaaa"></Topbar>
  <main>
    Prova
    <HelloWorld msg="Welcome to Your Vue.js + TypeScript App" />
  </main>
  <footer></footer>
</template>

<style scoped lang="scss"></style>
