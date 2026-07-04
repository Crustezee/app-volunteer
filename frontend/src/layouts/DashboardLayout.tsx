import { Outlet } from 'react-router-dom'

import { DashboardSidebar } from '@/layouts/DashboardSidebar'
import { MobileNav } from '@/layouts/MobileNav'

type DashboardLayoutProps = {
  area: 'volunteer' | 'organizer'
}

export function DashboardLayout({ area }: DashboardLayoutProps) {
  return (
    <div className="min-h-svh bg-background text-foreground">
      <div className="flex w-full gap-4 px-3 py-3 sm:px-4 lg:gap-5 lg:px-5">
        <DashboardSidebar area={area} />
        <main className="min-w-0 flex-1 pb-20 lg:pb-0">
          <Outlet />
        </main>
      </div>
      <MobileNav area={area} />
    </div>
  )
}
