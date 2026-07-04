import type {
  Certificate,
  DashboardStat,
  OrganizerMetric,
  VolunteerApplication,
  VolunteerProfile,
} from '@/types/migunani'

export const volunteerProfile: VolunteerProfile = {
  id: 'usr-nadira',
  name: 'Nadira Putri',
  university: 'Universitas Gadjah Mada',
  major: 'Ilmu Komunikasi',
  city: 'Yogyakarta',
  avatarInitials: 'NP',
  totalHours: 86,
  completedEvents: 13,
  certificates: 9,
  savedEventIds: ['evt-001', 'evt-002', 'evt-005', 'evt-008'],
  interests: ['Pendidikan', 'Lingkungan', 'Sosial'],
}

export const volunteerApplications: VolunteerApplication[] = [
  {
    id: 'app-001',
    volunteerProfileId: 'usr-nadira',
    eventId: 'evt-001',
    role: 'Education Mentor',
    status: 'Accepted',
    submittedAt: '2026-05-11',
    motivation:
      'Saya ingin membantu anak-anak belajar dengan metode yang ringan dan menyenangkan.',
    availability: ['Sabtu pagi', 'Briefing online malam hari'],
  },
  {
    id: 'app-002',
    volunteerProfileId: 'usr-nadira',
    eventId: 'evt-002',
    role: 'Field Volunteer',
    status: 'Submitted',
    submittedAt: '2026-05-13',
    motivation:
      'Saya tertarik ikut aksi lingkungan yang punya tindak lanjut audit sampah.',
    availability: ['Minggu pagi', 'Siap outdoor'],
  },
  {
    id: 'app-003',
    volunteerProfileId: 'usr-nadira',
    eventId: 'evt-006',
    role: 'Content & Documentation',
    status: 'Completed',
    submittedAt: '2026-04-18',
    motivation:
      'Saya ingin membuat dokumentasi kegiatan literasi yang bisa dipakai komunitas.',
    availability: ['Full day'],
  },
  {
    id: 'app-004',
    volunteerProfileId: 'usr-nadira',
    eventId: 'evt-008',
    role: 'Education Mentor',
    status: 'Draft',
    submittedAt: '2026-05-15',
    motivation: 'Saya pernah menerima beasiswa dan ingin berbagi prosesnya.',
    availability: ['Jumat malam'],
  },
]

export const certificates: Certificate[] = [
  {
    id: 'crt-001',
    applicationId: 'app-003',
    eventId: 'evt-006',
    issuedAt: '2026-04-24',
    credentialId: 'MGN-2026-LIT-0424',
    hours: 5,
    status: 'Issued',
    revisionNumber: 1,
    snapshot: { volunteerName: 'Nadira Putri', eventTitle: 'Perpustakaan Pop-up Kampung', organizerName: 'Aksara Muda Foundation', role: 'Content & Documentation', eventDate: '2026-06-21' },
  },
  {
    id: 'crt-002',
    applicationId: 'app-002',
    eventId: 'evt-003',
    issuedAt: '2026-03-18',
    credentialId: 'MGN-2026-HLT-0318',
    hours: 6,
    status: 'Issued',
    revisionNumber: 1,
    snapshot: { volunteerName: 'Nadira Putri', eventTitle: 'Posko Edukasi Kesehatan Kampus', organizerName: 'Sehat Bersama ID', role: 'Health Support', eventDate: '2026-06-12' },
  },
  {
    id: 'crt-003',
    applicationId: 'app-001',
    eventId: 'evt-004',
    issuedAt: '2026-02-22',
    credentialId: 'MGN-2026-SOC-0222',
    hours: 5,
    status: 'Issued',
    revisionNumber: 1,
    snapshot: { volunteerName: 'Nadira Putri', eventTitle: 'Dapur Hangat Malam Minggu', organizerName: 'Dapur Warga', role: 'Logistics Crew', eventDate: '2026-06-14' },
  },
]

export const dashboardStats: DashboardStat[] = [
  {
    id: 'hours',
    label: 'Jam kontribusi',
    value: '86',
    delta: '+14 jam bulan ini',
  },
  {
    id: 'events',
    label: 'Event selesai',
    value: '13',
    delta: '3 kategori aktif',
  },
  {
    id: 'certificates',
    label: 'Sertifikat',
    value: '9',
    delta: '2 siap diunduh',
  },
  {
    id: 'impact',
    label: 'Estimasi penerima manfaat',
    value: '1.240',
    delta: 'dari 7 organizer',
  },
]

export const organizerMetrics: OrganizerMetric[] = [
  {
    id: 'active-events',
    label: 'Event aktif',
    value: '7',
    helper: '4 sedang menerima relawan',
  },
  {
    id: 'applicants',
    label: 'Total pendaftar',
    value: '324',
    helper: '+38 dari minggu lalu',
  },
  {
    id: 'fill-rate',
    label: 'Rata-rata keterisian',
    value: '78%',
    helper: 'naik 12% setelah publish',
  },
  {
    id: 'response',
    label: 'Response time',
    value: '1.8j',
    helper: 'median balasan organizer',
  },
]
