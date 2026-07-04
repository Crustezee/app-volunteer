import { Navigate, useLocation } from 'react-router-dom'

import { LoadingState } from '@/components'
import { useAuth } from '@/context/AuthContext'

export function RequireAuth({
  capability,
  children,
}: {
  capability: 'volunteer' | 'organizer' | 'manageOrganizer'
  children: React.ReactNode
}) {
  const { session, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return <LoadingState label="Memeriksa sesi..." />
  }

  if (!session) {
    const next = `${location.pathname}${location.search}`
    return <Navigate to={`/login?next=${encodeURIComponent(next)}`} replace />
  }

  if (!session.capabilities[capability]) {
    const fallback = session.capabilities.organizer
      ? '/organizer'
      : session.capabilities.volunteer
        ? '/volunteer/dashboard'
        : '/'
    return <Navigate to={fallback} replace />
  }

  return children
}
