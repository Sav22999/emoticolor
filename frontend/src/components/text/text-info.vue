<script setup lang="ts">
import { onMounted } from 'vue'
import IconGeneric from '@/components/icon/icon-generic.vue'
import type { IconType } from '@/utils/types.ts'

const props = withDefaults(
  defineProps<{
    showIcon?: boolean
    icon?: IconType
    align?: 'center' | 'start' | 'end'
  }>(),
  {
    showIcon: true,
    icon: 'info',
    align: 'center',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    class="text-info"
    :class="{
      'align-center': align === 'center',
      'align-start': align === 'start',
      'align-end': align === 'end',
    }"
  >
    <div class="icon" v-if="showIcon">
      <icon-generic :name="icon" size="16px"></icon-generic>
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

  &.align-center {
    justify-content: center;
    text-align: center;
  }
  &.align-start {
    justify-content: flex-start;
    text-align: start;
  }
  &.align-end {
    justify-content: flex-end;
    text-align: end;
  }
}
</style>
