import {
  ArrowLeft,
  Ban,
  CheckCircle2,
  CircleStop,
  LoaderCircle,
  RotateCcw,
  Save,
  Users,
} from 'lucide-react'
import { useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useParams } from 'react-router-dom'

import {
  EmptyState,
  ErrorState,
  EventDetailPanel,
  LoadingState,
  PageHeader,
  StatusBadge,
} from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api, ApiError } from '@/lib/api'
import { getActiveOrganizer } from '@/lib/organizer'
import { cn } from '@/lib/utils'
import type {
  Category,
  EventMode,
  EventStatus,
  Organizer,
  VolunteerEvent,
} from '@/types/migunani'

const modeOptions: EventMode[] = ['Offline', 'Online', 'Hybrid']

export function EditEventPage() {
  const { eventId = '' } = useParams()
  const { session } = useAuth()
  const organizer = getActiveOrganizer(session)
  const eventQuery = useApiQuery(
    `organizer-event:${organizer?.id}:${eventId}`,
    () => api.organizerEvent(organizer!.id, eventId),
    Boolean(organizer && eventId),
  )
  const categoriesQuery = useApiQuery('edit-event-categories', api.categories)

  if (!organizer) return <EmptyState title="Organizer belum tersedia" description="Akun ini belum terhubung dengan data organizer." />
  if (eventQuery.loading || categoriesQuery.loading) return <LoadingState label="Memuat event..." />
  if (eventQuery.error) return <ErrorState error={eventQuery.error} onRetry={eventQuery.reload} />
  if (categoriesQuery.error) return <ErrorState error={categoriesQuery.error} onRetry={categoriesQuery.reload} />
  if (!eventQuery.data || !categoriesQuery.data) return null

  return (
    <EditEventForm
      key={eventQuery.data.id}
      initialEvent={eventQuery.data}
      organizer={organizer}
      categories={categoriesQuery.data}
    />
  )
}

function EditEventForm({ initialEvent, organizer, categories }: {
  initialEvent: VolunteerEvent
  organizer: Organizer
  categories: Category[]
}) {
  const [currentEvent, setCurrentEvent] = useState(initialEvent)
  const [title, setTitle] = useState(initialEvent.title)
  const [categoryId, setCategoryId] = useState(initialEvent.categoryId)
  const [mode, setMode] = useState(initialEvent.mode)
  const [city, setCity] = useState(initialEvent.city)
  const [location, setLocation] = useState(initialEvent.location)
  const [date, setDate] = useState(initialEvent.date.slice(0, 10))
  const [startTime, setStartTime] = useState(initialEvent.startTime.slice(0, 5))
  const [endTime, setEndTime] = useState(initialEvent.endTime.slice(0, 5))
  const [quota, setQuota] = useState(initialEvent.quota)
  const [description, setDescription] = useState(initialEvent.description)
  const [benefits, setBenefits] = useState(initialEvent.benefits.join(', '))
  const [skills, setSkills] = useState(initialEvent.skills.join(', '))
  const [roles, setRoles] = useState(initialEvent.roles.join(', '))
  const [saving, setSaving] = useState(false)
  const [changingStatus, setChangingStatus] = useState<EventStatus | null>(null)
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  async function save(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const roleItems = splitItems(roles)
    if (!roleItems.length) {
      setError('Minimal satu role relawan harus tersedia.')
      return
    }

    setSaving(true)
    setError(null)
    setMessage(null)
    try {
      const updated = await api.updateEvent(organizer.id, currentEvent.id, {
        title: title.trim(),
        categoryId,
        location: location.trim(),
        city: city.trim(),
        mode,
        date,
        startTime,
        endTime,
        quota,
        description: description.trim(),
        shortDescription: description.trim().slice(0, 180),
        benefits: splitItems(benefits),
        skills: splitItems(skills),
        roles: roleItems,
        tags: currentEvent.tags,
      })
      setCurrentEvent(updated)
      setMessage('Perubahan event berhasil disimpan.')
    } catch (caught) {
      setError(errorMessage(caught, 'Event gagal diperbarui.'))
    } finally {
      setSaving(false)
    }
  }

  async function changeStatus(status: EventStatus, label: string, needsConfirmation = false) {
    if (needsConfirmation && !window.confirm(`Yakin ingin ${label.toLowerCase()} event ini?`)) return

    setChangingStatus(status)
    setError(null)
    setMessage(null)
    try {
      const updated = await api.updateEvent(organizer.id, currentEvent.id, { status })
      setCurrentEvent(updated)
      setMessage(`Status event berubah menjadi ${updated.status}.`)
    } catch (caught) {
      setError(errorMessage(caught, 'Status event gagal diperbarui.'))
    } finally {
      setChangingStatus(null)
    }
  }

  const statusActions = getStatusActions(currentEvent.status)

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <Link to={`/organizer/events/${currentEvent.slug}`} className="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground transition hover:text-primary">
        <ArrowLeft size={16} />Kembali ke detail event
      </Link>

      <PageHeader
        eyebrow="Edit Event"
        title={currentEvent.title}
        description="Perbarui informasi kegiatan dan kelola status event sesuai tahap pelaksanaannya."
        action={<StatusBadge status={currentEvent.status} />}
      />

      {message ? <p className="rounded-md border border-primary/30 bg-accent p-3 text-sm font-semibold text-foreground">{message}</p> : null}
      {error ? <p className="rounded-md border border-destructive/30 bg-card p-3 text-sm font-semibold text-destructive">{error}</p> : null}

      <section className="grid gap-6 xl:grid-cols-[1fr_360px]">
        <form onSubmit={save} className="space-y-6 rounded-lg border bg-card p-6 shadow-sm">
          <div className="grid gap-4 md:grid-cols-2">
            <Field label="Judul event" className="md:col-span-2"><input required maxLength={255} value={title} onChange={(event) => setTitle(event.target.value)} className={inputClassName} /></Field>
            <Field label="Kategori"><select required value={categoryId} onChange={(event) => setCategoryId(event.target.value)} className={inputClassName}>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></Field>
            <Field label="Mode"><select value={mode} onChange={(event) => setMode(event.target.value as EventMode)} className={inputClassName}>{modeOptions.map((item) => <option key={item}>{item}</option>)}</select></Field>
            <Field label="Kota"><input required value={city} onChange={(event) => setCity(event.target.value)} className={inputClassName} /></Field>
            <Field label="Lokasi"><input required value={location} onChange={(event) => setLocation(event.target.value)} className={inputClassName} /></Field>
            <Field label="Tanggal"><input required type="date" value={date} onChange={(event) => setDate(event.target.value)} className={inputClassName} /></Field>
            <Field label="Kuota"><input required type="number" min={currentEvent.registered || 1} value={quota} onChange={(event) => setQuota(Number(event.target.value))} className={inputClassName} /></Field>
            <Field label="Waktu mulai"><input required type="time" value={startTime} onChange={(event) => setStartTime(event.target.value)} className={inputClassName} /></Field>
            <Field label="Waktu selesai"><input required type="time" value={endTime} onChange={(event) => setEndTime(event.target.value)} className={inputClassName} /></Field>
            <Field label="Deskripsi" className="md:col-span-2"><textarea required rows={6} value={description} onChange={(event) => setDescription(event.target.value)} className={cn(inputClassName, 'h-auto resize-none py-3 leading-7')} /></Field>
            <Field label="Benefit, pisahkan koma" className="md:col-span-2"><input value={benefits} onChange={(event) => setBenefits(event.target.value)} className={inputClassName} /></Field>
            <Field label="Skill, pisahkan koma" className="md:col-span-2"><input value={skills} onChange={(event) => setSkills(event.target.value)} className={inputClassName} /></Field>
            <Field label="Role relawan, pisahkan koma" className="md:col-span-2"><input required value={roles} onChange={(event) => setRoles(event.target.value)} className={inputClassName} /></Field>
          </div>

          <button type="submit" disabled={saving || Boolean(changingStatus)} className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green disabled:cursor-wait disabled:opacity-50">
            {saving ? <LoaderCircle size={17} className="animate-spin" /> : <Save size={17} />}Simpan perubahan
          </button>
        </form>

        <aside className="space-y-4 xl:sticky xl:top-24 xl:self-start">
          <EventDetailPanel event={currentEvent} organizer={organizer} />

          <section className="rounded-lg border bg-card p-5 shadow-sm">
            <p className="text-sm font-bold uppercase text-primary">Lifecycle event</p>
            <h2 className="mt-2 font-heading text-xl font-extrabold">Kelola status kegiatan</h2>
            <p className="mt-2 text-sm leading-6 text-muted-foreground">Status terminal tidak dapat dibuka kembali. Pastikan event benar-benar selesai atau dibatalkan.</p>
            {statusActions.length ? <div className="mt-4 grid gap-2">{statusActions.map((action) => (
              <button key={action.status} type="button" disabled={Boolean(changingStatus) || saving} onClick={() => void changeStatus(action.status, action.label, action.confirm)} className={cn('inline-flex h-10 items-center justify-center gap-2 rounded-md border px-4 text-sm font-bold transition disabled:cursor-wait disabled:opacity-50', action.danger ? 'border-destructive/30 text-destructive hover:bg-destructive/10' : 'bg-card hover:bg-muted')}>
                {changingStatus === action.status ? <LoaderCircle size={16} className="animate-spin" /> : <action.icon size={16} />}{action.label}
              </button>
            ))}</div> : <div className="mt-4 flex items-center gap-2 rounded-md bg-muted p-3 text-sm font-semibold text-muted-foreground"><CheckCircle2 size={17} />Lifecycle event telah berakhir.</div>}
          </section>

          <Link to={`/organizer/applicants?event=${currentEvent.id}`} className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-secondary px-4 text-sm font-bold text-secondary-foreground"><Users size={17} />Kelola applicant</Link>
        </aside>
      </section>
    </div>
  )
}

const inputClassName = 'h-11 w-full rounded-md border bg-background px-3 text-sm font-semibold outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/15'

function Field({ label, className, children }: { label: string; className?: string; children: React.ReactNode }) {
  return <label className={cn('block', className)}><span className="text-sm font-bold text-foreground">{label}</span><span className="mt-2 block">{children}</span></label>
}

function getStatusActions(status: EventStatus) {
  if (status === 'Open' || status === 'Nearly Full') {
    return [
      { status: 'Closed' as const, label: 'Tutup pendaftaran', icon: CircleStop, confirm: false, danger: false },
      { status: 'Completed' as const, label: 'Tandai selesai', icon: CheckCircle2, confirm: true, danger: false },
      { status: 'Cancelled' as const, label: 'Batalkan event', icon: Ban, confirm: true, danger: true },
    ]
  }
  if (status === 'Closed') {
    return [
      { status: 'Open' as const, label: 'Buka kembali', icon: RotateCcw, confirm: false, danger: false },
      { status: 'Completed' as const, label: 'Tandai selesai', icon: CheckCircle2, confirm: true, danger: false },
      { status: 'Cancelled' as const, label: 'Batalkan event', icon: Ban, confirm: true, danger: true },
    ]
  }
  return []
}

function splitItems(value: string) {
  return value.split(',').map((item) => item.trim()).filter(Boolean)
}

function errorMessage(error: unknown, fallback: string) {
  return error instanceof ApiError ? error.first() : error instanceof Error ? error.message : fallback
}
