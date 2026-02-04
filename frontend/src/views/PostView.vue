<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiService from '@/utils/api/api-service.ts'
import type { ApiErrorResponse, ApiPostsResponse } from '@/utils/api/api-interface.ts'
import CardPost from '@/components/card/card-post.vue'
import Spinner from '@/components/spinner.vue'
import Toast from '@/components/modal/toast.vue'
import Topbar from '@/components/header/topbar.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'

const route = useRoute()
const router = useRouter()
const postId = (route.params.postId as string) || ''

const loading = ref<boolean>(true)
const post = ref<any | null>(null)
const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const invalidPostId = ref<boolean>(false)

function goBack() {
  router.back()
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
      post.value = Array.isArray(res.data) && res.data.length > 0 ? res.data[0] : null
      if (!post.value) {
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

function goToHome() {
  router.push({ name: 'home' })
}
</script>

<template>
  <topbar variant="standard" :show-back-button="true" @onback="goToHome" />
  <main>
    <div class="content">
      <div v-if="loading" class="loading">
        <spinner color="primary" />
      </div>

      <div v-else>
        <div v-if="post">
          <card-post
            :id="post['post-id']"
            :datetime="post.created"
            :username="post.username"
            :profile-image="post['profile-image']"
            :emotion="post['emotion-text']"
            :color-hex="post['color-hex']"
            :visibility="post.visibility === 0 ? 'public' : 'private'"
            :is-user-followed="post['is-user-followed']"
            :is-emotion-followed="post['is-emotion-followed']"
            :is-own-post="post['is-own-post']"
            :content-text="post.text"
            :content-weather="post['weather-text']"
            :content-location="post.location"
            :content-place="post['place-text']"
            :content-together-with="post['together-with-text']"
            :content-body-part="post['body-part-text']"
            :content-image="post.image"
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
