import { ArrowLeft, CalendarPlus, Eye, FileText, LoaderCircle, MapPin, Users } from 'lucide-react'
import { useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import { EmptyState, ErrorState, EventCard, LoadingState, PageHeader } from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api, ApiError } from '@/lib/api'
import { getActiveOrganizer } from '@/lib/organizer'
import { cn } from '@/lib/utils'
import type { EventMode, VolunteerEvent } from '@/types/migunani'

const roleOptions = ['Field Volunteer', 'Education Mentor', 'Content & Documentation', 'Logistics Crew', 'Community Facilitator']
const modeOptions: EventMode[] = ['Offline', 'Online', 'Hybrid']
const previewImage = 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?auto=format&fit=crop&w=1200&q=80'

export function CreateEventPage() {
  const { session } = useAuth()
  const navigate = useNavigate()
  const organizer = getActiveOrganizer(session)
  const categoriesQuery = useApiQuery('create-event-categories', api.categories)
  const [title, setTitle] = useState('')
  const [categoryId, setCategoryId] = useState('')
  const [mode, setMode] = useState<EventMode>('Offline')
  const [city, setCity] = useState('')
  const [location, setLocation] = useState('')
  const [date, setDate] = useState('')
  const [startTime, setStartTime] = useState('08:00')
  const [endTime, setEndTime] = useState('13:00')
  const [quota, setQuota] = useState(30)
  const [description, setDescription] = useState('')
  const [benefits, setBenefits] = useState('Sertifikat, Konsumsi')
  const [skills, setSkills] = useState('Kerja tim, Komunikasi')
  const [selectedRoles, setSelectedRoles] = useState<string[]>(['Field Volunteer'])
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const categories = categoriesQuery.data ?? []
  const selectedCategory = categories.find((item) => item.id === categoryId) ?? categories[0]
  const resolvedCategoryId = categoryId || selectedCategory?.id || ''
  const previewEvent = useMemo<VolunteerEvent>(() => ({
    id: 'evt-preview',
    slug: 'preview-event',
    title: title || 'Judul event volunteer',
    categoryId: resolvedCategoryId,
    category: selectedCategory?.name ?? 'Komunitas',
    organizerId: organizer?.id ?? '',
    organizer,
    location: location || 'Lokasi kegiatan',
    city: city || 'Kota',
    mode,
    date: date || new Date().toISOString().slice(0, 10),
    startTime,
    endTime,
    durationHours: calculateDuration(startTime, endTime),
    quota,
    registered: 0,
    remainingQuota: quota,
    status: 'Open',
    image: previewImage,
    shortDescription: description || 'Deskripsi singkat event akan tampil di marketplace.',
    description,
    benefits: splitItems(benefits),
    skills: splitItems(skills),
    roles: selectedRoles,
    impactTarget: `${quota} relawan berkontribusi dalam kegiatan ini.`,
    tags: [mode, selectedCategory?.name ?? 'Komunitas'],
    featured: false,
    isSaved: false,
  }), [benefits, city, date, description, endTime, location, mode, organizer, quota, resolvedCategoryId, selectedCategory?.name, selectedRoles, skills, startTime, title])

  function toggleRole(role: string) {
    setSelectedRoles((current) => current.includes(role) ? current.filter((item) => item !== role) : [...current, role])
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!organizer || !resolvedCategoryId) return
    if (!selectedRoles.length) {
      setError('Pilih minimal satu role relawan.')
      return
    }

    setSubmitting(true)
    setError(null)
    try {
      const created = await api.createEvent(organizer.id, {
        title: title.trim(), categoryId: resolvedCategoryId, location: location.trim(), city: city.trim(), mode, date, startTime, endTime, quota,
        description: description.trim(), shortDescription: description.trim().slice(0, 180), benefits: splitItems(benefits), skills: splitItems(skills), roles: selectedRoles, tags: [mode, selectedCategory?.name ?? 'Komunitas'],
      })
      navigate(`/organizer/events/${created.slug}`)
    } catch (caught) {
      setError(caught instanceof ApiError ? caught.first() : caught instanceof Error ? caught.message : 'Event gagal dibuat.')
    } finally {
      setSubmitting(false)
    }
  }

  if (!organizer) return <EmptyState title="Organizer belum tersedia" description="Akun ini belum terhubung dengan data organizer." />
  if (categoriesQuery.loading) return <LoadingState label="Memuat form event..." />
  if (categoriesQuery.error) return <ErrorState error={categoriesQuery.error} onRetry={categoriesQuery.reload} />

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <Link to="/organizer" className="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground transition hover:text-primary"><ArrowLeft size={16} />Kembali ke organizer dashboard</Link>
      <PageHeader eyebrow="Create Event" title="Buat event volunteer dengan preview langsung." description="Lengkapi detail kegiatan. Event akan langsung tersimpan dan tersedia di dashboard organizer." />
      {error ? <p className="rounded-md border border-destructive/30 bg-card p-3 text-sm font-semibold text-destructive">{error}</p> : null}

      <section className="grid gap-6 xl:grid-cols-[1fr_420px]">
        <form onSubmit={submit} className="space-y-8 rounded-lg border bg-card p-6 shadow-sm">
          <FormSection icon={<FileText size={19} />} title="Informasi utama" description="Tentukan judul, kategori, mode, dan deskripsi event.">
            <div className="grid gap-4 md:grid-cols-2">
              <Field label="Judul event" className="md:col-span-2"><input required maxLength={255} value={title} onChange={(event) => setTitle(event.target.value)} className={inputClassName} /></Field>
              <Field label="Kategori"><select required value={resolvedCategoryId} onChange={(event) => setCategoryId(event.target.value)} className={inputClassName}>{categories.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></Field>
              <Field label="Mode kegiatan"><select value={mode} onChange={(event) => setMode(event.target.value as EventMode)} className={inputClassName}>{modeOptions.map((item) => <option key={item}>{item}</option>)}</select></Field>
              <Field label="Deskripsi" className="md:col-span-2"><textarea required value={description} onChange={(event) => setDescription(event.target.value)} rows={5} className={cn(inputClassName, 'h-auto resize-none py-3 leading-7')} /></Field>
            </div>
          </FormSection>

          <FormSection icon={<MapPin size={19} />} title="Lokasi dan jadwal" description="Jadwal harus jelas agar relawan bisa mengatur komitmennya.">
            <div className="grid gap-4 md:grid-cols-2">
              <Field label="Kota"><input required value={city} onChange={(event) => setCity(event.target.value)} className={inputClassName} /></Field>
              <Field label="Lokasi"><input required value={location} onChange={(event) => setLocation(event.target.value)} className={inputClassName} /></Field>
              <Field label="Tanggal"><input required type="date" value={date} onChange={(event) => setDate(event.target.value)} className={inputClassName} /></Field>
              <div className="grid grid-cols-2 gap-3"><Field label="Mulai"><input required type="time" value={startTime} onChange={(event) => setStartTime(event.target.value)} className={inputClassName} /></Field><Field label="Selesai"><input required type="time" value={endTime} onChange={(event) => setEndTime(event.target.value)} className={inputClassName} /></Field></div>
            </div>
          </FormSection>

          <FormSection icon={<Users size={19} />} title="Kebutuhan relawan" description="Atur kuota, role, benefit, dan skill yang dibutuhkan.">
            <div className="space-y-4">
              <Field label="Kuota relawan"><input required type="number" min="1" max="10000" value={quota} onChange={(event) => setQuota(Number(event.target.value))} className={inputClassName} /></Field>
              <div><p className="text-sm font-bold text-foreground">Role relawan</p><div className="mt-2 flex flex-wrap gap-2">{roleOptions.map((role) => { const active = selectedRoles.includes(role); return <button key={role} type="button" onClick={() => toggleRole(role)} className={cn('rounded-md border px-3 py-1.5 text-xs font-bold transition', active ? 'border-primary bg-primary text-primary-foreground' : 'bg-card text-muted-foreground hover:bg-accent')}>{role}</button> })}</div></div>
              <div className="grid gap-4 md:grid-cols-2"><Field label="Benefit, pisahkan koma"><input value={benefits} onChange={(event) => setBenefits(event.target.value)} className={inputClassName} /></Field><Field label="Skill, pisahkan koma"><input value={skills} onChange={(event) => setSkills(event.target.value)} className={inputClassName} /></Field></div>
            </div>
          </FormSection>

          <button type="submit" disabled={submitting || !resolvedCategoryId} className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green disabled:cursor-not-allowed disabled:opacity-50">{submitting ? <LoaderCircle size={17} className="animate-spin" /> : <CalendarPlus size={17} />}Publikasikan event</button>
        </form>

        <aside className="space-y-4 xl:sticky xl:top-24 xl:self-start">
          <section className="rounded-lg border bg-card p-5 shadow-sm"><div className="flex items-center justify-between gap-4"><div><p className="text-sm font-bold uppercase text-primary">Live preview</p><h2 className="mt-1 font-heading text-2xl font-extrabold">Marketplace card</h2></div><span className="flex size-10 items-center justify-center rounded-md bg-accent text-accent-foreground"><Eye size={19} /></span></div><div className="mt-5"><EventCard event={previewEvent} organizer={organizer} /></div></section>
        </aside>
      </section>
    </div>
  )
}

const inputClassName = 'h-11 w-full rounded-md border bg-background px-3 text-sm font-semibold outline-none transition placeholder:text-muted-foreground focus:border-primary focus:ring-2 focus:ring-primary/15'

function FormSection({ icon, title, description, children }: { icon: React.ReactNode; title: string; description: string; children: React.ReactNode }) {
  return <section className="space-y-5"><div className="flex gap-4"><span className="flex size-10 shrink-0 items-center justify-center rounded-md bg-accent text-accent-foreground">{icon}</span><div><h2 className="font-heading text-2xl font-extrabold">{title}</h2><p className="mt-1 text-sm leading-6 text-muted-foreground">{description}</p></div></div>{children}</section>
}

function Field({ label, className, children }: { label: string; className?: string; children: React.ReactNode }) {
  return <label className={cn('block', className)}><span className="text-sm font-bold text-foreground">{label}</span><span className="mt-2 block">{children}</span></label>
}

function splitItems(value: string) { return value.split(',').map((item) => item.trim()).filter(Boolean) }
function calculateDuration(startTime: string, endTime: string) { const [startHour = 0, startMinute = 0] = startTime.split(':').map(Number); const [endHour = 0, endMinute = 0] = endTime.split(':').map(Number); return Math.max(1, Math.round(((endHour * 60 + endMinute) - (startHour * 60 + startMinute)) / 60)) }
