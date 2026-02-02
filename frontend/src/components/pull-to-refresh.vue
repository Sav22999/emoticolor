<script setup lang="ts">
import { ref } from 'vue'
import Spinner from '@/components/spinner.vue'
import TextInfo from '@/components/text/text-info.vue'

const props = defineProps<{
  isRefreshing: boolean
}>()

const emit = defineEmits<{
  refresh: []
}>()

let startY = 0
let isPulling = false
const pullDistance = ref(0)

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
    <div class="background-text" v-if="isPulling && !isRefreshing && pullDistance > 10">
      <text-info v-if="pullDistance < 100" :show-icon="true"
        >Continua a trascinare in basso per aggiornare</text-info
      >
      <text-info icon="refresh" v-else>Rilascia per aggiornare</text-info>
    </div>
    <div
      class="pull-to-refresh"
      @touchstart="onTouchStart"
      @touchmove="onTouchMove"
      @touchend="onTouchEnd"
      :style="{ transform: `translateY(${Math.min(pullDistance * 0.5, 50)}px)` }"
    >
      <div class="refresh-indicator" v-if="isRefreshing">
        <spinner color="primary" />
      </div>
      <slot />
    </div>
  </div>
</template>

<style scoped lang="scss">
.pull-to-refresh {
  position: relative;
  transition: transform 0.3s ease;
  z-index: 2;

  .refresh-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--spacing-16);
    gap: var(--spacing-8);
    z-index: 2;

    spinner {
      color: var(--primary);
    }
  }
}

.wrapper {
  position: relative;
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
  z-index: 1;
}
</style>
