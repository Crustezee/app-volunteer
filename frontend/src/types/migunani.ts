export type EventCategory =
  | 'Pendidikan'
  | 'Lingkungan'
  | 'Kesehatan'
  | 'Sosial'
  | 'Bencana'
  | 'Literasi'
  | 'Komunitas'

export type EventMode = 'Offline' | 'Online' | 'Hybrid'

export type EventStatus =
  | 'Open'
  | 'Nearly Full'
  | 'Closed'
  | 'Cancelled'
  | 'Completed'

export type CertificateStatus = 'Issued' | 'Revoked'

export type ApplicationStatus =
  | 'Draft'
  | 'Submitted'
  | 'Accepted'
  | 'Waitlisted'
  | 'Rejected'
  | 'Withdrawn'
  | 'Completed'

export type VolunteerRole = string

export type Category = {
  id: string
  name: EventCategory
  description: string
  color: string
  bgColor: string
}

export type Organizer = {
  id: string
  name: string
  type: string
  city: string
  verified: boolean
  logoInitial: string
  rating: number
  totalEvents: number
  responseTime: string
  memberRole?: 'Owner' | 'Admin' | 'Member'
}

export type VolunteerEvent = {
  id: string
  slug: string
  title: string
  categoryId: string
  category: EventCategory
  organizerId: string
  organizer?: Organizer
  location: string
  city: string
  mode: EventMode
  date: string
  startTime: string
  endTime: string
  durationHours: number
  quota: number
  registered: number
  remainingQuota: number
  status: EventStatus
  image: string
  shortDescription: string
  description: string
  benefits: string[]
  skills: string[]
  roles: VolunteerRole[]
  impactTarget: string
  tags: string[]
  featured: boolean
  isSaved: boolean
  myApplication?: VolunteerApplication
  relatedEvents?: VolunteerEvent[]
}

export type VolunteerProfile = {
  id: string
  name: string
  university: string
  major: string
  city: string
  avatarInitials: string
  totalHours?: number
  completedEvents?: number
  certificates?: number
  savedEventIds?: string[]
  interests: EventCategory[]
}

export type VolunteerApplication = {
  id: string
  eventId: string
  volunteerProfileId: string
  role: VolunteerRole
  status: ApplicationStatus
  submittedAt: string
  motivation: string
  availability: string[]
  event?: VolunteerEvent
  volunteerProfile?: VolunteerProfile
  certificates?: Certificate[]
}

export type Certificate = {
  id: string
  applicationId: string
  eventId: string
  issuedAt: string
  credentialId: string
  hours: number
  status: CertificateStatus
  revisionNumber: number
  supersedesCertificateId?: string | null
  replacementCertificateId?: string | null
  replacementCredentialId?: string | null
  revokedAt?: string | null
  revocationReason?: string | null
  snapshot: {
    volunteerName: string
    eventTitle: string
    organizerName: string
    role: string
    eventDate: string
  }
  event?: VolunteerEvent
  volunteerProfile?: VolunteerProfile
}

export type NotificationItem = {
  id: string
  kind: string
  message: string
  data: Record<string, string | number | null>
  readAt?: string | null
  createdAt: string
}

export type PublicCertificateVerification = {
  credentialId: string
  status: CertificateStatus
  isValid: boolean
  revisionNumber: number
  volunteerName: string
  eventTitle: string
  organizerName: string
  role: string
  eventDate: string
  issuedAt: string
  hours: number
  revokedAt?: string | null
  revocationReason?: string | null
  replacementCredentialId?: string | null
}

export type DashboardStat = {
  id: string
  label: string
  value: string
  delta: string
}

export type OrganizerMetric = {
  id: string
  label: string
  value: string
  helper: string
}

export type User = {
  id: number
  name: string
  email: string
}

export type AuthSession = {
  user: User
  volunteerProfile?: VolunteerProfile
  organizers: Organizer[]
  capabilities: {
    volunteer: boolean
    organizer: boolean
    manageOrganizer: boolean
  }
}

export type HomeData = {
  stats: {
    eventCount: number
    availableEvents: number
    totalSlots: number
    totalRegistered: number
    categoryCount: number
    organizerCount: number
  }
  categories: Category[]
  featuredEvents: VolunteerEvent[]
}

export type VolunteerDashboard = {
  profile: VolunteerProfile
  stats: DashboardStat[]
  applications: VolunteerApplication[]
  certificates: Certificate[]
  savedEvents: VolunteerEvent[]
  notifications: NotificationItem[]
}

export type OrganizerDashboard = {
  organizer: Organizer
  metrics: OrganizerMetric[]
  events: VolunteerEvent[]
  applications: VolunteerApplication[]
}

export type PaginationMeta = {
  currentPage: number
  from: number | null
  lastPage: number
  path: string
  perPage: number
  to: number | null
  total: number
}

export type Paginated<T> = {
  data: T[]
  meta: PaginationMeta
  links: {
    first: string | null
    last: string | null
    previous: string | null
    next: string | null
  }
}

export type EventFilters = {
  q?: string
  categoryId?: string
  mode?: EventMode
  status?: EventStatus
  featured?: boolean
  sort?: 'relevance' | 'latest' | 'eventDate' | 'remainingQuota'
  page?: number
  perPage?: number
}

export type EventPayload = {
  title: string
  categoryId: string
  location: string
  city: string
  mode: EventMode
  date: string
  startTime: string
  endTime: string
  quota: number
  description: string
  shortDescription?: string
  image?: string
  benefits: string[]
  skills: string[]
  roles: string[]
  tags?: string[]
}
