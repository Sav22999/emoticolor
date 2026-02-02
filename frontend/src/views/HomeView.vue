<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, onUnmounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { ApiPostsResponse } from '@/utils/api/api-interface.ts'
import CardPost from '@/components/card/card-post.vue'
import Spinner from '@/components/spinner.vue'

const offsetPost = ref(0)
const limitPost = 50
const loading = ref(false)
const hasMore = ref(true)

const posts = ref<ApiPostsResponse | null>(null)

onMounted(() => {
  loadPosts()
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

function handleScroll() {
  if (loading.value || !hasMore.value) return
  const threshold = 100 // pixels before bottom
  if (window.scrollY + window.innerHeight >= document.body.scrollHeight - threshold) {
    loadMorePosts()
  }
}

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

function goToNotifications() {
  // Navigate to notifications view
  router.push({ name: 'notifications' })
}

function goToSearch() {
  // Navigate to search view
  router.push({ name: 'search' })
}

function loadPosts() {
  if (loading.value) return
  loading.value = true
  apiService
    .getHomePosts('it', offsetPost.value, limitPost)
    .then((response) => {
      console.log('Loaded posts:', response.data)
      if (response && response.data) {
        if (posts.value) {
          posts.value.data = [...posts.value.data, ...response.data]
        } else {
          posts.value = response
        }
        if (response.data.length < limitPost) {
          hasMore.value = false
        }
      }
      loading.value = false
    })
    .catch(() => {
      loading.value = false
    })
}

function loadMorePosts() {
  offsetPost.value += limitPost
  loadPosts()
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
    />
    <div class="loading" v-if="loading">
      <spinner color="primary" />
    </div>
  </main>
  <navbar @tab-change="changeView($event)" :selected-tab="1"></navbar>
</template>

<style scoped lang="scss">
main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding);

  .loading {
    display: flex;
    justify-content: center;
    padding: var(--spacing-16);
  }
}
</style>
