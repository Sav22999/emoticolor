<script setup lang="ts">
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import IconGeneric from '@/components/icon/icon-generic.vue'

const visibilePassword = ref<boolean>(false)
const value = ref<string>('')

const props = withDefaults(
  defineProps<{
    showSearchButton?: boolean
    placeholder?: string
    text?: string
    minLength?: number
    maxLength?: number
    visibleByDefault?: boolean
    charsAllowed?: string
    charsDisallowed?: string
    errorStatus?: boolean
  }>(),
  {
    showSearchButton: false,
    placeholder: 'Enter your password',
    text: '',
    minLength: 10,
    maxLength: 256,
    visibleByDefault: false,
    charsAllowed: undefined,
    charsDisallowed: undefined,
    errorStatus: false,
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
    emit('input', value.value)
  }
}
</script>

<template>
  <div class="input" :class="{ 'input-error': props.errorStatus }">
    <icon-generic name="password" size="18px" class="icon-label" />
    <input
      :type="visibilePassword ? 'text' : 'password'"
      :placeholder="props.placeholder"
      :value="value"
      @input="onInput(($event.target as HTMLInputElement).value)"
    />
    <icon-generic
      name="show"
      size="18px"
      v-if="!visibilePassword"
      @click="visibilePassword = !visibilePassword"
    />
    <icon-generic name="hide" size="18px" v-else @click="visibilePassword = !visibilePassword" />
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

  &.input-error {
    background-color: var(--color-red-10);
    color: var(--color-red-70);

    ::placeholder,
    ::-webkit-input-placeholder {
      color: var(--color-red-30) !important;
    }
  }
}
</style>
