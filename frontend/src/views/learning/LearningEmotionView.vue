<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onBeforeMount, onMounted, ref } from 'vue'
import Toast from '@/components/modal/toast.vue'
import type {
  ApiEmotionResponse,
  emotionObjectInterface,
  learningContentsStatisticsInterface,
  learningStatisticsInterface
} from '@/utils/api/api-interface.ts'
import apiService from '@/utils/api/api-service.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'
import Spinner from '@/components/spinner.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import Progressbar from '@/components/progressbar.vue'

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const emotionDetails = ref<emotionObjectInterface | undefined>(undefined)
const emotionStatistics = ref<learningStatisticsInterface[] | null>([])

const isLoading = ref<boolean>(true)
const learningContentsStatistics = ref<learningContentsStatisticsInterface | null>(null)

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
    goBack()
    return
  }
})

onMounted(() => {
  //get emotionId from route params (use prepopulated ref)
  loadEmotionDetails(Number(emotionId.value))
  loadEmotionStatus()
  loadContents()
})

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

function toggleFollowEmotion(): void {
  if (
    emotionId.value === null ||
    emotionDetails.value === undefined ||
    emotionDetails.value['is-followed'] === undefined
  ) {
    errorMessageToastText.value = `ID dell'emozione non valido.`
    errorMessageToastRef.value = true
    return
  }
  apiService
    .toggleEmotionFollow(
      emotionId.value as number,
      emotionDetails.value['is-followed'] ? 'unfollow' : 'follow',
    )
    .then((response) => {
      if (response && response.status === 204) {
        // Successfully toggled follow status, now update the local state
        //console.log(response)
        if (emotionDetails.value) {
          emotionDetails.value['is-followed'] = !emotionDetails.value['is-followed']
        }
      } else {
        errorMessageToastText.value = `Errore nel seguire/smettere di seguire l'emozione.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error toggling follow status:', error)
      errorMessageToastText.value = `Errore nel seguire/smettere di seguire l'emozione.`
      errorMessageToastRef.value = true
    })
}

function loadEmotionStatus(): void {
  apiService
    .getLearningStatistics('it', emotionId.value)
    .then((response) => {
      if (response && response.status === 200) {
        // Handle the response and update the state accordingly
        //console.log(response)
        emotionStatistics.value = response.data

        let biggerType: number | null = null
        if (emotionStatistics.value !== null && emotionStatistics.value.length > 0) {
          //console.log(emotionStatistics.value)
          biggerType = emotionStatistics.value.reduce((prev, current) =>
            prev['type'] > current['type'] ? prev : current,
          ).type
        } else if (emotionStatistics.value !== null && emotionStatistics.value.length === 0) {
          //console.log('No learning statistics available for this emotion.')
          biggerType = 0
        } else {
          //console.log('Learning statistics data is null.')
          biggerType = null
        }

        if (biggerType !== null) {
          if (biggerType === 0) {
            //console.log(`No learning statistics available for emotion ${emotionId.value}.`)
            insertStatistic(emotionId.value as number, 'learning')
          } else if (biggerType === 1) {
            //console.log(`Emotion ${emotionId.value} is being learned.`)
            //do nothing
          } else if (biggerType === 2) {
            //console.log(`Emotion ${emotionId.value} has been learned.`)
            insertStatistic(emotionId.value as number, 'reviewed')
          } else if (biggerType === 3) {
            //console.log(`Emotion ${emotionId.value} has been reviewed.`)
            insertStatistic(emotionId.value as number, 'reviewed')
          }
          //console.log(`Bigger type for emotion ${emotionId.value}: ${biggerType}`)
        }
        // Placeholder: process the learning statistics for this emotion if needed
      } else {
        errorMessageToastText.value = `Errore nel caricamento delle statistiche di apprendimento.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching learning statistics:', error)
      errorMessageToastText.value = `Errore nel caricamento delle statistiche di apprendimento.`
      errorMessageToastRef.value = true
    })
}

function insertStatistic(
  emotionId: number,
  type: 'not-started' | 'learning' | 'learned' | 'reviewed',
): void {
  apiService
    .insertLearningStatistics(emotionId, type)
    .then((response) => {
      if (response && (response.status === 201 || response.status === 204)) {
        // Successfully inserted learning statistic, now update the local state
        console.log('Inserted successful', response)
      } else {
        errorMessageToastText.value = `Errore nell'inserimento della statistica di apprendimento.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error inserting learning statistic:', error)
      errorMessageToastText.value = `Errore nell'inserimento della statistica di apprendimento.`
      errorMessageToastRef.value = true
    })
}

function loadContents(): void {
  isLoading.value = true
  apiService.getLearningContentsStatistics(emotionId.value as number).then((response) => {
    if (response && response.status === 200) {
      console.log(response)
      // if(response.data?.length === 1) {
      //   console.log('Learning contents statistics for emotion', emotionId.value, ':', response.data[0])
      // } else if (response.data?.length === 0) {
      //   console.log('No learning contents statistics available for emotion', emotionId.value)
      // } else {
      //   console.log('Learning contents statistics data is null or in unexpected format for emotion', emotionId.value)
      // }
      learningContentsStatistics.value = response.data ?? null
    } else {
      errorMessageToastText.value = `Errore nel caricamento dei contenuti di apprendimento.`
      errorMessageToastRef.value = true
    }
    isLoading.value = false
  })
}

function openPath() {
  //router.push('/learning/emotion/' + emotionId.value + '/path')
  errorMessageToastRef.value = true
  errorMessageToastText.value = `La sezione "Percorso guidato" è in fase di sviluppo. Torna più tardi!`
}

function openPills() {
  //router.push('/learning/emotion/' + emotionId.value + '/pills')
  errorMessageToastRef.value = true
  errorMessageToastText.value = `La sezione "Nozioni in pillole" è in fase di sviluppo. Torna più tardi!`
}

function goBack() {
  router.back()
}

function getPercentageProgress(type: 'path' | 'pills'): number {
  if (learningContentsStatistics.value === null) {
    return 0
  }
  const contents =
    type === 'path' ? learningContentsStatistics.value.path : learningContentsStatistics.value.pills
  if (contents.length === 0) {
    return 0
  }
  const completedContents = contents.filter((content) => content.done === true).length
  return Math.round((completedContents / contents.length) * 100)
}
</script>

<template>
  <topbar variant="standard" :show-back-button="true" @onback="goBack" title=""></topbar>
  <div class="header-emotion">
    <div class="emotion-name">
      {{ emotionDetails?.['emotion-text'] ?? '' }}
    </div>
    <div class="follow-button" v-if="emotionDetails && emotionDetails['is-followed'] !== undefined">
      <button-generic
        :text="emotionDetails['is-followed'] ? 'Smetti di seguire' : 'Segui'"
        variant="white"
        icon-position="end"
        :icon="emotionDetails['is-followed'] ? 'remove-circle' : 'plus-circle'"
        :small="true"
        @action="toggleFollowEmotion"
      />
    </div>
  </div>
  <main>
    <div class="loading-contents" v-if="isLoading">
      <spinner color="primary" />
    </div>
    <div
      class="no-contents"
      v-else-if="
        !isLoading &&
        (learningContentsStatistics === null ||
          (learningContentsStatistics &&
            learningContentsStatistics.path.length === 0 &&
            learningContentsStatistics.pills.length === 0))
      "
    >
      <text-paragraph align="center" color="primary">
        Non ci sono contenuti di apprendimento disponibili per questa emozione. Torna più tardi!
      </text-paragraph>
    </div>

    <div
      class="card-container card-guided-path"
      v-if="
        !isLoading &&
        learningContentsStatistics !== null &&
        learningContentsStatistics.path.length > 0
      "
    >
      <div class="banner">
        <img src="@/assets/images/banner-path.png" />
      </div>
      <div class="text">
        <h1>Percorso guidato</h1>
        <div class="button">
          <button-generic
            text=""
            icon="forward"
            :disabled-hover-effect="true"
            :small="true"
            @action="openPath"
          />
        </div>
      </div>
      <div class="progress">
        <progressbar :progress="getPercentageProgress('path')" variant="primary"></progressbar>
      </div>
    </div>
    <div
      class="card-container card-pills"
      v-if="
        !isLoading &&
        learningContentsStatistics !== null &&
        learningContentsStatistics.pills.length > 0
      "
    >
      <div class="banner">
        <img src="@/assets/images/banner-pills.png" />
      </div>
      <div class="text">
        <h1>Nozioni in pillole</h1>
        <div class="button">
          <button-generic
            text=""
            icon="forward"
            :disabled-hover-effect="true"
            :small="true"
            @action="openPills"
          />
        </div>
      </div>
      <div class="progress">
        <progressbar :progress="getPercentageProgress('pills')" variant="primary"></progressbar>
      </div>
    </div>
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
  padding: var(--no-padding);
}

.header-emotion {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: var(--spacing);
  padding: var(--padding-16);
  width: 100%;
  background-color: var(--primary);

  .emotion-name {
    font: var(--font-subtitle);
    color: var(--on-primary);
    flex: 1;
    text-transform: capitalize;
  }
  .follow-button {
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

main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing);
  padding: var(--padding);

  .card-container {
    display: flex;
    flex-direction: column;
    gap: var(--no-spacing);
    padding: var(--no-padding);
    background-color: var(--color-blue-10);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;

    > .banner {
      width: 100%;
      height: 150px;
      overflow: hidden;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }

    > .text {
      width: 100%;
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      padding: var(--padding-16);

      h1 {
        font: var(--font-title);
        color: var(--color-black);
        flex: 1;
      }
      .button {
        display: flex;
        align-items: center;
        justify-content: center;
      }
    }
    > .progress {
      padding: var(--padding-16);
      width: 100%;
    }
  }
}
</style>
