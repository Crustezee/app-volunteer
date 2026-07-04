import {
  ArrowLeft,
  ArrowRight,
  CalendarCheck,
  CheckCircle2,
  FileText,
  LoaderCircle,
  MessageSquareText,
  Send,
  UserRoundCheck,
} from 'lucide-react'
import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'

import {
  ErrorState,
  EventDetailPanel,
  LoadingState,
  PageHeader,
  RegistrationStepper,
  type RegistrationStep,
} from '@/components'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api, ApiError } from '@/lib/api'
import { cn } from '@/lib/utils'
import type { VolunteerApplication, VolunteerRole } from '@/types/migunani'

const registrationSteps: RegistrationStep[] = [
  { id: 'role', label: 'Role', description: 'Pilih kontribusi' },
  { id: 'motivation', label: 'Motivasi', description: 'Ceritakan alasan' },
  { id: 'availability', label: 'Waktu', description: 'Konfirmasi jadwal' },
  { id: 'review', label: 'Review', description: 'Cek dan kirim' },
]

const availabilityOptions = [
  'Siap hadir dari awal kegiatan',
  'Bisa ikut briefing online',
  'Bersedia dokumentasi kegiatan',
  'Bersedia membantu persiapan logistik',
  'Butuh surat tugas dari organizer',
]

export function ApplyPage() {
  const { eventId = '' } = useParams()
  const query = useApiQuery(`apply-event:${eventId}`, () => api.event(eventId), Boolean(eventId))
  const [currentStep, setCurrentStep] = useState(0)
  const [selectedRole, setSelectedRole] = useState<VolunteerRole>('')
  const [motivation, setMotivation] = useState('')
  const [availability, setAvailability] = useState<string[]>([])
  const [submitting, setSubmitting] = useState(false)
  const [submitted, setSubmitted] = useState<VolunteerApplication | null>(null)
  const [submitError, setSubmitError] = useState('')

  const effectiveRole = selectedRole || query.data?.roles[0] || ''

  const canContinue = useMemo(() => {
    if (currentStep === 0) return effectiveRole.length > 0
    if (currentStep === 1) return motivation.trim().length >= 24
    if (currentStep === 2) return availability.length > 0
    return true
  }, [availability.length, currentStep, effectiveRole, motivation])

  if (query.loading && !query.data) return <LoadingState label="Menyiapkan form pendaftaran..." />
  if (query.error || !query.data) return <ErrorState error={query.error ?? new Error('Event tidak ditemukan.')} onRetry={query.reload} />

  const event = query.data

  if (event.myApplication && !submitted) {
    return (
      <div className="mx-auto max-w-3xl">
        <PageHeader eyebrow="Application" title="Kamu sudah terdaftar pada event ini." description={`Status aplikasi saat ini: ${event.myApplication.status}.`} />
        <Link to="/volunteer/dashboard?tab=applications" className="mt-5 inline-flex h-11 items-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground">Lihat aplikasi <ArrowRight size={17} /></Link>
      </div>
    )
  }

  function toggleAvailability(option: string) {
    setAvailability((current) => current.includes(option) ? current.filter((item) => item !== option) : [...current, option])
  }

  async function goNext() {
    if (currentStep < registrationSteps.length - 1) {
      setCurrentStep((step) => step + 1)
      return
    }

    setSubmitting(true)
    setSubmitError('')
    try {
      setSubmitted(await api.apply(event.id, { role: effectiveRole, motivation: motivation.trim(), availability }))
    } catch (caught) {
      setSubmitError(caught instanceof ApiError ? caught.first() : 'Pendaftaran gagal dikirim.')
    } finally {
      setSubmitting(false)
    }
  }

  if (submitted) {
    return (
      <div className="mx-auto max-w-4xl">
        <section className="overflow-hidden rounded-lg border bg-card shadow-sm">
          <div className="bg-deep-green p-8 text-primary-foreground">
            <CheckCircle2 size={42} className="text-secondary" />
            <p className="mt-6 text-sm font-bold uppercase text-primary-foreground/70">Pendaftaran terkirim</p>
            <h1 className="mt-2 font-heading text-3xl font-extrabold sm:text-5xl">Kamu terdaftar untuk {event.title}.</h1>
            <p className="mt-4 max-w-2xl leading-7 text-primary-foreground/78">Organizer akan meninjau aplikasimu. Status dapat dipantau dari dashboard relawan.</p>
          </div>
          <div className="grid gap-4 p-6 sm:grid-cols-3">
            <SummaryTile label="Role" value={submitted.role} />
            <SummaryTile label="Status" value={submitted.status} />
            <SummaryTile label="Organizer" value={event.organizer?.name ?? 'Organizer'} />
          </div>
          <div className="flex flex-col gap-3 border-t p-6 sm:flex-row">
            <Link to="/volunteer/dashboard?tab=applications" className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground">Buka dashboard <ArrowRight size={17} /></Link>
            <Link to="/volunteer/events" className="inline-flex h-11 items-center justify-center rounded-md border px-5 text-sm font-bold">Cari event lain</Link>
          </div>
        </section>
      </div>
    )
  }

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <Link to={`/volunteer/events/${event.slug}`} className="inline-flex items-center gap-2 text-sm font-bold text-muted-foreground hover:text-primary"><ArrowLeft size={16} /> Kembali ke detail event</Link>
      <PageHeader eyebrow="Pendaftaran Relawan" title={`Daftar untuk ${event.title}`} description="Lengkapi role, motivasi, dan ketersediaan agar organizer dapat menilai aplikasimu." />
      <RegistrationStepper steps={registrationSteps} currentStep={currentStep} />

      <section className="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div className="rounded-lg border bg-card p-6 shadow-sm">
          {currentStep === 0 ? (
            <Step icon={<UserRoundCheck size={20} />} title="Pilih role volunteer" description="Pilih peran yang paling sesuai dengan kemampuanmu.">
              <div className="grid gap-3 md:grid-cols-2">{event.roles.map((role) => <button key={role} type="button" onClick={() => setSelectedRole(role)} className={cn('rounded-lg border p-4 text-left font-bold hover:border-primary/40', effectiveRole === role && 'border-primary bg-accent ring-2 ring-primary/15')}>{role}{effectiveRole === role ? <CheckCircle2 className="float-right text-primary" size={18} /> : null}</button>)}</div>
            </Step>
          ) : null}
          {currentStep === 1 ? (
            <Step icon={<MessageSquareText size={20} />} title="Tulis motivasimu" description="Minimal 24 karakter untuk membantu proses review.">
              <textarea value={motivation} onChange={(e) => setMotivation(e.target.value)} rows={8} className="w-full resize-none rounded-lg border bg-background p-4 text-sm leading-7 outline-none focus:border-primary" placeholder="Ceritakan alasanmu ingin bergabung..." />
              <p className="mt-2 text-sm text-muted-foreground">{motivation.trim().length} karakter</p>
            </Step>
          ) : null}
          {currentStep === 2 ? (
            <Step icon={<CalendarCheck size={20} />} title="Konfirmasi ketersediaan" description="Pilih semua kondisi yang sesuai.">
              <div className="grid gap-3">{availabilityOptions.map((option) => <button key={option} type="button" onClick={() => toggleAvailability(option)} className={cn('flex items-center justify-between rounded-lg border p-4 text-left text-sm font-bold', availability.includes(option) && 'border-primary bg-accent ring-2 ring-primary/15')}><span>{option}</span>{availability.includes(option) ? <CheckCircle2 size={18} className="text-primary" /> : null}</button>)}</div>
            </Step>
          ) : null}
          {currentStep === 3 ? (
            <Step icon={<FileText size={20} />} title="Review aplikasi" description="Pastikan informasi berikut sudah benar.">
              <div className="space-y-3"><Review label="Event" value={event.title} /><Review label="Role" value={effectiveRole} /><Review label="Motivasi" value={motivation} /><Review label="Ketersediaan" value={availability.join(', ')} /></div>
            </Step>
          ) : null}

          {submitError ? <p className="mt-5 rounded-md bg-destructive/10 p-3 text-sm font-semibold text-destructive">{submitError}</p> : null}
          <div className="mt-8 flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-between">
            <button type="button" onClick={() => setCurrentStep((step) => Math.max(step - 1, 0))} disabled={currentStep === 0 || submitting} className="inline-flex h-11 items-center justify-center gap-2 rounded-md border px-5 text-sm font-bold disabled:opacity-40"><ArrowLeft size={17} /> Kembali</button>
            <button type="button" onClick={() => void goNext()} disabled={!canContinue || submitting} className="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground disabled:opacity-40">{submitting ? <LoaderCircle className="animate-spin" size={17} /> : currentStep === 3 ? <Send size={17} /> : <ArrowRight size={17} />}{submitting ? 'Mengirim...' : currentStep === 3 ? 'Kirim aplikasi' : 'Lanjut'}</button>
          </div>
        </div>
        <div className="lg:sticky lg:top-24 lg:self-start"><EventDetailPanel event={event} organizer={event.organizer} /></div>
      </section>
    </div>
  )
}

function Step({ icon, title, description, children }: { icon: React.ReactNode; title: string; description: string; children: React.ReactNode }) {
  return <div><div className="flex items-start gap-3"><span className="flex size-10 items-center justify-center rounded-md bg-accent text-accent-foreground">{icon}</span><div><h2 className="font-heading text-xl font-extrabold">{title}</h2><p className="mt-1 text-sm text-muted-foreground">{description}</p></div></div><div className="mt-6">{children}</div></div>
}
function Review({ label, value }: { label: string; value: string }) { return <div className="rounded-md border bg-background p-4"><p className="text-xs font-bold uppercase text-muted-foreground">{label}</p><p className="mt-1 text-sm leading-6">{value}</p></div> }
function SummaryTile({ label, value }: { label: string; value: string }) { return <div className="rounded-md bg-muted p-4"><p className="text-xs font-bold uppercase text-muted-foreground">{label}</p><p className="mt-1 font-heading text-lg font-extrabold">{value}</p></div> }
