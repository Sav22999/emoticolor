<script setup lang="ts">
import { onMounted, ref } from 'vue'
import router from '@/router'

const reduceIcon = ref<boolean>(false)

onMounted(() => {
  setTimeout(() => {
    reduceIcon.value = true
    redirectToCorrectPage()
  }, 0)
})

function redirectToCorrectPage() {
  setTimeout(() => {
    // Verifichiamo se siamo ancora sulla rotta splash prima di navigare
    if (router.currentRoute.value.name === 'splash') {
      router.push({ name: 'home' })
    }
  }, 1500)
}
</script>

<template>
  <div class="bar hidden">
    <div class="purple"></div>
    <div class="yellow"></div>
    <div class="red"></div>
    <div class="blue"></div>
    <div class="gray"></div>
    <div class="green"></div>
    <div class="brown"></div>
  </div>
  <main>
    <div class="content">
      <img src="@/assets/images/icon.svg" alt="icon" class="icon" :class="{ reduce: reduceIcon }" />
    </div>
  </main>
  <div class="bar hidden">
    <div class="purple"></div>
    <div class="yellow"></div>
    <div class="red"></div>
    <div class="blue"></div>
    <div class="gray"></div>
    <div class="green"></div>
    <div class="brown"></div>
  </div>
</template>

<style scoped lang="scss">
main {
  background-color: var(--color-green-10);
  color: var(--primary);
  height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: var(--padding-32);

  .content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-16);
    text-align: center;
    width: 100%;

    img {
      flex: 1;
      width: 80%;
      height: auto;

      &.icon {
        min-width: auto;
        max-width: none;
        width: 50%;
        scale: 200%;
        transition: all 5s ease-in-out;

        &.reduce {
          scale: 100%;
        }
      }
    }
  }
}
</style>
