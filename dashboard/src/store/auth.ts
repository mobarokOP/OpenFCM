import { create } from 'zustand'
import { TOKEN_KEY } from '@/api/client'
import type { User } from '@/types'

const USER_KEY = 'op-user'

interface AuthState {
  token: string | null
  user: User | null
  isAuthenticated: boolean
  setAuth: (token: string, user: User) => void
  setUser: (user: User) => void
  clear: () => void
}

function storedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as User) : null
  } catch {
    return null
  }
}

export const useAuth = create<AuthState>((set) => {
  const token = localStorage.getItem(TOKEN_KEY)
  return {
    token,
    user: storedUser(),
    isAuthenticated: !!token,
    setAuth: (token, user) => {
      localStorage.setItem(TOKEN_KEY, token)
      localStorage.setItem(USER_KEY, JSON.stringify(user))
      set({ token, user, isAuthenticated: true })
    },
    setUser: (user) => {
      localStorage.setItem(USER_KEY, JSON.stringify(user))
      set({ user })
    },
    clear: () => {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
      set({ token: null, user: null, isAuthenticated: false })
    },
  }
})
