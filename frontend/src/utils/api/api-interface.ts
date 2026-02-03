import type { ReactionType, searchResultInterface } from '@/utils/types.ts'

export interface ApiLoginIdResponse extends ApiSuccessResponse {
  data: {
    'login-id': string
  }
}

export interface ApiLoginIdRefreshIdResponse extends ApiSuccessResponse {
  data: {
    'login-id': string
    'token-id': string
  }
}

export interface ApiSuccessNoContentResponse {
  status: number
  data: null
}

export interface ApiSuccessResponse {
  status: number
  message: string
}

export interface ApiErrorResponse {
  status: number
  message: string
  data: null
}

export interface ApiPostsResponse extends ApiSuccessResponse {
  data: ApiPostDetailedData[]
}

export interface ApiPostDetailedData {
  // identifiers & timestamps
  'post-id': string
  created: string

  // user info
  username: string
  'profile-image': string

  // flags and visibility
  'is-own-post': true
  'is-user-followed': boolean
  'is-emotion-followed': boolean
  visibility: number

  // post metadata
  language: string
  text: string | null
  'color-id': string
  'color-hex': string

  image: { 'image-id': string; 'image-url': string; 'image-source': string } | null
  location: string | null

  // emotion
  'emotion-id': number
  'emotion-text': string

  // weather
  'weather-id': string | null
  'weather-text': string | null
  'weather-icon': string | null

  // place
  'place-id': string | null
  'place-text': string | null
  'place-icon': string | null

  // together-with
  'together-with-id': string | null
  'together-with-text': string | null
  'together-with-icon': string | null

  // body-part
  'body-part-id': string | null
  'body-part-text': string | null
  'body-part-icon': string | null
}

export interface ApiReactionsPostResponse extends ApiSuccessResponse {
  data: ApiReactionsPostType[]
}

export type ApiReactionsPostType = {
  'reaction-id': number
  'reaction-icon-id': ReactionType
  'is-inserted': boolean | null
  count: number | null
}

export interface ApiEmotionResponse extends ApiSuccessResponse {
  data: {
    'emotion-id': number
    it: string
  }[]
}

export interface ApiPlaceResponse extends ApiSuccessResponse {
  data: {
    'place-id': number
    it: string
  }[]
}

export interface ApiWeatherResponse extends ApiSuccessResponse {
  data: {
    'weather-id': number
    it: string
  }[]
}

export interface ApiTogetherWithResponse extends ApiSuccessResponse {
  data: {
    'together-with-id': number
    it: string
  }[]
}

export interface ApiBodyPartResponse extends ApiSuccessResponse {
  data: {
    'body-part-id': number
    it: string
  }[]
}

export interface ApiImagesResponse extends ApiSuccessResponse {
  data: {
    'image-id': string
    'image-url': string
    'image-source': string
  }[]
}

export interface ApiCreatePostRequest {
  'login-id'?: string //it's required, but added in runtime before sending
  language: string
  visibility: 0 | 1
  'emotion-id': number
  'color-id': string
  text: string | null
  'image-id': string | null
  location: string | null
  'weather-id': number | null
  'place-id': number | null
  'together-with-id': number | null
  'body-part-id': number | null
}

export interface ApiCreatedPostResponse extends ApiSuccessResponse {
  data: {
    'post-id': string
  }
}

export interface ApiSearchResponse extends ApiSuccessResponse {
  data: searchResultInterface[]
}
