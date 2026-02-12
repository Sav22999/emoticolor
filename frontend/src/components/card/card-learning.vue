<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import { onMounted, ref, watch } from 'vue'
import type { learningContentInterface } from '@/utils/api/api-interface.ts'
import Toast from '@/components/modal/toast.vue'
import router from '@/router'
import TextParagraph from '@/components/text/text-paragraph.vue'
import TextInfo from '@/components/text/text-info.vue'

const expanded = ref<boolean>(false)

const learningContent = ref<learningContentInterface | undefined>(undefined)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

const showCreditsImageToastRef = ref<boolean>(false)

const props = defineProps<{
  content: learningContentInterface
  trigger: number
}>()

const emit = defineEmits<{
  (e: 'onexpanded', value: boolean): void
}>()

onMounted(() => {
  loadContent()
})

watch(
  () => props.trigger,
  () => {
    loadContent()
  },
)

function toggleExpanded() {
  expanded.value = !expanded.value
  emit('onexpanded', expanded.value)
}

function goToEmotion(emotionId: number) {
  router.push('/learning/emotion/' + emotionId)
}

function openSource(url: string) {
  window.open(url, '_blank')
}

function loadContent() {
  if (props.content) {
    learningContent.value = props.content
  }
}

function openCreditInfo() {
  // Open credit info
  showCreditsImageToastRef.value = true
}
</script>

<template>
  <div class="card" v-if="learningContent">
    <div class="content" v-if="learningContent.title">
      {{ learningContent.title }}
    </div>
    <button-generic
      :text="expanded ? 'Nascondi dettagli' : 'Mostra dettagli'"
      icon-position="end"
      :icon="expanded ? 'chevron-up' : 'chevron-down'"
      :no-border-radius="true"
      :small="false"
      @action="toggleExpanded"
      v-if="learningContent.text"
    />
    <div class="content-expanded" v-if="expanded && learningContent.text">
      <div class="text" v-if="learningContent.text">
        <text-paragraph :content="learningContent.text" align="justify" color="black">
          {{ learningContent.text }}
        </text-paragraph>
      </div>
      <div class="image" v-if="learningContent.image">
        <img :src="learningContent.image['image-url']" alt="Learning content image" />
        <button-generic
          :small="true"
          :disabled-hover-effect="true"
          text=""
          icon="info"
          @click="openCreditInfo()"
          class="button-credit"
        />
      </div>
      <div class="source" v-if="learningContent.sources && learningContent.sources.length > 0">
        <text-info
          icon="external"
          align="start"
          :show-icon="source['source-link'] !== ''"
          v-for="source in learningContent.sources"
          :key="source['source-id']"
          @click="source['source-link'] !== '' ? openSource(source['source-link']) : null"
        >
          Fonte "{{ source['source-text'] }}"
        </text-info>
      </div>
    </div>
  </div>

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
    v-if="showCreditsImageToastRef && learningContent?.image['image-source'] !== ''"
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
    {{ learningContent?.image['image-source'] }}
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

  .content {
    padding: var(--padding);
    font: var(--font-subtitle);
    color: var(--primary);
  }

  .content-expanded {
    > .text {
      padding: var(--padding);
    }

    > .image {
      position: relative;

      img {
        width: 100%;
        height: 220px;
        display: block;
        object-fit: cover;
      }

      .button-credit {
        position: absolute;
        bottom: var(--spacing-8);
        right: var(--spacing-8);
      }
    }

    > .source {
      padding: var(--padding);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: var(--spacing-4);
    }
  }
}
</style>
