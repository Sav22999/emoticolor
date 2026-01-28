export default class usefulFunctions {
  /**
   * Check if the value contains only allowed characters
   * @param value - The input string to check
   * @param charsAllowed - A string of allowed characters (es. "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789. "), consider space as character
   * @param charsDisallowed - A string of disallowed characters [optional]
   * @returns boolean - true if all characters are allowed, false otherwise
   */
  static checkAllowedChars(
    value: string,
    charsAllowed?: string,
    charsDisallowed?: string,
  ): boolean {
    if (charsDisallowed) {
      for (const char of value) {
        if (charsDisallowed.includes(char)) return false
      }
    }
    if (charsAllowed) {
      for (const char of value) {
        if (!charsAllowed.includes(char)) return false
      }
    }

    return true
  }

  /**
   * Remove disallowed characters from the input string
   * @param value - The input string to process
   * @param charsAllowed - A string of allowed characters (es. "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.")
   * @param charsDisallowed - A string of disallowed characters [optional]
   * @returns string - The processed string with only allowed characters
   */
  static removeDisallowedChars(
    value: string,
    charsAllowed?: string,
    charsDisallowed?: string,
  ): string {
    let result = value
    if (charsDisallowed) {
      const escapedDisallowed = charsDisallowed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      const regexDisallowed = new RegExp(`[${escapedDisallowed}]`, 'g')
      result = result.replace(regexDisallowed, '')
    }
    if (charsAllowed) {
      const escapedAllowed = charsAllowed.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
      const regexAllowed = new RegExp(`[^${escapedAllowed}]`, 'g')
      result = result.replace(regexAllowed, '')
    }

    return result
  }

  /**
   * Check if the value length is within the specified range
   * @param value - The input string to check
   * @param minLength - Minimum length
   * @param maxLength - Maximum length
   * @returns boolean - true if length is within range, false otherwise
   */
  static checkLength(value: string, minLength?: number, maxLength?: number): boolean {
    if (minLength !== undefined && value.length < minLength) {
      return false
    }
    if (maxLength !== undefined && value.length > maxLength) {
      return false
    }
    return true
  }

  /**
   * Validate an email address format
   * @param email - The email string to validate
   * @returns boolean - true if the email format is valid, false otherwise
   */
  static checkEmailValidity(email: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  /**
   * Check if the value is a valid number
   * @param value - The input string to check
   * @returns boolean - true if the value is a valid number, false otherwise
   */
  static checkNumberValidity(value: string): boolean {
    return !isNaN(Number(value))
  }

  /**
   * Validate a URL format
   * @param url - The URL string to validate
   * @returns boolean - true if the URL format is valid, false otherwise
   */
  static checkURLValidity(url: string): boolean {
    try {
      new URL(url)
      return true
    } catch {
      return false
    }
  }

  /**
   * Validate a username format (min-length: 3, max-length: 16, allowed chars: "a-z, 0-9, .", not start or end with ".")
   * @param username - The username string to validate
   * @returns boolean - true if the username format is valid, false otherwise
   */
  static checkUsernameValidity(username: string): boolean {
    const usernameRegex = /^(?!\.)([a-z0-9.]{3,16})(?<!\.)$/
    return usernameRegex.test(username)
  }

  /**
   * Validate a password format (min-length: 10, max-length: 256, at least one uppercase, one lowercase, one digit)
   * @param password - The password string to validate
   * @returns boolean - true if the password format is valid, false otherwise
   */
  static checkPasswordValidity(password: string): boolean {
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{10,256}$/
    return passwordRegex.test(password)
  }

  static generateUniqueComponentId(length: number = 16): string {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    let result = ''
    for (let i = 0; i < length; i++) {
      result += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return result
  }

  /**
   * Save an item to local storage
   * @param key - The key of the item to save
   * @param value - The value of the item to save
   * @return boolean - true if the item was successfully saved, false otherwise (failed)
   */
  static saveToLocalStorage(key: string, value: string): boolean {
    localStorage.setItem(key, value)
    return localStorage.getItem(key) !== null
  }

  /**
   * Edit an item in local storage
   * @param key - The key of the item to edit
   * @param value - The new value of the item
   * @return boolean - true if the item was successfully edited, false otherwise (if the item does not exist or edit failed)
   */
  static editToLocalStorage(key: string, value: string): boolean {
    if (localStorage.getItem(key) === null) {
      return false
    }
    localStorage.setItem(key, value)
    return localStorage.getItem(key) !== null
  }

  /**
   * Load an item from local storage
   * @param key - The key of the item to load
   * @returns string | null - The value of the item, or null if not found
   */
  static loadFromLocalStorage(key: string): string | null {
    const item = localStorage.getItem(key)
    return item ? item : null
  }

  /**
   * Remove an item from local storage
   * @param key - The key of the item to remove
   * @returns boolean - true if the item was successfully removed, false otherwise
   */
  static removeFromLocalStorage(key: string): boolean {
    localStorage.removeItem(key)
    return localStorage.getItem(key) === null
  }
}
