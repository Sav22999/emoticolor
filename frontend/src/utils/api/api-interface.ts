export interface ApiLoginIdResponse extends ApiSuccessResponse {
  data?: {
    'login-id': string
  }
}

export interface ApiLoginIdRefreshIdResponse extends ApiSuccessResponse {
  data?: {
    'login-id': string
    'token-id': string
  }
}

export interface ApiSuccessNoContentResponse {
  status: number
}

export interface ApiSuccessResponse {
  status: number
  message: string
}

export interface ApiErrorResponse {
  status: number
  message: string
}
