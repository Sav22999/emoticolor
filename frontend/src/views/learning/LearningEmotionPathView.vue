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
import ButtonGeneric from '@/components/button/button-generic.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import Spinner from '@/components/spinner.vue'

const isLoading = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const emotionDetails = ref<emotionObjectInterface | undefined>(undefined)
const paths = ref<{ path: number; contents: learningContentInterface[] }[]>([])
const contents = ref<learningContentInterface[]>([])
const selectedPath = ref<number>(0)

const pillsAlreadyCompleted = ref<boolean>(false)

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
})

function onNextPath() {
  //check if the current path is the last one, if so do nothing
  if (isTheLastPath()) {
    //the last path, insert stats and then exit
    insertContentStatistics(emotionId.value as number, selectedPath.value)
    if (pillsAlreadyCompleted.value) insertStatistic(emotionId.value as number, 'learned')
    setTimeout(() => {
      if (pillsAlreadyCompleted.value) goToEmotions()
      else goBack()
    }, 100)
    //console.log('Last path reached, no more paths to load.')
  } else {
    //insert stats for the current path
    insertContentStatistics(emotionId.value as number, selectedPath.value)
    selectedPath.value++
    //console.log('Moved to next path, current path index:', selectedPath.value)
  }
}

function onPreviousPath() {
  if (selectedPath.value > 0) {
    selectedPath.value--
  } else {
    //console.log('Already at the first path, cannot go back further.')
  }
}

function isTheLastPath(): boolean {
  return selectedPath.value >= paths.value.length - 1
}

function insertContentStatistics(emotionId: number, type2: number | null = null) {
  apiService
    .insertLearningContentProgress(emotionId, 'path', type2)
    .then((res) => {
      if (res && res.status >= 200) {
        //console.log('Content statistics inserted successfully')
      } else if (res && res.status === 409) {
        console.warn('Content statistics already exists, no need to insert')
      } else {
        console.warn('Failed to insert content statistics:', res)
        //errorMessageToastText.value = `Errore nell'inserimento delle statistiche dei contenuti.`
        //errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.warn('Error inserting content statistics:', error)
      //errorMessageToastText.value = `Errore nell'inserimento delle statistiche dei contenuti.`
      //errorMessageToastRef.value = true
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
        // Successfully inserted learning statistic
        //console.log('Inserted successful', response)
      } else {
        console.warn('Unexpected response when inserting learning statistic:', response)
        // errorMessageToastText.value = `Errore nell'inserimento della statistica di apprendimento.`
        // errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.warn('Error inserting learning statistic:', error)
      // errorMessageToastText.value = `Errore nell'inserimento della statistica di apprendimento.`
      // errorMessageToastRef.value = true
    })
}

function loadContentStatistics(emotionId: number) {
  apiService
    .getLearningContentsStatistics(emotionId)
    .then((response) => {
      if (response && response.data && response.status === 200) {
        // Handle the response and update the state accordingly
        if (response.data.path) {
          //get the higher 'type2' value from the statistics and set it as selectedPath, if there is no path set it to 0
          response.data.path.forEach((item, index) => {
            if (
              item['type-level2'] !== null &&
              item.done &&
              item['type-level2'] >= selectedPath.value
            ) {
              //console.log(paths.value[item['type-level2']])
              //console.log(paths.value[item['type-level2'] + 1])
              if (
                paths.value[item['type-level2'] + 1] !== undefined &&
                index <= response.data.path.length - 1
              ) {
                //check if the next path exists in the paths array, if not set selectedPath to 0
                selectedPath.value = item['type-level2'] + 1
              } else if (index === response.data.path.length - 1) {
                //if it's the last item and the next path doesn't exist, set selectedPath to 0
                selectedPath.value = 0
              } else {
                //if the item is done but there is no type2, set selectedPath to 0
                selectedPath.value = item['type-level2']
              }
            }
          })
          //console.log(response.data.path)
        }

        if (response.data.pills) {
          //check if all pills are done, if so set pillsAlreadyCompleted to true
          //console.log(response.data.pills)
          const allPillsDone = response.data.pills.every(
            (pill: { 'type-level2': number | null; done: boolean }) => pill.done,
          )
          pillsAlreadyCompleted.value = allPillsDone
        }
      } else {
        errorMessageToastText.value = `${response.status} Errore nel caricamento delle statistiche dei contenuti.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching content statistics:', error)
      errorMessageToastText.value = `Errore nel caricamento delle statistiche dei contenuti.`
      errorMessageToastRef.value = true
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
    .getLearningContents(emotionId.value as number, 'path', null, true)
    .then((response) => {
      if (response && response.data && response.status === 200) {
        //console.log(response.data)

        if (response.data.length > 0) {
          contents.value = response.data[0]?.contents || []
        } else {
          contents.value = []
        }

        //populate the paths array with the contents, grouping them by type2
        const groupedByType2: { [key: number]: learningContentInterface[] } = {}
        contents.value.forEach((content) => {
          const type2 = content['type-level2']
          if (type2 !== null) {
            if (!groupedByType2[type2]) {
              groupedByType2[type2] = []
            }
            groupedByType2[type2].push(content)
          } else {
            //no type2
          }
        })
        paths.value = Object.keys(groupedByType2).map((type2Key) => ({
          path: Number(type2Key),
          contents: groupedByType2[Number(type2Key)] ?? [],
        }))

        loadContentStatistics(emotionId.value as number)
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

function getPhaseName(phase: number): string {
  switch (paths.value[phase]?.path ?? -1) {
    case 0:
      return 'Psicologia'
    case 1:
      return 'Fisiologia'
    case 2:
      return 'Importanza dei colori'
    case 3:
      return 'Curiosità dal mondo'
    default:
      return 'Fase sconosciuta'
  }
}

function goBack() {
  router.back()
}

function goToEmotions() {
  router.push('/learning')
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
    :title="`${capitalizeFirstLetter(emotionDetails?.['emotion-text'] ?? '')} – Percorso guidato`"
  ></topbar>
  <div class="progress-steps" v-if="!isLoading && paths.length > 0">
    <div
      v-for="(path, index) in paths.length"
      :key="index"
      class="progress-step"
      :class="{ active: index <= selectedPath }"
    >
      {{ index + 1 }}
    </div>
  </div>
  <div class="progress-steps-title" v-if="!isLoading && paths.length > 0">
    {{ getPhaseName(selectedPath) }}
  </div>
  <div class="loading-contents" v-if="isLoading">
    <spinner color="primary" />
  </div>
  <div
    class="no-contents"
    v-else-if="
      !isLoading &&
      (paths[selectedPath]?.contents === null || paths[selectedPath]?.contents.length === 0)
    "
  >
    <text-paragraph align="center">
      Non sono ancora presenti contenuti di apprendimento di questa tipologia per questa emozione.
    </text-paragraph>
  </div>
  <main
    v-else-if="
      !isLoading &&
      paths[selectedPath] &&
      paths[selectedPath]?.contents &&
      paths[selectedPath]!.contents.length > 0
    "
  >
    <card-learning
      v-for="content in paths[selectedPath]?.contents ?? []"
      :content="content"
      :key="content['learning-id']"
      :trigger="0"
    />
  </main>
  <div class="buttons-footer" v-if="!isLoading && paths.length > 0">
    <button-generic
      variant="primary"
      @click="onPreviousPath"
      v-if="selectedPath > 0"
      :disabled="selectedPath === 0"
      text="Indietro"
      icon="back"
      icon-position="start"
    />
    <button-generic
      variant="cta"
      @click="onNextPath"
      :text="isTheLastPath() ? 'Completa' : 'Avanti'"
      :icon="isTheLastPath() ? 'mark-yes' : 'forward'"
      icon-position="end"
    />
  </div>

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

.progress-steps {
  display: flex;
  flex-direction: row;
  gap: var(--no-spacing);
  padding: var(--no-padding);
  background-color: var(--secondary);
  width: 100%;

  .progress-step {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--padding);
    font: var(--font-subtitle);
    width: 100%;
    background-color: var(--color-green-10);
    color: var(--secondary);
    border-right: 1px solid var(--color-green-20);

    &:last-child {
      border-right: none;
    }
    &.active {
      background-color: var(--secondary);
      color: var(--on-secondary);
      border-right-color: var(--color-green-40);
    }
  }
}
.progress-steps-title {
  padding: var(--padding);
  text-align: center;
  background-color: var(--secondary);
  color: var(--on-secondary);
  font: var(--font-subtitle);
}

main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing);
  padding: var(--padding);
}

.buttons-footer {
  background-color: var(--color-blue-10);
  padding: var(--padding);
  display: flex;
  flex-direction: row;
  gap: var(--spacing);
  height: auto;
  width: 100%;

  > * {
    flex: 1;
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
