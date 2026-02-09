<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onBeforeMount, onMounted, ref } from 'vue'
import Toast from '@/components/modal/toast.vue'
import type {
  ApiEmotionResponse,
  emotionObjectInterface,
  learningContentInterface,
} from '@/utils/api/api-interface.ts'
import apiService from '@/utils/api/api-service.ts'
import CardLearning from '@/components/card/card-learning.vue'
import Spinner from '@/components/spinner.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'

const isLoading = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const emotionDetails = ref<emotionObjectInterface | undefined>(undefined)
const learningContents = ref<learningContentInterface[] | null>(null)

// before of onMounted set the emotionId
const emotionId = ref<number | null>(null)
// Try to read the route param synchronously so other logic can use it before onMounted
const rawParam = router.currentRoute?.value?.params?.emotionId
if (rawParam !== undefined && rawParam !== null && !isNaN(Number(rawParam))) {
  emotionId.value = Number(rawParam)
}

onBeforeMount(() => {
  // Any logic that needs to run before mounting can go here
  if (emotionId.value === null || isNaN(Number(emotionId.value))) {
    errorMessageToastText.value = `ID dell'emozione non valido.`
    errorMessageToastRef.value = true
    //goBack()
    return
  }
})

onMounted(() => {
  loadEmotionDetails(emotionId.value as number)
  loadContents()
  insertContentStatistics(emotionId.value as number)
})

function insertContentStatistics(emotionId: number, type2: number | null = null) {
  apiService
    .insertLearningContentProgress(emotionId, 'pill', type2)
    .then((res) => {
      if (res && res.status >= 200) {
        //console.log('Content statistics inserted successfully')
      } else if (res && res.status === 409) {
        //console.log('Content statistics already exists, no need to insert')
      } else {
        console.warn('Failed to insert content statistics:', res)
        // errorMessageToastRef.value = true
        // errorMessageToastText.value = `Errore nell'inserimento delle statistiche dei contenuti.`
      }
    })
    .catch((error) => {
      console.warn('Error inserting content statistics:', error)
      // errorMessageToastRef.value = true
      // errorMessageToastText.value = `Errore nell'inserimento delle statistiche dei contenuti.`
    })
}

function loadEmotionDetails(emotionId: number) {
  apiService
    .getEmotions(emotionId)
    .then((response) => {
      //console.log(response)
      if (
        response &&
        (response as ApiEmotionResponse) &&
        response.data &&
        response.status === 200
      ) {
        // Handle the response and update the state accordingly
        //console.log(response.data)
        emotionDetails.value =
          response.data && response.data.length === 1 ? response.data[0] : undefined
      } else {
        errorMessageToastText.value = `${response.status} Errore nel caricamento dei dettagli dell'emozione.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      // Handle any errors that occur during the API call
      console.error('Error fetching emotion details:', error)

      errorMessageToastText.value = `Errore nel caricamento dei dettagli dell'emozione.`
      errorMessageToastRef.value = true
    })
}

function loadContents(onFinished?: () => void): void {
  isLoading.value = true
  apiService
    .getLearningContents(emotionId.value as number, 'pill', null, true)
    .then((response) => {
      if (response && response.data && response.status === 200) {
        console.log(response.data)

        if (!response.data || response.data.length === 0) {
          learningContents.value = []
          return
        }
        if (response.data.length === 1) {
          learningContents.value = response.data[0]?.contents ?? []
        } else {
          //this shouldn't happen
        }
      } else {
        errorMessageToastText.value = `${response.status} Errore nel caricamento dei contenuti di apprendimento.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching learning contents:', error)
      errorMessageToastText.value = `Errore nel caricamento dei contenuti di apprendimento.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoading.value = false
      if (onFinished) onFinished()
    })
}

function goBack() {
  router.back()
}

function capitalizeFirstLetter(text: string): string {
  if (!text) return ''
  return text.charAt(0).toUpperCase() + text.slice(1)
}
</script>

<template>
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goBack"
    :title="`${capitalizeFirstLetter(emotionDetails?.['emotion-text'] ?? '')} – Nozioni in pillole`"
  ></topbar>
  <div class="loading-contents" v-if="isLoading">
    <spinner color="primary" />
  </div>
  <div
    class="no-contents"
    v-else-if="!isLoading && (learningContents === null || learningContents.length === 0)"
  >
    <text-paragraph align="center">
      Non sono ancora presenti contenuti di apprendimento di questa tipologia per questa emozione.
    </text-paragraph>
  </div>
  <main v-else-if="!isLoading && learningContents && learningContents.length > 0">
    <card-learning
      v-for="content in learningContents"
      :content="content"
      :key="content['learning-id']"
      :trigger="0"
    />
  </main>

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
  padding: var(--padding);
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
