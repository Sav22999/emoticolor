<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, onUnmounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { ApiPostsResponse } from '@/utils/api/api-interface.ts'
import CardPost from '@/components/card/card-post.vue'
import Spinner from '@/components/spinner.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import InfiniteScroll from '@/components/container/infinite-scroll.vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import TextParagraph from '@/components/text/text-paragraph.vue'

const offsetPost = ref(0)
const limitPost = 30
const loading = ref(false)
const hasMore = ref(true)
const isRefreshing = ref(false)

const posts = ref<ApiPostsResponse | null>(null)

const isScrolled = ref(false)
const smallNewPostButton = ref<boolean>(false)
const smallNewPostButtonHover = ref<boolean>(false)

onMounted(() => {
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

function changeView(index: number) {
  if (index === 0) {
    // Navigate to learning view
    router.push({ name: 'learning' })
  } else if (index === 1) {
    // Stay in home view
  } else if (index === 2) {
    // Navigate to profile view
    router.push({ name: 'profile' })
  }
}

function handleScroll() {
  smallNewPostButton.value = window.scrollY > 100
}

function goToNotifications() {
  // Navigate to notifications view
  router.push({ name: 'notifications' })
}

function goToSearch() {
  // Navigate to search view
  router.push({ name: 'search' })
}

function loadPosts() {
  if (usefulFunctions.isInternetConnected()) {
    if (loading.value) return
    loading.value = true
    apiService
      .getHomePosts('it', offsetPost.value, limitPost)
      .then((response) => {
        //console.log('Loaded posts:', response.data)
        if (response && response.data) {
          if (offsetPost.value === 0) {
            posts.value = response
          } else {
            posts.value!.data = [...posts.value!.data, ...response.data]
          }
          if (response.data.length < limitPost) {
            hasMore.value = false
          }
        }
        loading.value = false
        isRefreshing.value = false
      })
      .catch(() => {
        loading.value = false
        isRefreshing.value = false
      })
  }
}

function loadMorePosts() {
  offsetPost.value += limitPost
  loadPosts()
}

function refreshPosts() {
  isRefreshing.value = true
  offsetPost.value = 0
  hasMore.value = true
  loadPosts()
}

function goToNewPost() {
  router.push({ name: 'create-post' })
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-search-button="true"
    :show-notifications-button="true"
    @onsearch="goToSearch"
    @onnotifications="goToNotifications"
  ></topbar>
  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="refreshPosts"
    @scrolled="isScrolled = $event"
  >
    <infinite-scroll :loading="loading" :has-more="hasMore" @load-more="loadMorePosts">
      <main>
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
          :show-always-avatar="true"
        />
        <div class="no-contents" v-if="!loading && (!posts || posts.data.length === 0)">
          <text-paragraph>
            Non hai stati emotivi da visualizzare. Puoi provare a seguire un'emozione o un utente
            per vedere i loro stati emotivi qui.
          </text-paragraph>
        </div>
        <div class="loading" v-if="loading">
          <spinner color="primary" />
        </div>
      </main>
    </infinite-scroll>
  </pull-to-refresh>
  <div class="new-post">
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
  <navbar @tab-change="changeView($event)" :selected-tab="1"></navbar>
</template>

<style scoped lang="scss">
main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding);
  position: relative;

  .loading {
    display: flex;
    justify-content: center;
    padding: var(--spacing-16);
  }

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

.flex-1 {
  flex: 1;
}
</style>
