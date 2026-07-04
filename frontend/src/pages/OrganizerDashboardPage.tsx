import {
  ArrowRight,
  BarChart3,
  CalendarPlus,
  Clock3,
  MessageCircle,
  Star,
  TrendingUp,
  Users,
} from 'lucide-react'
import { Link } from 'react-router-dom'

import {
  EmptyState,
  ErrorState,
  LoadingState,
  OrganizerEventRow,
  PageHeader,
  StatsCard,
  StatusBadge,
} from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api } from '@/lib/api'
import { getActiveOrganizer } from '@/lib/organizer'
import { formatDate, getFillPercentage } from '@/lib/format'

const metricIcons = [CalendarPlus, Users, TrendingUp, Clock3]
const metricTones = ['green', 'yellow', 'dark', 'neutral'] as const

export function OrganizerDashboardPage() {
  const { session } = useAuth()
  const organizerId = getActiveOrganizer(session)?.id
  const canManage = session?.capabilities.manageOrganizer ?? false
  const query = useApiQuery(
    `organizer-dashboard:${organizerId ?? 'none'}`,
    () => api.organizerDashboard(organizerId!),
    Boolean(organizerId),
  )

  if (!organizerId) return <EmptyState title="Organizer belum tersedia" description="Akun ini belum terhubung dengan data organizer." />
  if (query.loading) return <LoadingState label="Memuat dashboard organizer..." />
  if (query.error) return <ErrorState error={query.error} onRetry={query.reload} />
  if (!query.data) return null

  const { organizer, metrics, events, applications } = query.data

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <PageHeader
        eyebrow="Organizer Dashboard"
        title="Kelola event, applicant, dan performa kegiatan relawan."
        description="Lihat event aktif, keterisian kuota, pendaftar terbaru, dan tindakan yang perlu diselesaikan."
        action={canManage ? <Link to="/organizer/create" className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green">Buat event<ArrowRight size={17} /></Link> : undefined}
      />

      <section className="grid gap-4 lg:grid-cols-[1fr_340px]">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {metrics.map((metric, index) => <StatsCard key={metric.id} label={metric.label} value={metric.value} helper={metric.helper} icon={metricIcons[index] ?? TrendingUp} tone={metricTones[index] ?? 'neutral'} />)}
        </div>
        <article className="rounded-lg border bg-deep-green p-5 text-primary-foreground shadow-sm">
          <div className="flex items-center gap-4">
            <span className="flex size-14 items-center justify-center rounded-md bg-secondary font-heading text-xl font-extrabold text-secondary-foreground">{organizer.logoInitial}</span>
            <div><h2 className="font-heading text-xl font-extrabold">{organizer.name}</h2><p className="mt-1 text-sm text-primary-foreground/70">{organizer.type} · {organizer.city}</p></div>
          </div>
          <div className="mt-5 grid gap-3 text-sm font-semibold text-primary-foreground/80">
            <span className="inline-flex items-center gap-2"><Star size={16} className="text-secondary" />Rating {organizer.rating} dari relawan</span>
            <span className="inline-flex items-center gap-2"><MessageCircle size={16} className="text-secondary" />Response time {organizer.responseTime}</span>
          </div>
        </article>
      </section>

      <section className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div className="space-y-6">
          <section className="space-y-4">
            <SectionTitle eyebrow="Managed events" title="Event yang sedang dikelola." description="Pantau status publikasi, jumlah applicant, dan keterisian slot relawan." />
            {events.length ? <div className="grid gap-4">{events.map((event) => <OrganizerEventRow key={event.id} event={event} detailPathPrefix="/organizer/events" />)}</div> : <EmptyState title="Belum ada event" description="Buat event pertama untuk mulai menerima relawan." />}
          </section>

          <section className="space-y-4">
            <div className="flex items-end justify-between gap-4">
              <SectionTitle eyebrow="Applicant preview" title="Pendaftar terbaru." description="Tinjau pendaftar dan status seleksinya." />
              <Link to="/organizer/applicants" className="shrink-0 text-sm font-bold text-primary hover:underline">Lihat semua</Link>
            </div>
            {applications.length ? <div className="overflow-hidden rounded-lg border bg-card shadow-sm">
              <div className="grid grid-cols-[1fr_auto] gap-4 border-b bg-muted px-4 py-3 text-xs font-bold uppercase text-muted-foreground md:grid-cols-[1fr_160px_140px_120px]"><span>Relawan</span><span className="hidden md:block">Role</span><span className="hidden md:block">Submitted</span><span>Status</span></div>
              <div className="divide-y">{applications.slice(0, 6).map((application) => <article key={application.id} className="grid grid-cols-[1fr_auto] gap-4 px-4 py-4 md:grid-cols-[1fr_160px_140px_120px] md:items-center">
                <div className="min-w-0"><p className="font-heading text-base font-extrabold">{application.volunteerProfile?.name ?? 'Relawan'}</p><p className="mt-1 truncate text-sm text-muted-foreground">{application.event?.title ?? 'Event Migunani'}</p></div>
                <span className="hidden text-sm font-semibold text-muted-foreground md:block">{application.role}</span><span className="hidden text-sm font-semibold text-muted-foreground md:block">{formatDate(application.submittedAt)}</span><StatusBadge status={application.status} />
              </article>)}</div>
            </div> : <EmptyState title="Belum ada pendaftar" description="Pendaftar baru akan tampil di sini." />}
          </section>
        </div>

        <aside className="space-y-4">
          <section className="rounded-lg border bg-card p-5 shadow-sm">
            <div className="flex items-start gap-3"><span className="flex size-10 items-center justify-center rounded-md bg-accent text-accent-foreground"><BarChart3 size={19} /></span><div><h2 className="font-heading text-xl font-extrabold">Event performance</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">Keterisian dihitung langsung dari jumlah relawan terdaftar dan kuota event.</p></div></div>
            <div className="mt-5 space-y-4">{events.slice(0, 4).map((event) => { const fill = getFillPercentage(event.registered, event.quota); return <div key={event.id}><div className="flex items-center justify-between gap-3 text-sm font-bold"><span className="truncate">{event.title}</span><span>{fill}%</span></div><div className="mt-2 h-2 overflow-hidden rounded-full bg-muted"><div className="h-full rounded-full bg-primary" style={{ width: `${fill}%` }} /></div></div> })}</div>
          </section>
          {canManage ? <section className="rounded-lg border bg-secondary p-5 text-secondary-foreground shadow-sm"><p className="text-sm font-bold uppercase">Next action</p><h2 className="mt-2 font-heading text-2xl font-extrabold">Publikasikan event berikutnya.</h2><p className="mt-2 text-sm leading-6">Isi kebutuhan kegiatan dan periksa preview sebelum data dikirim.</p><Link to="/organizer/create" className="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-md bg-brand-black px-4 text-sm font-bold text-white transition hover:bg-deep-green">Create event<ArrowRight size={16} /></Link></section> : null}
        </aside>
      </section>
    </div>
  )
}

function SectionTitle({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
  return <div><p className="text-sm font-bold uppercase text-primary">{eyebrow}</p><h2 className="mt-2 font-heading text-3xl font-extrabold">{title}</h2><p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">{description}</p></div>
}
