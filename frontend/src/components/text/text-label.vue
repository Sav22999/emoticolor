<script setup lang="ts">
import { onMounted } from 'vue'
import publicIcon from '@/assets/icons/public.svg?component'
import privateIcon from '@/assets/icons/private.svg?component'

const props = withDefaults(
  defineProps<{
    text?: string
    icon?: '' | 'public' | 'private'
    color?: 'primary' | 'blue70'
  }>(),
  {
    text: 'Label',
    icon: '',
    color: 'primary',
  },
)

onMounted(() => {})
</script>

<template>
  <div class="text-label" :class="{ blue70: props.color === 'blue70' }">
    <div class="icon" v-if="icon !== ''">
      <public-icon v-if="props.icon === 'public'" />
      <private-icon v-else-if="props.icon === 'private'" />
    </div>
    <div class="text">
      {{ props.text }}
    </div>
  </div>
</template>

<style scoped lang="scss">
.text-label {
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  font: var(--font-label);
  gap: var(--spacing-8);
  padding: var(--padding-8);
  color: var(--primary);
  background-color: var(--on-primary);
  border-radius: var(--border-radius);
  width: auto;

  user-select: none;

  > .text {
    order: 2;
  }

  > .icon {
    order: 1;
    width: 16px;
    height: 16px;
  }

  &.blue70 {
    color: var(--color-blue-70);
  }
}
</style>
