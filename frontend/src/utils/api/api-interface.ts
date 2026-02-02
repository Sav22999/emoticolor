import type { ReactionType } from '@/utils/types.ts'

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
