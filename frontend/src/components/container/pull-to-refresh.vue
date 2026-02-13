<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import Spinner from '@/components/spinner.vue'
import TextInfo from '@/components/text/text-info.vue'

const props = withDefaults(
  defineProps<{
    isRefreshing: boolean
    textNoRefreshEnabled?: string
    textRefreshEnabled?: string
  }>(),
  {
    isRefreshing: false,
    textNoRefreshEnabled: 'Continua a trascinare in basso per aggiornare',
    textRefreshEnabled: 'Rilascia per aggiornare',
  },
)

const emit = defineEmits<{
  refresh: []
  scrolled: [value: boolean]
}>()

let startY = 0
let isPulling = false
const pullDistance = ref(0)
const currentScrolled = ref(false)

onMounted(() => {
  window.addEventListener('scroll', handleScrollPosition)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScrollPosition)
})

function handleScrollPosition() {
  const scrolled = window.scrollY > 0
  if (scrolled !== currentScrolled.value) {
    currentScrolled.value = scrolled
    emit('scrolled', scrolled)
  }
}

function onTouchStart(e: TouchEvent) {
  if (window.scrollY === 0 && e.touches.length > 0) {
    startY = e.touches[0]!.clientY
    isPulling = true
  }
}

function onTouchMove(e: TouchEvent) {
  if (!isPulling || e.touches.length === 0) return
  const currentY = e.touches[0]!.clientY
  pullDistance.value = Math.max(0, currentY - startY)
  if (pullDistance.value > 0) {
    e.preventDefault()
  }
}

function onTouchEnd() {
  if (!isPulling) return
  if (pullDistance.value > 100) {
    emit('refresh')
  }
  isPulling = false
  pullDistance.value = 0
}
</script>

<template>
  <div class="wrapper">
    <div
      class="background-text"
      v-if="isPulling && !props.isRefreshing && pullDistance > 20"
      :class="{ refresh: pullDistance >= 100 }"
    >
      <text-info v-if="pullDistance < 100" :show-icon="true">
        {{ props.textNoRefreshEnabled }}
      </text-info>
      <text-info icon="refresh" v-else>
        {{ props.textRefreshEnabled }}
      </text-info>
    </div>
    <div
      class="pull-to-refresh"
      @touchstart="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"
      :style="{ marginTop: `${Math.min(pullDistance * 0.5, 50)}px` }"
    >
      <div class="refresh-indicator" v-if="props.isRefreshing">
        <spinner color="primary" />
      </div>
      <slot />
    </div>
  </div>
</template>

<style scoped lang="scss">
.pull-to-refresh {
  transition: margin-top 0.3s ease;
  z-index: 2;

  .refresh-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--spacing-16);
    gap: var(--spacing-8);
    z-index: 2;
  }
}

.wrapper {
  position: relative;
  flex: 1;
}

.background-text {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  text-align: center;
  padding: var(--spacing-16);
  font: var(--font-small);
  color: var(--primary);
  background: linear-gradient(to bottom, var(--color-blue-10), var(--no-color));
  z-index: 1;
  transition: 0.2s background;

  &.refresh {
    color: inherit;
    background: linear-gradient(to bottom, var(--color-blue-20), var(--no-color));
  }
}
</style>
