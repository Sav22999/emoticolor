export default class apiService {
  API_BASE_URL = 'https://www.saveriomorelli.com/api/emoticolor' // the base URL of the API
  API_VERSION = 'v1' // the version of the API

  /**
   * Get the full URL for a given endpoint
   * @param endpoint - The API endpoint (e.g. "account/check-auth") | automatically adds slashes at the start and end
   * @returns The full URL as a string
   */
  private getFullUrl(endpoint: string): string {
    return `${this.API_BASE_URL}/${this.API_VERSION}/${endpoint}/`
  }

  /*static async checkLoginIdValid(loginId: string): Promise<ApiLoginIdResponse | ApiErrorResponse> {
    /!*const response = await fetch(`${new apiService().getFullUrl('account/check-auth')}`, {
      body: JSON.stringify({ loginId }),
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error(`API request failed with status ${response.status}`)
    }

    const data: ApiLoginIdResponse = await response.json()
    return data*!/
  }*/
}
