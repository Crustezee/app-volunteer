import { createContext, useContext } from 'react'

import type { AuthSession } from '@/types/migunani'

export type AuthContextValue = {
  session: AuthSession | null
  loading: boolean
  login: (email: string, password: string, accountType: 'volunteer' | 'organizer') => Promise<AuthSession>
  logout: () => Promise<void>
  refresh: () => Promise<void>
}

export const AuthContext = createContext<AuthContextValue | null>(null)

export function useAuth() {
  const context = useContext(AuthContext)

  if (!context) {
    throw new Error('useAuth must be used inside AuthProvider')
  }

  return context
}
