import { Outlet } from 'react-router-dom'

import { MobileNav } from '@/layouts/MobileNav'
import { SiteHeader } from '@/layouts/SiteHeader'

export function AppLayout() {
  return (
    <div className="min-h-svh bg-background text-foreground">
      <SiteHeader />
      <main className="w-full px-3 py-4 sm:px-4 lg:px-5">
        <Outlet />
      </main>
      <MobileNav area="public" />
    </div>
  )
}
