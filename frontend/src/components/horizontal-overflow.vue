<script setup lang="ts">
import { nextTick, onMounted, ref } from 'vue'

const overflowStart = ref<boolean>(false)
const overflowEnd = ref<boolean>(false)
const listRef = ref<HTMLElement>()

onMounted(() => {
  nextTick(() => updateOverflow())
})

function updateOverflow() {
  const el = listRef.value
  if (el) {
    overflowStart.value = el.scrollLeft > 0
    overflowEnd.value = el.scrollLeft + el.clientWidth < el.scrollWidth
  }
}

defineExpose({
  updateOverflow,
})
</script>

<template>
  <div class="horizontal-overflow">
    <div class="shadow-in-start" v-if="overflowStart"></div>
    <div class="shadow-in-end" v-if="overflowEnd"></div>
    <div class="list" ref="listRef" @scroll="updateOverflow">
      <slot />
    </div>
  </div>
</template>

<style scoped lang="scss">
.horizontal-overflow {
  position: relative;
  width: 100%;

  .shadow-in-start {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 40px;
    background: linear-gradient(to right, var(--color-blue-10), var(--no-color));
    pointer-events: none;
    z-index: 2;
  }
  .shadow-in-end {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 40px;
    background: linear-gradient(to left, var(--color-blue-10), var(--no-color));
    pointer-events: none;
    z-index: 2;
  }

  .list {
    display: flex;
    flex-direction: row;
    gap: var(--spacing-4);

    width: auto;
    overflow-x: auto;

    scrollbar-width: none;
    &::-webkit-scrollbar {
      display: none;
    }

    position: absolute;
    left: 0;
    right: 0;

    z-index: 1;
  }
}
</style>
