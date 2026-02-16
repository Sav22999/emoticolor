<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import topbar from '@/components/header/topbar.vue'
import { onMounted, onUnmounted, ref } from 'vue'
import TextInfo from '@/components/text/text-info.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import apiService from '@/utils/api/api-service'
import router from '@/router'

const timeoutDuration = ref<number>(15000)
const isLoading = ref<boolean>(false)
let timer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  timer = setInterval(decreaseTimeout, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

function decreaseTimeout() {
  if (timeoutDuration.value > 1000) {
    timeoutDuration.value -= 1000
  } else {
    checkConnection()
  }
}

async function checkConnection() {
  if (isLoading.value) return

  isLoading.value = true
  timeoutDuration.value = 15000
  const isOnline = navigator.onLine
  let isApiReachable = true

  if (isOnline) {
    isApiReachable = await apiService.ping()
  }

  if (isOnline && isApiReachable) {
    if (router.currentRoute.value.name === 'no-internet-connection') {
      router.go(-1)
    }
  }
  isLoading.value = false
}
</script>

<template>
  <topbar variant="simple-big" title="Problema di connessione" />
  <main>
    <div class="content">
      <text-paragraph>
        Spiacenti, sembra che non sia possibile connettersi ai nostri servizi.
      </text-paragraph>
      <text-paragraph>
        Controlla la tua connessione a Internet e riprova. Se sei connesso a una VPN, prova a
        disattivarla, poiché potrebbe interferire con il collegamento al server.
      </text-paragraph>
      <div class="info-box">
        <button-generic
          text="Controlla connessione"
          :full-width="true"
          icon="refresh"
          :loading="isLoading"
          @action="checkConnection"
        />
        <text-info :show-icon="false">
          Verrà effettuato un controllo della connessione automaticamente tra
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
