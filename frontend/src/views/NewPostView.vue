<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import router from '@/router'
import { onMounted, ref } from 'vue'
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
  weatherInterface,
} from '@/utils/types.ts'
import InputGeneric from '@/components/input/input-generic.vue'
import apiService from '@/utils/api/api-service.ts'
import InputSearchbox from '@/components/input/input-searchbox.vue'
import Spinner from '@/components/spinner.vue'
import Toast from '@/components/modal/toast.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import InfiniteScroll from '@/components/container/infinite-scroll.vue'
import type { ApiCreatePostRequest } from '@/utils/api/api-interface.ts'

const confirmationGoBack = ref<boolean>(false)
const contentEdited = ref<boolean>(false)

const emotion = ref<emotionInterface | null>(null)
const visibility = ref<visibilityInterface | null>(null)
const color = ref<colorInterface | null>(null)

const contentText = ref<string | null>(null)
const contentImage = ref<imageInterface | null>(null)
const contentPlace = ref<placeInterface | null>(null)
const contentLocation = ref<locationInterface | null>(null)
const contentWeather = ref<weatherInterface | null>(null)
const contentTogetherWith = ref<togetherWithInterface | null>(null)
const contentBodyPart = ref<bodyPartInterface | null>(null)

const emotionActionSheetRef = ref<boolean>(false)
const visibilityActionSheetRef = ref<boolean>(false)
const colorActionSheetRef = ref<boolean>(false)
const imageActionSheetRef = ref<boolean>(false)
const placeActionSheetRef = ref<boolean>(false)
const locationActionSheetRef = ref<boolean>(false)
const weatherActionSheetRef = ref<boolean>(false)
const togetherWithActionSheetRef = ref<boolean>(false)
const bodyPartActionSheetRef = ref<boolean>(false)
const showCreditsImageToastRef = ref<boolean>(false)

const emotionsList: emotionInterface[] = []
const emotionsListFiltered = ref<emotionInterface[]>([])
const colorsList: colorInterface[][] = [
  [
    {
      id: 'pur10',
      text: 'E9E5FF',
    },
    {
      id: 'pur20',
      text: 'D2CCFF',
    },
    {
      id: 'pur30',
      text: 'BCB2FF',
    },
    {
      id: 'pur40',
      text: 'A599FF',
    },
    {
      id: 'pur50',
      text: '8F7FFF',
    },
    {
      id: 'pur60',
      text: '7266CC',
    },
    {
      id: 'pur70',
      text: '564C99',
    },
    {
      id: 'pur80',
      text: '393366',
    },
    {
      id: 'pur90',
      text: '1D1933',
    },
  ],
  [
    {
      id: 'yel10',
      text: 'FFFBCD',
    },
    {
      id: 'yel20',
      text: 'FFF79B',
    },
    {
      id: 'yel30',
      text: 'FFF269',
    },
    {
      id: 'yel40',
      text: 'FFEE37',
    },
    {
      id: 'yel50',
      text: 'FFEA05',
    },
    {
      id: 'yel60',
      text: 'CCBB04',
    },
    {
      id: 'yel70',
      text: '998C03',
    },
    {
      id: 'yel80',
      text: '665E02',
    },
    {
      id: 'yel90',
      text: '332F01',
    },
  ],

  [
    {
      id: 'red10',
      text: 'FCD1CC',
    },
    {
      id: 'red20',
      text: 'F9A499',
    },
    {
      id: 'red30',
      text: 'F67666',
    },
    {
      id: 'red40',
      text: 'F34933',
    },
    {
      id: 'red50',
      text: 'F01B00',
    },
    {
      id: 'red60',
      text: 'C01600',
    },
    {
      id: 'red70',
      text: '901000',
    },
    {
      id: 'red80',
      text: '600B00',
    },
    {
      id: 'red90',
      text: '300500',
    },
  ],
  [
    {
      id: 'blu10',
      text: 'D4EDFF',
    },
    {
      id: 'blu20',
      text: 'A8D8FF',
    },
    {
      id: 'blu30',
      text: '7DC4FF',
    },
    {
      id: 'blu40',
      text: '51B1FF',
    },
    {
      id: 'blu50',
      text: '269DFF',
    },
    {
      id: 'blu60',
      text: '1E7ECC',
    },
    {
      id: 'blu70',
      text: '175E99',
    },
    {
      id: 'blu80',
      text: '0F3F66',
    },
    {
      id: 'blu90',
      text: '081F33',
    },
  ],
  [
    {
      id: 'gry10',
      text: 'F1F1F1',
    },
    {
      id: 'gry20',
      text: 'EEEEEE',
    },
    {
      id: 'gry30',
      text: 'DFDFDF',
    },
    {
      id: 'gry40',
      text: 'CFCFCF',
    },
    {
      id: 'gry50',
      text: 'BEBEBE',
    },
    {
      id: 'gry60',
      text: '8F8F8F',
    },
    {
      id: 'gry70',
      text: '5C5C5C',
    },
    {
      id: 'gry80',
      text: '2E2E2E',
    },
    {
      id: 'gry90',
      text: '111111',
    },
  ],

  [
    {
      id: 'grn10',
      text: 'CDF1DE',
    },
    {
      id: 'grn20',
      text: '9AE4BE',
    },
    {
      id: 'grn30',
      text: '68D69D',
    },
    {
      id: 'grn40',
      text: '35C97D',
    },
    {
      id: 'grn50',
      text: '03BB5C',
    },
    {
      id: 'grn60',
      text: '02964A',
    },
    {
      id: 'grn70',
      text: '027037',
    },
    {
      id: 'grn80',
      text: '014B25',
    },
    {
      id: 'grn90',
      text: '012512',
    },
  ],

  [
    {
      id: 'brw10',
      text: 'EFE1CC',
    },
    {
      id: 'brw20',
      text: 'DFC399',
    },
    {
      id: 'brw30',
      text: 'D0A466',
    },
    {
      id: 'brw40',
      text: 'C08633',
    },
    {
      id: 'brw50',
      text: 'B06800',
    },
    {
      id: 'brw60',
      text: '8D5300',
    },
    {
      id: 'brw70',
      text: '6E3E00',
    },
    {
      id: 'brw80',
      text: '462E00',
    },
    {
      id: 'brw90',
      text: '231500',
    },
  ],
]
const visibilityList: visibilityInterface[] = [
  {
    id: 0,
    text: 'Pubblico (tutti)',
    icon: 'public',
  },
  {
    id: 1,
    text: 'Privato (solo tu)',
    icon: 'private',
  },
]
const imagesList: imageInterface[] = []
const imagesListFiltered = ref<imageInterface[]>([])
const placesList: placeInterface[] = []
const weathersList: weatherInterface[] = []
const togetherWithList: togetherWithInterface[] = []
const bodyPartsList: bodyPartInterface[] = []

const isLoadingEmotions = ref<boolean>(false)
const isLoadingPlaces = ref<boolean>(false)
const isLoadingWeather = ref<boolean>(false)
const isLoadingTogetherWith = ref<boolean>(false)
const isLoadingBodyParts = ref<boolean>(false)
const isLoadingImages = ref<boolean>(false)

const valueSearchEmotion = ref<string>('')
const valueSearchImage = ref<string>('')
const sourceCreditInfo = ref<string>('')
const offsetImages = ref<number>(0)
const limitImages = ref<number>(50)
const hasMoreImages = ref<boolean>(true)

const isSendingPost = ref<boolean>(false)

const errorDuringCreationToastRef = ref<boolean>(false)

const errorMessageToastRef = ref<boolean>(false)
const errorMessageToastText = ref<string>('')

onMounted(() => {
  loadData()
})

function openCreditInfo(sourceCredit: string) {
  sourceCreditInfo.value = sourceCredit
  showCreditsImageToastRef.value = true
}

function removeSelectedImage() {
  contentImage.value = null
  checkContentEdited()
}

function goToHome() {
  // Navigate to home view
  router.push({ name: 'home' })
}

function goBack() {
  router.back()
}

function goBackWithConfirmation() {
  if (contentEdited.value) {
    confirmationGoBack.value = true
  } else {
    goBack()
  }
}

function loadData() {
  //load emotions
  isLoadingEmotions.value = true
  apiService
    .getEmotions()
    .then((response) => {
      //reset emotionsList array
      emotionsList.splice(0, emotionsList.length)
      if (response && response.status === 200) {
        response.data?.forEach((emotion) => {
          emotionsList.push({
            id: emotion['emotion-id'],
            text: emotion.it,
          })
        })
        emotionsList.sort((a, b) => a.text.localeCompare(b.text))
        emotionsListFiltered.value = emotionsList
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingEmotions.value = false
    })

  //load places
  isLoadingPlaces.value = true
  apiService
    .getPlaces()
    .then((response) => {
      //reset placesList array
      placesList.splice(0, placesList.length)
      if (response && response.status === 200) {
        response.data?.forEach((place) => {
          placesList.push({
            id: place['place-id'],
            text: place.it,
          })
        })
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingPlaces.value = false
    })

  //load weathers
  isLoadingWeather.value = true
  apiService
    .getWeather()
    .then((response) => {
      //reset weathersList array
      if (response && response.status === 200) {
        weathersList.splice(0, weathersList.length)
        response.data?.forEach((weather) => {
          weathersList.push({
            id: weather['weather-id'],
            text: weather.it,
          })
        })
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingWeather.value = false
    })

  //load together with
  isLoadingTogetherWith.value = true
  apiService
    .getTogetherWith()
    .then((response) => {
      //reset togetherWithList array
      togetherWithList.splice(0, togetherWithList.length)
      if (response && response.status === 200) {
        response.data?.forEach((togetherWith) => {
          togetherWithList.push({
            id: togetherWith['together-with-id'],
            text: togetherWith.it,
          })
        })
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingTogetherWith.value = false
    })

  //load body parts
  isLoadingBodyParts.value = true
  apiService
    .getBodyParts()
    .then((response) => {
      //reset bodyPartsList array
      bodyPartsList.splice(0, bodyPartsList.length)
      if (response && response.status === 200) {
        response.data?.forEach((bodyPart) => {
          bodyPartsList.push({
            id: bodyPart['body-part-id'],
            text: bodyPart.it,
          })
        })
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingBodyParts.value = false
    })

  //load images
  loadImages(offsetImages.value, limitImages.value)
}

function loadImages(offset: number, limit: number) {
  isLoadingImages.value = true
  apiService
    .getAllImages(undefined, offset, limit)
    .then((response) => {
      //reset imagesList array
      if (offset === 0) {
        imagesList.splice(0, imagesList.length)
      }
      if (response && response.status === 200) {
        response.data?.forEach((image) => {
          imagesList.push({
            id: image['image-id'],
            url: image['image-url'],
            source: image['image-source'],
          })
        })
        imagesListFiltered.value = imagesList
        if (response.data && response.data.length < limit) {
          hasMoreImages.value = false
        } else {
          hasMoreImages.value = true
        }
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingImages.value = false
    })
}

function loadMoreImages() {
  if (valueSearchImage.value.trim().length >= 3) {
    offsetImages.value += limitImages.value
    onLoadSearchImages(offsetImages.value, limitImages.value)
  } else {
    offsetImages.value += limitImages.value
    loadImages(offsetImages.value, limitImages.value)
  }
}

function checkContentEdited() {
  contentEdited.value =
    emotion.value !== null ||
    visibility.value !== null ||
    color.value !== null ||
    (contentText.value !== null && contentText.value !== '') ||
    contentImage.value !== null ||
    contentPlace.value !== null ||
    (contentLocation.value !== null && contentLocation.value.text !== '') ||
    contentWeather.value !== null ||
    contentTogetherWith.value !== null ||
    contentBodyPart.value !== null
}

function onSelectEmotion(value: number) {
  if (value !== -1) {
    emotion.value = emotionsList.find((e) => e.id === value) || null
    valueSearchEmotion.value = ''
    onSearchEmotion('')
    checkContentEdited()
  }
}

function onSearchEmotion(value: string) {
  if (value === '') {
    emotionsListFiltered.value = emotionsList
    return
  }
  emotionsListFiltered.value = emotionsList.filter((e) =>
    e.text.toLowerCase().includes(value.toLowerCase()),
  )
  valueSearchEmotion.value = value.toLowerCase()
}

function onSearchEnterEmotion() {
  //if there is just one result, select it
  if (emotionsListFiltered.value.length === 1) {
    onSelectEmotion(
      emotionsListFiltered.value &&
        emotionsListFiltered.value[0] &&
        emotionsListFiltered.value[0].id
        ? emotionsListFiltered.value[0].id
        : -1,
    )
    emotionActionSheetRef.value = false
  }
}

function onSearchImage(value: string) {
  valueSearchImage.value = value.toLowerCase()
  offsetImages.value = 0
  hasMoreImages.value = true
  onLoadSearchImages(offsetImages.value, limitImages.value)
}

function onLoadSearchImages(offset: number, limit: number) {
  if (valueSearchImage.value === '' || valueSearchImage.value.trim().length < 3) {
    imagesListFiltered.value = imagesList
    hasMoreImages.value = true
    return
  }
  isLoadingImages.value = true
  apiService
    .searchImages(valueSearchImage.value.toLowerCase(), offset, limit)
    .then((response) => {
      if (offset === 0) {
        imagesListFiltered.value = []
      }
      if (response && response.status === 200) {
        response.data?.forEach((image) => {
          imagesListFiltered.value.push({
            id: image['image-id'],
            url: image['image-url'],
            source: image['image-source'],
          })
        })
        if (response.data && response.data.length < limit) {
          hasMoreImages.value = false
        } else {
          hasMoreImages.value = true
        }
      } else {
        errorMessageToastText.value = `${response.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
        errorMessageToastRef.value = true
      }
    })
    .catch((error) => {
      errorMessageToastText.value = `${error.status} | Si è verificato un errore durante la creazione dell'account. Riprova più tardi.`
      errorMessageToastRef.value = true
    })
    .finally(() => {
      isLoadingImages.value = false
    })
}

function onSelectVisibility(value: number) {
  visibility.value = visibilityList.find((v) => v.id === value) || null
  checkContentEdited()
}

function onSelectColor(value: string) {
  color.value = colorsList.flat().find((c) => c.id === value) || null
  colorActionSheetRef.value = false
  checkContentEdited()
}

function onInputContentText(value: string) {
  contentText.value = value
  checkContentEdited()
}

function onSelectContentImage(value: string) {
  contentImage.value = imagesList.find((i) => i.id === value) || null
  valueSearchImage.value = ''
  onSearchImage('')
  checkContentEdited()
}

function onSelectContentPlace(value: number) {
  contentPlace.value = placesList.find((p) => p.id === value) || null
  checkContentEdited()
}

function onSelectContentLocation(value: string) {
  contentLocation.value = { text: value }
  if (value === '') {
    contentLocation.value = null
    checkContentEdited()
    return
  }
  checkContentEdited()
}

function onSelectContentWeather(value: number) {
  contentWeather.value = weathersList.find((w) => w.id === value) || null
  checkContentEdited()
}

function onSelectContentTogetherWith(value: number) {
  contentTogetherWith.value = togetherWithList.find((t) => t.id === value) || null
  checkContentEdited()
}

function onSelectContentBodyPart(value: number) {
  contentBodyPart.value = bodyPartsList.find((b) => b.id === value) || null
  checkContentEdited()
}

function checkRequiredFields(): boolean {
  return emotion.value !== null && visibility.value !== null && color.value !== null
}

function publishPost() {
  // Here you would typically send the post data to your backend API
  if (checkRequiredFields()) {
    const postData: ApiCreatePostRequest = {
      language: 'it',
      visibility: visibility.value!.id,
      'emotion-id': emotion.value!.id,
      'color-id': color.value!.id,
      text: contentText.value ?? '',
      'image-id': contentImage.value ? contentImage.value.id : null,
      'place-id': contentPlace.value ? contentPlace.value.id : null,
      location: contentLocation.value ? contentLocation.value.text : null,
      'weather-id': contentWeather.value ? contentWeather.value.id : null,
      'together-with-id': contentTogetherWith.value ? contentTogetherWith.value.id : null,
      'body-part-id': contentBodyPart.value ? contentBodyPart.value.id : null,
    }

    isSendingPost.value = true
    apiService
      .insertNewPost(postData)
      .then((response) => {
        // Navigate to home view after successful post creation
        console.log(isSendingPost.value, response)
        if (response.status === 200 || response.status === 204) {
          goBack()
        } else {
          errorDuringCreationToastRef.value = true
        }
      })
      .catch(() => {
        errorDuringCreationToastRef.value = true
      })
      .finally(() => {
        isSendingPost.value = false
      })
  }
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
          :icon="visibility !== null ? visibility.icon : ''"
          :value="visibility !== null ? visibility.text : ''"
          variant="text"
          @onselect="
            () => {
              visibilityActionSheetRef = true
            }
          "
          placeholder="Visibilità"
          :capitalize="true"
          :disabled="isSendingPost"
        />
        <button-select
          :icon="isLoadingEmotions ? 'animated-loading' : ''"
          :value="emotion !== null ? emotion.text : ''"
          variant="text"
          @onselect="
            () => {
              if (!isLoadingEmotions) {
                emotionActionSheetRef = true
              }
            }
          "
          placeholder="Emozione"
          :capitalize="true"
          :disabled="isSendingPost"
        />
        <button-select
          icon=""
          :value="color !== null ? color.text : ''"
          variant="color"
          @onselect="
            () => {
              colorActionSheetRef = true
            }
          "
          placeholder="Colore"
          :disabled="isSendingPost"
        />
      </div>
    </horizontal-overflow>
    <separator variant="primary" />
    <h1>Campi facoltativi</h1>
    <input-multiline
      placeholder="Scrivi qualcosa…"
      @input="onInputContentText"
      :disabled="isSendingPost"
    ></input-multiline>
    <horizontal-overflow>
      <div class="row">
        <button-select
          icon="image"
          :value="contentImage !== null ? 'Immagine' : ''"
          variant="text"
          @onselect="
            () => {
              imageActionSheetRef = true
            }
          "
          placeholder="Immagine"
          :disabled="isSendingPost"
        />
        <button-select
          :icon="isLoadingPlaces ? 'animated-loading' : 'place'"
          :value="contentPlace ? contentPlace.text : ''"
          variant="text"
          @onselect="
            () => {
              if (!isLoadingPlaces) {
                placeActionSheetRef = true
              }
            }
          "
          placeholder="Posto"
          :capitalize="true"
          :disabled="isSendingPost"
        />
        <button-select
          icon="location"
          :value="contentLocation ? contentLocation.text : ''"
          variant="text"
          @onselect="
            () => {
              locationActionSheetRef = true
            }
          "
          placeholder="Luogo"
          :capitalize="true"
          :disabled="isSendingPost"
        />
        <button-select
          :icon="isLoadingWeather ? 'animated-loading' : 'sun'"
          :value="contentWeather ? contentWeather.text : ''"
          variant="text"
          @onselect="
            () => {
              if (!isLoadingWeather) {
                weatherActionSheetRef = true
              }
            }
          "
          placeholder="Meteo"
          :capitalize="true"
          :disabled="isSendingPost"
        />
        <button-select
          :icon="isLoadingTogetherWith ? 'animated-loading' : 'people'"
          :value="contentTogetherWith ? contentTogetherWith.text : ''"
          variant="text"
          @onselect="
            () => {
              if (!isLoadingTogetherWith) {
                togetherWithActionSheetRef = true
              }
            }
          "
          placeholder="Insieme a"
          :capitalize="true"
          :disabled="isSendingPost"
        />
        <button-select
          :icon="isLoadingBodyParts ? 'animated-loading' : 'head'"
          :value="contentBodyPart ? contentBodyPart.text : ''"
          variant="text"
          @onselect="
            () => {
              if (!isLoadingBodyParts) {
                bodyPartActionSheetRef = true
              }
            }
          "
          placeholder="Parte del corpo"
          :capitalize="true"
          :disabled="isSendingPost"
        />
      </div>
    </horizontal-overflow>
    <div class="image-content image-item" v-if="contentImage !== null">
      <img :src="contentImage.url" :alt="contentImage.url" />
      <div class="button-credit" v-if="contentImage.source !== ''">
        <button-generic
          icon="info"
          variant="primary"
          :small="true"
          :disabled-hover-effect="true"
          @action="
            () => {
              openCreditInfo(contentImage?.source ?? '')
            }
          "
        />
      </div>
      <div class="button-remove" v-if="contentImage !== null">
        <button-generic
          icon="trash"
          variant="warning"
          :small="true"
          :disabled-hover-effect="true"
          @action="removeSelectedImage"
        />
      </div>
    </div>
    <separator variant="primary" />
    <button-generic
      variant="cta"
      icon="forward"
      text="Conferma creazione dello stato emotivo"
      :disabled="!checkRequiredFields() || isSendingPost"
      @action="publishPost"
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
    @action-button2="goBack"
    :height="50"
    @onclose="confirmationGoBack = false"
  >
    Il contenuto che stavi creando verrà perso se esci senza salvare.
  </action-sheet>

  <action-sheet
    v-if="visibilityActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona la visibilità"
    button1-text="Chiudi"
    @onclose="visibilityActionSheetRef = false"
    :height="40"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="false"
  >
    <!-- Visibility options would go here -->
    <div class="option-list">
      <button-generic
        v-for="option in visibilityList"
        :key="option.id"
        variant="simple"
        :text="option.text"
        :icon="option.icon"
        icon-position="end"
        align="space"
        :no-border-radius="true"
        :always-show-as-hover="visibility !== null && visibility.id === option.id"
        @action="
          () => {
            onSelectVisibility(option.id)
            visibilityActionSheetRef = false
          }
        "
      />
    </div>
  </action-sheet>

  <action-sheet
    v-if="emotionActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona l'emozione"
    button1-text="Chiudi"
    @onclose="emotionActionSheetRef = false"
    :height="80"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="false"
  >
    <div class="option-list no-margin">
      <div class="search">
        <input-searchbox
          placeholder="Ricerca un'emozione…"
          @input="onSearchEmotion($event)"
          @onenter="onSearchEnterEmotion"
          :text="valueSearchEmotion"
          :min-length="0"
        />
      </div>
      <button-generic
        v-for="option in emotionsListFiltered"
        :key="option.id"
        variant="simple"
        :text="option.text"
        :icon="emotion !== null && emotion.id === option.id ? 'mark-yes' : ''"
        icon-position="end"
        align="space"
        :no-border-radius="true"
        :always-show-as-hover="emotion !== null && emotion.id === option.id"
        @action="
          () => {
            onSelectEmotion(option.id)
            emotionActionSheetRef = false
          }
        "
      />
    </div>
  </action-sheet>

  <action-sheet
    v-if="colorActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona il colore"
    button1-text="Chiudi"
    @onclose="colorActionSheetRef = false"
    :height="80"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="false"
    :height-full="true"
  >
    <div class="colors-box">
      <div class="column" v-for="col_color in colorsList" :key="col_color[0]?.text">
        <div
          class="color"
          :class="{ selected: color !== null && cell_color.id === color.id }"
          :style="{ 'background-color': `#${cell_color.text}` }"
          @click="onSelectColor(cell_color.id)"
          v-for="cell_color in col_color"
          :key="cell_color.id"
        ></div>
      </div>
    </div>
  </action-sheet>

  <action-sheet
    v-if="imageActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona immagine"
    :button1-text="contentImage ? 'Elimina' : 'Chiudi'"
    :button1-icon="contentImage ? 'trash' : 'chevron-down'"
    :button1-style="contentImage ? 'warning' : 'primary'"
    @action-button1="
      () => {
        if (contentImage) {
          contentImage = null
          checkContentEdited()
        }
        imageActionSheetRef = false
      }
    "
    :button2-text="contentImage ? 'Conferma' : ''"
    button2-style="cta"
    button2-icon="mark-yes"
    @action-button2="imageActionSheetRef = false"
    @onclose="
      () => {
        imageActionSheetRef = false
        onSearchImage('')
      }
    "
    :height="99"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
  >
    <div class="images-box">
      <div class="search">
        <input-searchbox
          placeholder="Ricerca un'immagine…"
          @input="onSearchImage($event)"
          :text="valueSearchImage"
          :min-length="0"
        />
      </div>
      <infinite-scroll
        :loading="isLoadingImages"
        :has-more="hasMoreImages"
        @load-more="loadMoreImages"
      >
        <div class="no-results" v-if="!isLoadingImages && imagesListFiltered.length === 0">
          <text-paragraph> Nessuna immagine trovata. </text-paragraph>
        </div>
        <div class="images-grid" v-if="imagesListFiltered.length > 0">
          <div
            class="image-item"
            v-for="img in imagesListFiltered"
            :key="img.id"
            :class="{ selected: contentImage !== null && contentImage.id === img.id }"
          >
            <img :src="`${img.url}?url`" :alt="img.url" @click="onSelectContentImage(img.id)" />
            <div class="button-credit">
              <button-generic
                icon="info"
                variant="primary"
                :small="true"
                :disabled-hover-effect="true"
                @action="
                  () => {
                    openCreditInfo(img.source ?? '')
                  }
                "
                v-if="img.source !== ''"
              />
            </div>
          </div>
        </div>
        <div class="loading-images" v-if="isLoadingImages && imagesListFiltered.length > 0">
          <spinner color="primary" />
        </div>
      </infinite-scroll>
    </div>
  </action-sheet>

  <action-sheet
    v-if="placeActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona posto"
    :button1-text="contentPlace ? 'Elimina' : 'Chiudi'"
    :button1-icon="contentPlace ? 'trash' : 'chevron-down'"
    :button1-style="contentPlace ? 'warning' : 'primary'"
    @action-button1="
      () => {
        if (contentPlace) {
          contentPlace = null
          checkContentEdited()
        }
        placeActionSheetRef = false
      }
    "
    :button2-text="contentPlace ? 'Conferma' : ''"
    button2-style="cta"
    button2-icon="mark-yes"
    @action-button2="placeActionSheetRef = false"
    @onclose="placeActionSheetRef = false"
    :height="70"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
  >
    <div class="option-list">
      <button-generic
        v-for="option in placesList"
        :key="option.id"
        variant="simple"
        :text="option.text"
        :icon="contentPlace !== null && contentPlace.id === option.id ? 'mark-yes' : ''"
        icon-position="end"
        align="space"
        :no-border-radius="true"
        :always-show-as-hover="contentPlace !== null && contentPlace.id === option.id"
        @action="
          () => {
            onSelectContentPlace(option.id)
            //placeActionSheetRef = false
          }
        "
      />
    </div>
  </action-sheet>

  <action-sheet
    v-if="locationActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Inserisci luogo"
    :button1-text="contentLocation ? 'Elimina' : 'Chiudi'"
    :button1-icon="contentLocation ? 'trash' : 'chevron-down'"
    :button1-style="contentLocation ? 'warning' : 'primary'"
    @action-button1="
      () => {
        if (contentLocation) {
          contentLocation = null
          checkContentEdited()
        }
        locationActionSheetRef = false
      }
    "
    :button2-text="contentLocation ? 'Conferma' : ''"
    button2-style="cta"
    button2-icon="mark-yes"
    @action-button2="locationActionSheetRef = false"
    @onclose="locationActionSheetRef = false"
    :height="50"
    :fullscreen-possible="true"
    :no-padding="false"
    :show-buttons="true"
  >
    <div class="option-box">
      <input-generic
        :text="contentLocation ? contentLocation.text : ''"
        placeholder="Digita il luogo da inserire (es. Trento)"
        icon="location"
        :min-length="0"
        :max-length="100"
        :debounce-time="0"
        @onenter="locationActionSheetRef = false"
        @input="onSelectContentLocation"
      />
    </div>
  </action-sheet>

  <action-sheet
    v-if="weatherActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona meteo"
    :button1-text="contentWeather ? 'Elimina' : 'Chiudi'"
    :button1-icon="contentWeather ? 'trash' : 'chevron-down'"
    :button1-style="contentWeather ? 'warning' : 'primary'"
    @action-button1="
      () => {
        if (contentWeather) {
          contentWeather = null
          checkContentEdited()
        }
        weatherActionSheetRef = false
      }
    "
    :button2-text="contentWeather ? 'Conferma' : ''"
    button2-style="cta"
    button2-icon="mark-yes"
    @action-button2="weatherActionSheetRef = false"
    @onclose="weatherActionSheetRef = false"
    :height="80"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
  >
    <div class="option-list">
      <button-generic
        v-for="option in weathersList"
        :key="option.id"
        variant="simple"
        :text="option.text"
        :icon="contentWeather !== null && contentWeather.id === option.id ? 'mark-yes' : ''"
        icon-position="end"
        align="space"
        :no-border-radius="true"
        :always-show-as-hover="contentWeather !== null && contentWeather.id === option.id"
        @action="
          () => {
            onSelectContentWeather(option.id)
            //weatherActionSheetRef = false
          }
        "
      />
    </div>
  </action-sheet>

  <action-sheet
    v-if="togetherWithActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona con chi"
    :button1-text="contentTogetherWith ? 'Elimina' : 'Chiudi'"
    :button1-icon="contentTogetherWith ? 'trash' : 'chevron-down'"
    :button1-style="contentTogetherWith ? 'warning' : 'primary'"
    @action-button1="
      () => {
        if (contentTogetherWith) {
          contentTogetherWith = null
          checkContentEdited()
        }
        togetherWithActionSheetRef = false
      }
    "
    :button2-text="contentTogetherWith ? 'Conferma' : ''"
    button2-style="cta"
    button2-icon="mark-yes"
    @action-button2="togetherWithActionSheetRef = false"
    @onclose="togetherWithActionSheetRef = false"
    :height="80"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
  >
    <div class="option-list">
      <button-generic
        v-for="option in togetherWithList"
        :key="option.id"
        variant="simple"
        :text="option.text"
        :icon="
          contentTogetherWith !== null && contentTogetherWith.id === option.id ? 'mark-yes' : ''
        "
        icon-position="end"
        align="space"
        :no-border-radius="true"
        :always-show-as-hover="contentTogetherWith !== null && contentTogetherWith.id === option.id"
        @action="
          () => {
            onSelectContentTogetherWith(option.id)
            //togetherWithActionSheetRef = false
          }
        "
      />
    </div>
  </action-sheet>

  <action-sheet
    v-if="bodyPartActionSheetRef"
    :hidden-by-default="false"
    variant="standard"
    title="Seleziona parte del corpo"
    :button1-text="contentBodyPart ? 'Elimina' : 'Chiudi'"
    :button1-icon="contentBodyPart ? 'trash' : 'chevron-down'"
    :button1-style="contentBodyPart ? 'warning' : 'primary'"
    @action-button1="
      () => {
        if (contentBodyPart) {
          contentBodyPart = null
          checkContentEdited()
        }
        bodyPartActionSheetRef = false
      }
    "
    :button2-text="contentBodyPart ? 'Conferma' : ''"
    button2-style="cta"
    button2-icon="mark-yes"
    @action-button2="bodyPartActionSheetRef = false"
    @onclose="bodyPartActionSheetRef = false"
    :height="80"
    :fullscreen-possible="true"
    :no-padding="true"
    :show-buttons="true"
  >
    <div class="option-list">
      <button-generic
        v-for="option in bodyPartsList"
        :key="option.id"
        variant="simple"
        :text="option.text"
        :icon="contentBodyPart !== null && contentBodyPart.id === option.id ? 'mark-yes' : ''"
        icon-position="end"
        align="space"
        :no-border-radius="true"
        :always-show-as-hover="contentBodyPart !== null && contentBodyPart.id === option.id"
        @action="
          () => {
            onSelectContentBodyPart(option.id)
            //bodyPartActionSheetRef = false
          }
        "
      />
    </div>
  </action-sheet>

  <toast
    v-if="showCreditsImageToastRef && sourceCreditInfo"
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
    {{ sourceCreditInfo }}
  </toast>

  <toast
    v-if="errorDuringCreationToastRef"
    variant="warning"
    :show-button="false"
    :life-seconds="0"
    position="bottom"
    @onclose="
      () => {
        errorDuringCreationToastRef = false
      }
    "
  >
    Si è verificato un errore nella creazione dello stato emotivo. Riprova più tardi.
    <br />
    Se il problema persiste, contatta l'assistenza per favore.
  </toast>

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

  > .image-content {
    border-radius: var(--border-radius);
    border: 1px solid var(--color-white-o60);
    overflow: hidden;
    opacity: 1;
    position: relative;
    height: 180px;

    > img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    > .button-credit {
      position: absolute;
      bottom: var(--spacing-8);
      right: var(--spacing-8);
    }

    > .button-remove {
      position: absolute;
      top: var(--spacing-8);
      right: var(--spacing-8);
    }
  }
}

.modal-action-sheet {
  .option-list {
    display: flex;
    flex-direction: column;
    gap: var(--no-spacing);
    width: 100%;

    margin: var(--spacing-16) var(--no-spacing);

    text-transform: capitalize;

    &.no-margin {
      margin: var(--no-spacing);
    }

    .search {
      padding: var(--padding-16);
      text-transform: none;
      background-color: var(--color-white);

      position: sticky;
      top: 0;
      z-index: 10;
    }
  }

  .option-box {
    display: flex;
    flex-direction: column;
    gap: var(--no-spacing);
    width: 100%;
  }

  .images-box {
    display: flex;
    flex-direction: column;
    gap: var(--no-spacing);
    padding: var(--no-spacing);

    > .search {
      padding: var(--spacing-16);
      text-transform: none;
      background-color: var(--color-white);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    > .loading-images {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: var(--padding);
      position: relative;
      min-height: 100px;
    }
    > .no-results {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: var(--padding);
      position: relative;
      min-height: 100px;
    }

    .images-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: var(--spacing-8);
      padding: var(--padding-8) var(--padding);
      height: auto;

      > .image-item {
        border-radius: var(--border-radius);
        border: 1px solid var(--color-white-o60);
        overflow: hidden;
        opacity: 0.7;
        position: relative;
        height: 180px;

        &.selected {
          box-shadow: 0 0 0 4px var(--primary);
          opacity: 1;
        }

        > img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        > .button-credit {
          position: absolute;
          bottom: var(--spacing-8);
          right: var(--spacing-8);
        }

        > .button-remove {
          position: absolute;
          top: var(--spacing-8);
          right: var(--spacing-8);
        }
      }
    }
  }

  .colors-box {
    display: flex;
    flex-direction: row;
    gap: var(--spacing-8);
    padding: var(--spacing-8);
    justify-content: center;
    height: 100% !important;

    .column {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-8);
      flex: 1;

      .color {
        min-width: 40px;
        min-height: 40px;
        width: 100%;
        height: 100%;
        border-radius: var(--border-radius-4);
        cursor: pointer;
        border: 1px solid var(--color-white-o60);
        flex: 1;

        &.selected {
          box-shadow: 0 0 0 4px var(--primary);
        }
      }
    }
  }
}
</style>
