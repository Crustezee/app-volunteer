import { useCallback, useEffect, useMemo, useState } from 'react'

import { AuthContext } from '@/context/AuthContext'
import { api, ApiError } from '@/lib/api'
import type { AuthSession } from '@/types/migunani'

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<AuthSession | null>(null)
  const [loading, setLoading] = useState(true)

  const refresh = useCallback(async () => {
    try {
      setSession(await api.me())
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 401) throw error
      setSession(null)
    }
  }, [])

  useEffect(() => {
    void api.me()
      .then(setSession)
      .catch(() => setSession(null))
      .finally(() => setLoading(false))
  }, [])

  const value = useMemo(
    () => ({
      session,
      loading,
      login: async (email: string, password: string, accountType: 'volunteer' | 'organizer') => {
        const nextSession = await api.login(email, password, accountType)
        setSession(nextSession)
        return nextSession
      },
      logout: async () => {
        await api.logout()
        setSession(null)
      },
      refresh,
    }),
    [loading, refresh, session],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
