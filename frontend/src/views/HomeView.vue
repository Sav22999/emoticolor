<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import CardPost from '@/components/card/card-post.vue'

function doAction(name: string) {
  console.log('Action:', name)
}

function changeView(index: number) {
  if (index === 0) {
    // Navigate to learning view
    router.push({ name: 'learning' })
  } else if (index === 1) {
    // Stay in home view
  } else if (index === 2) {
    // Navigate to profile view
    router.push({ name: 'profile' })
  }
}

function goToNotifications() {
  // Navigate to notifications view
  router.push({ name: 'notifications' })
}

function goToSearch() {
  // Navigate to search view
  router.push({ name: 'search' })
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-search-button="true"
    :show-notifications-button="true"
    @onsearch="goToSearch"
    @onnotifications="goToNotifications"
  ></topbar>
  <main>
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->

    <card-post></card-post>
  </main>
  <navbar @tab-change="changeView($event)" :selected-tab="1"></navbar>
</template>

<style scoped lang="scss">
main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding);
}
</style>
