<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
import apiService from '@/utils/api/api-service.ts'
import ButtonGeneric from '@/components/button/button-generic.vue'
import Spinner from '@/components/spinner.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'
import InfiniteScroll from '@/components/container/infinite-scroll.vue'
import CardPost from '@/components/card/card-post.vue'
import type {
  ApiEmotionResponse,
  ApiPostsResponse,
  emotionObjectInterface,
} from '@/utils/api/api-interface.ts'
import usefulFunctions from '@/utils/useful-functions.ts'
import Toast from '@/components/modal/toast.vue'

const emotion = ref<number | null>(null)
const emotionDetails = ref<emotionObjectInterface | null>(null)
const isLoadingUserDetails = ref<boolean>(false)
const isLoading = ref<boolean>(false)
const offsetPosts = ref<number>(0)
const limitPosts = 30

const hasMore = ref(true)
const isRefreshing = ref(false)

const posts = ref<ApiPostsResponse | null>(null)

const isScrolled = ref(false)
const refreshCounter = ref(0)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

onMounted(() => {
  // verify the route params to see if an emotion is provided
  const routeParam = router.currentRoute.value.params.emotion as string | number | undefined
  let parsedEmotion: number | null = null

  if (typeof routeParam === 'number') {
    parsedEmotion = routeParam
  } else if (typeof routeParam === 'string' && routeParam.trim() !== '') {
    const n = Number(routeParam)
    if (!Number.isNaN(n)) parsedEmotion = n
  }

  if (parsedEmotion !== null) {
    emotion.value = parsedEmotion
    loadEmotionDetails()
  } else {
    emotion.value = null
  }

  loadPosts()
})

function setErrorFromResponse(
  responseOrError: unknown,
  defaultMessage = 'Si è verificato un errore. Riprova più tardi.',
) {
  // Try to extract a status or message from common error shapes without using `any`
  let status: unknown = undefined
  let message: unknown = undefined
  if (responseOrError && typeof responseOrError === 'object') {
    const obj = responseOrError as Record<string, unknown>
    status = obj.status ?? (obj.response && (obj.response as Record<string, unknown>).status)
    message = obj.message ?? (obj.response && (obj.response as Record<string, unknown>).data)
  }
  const finalMessage = typeof message === 'string' ? message : defaultMessage
  errorMessageToastText.value =
    typeof status === 'number' ? `${status} | ${finalMessage}` : `${finalMessage}`
  errorMessageToastRef.value = true
}

function loadEmotionDetails() {
  if (!emotion.value) return
  isLoadingUserDetails.value = true
  apiService
    .getEmotions(emotion.value)
    .then((response) => {
      if (response && response.status < 400) {
        const res = response as ApiEmotionResponse
        if (Array.isArray(res.data)) {
          emotionDetails.value = res.data[0] ?? null
          emotion.value = emotionDetails.value ? emotionDetails.value['emotion-id'] : null
          return
        }
      }
      setErrorFromResponse(response, "Errore nel caricamento dell'emozione.")
    })
    .catch((error: unknown) => {
      setErrorFromResponse(error, "Errore nel caricamento dell'emozione.")
    })
    .finally(() => {
      isLoadingUserDetails.value = false
    })
}

function loadPosts(onFinished?: () => void) {
  if (usefulFunctions.isInternetConnected()) {
    if (isLoading.value) {
      if (onFinished) onFinished()
      return
    }
    isLoading.value = true
    apiService
      .getPostsByEmotion(emotion.value ?? 1, offsetPosts.value, limitPosts)
      .then((response) => {
        if (
          response &&
          response.status < 400 &&
          Array.isArray((response as ApiPostsResponse).data)
        ) {
          const res = response as ApiPostsResponse
          if (offsetPosts.value === 0) {
            // Assign the whole ApiPostsResponse so posts.value has status/message and data array
            posts.value = res
          } else if (posts.value) {
            posts.value.data = [...posts.value.data, ...res.data]
          }
          hasMore.value = res.data.length === limitPosts
        } else {
          setErrorFromResponse(response, 'Errore nel caricamento dei post.')
        }
      })
      .catch((error: unknown) => {
        setErrorFromResponse(error, 'Errore nel caricamento dei post.')
      })
      .finally(() => {
        isLoading.value = false
        if (onFinished) onFinished()
      })
  } else {
    if (onFinished) onFinished()
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
  // Wait for posts to be updated before triggering children to refresh
  loadPosts(() => {
    refreshCounter.value++
    isRefreshing.value = false
  })
}

function toggleEmotionFollow(emotion: number, follow: boolean) {
  apiService
    .toggleEmotionFollow(emotion, follow ? 'unfollow' : 'follow')
    .then((response) => {
      if (response && response.status === 204) {
        if (emotionDetails.value) emotionDetails.value['is-followed'] = !follow
      } else {
        setErrorFromResponse(response, "Errore durante l'operazione di follow/unfollow.")
      }
    })
    .catch((error: unknown) => {
      setErrorFromResponse(error, "Errore durante l'operazione di follow/unfollow.")
    })
}

function goBack() {
  router.back()
}

function capitalizeFirstLetter(text: string): string {
  if (!text) return text
  return text.charAt(0).toUpperCase() + text.slice(1)
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar variant="standard" :show-back-button="true" @onback="goBack()"></topbar>
  <div class="header-emotion" v-if="emotionDetails">
    <div class="card-emotion-header">
      <div class="emotion-name">{{ capitalizeFirstLetter(emotionDetails['emotion-text']) }}</div>
      <div class="buttons">
        <button-generic
          variant="primary"
          :text="emotionDetails['is-followed'] ? 'Smetti di seguire' : 'Segui'"
          :small="true"
          icon-position="end"
          :icon="emotionDetails['is-followed'] ? 'remove-circle' : 'plus-circle'"
          @action="
            toggleEmotionFollow(
              emotionDetails['emotion-id'],
              emotionDetails['is-followed'] ?? false,
            )
          "
        ></button-generic>
      </div>
    </div>
    <div class="learning">
      <button-generic
        variant="primary"
        text="Vai all'apprendimento dell'emozione"
        :small="false"
        icon-position="end"
        icon="learning"
        :full-width="true"
        @action="router.push('/learning/emotion/' + emotionDetails['emotion-id'])"
      ></button-generic>
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
            :show-always-avatar="true"
          />
          <div class="no-contents" v-if="!isLoading && (!posts || posts.data.length === 0)">
            <text-paragraph> Non ci sono stati emotivi per questa emozione. </text-paragraph>
          </div>
          <div class="loading" v-if="isLoading || isLoadingUserDetails">
            <spinner color="primary" />
          </div>
        </div>
      </main>
    </infinite-scroll>
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
.header-emotion {
  background-color: var(--color-blue-10);
  border-radius: var(--no-border-radius);
  border-bottom: 5px solid var(--primary);
  display: flex;
  flex-direction: column;
  padding: var(--padding);
  gap: var(--spacing);

  .card-emotion-header {
    border-radius: var(--no-border-radius);
    padding: var(--no-padding);
    display: flex;
    flex-direction: row;
    gap: var(--spacing-4);
    align-items: center;
    justify-content: center;

    .emotion-name {
      font: var(--font-subtitle);
      color: var(--primary);
      flex-grow: 1;
      padding: var(--no-padding);
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
      padding: var(--no-padding);

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
  .learning {
    padding: var(--no-padding);
  }
}

.loading-contents,
.loading {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: var(--padding);
  min-height: 200px;
}

.posts-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding);
  position: relative;
}
</style>
