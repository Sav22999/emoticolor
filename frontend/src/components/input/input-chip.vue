<script setup lang="ts">
import { onMounted, ref } from 'vue'
import markYesIcon from '@/assets/icons/mark-yes.svg?component'

const enabled = ref<boolean>(false)

const props = withDefaults(
  defineProps<{
    text?: string
    disabled?: boolean
    enabledByDefault?: boolean
  }>(),
  {
    text: 'Chip',
    disabled: false,
    enabledByDefault: false,
  },
)

onMounted(() => {
  enabled.value = props.enabledByDefault ?? false
})

const emit = defineEmits<{
  (e: 'toggle', value: boolean): void
}>()

function onToggle() {
  if (!props.disabled) {
    enabled.value = !enabled.value
    emit('toggle', enabled.value)
  }
}
</script>

<template>
  <div class="chip" @click="onToggle" :class="{ enabled: enabled, disabled: disabled }">
    <div class="text">
      {{ props.text }}
    </div>
    <div class="icon" v-if="enabled">
      <mark-yes-icon />
    </div>
  </div>
</template>

<style scoped lang="scss">
.chip {
  display: flex;
  align-items: center;
  padding: var(--padding-8);
  color: var(--primary);
  background-color: var(--color-blue-10);
  gap: var(--spacing-8);
  font: var(--font-label);
  width: auto;
  border-radius: var(--border-radius-8);
  flex-direction: row;

  cursor: pointer;
  user-select: none;
  transition: 0.1s all;

  > .text {
    order: 1;
    width: auto;
  }

  > .icon {
    order: 2;
    width: 16px;
    height: 16px;
  }

  &.enabled {
    background-color: var(--primary);
    color: var(--on-primary);
  }

  &.disabled {
    background-color: var(--color-gray-20);
    color: var(--color-gray-50);
    cursor: not-allowed;
  }
}
</style>
