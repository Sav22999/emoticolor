<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { ref } from 'vue'
import ActionSheet from '@/components/modal/action-sheet.vue'
import ButtonSelect from '@/components/button/button-select.vue'
import Separator from '@/components/separator.vue'
import InputMultiline from '@/components/input/input-multiline.vue'
import HorizontalOverflow from '@/components/container/horizontal-overflow.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import type {
  bodyPartInterface,
  colorInterface,
  emotionInterface,
  imageInterface,
  locationInterface,
  placeInterface,
  togetherWithInterface,
  visibilityInterface,
  weatherInterface
} from '@/utils/types.ts'

const confirmationGoBack = ref<boolean>(false)
const contentEdited = ref<boolean>(false)

const emotion = ref<emotionInterface>('')
const visibility = ref<visibilityInterface>('')
const color = ref<colorInterface>('')

const contentText = ref<string>('')
const contentImage = ref<imageInterface>('')
const contentPlace = ref<placeInterface>('')
const contentLocation = ref<locationInterface>('')
const contentWeather = ref<weatherInterface>('')
const contentTogetherWith = ref<togetherWithInterface>('')
const contentBodyPart = ref<bodyPartInterface>('')

const emotionActionSheetRef = ref<boolean>(false)
const visibilityActionSheetRef = ref<boolean>(false)
const colorActionSheetRef = ref<boolean>(false)
const imageActionSheetRef = ref<boolean>(false)
const placeActionSheetRef = ref<boolean>(false)
const locationActionSheetRef = ref<boolean>(false)
const weatherActionSheetRef = ref<boolean>(false)
const togetherWithActionSheetRef = ref<boolean>(false)
const bodyPartActionSheetRef = ref<boolean>(false)

function doAction(name: string) {
  console.log('Action:', name)
}

function goToHome() {
  // Navigate to home view
  router.push({ name: 'home' })
}

function goBackWithConfirmation() {
  if (contentEdited.value) {
    confirmationGoBack.value = true
  } else {
    goToHome()
  }
}

function checkContentEdited() {
  contentEdited.value =
    emotion.value !== '' ||
    visibility.value !== '' ||
    color.value !== '' ||
    contentText.value !== '' ||
    contentImage.value !== '' ||
    contentPlace.value !== '' ||
    contentLocation.value !== '' ||
    contentWeather.value !== '' ||
    contentTogetherWith.value !== '' ||
    contentBodyPart.value !== ''
}

function onSelectEmotion(value: emotionInterface) {
  emotion.value = value
  checkContentEdited()
}

function onSelectVisibility(value: visibilityInterface) {
  visibility.value = value
  checkContentEdited()
}

function onSelectColor(value: colorInterface) {
  color.value = value
  checkContentEdited()
}

function onInputContentText(value: string) {
  contentText.value = value
  checkContentEdited()
}

function onSelectContentImage(value: imageInterface) {
  contentImage.value = value
  checkContentEdited()
}

function onSelectContentPlace(value: placeInterface) {
  contentPlace.value = value
  checkContentEdited()
}

function onSelectContentLocation(value: locationInterface) {
  contentLocation.value = value
  checkContentEdited()
}

function onSelectContentWeather(value: weatherInterface) {
  contentWeather.value = value
  checkContentEdited()
}

function onSelectContentTogetherWith(value: togetherWithInterface) {
  contentTogetherWith.value = value
  checkContentEdited()
}

function onSelectContentBodyPart(value: bodyPartInterface) {
  contentBodyPart.value = value
  checkContentEdited()
}
</script>

<template>
  <!--RouterLink to="/home">Home</RouterLink>-->
  <topbar
    variant="standard"
    :show-back-button="true"
    @onback="goBackWithConfirmation"
    title="Nuovo stato emotivo"
  ></topbar>
  <main>
    <h1>Campi obbligatori</h1>
    <horizontal-overflow>
      <div class="row">
        <button-select
          icon="public"
          value=""
          variant="text"
          :selected="false"
          @onselect="
            () => {
              visibilityActionSheetRef = true
            }
          "
          placeholder="Visibilità"
        />
        <button-select
          icon=""
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Emozione"
        />
        <button-select
          icon=""
          value=""
          variant="color"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Colore"
        />
      </div>
    </horizontal-overflow>
    <separator variant="primary" />
    <h1>Campi facoltativi</h1>
    <input-multiline placeholder="Scrivi qualcosa…" @input="onInputContentText"></input-multiline>
    <horizontal-overflow>
      <div class="row">
        <button-select
          icon="image"
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Immagine"
        />
        <button-select
          icon="place"
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Posto"
        />
        <button-select
          icon="location"
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Luogo"
        />
        <button-select
          icon="sun"
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Meteo"
        />
        <button-select
          icon="people"
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Insieme a"
        />
        <button-select
          icon="head"
          value=""
          variant="text"
          :selected="false"
          @onselect="doAction($event)"
          placeholder="Parte del corpo"
        />
      </div>
    </horizontal-overflow>
    <separator variant="primary" />
    <button-generic
      variant="cta"
      icon="forward"
      text="Conferma creazione dello stato emotivo"
      @action="doAction('confirm')"
    />
    <!--    <generic icon="search" @input="doAction($event)"></generic>
    <password @input="doAction($event)"></password>-->
  </main>

  <action-sheet
    v-if="confirmationGoBack"
    :hidden-by-default="false"
    variant="warning"
    title="Sei sicuro di voler uscire?"
    button1-text="Annulla"
    button2-text="Esci"
    button2-icon="trash"
    button2-style="warning"
    @action-button2="goToHome"
    :height="50"
    @onclose="confirmationGoBack = false"
  >
    Il contenuto che stavi creando verrà perso se esci senza salvare.
  </action-sheet>
</template>

<style scoped lang="scss">
main {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-16);
  padding: var(--padding-16);

  h1 {
    font: var(--font-subtitle);
    color: var(--primary);
  }

  .row {
    position: relative;
    min-width: 100%;
    width: auto;
    display: grid;
    grid-auto-flow: column;
    gap: var(--spacing-8);
  }
}
</style>
