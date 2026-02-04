<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import { nextTick, onMounted, ref, watch } from 'vue'
import ButtonReaction from '@/components/button/button-reaction.vue'
import TextLabel from '@/components/text/text-label.vue'
import apiService from '@/utils/api/api-service.ts'
import type { ApiReactionsPostResponse, ApiReactionsPostType } from '@/utils/api/api-interface.ts'
import ActionSheet from '@/components/modal/action-sheet.vue'
import Toast from '@/components/modal/toast.vue'
import HorizontalOverflow from '@/components/container/horizontal-overflow.vue'
import usefulFunctions from '@/utils/useful-functions.ts'
import router from '@/router'

const expanded = ref<boolean>(false)

const reactions = ref<ApiReactionsPostType[] | undefined>(undefined)
const onlyReactionsWithCount = ref<ApiReactionsPostType[] | undefined>(undefined)

const actionSheetAllReactionsRef = ref<boolean>(false)
const showCreditsImageToastRef = ref<boolean>(false)
const notAvailableToastRef = ref<boolean>(false)

const overflowRef = ref<InstanceType<typeof HorizontalOverflow>>()

const props = defineProps<{
  id: string
  datetime: string
  username: string
  profileImage: string
  emotion: string
  colorHex: string
  visibility: 'public' | 'private'
  isUserFollowed: boolean
  isEmotionFollowed: boolean
  isOwnPost: boolean
  contentText: string | null
  contentPlace: string | null
  contentLocation: string | null
  contentWeather: string | null
  contentTogetherWith: string | null
  contentBodyPart: string | null
  contentImage: { 'image-id': string; 'image-url': string; 'image-source': string } | null
  expandedByDefault: boolean
  showAlwaysAvatar?: boolean
  refreshTrigger: number
}>()

const emit = defineEmits<{
  (e: 'onexpanded', value: boolean): void
}>()

onMounted(() => {
  if (props.expandedByDefault) {
    expanded.value = true
  }

  loadReactions()
  //print all props to console
  //console.log('CardPost props:', props)
})

watch(
  () => props.refreshTrigger,
  () => {
    loadReactions()
  },
)

function toggleExpanded() {
  expanded.value = !expanded.value
  emit('onexpanded', expanded.value)
}

function onOpenMenu() {
  // Open post menu
  //todo (open action sheet with options)
  notAvailableToastRef.value = true
}

function openCreditInfo() {
  // Open credit info
  showCreditsImageToastRef.value = true
}

function openUsernameProfile() {
  // Open user profile
  router.push('/profile/' + props.username)
}

function openEmotionPage() {
  // Open emotion page
  //todo (navigate to emotion page)
  notAvailableToastRef.value = true
}

function openAllReactions() {
  // Open all reactions
  actionSheetAllReactionsRef.value = true
}

function closeAllReactions() {
  // Close all reactions
  actionSheetAllReactionsRef.value = false
}

function toggleReaction(reactionId: number, isActive: boolean) {
  // Toggle reaction
  apiService
    .togglePostReaction(props.id, reactionId, isActive ? 'remove' : 'add')
    .then((response) => {
      // remove or add reaction in reactions list
      if (response.status === 204) {
        //success
        if (isActive) {
          //remove reaction
          reactions.value = reactions.value?.map((reaction) => {
            if (reaction['reaction-id'] === reactionId) {
              return {
                ...reaction,
                'is-inserted': false,
                count: reaction.count !== null && reaction.count > 0 ? reaction.count - 1 : null,
              }
            }
            return reaction
          })
          //console.log('Removed reaction:', reactionId)
        } else {
          //add reaction
          reactions.value = reactions.value?.map((reaction) => {
            if (reaction['reaction-id'] === reactionId) {
              return {
                ...reaction,
                'is-inserted': true,
                count: reaction.count !== null ? reaction.count + 1 : null,
              }
            }
            return reaction
          })
          //console.log('Added reaction:', reactionId)
        }
      }
      nextTick(() => overflowRef.value?.updateOverflow())
    })
}

function loadReactions() {
  // Load reactions for the post
  apiService.getReactions(props.id).then((response) => {
    const res = response as ApiReactionsPostResponse
    reactions.value = res.data

    onlyReactionsWithCount.value = reactions.value.filter(
      (r) => r['is-inserted'] === true || (r['count'] !== null && r['count'] > 0),
    )
    //sort onlyReactionsWithCount by count descending
    onlyReactionsWithCount.value.sort((a, b) => {
      const countA = a.count ?? 0
      const countB = b.count ?? 0
      return countB - countA
    })

    //console.log(res)

    nextTick(() => overflowRef.value?.updateOverflow())
  })
}
</script>

<template>
  <div class="card">
    <div class="header">
      <div class="header-own-post" v-if="props.isOwnPost && !props.showAlwaysAvatar">
        <text-label
          text="Pubblico"
          icon="public"
          v-if="props.visibility === 'public'"
          align="center"
        />
        <text-label text="Privato" icon="private" v-else align="center" />
        <div class="date">
          {{ usefulFunctions.getDatetimeToShow(props.datetime) }}
        </div>
      </div>
      <div
        class="avatar clickable"
        @click="openUsernameProfile"
        v-if="!props.isOwnPost || props.showAlwaysAvatar"
      >
        <img :src="`https://gravatar.com/avatar/${props.profileImage}?url`" />
      </div>
      <div class="username-date" v-if="!props.isOwnPost || props.showAlwaysAvatar">
        <div class="username clickable" @click="openUsernameProfile">@{{ props.username }}</div>
        <div class="date">
          {{ usefulFunctions.getDatetimeToShow(props.datetime) }}
        </div>
      </div>
      <div class="buttons">
        <div class="header-own-post" v-if="props.isOwnPost && props.showAlwaysAvatar">
          <text-label text="" icon="public" v-if="props.visibility === 'public'" align="center" />
          <text-label text="" icon="private" v-else align="center" />
        </div>
        <button-generic
          icon="menu-h"
          variant="primary"
          :disabled-hover-effect="true"
          :small="true"
          @action="onOpenMenu"
        ></button-generic>
      </div>
    </div>
    <div class="color-bar" :style="{ 'background-color': `#${props.colorHex}` }"></div>
    <div class="content-emotion">
      <span v-if="!props.isOwnPost">
        <span class="strong clickable" @click="openUsernameProfile">@{{ props.username }}</span>
        stava
      </span>
      <span v-else-if="props.isOwnPost"> Stavi </span>
      provando
      <span class="strong clickable" @click="openEmotionPage">{{ props.emotion }}</span>
    </div>
    <div class="content" v-if="props.contentText">
      {{ props.contentText }}
    </div>
    <button-generic
      :text="expanded ? 'Nascondi dettagli' : 'Mostra dettagli'"
      icon-position="end"
      :icon="expanded ? 'chevron-up' : 'chevron-down'"
      :no-border-radius="true"
      :small="true"
      @action="toggleExpanded"
      v-if="
        props.contentPlace ||
        props.contentLocation ||
        props.contentWeather ||
        props.contentTogetherWith ||
        props.contentBodyPart ||
        props.contentImage
      "
    />
    <div
      class="content-expanded"
      v-if="
        expanded &&
        (props.contentPlace ||
          props.contentLocation ||
          props.contentWeather ||
          props.contentTogetherWith ||
          props.contentBodyPart ||
          props.contentImage)
      "
    >
      <div class="variables">
        <text-label
          :text="props.contentWeather"
          icon="sun"
          color="blue70"
          background="white-o60"
          :icon-padding="true"
          align="start"
          v-if="props.contentWeather"
        />
        <text-label
          :text="props.contentLocation"
          icon="location"
          color="blue70"
          background="white-o60"
          :icon-padding="true"
          align="start"
          v-if="props.contentLocation"
        />
        <text-label
          :text="props.contentPlace"
          icon="place"
          color="blue70"
          background="white-o60"
          :icon-padding="true"
          align="start"
          v-if="props.contentPlace"
        />
        <text-label
          :text="props.contentBodyPart"
          icon="head"
          color="blue70"
          background="white-o60"
          :icon-padding="true"
          align="start"
          v-if="props.contentBodyPart"
        />
        <text-label
          :text="props.contentTogetherWith"
          icon="people"
          color="blue70"
          background="white-o60"
          :icon-padding="true"
          align="start"
          v-if="props.contentTogetherWith"
        />
      </div>
      <div class="image" v-if="props.contentImage && props.contentImage['image-url'] !== ''">
        <img :src="`${props.contentImage['image-url']}?url`" />
        <div class="button-credit">
          <button-generic
            icon="info"
            variant="primary"
            :small="true"
            :disabled-hover-effect="true"
            @action="openCreditInfo"
            v-if="props.contentImage['image-source'] !== ''"
          />
        </div>
      </div>
    </div>
    <div
      class="reactions"
      v-if="(onlyReactionsWithCount && onlyReactionsWithCount.length > 0) || !props.isOwnPost"
    >
      <div class="reaction-button" v-if="!props.isOwnPost">
        <button-generic
          variant="primary"
          icon="reactions"
          :small="true"
          :disabled-hover-effect="true"
          @action="openAllReactions"
        />
      </div>
      <horizontal-overflow ref="overflowRef">
        <div class="all-reactions">
          <span
            class="reaction"
            v-for="reaction in onlyReactionsWithCount"
            :key="reaction['reaction-id']"
          >
            <button-reaction
              v-if="
                (reaction['is-inserted'] !== null && reaction['is-inserted'] === true) ||
                (reaction['count'] !== null && reaction['count'] > 0)
              "
              :reaction="reaction['reaction-icon-id']"
              :readonly="reaction['count'] !== null && reaction['count'] > 0"
              :count="reaction['count'] !== null ? reaction['count'] : 0"
              @ontoggle="toggleReaction(reaction['reaction-id'], reaction['is-inserted'] ?? false)"
            />
            <span class="hidden-reaction" v-else></span>
          </span>
        </div>
      </horizontal-overflow>
    </div>
  </div>

  <action-sheet
    v-if="reactions && actionSheetAllReactionsRef"
    title="Aggiungi o rimuovi una reaction"
    :height="80"
    :hiddenByDefault="!actionSheetAllReactionsRef"
    @onclose="closeAllReactions"
    button1-text="Chiudi"
    button1-style="primary"
    button1-icon="chevron-down"
    button2-text=""
  >
    <div class="list-all-reactions">
      <span class="reaction" v-for="reaction in reactions" :key="reaction['reaction-id']">
        <button-reaction
          :reaction="reaction['reaction-icon-id']"
          :readonly="reaction['count'] !== null"
          :count="reaction['count'] !== null ? reaction['count'] : 0"
          :variant="reaction['is-inserted'] ? 'primary' : 'blue10'"
          @ontoggle="toggleReaction(reaction['reaction-id'], reaction['is-inserted'] ?? false)"
          size="24px"
        />
      </span>
    </div>
  </action-sheet>

  <toast
    v-if="showCreditsImageToastRef && props.contentImage?.['image-source'] !== ''"
    variant="standard"
    :show-button="false"
    :life-seconds="0"
    position="bottom"
    @onclose="
      () => {
        showCreditsImageToastRef = false
      }
    "
  >
    {{ props.contentImage?.['image-source'] }}
  </toast>

  <toast
    v-if="notAvailableToastRef"
    :life-seconds="5"
    @onclose="
      () => {
        notAvailableToastRef = false
      }
    "
  >
    Funzionalità ancora non disponibile
  </toast>
</template>

<style scoped lang="scss">
.card {
  background-color: var(--color-blue-10);
  border-radius: var(--border-radius);
  overflow: hidden;
  width: 100%;

  display: flex;
  flex-direction: column;

  .header {
    padding: var(--padding-8);
    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);

    .header-own-post {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: start;
      padding: var(--no-padding);
      color: var(--primary);
      gap: var(--spacing-8);

      .date {
        font: var(--font-small);
      }
    }
    .avatar {
      width: 50px;
      height: 50px;
      border-radius: var(--border-radius);
      overflow: hidden;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }
    .username-date {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: var(--spacing-8);
      color: var(--primary);
      height: auto;

      .username {
        font: var(--font-subtitle);
      }
      .date {
        font: var(--font-small);
      }
    }

    .buttons {
      display: flex;
      align-items: start;
      justify-content: center;
      gap: var(--spacing-4);
    }
  }
  .color-bar {
    height: 10px;
  }
  .content-emotion {
    padding: var(--padding-16);
    font: var(--font-paragraph);
  }
  .content {
    padding: var(--padding-16);
    font: var(--font-paragraph);
  }
  .content-expanded {
    background-color: var(--color-blue-20);

    display: flex;
    flex-direction: column;

    .variables {
      display: grid;
      padding: var(--padding-16);
      grid-gap: var(--spacing-16);
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
    .image {
      position: relative;

      img {
        width: 100%;
        height: 180px;
        display: block;
        object-fit: cover;
      }

      .button-credit {
        position: absolute;
        bottom: var(--spacing-8);
        right: var(--spacing-8);
      }
    }
  }
  .reactions {
    padding: var(--padding);
    display: flex;
    flex-direction: row;
    gap: var(--spacing-16);

    .reaction-button {
    }

    .all-reactions {
      display: flex;
      flex-direction: row;
      gap: var(--spacing-4);

      min-width: 100%;
      width: auto;
      height: auto;
      position: relative;

      .reaction {
        height: 100%;
      }
      .reaction:has(.hidden-reaction) {
        display: none;
      }
    }
  }
}
.action-sheet {
  .list-all-reactions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(36px, 1fr));
    grid-gap: var(--spacing-16);

    .reaction {
      width: auto;
      position: relative;
    }
  }
}
</style>
