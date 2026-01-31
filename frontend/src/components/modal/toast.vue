<script setup lang="ts">
import { onMounted, ref } from 'vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import type { ButtonType, IconType } from '@/utils/types.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'

const residualLifeSeconds = ref<number>(10000)
const hidden = ref<boolean>(false)
const buttonStyleToUse = ref<ButtonType>('primary')
const pausedTimeout = ref<boolean>(false)
const hiding = ref<boolean>(false)
const opening = ref<boolean>(false)

const props = withDefaults(
  defineProps<{
    id?: string //unique id for the modal
    variant?: 'standard' | 'warning'
    showButton?: boolean
    buttonStyle?: ButtonType
    buttonIcon?: IconType
    lifeSeconds?: number //seconds before auto close (0 to persist)
    hiddenByDefault?: boolean
    position?: 'top' | 'bottom'
  }>(),
  {
    id: usefulFunctions.generateUniqueComponentId(),
    variant: 'warning',
    showButton: false,
    buttonStyle: undefined,
    buttonIcon: 'remove',
    lifeSeconds: 10,
    hiddenByDefault: false,
    position: 'bottom',
  },
)

onMounted(() => {
  hidden.value = props.hiddenByDefault ?? false
  if (!hidden.value) {
    onOpenToast()
  }
  if (!props.buttonStyle) {
    if (props.variant === 'standard') {
      buttonStyleToUse.value = 'primary'
    } else {
      buttonStyleToUse.value = 'warning'
    }
  } else {
    buttonStyleToUse.value = props.buttonStyle
  }
  if (props.lifeSeconds && props.lifeSeconds > 0) {
    residualLifeSeconds.value = props.lifeSeconds * 1000
  } else {
    residualLifeSeconds.value = -1
  }
})

const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'action-button'): void
  (e: 'onclose'): void
  (e: 'onopen'): void
}>()

function onActionButton() {
  emit('action-button')
  hideToast()
}

function hideToast() {
  hiding.value = true
  setTimeout(() => {
    hidden.value = true
    emit('onclose')
  }, 500)
}

function onOpenToast() {
  opening.value = true
  setTimeout(() => {
    setTimeout(decreaseTimeout, 1000)
    emit('onopen')
  }, 500)
}

function decreaseTimeout() {
  if (residualLifeSeconds.value > 250) {
    if (!pausedTimeout.value) {
      residualLifeSeconds.value -= 250
    }
    setTimeout(decreaseTimeout, 250)
  } else if (residualLifeSeconds.value === -1) {
    //persistent toast
  } else {
    hideToast()
  }
}

function pauseTimeout() {
  pausedTimeout.value = true
}

function resumeTimeout() {
  pausedTimeout.value = false
}
</script>

<template>
  <div class="modal-toast" v-if="!hidden" :id="props.id">
    <div class="background" :class="{ persistent: props.lifeSeconds === 0 }"></div>
    <div
      class="toast"
      :class="{
        'variant-standard': props.variant === 'standard',
        'variant-warning': props.variant === 'warning',
        'position-top': props.position === 'top',
        'position-bottom': props.position === 'bottom',
        hiding: hiding,
        opening: opening,
      }"
      @mouseenter="pauseTimeout()"
      @mouseleave="resumeTimeout()"
    >
      <div class="header">
        <div class="bar"></div>
        <div
          class="timing-bar"
          :style="{
            width: ((residualLifeSeconds - 250) / ((props.lifeSeconds - 1) * 1000)) * 100 + '%',
          }"
          v-if="residualLifeSeconds !== -1 && props.lifeSeconds! > 0"
        ></div>
      </div>
      <div class="content">
        <div class="slot-content">
          <slot></slot>
        </div>
        <div class="button" v-if="props.showButton">
          <button-generic
            :variant="buttonStyleToUse"
            :icon="props.buttonIcon"
            align="center"
            iconPosition="end"
            :disabled="false"
            @action="onActionButton"
            :disabled-hover-effect="true"
            :small="true"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.modal-toast {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100vh;
  z-index: 100;
  pointer-events: none;

  .background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0.8;
    z-index: 0;
    background-color: transparent;
    pointer-events: none;

    &.persistent {
      pointer-events: all;
      background-color: var(--color-black);
    }
  }

  .toast {
    position: absolute;
    left: 0;
    right: 0;
    width: auto;
    background-color: var(--color-red-10);
    color: var(--color-red-70);

    display: flex;
    flex-direction: column;
    z-index: 1;
    overflow: auto;

    transition: 0.1s;

    pointer-events: all;

    transform: translateY(100%);

    &.position-top {
      top: 0;
      bottom: auto;
    }
    &.position-bottom {
      top: auto;
      bottom: 0;
    }

    &.hiding {
      transform: translateY(100%);
      transition: 500ms ease;
    }
    &.opening {
      transform: translateY(0%);
      transition: 500ms ease;
    }

    .header {
      display: flex;
      flex-direction: column;
      gap: var(--no-spacing);
      padding: var(--no-padding);
      align-content: start;
      justify-content: start;

      .bar {
        width: 100%;
        height: 4px;
        background-color: var(--color-red-50);
      }

      .timing-bar {
        width: 100%;
        height: 4px;
        background-color: var(--color-red-30);
        transition: width 250ms linear;
      }
    }

    &.variant-standard {
      background-color: var(--color-blue-10);
      color: var(--color-blue-70);

      .bar {
        background-color: var(--color-blue-50);
      }
      .timing-bar {
        background-color: var(--color-blue-30);
      }
    }

    .content {
      padding: var(--padding-16);
      overflow-y: auto;
      flex: 1;

      min-height: 10vh;
      height: auto;

      display: flex;
      flex-direction: row;
      gap: var(--spacing-8);

      .slot-content {
        display: block;
        width: 100%;
        height: auto;
        flex: 1;
      }

      .button {
        display: flex;
        align-items: center;
      }
    }
  }
}
</style>
