<script setup lang="ts">
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'

const timeoutRef = ref<NodeJS.Timeout | null>(null)
const value = ref<string>('')

const props = withDefaults(
  defineProps<{
    placeholder?: string
    text?: string
    debounceTime?: number
    minLength?: number
    maxLength?: number
    charsAllowed?: string
    charsDisallowed?: string
  }>(),
  {
    placeholder: 'Enter your text…',
    text: '',
    debounceTime: 100,
    minLength: 0,
    maxLength: 500,
    charsAllowed: undefined,
    charsDisallowed: undefined,
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
</script>

<template>
  <div class="input">
    <textarea
      :placeholder="props.placeholder"
      @input="onInput($event.target?.value ?? '')"
      :value="value"
    ></textarea>
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
  padding: 0;
  box-sizing: border-box;
  gap: 0;
  font: var(--font-inter);

  &::placeholder {
    color: var(--color-blue-30);
  }

  textarea {
    all: unset;
    resize: vertical;
    width: 100%;
    box-sizing: border-box;
    font: var(--font-label);
    line-height: var(--spacing-20);
    padding: var(--padding-16);
    padding-right: 0;
    flex: 1;
    min-height: 120px;
  }

  .icon {
    width: 16px;
    height: 16px;
    margin: var(--spacing-8) var(--spacing-16);
  }
}
</style>
