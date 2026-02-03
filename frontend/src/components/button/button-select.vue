<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import type { IconType } from '@/utils/types.ts'
import IconGeneric from '@/components/icon/icon-generic.vue'
import Spinner from '@/components/spinner.vue'

const valueSelected = ref<string>('')
const selected = ref<boolean>(false)

const props = withDefaults(
  defineProps<{
    variant?: 'text' | 'color'
    icon?: IconType | 'animated-loading'
    value?: string
    placeholder?: string
    capitalize?: boolean
    disabled?: boolean
  }>(),
  {
    variant: 'text',
    icon: '',
    value: '',
    placeholder: '',
    capitalize: false,
    disabled: false,
  },
)

onMounted(() => {
  valueSelected.value = props.value || ''
  if (valueSelected.value !== '') {
    selected.value = true
  }
})

watch(
  () => props.value,
  (newValue) => {
    valueSelected.value = newValue || ''
    selected.value = valueSelected.value !== ''
  },
)

const emit = defineEmits<{
  (e: 'onselect', value: string): void
}>()

function onSelect() {
  if (props.disabled) return
  emit('onselect', valueSelected.value)
}
</script>

<template>
  <div
    class="button-select"
    :class="{
      'variant-text': props.variant === 'text',
      'variant-color': props.variant === 'color',
      selected: selected,
      disabled: props.disabled,
    }"
    @click="onSelect"
  >
    <div class="icon" v-if="variant === 'text' && props.icon !== ''">
      <icon-generic :name="props.icon" size="18px" v-if="props.icon !== 'animated-loading'" />
      <spinner size="16px" color="blue70" v-else />
    </div>
    <div
      class="text placeholder"
      v-if="props.placeholder && props.placeholder !== '' && valueSelected === ''"
    >
      {{ props.placeholder }}
    </div>
    <div
      class="text"
      v-if="props.variant === 'text' && valueSelected !== ''"
      :class="{ capitalize: props.capitalize }"
    >
      {{ valueSelected }}
    </div>
    <div
      class="color"
      v-if="props.variant === 'color' && valueSelected !== ''"
      :style="{ 'background-color': `#${valueSelected}` }"
    ></div>
    <div class="vertical-separator"></div>
    <div class="icon">
      <icon-generic name="scrolling-v" size="18px" />
    </div>
  </div>
</template>

<style scoped lang="scss">
.button-select {
  display: flex;
  align-items: stretch;
  justify-content: center;
  gap: var(--spacing-8);
  padding: var(--padding-8);
  border-radius: var(--border-radius);
  cursor: pointer;
  width: auto;

  background-color: var(--color-blue-10);
  color: var(--color-blue-70);

  .icon {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .text {
    font: var(--font-label);
    display: flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    flex: 1;
    &.capitalize {
      text-transform: capitalize;
    }
  }
  .color {
    min-width: 40px;
    width: 100%;
    height: auto;
    border: 1px solid var(--color-white-o60);
    border-radius: var(--border-radius-4);
  }

  > .vertical-separator {
    width: 1px;
    height: auto;
    background-color: var(--color-blue-20);
  }

  .placeholder {
    color: var(--color-blue-30);
  }

  &.selected {
    background-color: var(--primary);
    color: var(--color-white);
  }

  &.disabled {
    cursor: not-allowed;
    background-color: var(--color-gray-20);
    color: var(--color-gray-50);

    .placeholder {
      color: var(--color-gray-40);
    }

    .vertical-separator {
      background-color: var(--color-gray-40);
    }
  }
}
</style>
