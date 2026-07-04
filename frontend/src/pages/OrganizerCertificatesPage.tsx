import { Award, CheckCircle2, ExternalLink, LoaderCircle, Search, ShieldX } from 'lucide-react'
import { useDeferredValue, useState } from 'react'
import { Link } from 'react-router-dom'

import { EmptyState, ErrorState, LoadingState, PageHeader, StatsCard, StatusBadge } from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api, ApiError } from '@/lib/api'
import { formatDate } from '@/lib/format'
import { getActiveOrganizer } from '@/lib/organizer'
import type { Certificate, CertificateStatus } from '@/types/migunani'

export function OrganizerCertificatesPage() {
  const { session } = useAuth()
  const organizer = getActiveOrganizer(session)
  const canManage = session?.capabilities.manageOrganizer ?? false
  const [search, setSearch] = useState('')
  const deferredSearch = useDeferredValue(search.trim())
  const [status, setStatus] = useState<CertificateStatus | ''>('')
  const [eventId, setEventId] = useState('')
  const [revokeTarget, setRevokeTarget] = useState<Certificate | null>(null)
  const [reason, setReason] = useState('')
  const [revoking, setRevoking] = useState(false)
  const [actionError, setActionError] = useState<string | null>(null)

  const eventsQuery = useApiQuery(
    `certificate-events:${organizer?.id}`,
    () => api.organizerEvents(organizer!.id, { perPage: 50 }),
    Boolean(organizer),
  )
  const query = useApiQuery(
    `organizer-certificates:${organizer?.id}:${deferredSearch}:${status}:${eventId}`,
    () => api.organizerCertificates(organizer!.id, {
      q: deferredSearch || undefined,
      status: status || undefined,
      eventId: eventId || undefined,
      perPage: 50,
    }),
    Boolean(organizer),
  )

  async function revoke() {
    if (!organizer || !revokeTarget || reason.trim().length < 10) return
    setRevoking(true)
    setActionError(null)
    try {
      const updated = await api.revokeCertificate(organizer.id, revokeTarget.id, reason.trim())
      query.setData((current) => current ? { ...current, data: current.data.map((item) => item.id === updated.id ? updated : item) } : current)
      setRevokeTarget(null)
      setReason('')
    } catch (error) {
      setActionError(error instanceof ApiError ? error.first() : error instanceof Error ? error.message : 'Sertifikat gagal dicabut.')
    } finally {
      setRevoking(false)
    }
  }

  if (!organizer) return <EmptyState title="Organizer belum tersedia" description="Akun ini belum terhubung dengan organizer." />
  if (query.loading || eventsQuery.loading) return <LoadingState label="Memuat sertifikat organizer..." />
  if (query.error) return <ErrorState error={query.error} onRetry={query.reload} />
  if (eventsQuery.error) return <ErrorState error={eventsQuery.error} onRetry={eventsQuery.reload} />
  if (!query.data) return null

  const issued = query.data.data.filter((item) => item.status === 'Issued').length
  const revoked = query.data.data.filter((item) => item.status === 'Revoked').length

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <PageHeader eyebrow="Organizer Certificates" title="Kelola sertifikat volunteer." description="Cari credential, filter per event, cabut sertifikat yang salah, dan pantau riwayat revisi." />
      {actionError ? <p className="rounded-md border border-destructive/30 bg-card p-3 text-sm font-semibold text-destructive">{actionError}</p> : null}

      <section className="grid gap-4 md:grid-cols-3">
        <StatsCard label="Total sertifikat" value={query.data.meta.total.toString()} helper="sesuai filter" icon={Award} tone="green" />
        <StatsCard label="Aktif" value={issued.toString()} helper="pada halaman ini" icon={CheckCircle2} tone="yellow" />
        <StatsCard label="Revoked" value={revoked.toString()} helper="pada halaman ini" icon={ShieldX} tone="dark" />
      </section>

      <section className="grid gap-3 rounded-lg border bg-card p-4 shadow-sm lg:grid-cols-[1fr_220px_260px]">
        <label className="relative"><Search size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" /><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari credential, volunteer, atau event" className="h-11 w-full rounded-md border bg-background pl-10 pr-3 text-sm font-semibold outline-none focus:border-primary" /></label>
        <select value={status} onChange={(event) => setStatus(event.target.value as CertificateStatus | '')} className="h-11 rounded-md border bg-background px-3 text-sm font-bold outline-none focus:border-primary"><option value="">Semua status</option><option value="Issued">Issued</option><option value="Revoked">Revoked</option></select>
        <select value={eventId} onChange={(event) => setEventId(event.target.value)} className="h-11 rounded-md border bg-background px-3 text-sm font-bold outline-none focus:border-primary"><option value="">Semua event</option>{eventsQuery.data?.data.map((event) => <option key={event.id} value={event.id}>{event.title}</option>)}</select>
      </section>

      {query.data.data.length ? <section className="overflow-hidden rounded-lg border bg-card shadow-sm">
        <div className="hidden grid-cols-[1fr_1fr_150px_120px_150px] gap-4 border-b bg-muted px-4 py-3 text-xs font-bold uppercase text-muted-foreground lg:grid"><span>Volunteer</span><span>Event</span><span>Terbit</span><span>Status</span><span>Aksi</span></div>
        <div className="divide-y">{query.data.data.map((certificate) => <article key={certificate.id} className="grid gap-4 p-4 lg:grid-cols-[1fr_1fr_150px_120px_150px] lg:items-center">
          <div className="min-w-0"><p className="font-heading font-extrabold">{certificate.snapshot.volunteerName}</p><p className="mt-1 truncate font-mono text-xs font-bold text-muted-foreground">{certificate.credentialId}</p></div>
          <div className="min-w-0"><p className="truncate text-sm font-bold">{certificate.snapshot.eventTitle}</p><p className="mt-1 text-xs text-muted-foreground">Revisi {certificate.revisionNumber} · {certificate.hours} jam</p></div>
          <span className="text-sm font-semibold text-muted-foreground">{formatDate(certificate.issuedAt)}</span>
          <StatusBadge status={certificate.status} />
          <div className="flex gap-2"><Link to={`/verify/${certificate.credentialId}`} aria-label="Verifikasi credential" title="Verifikasi credential" className="inline-flex size-9 items-center justify-center rounded-md border hover:bg-muted"><ExternalLink size={16} /></Link>{canManage && certificate.status === 'Issued' ? <button type="button" onClick={() => { setRevokeTarget(certificate); setReason('') }} className="inline-flex h-9 items-center rounded-md border border-destructive/30 px-3 text-xs font-bold text-destructive hover:bg-destructive/10">Revoke</button> : null}</div>
        </article>)}</div>
      </section> : <EmptyState title="Sertifikat tidak ditemukan" description="Ubah filter atau terbitkan sertifikat dari halaman applicant Completed." />}

      {revokeTarget ? <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/45 p-4" role="dialog" aria-modal="true" aria-labelledby="revoke-title">
        <section className="w-full max-w-lg rounded-lg border bg-card p-6 shadow-lg">
          <h2 id="revoke-title" className="font-heading text-2xl font-extrabold">Cabut sertifikat</h2>
          <p className="mt-2 text-sm leading-6 text-muted-foreground">Credential {revokeTarget.credentialId} akan menjadi tidak valid. Alasan pencabutan akan terlihat pada verifikasi publik.</p>
          <label className="mt-5 block"><span className="text-sm font-bold">Alasan pencabutan</span><textarea autoFocus rows={4} value={reason} onChange={(event) => setReason(event.target.value)} className="mt-2 w-full resize-none rounded-md border bg-background p-3 text-sm outline-none focus:border-primary" /></label>
          <div className="mt-5 flex justify-end gap-2"><button type="button" onClick={() => setRevokeTarget(null)} disabled={revoking} className="h-10 rounded-md border px-4 text-sm font-bold hover:bg-muted">Batal</button><button type="button" onClick={() => void revoke()} disabled={revoking || reason.trim().length < 10} className="inline-flex h-10 items-center gap-2 rounded-md bg-destructive px-4 text-sm font-bold text-destructive-foreground disabled:opacity-50">{revoking ? <LoaderCircle size={16} className="animate-spin" /> : <ShieldX size={16} />}Cabut sertifikat</button></div>
        </section>
      </div> : null}
    </div>
  )
}
