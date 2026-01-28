<script setup lang="ts">
import { onMounted } from 'vue'

const props = withDefaults(
  defineProps<{
    text?: string
    disabled?: boolean
  }>(),
  {
    text: 'Click here',
    disabled: false,
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
  <a class="link" @click="onAction">{{ props.text }}</a>
</template>

<style scoped lang="scss">
.link {
  color: var(--primary);
  text-decoration: underline;
  font: var(--font-link);
  cursor: pointer;

  margin: auto;
}
</style>
