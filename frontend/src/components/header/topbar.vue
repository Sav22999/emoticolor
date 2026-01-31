<script setup lang="ts">
//import { ref } from 'vue'

import InputSearchbox from '@/components/input/input-searchbox.vue'
import IconGeneric from '@/components/icon/icon-generic.vue'

const props = withDefaults(
  defineProps<{
    variant?: 'simple-big' | 'standard' | 'search'
    showBackButton?: boolean
    showNotificationsButton?: boolean
    showSearchButton?: boolean
    showSettingsButton?: boolean
    title?: string
  }>(),
  {
    variant: 'search',
    showBackButton: false,
    showNotificationsButton: false,
    showSearchButton: false,
    showSettingsButton: false,
    title: '',
  },
)
const emit = defineEmits<{
  /*(e: 'update:modelValue', value: number): void*/
  (e: 'onsearch'): void
  (e: 'oninputsearch', value: string): void
  (e: 'onback'): void
  (e: 'onnotifications'): void
  (e: 'onsettings'): void
  (e: 'action', value: string): void
  (e: 'onlogo'): void
}>()

function onSettings() {
  emit('onsettings')
}

function onSearch() {
  emit('onsearch')
}
function onInputSearch(value: string) {
  emit('oninputsearch', value)
}
function onBack() {
  emit('onback')
}
function onNotifications() {
  emit('onnotifications')
}

function onLogoClick() {
  emit('onlogo')
}
</script>

<template>
  <div class="bar sticky-top-0" v-if="props.variant === 'simple-big'">
    <div class="purple"></div>
    <div class="yellow"></div>
    <div class="red"></div>
    <div class="blue"></div>
    <div class="gray"></div>
    <div class="green"></div>
    <div class="brown"></div>
  </div>
  <header class="simple" v-if="props.variant === 'simple-big'">
    <div class="header" @click="onLogoClick">
      <img alt="Emoticolor logo" class="logo" src="@/assets/images/logo.svg" />
    </div>
  </header>

  <header class="standard" v-else-if="props.variant === 'standard' || props.variant === 'search'">
    <div class="header">
      <div class="start">
        <icon-generic name="back" v-if="props.showBackButton" size="24px" @click="onBack" />
        <icon-generic
          name="notifications"
          v-if="
            props.showNotificationsButton && !props.showBackButton && props.variant !== 'search'
          "
          size="24px"
          @click="onNotifications"
        />
      </div>
      <div class="center" @click="onLogoClick">
        <img alt="Emoticolor logo" class="logo" src="@/assets/images/logo.svg" />
      </div>
      <div class="end">
        <icon-generic name="search" v-if="props.showSearchButton && variant==='standard'" size="24px" @click="onSearch" />
        <icon-generic name="settings" v-if="props.showSettingsButton && variant==='standard' && !props.showSearchButton" size="24px" @click="onSettings" />
      </div>
    </div>
    <div class="bar" v-if="props.variant === 'standard'">
      <div class="purple"></div>
      <div class="yellow"></div>
      <div class="red"></div>
      <div class="blue"></div>
      <div class="gray"></div>
      <div class="green"></div>
      <div class="brown"></div>
    </div>
  </header>
  <div class="bar sticky-top-0" v-if="props.variant === 'search'">
    <div class="purple"></div>
    <div class="yellow"></div>
    <div class="red"></div>
    <div class="blue"></div>
    <div class="gray"></div>
    <div class="green"></div>
    <div class="brown"></div>
  </div>
  <header>
    <div class="title font-title" v-if="props.title !== '' && props.variant === 'simple-big'">
      {{ props.title }}
    </div>
    <div class="title font-subtitle" v-if="props.title !== '' && props.variant === 'standard'">
      {{ props.title }}
    </div>
    <div class="searchbox" v-if="props.variant === 'search'">
      <input-searchbox
        :placeholder="props.title !== '' ? props.title : 'Search...'"
        @input="onInputSearch($event)"
      ></input-searchbox>
    </div>
  </header>
</template>

<style scoped lang="scss">
.simple {
  display: flex;
  flex-direction: column;

  z-index: 10;

  .header {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 120px;
    background-color: var(--color-green-10);
    padding: var(--padding-32) var(--padding);

    .logo {
      width: 70%;
      height: auto;
      max-width: 240px;
      min-width: 100px;
    }
  }
}

.bar.sticky-top-0 {
  position: sticky;
  top: 0;
  z-index: 9;
}

.standard {
  display: flex;
  flex-direction: column;

  position: sticky;
  top: 0;

  z-index: 7;

  .bar {
    position: sticky;
    top: 60px;
  }

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 60px;
    background-color: var(--color-green-10);
    padding: var(--padding) var(--padding);
    flex-direction: row;
    color: var(--primary);

    position: sticky;
    top: 0;

    > .start,
    > .end {
      width: auto;
      min-width: 24px;
      height: 100%;
      min-height: 24px;
      order: 1;
      display: flex;
      justify-content: center;
      align-items: center;

      .icon {
        cursor: pointer;
      }
    }
    > .center {
      width: auto;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      order: 2;

      .logo {
        height: 24px;
        width: auto;
      }
    }
    > .end {
      order: 3;
    }
  }
}

header {
  .title {
    background-color: var(--primary);
    color: var(--on-primary);
    height: auto;
    display: flex;
    justify-content: center;
    align-items: center;

    padding: var(--padding);
  }
}

#app:has(.searchbox) {
  .standard {
    position: relative;
  }
  header:has(.searchbox) {
    position: sticky;
    top: 5px;
    z-index: 8;

    .searchbox {
      padding: var(--padding);
      background-color: var(--primary);
      color: var(--on-primary);
    }
  }
}
</style>
