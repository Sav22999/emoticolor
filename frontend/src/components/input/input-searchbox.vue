<script setup lang="ts">
import { onMounted, ref } from 'vue'
import InputGeneric from '@/components/input/input-generic.vue'

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
    showSearchIcon?: boolean
  }>(),
  {
    placeholder: 'Search…',
    text: '',
    debounceTime: 300,
    minLength: 3,
    maxLength: 256,
    charsAllowed: undefined,
    charsDisallowed: undefined,
    showSearchIcon: true,
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
  emit('input', keyword)
}
</script>

<template>
  <input-generic
    :text="props.text"
    :icon="props.showSearchIcon ? 'search' : ''"
    @input="onInput($event)"
    :placeholder="props.placeholder"
    :min-length="props.minLength"
    :max-length="props.maxLength"
    :chars-allowed="props.charsAllowed"
    :chars-disallowed="props.charsDisallowed"
    :debounce-time="props.debounceTime"
  ></input-generic>
</template>

<style scoped lang="scss">
</style>
