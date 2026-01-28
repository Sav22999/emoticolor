<script setup lang="ts">
import { onMounted, ref } from 'vue'
import searchIcon from '@/assets/icons/search.svg?component'
import usefulFunctions from '@/utils/useful-functions.ts'

const timeoutRef = ref<NodeJS.Timeout | null>(null)
const value = ref<string>('')

const props = withDefaults(
  defineProps<{
    showSearchButton?: boolean
    placeholder?: string
    text?: string
    debounceTime?: number
    minLength?: number
    maxLength?: number
    charsAllowed?: string
  }>(),
  {
    showSearchButton: false,
    placeholder: 'Search…',
    text: '',
    debounceTime: 300,
    minLength: 3,
    maxLength: 256,
    charsAllowed: undefined,
  },
)
const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'input', value: string): void
}>()

onMounted(() => {
  value.value = props.text
})

function onInput(keyword: string) {
  value.value = keyword
  if (!usefulFunctions.checkAllowedChars(keyword, props.charsAllowed)) {
    value.value = usefulFunctions.removeDisallowedChars(value.value, props.charsAllowed)
  }
  if (value.value.length >= props.maxLength) {
    value.value = value.value.slice(0, props.maxLength)
  }
  if (
    (usefulFunctions.checkLength(value.value, props.minLength, props.maxLength) &&
      usefulFunctions.checkAllowedChars(value.value, props.charsAllowed)) ||
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
</script>

<template>
  <div class="input">
    <input
      type="text"
      :placeholder="props.placeholder"
      :value="value"
      @input="onInput($event.target?.value ?? '')"
    />
    <searchIcon class="icon"></searchIcon>
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
  padding: var(--padding-4);

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
    padding-left: var(--padding-12);
    padding-right: 0;
    flex: 1;
    order: 1;
  }

  .icon {
    width: 18px;
    height: 18px;
    margin: var(--spacing-8) var(--spacing-12);
    order: 2;
  }
}
</style>
