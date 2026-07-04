import {
  BadgeCheck,
  BarChart3,
  CalendarPlus,
  Home,
  LayoutDashboard,
  ListChecks,
  LogOut,
} from 'lucide-react'
import { Link, useLocation } from 'react-router-dom'

import { Logo } from '@/layouts/Logo'
import { cn } from '@/lib/utils'

const volunteerItems = [
  { label: 'My Dashboard', to: '/volunteer/dashboard', icon: LayoutDashboard },
  {
    label: 'Applications',
    to: '/volunteer/dashboard?tab=applications',
    icon: ListChecks,
  },
  {
    label: 'Certificates',
    to: '/volunteer/dashboard?tab=certificates',
    icon: BadgeCheck,
  },
  { label: 'Explore Events', to: '/volunteer/events', icon: Home },
]

const organizerItems = [
  { label: 'Organizer', to: '/organizer', icon: BarChart3 },
  { label: 'Applicants', to: '/organizer/applicants', icon: ListChecks },
  { label: 'Certificates', to: '/organizer/certificates', icon: BadgeCheck },
  { label: 'Explore Events', to: '/organizer/events', icon: Home },
  { label: 'Create Event', to: '/organizer/create', icon: CalendarPlus },
]

type DashboardSidebarProps = {
  area: 'volunteer' | 'organizer'
}

export function DashboardSidebar({ area }: DashboardSidebarProps) {
  const items = area === 'volunteer' ? volunteerItems : organizerItems

  return (
    <aside className="sticky top-4 hidden h-[calc(100svh-2rem)] w-72 shrink-0 rounded-lg border bg-card p-4 shadow-sm lg:flex lg:flex-col">
      <Logo to={area === 'volunteer' ? '/volunteer/dashboard' : '/organizer'} />

      <div className="mt-8 space-y-6">
        <SidebarGroup label={area === 'volunteer' ? 'Volunteer' : 'Organizer'} items={items} />
      </div>
      <Link
        to="/logout"
        className="mt-auto flex items-center gap-3 rounded-md border bg-card px-3 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-accent hover:text-accent-foreground"
      >
        <LogOut size={17} />
        Logout
      </Link>
    </aside>
  )
}

type SidebarGroupProps = {
  label: string
  items: typeof volunteerItems
}

function SidebarGroup({ label, items }: SidebarGroupProps) {
  const location = useLocation()

  return (
    <div>
      <p className="px-3 text-xs font-bold uppercase text-muted-foreground">{label}</p>
      <nav className="mt-2 space-y-1" aria-label={`${label} navigation`}>
        {items.map((item) => (
          <Link
            key={item.to}
            to={item.to}
            className={cn(
              'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-semibold text-muted-foreground transition hover:bg-accent hover:text-accent-foreground',
              isSidebarItemActive(item.to, location.pathname, location.search) &&
                'bg-accent text-accent-foreground',
            )}
          >
            <item.icon size={17} />
            {item.label}
          </Link>
        ))}
      </nav>
    </div>
  )
}

function isSidebarItemActive(to: string, pathname: string, search: string) {
  const [toPath, toSearch = ''] = to.split('?')

  if (to === '/') {
    return pathname === '/'
  }

  if (toSearch) {
    return pathname === toPath && search === `?${toSearch}`
  }

  return pathname === toPath && search === ''
}
