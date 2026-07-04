import type {
  ApplicationStatus,
  AuthSession,
  Category,
  Certificate,
  CertificateStatus,
  EventFilters,
  EventPayload,
  EventStatus,
  HomeData,
  Organizer,
  OrganizerDashboard,
  Paginated,
  PublicCertificateVerification,
  VolunteerApplication,
  VolunteerDashboard,
  VolunteerEvent,
} from '@/types/migunani'

const apiBaseUrl = (import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '')

type ApiEnvelope<T> = { data: T }
type ValidationErrors = Record<string, string[]>

export class ApiError extends Error {
  status: number
  errors: ValidationErrors

  constructor(message: string, status: number, errors: ValidationErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  first(field?: string) {
    if (field && this.errors[field]?.[0]) {
      return this.errors[field][0]
    }

    return Object.values(this.errors)[0]?.[0] ?? this.message
  }
}

function endpoint(path: string) {
  return `${apiBaseUrl}${path}`
}

function xsrfToken() {
  const cookie = document.cookie
    .split('; ')
    .find((item) => item.startsWith('XSRF-TOKEN='))

  return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : null
}

async function ensureCsrfCookie() {
  if (xsrfToken()) {
    return
  }

  await fetch(endpoint('/sanctum/csrf-cookie'), {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const method = (options.method ?? 'GET').toUpperCase()

  if (!['GET', 'HEAD'].includes(method)) {
    await ensureCsrfCookie()
  }

  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }

  const token = xsrfToken()
  if (token) {
    headers.set('X-XSRF-TOKEN', token)
  }

  const response = await fetch(endpoint(path), {
    ...options,
    credentials: 'include',
    headers,
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    throw new ApiError(
      body.message ?? 'Permintaan tidak dapat diproses.',
      response.status,
      body.errors ?? {},
    )
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}

function queryString(values: Record<string, string | number | boolean | undefined>) {
  const params = new URLSearchParams()

  Object.entries(values).forEach(([key, value]) => {
    if (value !== undefined && value !== '') {
      params.set(key, String(value))
    }
  })

  const query = params.toString()
  return query ? `?${query}` : ''
}

async function data<T>(path: string, options?: RequestInit) {
  const response = await request<ApiEnvelope<T>>(path, options)
  return response.data
}

export const api = {
  me: () => data<AuthSession>('/api/auth/me'),
  login: (email: string, password: string, accountType: 'volunteer' | 'organizer') =>
    data<AuthSession>('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password, accountType }),
    }),
  logout: () => request<void>('/api/auth/logout', { method: 'POST' }),
  home: () => data<HomeData>('/api/home'),
  categories: () => data<Category[]>('/api/categories'),
  organizers: () => data<Organizer[]>('/api/organizers'),
  events: (filters: EventFilters = {}) =>
    request<Paginated<VolunteerEvent>>(`/api/events${queryString(filters)}`),
  event: (idOrSlug: string) => data<VolunteerEvent>(`/api/events/${idOrSlug}`),
  volunteerDashboard: () => data<VolunteerDashboard>('/api/volunteer/dashboard'),
  apply: (eventId: string, payload: { role: string; motivation: string; availability: string[] }) =>
    data<VolunteerApplication>(`/api/events/${eventId}/applications`, {
      method: 'POST',
      body: JSON.stringify(payload),
    }),
  saveEvent: (eventId: string) =>
    data<VolunteerEvent>(`/api/volunteer/saved-events/${eventId}`, { method: 'PUT' }),
  removeSavedEvent: (eventId: string) =>
    request<void>(`/api/volunteer/saved-events/${eventId}`, { method: 'DELETE' }),
  organizerDashboard: (organizerId: string) =>
    data<OrganizerDashboard>(`/api/organizers/${organizerId}/dashboard`),
  organizerEvents: (organizerId: string, filters: Pick<EventFilters, 'q' | 'status' | 'page' | 'perPage'> = {}) =>
    request<Paginated<VolunteerEvent>>(
      `/api/organizers/${organizerId}/events${queryString(filters)}`,
    ),
  organizerEvent: (organizerId: string, eventId: string) =>
    data<VolunteerEvent>('/api/organizers/' + organizerId + '/events/' + eventId),
  createEvent: (organizerId: string, payload: EventPayload) =>
    data<VolunteerEvent>(`/api/organizers/${organizerId}/events`, {
      method: 'POST',
      body: JSON.stringify(payload),
    }),
  updateEvent: (
    organizerId: string,
    eventId: string,
    payload: Partial<EventPayload> & { status?: EventStatus },
  ) =>
    data<VolunteerEvent>('/api/organizers/' + organizerId + '/events/' + eventId, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    }),
  organizerCertificates: (
    organizerId: string,
    filters: {
      q?: string
      eventId?: string
      status?: CertificateStatus
      issuedFrom?: string
      issuedTo?: string
      page?: number
      perPage?: number
    } = {},
  ) =>
    request<Paginated<Certificate>>(
      '/api/organizers/' + organizerId + '/certificates' + queryString(filters),
    ),
  issueCertificate: (
    organizerId: string,
    applicationId: string,
    payload: { hours: number; issuedAt?: string; supersedesCertificateId?: string },
  ) =>
    data<Certificate>(
      '/api/organizers/' + organizerId + '/applications/' + applicationId + '/certificate',
      { method: 'POST', body: JSON.stringify(payload) },
    ),
  revokeCertificate: (organizerId: string, certificateId: string, reason: string) =>
    data<Certificate>(
      '/api/organizers/' + organizerId + '/certificates/' + certificateId + '/revoke',
      { method: 'PATCH', body: JSON.stringify({ reason }) },
    ),
  verifyCertificate: (credentialId: string) =>
    data<PublicCertificateVerification>(
      '/api/certificates/verify/' + encodeURIComponent(credentialId),
    ),
  markNotificationRead: (notificationId: string) =>
    request<void>('/api/notifications/' + notificationId + '/read', { method: 'PATCH' }),
  organizerApplications: (
    organizerId: string,
    filters: {
      q?: string
      eventId?: string
      status?: ApplicationStatus
      sort?: 'latest' | 'oldest' | 'status'
      page?: number
      perPage?: number
    } = {},
  ) =>
    request<Paginated<VolunteerApplication>>(
      `/api/organizers/${organizerId}/applications${queryString(filters)}`,
    ),
  updateApplicationStatus: (
    organizerId: string,
    applicationId: string,
    status: ApplicationStatus,
  ) =>
    data<VolunteerApplication>(
      `/api/organizers/${organizerId}/applications/${applicationId}/status`,
      { method: 'PATCH', body: JSON.stringify({ status }) },
    ),
  downloadCertificate: async (certificateId: string) => {
    await ensureCsrfCookie()
    const response = await fetch(
      endpoint(`/api/volunteer/certificates/${certificateId}/download`),
      { credentials: 'include', headers: { Accept: 'application/pdf' } },
    )

    if (!response.ok) {
      throw new ApiError('Sertifikat tidak dapat diunduh.', response.status)
    }

    const disposition = response.headers.get('Content-Disposition') ?? ''
    const filename = disposition.match(/filename="?([^";]+)"?/)?.[1] ?? 'sertifikat.pdf'
    const url = URL.createObjectURL(await response.blob())
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = filename
    anchor.click()
    URL.revokeObjectURL(url)
  },
}
