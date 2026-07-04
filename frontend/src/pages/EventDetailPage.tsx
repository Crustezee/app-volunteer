import {
  ArrowLeft,
  ArrowRight,
  BadgeCheck,
  CheckCircle2,
  MapPin,
  Pencil,
  ShieldCheck,
  Star,
} from 'lucide-react'
import { Link, useParams } from 'react-router-dom'

import {
  ErrorState,
  EventCard,
  EventDetailPanel,
  LoadingState,
  PageHeader,
  StatusBadge,
} from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'

type EventDetailPageProps = { viewer?: 'public' | 'volunteer' | 'organizer' }

export function EventDetailPage({ viewer = 'public' }: EventDetailPageProps) {
  const { slug = '' } = useParams()
  const { session } = useAuth()
  const canManageOrganizer = session?.capabilities.manageOrganizer ?? false
  const query = useApiQuery(`event:${slug}`, () => api.event(slug), Boolean(slug))

  if (query.loading && !query.data) return <LoadingState label="Memuat detail event..." />
  if (query.error || !query.data) return <ErrorState error={query.error ?? new Error('Event tidak ditemukan.')} onRetry={query.reload} />

  const event = query.data
  const organizer = event.organizer
  const relatedEvents = event.relatedEvents ?? []
  const isVolunteerView = viewer === 'volunteer'
  const isOrganizerView = viewer === 'organizer'
  const applyHref = isVolunteerView
    ? `/volunteer/apply/${event.id}`
    : `/login?next=${encodeURIComponent(`/volunteer/apply/${event.id}`)}`
  const backHref = isVolunteerView ? '/volunteer/events' : isOrganizerView ? '/organizer/events' : '/events'
  const relatedPrefix = isVolunteerView ? '/volunteer/events' : isOrganizerView ? '/organizer/events' : '/events'

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <Link to={backHref} className="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground transition hover:text-primary">
        <ArrowLeft size={16} /> Kembali ke daftar event
      </Link>

      <section className="overflow-hidden rounded-lg border bg-card shadow-sm">
        <div className="relative h-[320px] bg-muted md:h-[420px]">
          <img src={event.image} alt={event.title} className="size-full object-cover" />
          <div className="absolute inset-0 bg-black/45" />
          <div className="absolute inset-x-0 bottom-0 p-5 text-white sm:p-8">
            <div className="flex flex-wrap gap-2">
              <StatusBadge status={event.status} className="bg-card text-foreground" />
              <span className="rounded-full border border-white/20 bg-black/30 px-3 py-1 text-xs font-bold">{event.mode}</span>
            </div>
            <h1 className="mt-4 max-w-4xl font-heading text-3xl font-extrabold leading-tight sm:text-5xl">{event.title}</h1>
            <p className="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-base">{event.shortDescription}</p>
          </div>
        </div>
      </section>

      <section className="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div className="space-y-6">
          <PageHeader
            eyebrow="Event Detail"
            title="Detail kegiatan"
            description={event.description}
            action={<EventAction eventId={event.id} status={event.myApplication?.status} eventStatus={event.status} applyHref={applyHref} isVolunteerView={isVolunteerView} isOrganizerView={isOrganizerView} canManageOrganizer={canManageOrganizer} />}
          />

          <div className="grid gap-4 md:grid-cols-3">
            <InfoCard icon={<CheckCircle2 size={20} />} label="Benefit" items={event.benefits} />
            <InfoCard icon={<Star size={20} />} label="Skill dibutuhkan" items={event.skills} />
            <InfoCard icon={<BadgeCheck size={20} />} label="Role relawan" items={event.roles} />
          </div>

          <section className="rounded-lg border bg-card p-6 shadow-sm">
            <div className="flex flex-col justify-between gap-4 md:flex-row md:items-start">
              <div>
                <p className="text-sm font-bold uppercase text-primary">Organizer</p>
                <h2 className="mt-2 font-heading text-2xl font-extrabold">{organizer?.name ?? 'Organizer komunitas'}</h2>
                <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                  {organizer?.type ?? 'Komunitas'} dari {organizer?.city ?? event.city}. Response time {organizer?.responseTime ?? 'belum tersedia'}.
                </p>
              </div>
              <div className="grid min-w-56 gap-2 text-sm font-semibold text-muted-foreground">
                <span className="inline-flex items-center gap-2"><ShieldCheck size={16} className="text-primary" />{organizer?.verified ? 'Terverifikasi' : 'Belum terverifikasi'}</span>
                <span className="inline-flex items-center gap-2"><Star size={16} className="text-primary" />Rating {organizer?.rating ?? '-'}</span>
                <span className="inline-flex items-center gap-2"><MapPin size={16} className="text-primary" />{organizer?.city ?? event.city}</span>
              </div>
            </div>
          </section>
        </div>

        <div className="space-y-4 lg:sticky lg:top-24 lg:self-start">
          <EventDetailPanel event={event} organizer={organizer} />
          <EventAction eventId={event.id} status={event.myApplication?.status} eventStatus={event.status} applyHref={applyHref} isVolunteerView={isVolunteerView} isOrganizerView={isOrganizerView} canManageOrganizer={canManageOrganizer} fullWidth />
        </div>
      </section>

      {relatedEvents.length ? (
        <section className="space-y-4">
          <div><p className="text-sm font-bold uppercase text-primary">Event serupa</p><h2 className="mt-2 font-heading text-3xl font-extrabold">Aksi lain di kategori {event.category}.</h2></div>
          <div className="grid gap-5 lg:grid-cols-3">
            {relatedEvents.map((related) => <EventCard key={related.id} event={related} organizer={related.organizer} saved={related.isSaved} detailPathPrefix={relatedPrefix} variant="compact" />)}
          </div>
        </section>
      ) : null}
    </div>
  )
}

function EventAction({ applyHref, isVolunteerView, isOrganizerView, canManageOrganizer, eventId, status, eventStatus, fullWidth = false }: {
  applyHref: string
  isVolunteerView: boolean
  isOrganizerView: boolean
  canManageOrganizer: boolean
  eventId: string
  status?: string
  eventStatus: string
  fullWidth?: boolean
}) {
  if (isOrganizerView) {
    return <div className={cn('flex flex-wrap gap-2', fullWidth && 'w-full flex-col')}>
      {canManageOrganizer ? <Link to={'/organizer/events/' + eventId + '/edit'} className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground hover:bg-deep-green"><Pencil size={17} />Edit event</Link> : null}
      <Link to={'/organizer/applicants?event=' + eventId} className="inline-flex h-11 items-center justify-center gap-2 rounded-md border bg-card px-5 text-sm font-bold hover:bg-muted">Kelola applicant <ArrowRight size={17} /></Link>
    </div>
  }
  if (status) {
    return <Link to="/volunteer/dashboard?tab=applications" className="inline-flex h-11 items-center justify-center gap-2 rounded-md border bg-accent px-5 text-sm font-bold">Status aplikasi: {status} <ArrowRight size={17} /></Link>
  }
  if (['Closed', 'Cancelled', 'Completed'].includes(eventStatus)) {
    return <span className="inline-flex h-11 items-center justify-center rounded-md border bg-muted px-5 text-sm font-bold text-muted-foreground">Pendaftaran ditutup</span>
  }
  return <Link to={applyHref} className={`inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground hover:bg-deep-green ${fullWidth ? 'w-full' : ''}`}>{isVolunteerView ? 'Daftar sebagai relawan' : 'Masuk untuk mendaftar'} <ArrowRight size={17} /></Link>
}

function InfoCard({ icon, label, items }: { icon: React.ReactNode; label: string; items: string[] }) {
  return (
    <article className="rounded-lg border bg-card p-5 shadow-sm">
      <div className="flex items-center gap-3"><span className="flex size-10 items-center justify-center rounded-md bg-accent text-accent-foreground">{icon}</span><h2 className="font-heading text-lg font-extrabold">{label}</h2></div>
      <ul className="mt-4 space-y-2">{items.map((item) => <li key={item} className="flex gap-2 text-sm leading-6 text-muted-foreground"><CheckCircle2 size={16} className="mt-1 shrink-0 text-primary" /><span>{item}</span></li>)}</ul>
    </article>
  )
}
