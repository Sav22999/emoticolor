<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import Toast from '@/components/modal/toast.vue'
import type {
  ApiLearningStatisticsResponse,
  learningStatisticsInterface,
} from '@/utils/api/api-interface.ts'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import Spinner from '@/components/spinner.vue'

const isLoading = ref<boolean>(false)

const isScrolled = ref(false)
const isRefreshing = ref(false)
const refreshCounter = ref(0)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const learningStatistics = ref<learningStatisticsInterface[] | null>(null)
const learningStatisticsGrouped = ref<{ datetime: string; items: learningStatisticsInterface[] }[]>(
  [],
)

onMounted(() => {
  loadStatistics()
})

function loadStatistics(onFinished?: () => void): void {
  isLoading.value = true
  apiService
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
    })
}

/**
 * Group learning statistics by date (use the showDatetime function)
 */
function groupStatisticsByDate() {
  // Reset grouped result
  learningStatisticsGrouped.value = []

  if (!learningStatistics.value || learningStatistics.value.length === 0) return

  const grouped: { [key: string]: learningStatisticsInterface[] } = {}

  learningStatistics.value.forEach((item) => {
    // Defensive: item.created may be missing or invalid
    const dateKey = item && item.created ? showDatetime(item.created) : 'Sconosciuto'
    if (!grouped[dateKey]) {
      grouped[dateKey] = []
    }
    grouped[dateKey].push(item)
  })

  // Convert grouped object to array and sort groups by date (newest first)
  const groups: { datetime: string; items: learningStatisticsInterface[] }[] = Object.keys(
    grouped,
  ).map((dateStr) => ({
    datetime: dateStr,
    items: grouped[dateStr]!,
  }))

  // Helper to parse our DD/MM/YYYY format into a timestamp for sorting; unknown dates go last
  function parseGroupDate(d: string): number {
    if (!d || d === 'Sconosciuto') return 0
    const parts = d.split('/')
    if (parts.length !== 3) return 0
    const day = parseInt(parts[0]!, 10)
    const month = parseInt(parts[1]!, 10) - 1
    const year = parseInt(parts[2]!, 10)
    const dt = new Date(year, month, day)
    return isNaN(dt.getTime()) ? 0 : dt.getTime()
  }

  // Sort items inside each group by created timestamp (newest first) and then sort groups
  groups.forEach((g) => {
    // g.items is guaranteed by the typing above
    g.items.sort((a, b) => {
      const ta = a && a.created ? new Date(a.created).getTime() : 0
      const tb = b && b.created ? new Date(b.created).getTime() : 0
      return tb - ta
    })
  })

  groups.sort((a, b) => parseGroupDate(b.datetime) - parseGroupDate(a.datetime))

  learningStatisticsGrouped.value = groups
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
  // Call loadStatistics and reset isRefreshing via callback when done
  loadStatistics(() => {
    isRefreshing.value = false
  })
}

function goBack() {
  router.back()
}

/**
 * Return a formatted date string in the format DD mm YYYY (eg. 25 march 2024) in italian
 * @param dateString - The date string to format
 */
function showDatetime(dateString?: string | null): string {
  if (!dateString) return 'Sconosciuto'
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return 'Sconosciuto'

  const options: Intl.DateTimeFormatOptions = {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }
  return date.toLocaleDateString('it-IT', options)
}

function statisticsLabel(type: 0 | 1 | 2 | 3): string {
  switch (type) {
    case 0:
      return 'Non iniziata'
    case 1:
      return 'Iniziata'
    case 2:
      return 'Conclusa'
    case 3:
      return 'Ripetuta'
    default:
      return ''
  }
}
</script>

<template>
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goBack"
    title="Statistiche sull'apprendimento"
  ></topbar>
  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="refreshContents"
    @scrolled="isScrolled = $event"
  >
    <div class="no-contents" v-if="!isLoading && learningStatisticsGrouped?.length === 0">
      <text-paragraph align="center">
        Non ci sono statistiche di apprendimento disponibili.
      </text-paragraph>
    </div>
    <div class="loading-contents" v-if="isLoading">
      <spinner color="primary" />
    </div>
    <main v-if="learningStatisticsGrouped?.length > 0 && !isLoading">
      <div class="main" v-for="group in learningStatisticsGrouped" :key="group.datetime">
        <h2>{{ group.datetime }}</h2>
        <div
          class="card-learning-statistic"
          v-for="item in group.items"
          :key="item['statistic-id']"
        >
          <div class="text">
            {{ item['emotion-text'] }}
          </div>
          <div class="label">
            {{ statisticsLabel(item.type) }}
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

    .card-learning-statistic {
      border: 0 solid transparent;
      padding: var(--padding-8);
      border-radius: var(--border-radius);
      background-color: var(--color-blue-10);

      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: center;
      width: 100%;
      gap: var(--spacing-16);

      > .text {
        flex: 1;
        font: var(--font-subtitle);
        color: var(--primary);
        text-transform: capitalize;
      }
      > .label {
        font: var(--font-small);
        color: var(--primary);
        padding: var(--padding-8);
        border-radius: var(--border-radius-8);
        background-color: var(--color-white-o60);
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
  min-height: 200px;
}
</style>
