<script setup lang="ts">
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions'
import IconGeneric from '@/components/icon/icon-generic.vue'
import type { IconType } from '@/utils/types.ts'

const timeoutRef = ref<number | null>(null)
const value = ref<string>('')

const props = withDefaults(
  defineProps<{
    type?: 'text' | 'email' | 'url' | 'number'
    name?: string
    icon?: IconType
    iconPosition?: 'left' | 'right'
    placeholder?: string
    text?: string
    debounceTime?: number
    minLength?: number
    maxLength?: number
    charsAllowed?: string // undefined or a string of allowed characters
    charsDisallowed?: string // undefined or a string of disallowed characters
    errorStatus?: boolean
    disabled?: boolean
  }>(),
  {
    type: 'text',
    name: '',
    icon: 'email',
    iconPosition: 'left',
    placeholder: 'Enter your text…',
    text: '',
    debounceTime: 100,
    minLength: 0,
    maxLength: 256,
    charsAllowed: undefined,
    charsDisallowed: undefined,
    errorStatus: false,
    disabled: false,
  },
)
const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'input', value: string): void
  (e: 'onenter'): void
  (e: 'oniconclick'): void
}>()

onMounted(() => {
  value.value = props.text
})

function onInput(keyword: string) {
  if (props.disabled) return
  value.value = keyword
  if (!usefulFunctions.checkAllowedChars(keyword, props.charsAllowed, props.charsDisallowed)) {
    value.value = usefulFunctions.removeDisallowedChars(
      value.value,
      props.charsAllowed,
      props.charsDisallowed,
    )
  }
  if (value.value.length >= props.maxLength) {
    value.value = value.value.slice(0, props.maxLength)
  }
  if (
    (usefulFunctions.checkLength(value.value, props.minLength, props.maxLength) &&
      usefulFunctions.checkAllowedChars(value.value, props.charsAllowed, props.charsDisallowed)) ||
    value.value.length === 0
  ) {
    if (props.debounceTime && props.debounceTime > 0) {
      if (timeoutRef.value) clearTimeout(timeoutRef.value)
      timeoutRef.value = setTimeout(() => {
        emit('input', value.value)
      }, props.debounceTime)
    } else {
      emit('input', value.value)
    }
  }
}

function onKeydown(event: KeyboardEvent) {
  if (props.disabled) return
  if (event.key === 'Enter') {
    emit('onenter')
  }
}

function onIconClick() {
  if (props.disabled) return
  emit('oniconclick')
}
</script>

<template>
  <div class="input" :class="{ 'input-error': props.errorStatus, disabled: props.disabled }">
    <input
      type="text"
      :class="{
        'not-empty': value !== '',
        'position-left': props.iconPosition === 'right',
        'position-right': props.iconPosition === 'left',
      }"
      :name="props.name"
      :placeholder="props.placeholder"
      :value="value"
      @input="onInput(($event.target as HTMLInputElement)?.value ?? '')"
      @keydown="onKeydown($event)"
      :disabled="props.disabled"
    />
    <icon-generic
      :name="props.icon"
      size="18px"
      class="icon-label"
      @click="onIconClick"
      :class="{
        'position-left': props.iconPosition === 'left',
        'position-right': props.iconPosition === 'right',
      }"
    ></icon-generic>
  </div>
</template>

<style scoped lang="scss">
.input {
  border-radius: var(--border-radius-8);
  background-color: var(--color-blue-10);
  color: var(--primary);

  display: flex;
  flex-direction: row;
  align-items: center;
  position: relative;
  width: 100%;
  box-sizing: border-box;
  gap: 0;
  font: var(--font-inter);
  padding: var(--padding-8);

  ::placeholder,
  ::-webkit-input-placeholder {
    color: var(--color-blue-30) !important;
  }

  input {
    all: unset;
    width: 100%;
    box-sizing: border-box;
    font: var(--font-label);
    line-height: var(--line-height-24);
    padding: var(--padding-8) var(--padding-8);
    flex: 1;
    order: 2;
    color: inherit;
  }

  .icon {
    order: 1;
    width: 18px;
    height: 18px;
    margin: var(--spacing-8) var(--spacing-12);
  }

  &.input-error {
    background-color: var(--color-red-10);
    color: var(--color-red-70);

    ::placeholder,
    ::-webkit-input-placeholder {
      color: var(--color-red-30) !important;
    }
  }

  .not-empty {
    color: var(--color-blue-70);
  }

  .position-left {
    order: 1;
    &.icon-label {
      margin-right: var(--spacing-4);
    }
  }
  .position-right {
    order: 2;
    &.icon-label {
      margin-left: var(--spacing-4);
    }
  }

  &.disabled {
    background-color: var(--color-gray-20);
    cursor: not-allowed !important;
    pointer-events: none;
    color: var(--color-gray-50);

    ::placeholder,
    ::-webkit-input-placeholder {
      color: var(--color-gray-40) !important;
    }
  }
}
</style>
