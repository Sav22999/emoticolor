<script setup lang="ts">
import { onMounted, ref } from 'vue'
import passwordIcon from '@/assets/icons/password.svg?component'
import showIcon from '@/assets/icons/show.svg?component'
import hideIcon from '@/assets/icons/hide.svg?component'

const visibilePassword = ref<boolean>(false)
const value = ref<string>('')

const props = withDefaults(
  defineProps<{
    showSearchButton?: boolean
    placeholder?: string
    text?: string
    minLength?: number
    visibleByDefault?: boolean
  }>(),
  {
    showSearchButton: false,
    placeholder: 'Enter your password',
    text: '',
    minLength: 3,
    visibleByDefault: false,
  },
)
const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'input', value: string): void
}>()

onMounted(() => {
  visibilePassword.value = props.visibleByDefault ?? false
  value.value = props.text
})

function onInput(keyword: string) {
  value.value = keyword
  emit('input', value.value)
}
</script>

<template>
  <div class="input">
    <passwordIcon class="icon icon-label"></passwordIcon>
    <input
      :type="visibilePassword ? 'text' : 'password'"
      :placeholder="props.placeholder"
      :value="value"
      @input="onInput($event.target?.value ?? '')"
    />
    <showIcon
      class="icon"
      v-if="!visibilePassword"
      @click="visibilePassword = !visibilePassword"
    ></showIcon>
    <hideIcon class="icon" v-else @click="visibilePassword = !visibilePassword"></hideIcon>
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
    padding-right: 0;
    flex: 1;
  }

  .icon {
    width: 18px;
    height: 18px;
    margin: var(--spacing-8) var(--spacing-12);
    cursor: pointer;
  }

  .icon-label {
    margin-right: var(--spacing-4);
    cursor: default;
  }
}
</style>
