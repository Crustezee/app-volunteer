import { categories } from '@/data/categories'
import { events } from '@/data/events'
import { organizers } from '@/data/organizers'

export * from '@/data/categories'
export * from '@/data/events'
export * from '@/data/organizers'
export * from '@/data/profile'

export const featuredEvents = events.filter((event) => event.featured)

export const openEvents = events.filter((event) => event.status !== 'Closed')

export function getEventBySlug(slug: string) {
  return events.find((event) => event.slug === slug)
}

export function getEventById(id: string) {
  return events.find((event) => event.id === id)
}

export function getOrganizerById(id: string) {
  return organizers.find((organizer) => organizer.id === id)
}

export function getCategoryMeta(name: string) {
  return categories.find((category) => category.name === name)
}

export function getRelatedEvents(eventId: string) {
  const current = getEventById(eventId)

  if (!current) {
    return []
  }

  return events
    .filter((event) => event.id !== current.id && event.category === current.category)
    .slice(0, 3)
}

export function getOrganizerEvents(organizerId: string) {
  return events.filter((event) => event.organizerId === organizerId)
}
