<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, onUnmounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { userProfileInterface } from '@/utils/types.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'
import Spinner from '@/components/spinner.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import TextInfo from '@/components/text/text-info.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import InfiniteScroll from '@/components/container/infinite-scroll.vue'
import CardPost from '@/components/card/card-post.vue'
import type { ApiPostsResponse } from '@/utils/api/api-interface.ts'
import usefulFunctions from '@/utils/useful-functions.ts'
import Toast from '@/components/modal/toast.vue'

const username = ref<string | null>(null) //if null it's "my" profile, else it's the username of the profile being viewed
const userDetails = ref<userProfileInterface | null>(null)
const isLoadingUserDetails = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const offsetPosts = ref<number>(0)
const limitPosts = 30

const hasMore = ref(true)
const isRefreshing = ref(false)

const posts = ref<ApiPostsResponse | null>(null)

const isScrolled = ref(false)
const refreshCounter = ref(0)

const smallNewPostButton = ref<boolean>(false)
const smallNewPostButtonHover = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const cannotSeeFollowersToastRef = ref<boolean>(false)

onMounted(() => {
  // verify the route params to see if a username is provided
  const routeUsername = router.currentRoute.value.params.username
  if (routeUsername && typeof routeUsername === 'string') {
    username.value = routeUsername
  } else {
    username.value = null
  }

  loadUserProfile()
  loadPosts()
  window.addEventListener('scroll', () => {
    handleScroll()
  })
})
onUnmounted(() => {
  window.removeEventListener('scroll', () => {
    handleScroll()
  })
})

function loadUserProfile() {
  isLoadingUserDetails.value = true
  apiService.getUserDetails(username.value).then((response) => {
    isLoadingUserDetails.value = false
    if (response.status === 200) {
      if (response.data) {
        userDetails.value = response.data
        //console.log(response.data)
      }
    } else {
      errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    }
  })
}

function loadPosts() {
  if (usefulFunctions.isInternetConnected()) {
    if (isLoading.value) return
    isLoading.value = true
    apiService
      .getUserPosts(username.value ?? null, offsetPosts.value, limitPosts)
      .then((response) => {
        //console.log('Loaded posts:', response.data)
        if (response && response.data) {
          if (offsetPosts.value === 0) {
            posts.value = response
          } else {
            posts.value!.data = [...posts.value!.data, ...response.data]
          }
          if (response.data.length < limitPosts) {
            hasMore.value = false
          }
        } else {
          errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
          errorMessageToastRef.value = true
        }
        isLoading.value = false
        isRefreshing.value = false
      })
      .catch((error) => {
        errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
        isLoading.value = false
        isRefreshing.value = false
      })
  }
}

function loadMorePosts() {
  offsetPosts.value += limitPosts
  loadPosts()
}

function refreshPosts() {
  isRefreshing.value = true
  isLoading.value = false
  isLoadingUserDetails.value = false
  offsetPosts.value = 0
  hasMore.value = true
  refreshCounter.value++
  loadPosts()
  loadUserProfile()
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
    //console.log(response)
    if (response && response.status === 204) {
      if (userDetails.value) userDetails.value['is-following'] = !follow
    } else {
      errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    }
  })
}

function goBack() {
  router.back()
}

function goToSettings() {
  router.push({ name: 'settings' })
}

function goToUsersEmotionsFollowed() {
  router.push({ name: 'users-emotions-followed' })
}

function goToNewPost() {
  router.push({ name: 'create-post' })
}

function handleScroll() {
  smallNewPostButton.value = window.scrollY > 100
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-settings-button="!((userDetails && !userDetails['is-own-profile']) ?? false)"
    :show-back-button="(userDetails && !userDetails['is-own-profile']) ?? false"
    @onback="goBack()"
    @onsettings="goToSettings()"
  ></topbar>
  <div class="header-user" v-if="userDetails">
    <div class="card-my-profile" v-if="userDetails['is-own-profile'] === true">
      <img
        :src="`https://gravatar.com/avatar/${userDetails['profile-image']}?url`"
        class="avatar"
      />
      <div class="username">@{{ userDetails.username }}</div>
      <div class="buttons">
        <div class="text-value" v-if="userDetails" @click="cannotSeeFollowersToastRef = true">
          <div class="text-number">
            {{ userDetails['followers-count'] }}
          </div>
          <div class="text-key">seguaci</div>
        </div>
        <div class="text-value clickable" @click="goToUsersEmotionsFollowed" v-if="userDetails">
          <div class="text-number">
            {{
              (userDetails['users-followed-count'] || 0) +
              (userDetails['emotions-followed-count'] || 0)
            }}
          </div>
          <div class="text-key">seguiti</div>
        </div>
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
  <!--    <div class="loading-contents" v-if="isLoading">
      <spinner color="primary" />
    </div>-->

  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="refreshPosts"
    @scrolled="isScrolled = $event"
  >
    <infinite-scroll :loading="isLoading" :has-more="hasMore" @load-more="loadMorePosts">
      <main>
        <div class="posts-container">
          <!--    <generic icon="search" @input="doAction($event)"></generic>
          <password @input="doAction($event)"></password>-->
          <card-post
            v-for="post in posts?.data"
            :key="post['post-id']"
            :id="post['post-id']"
            :datetime="post['created']"
            :username="post['username']"
            :profile-image="post['profile-image']"
            :emotion="post['emotion-text']"
            :emotion-id="post['emotion-id']"
            :color-hex="post['color-hex']"
            :visibility="post['visibility'] === 0 ? 'public' : 'private'"
            :is-user-followed="post['is-user-followed']"
            :is-emotion-followed="post['is-emotion-followed']"
            :is-own-post="post['is-own-post']"
            :content-text="post['text']"
            :content-weather="post['weather-text']"
            :content-location="post['location']"
            :content-place="post['place-text']"
            :content-together-with="post['together-with-text']"
            :content-body-part="post['body-part-text']"
            :content-image="post['image']"
            :expanded-by-default="false"
            :refresh-trigger="refreshCounter"
            :reactions-props="post['reactions']"
            :show-always-avatar="false"
          />
          <div class="no-contents" v-if="!isLoading && (!posts || posts.data.length === 0)">
            <text-paragraph>
              Non hai stati emotivi da visualizzare.
              <span v-if="userDetails && userDetails['is-own-profile']">
                Creane uno nuovo premendo il pulsante in basso!
              </span>
            </text-paragraph>
          </div>
          <div class="loading" v-if="isLoading || isLoadingUserDetails">
            <spinner color="primary" />
          </div>
        </div>
      </main>
    </infinite-scroll>
  </pull-to-refresh>
  <div class="new-post" v-if="userDetails && userDetails['is-own-profile'] === true">
    <button-generic
      variant="cta"
      icon="plus"
      :text="!smallNewPostButton ? 'Crea un nuovo stato emotivo' : 'Crea un nuovo stato emotivo'"
      :full-width="!smallNewPostButton || smallNewPostButtonHover"
      :small="smallNewPostButton && !smallNewPostButtonHover"
      :class="{
        scrolled: smallNewPostButton,
        'scrolled-hover': smallNewPostButton && smallNewPostButtonHover,
      }"
      @action="goToNewPost"
      @mouseenter="smallNewPostButtonHover = true"
      @mouseleave="smallNewPostButtonHover = false"
    />
  </div>
  <navbar
    @tab-change="changeView($event)"
    :selected-tab="2"
    v-if="userDetails && userDetails['is-own-profile'] === true"
  ></navbar>

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

  <toast
    v-if="cannotSeeFollowersToastRef"
    :life-seconds="0"
    variant="standard"
    :show-button="true"
    @onclose="
      () => {
        cannotSeeFollowersToastRef = false
      }
    "
  >
    In Emoticolor non puoi vedere la lista degli utenti che ti seguono, ma solo il conteggio totale.
    <br />
    Questo è un approccio pensato per proteggere la privacy e l'anonimato degli utenti.
    <br />
    <b>Sentiti libero di poter esprimere al meglio, senza l'ansia di sapere chi legge!</b>
  </toast>
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

    .text-value {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-4);
    }

    .buttons {
      display: flex;
      flex-direction: row;
      gap: var(--spacing-8);
      align-items: center;
      justify-content: center;
      padding: var(--padding-4);

      .text-value {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--no-spacing);

        .text-number {
          font: var(--font-subtitle);
          color: var(--color-blue-70);
        }

        .text-key {
          font: var(--font-small);
          color: var(--primary);
        }
      }
    }
  }
  .bio {
    padding: var(--padding-16);
  }
}

.loading-contents,
.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--spacing-16);
  min-height: 200px;
}

.posts-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding);
  position: relative;

  padding-bottom: calc(var(--padding) + 40px);
}

.new-post {
  position: fixed;
  bottom: calc(50px + var(--spacing-16));
  left: var(--spacing-16);
  right: var(--spacing-16);
  z-index: 99;
  display: flex;
  justify-content: center;
  align-content: center;

  &.scrolled:not(.scrolled-hover) {
    width: auto;
    margin: 0 auto;
  }
}
</style>
