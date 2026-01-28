<script setup lang="ts">
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import type { IconType } from '@/utils/types.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'

const hidden = ref<boolean>(true)

const props = withDefaults(
  defineProps<{
    id?: string //unique id for the modal
    height?: number //0-100 (percentage of screen height)
    showButtons?: boolean
    button1Text?: string
    button1Style?: 'primary' | 'cta' | 'outline' | 'simple'
    button1Icon?: IconType
    button1Close?: boolean
    button2Text?: string
    button2Style?: 'primary' | 'cta' | 'outline' | 'simple'
    button2Icon?: IconType
    button2Close?: boolean
    title?: string
    hiddenByDefault?: boolean
  }>(),
  {
    id: usefulFunctions.generateUniqueComponentId(),
    height: 100,
    showButtons: true,
    button1Text: 'Annulla',
    button1Style: 'primary',
    button1Icon: 'remove',
    button1Close: true,
    button2Text: 'Conferma',
    button2Icon: 'mark-yes',
    button2Style: 'cta',
    button2Close: true,
    title: '',
    hiddenByDefault: true,
  },
)

onMounted(() => {
  hidden.value = props.hiddenByDefault
  if (!hidden.value) {
    emit('onopen')
  }
})

const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'action-button1'): void
  (e: 'action-button2'): void
  (e: 'onclose'): void
  (e: 'onopen'): void
}>()

function onActionButton1() {
  emit('action-button1')
  if (props.button1Close) {
    closeSheet()
  }
}

function onActionButton2() {
  emit('action-button2')
  if (props.button2Close) {
    closeSheet()
  }
}

function closeSheet() {
  hidden.value = true
  emit('onclose')
}
</script>

<template>
  <div class="modal-action-sheet" v-if="!hidden" :id="props.id">
    <div class="background" @click="closeSheet"></div>
    <div class="action-sheet" :style="{ height: props.height + 'vh' }">
      <div class="header" v-if="props.title">
        <div class="action-bar"></div>
        <div class="title">{{ props.title }}</div>
      </div>
      <div class="content">
        <div class="slot-content">
          <slot></slot>
        </div>
      </div>
      <div
        class="buttons"
        v-if="props.showButtons && (props.button1Text !== '' || props.button2Text !== '')"
      >
        <button-generic
          :text="props.button1Text"
          :variant="props.button1Style"
          :icon="props.button1Icon"
          align="center"
          iconPosition="end"
          :disabled="false"
          @action="onActionButton1"
          v-if="props.button1Text !== ''"
        />
        <button-generic
          :text="props.button2Text"
          :variant="props.button2Style"
          :icon="props.button2Icon"
          align="center"
          iconPosition="end"
          :disabled="false"
          @action="onActionButton2"
          v-if="props.button2Text !== ''"
        />
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.modal-action-sheet {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 100;

  .background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--color-black);
    opacity: 0.8;
    z-index: 0;
  }

  .action-sheet {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    width: auto;
    background-color: transparent;

    display: flex;
    flex-direction: column;
    z-index: 1;

    .header {
      background-color: var(--color-blue-10);
      padding: var(--padding-8);
      border-top-left-radius: var(--border-radius);
      border-top-right-radius: var(--border-radius);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: stretch;
      color: var(--primary);
      gap: var(--spacing-4);
      border-bottom: 4px solid var(--primary);
      user-select: none;
      cursor: pointer;

      .action-bar {
        width: var(--spacing-32);
        height: 2px;
        background-color: var(--primary);
        border-radius: 2px;
        margin: 0px;
      }

      .title {
        font: var(--font-subtitle);
        padding: var(--padding-8);
      }
    }

    .content {
      background-color: var(--color-white);
      padding: var(--padding-16);
      overflow-y: auto;
      flex: 1;

      .slot-content {
        display: block;
        width: 100%;
        height: 100%;
      }
    }

    .buttons {
      background-color: var(--color-blue-10);
      padding: var(--padding-16);
      display: flex;
      flex-direction: row;
      gap: var(--spacing-16);

      > * {
        flex: 1;
      }
    }
  }
}
</style>
