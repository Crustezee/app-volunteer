import { Award, Download, ExternalLink, LoaderCircle, ShieldCheck } from 'lucide-react'
import { Link } from 'react-router-dom'

import { StatusBadge } from '@/components/StatusBadge'
import { formatDate } from '@/lib/format'
import type { Certificate } from '@/types/migunani'

type CertificateCardProps = {
  certificate: Certificate
  downloading?: boolean
  onDownload: (certificateId: string) => void
}

export function CertificateCard({ certificate, downloading = false, onDownload }: CertificateCardProps) {
  const isIssued = certificate.status === 'Issued'

  return (
    <article className="overflow-hidden rounded-lg border bg-card shadow-sm">
      <div className="bg-deep-green p-5 text-primary-foreground">
        <div className="flex items-start justify-between gap-4">
          <span className="flex size-11 items-center justify-center rounded-md bg-secondary text-secondary-foreground"><Award size={22} /></span>
          <div className="flex flex-col items-end gap-2">
            <StatusBadge status={certificate.status} className="bg-card text-foreground" />
            <span className="text-xs font-bold">{certificate.hours} jam · revisi {certificate.revisionNumber}</span>
          </div>
        </div>
        <p className="mt-6 text-xs font-bold uppercase text-primary-foreground/70">Sertifikat Volunteer</p>
        <h3 className="mt-2 font-heading text-xl font-extrabold">{certificate.snapshot?.eventTitle ?? certificate.event?.title ?? 'Event Migunani'}</h3>
      </div>

      <div className="space-y-4 p-5">
        <div>
          <p className="text-xs font-bold uppercase text-muted-foreground">Credential ID</p>
          <p className="mt-1 break-all font-mono text-sm font-bold text-foreground">{certificate.credentialId}</p>
        </div>
        {!isIssued && certificate.revocationReason ? <p className="rounded-md bg-destructive/10 p-3 text-sm font-semibold text-destructive">{certificate.revocationReason}</p> : null}
        <div className="flex items-center justify-between gap-4">
          <span className="inline-flex items-center gap-2 text-sm font-semibold text-muted-foreground"><ShieldCheck size={16} className="text-primary" />Terbit {formatDate(certificate.issuedAt)}</span>
          <div className="flex gap-2">
            <Link to={`/verify/${certificate.credentialId}`} aria-label="Verifikasi sertifikat" title="Verifikasi sertifikat" className="inline-flex size-10 items-center justify-center rounded-md border bg-card text-foreground transition hover:bg-muted"><ExternalLink size={17} /></Link>
            <button type="button" onClick={() => onDownload(certificate.id)} disabled={downloading || !isIssued} className="inline-flex size-10 items-center justify-center rounded-md border bg-card text-foreground transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-40" aria-label={isIssued ? 'Download sertifikat' : 'Sertifikat revoked tidak dapat diunduh'} title={isIssued ? 'Download sertifikat' : 'Sertifikat telah dicabut'}>
              {downloading ? <LoaderCircle className="animate-spin" size={17} /> : <Download size={17} />}
            </button>
          </div>
        </div>
        {certificate.replacementCredentialId ? <Link to={`/verify/${certificate.replacementCredentialId}`} className="block text-sm font-bold text-primary hover:underline">Lihat sertifikat pengganti</Link> : null}
      </div>
    </article>
  )
}
