<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import Toast from '@/components/modal/toast.vue'
import type { ApiEmotionResponse, emotionObjectInterface } from '@/utils/api/api-interface.ts'
import apiService from '@/utils/api/api-service.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const emotionDetails = ref<emotionObjectInterface | undefined>(undefined)

// before of onMounted set the emotionId
const emotionId = ref<number | null>(null)
// Try to read the route param synchronously so other logic can use it before onMounted
const rawParam = router.currentRoute?.value?.params?.emotionId
if (rawParam !== undefined && rawParam !== null && !isNaN(Number(rawParam))) {
  emotionId.value = Number(rawParam)
}

onMounted(() => {
  //get :emotionId from route params (use prepopulated ref)
  if (emotionId.value === null || isNaN(Number(emotionId.value))) {
    errorMessageToastText.value = `ID dell'emozione non valido.`
    errorMessageToastRef.value = true
    return
  }
  loadEmotionDetails(Number(emotionId.value))
  loadContents()
})

function loadEmotionDetails(emotionId: number) {
  apiService
    .getEmotions(emotionId)
    .then((response) => {
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

function loadContents(): void {
  // Placeholder: enable loading of learning statistics for this emotion if needed
  // The previous implementation is commented out; restore and adapt when required.
}

function goBack() {
  router.back()
}
</script>

<template>
  <topbar variant="standard" :show-back-button="true" @onback="goBack" title=""></topbar>
  <div class="header">
    <div class="emotion-name">
      {{ emotionDetails?.['emotion-text'] ?? '' }}
    </div>
    <div class="follow-button">
      <button-generic
        text="Segui"
        variant="white"
        icon-position="end"
        icon="plus-circle"
        :small="true"
      />
    </div>
  </div>
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
