<script setup lang="ts">
import { ref } from 'vue'
import searchIcon from '@/assets/icons/search.svg?component'

defineOptions({ name: 'emoticolor-searchbox' })

const timeoutRef = ref<NodeJS.Timeout | null>(null)

const props = withDefaults(
  defineProps<{
    showSearchButton?: boolean
    placeholder?: string
    text?: string
    debounceTime?: number
    minLength?: number
  }>(),
  {
    showSearchButton: false,
    placeholder: 'Search…',
    text: '',
    debounceTime: 300,
    minLength: 3,
  },
)
const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'input', value: string): void
}>()

function onInput(keyword: string) {
  if (
    (props.minLength && keyword.length >= props.minLength) ||
    !props.minLength ||
    keyword.length === 0
  ) {
    if (props.debounceTime && props.debounceTime > 0) {
      if (timeoutRef.value) clearTimeout(timeoutRef.value)
      timeoutRef.value = setTimeout(() => {
        emit('input', keyword)
      }, props.debounceTime)
    } else {
      emit('input', keyword)
    }
  }
}
</script>

<template>
  <div class="input">
    <input
      type="text"
      :placeholder="props.placeholder !== '' ? props.placeholder : 'Search...'"
      :value="props.text"
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
  padding: 0;
  box-sizing: border-box;
  gap: 0;
  font: var(--font-inter);

  &::placeholder {
    color: var(--color-blue-30);
  }

  input {
    all: unset;
    width: 100%;
    box-sizing: border-box;
    font: var(--font-label);
    line-height: var(--line-height-24);
    padding: var(--padding-8) var(--padding-16);
    padding-right: 0;
    flex: 1;
  }

  .icon {
    width: 16px;
    height: 16px;
    margin: var(--spacing-8) var(--spacing-16);
  }
}
</style>
