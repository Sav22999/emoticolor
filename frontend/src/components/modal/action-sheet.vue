<script setup lang="ts">
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import type { ActionSheetHeightType, ButtonType, IconType } from '@/utils/types.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'

const hidden = ref<boolean>(true)
const isDragging = ref(false)
const startY = ref(0)
const currentHeight = ref(0)
const baseHeight = ref(0)
const originalHeight = ref(0)

const props = withDefaults(
  defineProps<{
    id?: string //unique id for the modal
    height?: ActionSheetHeightType //0-99 (percentage of screen height)
    fullscreenPossible?: boolean
    showButtons?: boolean
    button1Text?: string
    button1Style?: ButtonType
    button1Icon?: IconType
    button1Close?: boolean
    button2Text?: string
    button2Style?: ButtonType
    button2Icon?: IconType
    button2Close?: boolean
    title?: string
    hiddenByDefault?: boolean
    noPadding?: boolean
    heightFull?: boolean
  }>(),
  {
    id: usefulFunctions.generateUniqueComponentId(),
    height: 99,
    fullscreenPossible: true,
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
    noPadding: false,
    heightFull: false,
  },
)

onMounted(() => {
  hidden.value = props.hiddenByDefault
  currentHeight.value = props.height
  baseHeight.value = props.height
  originalHeight.value = props.height
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
  baseHeight.value = props.height
  // emit('onclose')
}

// Emit onclose only after the leave transition finishes so parent can wait
function onAfterLeave() {
  emit('onclose')
}

function onTouchStart(e: TouchEvent) {
  const touch = e.touches.item(0)
  if (!touch) return
  isDragging.value = true
  startY.value = touch.clientY
  document.addEventListener('touchmove', onTouchMove, { passive: false })
  document.addEventListener('touchend', onTouchEnd)
}

function onTouchMove(e: TouchEvent) {
  if (!isDragging.value) return
  const touch = e.touches.item(0)
  if (!touch) return
  e.preventDefault()
  const deltaY = touch.clientY - startY.value
  currentHeight.value = Math.max(0, baseHeight.value - (deltaY / window.innerHeight) * 100)
}

function onTouchEnd() {
  if (!isDragging.value) return
  document.removeEventListener('touchmove', onTouchMove)
  document.removeEventListener('touchend', onTouchEnd)
  isDragging.value = false
  if (currentHeight.value <= originalHeight.value / (1 + 1 / 3)) {
    closeSheet()
  } else if (currentHeight.value >= (originalHeight.value + 99) / 2 && props.fullscreenPossible) {
    currentHeight.value = 99
    baseHeight.value = 99
  } else {
    if (baseHeight.value === 99) {
      currentHeight.value = originalHeight.value
      baseHeight.value = originalHeight.value
    } else {
      currentHeight.value = baseHeight.value
    }
  }
}

function onMouseDown(e: MouseEvent) {
  isDragging.value = true
  startY.value = e.clientY
  document.addEventListener('mousemove', onMouseMove)
  document.addEventListener('mouseup', onMouseUp)
}

function onMouseMove(e: MouseEvent) {
  if (!isDragging.value) return
  e.preventDefault()
  const deltaY = e.clientY - startY.value
  currentHeight.value = Math.max(0, baseHeight.value - (deltaY / window.innerHeight) * 100)
}

function onMouseUp() {
  if (!isDragging.value) return
  document.removeEventListener('mousemove', onMouseMove)
  document.removeEventListener('mouseup', onMouseUp)
  isDragging.value = false
  if (currentHeight.value <= originalHeight.value / (1 + 1 / 3)) {
    closeSheet()
  } else if (currentHeight.value >= (originalHeight.value + 99) / 2 && props.fullscreenPossible) {
    currentHeight.value = 99
    baseHeight.value = 99
  } else {
    if (baseHeight.value === 99) {
      currentHeight.value = originalHeight.value
      baseHeight.value = originalHeight.value
    } else {
      currentHeight.value = baseHeight.value
    }
  }
}
</script>

<template>
  <div class="modal-action-sheet" :id="props.id" :class="{ 'is-visible': !hidden }">
    <transition name="modal-fade">
      <div class="background" v-show="!hidden" @click="closeSheet"></div>
    </transition>

    <transition name="action-sheet-slide" @after-leave="onAfterLeave">
      <div class="action-sheet" v-show="!hidden" :style="{ height: currentHeight + 'vh' }">
        <div class="header" v-if="props.title" @touchstart="onTouchStart" @mousedown="onMouseDown">
          <div class="action-bar"></div>
          <div class="title">{{ props.title }}</div>
        </div>
        <div class="content" :class="{ 'no-padding': props.noPadding }">
          <div class="slot-content" :class="{ 'height-full': props.heightFull }">
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
    </transition>
  </div>
</template>

<style scoped lang="scss">
.modal-action-sheet {
  /* When the root is not visible, don't block pointer events so underlying UI remains interactive */
  pointer-events: none;

  &.is-visible {
    pointer-events: auto;
  }

  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  z-index: 100;

  .background {
    pointer-events: auto;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--color-black);
    opacity: 0.8;
    z-index: 0;
  }

  .action-sheet {
    pointer-events: auto;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    width: auto;
    background-color: transparent;

    display: flex;
    flex-direction: column;
    z-index: 1;
    overflow: auto;

    /* Avoid conflicting generic transitions; explicitly animate transform & opacity via transition classes */
    will-change: transform, opacity;

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
      position: sticky;
      top: 0;

      .action-bar {
        width: var(--spacing-32);
        height: 2px;
        background-color: var(--primary);
        border-radius: 2px;
        margin: 0;
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

      min-height: 20vh;

      &.no-padding {
        padding: var(--no-padding);
      }

      .slot-content {
        display: block;
        width: 100%;
        height: auto;

        &.height-full {
          height: 100% !important;
        }
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

  /* Fade transition for background overlay */
  .modal-fade-enter-from,
  .modal-fade-leave-to {
    opacity: 0;
  }
  .modal-fade-enter-active,
  .modal-fade-leave-active {
    transition: opacity 180ms ease;
  }
  .modal-fade-enter-to,
  .modal-fade-leave-from {
    opacity: 0.8;
  }

  /* Slide transition for the action sheet */
  .action-sheet-slide-enter-from,
  .action-sheet-slide-leave-to {
    transform: translateY(100%);
    opacity: 0;
  }
  .action-sheet-slide-enter-active,
  .action-sheet-slide-leave-active {
    transition:
      transform 200ms cubic-bezier(0.22, 0.89, 0.29, 1),
      opacity 200ms ease;
  }
  .action-sheet-slide-enter-to,
  .action-sheet-slide-leave-from {
    transform: translateY(0);
    opacity: 1;
  }
}
</style>
