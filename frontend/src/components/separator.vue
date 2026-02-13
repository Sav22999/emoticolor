<script setup lang="ts">
import { onMounted } from 'vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'secondary40'
  }>(),
  {
    variant: 'primary',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    class="separator"
    :class="{ primary: props.variant === 'primary', secondary: props.variant === 'secondary' }"
  ></div>
</template>

<style scoped lang="scss">
.separator {
  width: 100%;
  height: 1px;
  background-color: var(--color-blue-20);

  &.secondary {
    background-color: var(--color-green-20);
  }

  &.secondary40 {
    background-color: var(--color-green-40);
  }
}
</style>
