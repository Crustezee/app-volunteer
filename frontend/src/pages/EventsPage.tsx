import { useDeferredValue, useState } from 'react'
import { useSearchParams } from 'react-router-dom'

import { EmptyState, ErrorState, EventCard, FilterBar, LoadingState, PageHeader } from '@/components'
import { useAuth } from '@/context/AuthContext'
import { useApiQuery } from '@/hooks/useApiQuery'
import { api } from '@/lib/api'
import { getActiveOrganizer } from '@/lib/organizer'
import type { EventCategory, EventMode } from '@/types/migunani'

type EventsPageProps = { viewer?: 'public' | 'volunteer' | 'organizer' }
type SortMode = 'relevance' | 'latest' | 'eventDate' | 'remainingQuota'

export function EventsPage({ viewer = 'public' }: EventsPageProps) {
  const { session } = useAuth()
  const [searchParams] = useSearchParams()
  const [search, setSearch] = useState(searchParams.get('q') ?? '')
  const deferredSearch = useDeferredValue(search.trim())
  const [selectedCategory, setSelectedCategory] = useState<EventCategory | 'Semua'>('Semua')
  const [selectedMode, setSelectedMode] = useState<EventMode | 'Semua'>('Semua')
  const [sort, setSort] = useState<SortMode>('relevance')
  const [view, setView] = useState<'grid' | 'list'>('grid')
  const [savingId, setSavingId] = useState<string | null>(null)
  const organizerId = getActiveOrganizer(session)?.id

  const categoriesQuery = useApiQuery('categories', api.categories)
  const categoryId = categoriesQuery.data?.find((item) => item.name === selectedCategory)?.id
  const queryKey = JSON.stringify({ viewer, organizerId, deferredSearch, categoryId, selectedMode, sort })
  const eventsQuery = useApiQuery(
    queryKey,
    () => viewer === 'organizer' && organizerId
      ? api.organizerEvents(organizerId, { q: deferredSearch || undefined, perPage: 50 })
      : api.events({
          q: deferredSearch || undefined,
          categoryId,
          mode: selectedMode === 'Semua' ? undefined : selectedMode,
          sort,
          perPage: 50,
        }),
    viewer !== 'organizer' || Boolean(organizerId),
  )

  const allEvents = eventsQuery.data?.data ?? []
  const visibleEvents = viewer === 'organizer'
    ? allEvents.filter((event) =>
        (selectedCategory === 'Semua' || event.category === selectedCategory)
        && (selectedMode === 'Semua' || event.mode === selectedMode),
      )
    : allEvents
  const detailPathPrefix = viewer === 'volunteer'
    ? '/volunteer/events'
    : viewer === 'organizer'
      ? '/organizer/events'
      : '/events'

  async function toggleSaved(eventId: string) {
    const event = eventsQuery.data?.data.find((item) => item.id === eventId)
    if (!event) return
    setSavingId(eventId)
    try {
      if (event.isSaved) await api.removeSavedEvent(eventId)
      else await api.saveEvent(eventId)
      eventsQuery.setData((current) => current ? {
        ...current,
        data: current.data.map((item) => item.id === eventId ? { ...item, isSaved: !item.isSaved } : item),
      } : current)
    } finally {
      setSavingId(null)
    }
  }

  if ((eventsQuery.loading && !eventsQuery.data) || (categoriesQuery.loading && !categoriesQuery.data)) {
    return <LoadingState label="Memuat daftar event..." />
  }

  if (eventsQuery.error || categoriesQuery.error) {
    return <ErrorState error={eventsQuery.error ?? categoriesQuery.error ?? new Error('Event gagal dimuat.')} onRetry={() => { void eventsQuery.reload(); void categoriesQuery.reload() }} />
  }

  return (
    <div className="space-y-6 pb-20 lg:pb-0">
      <PageHeader
        eyebrow={viewer === 'organizer' ? 'Managed Events' : 'Explore Events'}
        title={viewer === 'organizer' ? 'Event yang dikelola organizer.' : 'Cari event volunteer yang sesuai dengan waktu dan minatmu.'}
        description={viewer === 'organizer' ? 'Cari dan pantau keterisian event milik organisasi.' : 'Gunakan pencarian, kategori, mode, dan urutan untuk menemukan peluang yang relevan.'}
      />

      <FilterBar
        categories={categoriesQuery.data ?? []}
        search={search}
        selectedCategory={selectedCategory}
        selectedMode={selectedMode}
        view={view}
        onSearchChange={setSearch}
        onCategoryChange={setSelectedCategory}
        onModeChange={setSelectedMode}
        onViewChange={setView}
      />

      <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
        <p className="text-sm font-semibold text-muted-foreground">Menampilkan <span className="text-foreground">{visibleEvents.length}</span> event</p>
        {viewer !== 'organizer' ? (
          <select value={sort} onChange={(event) => setSort(event.target.value as SortMode)} className="h-10 w-fit rounded-md border bg-card px-3 text-sm font-bold outline-none focus:border-primary">
            <option value="relevance">Paling relevan</option>
            <option value="latest">Terbaru</option>
            <option value="eventDate">Tanggal event</option>
            <option value="remainingQuota">Kuota tersisa</option>
          </select>
        ) : null}
      </div>

      {visibleEvents.length ? (
        <section className={view === 'grid' ? 'grid gap-5 md:grid-cols-2 xl:grid-cols-3' : 'grid gap-5'}>
          {visibleEvents.map((event) => (
            <EventCard
              key={event.id}
              event={event}
              organizer={event.organizer}
              saved={event.isSaved}
              saving={savingId === event.id}
              onSavedChange={viewer === 'volunteer' ? toggleSaved : undefined}
              detailPathPrefix={detailPathPrefix}
              variant={view}
            />
          ))}
        </section>
      ) : <EmptyState title="Event tidak ditemukan." description="Coba ubah kata kunci, kategori, atau mode kegiatan." />}
    </div>
  )
}
