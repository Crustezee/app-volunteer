import { AlertTriangle, CheckCircle2, Search, ShieldCheck, XCircle } from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'

import { ErrorState, LoadingState, StatusBadge } from '@/components'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api, ApiError } from '@/lib/api'
import { formatDate } from '@/lib/format'

export function VerifyCertificatePage() {
  const { credentialId = '' } = useParams()
  const navigate = useNavigate()
  const [value, setValue] = useState(credentialId)
  const query = useApiQuery(
    `verify-certificate:${credentialId}`,
    () => api.verifyCertificate(credentialId),
    Boolean(credentialId),
  )

  function search(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const normalized = value.trim().toUpperCase()
    if (normalized) navigate(`/verify/${encodeURIComponent(normalized)}`)
  }

  const notFound = query.error instanceof ApiError && query.error.status === 404

  return (
    <div className="mx-auto max-w-5xl space-y-6 pb-20 lg:pb-0">
      <header>
        <p className="text-sm font-bold uppercase text-primary">Credential verification</p>
        <h1 className="mt-2 font-heading text-3xl font-extrabold sm:text-5xl">Verifikasi sertifikat Migunani</h1>
        <p className="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground">Masukkan Credential ID untuk memeriksa status dan data penerbitan sertifikat.</p>
      </header>

      <form onSubmit={search} className="flex flex-col gap-3 rounded-lg border bg-card p-4 shadow-sm sm:flex-row">
        <label className="relative flex-1">
          <Search size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input value={value} onChange={(event) => setValue(event.target.value)} placeholder="Contoh: MGN-2026-XXXXXXXXXX" className="h-11 w-full rounded-md border bg-background pl-10 pr-3 font-mono text-sm font-bold uppercase outline-none focus:border-primary focus:ring-2 focus:ring-primary/15" />
        </label>
        <button type="submit" className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground hover:bg-deep-green"><ShieldCheck size={17} />Verifikasi</button>
      </form>

      {query.loading ? <LoadingState label="Memeriksa credential..." /> : null}
      {query.error && !notFound ? <ErrorState error={query.error} onRetry={query.reload} /> : null}
      {notFound ? <section className="rounded-lg border border-destructive/30 bg-card p-8 text-center shadow-sm"><XCircle size={42} className="mx-auto text-destructive" /><h2 className="mt-4 font-heading text-2xl font-extrabold">Credential tidak ditemukan</h2><p className="mt-2 text-sm text-muted-foreground">Periksa kembali Credential ID yang dimasukkan.</p></section> : null}

      {query.data ? <section className="overflow-hidden rounded-lg border bg-card shadow-sm">
        <div className={query.data.isValid ? 'bg-deep-green p-6 text-primary-foreground' : 'bg-destructive p-6 text-destructive-foreground'}>
          <div className="flex items-start justify-between gap-4">
            {query.data.isValid ? <CheckCircle2 size={40} /> : <AlertTriangle size={40} />}
            <StatusBadge status={query.data.status} className="bg-card text-foreground" />
          </div>
          <h2 className="mt-5 font-heading text-3xl font-extrabold">{query.data.isValid ? 'Sertifikat valid' : 'Sertifikat telah dicabut'}</h2>
          <p className="mt-2 break-all font-mono text-sm font-bold opacity-80">{query.data.credentialId}</p>
        </div>

        <div className="grid gap-x-8 gap-y-5 p-6 md:grid-cols-2">
          <Detail label="Nama volunteer" value={query.data.volunteerName} />
          <Detail label="Role" value={query.data.role} />
          <Detail label="Event" value={query.data.eventTitle} />
          <Detail label="Organizer" value={query.data.organizerName} />
          <Detail label="Tanggal event" value={formatDate(query.data.eventDate)} />
          <Detail label="Tanggal terbit" value={formatDate(query.data.issuedAt)} />
          <Detail label="Jam kontribusi" value={`${query.data.hours} jam`} />
          <Detail label="Revisi" value={query.data.revisionNumber.toString()} />
        </div>

        {!query.data.isValid && query.data.revocationReason ? <div className="border-t p-6"><p className="text-xs font-bold uppercase text-muted-foreground">Alasan pencabutan</p><p className="mt-2 text-sm leading-6 text-foreground">{query.data.revocationReason}</p></div> : null}
        {query.data.replacementCredentialId ? <div className="border-t p-6"><Link to={`/verify/${query.data.replacementCredentialId}`} className="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-bold text-primary-foreground">Lihat credential pengganti</Link></div> : null}
      </section> : null}
    </div>
  )
}

function Detail({ label, value }: { label: string; value: string }) {
  return <div><p className="text-xs font-bold uppercase text-muted-foreground">{label}</p><p className="mt-1 font-heading text-lg font-extrabold text-foreground">{value}</p></div>
}
