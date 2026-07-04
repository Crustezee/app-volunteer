import {
  Award,
  BadgeCheck,
  Bell,
  CalendarDays,
  CheckCircle2,
  Clock3,
  HeartHandshake,
  LayoutDashboard,
  ListChecks,
  MapPin,
  TrendingUp,
  X,
} from 'lucide-react'
import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'

import {
  CategoryChip,
  CertificateCard,
  EmptyState,
  ErrorState,
  EventCard,
  LoadingState,
  PageHeader,
  StatsCard,
  StatusBadge,
} from '@/components'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api } from '@/lib/api'
import { formatDate } from '@/lib/format'
import { cn } from '@/lib/utils'
import type { VolunteerApplication, VolunteerEvent, VolunteerProfile } from '@/types/migunani'

type DashboardTab = 'overview' | 'applications' | 'certificates'

const tabs = [
  { id: 'overview', label: 'Overview', icon: LayoutDashboard },
  { id: 'applications', label: 'Applications', icon: ListChecks },
  { id: 'certificates', label: 'Certificates', icon: BadgeCheck },
] as const
const statIcons = [Clock3, CheckCircle2, Award, HeartHandshake]
const statTones = ['green', 'yellow', 'dark', 'neutral'] as const

export function VolunteerDashboardPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const activeTab = getActiveTab(searchParams.get('tab'))
  const query = useApiQuery('volunteer-dashboard', api.volunteerDashboard)
  const [savingEventId, setSavingEventId] = useState<string | null>(null)
  const [downloadingId, setDownloadingId] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)

  async function removeSavedEvent(eventId: string) {
    setSavingEventId(eventId)
    setActionError(null)
    try {
      await api.removeSavedEvent(eventId)
      query.setData((current) =>
        current
          ? { ...current, savedEvents: current.savedEvents.filter((event) => event.id !== eventId) }
          : current,
      )
    } catch (error) {
      setActionError(error instanceof Error ? error.message : 'Event tersimpan gagal dihapus.')
    } finally {
      setSavingEventId(null)
    }
  }

  async function downloadCertificate(certificateId: string) {
    setDownloadingId(certificateId)
    setActionError(null)
    try {
      await api.downloadCertificate(certificateId)
    } catch (error) {
      setActionError(error instanceof Error ? error.message : 'Sertifikat gagal diunduh.')
    } finally {
      setDownloadingId(null)
    }
  }

  async function dismissNotification(notificationId: string) {
    try {
      await api.markNotificationRead(notificationId)
      query.setData((current) => current ? {
        ...current,
        notifications: current.notifications.filter((item) => item.id !== notificationId),
      } : current)
    } catch (error) {
      setActionError(error instanceof Error ? error.message : 'Notifikasi gagal diperbarui.')
    }
  }

  if (query.loading) return <LoadingState label="Memuat dashboard relawan..." />
  if (query.error) return <ErrorState error={query.error} onRetry={query.reload} />
  if (!query.data) return null

  const { profile, stats, applications, certificates, savedEvents, notifications } = query.data
  const activeApplications = applications.filter((item) => item.status !== 'Completed').length

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <PageHeader
        eyebrow="Volunteer Dashboard"
        title={`Halo, ${profile.name}.`}
        description="Pantau aplikasi event, jam kontribusi, sertifikat, dan ringkasan impact untuk portofolio keaktifanmu."
        action={
          <Link to="/volunteer/events" className="inline-flex h-11 items-center justify-center rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green">
            Cari event baru
          </Link>
        }
      />

      {actionError ? <p className="rounded-md border border-destructive/30 bg-card p-3 text-sm font-semibold text-destructive">{actionError}</p> : null}

      {notifications.filter((item) => !item.readAt).length ? <section className="space-y-2">{notifications.filter((item) => !item.readAt).map((notification) => <article key={notification.id} className="flex items-start gap-3 rounded-md border border-primary/30 bg-accent p-4"><Bell size={18} className="mt-0.5 shrink-0 text-primary" /><div className="min-w-0 flex-1"><p className="text-sm font-bold text-foreground">{notification.message}</p><p className="mt-1 text-xs text-muted-foreground">{formatDate(notification.createdAt)}</p></div><button type="button" onClick={() => void dismissNotification(notification.id)} className="inline-flex size-8 shrink-0 items-center justify-center rounded-md hover:bg-card" aria-label="Tandai notifikasi dibaca" title="Tandai dibaca"><X size={15} /></button></article>)}</section> : null}

      <section className="grid gap-4 lg:grid-cols-[1fr_340px]">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {stats.map((stat, index) => (
            <StatsCard key={stat.id} label={stat.label} value={stat.value} helper={stat.delta} icon={statIcons[index] ?? TrendingUp} tone={statTones[index] ?? 'neutral'} />
          ))}
        </div>
        <article className="rounded-lg border bg-deep-green p-5 text-primary-foreground shadow-sm">
          <div className="flex items-center gap-4">
            <span className="flex size-14 items-center justify-center rounded-md bg-secondary font-heading text-xl font-extrabold text-secondary-foreground">{profile.avatarInitials}</span>
            <div>
              <h2 className="font-heading text-xl font-extrabold">{profile.name}</h2>
              <p className="mt-1 text-sm text-primary-foreground/70">{profile.major} · {profile.university}</p>
            </div>
          </div>
          <div className="mt-5 flex flex-wrap gap-2">
            {profile.interests.map((interest) => <CategoryChip key={interest} category={interest} className="border-white/20 bg-white/10 text-primary-foreground" />)}
          </div>
        </article>
      </section>

      <section className="rounded-lg border bg-card p-2 shadow-sm">
        <div className="grid gap-2 sm:grid-cols-3">
          {tabs.map((tab) => (
            <button key={tab.id} type="button" onClick={() => setSearchParams(tab.id === 'overview' ? {} : { tab: tab.id })} className={cn('inline-flex h-11 items-center justify-center gap-2 rounded-md px-4 text-sm font-bold transition', activeTab === tab.id ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground')}>
              <tab.icon size={17} />{tab.label}
            </button>
          ))}
        </div>
      </section>

      {activeTab === 'overview' ? <OverviewTab profile={profile} activeApplications={activeApplications} savedEvents={savedEvents} savingEventId={savingEventId} onRemoveSaved={removeSavedEvent} /> : null}
      {activeTab === 'applications' ? <ApplicationsTab applications={applications} /> : null}
      {activeTab === 'certificates' ? (
        <section className="space-y-4">
          <SectionTitle eyebrow="Certificates" title="Sertifikat volunteer." description="Unduh sertifikat resmi dari kegiatan yang telah selesai." />
          {certificates.length ? (
            <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
              {certificates.map((certificate) => <CertificateCard key={certificate.id} certificate={certificate} downloading={downloadingId === certificate.id} onDownload={downloadCertificate} />)}
            </div>
          ) : <EmptyState title="Belum ada sertifikat" description="Sertifikat akan tersedia setelah kegiatan selesai dan diterbitkan organizer." />}
        </section>
      ) : null}
    </div>
  )
}

function OverviewTab({ profile, activeApplications, savedEvents, savingEventId, onRemoveSaved }: {
  profile: VolunteerProfile
  activeApplications: number
  savedEvents: VolunteerEvent[]
  savingEventId: string | null
  onRemoveSaved: (eventId: string) => void
}) {
  return (
    <div className="grid gap-6 xl:grid-cols-[1fr_360px]">
      <section className="space-y-4">
        <SectionTitle eyebrow="Event tersimpan" title="Aksi yang sedang kamu pantau." description="Bandingkan event tersimpan sebelum memutuskan untuk mendaftar." />
        {savedEvents.length ? (
          <div className="grid gap-5 lg:grid-cols-2">
            {savedEvents.slice(0, 4).map((event) => <EventCard key={event.id} event={event} organizer={event.organizer} saved detailPathPrefix="/volunteer/events" variant="compact" saving={savingEventId === event.id} onSavedChange={onRemoveSaved} />)}
          </div>
        ) : <EmptyState title="Belum ada event tersimpan" description="Simpan event dari halaman eksplorasi agar mudah ditemukan kembali." />}
      </section>
      <aside>
        <article className="rounded-lg border bg-card p-5 shadow-sm">
          <div className="flex items-start gap-3">
            <span className="flex size-10 items-center justify-center rounded-md bg-accent text-accent-foreground"><TrendingUp size={19} /></span>
            <div>
              <h2 className="font-heading text-xl font-extrabold">Impact summary</h2>
              <p className="mt-2 text-sm leading-6 text-muted-foreground">Aktif di {profile.interests.length} kategori dengan {activeApplications} aplikasi berjalan.</p>
            </div>
          </div>
          <div className="mt-5 space-y-3">
            <ImpactRow label="Jam kontribusi" value={`${profile.totalHours ?? 0} jam`} />
            <ImpactRow label="Event selesai" value={`${profile.completedEvents ?? 0} event`} />
            <ImpactRow label="Sertifikat" value={`${profile.certificates ?? 0}`} />
            <ImpactRow label="Kota utama" value={profile.city} />
          </div>
        </article>
      </aside>
    </div>
  )
}

function ApplicationsTab({ applications }: { applications: VolunteerApplication[] }) {
  return (
    <section className="space-y-4">
      <SectionTitle eyebrow="Applications" title="Status pendaftaran event." description="Pantau aplikasi yang terkirim, diterima, ditolak, atau sudah selesai." />
      {applications.length ? <div className="grid gap-4">{applications.map((application) => {
        const event = application.event
        return (
          <article key={application.id} className="grid gap-4 rounded-lg border bg-card p-5 shadow-sm lg:grid-cols-[1fr_auto] lg:items-center">
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2"><StatusBadge status={application.status} />{event ? <CategoryChip category={event.category} /> : null}</div>
              <h2 className="mt-3 font-heading text-xl font-extrabold">{event?.title ?? 'Event Migunani'}</h2>
              <p className="mt-2 text-sm leading-6 text-muted-foreground">Role {application.role} · {event?.organizer?.name ?? 'Organizer'} · submit {formatDate(application.submittedAt)}</p>
              {event ? <div className="mt-3 flex flex-wrap gap-3 text-sm font-semibold text-muted-foreground"><span className="inline-flex items-center gap-2"><CalendarDays size={15} className="text-primary" />{formatDate(event.date)}</span><span className="inline-flex items-center gap-2"><MapPin size={15} className="text-primary" />{event.city}</span></div> : null}
            </div>
            <Link to={event ? `/volunteer/events/${event.slug}` : '/volunteer/events'} className="inline-flex h-10 items-center justify-center rounded-md border bg-card px-4 text-sm font-bold transition hover:bg-muted">Lihat event</Link>
          </article>
        )
      })}</div> : <EmptyState title="Belum ada aplikasi" description="Pilih event dan kirim aplikasi pertamamu." />}
    </section>
  )
}

function SectionTitle({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
  return <div><p className="text-sm font-bold uppercase text-primary">{eyebrow}</p><h2 className="mt-2 font-heading text-3xl font-extrabold">{title}</h2><p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">{description}</p></div>
}

function ImpactRow({ label, value }: { label: string; value: string }) {
  return <div className="flex items-center justify-between gap-4 rounded-md bg-muted p-3"><span className="text-sm font-semibold text-muted-foreground">{label}</span><span className="text-sm font-bold text-foreground">{value}</span></div>
}

function getActiveTab(value: string | null): DashboardTab {
  return value === 'applications' || value === 'certificates' ? value : 'overview'
}
