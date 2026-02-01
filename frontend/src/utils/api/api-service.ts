import type {
  ApiErrorResponse,
  ApiLoginIdRefreshIdResponse,
  ApiLoginIdResponse,
  ApiPostsResponse,
  ApiSuccessNoContentResponse,
} from '@/utils/api/api-interface.ts'

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
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
      }
    }
    const data: ApiLoginIdResponse = await response.json()
    return data
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
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
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
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
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
      }
    }
    if (response.status === 204) {
      return {
        status: response.status,
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
    const loginId = localStorage.getItem('login-id') || ''
    //make api call only if loginId is present
    if (!loginId) {
      return {
        status: 401,
        message: 'User not logged in',
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
      }
    }
    const data: ApiPostsResponse | ApiErrorResponse = await response.json()
    data.status = response.status
    return data
  }
}
