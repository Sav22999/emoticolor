<script setup lang="ts">
import Spinner from '@/components/spinner.vue'

const props = defineProps<{
  isRefreshing: boolean
}>()

const emit = defineEmits<{
  refresh: []
}>()

let startY = 0
let isPulling = false
let pullDistance = 0

function onTouchStart(e: TouchEvent) {
  if (window.scrollY === 0 && e.touches.length > 0) {
    startY = e.touches[0]!.clientY
    isPulling = true
  }
}

function onTouchMove(e: TouchEvent) {
  if (!isPulling || e.touches.length === 0) return
  const currentY = e.touches[0]!.clientY
  pullDistance = currentY - startY
  if (pullDistance > 0) {
    e.preventDefault()
  }
}

function onTouchEnd() {
  if (!isPulling) return
  if (pullDistance > 100) {
    emit('refresh')
  }
  isPulling = false
  pullDistance = 0
}
</script>

<template>
  <div
    class="pull-to-refresh"
    @touchstart="onTouchStart"
    @touchmove="onTouchMove"
    @touchend="onTouchEnd"
  >
    <div class="refresh-indicator" v-if="isRefreshing">
      <spinner color="primary" />
    </div>
    <slot />
  </div>
</template>

<style scoped lang="scss">
.pull-to-refresh {
  position: relative;

  .refresh-indicator {
    display: flex;
    justify-content: center;
    padding: var(--spacing-16);
  }
}
</style>
