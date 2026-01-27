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
    variant?: 'primary' | 'secondary' | 'secondary40'
  }>(),
  {
    variant: 'primary',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    class="separator"
    :class="{ primary: props.variant === 'primary', secondary: props.variant === 'secondary' }"
  ></div>
</template>

<style scoped lang="scss">
.separator {
  width: 100%;
  height: 1px;
  background-color: var(--color-blue-20);

  &.secondary {
    background-color: var(--color-green-20);
  }

  &.secondary40 {
    background-color: var(--color-green-40);
  }
}
</style>
