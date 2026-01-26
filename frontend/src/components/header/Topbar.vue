<script setup lang="ts">
//import { ref } from 'vue'
import backIcon from '@/assets/icons/back.svg?component'
import notificationsIcon from '@/assets/icons/notifications.svg?component'
import searchIcon from '@/assets/icons/search.svg?component'

defineOptions({ name: 'AppTopbar' })

const props = withDefaults(
  defineProps<{
    variant?: 'simple-big' | 'standard' | 'search'
    showBackButton?: boolean
    showNotificationsButton?: boolean
    showSearchButton?: boolean
    title?: string
  }>(),
  {
    variant: 'standard',
    showBackButton: false,
    showNotificationsButton: false,
    showSearchButton: false,
    title: '',
  },
)
/*const emit = defineEmits<{
  (e: 'update:modelValue', value: number): void
  (e: 'increment'): void
}>()*/

function doAction(name: string) {
  //emit('increment')
  console.log('Action:', name)
}
</script>

<template>
  <header class="simple" v-if="props.variant === 'simple-big'">
    <div class="bar">
      <div class="purple"></div>
      <div class="yellow"></div>
      <div class="red"></div>
      <div class="blue"></div>
      <div class="gray"></div>
      <div class="green"></div>
      <div class="brown"></div>
    </div>
    <div class="header">
      <img alt="Emoticolor logo" class="logo" src="@/assets/images/logo.svg" />
    </div>
    <div class="title font-title" v-if="props.title !== ''">{{ props.title }}</div>
  </header>

  <header class="standard" v-else-if="props.variant === 'standard'">
    <div class="header">
      <div class="start">
        <backIcon class="icon" v-if="props.showBackButton" @click="doAction('back')"></backIcon>
        <notificationsIcon
          class="icon"
          v-if="props.showNotificationsButton && !props.showBackButton"
        ></notificationsIcon>
      </div>
      <div class="center">
        <img alt="Emoticolor logo" class="logo" src="@/assets/images/logo.svg" />
      </div>
      <div class="end">
        <searchIcon class="icon" v-if="props.showSearchButton"></searchIcon>
      </div>
    </div>
    <div class="bar">
      <div class="purple"></div>
      <div class="yellow"></div>
      <div class="red"></div>
      <div class="blue"></div>
      <div class="gray"></div>
      <div class="green"></div>
      <div class="brown"></div>
    </div>
    <div class="title font-subtitle" v-if="props.title !== ''">{{ props.title }}</div>
  </header>
</template>

<style scoped lang="scss">
.bar {
  display: flex;
  height: 10px;
  width: 100%;
}
.bar > * {
  flex: 1;
  height: 100%;
  width: 100%;
}
.bar > .purple {
  background-color: var(--color-purple-50);
}
.bar > .yellow {
  background-color: var(--color-yellow-50);
}
.bar > .red {
  background-color: var(--color-red-50);
}
.bar > .blue {
  background-color: var(--color-blue-50);
}
.bar > .gray {
  background-color: var(--color-gray-30);
}
.bar > .green {
  background-color: var(--color-green-50);
}
.bar > .brown {
  background-color: var(--color-brown-50);
}

.simple {
  display: flex;
  flex-direction: column;

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

.standard {
  display: flex;
  flex-direction: column;

  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 60px;
    background-color: var(--color-green-10);
    padding: var(--padding) var(--padding);
    flex-direction: row;
    color: var(--primary);

    > .start,
    > .end {
      width: auto;
      min-width: 24px;
      height: 100%;
      min-height: 24px;
      order: 1;

      .icon {
        height: 24px;
        width: 24px;

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
</style>
