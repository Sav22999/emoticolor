import type {
  ApiErrorResponse,
  ApiLoginIdRefreshIdResponse,
  ApiLoginIdResponse,
  ApiPostsResponse,
  ApiReactionsPostResponse,
  ApiSuccessNoContentResponse,
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
   * Add / Remove reaction to a post
   * @param postId - Post ID
   * @param reactionId - Reaction ID
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
}
