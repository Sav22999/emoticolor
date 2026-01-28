<script setup lang="ts">
import { onMounted } from 'vue'

const props = withDefaults(
  defineProps<{
    align?: 'start' | 'center' | 'end' | 'justify'
  }>(),
  {
    align: 'center',
  },
)

onMounted(() => {})
</script>

<template>
  <div class="text-paragraph" :class="`align-${props.align}`">
    <slot />
  </div>
</template>

<style scoped lang="scss">
.text-paragraph {
  display: block;
  font: var(--font-paragraph);
  color: var(--primary);
  width: 100%;

  &.align-start {
    text-align: start;
  }
  &.align-center {
    text-align: center;
  }
  &.align-end {
    text-align: end;
  }
  &.align-justify {
    text-align: justify;
  }
}
</style>
