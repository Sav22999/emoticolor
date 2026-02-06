<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import Toast from '@/components/modal/toast.vue'
import type { learningStatisticsInterface } from '@/utils/api/api-interface.ts'

const isLoading = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const learningStatistics = ref<learningStatisticsInterface[] | null>(null)
const learningStatisticsGrouped = ref<{ datetime: string; items: learningStatisticsInterface[] }[]>(
  [],
)

onMounted(() => {
  loadContents()
})

function loadContents(onFinished?: () => void): void {
  isLoading.value = true
  /*apiService
    .getLearningStatistics()
    .then((response) => {
      if (
        response &&
        (response as ApiLearningStatisticsResponse) &&
        response.data &&
        response.status === 200
      ) {
        // Handle the response and update the state accordingly
        //console.log(response.data)
        learningStatistics.value = response.data
        // Re-group immediately after receiving data
        groupStatisticsByDate()
      } else {
        errorMessageToastText.value = `${response.status} Errore nel caricamento dei contenuti di apprendimento.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      // Handle any errors that occur during the API call
      console.error('Error fetching learning contents:', error)

      errorMessageToastText.value = `Errore nel caricamento dei contenuti di apprendimento.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoading.value = false
      if (onFinished) onFinished()
    })*/
}

function goBack() {
  router.back()
}
</script>

<template>
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goBack"
    title="Statistiche sull'apprendimento"
  ></topbar>
  <main></main>

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
main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing);
  padding: var(--no-padding);
}

.loading-contents {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--padding-16);
  min-height: 200px;
}

.no-contents {
  display: flex;
  flex-direction: column;
  align-items: start;
  justify-content: center;
  padding: var(--padding-16);
  font: var(--font-paragraph);
  min-height: 100px;

  .text {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100px;
  }
}
</style>
