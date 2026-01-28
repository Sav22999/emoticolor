<script setup lang="ts">
import { onMounted } from 'vue'

const props = withDefaults(
  defineProps<{
    align?: 'start' | 'center' | 'end' | 'justify'
    color?: 'primary' | 'secondary' | 'white' | 'black'
  }>(),
  {
    align: 'center',
    color: 'primary',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    class="text-paragraph"
    :class="{
      'align-start': props.align === 'start',
      'align-center': props.align === 'center',
      'align-end': props.align === 'end',
      'align-justify': props.align === 'justify',
      'color-primary': props.color === 'primary',
      'color-secondary': props.color === 'secondary',
      'color-white': props.color === 'white',
      'color-black': props.color === 'black',
    }"
  >
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

  &.color-primary {
    color: var(--primary);
  }
  &.color-secondary {
    color: var(--secondary);
  }
  &.color-white {
    color: var(--color-white);
  }
  &.color-black {
    color: var(--color-black);
  }
}
</style>
