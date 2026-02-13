<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import ButtonGeneric from '@/components/button/button-generic.vue'
import { ref } from 'vue'
import TextInfo from '@/components/text/text-info.vue'
import usefulFunctions from '@/utils/useful-functions.ts'

const selectedView = ref<number>(0)
const views: {
  'image-url': string
  text: string
  'small-text': string
}[] = [
  {
    'image-url': 'https://emoticolor.org/cdn/initial-tutorial/screen1.png?url',
    text: 'Apprendi e approfondisci le emozioni',
    'small-text':
      'Impara a conoscerti meglio. Impara o approfondisci le tue emozioni, i tuoi stati d’animo e i tuoi sentimenti.',
  },
  {
    'image-url': 'https://emoticolor.org/cdn/initial-tutorial/screen3.png?url',
    text: 'Niente algoritmi: solo tu e ciò che segui',
    'small-text':
      'Visualizzerai solamente i post degli utenti che segui oppure che riguardano emozioni che segui. Se il post è tuo: vedi solo il numero totale di reaction; se è di un altro utente: vedi solo le reaction che hai espresso tu, così non sarai influenzato dalle reazioni degli altri.',
  },
  {
    'image-url': 'https://emoticolor.org/cdn/initial-tutorial/screen2.png?url',
    text: 'Esprimi tutte le tue emozioni, in libertà e sicurezza',
    'small-text':
      'I post pubblici sono visibili a tutti, quelli privati solo a te: il tuo diario personale digitale.',
  },
  {
    'image-url': 'https://emoticolor.org/cdn/initial-tutorial/screen4.png?url',
    text: "Tieni d'occhio i tuoi post nel tuo profilo",
    'small-text':
      'Solo tu potrai vedere il numero di utenti che ti seguono ma non chi sono: la tua popolarità è solo tua, non confrontarla con quella degli altri!',
  },
]

const animatedBarActive = ref<boolean>(false)

function goToLogin() {
  // Navigate to login view
  router.push({ name: 'login' })
}

function nextView() {
  if (selectedView.value < views.length - 1) {
    selectedView.value++
  } else {
    if (selectedView.value === views.length - 1) {
      selectedView.value++
    } else if (selectedView.value === views.length) {
      animatedBarActive.value = true
      usefulFunctions.saveToLocalStorage('initial-tutorial-seen', 'true')
      setTimeout(() => {
        goToLogin()
      }, 1000)
      selectedView.value++
    }
  }
}

function previousView() {
  if (selectedView.value > 0) {
    selectedView.value--
  }
}

// --- Swipe handling (touch + mouse fallback) ---
const touchStartX = ref<number | null>(null)
const touchStartY = ref<number | null>(null)
const touchEndX = ref<number | null>(null)
const touchEndY = ref<number | null>(null)
const mouseDown = ref<boolean>(false)

const SWIPE_THRESHOLD = 60 // px
const MAX_VERTICAL_DELTA = 120 // px - to ensure mostly horizontal swipe

function handleTouchStart(e: TouchEvent) {
  const t = e.touches?.[0]
  if (!t) return
  touchStartX.value = t.clientX
  touchStartY.value = t.clientY
  touchEndX.value = null
  touchEndY.value = null
}

function handleTouchMove(e: TouchEvent) {
  const t = e.touches?.[0]
  if (!t) return
  touchEndX.value = t.clientX
  touchEndY.value = t.clientY
}

function handleTouchEnd() {
  if (touchStartX.value === null || touchEndX.value === null) {
    // nothing meaningful happened
    touchStartX.value = touchStartY.value = touchEndX.value = touchEndY.value = null
    return
  }
  const dx = touchEndX.value - touchStartX.value
  const dy = (touchEndY.value ?? 0) - (touchStartY.value ?? 0)
  if (Math.abs(dx) > SWIPE_THRESHOLD && Math.abs(dy) < MAX_VERTICAL_DELTA) {
    if (dx < 0) {
      // swipe left -> next
      nextView()
    } else {
      // swipe right -> previous
      previousView()
    }
  }
  touchStartX.value = touchStartY.value = touchEndX.value = touchEndY.value = null
}

function handleMouseDown(e: MouseEvent) {
  if (e.button !== 0) return // only primary button
  mouseDown.value = true
  touchStartX.value = e.clientX
  touchStartY.value = e.clientY
}

function handleMouseMove(e: MouseEvent) {
  if (!mouseDown.value) return
  touchEndX.value = e.clientX
  touchEndY.value = e.clientY
}

function handleMouseUp() {
  if (!mouseDown.value) return
  mouseDown.value = false
  handleTouchEnd()
}
</script>

<template>
  <topbar variant="simple-big"></topbar>
  <main
    v-if="views.length > 0 && selectedView >= 0 && selectedView < views.length"
    @touchstart.passive="handleTouchStart"
    @touchmove.passive="handleTouchMove"
    @touchend.passive="handleTouchEnd"
    @mousedown="handleMouseDown"
    @mousemove="handleMouseMove"
    @mouseup="handleMouseUp"
  >
    <div class="top" v-if="views[selectedView]?.['image-url'] !== ''">
      <div class="image">
        <img :src="views[selectedView]?.['image-url']" alt="tutorial image" />
      </div>
    </div>
    <div
      class="middle"
      v-if="
        (views.length > 0 &&
          selectedView >= 0 &&
          selectedView < views.length &&
          views[selectedView]?.text !== '') ||
        views[selectedView]?.['small-text'] !== ''
      "
    >
      <h2 v-if="views[selectedView]?.text !== ''">
        {{ views[selectedView]?.text }}
      </h2>
      <text-info
        :show-icon="false"
        v-if="views[selectedView]?.['small-text'] !== ''"
        align="center"
      >
        {{ views[selectedView]?.['small-text'] }}
      </text-info>
    </div>
  </main>
  <main
    v-else
    class="last-screen"
    @touchstart.passive="handleTouchStart"
    @touchmove.passive="handleTouchMove"
    @touchend.passive="handleTouchEnd"
    @mousedown="handleMouseDown"
    @mousemove="handleMouseMove"
    @mouseup="handleMouseUp"
  >
    <div class="top">
      <div class="text blue-50">Dai voce a ogni tua emozione</div>
      <div class="text blue-40">Esplora, condividi, impara a sentire</div>
      <div class="text blue-30">Esprimi chi sei, emozione dopo emozione</div>
    </div>
    <div class="middle">
      <div class="button">
        <button-generic
          variant="cta"
          text="Inizia l'esperienza"
          icon-position="end"
          icon="forward"
          @action="nextView"
          :full-width="false"
        />
      </div>
    </div>
  </main>
  <div class="bottom" v-if="views.length > 0 && selectedView >= 0 && selectedView < views.length">
    <div class="progress-dots">
      <div
        class="dot"
        v-for="(_, i) in views"
        :key="i"
        :class="{ active: i === selectedView }"
      ></div>
    </div>
    <div class="buttons" v-if="views.length > 0">
      <button-generic
        variant="primary"
        text="Indietro"
        icon-position="start"
        icon="back"
        v-if="selectedView > 0"
        @action="previousView"
      />
      <button-generic
        variant="cta"
        text="Avanti"
        icon-position="end"
        icon="forward"
        @action="nextView"
      />
    </div>
  </div>

  <div class="bar animated-bar animated-bar-top" :class="animatedBarActive ? 'active' : ''">
    <div class="purple"></div>
    <div class="yellow"></div>
    <div class="red"></div>
    <div class="blue"></div>
    <div class="gray"></div>
    <div class="green"></div>
    <div class="brown"></div>
  </div>
  <div class="bar animated-bar animated-bar-bottom" :class="animatedBarActive ? 'active' : ''">
    <div class="purple"></div>
    <div class="yellow"></div>
    <div class="red"></div>
    <div class="blue"></div>
    <div class="gray"></div>
    <div class="green"></div>
    <div class="brown"></div>
  </div>
</template>

<style scoped lang="scss">
main {
  display: flex;
  flex-direction: column;
  flex: 1;
  align-items: center;
  justify-content: space-between;

  padding: var(--padding);
  gap: var(--spacing-32);

  .top,
  .middle {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-16);
    width: 100%;
  }

  .top {
    .image {
      display: flex;
      justify-content: center;
      align-items: center;
      width: auto;
      overflow: visible;
      height: auto;
      max-height: 400px;
      img {
        width: 60%;
        height: auto;
        max-height: 300px;
        object-fit: cover;
        max-width: 300px;
        min-width: 200px;
        border-radius: var(--border-radius);
        object-position: top;
        box-shadow: 0px 0px var(--spacing-8) var(--color-blue-10);
      }
    }
  }

  .middle {
    height: auto;
    flex: 1;
    align-content: space-between;
    justify-content: space-between;

    h2 {
      font: var(--font-title);
      color: var(--primary);
      text-align: center;
    }
    .small {
      text-align: center;
    }
  }

  &.last-screen {
    padding: var(--no-padding);

    .top {
      justify-content: start;
      align-items: center;
      gap: var(--no-spacing);
      text-align: center;

      > .text {
        padding: var(--padding-32);
        font: var(--font-title);
        background-color: var(--color-blue-10);
        color: var(--on-primary);
        width: 100%;

        &.blue-50 {
          background-color: var(--color-blue-50);
        }
        &.blue-40 {
          background-color: var(--color-blue-40);
        }
        &.blue-30 {
          background-color: var(--color-blue-30);
        }
      }
    }
    .middle {
      flex: 1;
      justify-content: center;
      align-content: center;
      width: 70%;
    }
  }
}

.bottom {
  padding: var(--padding);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing);
  background-color: var(--color-blue-10);
  margin-bottom: 10px;

  .progress-dots {
    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);

    .dot {
      width: 8px;
      height: 8px;
      border-radius: var(--padding-4);
      background-color: var(--color-blue-20);
      transition: 0.5s all;

      &.active {
        background-color: var(--secondary);
        width: 32px;
      }
    }
  }

  .buttons {
    display: flex;
    flex-direction: row;
    gap: var(--spacing);
    width: 100%;

    > * {
      flex: 1;
    }
  }
}

.bar {
  &.animated-bar {
    transition: 0.5s all;
    z-index: 999;

    * {
      transition: 0.5s all;
    }
    &.animated-bar-top {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
    }
    &.animated-bar-bottom {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
    }

    &.active {
      height: 50vh;
    }
  }
}
</style>
