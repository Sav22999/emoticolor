<script setup lang="ts">
import { onMounted, ref } from 'vue'
import type { IconSize, ReactionType } from '@/utils/types.ts'
import IconReaction from '@/components/icon/icon-reaction.vue'

const countToUse = ref<number>(1)

const props = withDefaults(
  defineProps<{
    id?: string
    variant?: 'primary' | 'blue10'
    readonly?: boolean
    reaction?: ReactionType
    count?: number
    text?: string
    size?: IconSize
  }>(),
  {
    id: undefined,
    variant: 'primary',
    readonly: false,
    reaction: '',
    count: 1,
    text: undefined,
    size: '18px',
  },
)

onMounted(() => {
  if (props.count && props.count > 0) {
    countToUse.value = props.count
  }
})

const emit = defineEmits<{
  (e: 'ontoggle', value: boolean): void
}>()

function onToggle() {
  if (!props.readonly) {
    emit('ontoggle', true)
  }
}
</script>

<template>
  <div
    class="button-reaction"
    v-if="countToUse > 0"
    :class="{
      'read-only': props.readonly,
      'variant-blue10': props.variant === 'blue10',
      'variant-primary': props.variant === 'primary',
    }"
    @click="onToggle"
  >
    <div class="text" v-if="props.text">{{ props.text }}</div>
    <icon-reaction :size="props.size" class="shadow-icon" :name="props.reaction" />
    <div class="vertical-separator" v-if="countToUse > 0 && props.readonly"></div>
    <div class="count" v-if="countToUse > 0 && props.readonly">
      {{ countToUse }}
    </div>
  </div>
</template>

<style scoped lang="scss">
.button-reaction {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-8);
  padding: var(--padding-8);
  border-radius: var(--border-radius);
  cursor: pointer;

  .text {
    font: var(--font-label);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  > .vertical-separator {
    width: 1px;
    height: auto;
    background-color: var(--color-blue-20);
  }

  .count {
    font-weight: var(--font-weight-bold);
  }

  > .shadow-icon {
    filter: drop-shadow(0px 0px var(--spacing-8) var(--color-blue-60));
  }

  &.read-only {
    cursor: default;
    background-color: var(--color-white-o60);
    color: var(--primary);
  }
  &.variant-blue10:not(.read-only) {
    background-color: var(--color-blue-10);
    color: var(--color-blue-70);
  }
  &.variant-primary:not(.read-only) {
    background-color: var(--primary);
    color: var(--color-white);
  }
}
</style>
