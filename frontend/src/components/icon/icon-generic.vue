<script setup lang="ts">
import { onMounted } from 'vue'
import backIcon from '@/assets/icons/back.svg?component'
import boltIcon from '@/assets/icons/bolt.svg?component'
import bookmarkIcon from '@/assets/icons/bookmark.svg?component'
import cameraIcon from '@/assets/icons/camera.svg?component'
import chevronDownIcon from '@/assets/icons/chevron-down.svg?component'
import chevronUpIcon from '@/assets/icons/chevron-up.svg?component'
import codeIcon from '@/assets/icons/code.svg?component'
import dashboardIcon from '@/assets/icons/dashboard.svg?component'
import deleteIcon from '@/assets/icons/delete.svg?component'
import downloadIcon from '@/assets/icons/download.svg?component'
import editIcon from '@/assets/icons/edit.svg?component'
import emailIcon from '@/assets/icons/email.svg?component'
import externalIcon from '@/assets/icons/external.svg?component'
import followNIcon from '@/assets/icons/follow-n.svg?component'
import followYIcon from '@/assets/icons/follow-y.svg?component'
import forwardIcon from '@/assets/icons/forward.svg?component'
import headIcon from '@/assets/icons/head.svg?component'
import helpIcon from '@/assets/icons/help.svg?component'
import hideIcon from '@/assets/icons/hide.svg?component'
import holidayIcon from '@/assets/icons/holiday.svg?component'
import homeIcon from '@/assets/icons/home.svg?component'
import hospitalIcon from '@/assets/icons/hospital.svg?component'
import imageIcon from '@/assets/icons/image.svg?component'
import infoIcon from '@/assets/icons/info.svg?component'
import laptopIcon from '@/assets/icons/laptop.svg?component'
import learningIcon from '@/assets/icons/learning.svg?component'
import locationIcon from '@/assets/icons/location.svg?component'
import loginIcon from '@/assets/icons/login.svg?component'
import logoutIcon from '@/assets/icons/logout.svg?component'
import loveIcon from '@/assets/icons/love.svg?component'
import markYesIcon from '@/assets/icons/mark-yes.svg?component'
import menuHIcon from '@/assets/icons/menu-h.svg?component'
import menuVIcon from '@/assets/icons/menu-v.svg?component'
import notificationsIcon from '@/assets/icons/notifications.svg?component'
import passwordIcon from '@/assets/icons/password.svg?component'
import peopleIcon from '@/assets/icons/people.svg?component'
import placeIcon from '@/assets/icons/place.svg?component'
import plusIcon from '@/assets/icons/plus.svg?component'
import plusCircleIcon from '@/assets/icons/plus-circle.svg?component'
import printIcon from '@/assets/icons/print.svg?component'
import privateIcon from '@/assets/icons/private.svg?component'
import publicIcon from '@/assets/icons/public.svg?component'
import rainIcon from '@/assets/icons/rain.svg?component'
import reactionsIcon from '@/assets/icons/reactions.svg?component'
import refreshIcon from '@/assets/icons/refresh.svg?component'
import removeIcon from '@/assets/icons/remove.svg?component'
import removeCircleIcon from '@/assets/icons/remove-circle.svg?component'
import reportIcon from '@/assets/icons/report.svg?component'
import saveIcon from '@/assets/icons/save.svg?component'
import scrollingHIcon from '@/assets/icons/scrolling-h.svg?component'
import scrollingVIcon from '@/assets/icons/scrolling-v.svg?component'
import searchIcon from '@/assets/icons/search.svg?component'
import settingsIcon from '@/assets/icons/settings.svg?component'
import shareIcon from '@/assets/icons/share.svg?component'
import shoppingIcon from '@/assets/icons/shopping.svg?component'
import showIcon from '@/assets/icons/show.svg?component'
import snowIcon from '@/assets/icons/snow.svg?component'
import stats1Icon from '@/assets/icons/stats1.svg?component'
import stats2Icon from '@/assets/icons/stats2.svg?component'
import sunIcon from '@/assets/icons/sun.svg?component'
import trashIcon from '@/assets/icons/trash.svg?component'
import userIcon from '@/assets/icons/user.svg?component'
import usernameIcon from '@/assets/icons/username.svg?component'
import videoIcon from '@/assets/icons/video.svg?component'
import warningIcon from '@/assets/icons/warning.svg?component'
import workplaceIcon from '@/assets/icons/workplace.svg?component'
import type { IconType } from '@/utils/types.ts'

const props = withDefaults(
  defineProps<{
    name: IconType
    size?: '16px' | '18px' | '20px' | '24px'
  }>(),
  {
    name: '',
    size: '18px',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    v-if="name !== ''"
    class="icon"
    :style="{
      width: props.size,
      height: props.size,
      'min-width': props.size,
      'min-height': props.size,
    }"
  >
    <back-icon v-if="name === 'back'"></back-icon>
    <bolt-icon v-else-if="name === 'bolt'"></bolt-icon>
    <bookmark-icon v-else-if="name === 'bookmark'"></bookmark-icon>
    <camera-icon v-else-if="name === 'camera'"></camera-icon>
    <chevron-down-icon v-else-if="name === 'chevron-down'"></chevron-down-icon>
    <chevron-up-icon v-else-if="name === 'chevron-up'"></chevron-up-icon>
    <code-icon v-else-if="name === 'code'"></code-icon>
    <dashboard-icon v-else-if="name === 'dashboard'"></dashboard-icon>
    <delete-icon v-else-if="name === 'delete'"></delete-icon>
    <download-icon v-else-if="name === 'download'"></download-icon>
    <edit-icon v-else-if="name === 'edit'"></edit-icon>
    <email-icon v-else-if="name === 'email'"></email-icon>
    <external-icon v-else-if="name === 'external'"></external-icon>
    <follow-n-icon v-else-if="name === 'follow-n'"></follow-n-icon>
    <follow-y-icon v-else-if="name === 'follow-y'"></follow-y-icon>
    <forward-icon v-else-if="name === 'forward'"></forward-icon>
    <head-icon v-else-if="name === 'head'"></head-icon>
    <help-icon v-else-if="name === 'help'"></help-icon>
    <hide-icon v-else-if="name === 'hide'"></hide-icon>
    <holiday-icon v-else-if="name === 'holiday'"></holiday-icon>
    <home-icon v-else-if="name === 'home'"></home-icon>
    <hospital-icon v-else-if="name === 'hospital'"></hospital-icon>
    <image-icon v-else-if="name === 'image'"></image-icon>
    <info-icon v-else-if="name === 'info'"></info-icon>
    <laptop-icon v-else-if="name === 'laptop'"></laptop-icon>
    <learning-icon v-else-if="name === 'learning'"></learning-icon>
    <location-icon v-else-if="name === 'location'"></location-icon>
    <login-icon v-else-if="name === 'login'"></login-icon>
    <logout-icon v-else-if="name === 'logout'"></logout-icon>
    <love-icon v-else-if="name === 'love'"></love-icon>
    <mark-yes-icon v-else-if="name === 'mark-yes'"></mark-yes-icon>
    <menu-h-icon v-else-if="name === 'menu-h'"></menu-h-icon>
    <menu-v-icon v-else-if="name === 'menu-v'"></menu-v-icon>
    <notifications-icon v-else-if="name === 'notifications'"></notifications-icon>
    <password-icon v-else-if="name === 'password'"></password-icon>
    <people-icon v-else-if="name === 'people'"></people-icon>
    <place-icon v-else-if="name === 'place'"></place-icon>
    <plus-icon v-else-if="name === 'plus'"></plus-icon>
    <plus-circle-icon v-else-if="name === 'plus-circle'"></plus-circle-icon>
    <print-icon v-else-if="name === 'print'"></print-icon>
    <private-icon v-else-if="name === 'private'"></private-icon>
    <public-icon v-else-if="name === 'public'"></public-icon>
    <rain-icon v-else-if="name === 'rain'"></rain-icon>
    <reactions-icon v-else-if="name === 'reactions'"></reactions-icon>
    <refresh-icon v-else-if="name === 'refresh'"></refresh-icon>
    <remove-icon v-else-if="name === 'remove'"></remove-icon>
    <remove-circle-icon v-else-if="name === 'remove-circle'"></remove-circle-icon>
    <report-icon v-else-if="name === 'report'"></report-icon>
    <save-icon v-else-if="name === 'save'"></save-icon>
    <scrolling-h-icon v-else-if="name === 'scrolling-h'"></scrolling-h-icon>
    <scrolling-v-icon v-else-if="name === 'scrolling-v'"></scrolling-v-icon>
    <search-icon v-else-if="name === 'search'"></search-icon>
    <settings-icon v-else-if="name === 'settings'"></settings-icon>
    <share-icon v-else-if="name === 'share'"></share-icon>
    <shopping-icon v-else-if="name === 'shopping'"></shopping-icon>
    <show-icon v-else-if="name === 'show'"></show-icon>
    <snow-icon v-else-if="name === 'snow'"></snow-icon>
    <stats1-icon v-else-if="name === 'stats1'"></stats1-icon>
    <stats2-icon v-else-if="name === 'stats2'"></stats2-icon>
    <sun-icon v-else-if="name === 'sun'"></sun-icon>
    <trash-icon v-else-if="name === 'trash'"></trash-icon>
    <user-icon v-else-if="name === 'user'"></user-icon>
    <username-icon v-else-if="name === 'username'"></username-icon>
    <video-icon v-else-if="name === 'video'"></video-icon>
    <warning-icon v-else-if="name === 'warning'"></warning-icon>
    <workplace-icon v-else-if="name === 'workplace'"></workplace-icon>
  </div>
</template>

<style scoped lang="scss">
.icon {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: var(--no-spacing);

  > * {
    width: 100%;
    height: 100%;
  }
}
</style>
