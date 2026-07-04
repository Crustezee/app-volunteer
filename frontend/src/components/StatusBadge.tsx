import type { ApplicationStatus, CertificateStatus, EventStatus } from '@/types/migunani'
import { cn } from '@/lib/utils'

type StatusBadgeProps = {
  status: EventStatus | ApplicationStatus | CertificateStatus
  className?: string
}

const statusLabel: Record<EventStatus | ApplicationStatus | CertificateStatus, string> = {
  Open: 'Open',
  'Nearly Full': 'Hampir penuh',
  Closed: 'Tutup',
  Cancelled: 'Dibatalkan',
  Draft: 'Draft',
  Submitted: 'Terkirim',
  Accepted: 'Diterima',
  Waitlisted: 'Waiting list',
  Rejected: 'Ditolak',
  Withdrawn: 'Ditarik',
  Completed: 'Selesai',
  Issued: 'Valid',
  Revoked: 'Dicabut',
}

const statusClassName: Record<EventStatus | ApplicationStatus | CertificateStatus, string> = {
  Open: 'border-primary/25 bg-primary/10 text-primary',
  'Nearly Full': 'border-secondary/70 bg-secondary/25 text-foreground',
  Closed: 'border-muted bg-muted text-muted-foreground',
  Cancelled: 'border-destructive/25 bg-destructive/10 text-destructive',
  Draft: 'border-muted bg-muted text-muted-foreground',
  Submitted: 'border-info/25 bg-info/10 text-info',
  Accepted: 'border-primary/25 bg-primary/10 text-primary',
  Waitlisted: 'border-warning/25 bg-warning/10 text-warning',
  Rejected: 'border-destructive/25 bg-destructive/10 text-destructive',
  Withdrawn: 'border-muted bg-muted text-muted-foreground',
  Completed: 'border-deep-green/25 bg-deep-green/10 text-deep-green',
  Issued: 'border-primary/25 bg-primary/10 text-primary',
  Revoked: 'border-destructive/25 bg-destructive/10 text-destructive',
}

export function StatusBadge({ status, className }: StatusBadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-bold',
        statusClassName[status],
        className,
      )}
    >
      {statusLabel[status]}
    </span>
  )
}
