# Kontrak API Migunani

Dokumen ini adalah acuan integrasi frontend dan backend. Frontend cukup memakai endpoint di bawah ini dan tidak perlu mengetahui detail tabel database.

## Base URL

Development lokal:

```text
http://127.0.0.1:8001
```

Contoh production/demo:

```text
https://api.wishmeluck.web.id
```

Frontend utama:

```text
https://wishmeluck.web.id
```

Frontend Vercel saat ini:

```text
https://wishmeluckteam-app-volunteer.vercel.app
```

## Auth

Backend memakai Laravel Sanctum cookie session. Untuk frontend browser, flow login harus:

1. `GET /sanctum/csrf-cookie`
2. `POST /api/auth/login`
3. Request berikutnya memakai cookie session yang sama.

Fetch/axios wajib mengirim cookie:

```ts
fetch(`${API_BASE_URL}/api/auth/me`, {
  credentials: "include",
});
```

Untuk axios:

```ts
axios.defaults.withCredentials = true;
```

Header umum:

```text
Accept: application/json
Content-Type: application/json
```

## Konfigurasi Environment

Backend `.env` untuk frontend Vercel:

```env
APP_URL=https://api.wishmeluck.web.id
FRONTEND_URL=https://wishmeluckteam-app-volunteer.vercel.app
SANCTUM_STATEFUL_DOMAINS=wishmeluckteam-app-volunteer.vercel.app
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
```

Backend `.env` jika memakai domain utama:

```env
APP_URL=https://api.wishmeluck.web.id
FRONTEND_URL=https://wishmeluck.web.id
SANCTUM_STATEFUL_DOMAINS=wishmeluck.web.id
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
```

Frontend env:

```env
VITE_API_BASE_URL=https://api.wishmeluck.web.id
```

Setelah mengubah `.env` backend:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Response Envelope

Response resource tunggal umumnya:

```json
{
  "data": {}
}
```

Response collection paginated umumnya:

```json
{
  "data": [],
  "links": {},
  "meta": {}
}
```

Response error validasi:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["Email atau password tidak valid."]
  }
}
```

Response belum login:

```json
{
  "message": "Unauthenticated."
}
```

Response login tanpa CSRF/session:

```json
{
  "message": "Session SPA belum tersedia. Panggil /sanctum/csrf-cookie dari frontend terlebih dahulu."
}
```

## Akun Demo

```text
Relawan
email: nadira.putri@mail.com
password: prototype123

Organizer
email: bagus.setiawan@mail.com
password: prototype123

Admin
email: admin@migunani.id
password: prototype123
```

## Nilai Enum

Event mode:

```text
Offline
Online
Hybrid
```

Event status:

```text
Open
Nearly Full
Closed
Cancelled
Completed
```

Application status:

```text
Draft
Submitted
Accepted
Waitlisted
Rejected
Withdrawn
Cancelled
Completed
```

Certificate status:

```text
Issued
Revoked
```

## Public API

### Health

```http
GET /api/health
```

Dipakai untuk mengecek backend hidup.

### Home

```http
GET /api/home
```

Mengembalikan data halaman utama: featured events, kategori, organizer, dan ringkasan platform.

### Categories

```http
GET /api/categories
```

Mengembalikan daftar kategori event.

### Organizers

```http
GET /api/organizers
GET /api/organizers/{id}
```

Mengembalikan daftar organizer dan detail organizer.

### Events

```http
GET /api/events
GET /api/events/{idOrSlug}
```

Query params `GET /api/events`:

```text
q
categoryId
mode
status
featured
sort=relevance|latest|eventDate|remainingQuota
perPage
page
```

Contoh:

```http
GET /api/events?q=pantai&mode=Offline&sort=eventDate&perPage=12
```

### Public Certificate Verification

```http
GET /api/certificates/verify/{credentialId}
```

Dipakai untuk halaman publik verifikasi sertifikat. Tidak perlu login.

## Auth API

### Login

```http
POST /api/auth/login
```

Body:

```json
{
  "email": "nadira.putri@mail.com",
  "password": "prototype123"
}
```

`accountType` masih boleh dikirim untuk kompatibilitas lama, tetapi tidak wajib:

```json
{
  "email": "bagus.setiawan@mail.com",
  "password": "prototype123",
  "accountType": "organizer"
}
```

Response penting:

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Nadira Putri",
      "email": "nadira.putri@mail.com",
      "role": "volunteer"
    },
    "volunteerProfile": {},
    "organizers": [],
    "capabilities": {
      "volunteer": true,
      "organizer": false,
      "manageOrganizer": false,
      "admin": false
    }
  }
}
```

Frontend redirect berdasarkan `capabilities`:

```text
admin -> /portal
organizer/manageOrganizer -> /organizer
volunteer -> /volunteer
```

### Register

```http
POST /api/auth/register
```

Body relawan:

```json
{
  "role": "volunteer",
  "name": "Relawan Baru",
  "email": "relawan.baru@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "city": "Yogyakarta",
  "university": "Universitas Contoh",
  "major": "Sistem Informasi",
  "interests": ["Pendidikan", "Lingkungan"]
}
```

Body organizer:

```json
{
  "role": "organizer",
  "name": "Aksi Muda",
  "organizationName": "Aksi Muda",
  "organizationType": "Komunitas",
  "email": "aksi.muda@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "city": "Yogyakarta"
}
```

### Me

```http
GET /api/auth/me
```

Butuh login. Dipakai untuk restore session saat page reload.

### Logout

```http
POST /api/auth/logout
```

Butuh login. Response sukses `204 No Content`.

## Volunteer API

Semua endpoint volunteer butuh login sebagai relawan.

### Dashboard

```http
GET /api/volunteer/dashboard
```

Mengembalikan profil, statistik, event tersimpan, aplikasi terbaru, sertifikat, dan notifikasi.

### Applications

```http
GET /api/volunteer/applications
POST /api/events/{event}/applications
PATCH /api/volunteer/applications/{application}/cancel
```

Query params `GET /api/volunteer/applications`:

```text
status
perPage
page
```

Body apply event:

```json
{
  "role": "Fasilitator",
  "motivation": "Saya ingin berkontribusi dalam kegiatan sosial ini.",
  "availability": "Saya tersedia sepanjang jadwal event."
}
```

Catatan logic:

- Relawan tidak bisa apply event yang sama dua kali.
- Event penuh, selesai, dibatalkan, atau ditutup tidak menerima pendaftaran.
- Saat apply sukses, `registered` event bertambah.
- Relawan hanya bisa cancel application miliknya sendiri dari status `Submitted`, `Waitlisted`, atau `Accepted`.
- Cancel application mengubah status menjadi `Cancelled` dan mengembalikan application terbaru.

### Saved Events

```http
GET /api/volunteer/saved-events
PUT /api/volunteer/saved-events/{event}
DELETE /api/volunteer/saved-events/{event}
```

Dipakai untuk bookmark event.

### Certificates

```http
GET /api/volunteer/certificates
GET /api/volunteer/certificates/{certificate}
GET /api/volunteer/certificates/{certificate}/download
```

Query params `GET /api/volunteer/certificates`:

```text
status
perPage
page
```

Download mengembalikan PDF sertifikat on-demand.

## Organizer API

Semua endpoint organizer butuh login sebagai user yang menjadi member organizer tersebut.

### Dashboard

```http
GET /api/organizers/{organizer}/dashboard
```

Mengembalikan statistik organizer, event, application, certificate, dan notifikasi terkait.

### Organizer Events

```http
GET /api/organizers/{organizer}/events
POST /api/organizers/{organizer}/events
GET /api/organizers/{organizer}/events/{event}
PATCH /api/organizers/{organizer}/events/{event}
```

Query params `GET /api/organizers/{organizer}/events`:

```text
q
status
perPage
page
```

Body create event:

```json
{
  "title": "Aksi Bersih Pantai",
  "categoryId": "cat-lingkungan",
  "location": "Pantai Parangtritis",
  "city": "Yogyakarta",
  "mode": "Offline",
  "date": "2026-08-10",
  "startTime": "08:00",
  "endTime": "12:00",
  "quota": 30,
  "description": "Kegiatan bersih pantai bersama komunitas lokal.",
  "shortDescription": "Bersih pantai bersama komunitas lokal.",
  "image": "https://example.com/image.jpg",
  "benefits": ["Sertifikat", "Relasi komunitas"],
  "skills": ["Komunikasi", "Kerja tim"],
  "roles": ["Koordinator Lapangan", "Dokumentasi"],
  "tags": ["Lingkungan", "Pantai"]
}
```

Body update event boleh partial, misalnya:

```json
{
  "status": "Completed"
}
```

### Organizer Applications

```http
GET /api/organizers/{organizer}/applications
GET /api/organizers/{organizer}/applications/{application}
PATCH /api/organizers/{organizer}/applications/{application}/status
PATCH /api/organizers/{organizer}/applications/{application}/check-in
```

Query params:

```text
eventId
status
q
sort=latest|oldest|status
perPage
page
```

Body update status:

```json
{
  "status": "Accepted"
}
```

Check-in tidak memerlukan body:

```http
PATCH /api/organizers/{organizer}/applications/{application}/check-in
```

Catatan logic:

- Check-in hanya mengisi `checked_in_at` pertama kali.
- Request berikutnya idempotent dan mengembalikan application yang sama.

### Organizer Certificates

```http
GET /api/organizers/{organizer}/certificates
GET /api/organizers/{organizer}/certificates/{certificate}
POST /api/organizers/{organizer}/applications/{application}/certificate
POST /api/organizers/{organizer}/certificates/{certificate}/replacement
PATCH /api/organizers/{organizer}/certificates/{certificate}/revoke
```

Query params `GET /api/organizers/{organizer}/certificates`:

```text
q
eventId
status
issuedFrom
issuedTo
perPage
page
```

Body issue certificate:

```json
{
  "hours": 8,
  "issuedAt": "2026-08-11",
  "notes": "Telah menyelesaikan kegiatan dengan baik."
}
```

Body revoke:

```json
{
  "reason": "Data peserta perlu direvisi."
}
```

Body replacement:

```json
{
  "reason": "Nama relawan perlu koreksi."
}
```

Catatan logic:

- Sertifikat terhubung ke application, sehingga jelas event dan volunteer pemiliknya.
- PDF tidak disimpan permanen; file dibuat on-demand saat download.
- Jika sertifikat direvoke, status menjadi `Revoked` dan reason tersimpan.
- Replacement akan revoke sertifikat lama jika masih aktif, lalu membuat certificate revisi baru dengan `supersedesCertificateId`.

## Admin API

Semua endpoint admin butuh login user dengan `role=admin`.

```http
GET /api/admin/dashboard
GET /api/admin/users
PATCH /api/admin/users/{user}/status
GET /api/admin/events
PATCH /api/admin/events/{event}/status
GET /api/admin/organizers
PATCH /api/admin/organizers/{organizer}/verification
```

`{user}` pada `PATCH /api/admin/users/{user}/status` menerima:

```text
5
usr-5
usr-nadira-frontend
```

Payload update user status:

```json
{
  "status": "Active"
}
```

Allowed user status:

```text
Active
Inactive
Suspended
```

Payload update event status:

```json
{
  "status": "Open"
}
```

Allowed admin event status:

```text
Open
Nearly Full
Closed
```

Payload organizer verification:

```json
{
  "verified": true
}
```

`GET /api/admin/dashboard` mengembalikan:

```text
stats
users
events
organizers
```

Catatan saat ini:

- Endpoint admin masih collection penuh tanpa pagination.
- Untuk demo/lomba masih cukup.
- Untuk production sebaiknya ditambah pagination dan filter.

## Notifications

```http
GET /api/notifications
PATCH /api/notifications/{notification}/read
PATCH /api/notifications/read-all
```

Butuh login. Semua endpoint notification hanya mengakses notifikasi milik user yang sedang login.

Response minimal list:

```json
{
  "data": [
    {
      "id": "notif-001",
      "kind": "accepted",
      "title": "Aplikasi diterima",
      "description": "Kelas Inspirasi siap masuk briefing.",
      "time": "1 menit yang lalu",
      "readAt": null,
      "createdAt": "2026-07-05T10:00:00+00:00"
    }
  ]
}
```

## Checklist Integrasi Frontend

1. Set `VITE_API_BASE_URL` ke URL backend publik.
2. Pastikan semua request memakai `credentials: "include"`.
3. Sebelum login, panggil `/sanctum/csrf-cookie`.
4. Setelah login, panggil `/api/auth/me` untuk validasi session.
5. Redirect user berdasarkan `capabilities`.
6. Untuk organizer, ambil organizer id dari response login `organizers[0].id`.
7. Untuk relawan, jangan izinkan apply jika event sudah applied atau closed.
8. Untuk certificate sharing, arahkan publik ke halaman frontend yang memanggil `/api/certificates/verify/{credentialId}`.

## Smoke Test Manual

Cek backend:

```bash
curl https://api.wishmeluck.web.id/api/health
```

Cek daftar event:

```bash
curl https://api.wishmeluck.web.id/api/events
```

Flow browser:

1. Buka `/login`.
2. Login sebagai relawan.
3. Pastikan masuk dashboard relawan.
4. Buka event detail lalu apply.
5. Logout.
6. Login sebagai organizer.
7. Cek application masuk.
8. Accept application, check-in, lalu issue certificate.
9. Logout.
10. Login sebagai admin.
11. Cek dashboard admin, users, events, organizers.
