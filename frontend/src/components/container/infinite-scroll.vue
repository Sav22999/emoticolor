<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

const props = defineProps<{
  loading: boolean
  hasMore: boolean
}>()

const emit = defineEmits<{
  'load-more': []
}>()

let scrollElement: Element | Window = window
const infiniteScrollEl = ref<HTMLElement>()

function findScrollableParent(el: Element | null): Element | Window {
  while (el && el !== document.body) {
    const style = getComputedStyle(el)
    if (
      style.overflowY === 'auto' ||
      style.overflowY === 'scroll' ||
      style.overflow === 'auto' ||
      style.overflow === 'scroll'
    ) {
      return el
    }
    el = el.parentElement
  }
  return window
}

function handleScroll() {
  if (props.loading || !props.hasMore) return
  const threshold = 100 // pixels before bottom
  let scrollTop: number
  let clientHeight: number
  let scrollHeight: number
  if (scrollElement === window) {
    scrollTop = window.scrollY
    clientHeight = window.innerHeight
    scrollHeight = document.body.scrollHeight
  } else {
    scrollTop = (scrollElement as Element).scrollTop
    clientHeight = (scrollElement as Element).clientHeight
    scrollHeight = (scrollElement as Element).scrollHeight
  }
  if (scrollTop + clientHeight >= scrollHeight - threshold) {
    emit('load-more')
  }
}

function addScrollListener() {
  scrollElement.addEventListener('scroll', handleScroll)
}

function removeScrollListener() {
  scrollElement.removeEventListener('scroll', handleScroll)
}

// Expose functions to parent for onMounted/onUnmounted
defineExpose({
  addScrollListener,
  removeScrollListener,
})

onMounted(() => {
  scrollElement = findScrollableParent(infiniteScrollEl.value?.parentElement || null)
  addScrollListener()
})

onUnmounted(() => {
  removeScrollListener()
})
</script>

<template>
  <div ref="infiniteScrollEl" class="infinite-scroll">
    <slot />
  </div>
</template>

<style scoped lang="scss">
.infinite-scroll {
  /* No specific styles needed */
}
</style>
