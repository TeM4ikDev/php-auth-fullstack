const TOKEN_KEY = 'token'

export function getTokenFromLocalStorage(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setTokenToLocalStorage(token: string): void {
  try {
    localStorage.setItem(TOKEN_KEY, token)
  } catch {
  }
}

export function removeTokenFromLocalStorage(): void {
  try {
    localStorage.removeItem(TOKEN_KEY)
  } catch {
  }
}
