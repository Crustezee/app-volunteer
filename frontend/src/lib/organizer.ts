import type { AuthSession, Organizer } from '@/types/migunani'

export function canManageOrganizer(organizer: Organizer | undefined) {
  return organizer?.memberRole === 'Owner' || organizer?.memberRole === 'Admin'
}

export function getActiveOrganizer(session: AuthSession | null) {
  return session?.organizers.find(canManageOrganizer) ?? session?.organizers[0]
}
