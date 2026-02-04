<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'
import Separator from '@/components/separator.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'

const isLoading = ref<boolean>(false)

onMounted(() => {
  loadNotifications()
})

function loadNotifications() {
  isLoading.value = true
  apiService
    .getNotifications()
    .then((response) => {
      // Handle the response data
      console.log('Notifications:', response.data)
    })
    .catch((error) => {
      // Handle any errors
      console.error('Error fetching notifications:', error)
    })
    .finally(() => {
      isLoading.value = false
    })
}

function markAsRead(notificationId: number) {
  apiService
    .markNotificationsAsRead(notificationId)
    .then((response) => {
      console.log('Notification marked as read:', response.data)
    })
    .catch((error) => {
      console.error('Error marking notification as read:', error)
    })
}

function doAction(name: string) {
  console.log('Action:', name)
}

function goToHome() {
  // Navigate to home view
  router.push({ name: 'home' })
}

function changeView(index: number) {
  if (index === 0) {
    // Navigate to learning view
    router.push({ name: 'learning' })
  } else if (index === 1) {
    // Navigate to home view
    router.push({ name: 'home' })
  } else if (index === 2) {
    // Navigate to profile view
    router.push({ name: 'profile' })
  }
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar variant="standard" :show-back-button="true" @onback="goToHome" title="Notifiche"></topbar>
  <main>
    <div class="notification-card unread">
      <div class="notification-details">
        <div class="username-info">
          <img src="" class="avatar" />
          <div class="username-date">
            <div class="font-subtitle">@prova</div>
            <div class="datetime">25 minuti fa</div>
          </div>
        </div>
        <separator variant="primary"></separator>
        <div class="notification-message">
          <text-paragraph align="start" color="black">
            <span>@username</span> stava provando <span class="strong">tristezza</span>
          </text-paragraph>
        </div>
      </div>
      <div class="notification-button">
        <button-generic
          @click="doAction('Mark as Read')"
          text=""
          icon="forward"
          variant="primary"
          :disabled-hover-effect="true"
          :small="true"
        />
      </div>
    </div>
  </main>
  <navbar @tab-change="changeView($event)" :selected-tab="-1"></navbar>
</template>

<style scoped lang="scss">
main {
  padding: var(--padding-16);

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
}
</style>
