<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import Toast from '@/components/modal/toast.vue'

function doAction(name: string) {
  console.log('Action:', name)
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-back-button="false"
    :show-search-button="true"
    :show-notifications-button="true"
    @onsearch="doAction('search')"
    @onnotifications="doAction('notifications')"
  ></topbar>
  <main>
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->

    <toast
      variant="standard"
      :show-button="true"
      :life-seconds="5"
      @action-button="doAction('toast-action')"
      @onopen="doAction('toast-opened')"
      @onclose="doAction('toast-closed')"
    >
      Prova di testo bla bla bla Prova di testo bla bla bla Prova di testo bla bla bla Prova di
      testo bla bla bla Prova di testo bla bla bla Prova di testo bla bla bla Prova di testo bla bla
      bla Prova di testo bla bla bla Prova di testo bla bla bla
    </toast>
  </main>
  <navbar @tabChange="doAction($event)" :selected-tab="1"></navbar>
</template>

<style scoped lang="scss"></style>
