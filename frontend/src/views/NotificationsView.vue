<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'
import Separator from '@/components/separator.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import type { notificationInterface } from '@/utils/types.ts'
import Toast from '@/components/modal/toast.vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import InfiniteScroll from '@/components/container/infinite-scroll.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import Spinner from '@/components/spinner.vue'

const offsetNotification = ref(0)
const limitNotification = 5
const hasMore = ref(true)

const isScrolled = ref(false)
const isRefreshing = ref(false)
const refreshCounter = ref(0)

const isLoading = ref<boolean>(false)

const notifications = ref<notificationInterface[]>([])

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

onMounted(() => {
  loadNotifications()
})

function loadNotifications() {
  // Prevent duplicate concurrent loads
  if (isLoading.value) return

  // Optional: check connectivity
  if (!usefulFunctions.isInternetConnected()) {
    errorMessageToastText.value = `Nessuna connessione a internet. Riprova più tardi.`
    errorMessageToastRef.value = true
    isRefreshing.value = false
    return
  }

  isLoading.value = true
  apiService
    .getNotifications(limitNotification, offsetNotification.value)
    .then((response) => {
      if (response && response.data && response.status === 200) {
        const data = Array.isArray(response.data) ? response.data : []
        if (offsetNotification.value === 0) {
          // First page -> replace
          notifications.value = data
        } else {
          // Subsequent pages -> append
          notifications.value = [...notifications.value, ...data]
        }
        // If returned less than limit, no more pages
        if (data.length < limitNotification) {
          hasMore.value = false
        }
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante il caricamento delle notifiche. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error fetching notifications:', error)
      errorMessageToastText.value = `Si è verificato un errore durante il caricamento delle notifiche. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoading.value = false
      isRefreshing.value = false
    })
}

function markAsRead(notificationId: number, postId: string) {
  apiService
    .markNotificationsAsRead(notificationId)
    .then((response) => {
      if (response && response.status >= 200 && response.status < 300) {
        const notification = notifications.value.find(
          (n) => n['notification-id'] === notificationId,
        )
        if (notification) {
          notification['is-read'] = true
        }
        // Navigate to the related post only on success
        goToPostById(postId)
      } else {
        console.error('Failed to mark notification as read. Response:', response)
        const status = response?.status ?? 'unknown'
        errorMessageToastText.value = `${status} | Si è verificato un errore durante l'aggiornamento della notifica. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      console.error('Error marking notification as read:', error)
      errorMessageToastText.value = `Si è verificato un errore durante l'aggiornamento della notifica. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
}

function loadMoreNotifications() {
  if (isLoading.value || !hasMore.value) return
  offsetNotification.value += limitNotification
  loadNotifications()
}

function refreshNotifications() {
  isRefreshing.value = true
  offsetNotification.value = 0
  hasMore.value = true
  refreshCounter.value++
  loadNotifications()
}

function goToPostById(postId: string) {
  router.push('post/' + postId)
}

function goToHome() {
  router.push({ name: 'home' })
}

function changeView(index: number) {
  if (index === 0) {
    router.push({ name: 'learning' })
  } else if (index === 1) {
    router.push({ name: 'home' })
  } else if (index === 2) {
    router.push({ name: 'profile' })
  }
}
</script>

<template>
  <topbar variant="standard" :show-back-button="true" @onback="goToHome" title="Notifiche"></topbar>
  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="refreshNotifications"
    @scrolled="isScrolled = $event"
  >
    <infinite-scroll :loading="isLoading" :has-more="hasMore" @load-more="loadMoreNotifications">
      <main>
        <div
          class="notification-card"
          v-for="notification in notifications"
          :class="{ unread: !notification['is-read'] }"
          :key="notification['notification-id']"
        >
          <div class="notification-details">
            <div class="username-info">
              <img
                :src="`https://gravatar.com/avatar/${notification['profile-image']}?url`"
                :alt="`Avatar ${notification.username}`"
                class="avatar"
              />
              <div class="username-date">
                <div class="font-subtitle">@{{ notification.username }}</div>
                <div class="datetime">
                  {{ usefulFunctions.getDatetimeToShow(notification['notification-datetime']) }}
                </div>
              </div>
            </div>
            <separator variant="primary"></separator>
            <div class="notification-message">
              <text-paragraph align="start" color="black">
                <span>@{{ notification.username }}</span> stava provando
                <span class="strong">{{ notification['post-emotion-text'] }}</span>
              </text-paragraph>
            </div>
          </div>
          <div class="notification-button">
            <button-generic
              @click="markAsRead(notification['notification-id'], notification['post-id'])"
              text=""
              icon="forward"
              variant="primary"
              :disabled-hover-effect="true"
              :small="true"
            />
          </div>
        </div>

        <div
          class="no-contents"
          v-if="!isLoading && (!notifications || notifications.length === 0)"
        >
          <text-paragraph> Non ci sono notifiche da visualizzare. </text-paragraph>
        </div>
        <div class="loading" v-if="isLoading">
          <spinner color="primary" />
        </div>
      </main>
    </infinite-scroll>
  </pull-to-refresh>
  <navbar @tab-change="changeView($event)" :selected-tab="-1"></navbar>

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
  padding: var(--padding-16);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16) !important;

  .notification-card {
    background-color: var(--color-gray-10);
    border-left: 4px solid transparent;

    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);
    border-radius: var(--border-radius);
    padding: var(--padding-8);

    width: 100%;

    .notification-details {
      height: 100%;
      flex: 1;

      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;

      gap: var(--spacing-8);

      .username-info {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: var(--spacing-8);
        width: 100%;

        .avatar {
          width: 50px;
          height: 50px;
          border-radius: var(--border-radius-8);
          object-fit: cover;
        }

        .username-date {
          display: flex;
          flex-direction: column;
          gap: var(--spacing-16);

          flex: 1;

          .font-subtitle {
            color: var(--primary);
          }

          .datetime {
            color: var(--primary);
            font: var(--font-small);
          }
        }
      }

      .notification-message {
        width: 100%;
        padding: var(--padding-8);
      }
    }
    .notification-button {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    &.unread {
      background-color: var(--color-blue-10);
      border-color: var(--color-blue-20);
    }
  }

  .no-contents {
    text-align: center;
    padding: var(--padding-32);
    color: var(--color-gray-50);
  }

  .loading {
    display: flex;
    justify-content: center;
    padding: var(--padding-32);
  }
}
</style>
