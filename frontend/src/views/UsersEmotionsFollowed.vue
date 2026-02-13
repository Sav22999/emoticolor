<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { followedEmotionInterface, followedUserInterface } from '@/utils/types.ts'
import Toast from '@/components/modal/toast.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import Spinner from '@/components/spinner.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import HorizontalOverflow from '@/components/container/horizontal-overflow.vue'
import InputChip from '@/components/input/input-chip.vue'

const isLoadingUsers = ref<boolean>(false)
const isLoadingEmotions = ref<boolean>(false)

const isScrolled = ref(false)
const isRefreshing = ref(false)

const usersFollowed = ref<followedUserInterface[]>([])
const emotionsFollowed = ref<followedEmotionInterface[]>([])

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const chipUsersEnabled = ref<boolean>(true)
const chipEmotionsEnabled = ref<boolean>(true)

onMounted(() => {
  loadUsersAndEmotions()
})

function loadUsersAndEmotions() {
  loadEmotions()
  loadUsers()
}

function loadEmotions() {
  isLoadingEmotions.value = true
  apiService
    .getFollowedEmotions()
    .then((response) => {
      if (response && response.data && (response.status === 200 || response.status === 201)) {
        console.log('Followed Emotions:', response.data)
        emotionsFollowed.value = response.data
        //check if each emotionsFollowed.value item has 'is-followed' property, if not add it with value true
        emotionsFollowed.value = emotionsFollowed.value.map((emotion) => {
          if (!emotion.hasOwnProperty('is-followed')) {
            return { ...emotion, 'is-followed': true }
          }
          return emotion
        })
      } else {
        errorMessageToastText.value = `${response.status} | Errore — Impossibile caricare le emozioni seguite. ${(response as { message?: string })?.message ?? 'Riprova più tardi.'}`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching followed emotions:', error)
      errorMessageToastText.value = `${(error as { status?: number })?.status ?? ''} | Errore — Impossibile caricare le emozioni seguite. ${(error as { message?: string })?.message ?? 'Riprova più tardi.'}`
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
      if (response && response.data && (response.status === 200 || response.status === 201)) {
        console.log('Followed Users:', response.data)
        usersFollowed.value = response.data
        //check if each usersFollowed.value item has 'is-followed' property, if not add it with value true
        usersFollowed.value = usersFollowed.value.map((user) => {
          if (!user.hasOwnProperty('is-followed')) {
            return { ...user, 'is-followed': true }
          }
          return user
        })
      } else {
        errorMessageToastText.value = `${response.status} | Errore — Impossibile caricare gli utenti seguiti. ${(response as { message?: string })?.message ?? 'Riprova più tardi.'}`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching followed users:', error)
      errorMessageToastText.value = `${(error as { status?: number })?.status ?? ''} | Errore — Impossibile caricare gli utenti seguiti. ${(error as { message?: string })?.message ?? 'Riprova più tardi.'}`
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
          return { ...user, 'is-followed': !follow }
        }
        return user
      })
    } else {
      errorMessageToastText.value = `${response.status} | Errore — Operazione non riuscita. ${(response as { message?: string })?.message ?? 'Riprova più tardi.'}`
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
          return { ...emotion, 'is-followed': !follow }
        }
        return emotion
      })
    } else {
      errorMessageToastText.value = `${response.status} | Errore — Operazione non riuscita. ${(response as { message?: string })?.message ?? 'Riprova più tardi.'}`
      errorMessageToastRef.value = true
    }
  })
}

function goToMyProfile() {
  router.push({ name: 'profile' })
}

function openProfile(username: string) {
  router.push('/profile/' + username)
}

function openEmotionPage(emotionId: number) {
  router.push('/learning/emotion/' + emotionId)
}

function toggleEmotionsFollow() {
  chipEmotionsEnabled.value = !chipEmotionsEnabled.value
}

function toggleUsersFollow() {
  chipUsersEnabled.value = !chipUsersEnabled.value
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goToMyProfile"
    title="Utenti ed emozioni seguiti"
  ></topbar>
  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="loadUsersAndEmotions"
    @scrolled="isScrolled = $event"
  >
    <main>
      <div class="chips">
        <horizontal-overflow>
          <div class="all-chips">
            <input-chip
              text="Utenti"
              @toggle="toggleUsersFollow()"
              :enabled-by-default="chipUsersEnabled"
            />
            <input-chip
              text="Emozioni"
              @toggle="toggleEmotionsFollow()"
              :enabled-by-default="chipEmotionsEnabled"
            />
          </div>
        </horizontal-overflow>
      </div>

      <div class="font-subtitle" v-if="chipEmotionsEnabled">Emozioni seguite</div>
      <div
        class="results"
        v-if="
          !isLoadingEmotions &&
          emotionsFollowed &&
          emotionsFollowed.length > 0 &&
          chipEmotionsEnabled
        "
      >
        <div class="item" v-for="result in emotionsFollowed" :key="result['emotion-id']">
          <div class="card-emotion">
            <div class="emotion-name clickable" @click="openEmotionPage(result['emotion-id'])">
              {{ result['emotion-text'] }}
            </div>
            <div class="buttons">
              <button-generic
                variant="primary"
                :text="result['is-followed'] ? 'Smetti di seguire' : 'Segui'"
                :small="true"
                icon-position="end"
                :icon="result['is-followed'] ? 'remove-circle' : 'plus-circle'"
                @action="toggleEmotionFollow(result['emotion-id'], result['is-followed'] ?? false)"
              ></button-generic>
            </div>
          </div>
        </div>
      </div>
      <div
        class="no-contents"
        v-else-if="
          !isLoadingEmotions &&
          emotionsFollowed &&
          emotionsFollowed.length === 0 &&
          chipEmotionsEnabled
        "
      >
        <text-paragraph> Non stai seguendo nessuna emozione. </text-paragraph>
      </div>
      <div class="loading-contents" v-else-if="isLoadingEmotions && chipEmotionsEnabled">
        <spinner color="primary" />
      </div>

      <div class="font-subtitle" v-if="chipUsersEnabled">Utenti seguiti</div>
      <div
        class="results"
        v-if="!isLoadingUsers && usersFollowed && usersFollowed.length > 0 && chipUsersEnabled"
      >
        <div class="item" v-for="result in usersFollowed" :key="result.username">
          <div class="card-user">
            <img
              :src="`https://gravatar.com/avatar/${result['profile-image']}?url`"
              alt="avatar"
              class="avatar clickable"
              @click="openProfile(result.username)"
            />
            <div class="username clickable" @click="openProfile(result.username)">
              @{{ result.username }}
            </div>
            <div class="buttons">
              <button-generic
                variant="primary"
                :text="result['is-followed'] ? 'Smetti di seguire' : 'Segui'"
                :small="true"
                icon-position="end"
                :icon="result['is-followed'] ? 'remove-circle' : 'plus-circle'"
                @action="toggleUserFollow(result.username, result['is-followed'] ?? false)"
              ></button-generic>
            </div>
          </div>
        </div>
      </div>
      <div
        class="no-contents"
        v-else-if="
          !isLoadingUsers && usersFollowed && usersFollowed.length === 0 && chipUsersEnabled
        "
      >
        <text-paragraph> Non stai seguendo nessun utente. </text-paragraph>
      </div>
      <div class="loading-contents" v-else-if="isLoadingUsers && chipUsersEnabled">
        <spinner color="primary" />
      </div>
    </main>
  </pull-to-refresh>

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

<style scoped lang="scss">
main {
  padding: var(--padding);
  display: flex;
  gap: var(--spacing-16);
  flex-direction: column;

  .chips {
    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);
    width: auto;

    .all-chips {
      display: flex;
      flex-direction: row;
      gap: var(--spacing-8);
    }
  }

  .font-subtitle {
    color: var(--primary);
  }

  .results {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-16);
    padding: var(--no-padding);

    .card-user,
    .card-emotion {
      background-color: var(--color-blue-10);
      border-radius: var(--border-radius);
      padding: var(--padding-8);
      display: flex;
      flex-direction: row;
      gap: var(--spacing-4);
      align-items: center;
      justify-content: center;

      img.avatar {
        width: 50px;
        height: 50px;
        border-radius: var(--border-radius-8);
        object-fit: cover;
      }

      .username,
      .emotion-name {
        font: var(--font-subtitle);
        color: var(--primary);
        flex-grow: 1;
        padding: var(--padding-4);
        word-break: break-all;
        text-transform: capitalize;
      }
      .buttons {
        display: flex;
        flex-direction: row;
        gap: var(--spacing-8);
        align-items: center;
        justify-content: center;
      }
    }
  }

  .loading-contents {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: var(--spacing-16);
    min-height: 100px;
  }

  .no-contents {
    min-height: 100px;
  }
}
</style>
