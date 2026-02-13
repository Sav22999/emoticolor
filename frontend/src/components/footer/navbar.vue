<script setup lang="ts">
import { onMounted, ref } from 'vue'
import IconGeneric from '@/components/icon/icon-generic.vue'

const currentTab = ref<number>(-1)

const props = withDefaults(
  defineProps<{
    selectedTab?: number
  }>(),
  {
    selectedTab: -1,
  },
)
const emit = defineEmits<{
  (e: 'tab-change', value: number): void
}>()

onMounted(() => {
  currentTab.value = props.selectedTab!
})

function changeTab(tabIndex: number) {
  currentTab.value = tabIndex
  emit('tab-change', tabIndex)
}
</script>

<template>
  <footer>
    <div class="navbar">
      <div class="tab" :class="{ selected: currentTab === 0 }" @click="changeTab(0)">
        <div class="icon">
          <icon-generic name="learning" size="20px"></icon-generic>
        </div>
        <div class="text">Impara</div>
      </div>
      <div class="tab" :class="{ selected: currentTab === 1 }" @click="changeTab(1)">
        <div class="icon">
          <icon-generic name="home" size="20px"></icon-generic>
        </div>
        <div class="text">Home</div>
      </div>
      <div class="tab" :class="{ selected: currentTab === 2 }" @click="changeTab(2)">
        <div class="icon">
          <icon-generic name="user" size="20px"></icon-generic>
        </div>
        <div class="text">Profilo</div>
      </div>
    </div>
  </footer>
</template>

<style scoped lang="scss">
footer {
  background-color: var(--color-green-10);
  color: var(--primary);
  border-top: var(--spacing-4) solid var(--primary);
  font: var(--font-small);

  position: sticky;
  bottom: 0;
  z-index: 10;
  padding-bottom: var(--padding-8);
  box-shadow: 0px 0px var(--spacing-4) var(--color-white-o60);

  .navbar {
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    align-items: center;
    padding: var(--spacing-4);
    padding-top: 0;

    .tab {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      gap: var(--spacing-4);
      padding: var(--padding-4);
      max-width: 70px;
      flex: 1;
      cursor: pointer;
      user-select: none;

      .icon {
        width: 20px;
        height: 20px;
      }

      &.selected {
        background-color: var(--primary);
        color: var(--on-primary);
        border-bottom-left-radius: var(--padding-4);
        border-bottom-right-radius: var(--padding-4);
      }
    }
  }
}
</style>
