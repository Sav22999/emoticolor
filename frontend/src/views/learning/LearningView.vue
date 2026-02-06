<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import Toast from '@/components/modal/toast.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import type {
  ApiLearningStatisticsResponse,
  learningStatisticsInterface,
} from '@/utils/api/api-interface.ts'
import TextInfo from '@/components/text/text-info.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import Spinner from '@/components/spinner.vue'

const isLoading = ref<boolean>(false)

const isScrolled = ref(false)
const isRefreshing = ref(false)
const refreshCounter = ref(0)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const learningStatistics = ref<learningStatisticsInterface[] | null>(null)

const learningStatisticsNotStarted = ref<learningStatisticsInterface[] | null>(null)
const learningStatisticsStarted = ref<learningStatisticsInterface[] | null>(null)
const learningStatisticsFinished = ref<learningStatisticsInterface[] | null>(null)

onMounted(async () => {
  loadContents()
})

function loadContents() {
  isLoading.value = true
  apiService
    .getLearningStatisticsEmotions()
    .then((response) => {
      if (
        response &&
        (response as ApiLearningStatisticsResponse) &&
        response.data &&
        response.status === 200
      ) {
        learningStatistics.value = response.data ?? []

        // set "not-started" learning statistics
        learningStatisticsNotStarted.value = learningStatistics.value.filter(
          (stat) => stat.type === 0,
        )
        // set "started" learning statistics
        learningStatisticsStarted.value = learningStatistics.value.filter((stat) => stat.type === 1)
        // set "finished" learning statistics
        learningStatisticsFinished.value = learningStatistics.value.filter(
          (stat) => stat.type === 2 || stat.type === 3,
        )
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
      isRefreshing.value = false
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

function refreshContents() {
  isRefreshing.value = true
  refreshCounter.value++
  loadContents()
}

/**
 * Return a formatted date string in the format DD/MM/YYYY
 * @param dateString - The date string to format
 */
function showDatetime(dateString: string): string {
  const date = new Date(dateString)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0') // Months are zero-based
  const year = date.getFullYear()
  return `${day}/${month}/${year}`
}

function goToLearningStatistics() {
  router.push({ name: 'learning-statistics' })
}

function goToEmotion(emotionId: number) {
  router.push('/learning/emotion/' + emotionId)
}
</script>

<template>
  <topbar variant="standard"></topbar>
  <div class="header-learning">
    <h2>Statistiche sull'apprendimento</h2>
    <div class="text">
      <text-paragraph align="start" color="black">
        Visualizza le tue statistiche sulle emozioni che stai imparando o che hai imparato, così da
        visualizzare in maniera grafica quanto tempo dedichi all’apprendimento delle emozioni.
      </text-paragraph>
    </div>
    <button-generic
      text="Visualizza le statistiche"
      icon-position="end"
      icon="stats2"
      :full-width="true"
      @action="goToLearningStatistics"
    />
  </div>
  <div class="loading-contents" v-if="isLoading">
    <spinner color="primary" />
  </div>
  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="refreshContents"
    @scrolled="isScrolled = $event"
  >
    <main v-if="!isLoading">
      <div class="no-contents" v-if="!isLoading && learningStatisticsStarted?.length === 0">
        <h2>Apprendimenti in corso</h2>
        <text-paragraph align="center" class="text">
          Non ci sono apprendimenti in corso.
        </text-paragraph>
      </div>
      <div class="main" v-for="content in learningStatisticsStarted" :key="content['emotion-id']">
        <h2>Apprendimenti in corso</h2>
        <div
          class="card-learning-emotion started"
          v-if="
            !isLoading &&
            learningStatisticsStarted !== null &&
            learningStatisticsStarted?.length > 0
          "
        >
          <h1>{{ content['emotion-text'] }}</h1>
          <div class="text">
            <text-paragraph align="start" color="primary">
              {{ content['emotion-description'] }}
            </text-paragraph>
          </div>
          <div class="button">
            <button-generic
              text="Continua a imparare o approfondire"
              icon-position="end"
              icon="forward"
              :full-width="true"
              @action="goToEmotion(content['emotion-id'])"
            />
            <text-info>Apprendimento iniziato il {{ showDatetime(content.created) }}</text-info>
          </div>
        </div>
      </div>
      <div class="no-contents" v-if="!isLoading && learningStatisticsNotStarted?.length === 0">
        <h2>Apprendimenti non ancora iniziati</h2>
        <text-paragraph align="center" class="text">
          Non ci sono apprendimenti non ancora iniziati.
        </text-paragraph>
      </div>
      <div
        class="main"
        v-for="content in learningStatisticsNotStarted"
        :key="content['emotion-id']"
      >
        <h2>Apprendimenti non ancora iniziati</h2>
        <div
          class="card-learning-emotion not-started"
          v-if="
            !isLoading &&
            learningStatisticsNotStarted !== null &&
            learningStatisticsNotStarted?.length > 0
          "
        >
          <h1>{{ content['emotion-text'] }}</h1>
          <div class="text">
            <text-paragraph align="start" color="primary">
              {{ content['emotion-description'] }}
            </text-paragraph>
          </div>
          <div class="button">
            <button-generic
              text="Inizia l'apprendimento"
              icon-position="end"
              icon="forward"
              :full-width="true"
              @action="goToEmotion(content['emotion-id'])"
            />
            <text-info>Apprendimento non ancora iniziato</text-info>
          </div>
        </div>
      </div>
      <div class="no-contents" v-if="!isLoading && learningStatisticsFinished?.length === 0">
        <h2>Apprendimenti conclusi</h2>
        <text-paragraph align="center" class="text">
          Non ci sono apprendimenti conclusi.
        </text-paragraph>
      </div>
      <div class="main" v-for="content in learningStatisticsFinished" :key="content['emotion-id']">
        <h2>Apprendimenti conclusi</h2>
        <div
          class="card-learning-emotion finished"
          v-if="
            !isLoading &&
            learningStatisticsFinished !== null &&
            learningStatisticsFinished?.length > 0
          "
        >
          <h1>{{ content['emotion-text'] }}</h1>
          <div class="text">
            <text-paragraph align="start" color="primary">
              {{ content['emotion-description'] }}
            </text-paragraph>
          </div>
          <div class="button">
            <button-generic
              text="Ripeti quanto già appreso"
              icon-position="end"
              icon="forward"
              :full-width="true"
              @action="goToEmotion(content['emotion-id'])"
            />
            <text-info>Apprendimento concluso il {{ showDatetime(content.created) }}</text-info>
          </div>
        </div>
      </div>
    </main>
  </pull-to-refresh>
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
  text-transform: capitalize;
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
}

main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing);
  padding: var(--no-padding);

  .main {
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

      display: flex;
      flex-direction: column;
      align-items: start;
      justify-content: center;
      width: 100%;
      gap: var(--spacing-16);

      > .button {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-4);
        width: 100%;
      }

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
