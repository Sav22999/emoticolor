<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { followedEmotionInterface, followedUserInterface } from '@/utils/types.ts'
import Toast from '@/components/modal/toast.vue'

const isLoadingUsers = ref<boolean>(false)
const isLoadingEmotions = ref<boolean>(false)

const usersFollowed = ref<followedUserInterface[]>([])
const emotionsFollowed = ref<followedEmotionInterface[]>([])

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

onMounted(() => {
  isLoadingEmotions.value = true

  loadEmotions()
  loadUsers()
})

function loadEmotions() {
  apiService
    .getFollowedEmotions()
    .then((response) => {
      if (response && response.data && response.status === 200) {
        console.log('Followed Emotions:', response.data)
        emotionsFollowed.value = response.data
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching followed emotions:', error)
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingEmotions.value = false
    })
}

function loadUsers() {
  isLoadingUsers.value = true
  apiService
    .getFollowedUsers()
    .then((response) => {
      if (response && response.data && response.status === 200) {
        console.log('Followed Users:', response.data)
        usersFollowed.value = response.data
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching followed users:', error)
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingUsers.value = false
    })
}

function toggleUserFollow(username: string, follow: boolean) {
  apiService.toggleUserFollow(username, follow ? 'unfollow' : 'follow').then((response) => {
    console.log(response)
    if (response && response.status === 204) {
      // Update local state
      usersFollowed.value = usersFollowed.value.map((user) => {
        if (user.username === username) {
          return { ...user, is_followed: follow ? false : true }
        }
        return user
      })
    } else {
      errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    }
  })
}

function toggleEmotionFollow(emotionId: number, follow: boolean) {
  apiService.toggleEmotionFollow(emotionId, follow ? 'unfollow' : 'follow').then((response) => {
    console.log(response)
    if (response && response.status === 204) {
      // Update local state
      emotionsFollowed.value = emotionsFollowed.value.map((emotion) => {
        if (emotion['emotion-id'] === emotionId) {
          return { ...emotion, is_followed: follow ? false : true }
        }
        return emotion
      })
    } else {
      errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    }
  })
}

function backNavigation() {
  router.back()
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="backNavigation()"
    title="Utenti ed emozioni seguiti"
  ></topbar>
  <main>
    <div class="font-subtitle">Utenti seguiti</div>
    {{ usersFollowed }}
    <div class="font-subtitle">Emozioni seguite</div>
    {{ emotionsFollowed }}
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>

  <toast
    v-if="errorMessageToastRef"
    :life-seconds="20"
    @onclose="
      () => {
        errorMessageToastRef = false
      }
    "
  >
    {{ errorMessageToastText }}
  </toast>
</template>

<style scoped lang="scss"></style>
