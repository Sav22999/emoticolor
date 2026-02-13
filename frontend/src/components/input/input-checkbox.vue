<script setup lang="ts">
import { onMounted, ref } from 'vue'
import IconGeneric from '@/components/icon/icon-generic.vue'

const enabled = ref<boolean>(false)

const props = withDefaults(
  defineProps<{
    text?: string
    disabled?: boolean
    enabledByDefault?: boolean
  }>(),
  {
    text: 'Checkbox',
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
    <div class="icon">
      <icon-generic name="mark-yes" size="16px" v-if="enabled" />
    </div>
    <div class="text">
      {{ props.text }}
    </div>
  </div>
</template>

<style scoped lang="scss">
.chip {
  display: flex;
  align-items: center;
  padding: var(--padding-8);
  color: var(--primary);
  background-color: transparent;
  gap: var(--spacing-8);
  font: var(--font-label);
  width: auto;
  border-radius: var(--border-radius-8);
  flex-direction: row;

  cursor: pointer;
  user-select: none;
  transition: 0.1s all;

  > .text {
    order: 2;
    width: auto;
  }

  > .icon {
    order: 1;
    width: 20px;
    height: 20px;
    border-radius: var(--border-radius-4);
    border: 1px solid var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &.disabled {
    color: var(--color-gray-50);
    cursor: not-allowed;

    > .icon {
      border-color: var(--color-gray-50);
    }
  }
}
</style>
