<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import navbar from '@/components/footer/navbar.vue'
import router from '@/router'
import InputChip from '@/components/input/input-chip.vue'
import { onMounted, ref } from 'vue'
import type { searchResultInterface } from '@/utils/types.ts'
import apiService from '@/utils/api/api-service.ts'
import TextParagraph from '@/components/text/text-paragraph.vue'
import Spinner from '@/components/spinner.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import TextLabel from '@/components/text/text-label.vue'
import HorizontalOverflow from '@/components/container/horizontal-overflow.vue'
import type { ApiPostsResponse } from '@/utils/api/api-interface.ts'
import CardPost from '@/components/card/card-post.vue'
import usefulFunctions from '@/utils/useful-functions.ts'

const chipUsersEnabled = ref<boolean>(true)
const chipEmotionsEnabled = ref<boolean>(true)
const searchValue = ref<string>('')

const searchResults = ref<searchResultInterface[] | null>(null)
const isSearching = ref<boolean>(false)
const searchOffset = ref<number>(0)
const searchLimit = ref<number>(10)

const posts = ref<ApiPostsResponse | null>(null)

// loading flag for latest posts
const isLoadingLatestPosts = ref<boolean>(false)
const hasMoreLatestPosts = ref<boolean>(true)

onMounted(() => {
  searchResults.value = null
  // load the latest posts to show in the default (no search) view
  loadLatestPosts()
})

function goToHome() {
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

function onToggleEmotionChip(enabled: boolean) {
  chipEmotionsEnabled.value = enabled
  onSearch(searchValue.value)
}
function onToggleUserChip(enabled: boolean) {
  chipUsersEnabled.value = enabled
  onSearch(searchValue.value)
}

function onSearch(value: string) {
  searchValue.value = value
  // console.log(searchValue.value)
  loadSearchResults(searchOffset.value, searchLimit.value)
}

function loadSearchResults(offset: number, limit: number) {
  if (searchValue.value.trim().length >= 3) {
    isSearching.value = true
    apiService
      .searchEmotionsAndUsers(
        searchValue.value,
        chipUsersEnabled.value,
        chipEmotionsEnabled.value,
        'it',
        offset,
        limit,
      )
      .then((results) => {
        // console.log(results)
        if (results && results.data) {
          searchResults.value = results.data
        } else {
          searchResults.value = []
        }
        isSearching.value = false
      })
  } else {
    searchResults.value = null
  }
}

function toggleUserFollow(username: string, follow: boolean) {
  apiService.toggleUserFollow(username, follow ? 'unfollow' : 'follow').then((response) => {
    // console.log(response)
    if (response && response.status === 204) {
      // Aggiorna lo stato di follow dell'utente nei risultati di ricerca
      searchResults.value =
        searchResults.value?.map((result) => {
          if (result.type === 'user' && result.text === username) {
            return { ...result, followed: !follow }
          }
          return result
        }) || null
    }
  })
}

function toggleEmotionFollow(emotionId: number, follow: boolean) {
  apiService.toggleEmotionFollow(emotionId, follow ? 'unfollow' : 'follow').then((response) => {
    // console.log(response)
    if (response && response.status === 204) {
      // Aggiorna lo stato di follow dell'emozione nei risultati di ricerca
      searchResults.value =
        searchResults.value?.map((result) => {
          if (result.type === 'emotion' && result.id === emotionId) {
            return { ...result, followed: !follow }
          }
          return result
        }) || null
    }
  })
}

function openProfile(username: string) {
  router.push('/profile/' + username)
}

function goToEmotion(emotionId: number) {
  router.push('/emotion/' + emotionId)
}

async function loadLatestPostsInternal(offset = searchOffset.value, limit = searchLimit.value) {
  // Avoid concurrent loads and stop if no more
  if (isLoadingLatestPosts.value || !hasMoreLatestPosts.value) return
  if (!usefulFunctions.isInternetConnected()) return
  isLoadingLatestPosts.value = true

  try {
    const response = await apiService.getLatestPosts(offset, limit)
    if (response && response.status === 200 && Array.isArray((response as ApiPostsResponse).data)) {
      const res = response as ApiPostsResponse
      if (offset === 0) {
        posts.value = res
      } else if (posts.value) {
        posts.value.data = [...posts.value.data, ...res.data]
      }
    } else {
      // on error, clear posts
      posts.value = null
    }
  } catch (err: unknown) {
    // swallow and keep posts as-is (or null if none)
    console.error('Errore caricamento latest posts', err)
  } finally {
    isLoadingLatestPosts.value = false
  }
}

function loadLatestPosts() {
  // helper wrapper so the template can call loadLatestPosts without TS complaining about overload
  return loadLatestPostsInternal(searchOffset.value, searchLimit.value)
}
</script>

<template>
  <topbar
    variant="search"
    :show-back-button="true"
    @oninputsearch="onSearch($event)"
    @onback="goToHome"
    title="Digita qualcosa da ricercare…"
  ></topbar>
  <main>
    <div class="chips" v-if="!isSearching && searchResults !== null">
      <horizontal-overflow>
        <div class="all-chips">
          <input-chip
            text="Utenti"
            @toggle="onToggleUserChip"
            :enabled-by-default="chipUsersEnabled"
          />
          <input-chip
            text="Emozioni"
            @toggle="onToggleEmotionChip"
            :enabled-by-default="chipEmotionsEnabled"
          />
        </div>
      </horizontal-overflow>
    </div>
    <div class="no-contents" v-if="!isSearching && searchResults && searchResults.length === 0">
      <text-paragraph>
        Non ci sono risultati da mostrare. Prova a modificare i filtri di ricerca o a cercare
        qualcos'altro.
      </text-paragraph>
    </div>
    <div class="no-contents" v-if="!isSearching && searchResults === null">
      <div class="posts-container" v-if="posts?.data && posts?.data.length > 0">
        <!--        <separator />-->
        <div class="font-subtitle">Gli ultimi post pubblicati dagli utenti</div>
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
          :reactions-props="post['reactions']"
          :show-always-avatar="true"
        />
      </div>
      <div class="no-posts" v-else>
        <text-paragraph>
          Non ci sono post da mostrare. Prova a cercare qualcosa per vedere i risultati qui.
        </text-paragraph>
      </div>
    </div>
    <div class="loading-contents" v-if="isSearching">
      <spinner color="primary" />
    </div>
    <div class="results" v-if="!isSearching && searchResults && searchResults.length > 0">
      <div class="item" v-for="result in searchResults" :key="result.text">
        <div class="card-user" v-if="result.type === 'user'">
          <img
            :src="`https://gravatar.com/avatar/${result.avatar}?url`"
            :alt="'Avatar di ' + result.text"
            class="avatar clickable"
            @click="openProfile(result.text)"
          />
          <div class="username clickable" @click="openProfile(result.text)">@{{ result.text }}</div>
          <div class="buttons">
            <text-label text="Utente" color="primary"></text-label>
            <button-generic
              variant="primary"
              :text="result.followed ? 'Smetti di seguire' : 'Segui'"
              :small="true"
              icon-position="end"
              :icon="result.followed ? 'remove-circle' : 'plus-circle'"
              @action="toggleUserFollow(result.text, result.followed ?? false)"
            ></button-generic>
          </div>
        </div>
        <div class="card-emotion" v-else-if="result.type === 'emotion' && result.id">
          <div class="emotion-name clickable" @click="goToEmotion(result.id)">
            {{ result.text }}
          </div>
          <div class="buttons">
            <text-label text="Emozione" color="primary"></text-label>
            <button-generic
              variant="primary"
              :text="result.followed ? 'Smetti di seguire' : 'Segui'"
              :small="true"
              icon-position="end"
              :icon="result.followed ? 'remove-circle' : 'plus-circle'"
              @action="toggleEmotionFollow(result.id, result.followed ?? false)"
            ></button-generic>
          </div>
        </div>
      </div>
    </div>
  </main>
  <navbar @tab-change="changeView($event)"></navbar>
</template>

<style scoped lang="scss">
main {
  padding: var(--padding);
  display: flex;
  gap: var(--spacing-16);
  flex-direction: column;

  .chips {
    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);
    width: auto;

    .all-chips {
      display: flex;
      flex-direction: row;
      gap: var(--spacing-8);
    }
  }

  .results {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-16);
    padding: var(--no-padding);

    .card-user,
    .card-emotion {
      background-color: var(--color-blue-10);
      border-radius: var(--border-radius);
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

      .username,
      .emotion-name {
        font: var(--font-subtitle);
        color: var(--primary);
        flex-grow: 1;
        padding: var(--padding-4);
        word-break: break-all;
      }
      .emotion-name {
        text-transform: capitalize;
      }
      .buttons {
        display: flex;
        flex-direction: row;
        gap: var(--spacing-8);
        align-items: center;
        justify-content: center;
      }
    }
  }

  .loading-contents {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: var(--spacing-16);
    min-height: 200px;
  }
}

.posts-container {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--no-padding);
  position: relative;
}

.font-subtitle {
  font: var(--font-subtitle);
  color: var(--primary);
}
</style>
