<script setup lang="ts">
const props = defineProps<{
  isLoading: boolean
  hasMore: boolean
}>()

const emit = defineEmits<{
  loadMore: []
}>()

function handleScroll() {
  if (props.isLoading || !props.hasMore) return
  const threshold = 100 // pixels before bottom
  if (window.scrollY + window.innerHeight >= document.body.scrollHeight - threshold) {
    emit('loadMore')
  }
}

function addScrollListener() {
  window.addEventListener('scroll', handleScroll)
}

function removeScrollListener() {
  window.removeEventListener('scroll', handleScroll)
}

// Expose functions to parent for onMounted/onUnmounted
defineExpose({
  addScrollListener,
  removeScrollListener
})
</script>

<template>
  <div class="infinite-scroll">
    <slot />
  </div>
</template>

<style scoped lang="scss">
.infinite-scroll {
  /* No specific styles needed */
}
</style>
