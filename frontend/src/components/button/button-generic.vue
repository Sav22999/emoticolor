<script setup lang="ts">
import { onMounted, ref } from 'vue'
import searchIcon from '@/assets/icons/search.svg?component'
import emailIcon from '@/assets/icons/email.svg?component'
import userIcon from '@/assets/icons/user.svg?component'
import loginIcon from '@/assets/icons/login.svg?component'
import logoutIcon from '@/assets/icons/logout.svg?component'
import editIcon from '@/assets/icons/edit.svg?component'
import codeIcon from '@/assets/icons/code.svg?component'
import deleteIcon from '@/assets/icons/delete.svg?component'
import downloadIcon from '@/assets/icons/download.svg?component'
import followNIcon from '@/assets/icons/follow-n.svg?component'
import followYIcon from '@/assets/icons/follow-y.svg?component'
import backIcon from '@/assets/icons/back.svg?component'
import forwardIcon from '@/assets/icons/forward.svg?component'
import markYesIcon from '@/assets/icons/mark-yes.svg?component'
import menuHIcon from '@/assets/icons/menu-h.svg?component'
import menuVIcon from '@/assets/icons/menu-v.svg?component'
import plusIcon from '@/assets/icons/plus.svg?component'
import plusCircleIcon from '@/assets/icons/plus-circle.svg?component'
import privateIcon from '@/assets/icons/private.svg?component'
import publicIcon from '@/assets/icons/public.svg?component'
import removeIcon from '@/assets/icons/remove.svg?component'
import removeCircleIcon from '@/assets/icons/remove-circle.svg?component'
import saveIcon from '@/assets/icons/save.svg?component'
import scrollingHIcon from '@/assets/icons/scrolling-h.svg?component'
import scrollingVIcon from '@/assets/icons/scrolling-v.svg?component'
import settingsIcon from '@/assets/icons/settings.svg?component'
import shareIcon from '@/assets/icons/share.svg?component'
import externalIcon from '@/assets/icons/external.svg?component'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'cta' | 'outline' | 'simple'
    icon?:
      | ''
      | 'search'
      | 'email'
      | 'user'
      | 'login'
      | 'logout'
      | 'edit'
      | 'code'
      | 'delete'
      | 'download'
      | 'follow-n'
      | 'follow-y'
      | 'back'
      | 'forward'
      | 'mark-yes'
      | 'menu-h'
      | 'menu-v'
      | 'plus'
      | 'plus-circle'
      | 'private'
      | 'public'
      | 'remove'
      | 'remove-circle'
      | 'save'
      | 'scrolling-h'
      | 'scrolling-v'
      | 'settings'
      | 'share'
      | 'external'
    iconPosition?: 'start' | 'end'
    placeholder?: string
    text?: string
    small?: boolean
    fullWidth?: boolean
    align?: 'start' | 'center' | 'end' | 'space'
    disabledHoverEffect?: boolean
    disabled?: boolean
  }>(),
  {
    variant: 'primary',
    icon: '',
    iconPosition: 'end',
    placeholder: 'Press button',
    text: '',
    small: false,
    fullWidth: false,
    align: 'center',
    disabledHoverEffect: false,
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
  <div
    class="button"
    @click="onAction"
    :class="{
      primary: props.variant === 'primary',
      cta: props.variant === 'cta',
      outline: props.variant === 'outline',
      simple: props.variant === 'simple',
      small: props.small,
      'icon-start': props.iconPosition === 'start',
      'icon-end': props.iconPosition === 'end',
      'full-width': props.fullWidth,
      'align-start': props.align === 'start',
      'align-center': props.align === 'center',
      'align-end': props.align === 'end',
      'align-space': props.align === 'space',
      'no-hover': props.disabledHoverEffect,
      disabled: props.disabled,
    }"
  >
    <div class="label" v-if="props.text !== ''">{{ props.text }}</div>
    <search-icon class="icon icon-label" v-if="icon === 'search'"></search-icon>
    <email-icon class="icon icon-label" v-else-if="icon === 'email'"></email-icon>
    <user-icon class="icon icon-label" v-else-if="icon === 'user'"></user-icon>
    <login-icon class="icon icon-label" v-else-if="icon === 'login'"></login-icon>
    <logout-icon class="icon icon-label" v-else-if="icon === 'logout'"></logout-icon>
    <edit-icon class="icon icon-label" v-else-if="icon === 'edit'"></edit-icon>
    <code-icon class="icon icon-label" v-else-if="icon === 'code'"></code-icon>
    <delete-icon class="icon icon-label" v-else-if="icon === 'delete'"></delete-icon>
    <download-icon class="icon icon-label" v-else-if="icon === 'download'"></download-icon>
    <follow-n-icon class="icon icon-label" v-else-if="icon === 'follow-n'"></follow-n-icon>
    <follow-y-icon class="icon icon-label" v-else-if="icon === 'follow-y'"></follow-y-icon>
    <forward-icon class="icon icon-label" v-else-if="icon === 'forward'"></forward-icon>
    <back-icon class="icon icon-label" v-else-if="icon === 'back'"></back-icon>
    <mark-yes-icon class="icon icon-label" v-else-if="icon === 'mark-yes'"></mark-yes-icon>
    <menu-h-icon class="icon icon-label" v-else-if="icon === 'menu-h'"></menu-h-icon>
    <menu-v-icon class="icon icon-label" v-else-if="icon === 'menu-v'"></menu-v-icon>
    <plus-icon class="icon icon-label" v-else-if="icon === 'plus'"></plus-icon>
    <plus-circle-icon class="icon icon-label" v-else-if="icon === 'plus-circle'"></plus-circle-icon>
    <private-icon class="icon icon-label" v-else-if="icon === 'private'"></private-icon>
    <public-icon class="icon icon-label" v-else-if="icon === 'public'"></public-icon>
    <remove-icon class="icon icon-label" v-else-if="icon === 'remove'"></remove-icon>
    <remove-circle-icon
      class="icon icon-label"
      v-else-if="icon === 'remove-circle'"
    ></remove-circle-icon>
    <save-icon class="icon icon-label" v-else-if="icon === 'save'"></save-icon>
    <scrolling-h-icon class="icon icon-label" v-else-if="icon === 'scrolling-h'"></scrolling-h-icon>
    <scrolling-v-icon class="icon icon-label" v-else-if="icon === 'scrolling-v'"></scrolling-v-icon>
    <settings-icon class="icon icon-label" v-else-if="icon === 'settings'"></settings-icon>
    <share-icon class="icon icon-label" v-else-if="icon === 'share'"></share-icon>
    <external-icon class="icon icon-label" v-else-if="icon === 'external'"></external-icon>
  </div>
</template>

<style scoped lang="scss">
.button {
  border-radius: var(--border-radius-8);

  display: flex;
  flex-direction: row;
  gap: var(--spacing-16);
  padding: var(--padding-16);
  font: var(--font-label);

  transition: 0.1s all;

  cursor: pointer;
  user-select: none;

  .label {
    order: 2;
    text-align: center;
  }

  .icon {
    width: 18px;
    height: 18px;
    margin: var(--no-spacing);
  }

  &.icon-start {
    > .icon {
      order: 1;
    }
  }
  &.icon-end {
    > .icon {
      order: 3;
    }
  }

  &.full-width {
    width: 100%;
  }

  &.small {
    padding: var(--padding-8);
  }

  &.align-start {
    justify-content: start;
  }
  &.align-center {
    justify-content: center;
  }
  &.align-end {
    justify-content: end;
  }
  &.align-space {
    justify-content: space-between;
  }

  &:not(.outline) {
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
  }
  &.no-hover:not(.outline) {
    border-left: 0px solid transparent;
    border-right: 0px solid transparent;
  }

  &.primary {
    background-color: var(--primary);
    color: var(--on-primary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-blue-70);
      border-right: 4px solid var(--color-blue-70);
      background-color: var(--color-blue-60);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.cta {
    background-color: var(--secondary);
    color: var(--on-secondary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-green-70);
      border-right: 4px solid var(--color-green-70);
      background-color: var(--color-green-60);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.outline {
    background-color: var(--transparent);
    color: var(--primary);
    border: 1px solid var(--primary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 5px solid var(--primary);
      border-right: 5px solid var(--primary);
      background-color: var(--color-blue-10);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      border: 1px solid var(--color-gray-50);
      cursor: not-allowed;
    }
  }

  &.simple {
    background-color: var(--transparent);
    color: var(--primary);

    &:hover:not(.no-hover):not(.disabled) {
      border-left: 4px solid var(--color-blue-20);
      border-right: 4px solid var(--color-blue-20);
      background-color: var(--color-blue-10);
    }

    &.disabled {
      background-color: var(--color-gray-20);
      color: var(--color-gray-50);
      cursor: not-allowed;
    }
  }
}
</style>
