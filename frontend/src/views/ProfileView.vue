<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { userProfileInterface } from '@/utils/types.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'
import TextLabel from '@/components/text/text-label.vue'
import Spinner from '@/components/spinner.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import TextInfo from '@/components/text/text-info.vue'

const username = ref<string | null>(null) //if null it's "my" profile, else it's the username of the profile being viewed
const userDetails = ref<userProfileInterface | null>(null)
const isLoading = ref<boolean>(true)

onMounted(() => {
  // verify the route params to see if a username is provided
  const routeUsername = router.currentRoute.value.params.username
  if (routeUsername && typeof routeUsername === 'string') {
    username.value = routeUsername
  } else {
    username.value = null
  }

  isLoading.value = true
  apiService.getUserDetails(username.value).then((response) => {
    isLoading.value = false
    if (response.status === 200) {
      if (response.data) {
        userDetails.value = response.data
        console.log(response.data)
      }
    }
  })
})

function doAction(name: string) {
  console.log('Action:', name)
}

function changeView(index: number) {
  if (index === 0) {
    // Navigate to learning view
    router.push({ name: 'learning' })
  } else if (index === 1) {
    // Navigate to home view
    router.push({ name: 'home' })
  } else if (index === 2) {
    // Stay in profile view
  }
}

function toggleUserFollow(username: string, follow: boolean) {
  apiService.toggleUserFollow(username, follow ? 'unfollow' : 'follow').then((response) => {
    console.log(response)
    if (response && response.status === 204) {
      if (userDetails.value) userDetails.value['is-following'] = !follow
    }
  })
}

function goToHome() {
  router.push({ name: 'home' })
}

function goToSettings() {
  router.push({ name: 'settings' })
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-settings-button="!((userDetails && !userDetails['is-own-profile']) ?? false)"
    :show-back-button="(userDetails && !userDetails['is-own-profile']) ?? false"
    @onback="goToHome()"
    @onsettings="goToSettings()"
  ></topbar>
  <main>
    <div class="header-user" v-if="userDetails">
      <div class="card-my-profile" v-if="userDetails['is-own-profile'] === true">
        <img
          :src="`https://gravatar.com/avatar/${userDetails['profile-image']}?url`"
          class="avatar"
        />
        <div class="username">@{{ userDetails.username }}</div>
        <div class="buttons">
          <text-label text="Utente" color="primary"></text-label>
        </div>
      </div>
      <div class="card-other-profile" v-else>
        <img
          :src="`https://gravatar.com/avatar/${userDetails['profile-image']}?url`"
          class="avatar"
        />
        <div class="username">@{{ userDetails.username }}</div>
        <div class="buttons">
          <button-generic
            variant="primary"
            :text="userDetails['is-following'] ? 'Smetti di seguire' : 'Segui'"
            :small="true"
            icon-position="end"
            :icon="userDetails['is-following'] ? 'follow-n' : 'follow-y'"
            @action="toggleUserFollow(userDetails.username, userDetails['is-following'] ?? false)"
          ></button-generic>
        </div>
      </div>
      <div class="bio" v-if="userDetails.bio">
        <text-info align="start" :show-icon="false">Bio</text-info>
        <text-paragraph align="justify" color="black">
          {{ userDetails.bio }}
        </text-paragraph>
      </div>
    </div>
    <div class="loading-contents" v-if="isLoading">
      <spinner color="primary" />
    </div>
  </main>
  <navbar
    @tab-change="changeView($event)"
    :selected-tab="2"
    v-if="userDetails && userDetails['is-own-profile'] === true"
  ></navbar>
</template>

<style scoped lang="scss">
.header-user {
  background-color: var(--color-blue-10);
  border-radius: var(--no-border-radius);
  border-bottom: 5px solid var(--primary);
  display: flex;
  flex-direction: column;
  gap: var(--no-spacing);

  .card-my-profile,
  .card-other-profile {
    border-radius: var(--no-border-radius);
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

    .username {
      font: var(--font-subtitle);
      color: var(--primary);
      flex-grow: 1;
      padding: var(--padding-4);
      word-break: break-all;
    }
    .buttons {
      display: flex;
      flex-direction: row;
      gap: var(--spacing-8);
      align-items: center;
      justify-content: center;
    }
  }
  .bio {
    padding: var(--padding-16);
  }
}

.loading-contents {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--spacing-16);
  min-height: 200px;
}
</style>
