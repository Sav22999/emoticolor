<script setup lang="ts">
import { onMounted } from 'vue'
import IconGeneric from '@/components/icon/icon-generic.vue'

const props = withDefaults(
  defineProps<{
    text?: string
    icon?: '' | 'public' | 'private' | 'place' | 'location' | 'head' | 'people' | 'sun'
    iconPadding?: boolean
    color?: 'primary' | 'blue70'
    background?: 'white' | 'white-o60'
    align: 'start' | 'center' | 'end'
  }>(),
  {
    text: '',
    icon: '',
    iconPadding: false,
    color: 'primary',
    background: 'white',
    align: 'center',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    class="text-label"
    :class="{
      blue70: props.color === 'blue70',
      'bg-white-o60': props.background === 'white-o60',
      'icon-padding': props.iconPadding,
      'align-start': props.align === 'start',
      'align-center': props.align === 'center',
      'align-end': props.align === 'end',
    }"
  >
    <div class="icon" v-if="icon !== ''">
      <icon-generic :name="props.icon" size="18px" />
    </div>
    <div class="text" v-if="props.text !== ''">
      {{ props.text }}
    </div>
  </div>
</template>

<style scoped lang="scss">
.text-label {
  display: flex;
  flex-direction: row;
  align-items: center;
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
    width: auto;
    height: auto;
  }

  &.blue70 {
    color: var(--color-blue-70);
  }

  &.bg-white-o60 {
    background-color: var(--color-white-o60);
  }

  &.icon-padding {
    > .icon {
      padding: var(--padding-4);
    }
  }

  &.align-start {
    justify-content: start;
  }
  &.align-center {
    justify-content: center;
  }
  &.align-end {
    justify-content: end;
  }
}
</style>
