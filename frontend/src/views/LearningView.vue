<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import Toast from '@/components/modal/toast.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'

const isLoading = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

onMounted(async () => {
  loaadContents()
})

function loaadContents() {
  isLoading.value = true
  apiService
    .getLearningStatistics()
    .then((response) => {
      // Handle the response and update the state accordingly
      console.log(response.data)
    })
    .catch((error) => {
      // Handle any errors that occur during the API call
      console.error('Error fetching learning contents:', error)
    })
    .finally(() => {
      isLoading.value = false
    })
}

function changeView(index: number) {
  if (index === 0) {
    // Stay in learning view
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
  <topbar variant="standard"></topbar>
  <div class="header-learning">
    <h2>Statistiche sull'apprendimento</h2>
    <div class="text">
      Visualizza le tue statistiche sulle emozioni che stai imparando o che hai imparato, così da
      visualizzare in maniera grafica quanto tempo dedichi all’apprendimento delle emozioni.
    </div>
    <button-generic
      text="Visualizza le statistiche"
      icon-position="end"
      icon="stats2"
      :full-width="true"
    />
  </div>
  <main>
    <h2>Apprendimenti in corso</h2>
    <div class="card-learning-emotion started"></div>
    <h2>Apprendimenti non ancora iniziati</h2>
    <div class="card-learning-emotion not-started"></div>
    <h2>Apprendimenti terminati</h2>
    <div class="card-learning-emotion finished"></div>
  </main>
  <navbar @tab-change="changeView($event)" :selected-tab="0"></navbar>

  <toast
    v-if="errorMessageToastRef"
    :life-seconds="20"
    @onclose="
      () => {
        errorMessageToastRef = false
      }
    "
  >
    {{ errorMessageToastText }}
  </toast>
</template>

<style scoped lang="scss">
h2 {
  font: var(--font-subtitle);
  color: var(--primary);
}
h1 {
  font: var(--font-title);
  color: var(--color-black);
}
.header-learning {
  display: flex;
  flex-direction: column;
  align-items: start;
  justify-content: center;
  padding: var(--padding);
  gap: var(--spacing);

  background-color: var(--color-blue-10);
  border-bottom: 5px solid var(--primary);

  .text {
    font: var(--font-paragraph);
    color: var(--color-black);
  }
}

main {
  display: flex;
  flex-direction: column;
  padding: var(--padding);
  gap: var(--spacing);

  .card-learning-emotion {
    border: 0 solid transparent;
    /*border-left: 4px solid transparent;*/
    background-color: var(--color-gray-10);
    padding: var(--padding);
    border-radius: var(--border-radius);

    &.not-started {
    }

    &.started {
      border-left-color: var(--color-blue-20);
      background-color: var(--color-blue-10);
    }
    &.finished {
      border-left-color: var(--color-green-20);
      background-color: var(--color-green-10);
    }
  }
}
</style>
