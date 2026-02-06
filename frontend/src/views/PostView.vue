<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiService from '@/utils/api/api-service.ts'
import type {
  ApiErrorResponse,
  ApiPostDetailedData,
  ApiPostsResponse,
  ApiReactionsPostType,
} from '@/utils/api/api-interface.ts'
import CardPost from '@/components/card/card-post.vue'
import Spinner from '@/components/spinner.vue'
import Toast from '@/components/modal/toast.vue'
import Topbar from '@/components/header/topbar.vue'
import PullToRefresh from '@/components/container/pull-to-refresh.vue'

const route = useRoute()
const router = useRouter()
const postId = (route.params.postId as string) || ''

const loading = ref<boolean>(true)
const post = ref<ApiPostDetailedData | null>(null) // raw API object

const isScrolled = ref(false)
const isRefreshing = ref(false)
const refreshCounter = ref(0)

interface MappedPost {
  id: string
  datetime: string
  username: string
  profileImage: string
  emotion: string
  emotionId: number
  colorHex: string
  visibility: 'public' | 'private'
  isUserFollowed: boolean
  isEmotionFollowed: boolean
  isOwnPost: boolean | null
  contentText: string | null
  contentWeather: string | null
  contentLocation: string | null
  contentPlace: string | null
  contentTogetherWith: string | null
  contentBodyPart: string | null
  contentImage: { 'image-id': string; 'image-url': string; 'image-source': string } | null
  reactions: ApiReactionsPostType[]
}

const mappedPost = ref<MappedPost | null>(null) // normalized object used by the template / card-post
const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const invalidPostId = ref<boolean>(false)

function normalizePost(raw: ApiPostDetailedData | null): MappedPost | null {
  if (!raw) return null
  return {
    id: raw['post-id'],
    datetime: raw.created,
    username: raw.username,
    profileImage: raw['profile-image'],
    // Ensure emotion is always a string (fixes prop type mismatch)
    emotion: String(raw['emotion-text']),
    emotionId: raw['emotion-id'],
    colorHex: String(raw['color-hex']),
    visibility: raw.visibility === 0 ? 'public' : 'private',
    isUserFollowed: Boolean(raw['is-user-followed']),
    isEmotionFollowed: Boolean(raw['is-emotion-followed']),
    isOwnPost: raw['is-own-post'] ?? null,
    contentText: raw.text ?? null,
    contentWeather: raw['weather-text'] ?? null,
    contentLocation: raw.location ?? null,
    contentPlace: raw['place-text'] ?? null,
    contentTogetherWith: raw['together-with-text'] ?? null,
    contentBodyPart: raw['body-part-text'] ?? null,
    contentImage: raw.image ?? null,
    reactions: raw.reactions ?? [],
  }
}

async function loadPost() {
  loading.value = true
  try {
    const response = await apiService.getPostById(postId)
    if (
      (response as ApiPostsResponse).status === 200 &&
      'data' in (response as ApiPostsResponse) &&
      (response as ApiPostsResponse).data
    ) {
      const res = response as ApiPostsResponse
      // Expecting API to return an object with data array; take first post
      console.log('API Response:', res)
      const raw =
        Array.isArray(res.data) && res.data.length > 0 ? (res.data[0] as ApiPostDetailedData) : null
      post.value = raw
      if (raw) {
        mappedPost.value = normalizePost(raw)
      } else {
        errorMessageToastText.value = `${res.status ?? ''} | Errore — Impossibile trovare il post richiesto. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    } else if ((response as ApiPostsResponse) && response.status === 201) {
      // Post id could be invalid or private, so not available
      invalidPostId.value = true
    } else {
      const err = response as ApiErrorResponse
      errorMessageToastText.value = `${err.status ?? ''} | Errore — Impossibile caricare il post. ${err.message ?? 'Riprova più tardi.'}`
      errorMessageToastRef.value = true
    }
  } catch (e) {
    console.error('API Error:', e)
    const err = e as { status?: number; message?: string }
    errorMessageToastText.value = `${err.status ?? ''} | Errore — Impossibile contattare il server. ${err.message ?? 'Controlla la connessione.'}`
    errorMessageToastRef.value = true
  } finally {
    loading.value = false
    isRefreshing.value = false
  }
}

onMounted(() => {
  if (!postId || postId === '') {
    // invalid id, go back
    router.back()
    return
  }
  loadPost()
})

function goBack() {
  router.back()
}

async function refreshContents() {
  isRefreshing.value = true
  // Wait for the post to be reloaded before bumping the trigger so children use updated props
  await loadPost()
  refreshCounter.value++
  isRefreshing.value = false
}
</script>

<template>
  <topbar variant="standard" :show-back-button="true" @onback="goBack" />
  <pull-to-refresh
    class="flex-1"
    :is-refreshing="isRefreshing"
    @refresh="refreshContents"
    @scrolled="isScrolled = $event"
  >
    <main>
      <div class="content">
        <div v-if="loading" class="loading">
          <spinner color="primary" />
        </div>

        <div v-else>
          <div v-if="mappedPost">
            <card-post
              :id="mappedPost.id"
              :datetime="mappedPost.datetime"
              :username="mappedPost.username"
              :profile-image="mappedPost.profileImage"
              :emotion="mappedPost.emotion"
              :emotion-id="mappedPost.emotionId"
              :color-hex="mappedPost.colorHex"
              :visibility="mappedPost.visibility"
              :is-user-followed="mappedPost.isUserFollowed"
              :is-emotion-followed="mappedPost.isEmotionFollowed"
              :is-own-post="mappedPost.isOwnPost"
              :content-text="mappedPost.contentText"
              :content-weather="mappedPost.contentWeather"
              :content-location="mappedPost.contentLocation"
              :content-place="mappedPost.contentPlace"
              :content-together-with="mappedPost.contentTogetherWith"
              :content-body-part="mappedPost.contentBodyPart"
              :content-image="mappedPost.contentImage"
              :reactions-props="mappedPost.reactions"
              :expanded-by-default="true"
              :show-always-avatar="true"
            />
          </div>
          <div v-else>
            <div class="no-post" v-if="!invalidPostId">
              <text-paragraph align="center">Post non trovato.</text-paragraph>
            </div>
            <div class="no-post" v-else>
              <text-paragraph align="center">
                Il post che stai cercando non è disponibile. Potrebbe essere un id non valido o il
                post è privato.
              </text-paragraph>
            </div>
          </div>
        </div>
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
.content {
  padding: var(--padding-32);
}
.loading {
  display: flex;
  justify-content: center;
  padding: var(--padding-32);
}
.no-post {
  text-align: center;
  padding: var(--padding-24);
}
</style>
