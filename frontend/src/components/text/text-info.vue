<script setup lang="ts">
import { onMounted } from 'vue'
import infoIcon from '@/assets/icons/info.svg?component'

const props = withDefaults(
  defineProps<{
    text?: string
  }>(),
  {
    text: 'Info',
  },
)

onMounted(() => {})
</script>

<template>
  <div class="text-info">
    <div class="icon">
      <info-icon></info-icon>
    </div>
    <div class="text">
      {{ props.text }}
    </div>
  </div>
</template>

<style scoped lang="scss">
.text-info {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  font: var(--font-small);
  gap: var(--spacing-8);
  color: var(--primary);

  > .text {
  }

  > .icon {
    width: 16px;
    height: 16px;
  }
}
</style>
