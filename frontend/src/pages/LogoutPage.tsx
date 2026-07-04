import { useEffect } from 'react'
import { useNavigate } from 'react-router-dom'

import { LoadingState } from '@/components'
import { useAuth } from '@/context/AuthContext'

export function LogoutPage() {
  const { logout } = useAuth()
  const navigate = useNavigate()

  useEffect(() => {
    logout()
      .catch(() => undefined)
      .finally(() => navigate('/', { replace: true }))
  }, [logout, navigate])

  return <LoadingState label="Mengakhiri sesi..." />
}
