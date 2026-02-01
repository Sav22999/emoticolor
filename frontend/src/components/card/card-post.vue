<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import { nextTick, onMounted, ref } from 'vue'
import ButtonReaction from '@/components/button/button-reaction.vue'
import TextLabel from '@/components/text/text-label.vue'
import type { ReactionsOtherUser, ReactionsOwnPublic } from '@/utils/api/api-interface.ts'

const expanded = ref<boolean>(false)
const overflowStart = ref<boolean>(false)
const overflowEnd = ref<boolean>(false)
const listRef = ref<HTMLElement>()

const props = defineProps<{
  id: string
  datetime: string
  username: string
  profileImage: string
  emotion: string
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
  reactions?: (ReactionsOwnPublic | ReactionsOtherUser)[] | []
  expandedByDefault: boolean
}>()

const emit = defineEmits<{
  (e: 'onexpanded', value: boolean): void
}>()

onMounted(() => {
  if (props.expandedByDefault) {
    expanded.value = true
  }
  nextTick(() => updateOverflow())

  //print all props to console
  console.log('CardPost props:', props)
})

function toggleExpanded() {
  expanded.value = !expanded.value
  emit('onexpanded', expanded.value)
}

function updateOverflow() {
  const el = listRef.value
  if (el) {
    overflowStart.value = el.scrollLeft > 0
    overflowEnd.value = el.scrollLeft + el.clientWidth < el.scrollWidth
  }
}

function onOpenMenu() {
  // Open post menu
  //todo (open action sheet with options)
}

function openCreditInfo() {
  // Open credit info
  //todo (open toast with info)
}

function getDatetimeToShow(datetime: string) {
  // Return a formatted datetime string
  //todo (implement datetime formatting)
  return datetime
}

function openUsernameProfile() {
  // Open user profile
  //todo (navigate to user profile)
}

function openEmotionPage() {
  // Open emotion page
  //todo (navigate to emotion page)
}

function openAllReactions() {
  // Open all reactions
  //todo (open reactions modal)
}
</script>

<template>
  <div class="card">
    <div class="header">
      <div class="avatar">
        <img :src="`https://gravatar.com/avatar/${props.profileImage}?url`" />
      </div>
      <div class="username-date">
        <div class="username clickable" @click="openUsernameProfile">@{{ props.username }}</div>
        <div class="date">
          {{ getDatetimeToShow(props.datetime) }}
        </div>
      </div>
      <div class="button">
        <button-generic
          icon="menu-h"
          variant="primary"
          :disabled-hover-effect="true"
          :small="true"
          @action="onOpenMenu"
        ></button-generic>
      </div>
    </div>
    <div class="color-bar"></div>
    <div class="content-emotion">
      <span class="strong clickable" @click="openUsernameProfile">@{{ props.username }}</span> stava
      provando
      <span class="strong clickable" @click="openEmotionPage">tristezza</span>
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
    <div class="reactions">
      <div class="reaction-button">
        <button-generic
          variant="primary"
          icon="reactions"
          :small="true"
          :disabled-hover-effect="true"
          @action="openAllReactions"
        />
      </div>
      <div class="all-reactions">
        <div class="shadow-in-start" v-if="overflowStart"></div>
        <div class="shadow-in-end" v-if="overflowEnd"></div>
        <div class="list" ref="listRef" @scroll="updateOverflow">
          <button-reaction
            v-for="reaction in props.reactions"
            :key="reaction['reaction-id']"
            :reaction="reaction['reaction-icon-id']"
          />
        </div>
      </div>
    </div>
  </div>
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

    .button {
      display: flex;
      align-items: start;
      justify-content: center;
    }
  }
  .color-bar {
    height: 10px;
    background-color: var(--color-blue-50);
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
      flex: 1;

      display: flex;
      flex-direction: row;
      gap: var(--spacing-4);

      position: relative;
      width: 100%;

      .shadow-in-start {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 40px;
        background: linear-gradient(to right, var(--color-blue-10), var(--no-color));
        pointer-events: none;
        z-index: 2;
      }
      .shadow-in-end {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 40px;
        background: linear-gradient(to left, var(--color-blue-10), var(--no-color));
        pointer-events: none;
        z-index: 2;
      }

      .list {
        display: flex;
        flex-direction: row;
        gap: var(--spacing-4);

        width: auto;
        overflow-x: auto;

        scrollbar-width: none;
        &::-webkit-scrollbar {
          display: none;
        }

        position: absolute;
        left: 0;
        right: 0;

        z-index: 1;
      }
    }
  }
}
</style>
