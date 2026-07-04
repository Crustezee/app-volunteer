import { BadgeCheck, CalendarDays, Clock, MapPin, Target, Users } from 'lucide-react'

import { CategoryChip } from '@/components/CategoryChip'
import { StatusBadge } from '@/components/StatusBadge'
import { formatDate, formatEventTime, getFillPercentage } from '@/lib/format'
import type { Organizer, VolunteerEvent } from '@/types/migunani'

type EventDetailPanelProps = {
  event: VolunteerEvent
  organizer?: Organizer
}

export function EventDetailPanel({ event, organizer }: EventDetailPanelProps) {
  const fill = getFillPercentage(event.registered, event.quota)

  return (
    <aside className="rounded-lg border bg-card p-5 shadow-sm">
      <div className="flex flex-wrap gap-2">
        <StatusBadge status={event.status} />
        <CategoryChip category={event.category} />
      </div>

      <div className="mt-5 space-y-4">
        <DetailItem icon={CalendarDays} label="Tanggal" value={formatDate(event.date)} />
        <DetailItem
          icon={Clock}
          label="Waktu"
          value={formatEventTime(event.startTime, event.endTime)}
        />
        <DetailItem icon={MapPin} label="Lokasi" value={`${event.location}, ${event.city}`} />
        <DetailItem icon={Users} label="Kuota" value={`${event.registered}/${event.quota} relawan`} />
        <DetailItem icon={Target} label="Target dampak" value={event.impactTarget} />
        <DetailItem
          icon={BadgeCheck}
          label="Organizer"
          value={organizer ? `${organizer.name} · ${organizer.responseTime}` : 'Organizer komunitas'}
        />
      </div>

      <div className="mt-5 space-y-2">
        <div className="flex items-center justify-between text-sm font-semibold">
          <span>Keterisian</span>
          <span>{fill}%</span>
        </div>
        <div className="h-2 overflow-hidden rounded-full bg-muted">
          <div className="h-full rounded-full bg-primary" style={{ width: `${fill}%` }} />
        </div>
      </div>
    </aside>
  )
}

type DetailItemProps = {
  icon: typeof CalendarDays
  label: string
  value: string
}

function DetailItem({ icon: Icon, label, value }: DetailItemProps) {
  return (
    <div className="flex gap-3">
      <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">
        <Icon size={17} />
      </span>
      <div>
        <p className="text-xs font-bold uppercase text-muted-foreground">{label}</p>
        <p className="mt-0.5 text-sm font-semibold leading-6 text-foreground">{value}</p>
      </div>
    </div>
  )
}
