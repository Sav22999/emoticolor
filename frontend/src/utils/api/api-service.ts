import type {
  ApiBodyPartResponse,
  ApiCreatedPostResponse,
  ApiCreatePostRequest,
  ApiEmotionResponse,
  ApiEmotionsFollowedResponse,
  ApiErrorResponse,
  ApiImagesResponse,
  ApiLearningContentsResponse,
  ApiLearningContentsStatisticsResponse,
  ApiLearningStatisticsResponse,
  ApiLoginIdRefreshIdResponse,
  ApiLoginIdResponse,
  ApiPlaceResponse,
  ApiPostsResponse,
  ApiReactionsPostResponse,
  ApiSearchResponse,
  ApiSuccessNoContentResponse,
  ApiTogetherWithResponse,
  ApiUserProfileResponse,
  ApiUsersFollowedResponse,
  ApiWeatherResponse,
} from '@/utils/api/api-interface.ts'
import usefulFunctions from '@/utils/useful-functions.ts'

export default class apiService {
  private static API_BASE_URL = 'https://www.saveriomorelli.com/api/emoticolor' // the base URL of the API
  private static API_VERSION = 'v1' // the version of the API

  /**
   * Get the full URL for a given endpoint
   * @param endpoint - The API endpoint (e.g. "account/check-auth") | automatically adds slashes at the start and end
   * @returns The full URL as a string
   */
  static getFullUrl(endpoint: string): string {
    return `${this.API_BASE_URL}/${this.API_VERSION}/${endpoint}/`
  }

  static async checkLoginIdValid(
    loginId: string,
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/auth-check')}`, {
      body: JSON.stringify({ 'login-id': loginId }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    return await response.json()
  }

  static async refreshLoginId(
    loginId: string,
    refreshId: string,
  ): Promise<ApiLoginIdResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/auth-check/refresh')}`, {
      body: JSON.stringify({ 'login-id': loginId, 'token-id': refreshId }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }

    const data: ApiLoginIdResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Login user with email and password
   * @param email - User email
   * @param password - User password
   * @returns ApiLoginIdResponse or ApiErrorResponse
   */
  static async login(
    email: string,
    password: string,
  ): Promise<ApiLoginIdResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/login')}`, {
      body: JSON.stringify({ email: email, password: password }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLoginIdResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Signup user with email and password
   * @param email - User email
   * @param password - User password
   * @param username - User username
   * @returns ApiLoginIdResponse or ApiErrorResponse
   */
  static async signup(
    email: string,
    password: string,
    username: string,
  ): Promise<ApiLoginIdResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/signup')}`, {
      body: JSON.stringify({ email: email, password: password, username: username }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLoginIdResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Request for password reset
   * @param email - User email
   * @returns ApiLoginIdResponse or ApiErrorResponse
   */
  static async resetPassword(email: string): Promise<ApiLoginIdResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/reset-password')}`, {
      body: JSON.stringify({ email: email }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLoginIdResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Set new password after reset
   * @param loginId - Login ID from reset password request
   * @param newPassword - New password to set
   * @returns ApiLoginIdResponse or ApiErrorResponse
   */
  static async setNewPassword(
    loginId: string,
    newPassword: string,
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/change-password/set')}`, {
      body: JSON.stringify({ 'login-id': loginId, password: newPassword }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Verify otp code
   * @param loginId - Login ID from previous request
   * @param code - OTP code to verify
   * @returns ApiLoginIdResponse or ApiLoginIdRefreshIdResponse or ApiErrorResponse
   */
  static async verifyOtpCode(
    loginId: string,
    code: string,
  ): Promise<
    | ApiLoginIdResponse
    | ApiLoginIdRefreshIdResponse
    | ApiSuccessNoContentResponse
    | ApiErrorResponse
  > {
    const response = await fetch(`${apiService.getFullUrl('account/code/verify')}`, {
      body: JSON.stringify({ 'login-id': loginId, code: code }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiLoginIdResponse | ApiLoginIdRefreshIdResponse | ApiErrorResponse =
      await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get new otp code
   * @param loginId - Login ID from previous request
   * @returns ApiSuccessNoContentResponse or ApiErrorResponse
   */
  static async newOtpCode(
    loginId: string,
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('account/code/get')}`, {
      body: JSON.stringify({ 'login-id': loginId }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get posts for the home
   * @param language - Preferred language for posts (default: 'it')
   * @param offset - Offset for pagination (default: 0)
   * @param limit - Limit for pagination (default: 50)
   * @returns ApiPostsResponse or ApiErrorResponse
   */
  static async getHomePosts(
    language: string = 'it',
    offset: number,
    limit: number,
  ): Promise<ApiPostsResponse | ApiErrorResponse> {
    //check if loginId is stored in localStorage
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const response = await fetch(`${apiService.getFullUrl('post/get')}`, {
      body: JSON.stringify({
        'login-id': loginId,
        language: language,
        offset: offset,
        limit: limit,
      }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiPostsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get posts for a specific user
   * @param username - Username of the user
   * @param offset - Offset for pagination (default: 0)
   * @param limit - Limit for pagination (default: 50)
   * @returns ApiPostsResponse or ApiErrorResponse
   */
  static async getUserPosts(
    username: string | null,
    offset: number,
    limit: number,
  ): Promise<ApiPostsResponse | ApiErrorResponse> {
    //check if loginId is stored in localStorage
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const response = await fetch(`${apiService.getFullUrl('post/get')}`, {
      body: JSON.stringify({
        'login-id': loginId,
        username: username ?? undefined,
        'my-profile': username === null ? true : undefined,
        offset: offset,
        limit: limit,
      }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiPostsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get a specific post by post-id
   */
  static async getPostById(
    postId: string,
    language: string = 'it',
  ): Promise<ApiPostsResponse | ApiErrorResponse> {
    //check if loginId is stored in localStorage
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    const response = await fetch(`${apiService.getFullUrl('post/get')}`, {
      body: JSON.stringify({
        'login-id': loginId ?? undefined,
        'post-id': postId,
        language: language,
      }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiPostsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }
  /**
   * Add / Remove reaction to a post
   * @param postId - Post ID
   * @param reactionId - Reaction ID
   * @param action - 'add' to add reaction, 'remove' to remove reaction
   * @returns ApiSuccessNoContentResponse or ApiErrorResponse
   */
  static async togglePostReaction(
    postId: string,
    reactionId: number,
    action: 'add' | 'remove',
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    //check if loginId is stored in localStorage
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const endpoint = action === 'add' ? 'reactions/add' : 'reactions/remove'
    const response = await fetch(`${apiService.getFullUrl(endpoint)}`, {
      body: JSON.stringify({
        'login-id': loginId,
        'post-id': postId,
        'reaction-id': reactionId,
      }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get available reactions (for post reactions optionally)
   * @param postId - Post ID (optional)
   * @returns ApiReactionsPostResponse or ApiErrorResponse
   */
  static async getReactions(postId?: string): Promise<ApiReactionsPostResponse | ApiErrorResponse> {
    //check if loginId is stored in localStorage
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body: { 'login-id': string; 'post-id'?: string } = { 'login-id': loginId }
    if (postId) {
      body['post-id'] = postId
    }
    const response = await fetch(`${apiService.getFullUrl('reactions/get')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiReactionsPostResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get available emotions
   */
  static async getEmotions(emotionId?: number): Promise<ApiEmotionResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
    }
    let url = `${apiService.getFullUrl('emotions/get')}`
    if (emotionId) {
      url += `?emotion-id=${emotionId}`
    }
    const response = await fetch(url, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiEmotionResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get available places
   */
  static async getPlaces(): Promise<ApiPlaceResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('places/get')}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiPlaceResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get available together-with options
   */
  static async getTogetherWith(): Promise<ApiTogetherWithResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('together-with/get')}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiTogetherWithResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get available weather options
   */
  static async getWeather(): Promise<ApiWeatherResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('weather/get')}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiWeatherResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get available body parts
   */
  static async getBodyParts(): Promise<ApiBodyPartResponse | ApiErrorResponse> {
    const response = await fetch(`${apiService.getFullUrl('body-parts/get')}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiBodyPartResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get all images
   * @param imageId - Image ID to filter (optional)
   * @param offset - Offset for pagination (optional)
   * @param limit - Limit for pagination (optional)
   * @returns ApiImagesResponse or ApiErrorResponse
   */
  static async getAllImages(
    imageId?: string,
    offset: number = 0,
    limit: number = 50,
  ): Promise<ApiImagesResponse | ApiErrorResponse> {
    const body: { 'image-id'?: string; offset?: number; limit?: number } = {}
    if (imageId) {
      body['image-id'] = imageId
    }
    body.offset = offset
    body.limit = limit
    let url = `${apiService.getFullUrl('images/get')}`
    if (Object.keys(body).length > 0) {
      url += `?${new URLSearchParams(body as Record<string, string>).toString()}`
    }
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiImagesResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Search for images
   */
  static async searchImages(
    query: string,
    offset: number = 0,
    limit: number = 50,
    language: string = 'it',
  ): Promise<ApiImagesResponse | ApiErrorResponse> {
    const body: { text?: string; language?: string; offset?: number; limit?: number } = {}
    if (query) {
      body['text'] = query
    }
    body.offset = offset
    body.limit = limit
    body.language = language

    let url = `${apiService.getFullUrl('images/search')}`
    if (Object.keys(body).length > 0) {
      url += `?${new URLSearchParams(body as Record<string, string>).toString()}`
    }
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiImagesResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Insert a new post
   */
  static async insertNewPost(
    data: ApiCreatePostRequest,
  ): Promise<ApiCreatedPostResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body: ApiCreatePostRequest = { ...data }
    body['login-id'] = loginId
    const response = await fetch(`${apiService.getFullUrl('post/new')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const responseData: ApiCreatedPostResponse | ApiErrorResponse = await response.json()
    responseData.status = response.status
    return responseData
  }

  /**
   * Search for emotions and users
   */
  static async searchEmotionsAndUsers(
    query: string,
    user: boolean,
    emotion: boolean,
    language: string = 'it',
    offset: number = 0,
    limit: number = 50,
  ): Promise<ApiSearchResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const bodyPost = {
      'login-id': loginId,
    }
    const bodyGet = {
      q: query,
      user: user,
      emotion: emotion,
      language: language,
      offset: offset,
      limit: limit,
    }
    let url = `${apiService.getFullUrl('search')}`
    url += `?q=${bodyGet.q}&user=${bodyGet.user}&emotion=${bodyGet.emotion}&language=${bodyGet.language}&offset=${bodyGet.offset}&limit=${bodyGet.limit}`
    const response = await fetch(url, {
      body: JSON.stringify(bodyPost),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiSearchResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Set follow or unfollow an user
   */
  static async toggleUserFollow(
    username: string,
    action: 'follow' | 'unfollow',
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const endpoint = action === 'follow' ? 'follow' : 'unfollow'
    const response = await fetch(`${apiService.getFullUrl(`users/${endpoint}`)}`, {
      body: JSON.stringify({
        'login-id': loginId,
        username: username,
      }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Set follow or unfollow an emotion
   */
  static async toggleEmotionFollow(
    emotionId: number,
    action: 'follow' | 'unfollow',
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const endpoint = action === 'follow' ? 'follow' : 'unfollow'
    const response = await fetch(`${apiService.getFullUrl(`emotions/${endpoint}`)}`, {
      body: JSON.stringify({
        'login-id': loginId,
        'emotion-id': emotionId,
      }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get details of a specific user (if not provided, get own user details)
   */
  static async getUserDetails(
    username: string | null,
  ): Promise<ApiUserProfileResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
    }
    let url = `${apiService.getFullUrl('users/get')}`
    if (username !== null) {
      url += `?username=${encodeURIComponent(username)}`
    }
    const response = await fetch(url, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiUserProfileResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get emotions followed
   */
  static async getFollowedEmotions(): Promise<ApiEmotionsFollowedResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
    }
    const response = await fetch(`${apiService.getFullUrl('emotions/followed')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiEmotionsFollowedResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get users followed
   */
  static async getFollowedUsers(): Promise<ApiUsersFollowedResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
    }
    const response = await fetch(`${apiService.getFullUrl('users/followed')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiUsersFollowedResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Logout user (send both login-id and token-id to invalidate them)
   * Return 204 if successful, otherwise return error
   */
  static async logout(): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    const refreshId = usefulFunctions.loadFromLocalStorage('token-id')
    if (!loginId || !refreshId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'token-id': refreshId,
    }
    const response = await fetch(`${apiService.getFullUrl('account/logout')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get all notifications associated to the user
   */
  static async getNotifications(
    limit: number = 20,
    offset: number = 0,
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      limit: limit,
      offset: offset,
    }
    const response = await fetch(`${apiService.getFullUrl('notifications/get')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Mark notifications as read
   */
  static async markNotificationsAsRead(
    notificationId: number,
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'notification-id': notificationId,
    }
    const response = await fetch(`${apiService.getFullUrl('notifications/read')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    // If no content
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }

    // Robust parsing: read text first and only parse JSON if non-empty
    const text = await response.text()
    if (!text || text.trim() === '') {
      return {
        status: response.status,
        data: null,
      }
    }
    try {
      const data: ApiSuccessNoContentResponse | ApiErrorResponse = JSON.parse(text)
      data.status = response.status
      return data
    } catch (err) {
      console.error('Failed to parse JSON response for markNotificationsAsRead:', err)
      return {
        status: response.status,
        data: null,
      }
    }
  }

  /**
   * Get learning statistics
   */
  static async getLearningStatistics(
    language: string = 'it',
    emotionId: number | null = null,
  ): Promise<ApiLearningStatisticsResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'emotion-id': emotionId ?? undefined,
      language: language,
    }
    const response = await fetch(`${apiService.getFullUrl('learning/statistics/get')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLearningStatisticsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Insert item in learning statistics
   */
  static async insertLearningStatistics(
    emotionId: number,
    status: 'not-started' | 'learning' | 'learned' | 'reviewed',
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    let statusToUse: 0 | 1 | 2 | 3 = 0
    if (status === 'not-started') {
      statusToUse = 0
    } else if (status === 'learning') {
      statusToUse = 1
    } else if (status === 'learned') {
      statusToUse = 2
    } else if (status === 'reviewed') {
      statusToUse = 3
    }
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'emotion-id': emotionId,
      type: statusToUse,
    }
    const response = await fetch(`${apiService.getFullUrl('learning/statistics/insert')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * POST request | Get learning contents for a specific emotion
   */
  static async getLearningContents(
    emotionId: number | null,
    type: 'pill' | 'path',
    type2: number | null,
    sorted: boolean = true,
    language: string = 'it',
  ): Promise<ApiLearningContentsResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'emotion-id': emotionId ?? undefined,
      language: language,
      type: type === 'path' ? 0 : 1,
      type2: type2 ?? undefined,
      sorted: sorted,
    }
    const response = await fetch(`${apiService.getFullUrl('learning/contents/get')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLearningContentsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get learning statistics (for all emotions)
   */
  static async getLearningStatisticsEmotions(
    language: string = 'it',
  ): Promise<ApiLearningStatisticsResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      language: language,
    }
    const response = await fetch(`${apiService.getFullUrl('learning/statistics/emotions')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLearningStatisticsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Get learning contents statistics
   */
  static async getLearningContentsStatistics(
    emotionId: number,
  ): Promise<ApiLearningContentsStatisticsResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'emotion-id': emotionId,
    }
    const response = await fetch(`${apiService.getFullUrl('learning/contents/statistics/get')}`, {
      body: JSON.stringify(body),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    const data: ApiLearningContentsStatisticsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }

  /**
   * Insert learning content progress
   */
  static async insertLearningContentProgress(
    emotionId: number,
    type: 'pill' | 'path',
    type2: number,
  ): Promise<ApiSuccessNoContentResponse | ApiErrorResponse> {
    const loginId = usefulFunctions.loadFromLocalStorage('login-id')
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
        data: null,
      }
    }
    const body = {
      'login-id': loginId,
      'emotion-id': emotionId,
      type: type === 'path' ? 0 : 1,
      type2: type2,
    }
    const response = await fetch(
      `${apiService.getFullUrl('learning/contents/statistics/insert')}`,
      {
        body: JSON.stringify(body),
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
      },
    )
    if (!response.ok) {
      return {
        status: response.status,
        message: `API request failed`,
        data: null,
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
        data: null,
      }
    }
    const data: ApiSuccessNoContentResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }
}
