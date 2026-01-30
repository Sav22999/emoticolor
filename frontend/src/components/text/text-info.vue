<script setup lang="ts">
import { onMounted } from 'vue'
import IconGeneric from '@/components/icon/icon-generic.vue'

const props = withDefaults(
  defineProps<{
    showIcon?: boolean
  }>(),
  {
    showIcon: true,
  },
)

onMounted(() => {})
</script>

<template>
  <div class="text-info">
    <div class="icon" v-if="showIcon">
      <icon-generic name="info" size="16px"></icon-generic>
    </div>
    <div class="text">
      <slot />
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
    min-width: 16px;
    min-height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;

    > * {
      width: 100%;
      height: 100%;
    }
  }
}
</style>
