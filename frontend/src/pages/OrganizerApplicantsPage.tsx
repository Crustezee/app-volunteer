import { Award, CalendarDays, CheckCircle2, LoaderCircle, Search, Users } from 'lucide-react'
import { useDeferredValue, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'

import { CategoryChip, EmptyState, ErrorState, LoadingState, PageHeader, StatsCard, StatusBadge } from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api, ApiError } from '@/lib/api'
import { getActiveOrganizer } from '@/lib/organizer'
import type { ApplicationStatus, VolunteerApplication } from '@/types/migunani'

const statusOptions: ApplicationStatus[] = ['Draft', 'Submitted', 'Accepted', 'Waitlisted', 'Rejected', 'Withdrawn', 'Completed']

export function OrganizerApplicantsPage() {
  const { session } = useAuth()
  const organizer = getActiveOrganizer(session)
  const canManage = session?.capabilities.manageOrganizer ?? false
  const [searchParams] = useSearchParams()
  const focusedEventId = searchParams.get('event') ?? undefined
  const [search, setSearch] = useState('')
  const deferredSearch = useDeferredValue(search.trim())
  const [status, setStatus] = useState<ApplicationStatus | ''>('')
  const [updatingId, setUpdatingId] = useState<string | null>(null)
  const [issueTarget, setIssueTarget] = useState<VolunteerApplication | null>(null)
  const [hours, setHours] = useState(1)
  const [issuedAt, setIssuedAt] = useState(new Date().toISOString().slice(0, 10))
  const [issuing, setIssuing] = useState(false)
  const [actionError, setActionError] = useState<string | null>(null)

  const query = useApiQuery(
    `organizer-applicants:${organizer?.id}:${focusedEventId}:${deferredSearch}:${status}`,
    () => api.organizerApplications(organizer!.id, {
      q: deferredSearch || undefined,
      eventId: focusedEventId,
      status: status || undefined,
      perPage: 50,
    }),
    Boolean(organizer),
  )

  async function changeStatus(applicationId: string, nextStatus: ApplicationStatus) {
    if (!organizer) return
    setUpdatingId(applicationId)
    setActionError(null)
    try {
      const updated = await api.updateApplicationStatus(organizer.id, applicationId, nextStatus)
      query.setData((current) => current ? { ...current, data: current.data.map((item) => item.id === updated.id ? updated : item) } : current)
    } catch (error) {
      setActionError(error instanceof ApiError ? error.first() : error instanceof Error ? error.message : 'Status applicant gagal diperbarui.')
    } finally {
      setUpdatingId(null)
    }
  }

  function openIssue(application: VolunteerApplication) {
    setIssueTarget(application)
    setHours(Math.max(application.event?.durationHours ?? 1, 1))
    setIssuedAt(new Date().toISOString().slice(0, 10))
    setActionError(null)
  }

  async function issueCertificate() {
    if (!organizer || !issueTarget) return
    const latest = latestCertificate(issueTarget)
    setIssuing(true)
    setActionError(null)
    try {
      const certificate = await api.issueCertificate(organizer.id, issueTarget.id, {
        hours,
        issuedAt,
        supersedesCertificateId: latest?.status === 'Revoked' ? latest.id : undefined,
      })
      query.setData((current) => current ? {
        ...current,
        data: current.data.map((item) => item.id === issueTarget.id
          ? { ...item, certificates: [...(item.certificates ?? []), certificate] }
          : item),
      } : current)
      setIssueTarget(null)
    } catch (error) {
      setActionError(error instanceof ApiError ? error.first() : error instanceof Error ? error.message : 'Sertifikat gagal diterbitkan.')
    } finally {
      setIssuing(false)
    }
  }

  if (!organizer) return <EmptyState title="Organizer belum tersedia" description="Akun ini belum terhubung dengan data organizer." />
  if (query.loading) return <LoadingState label="Memuat applicant..." />
  if (query.error) return <ErrorState error={query.error} onRetry={query.reload} />
  if (!query.data) return null

  const rows = query.data.data
  const acceptedCount = rows.filter((item) => item.status === 'Accepted').length
  const submittedCount = rows.filter((item) => item.status === 'Submitted').length
  const focusedEvent = rows.find((item) => item.eventId === focusedEventId)?.event

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <PageHeader eyebrow="Organizer Applicants" title="Kelola daftar applicant relawan." description={focusedEvent ? `Menampilkan applicant untuk ${focusedEvent.title}.` : 'Cari pendaftar, kelola status seleksi, dan terbitkan sertifikat setelah kegiatan selesai.'} action={canManage ? <Link to="/organizer/create" className="inline-flex h-11 items-center justify-center rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground hover:bg-deep-green">Buat event baru</Link> : undefined} />
      {actionError ? <p className="rounded-md border border-destructive/30 bg-card p-3 text-sm font-semibold text-destructive">{actionError}</p> : null}

      <section className="grid gap-4 md:grid-cols-3">
        <StatsCard label="Total applicant" value={query.data.meta.total.toString()} helper="sesuai filter saat ini" icon={Users} tone="green" />
        <StatsCard label="Accepted" value={acceptedCount.toString()} helper="pada halaman ini" icon={CheckCircle2} tone="yellow" />
        <StatsCard label="Submitted" value={submittedCount.toString()} helper="perlu review" icon={CalendarDays} tone="dark" />
      </section>

      <section className="grid gap-3 rounded-lg border bg-card p-4 shadow-sm lg:grid-cols-[1fr_260px]">
        <label className="relative"><Search size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" /><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari nama, role, atau event" className="h-11 w-full rounded-md border bg-background pl-10 pr-3 text-sm font-semibold outline-none focus:border-primary" /></label>
        <select value={status} onChange={(event) => setStatus(event.target.value as ApplicationStatus | '')} className="h-11 rounded-md border bg-background px-3 text-sm font-bold outline-none focus:border-primary"><option value="">Semua status</option>{statusOptions.map((item) => <option key={item}>{item}</option>)}</select>
      </section>

      {rows.length ? <section className="overflow-hidden rounded-lg border bg-card shadow-sm">
        <div className="hidden grid-cols-[1fr_1fr_150px_150px_150px] gap-4 border-b bg-muted px-4 py-3 text-xs font-bold uppercase text-muted-foreground lg:grid"><span>Applicant</span><span>Event</span><span>Role</span><span>Status</span><span>Sertifikat</span></div>
        <div className="divide-y">{rows.map((application) => {
          const profile = application.volunteerProfile
          const event = application.event
          const nextStatuses = canManage ? getNextStatuses(application.status) : []
          const activeCertificate = application.certificates?.find((item) => item.status === 'Issued')
          const latest = latestCertificate(application)

          return <article key={application.id} className="grid gap-4 p-4 lg:grid-cols-[1fr_1fr_150px_150px_150px] lg:items-center">
            <div className="min-w-0"><p className="font-heading font-extrabold">{profile?.name ?? 'Relawan'}</p><p className="mt-1 truncate text-sm text-muted-foreground">{profile ? `${profile.major} · ${profile.university}` : 'Profil relawan'}</p></div>
            <div className="min-w-0"><Link to={event ? `/organizer/events/${event.slug}` : '/organizer/events'} className="block truncate font-bold hover:text-primary">{event?.title ?? 'Event Migunani'}</Link>{event ? <div className="mt-1"><CategoryChip category={event.category} /></div> : null}</div>
            <span className="text-sm font-semibold text-muted-foreground">{application.role}</span>
            <div>{updatingId === application.id ? <LoaderCircle size={18} className="animate-spin text-primary" /> : nextStatuses.length ? <select aria-label={'Ubah status ' + (profile?.name ?? 'applicant')} value={application.status} onChange={(event) => void changeStatus(application.id, event.target.value as ApplicationStatus)} className="h-9 min-w-32 rounded-md border bg-background px-2 text-sm font-bold"><option value={application.status}>{application.status}</option>{nextStatuses.map((item) => <option key={item}>{item}</option>)}</select> : <StatusBadge status={application.status} />}</div>
            <div>{activeCertificate ? <Link to={`/verify/${activeCertificate.credentialId}`} className="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-bold text-primary hover:bg-accent"><Award size={15} />Issued</Link> : canManage && application.status === 'Completed' ? <button type="button" onClick={() => openIssue(application)} className="inline-flex h-9 items-center gap-2 rounded-md bg-secondary px-3 text-xs font-bold text-secondary-foreground"><Award size={15} />{latest ? 'Issue revisi' : 'Terbitkan'}</button> : <span className="text-xs font-semibold text-muted-foreground">Belum tersedia</span>}</div>
          </article>
        })}</div>
      </section> : <EmptyState title="Applicant tidak ditemukan" description="Ubah pencarian atau filter status untuk melihat data lain." />}

      {issueTarget ? <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4" role="dialog" aria-modal="true" aria-labelledby="issue-title">
        <section className="w-full max-w-lg rounded-lg border bg-card p-6 shadow-lg">
          <div className="flex items-start gap-3"><span className="flex size-10 items-center justify-center rounded-md bg-accent text-primary"><Award size={20} /></span><div><h2 id="issue-title" className="font-heading text-2xl font-extrabold">{latestCertificate(issueTarget) ? 'Terbitkan revisi' : 'Terbitkan sertifikat'}</h2><p className="mt-1 text-sm text-muted-foreground">{issueTarget.volunteerProfile?.name} · {issueTarget.event?.title}</p></div></div>
          <div className="mt-5 grid gap-4 sm:grid-cols-2"><label><span className="text-sm font-bold">Jam kontribusi</span><input autoFocus type="number" min="1" max="1000" value={hours} onChange={(event) => setHours(Number(event.target.value))} className="mt-2 h-11 w-full rounded-md border bg-background px-3 text-sm font-bold outline-none focus:border-primary" /></label><label><span className="text-sm font-bold">Tanggal terbit</span><input type="date" value={issuedAt} onChange={(event) => setIssuedAt(event.target.value)} className="mt-2 h-11 w-full rounded-md border bg-background px-3 text-sm font-bold outline-none focus:border-primary" /></label></div>
          <p className="mt-4 rounded-md bg-muted p-3 text-sm leading-6 text-muted-foreground">Nama volunteer, event, organizer, role, dan tanggal event akan disimpan sebagai snapshot immutable.</p>
          <div className="mt-5 flex justify-end gap-2"><button type="button" onClick={() => setIssueTarget(null)} disabled={issuing} className="h-10 rounded-md border px-4 text-sm font-bold hover:bg-muted">Batal</button><button type="button" onClick={() => void issueCertificate()} disabled={issuing || hours < 1 || !issuedAt} className="inline-flex h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-bold text-primary-foreground disabled:opacity-50">{issuing ? <LoaderCircle size={16} className="animate-spin" /> : <Award size={16} />}Terbitkan</button></div>
        </section>
      </div> : null}
    </div>
  )
}

function latestCertificate(application: VolunteerApplication) {
  return [...(application.certificates ?? [])].sort((a, b) => b.revisionNumber - a.revisionNumber)[0]
}

function getNextStatuses(status: ApplicationStatus): ApplicationStatus[] {
  if (status === 'Submitted') return ['Accepted', 'Waitlisted', 'Rejected']
  if (status === 'Waitlisted') return ['Accepted', 'Rejected']
  if (status === 'Accepted') return ['Completed']
  return []
}
