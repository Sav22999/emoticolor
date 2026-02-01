<script setup lang="ts">
import { onMounted, ref } from 'vue'
import type { ReactionType } from '@/utils/types.ts'
import IconReaction from '@/components/icon/icon-reaction.vue'

const countToUse = ref<number>(1)

const props = withDefaults(
  defineProps<{
    id?: string
    readonly?: boolean
    reaction?: ReactionType
    count?: number
    text?: string
  }>(),
  {
    id: undefined,
    readonly: false,
    reaction: '',
    count: 1,
    text: undefined,
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
    :class="{ 'read-only': props.readonly }"
    @click="onToggle"
  >
    <div class="text" v-if="props.text">{{ props.text }}</div>
    <icon-reaction :name="props.reaction" />
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
  background-color: var(--primary);
  color: var(--color-white);

  .text {
    font: var(--font-label);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .vertical-separator {
    width: 1px;
    height: 100%;
    background-color: var(--color-blue-20);
  }

  .count {
    font-weight: var(--font-weight-bold);
  }

  &.read-only {
    cursor: default;
    background-color: var(--color-white-o60);
    color: var(--primary);
  }
}
</style>
