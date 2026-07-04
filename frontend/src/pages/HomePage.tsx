import {
  ArrowRight,
  BadgeCheck,
  Building2,
  CalendarDays,
  CheckCircle2,
  HeartHandshake,
  Search,
  Sparkles,
  TrendingUp,
  Users,
} from 'lucide-react'
import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'

import heroImage from '@/assets/hero.png'
import { CategoryChip, ErrorState, EventCard, LoadingState, StatsCard } from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api } from '@/lib/api'
import { formatNumber } from '@/lib/format'

export function HomePage() {
  const navigate = useNavigate()
  const { session } = useAuth()
  const { data, setData, loading, error, reload } = useApiQuery('home', api.home)
  const [searchQuery, setSearchQuery] = useState('')
  const [savingId, setSavingId] = useState<string | null>(null)
  const [actionError, setActionError] = useState('')

  if (loading && !data) {
    return <LoadingState label="Memuat peluang volunteer..." />
  }

  if (error || !data) {
    return <ErrorState error={error ?? new Error('Data beranda tidak tersedia.')} onRetry={reload} />
  }

  const exploreHref = searchQuery.trim()
    ? `/events?q=${encodeURIComponent(searchQuery.trim())}`
    : '/events'

  async function toggleSaved(eventId: string) {
    if (!session?.capabilities.volunteer) {
      navigate(`/login?next=${encodeURIComponent('/')}`)
      return
    }

    const event = data?.featuredEvents.find((item) => item.id === eventId)
    if (!event) return

    setSavingId(eventId)
    setActionError('')
    try {
      if (event.isSaved) {
        await api.removeSavedEvent(eventId)
      } else {
        await api.saveEvent(eventId)
      }
      setData((current) => current ? {
        ...current,
        featuredEvents: current.featuredEvents.map((item) =>
          item.id === eventId ? { ...item, isSaved: !item.isSaved } : item,
        ),
      } : current)
    } catch (caught) {
      setActionError(caught instanceof Error ? caught.message : 'Event gagal disimpan.')
    } finally {
      setSavingId(null)
    }
  }

  return (
    <div className="space-y-8 pb-20 lg:pb-0">
      <section
        className="relative min-h-[min(760px,calc(100svh-7rem))] overflow-hidden rounded-lg border bg-deep-green bg-cover bg-center text-white shadow-sm"
        style={{ backgroundImage: `linear-gradient(90deg, rgba(0,66,37,.94), rgba(0,66,37,.62)), url(${heroImage})` }}
      >
        <div className="relative flex min-h-[min(760px,calc(100svh-7rem))] max-w-5xl flex-col justify-center p-6 sm:p-10 lg:p-14">
          <span className="inline-flex w-fit items-center gap-2 rounded-full border border-white/20 bg-black/20 px-3 py-1.5 text-sm font-bold backdrop-blur">
            <Sparkles size={16} />
            Marketplace volunteer untuk aksi berdampak
          </span>
          <h1 className="mt-6 max-w-4xl font-heading text-4xl font-extrabold leading-tight sm:text-6xl lg:text-7xl">
            Migunani Volunteer Marketplace
          </h1>
          <p className="mt-5 max-w-2xl text-base leading-8 text-white/80 sm:text-lg">
            Temukan kegiatan sosial, pendidikan, lingkungan, dan komunitas. Daftar,
            pantau status, dan simpan bukti kontribusimu dalam satu tempat.
          </p>

          <form
            className="mt-8 max-w-3xl rounded-lg border border-white/20 bg-black/20 p-2 backdrop-blur"
            onSubmit={(event) => {
              event.preventDefault()
              navigate(exploreHref)
            }}
          >
            <div className="grid gap-2 rounded-md bg-card p-2 text-foreground shadow-sm md:grid-cols-[1fr_auto]">
              <label className="relative block">
                <Search size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                  value={searchQuery}
                  onChange={(event) => setSearchQuery(event.target.value)}
                  placeholder="Cari cleanup, mentoring, kesehatan..."
                  className="h-12 w-full rounded-md border bg-background pl-10 pr-3 text-sm font-semibold outline-none focus:border-primary"
                  aria-label="Cari event volunteer"
                />
              </label>
              <button type="submit" className="inline-flex h-12 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-bold text-primary-foreground transition hover:bg-deep-green">
                Explore event <ArrowRight size={17} />
              </button>
            </div>
          </form>
        </div>
      </section>

      <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <StatsCard label="Event tersedia" value={formatNumber(data.stats.availableEvents)} helper={`${data.stats.eventCount} event terdaftar`} icon={CalendarDays} tone="green" />
        <StatsCard label="Slot relawan" value={formatNumber(data.stats.totalSlots)} helper={`${formatNumber(data.stats.totalRegistered)} pendaftar`} icon={Users} tone="yellow" />
        <StatsCard label="Organizer" value={formatNumber(data.stats.organizerCount)} helper="penyelenggara terdaftar" icon={BadgeCheck} tone="dark" />
        <StatsCard label="Kategori aksi" value={formatNumber(data.stats.categoryCount)} helper="beragam bidang kontribusi" icon={Sparkles} tone="neutral" />
      </section>

      <section className="grid gap-5 rounded-lg border bg-card p-6 shadow-sm lg:grid-cols-[0.85fr_1.15fr] lg:p-8">
        <div className="rounded-lg bg-deep-green p-6 text-primary-foreground">
          <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-sm font-bold"><TrendingUp size={16} /> SDG 8 Focus</span>
          <h2 className="mt-5 font-heading text-3xl font-extrabold md:text-5xl">Decent Work and Economic Growth.</h2>
          <p className="mt-4 text-sm leading-7 text-primary-foreground/78">Pengalaman relawan membangun skill, jejaring, dan portofolio kontribusi yang dapat diverifikasi.</p>
        </div>
        <div className="grid gap-3 md:grid-cols-3">
          <SdgPoint icon={<HeartHandshake size={20} />} title="Skill readiness" description="Pilih role dan asah komunikasi, koordinasi, serta kerja lapangan." />
          <SdgPoint icon={<CheckCircle2 size={20} />} title="Verified portfolio" description="Jam kontribusi dan sertifikat tercatat dari event yang selesai." />
          <SdgPoint icon={<Building2 size={20} />} title="Organizer growth" description="Kelola rekrutmen, applicant, dan keterisian event secara terukur." />
        </div>
      </section>

      <section className="space-y-4">
        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
          <div><p className="text-sm font-bold uppercase text-primary">Kategori</p><h2 className="mt-2 font-heading text-3xl font-extrabold">Mulai dari isu yang kamu peduli.</h2></div>
          <Link to="/events" className="inline-flex h-10 w-fit items-center gap-2 rounded-md border bg-card px-4 text-sm font-bold hover:bg-muted">Semua kategori <ArrowRight size={16} /></Link>
        </div>
        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          {data.categories.map((category) => (
            <Link key={category.id} to={`/events?categoryId=${category.id}`} className="rounded-lg border bg-card p-5 shadow-sm transition hover:border-primary/30 hover:shadow-md">
              <CategoryChip category={category} active />
              <h3 className="mt-5 font-heading text-xl font-extrabold">{category.name}</h3>
              <p className="mt-2 text-sm leading-6 text-muted-foreground">{category.description}</p>
            </Link>
          ))}
        </div>
      </section>

      <section className="space-y-4">
        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
          <div><p className="text-sm font-bold uppercase text-primary">Featured events</p><h2 className="mt-2 font-heading text-3xl font-extrabold">Event pilihan minggu ini.</h2></div>
          <Link to="/events" className="inline-flex h-10 w-fit items-center gap-2 rounded-md bg-primary px-4 text-sm font-bold text-primary-foreground hover:bg-deep-green">Explore semua <ArrowRight size={16} /></Link>
        </div>
        {actionError ? <p className="rounded-md bg-destructive/10 p-3 text-sm font-semibold text-destructive">{actionError}</p> : null}
        <div className="grid gap-5 lg:grid-cols-3">
          {data.featuredEvents.map((event) => (
            <EventCard
              key={event.id}
              event={event}
              organizer={event.organizer}
              saved={event.isSaved}
              saving={savingId === event.id}
              onSavedChange={session?.capabilities.volunteer ? toggleSaved : undefined}
            />
          ))}
        </div>
      </section>
    </div>
  )
}

function SdgPoint({ icon, title, description }: { icon: React.ReactNode; title: string; description: string }) {
  return <article className="rounded-lg border bg-muted p-5"><span className="text-primary">{icon}</span><h3 className="mt-4 font-heading text-lg font-extrabold">{title}</h3><p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p></article>
}
