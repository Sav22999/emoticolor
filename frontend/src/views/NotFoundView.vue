<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import TextInfo from '@/components/text/text-info.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'

const timeoutDuration = ref<number>(15000)

onMounted(() => {
  setTimeout(decreaseTimeout, 1000)
})

function decreaseTimeout() {
  if (timeoutDuration.value > 1000) {
    timeoutDuration.value -= 1000
    setTimeout(decreaseTimeout, 1000)
  } else {
    goToHome()
  }
}

function goToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <topbar variant="simple-big" title="404 - Pagina non trovata"></topbar>
  <main>
    <div class="content">
      <text-paragraph>
        Spiacenti, la pagina che stai cercando non esiste o è stata spostata.
      </text-paragraph>
      <div class="info-box">
        <button-generic text="Vai alla home" :full-width="true" icon="forward" @action="goToHome" />
        <text-info :show-icon="false">
          Sarai reindirizzato automaticamente alla home in
          <strong>{{ timeoutDuration / 1000 }}</strong> secondi.
        </text-info>
      </div>
    </div>
  </main>
  <div class="bar">
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
  background-color: var(--color-white);
  color: var(--primary);
  height: 100vh;
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: var(--padding-32);

  .content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: var(--spacing-16);
    text-align: center;

    h1 {
      font: var(--font-title);
    }

    p {
      font: var(--font-paragraph);
    }

    .info-box {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-4);
    }
  }
}
</style>
