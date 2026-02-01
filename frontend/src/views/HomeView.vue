<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import type { ApiPostsResponse } from '@/utils/api/api-interface.ts'
import CardPost from '@/components/card/card-post.vue'

const offsetPost = ref(0)
const limitPost = 10

const posts = ref<ApiPostsResponse>()

onMounted(() => {
  loadPosts()
})

function doAction(name: string) {
  console.log('Action:', name)
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
  apiService.getHomePosts('it', offsetPost.value, limitPost.value).then((response) => {
    console.log('Loaded posts:', response.data)
    posts.value = response.data
  })
}

function loadMorePosts() {
  // Logic to load more posts when user scrolls down
  console.log('Loading more posts...')
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
      v-for="post in posts"
      :key="post['post-id']"
      :id="post['post-id']"
      :datetime="post['created']"
      :username="post['username']"
      :profile-image="post['profile-image']"
      :emotion="post['emotion-text']"
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
      :reactions="post['reactions']"
      :expanded-by-default="false"
    />
  </main>
  <navbar @tab-change="changeView($event)" :selected-tab="1"></navbar>
</template>

<style scoped lang="scss">
main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding);
}
</style>
