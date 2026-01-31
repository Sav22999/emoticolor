<script setup lang="ts">
import { onMounted } from 'vue'
import type { ButtonType, IconType } from '@/utils/types.ts'
import IconGeneric from '@/components/icon/icon-generic.vue'

const props = withDefaults(
  defineProps<{
    variant?: ButtonType
    icon?: IconType
    iconPosition?: 'start' | 'end'
    placeholder?: string
    text?: string
    small?: boolean
    fullWidth?: boolean
    align?: 'start' | 'center' | 'end' | 'space'
    disabledHoverEffect?: boolean
    disabled?: boolean
    noBorderRadius?: boolean
  }>(),
  {
    variant: 'primary',
    icon: '',
    iconPosition: 'end',
    placeholder: 'Press button',
    text: '',
    small: false,
    fullWidth: false,
    align: 'center',
    disabledHoverEffect: false,
    disabled: false,
    noBorderRadius: false,
  },
)

onMounted(() => {})

const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'action'): void
}>()

function onAction() {
  if (!props.disabled) {
    emit('action')
  }
}
</script>

<template>
  <div
    class="button"
    @click="onAction"
    :class="{
      primary: props.variant === 'primary',
      cta: props.variant === 'cta',
      outline: props.variant === 'outline',
      simple: props.variant === 'simple',
      warning: props.variant === 'warning',
      small: props.small,
      'icon-start': props.iconPosition === 'start',
      'icon-end': props.iconPosition === 'end',
      'full-width': props.fullWidth,
      'align-start': props.align === 'start',
      'align-center': props.align === 'center',
      'align-end': props.align === 'end',
      'align-space': props.align === 'space',
      'no-hover': props.disabledHoverEffect,
      disabled: props.disabled,
      'no-border-radius': props.noBorderRadius,
    }"
  >
    <div class="label" v-if="props.text !== ''">{{ props.text }}</div>
    <icon-generic v-if="props.icon !== ''" :name="props.icon" size="18px" />
  </div>
</template>

<style scoped lang="scss">
.button {
  border-radius: var(--border-radius-8);

  display: flex;
  flex-direction: row;
  gap: var(--spacing-16);
  padding: var(--padding-16);
  font: var(--font-label);

  transition: 0.1s all;

  cursor: pointer;
  user-select: none;

  .label {
    order: 2;
    text-align: center;
  }

  &.icon-start {
    > .icon {
      order: 1;
    }
  }
  &.icon-end {
    > .icon {
      order: 3;
    }
  }

  &.full-width {
    width: 100%;
  }

  &.small {
    padding: var(--padding-8);
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
  &.align-space {
    justify-content: space-between;
  }

  &:not(.outline) {
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
  }
  &.no-hover:not(.outline) {
    border-left: 0px solid transparent;
    border-right: 0px solid transparent;
  }

  &.primary {
    background-color: var(--primary);
    color: var(--on-primary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-blue-70);
      border-right: 4px solid var(--color-blue-70);
      background-color: var(--color-blue-60);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.cta {
    background-color: var(--secondary);
    color: var(--on-secondary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-green-70);
      border-right: 4px solid var(--color-green-70);
      background-color: var(--color-green-60);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.outline {
    background-color: var(--transparent);
    color: var(--primary);
    border: 1px solid var(--primary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 5px solid var(--primary);
      border-right: 5px solid var(--primary);
      background-color: var(--color-blue-10);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      border: 1px solid var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.simple {
    background-color: var(--transparent);
    color: var(--primary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-blue-20);
      border-right: 4px solid var(--color-blue-20);
      background-color: var(--color-blue-10);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.warning {
    background-color: var(--color-red-60);
    color: var(--color-white);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-red-80);
      border-right: 4px solid var(--color-red-80);
      background-color: var(--color-red-70);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.no-border-radius {
    border-radius: 0;
  }
}
</style>
