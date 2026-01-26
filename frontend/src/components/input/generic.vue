<script setup lang="ts">
import { onMounted, ref } from 'vue'
import searchIcon from '@/assets/icons/search.svg?component'
import emailIcon from '@/assets/icons/email.svg?component'
import userIcon from '@/assets/icons/user.svg?component'

defineOptions({ name: 'emoticolor-searchbox' })

const timeoutRef = ref<NodeJS.Timeout | null>(null)
const value = ref<string>('')

const props = withDefaults(
  defineProps<{
    icon: string
    placeholder?: string
    text?: string
    debounceTime?: number
    minLength?: number
  }>(),
  {
    icon: 'email',
    placeholder: 'Enter your text…',
    text: '',
    debounceTime: 100,
    minLength: 0,
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
      :placeholder="props.placeholder !== '' ? props.placeholder : 'Enter your text…'"
      :value="value"
      @input="onInput($event.target?.value ?? '')"
    />
    <searchIcon class="icon icon-label" v-if="icon === 'search'"></searchIcon>
    <emailIcon class="icon icon-label" v-else-if="icon === 'email'"></emailIcon>
    <userIcon class="icon icon-label" v-else-if="icon === 'user'"></userIcon>
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

  &::placeholder {
    color: var(--color-blue-30);
  }

  input {
    all: unset;
    width: 100%;
    box-sizing: border-box;
    font: var(--font-label);
    line-height: var(--line-height-24);
    padding: var(--padding-8) var(--padding-8);
    padding-right: 0;
    flex: 1;
    order: 2;
  }

  .icon {
    order: 1;
    width: 16px;
    height: 16px;
    margin: var(--spacing-8) var(--spacing-16);
  }

  .icon-label {
    margin-right: var(--spacing-4);
  }
}
</style>
