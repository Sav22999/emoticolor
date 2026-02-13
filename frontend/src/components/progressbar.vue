<script setup lang="ts">
import { onMounted } from 'vue'
import TextInfo from '@/components/text/text-info.vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'secondary40'
    progress: number // 0 to 100
  }>(),
  {
    variant: 'primary',
  },
)

onMounted(() => {})

function showCorrectProgress() {
  if (props.progress < 0) return 0
  if (props.progress > 100) return 100
  return props.progress
}
</script>

<template>
  <div class="progress-bar-container">
    <div class="progress-bar">
      <div
        class="progress-bar-inner"
        :class="{ primary: props.variant === 'primary', secondary: props.variant === 'secondary' }"
        :style="{ width: `${showCorrectProgress()}%` }"
      ></div>
    </div>
    <text-info :show-icon="false">{{ showCorrectProgress() }}%</text-info>
  </div>
</template>

<style scoped lang="scss">
.progress-bar-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-4);

  .progress-bar {
    width: 100%;
    height: 10px;
    background-color: var(--color-white-o60);
    border-radius: var(--border-radius);
    overflow: hidden;
    position: relative;

    .progress-bar-inner {
      position: absolute;
      top: 0;
      left: 0;
      bottom: 0;
      border-radius: var(--border-radius);

      &.primary {
        background-color: var(--primary);
      }

      &.secondary {
        background-color: var(--secondary);
      }
    }
  }
}
</style>
