# Backend QA Audit

Tanggal audit: 2026-07-05

## Ringkasan

Audit mencakup seluruh 40 endpoint API Laravel: auth, public data, volunteer, organizer, admin, certificate, saved event, dan notification. Fokus audit adalah sinkronisasi flow, logic, role/ownership scope, validation, error contract, dan kesesuaian response dengan kebutuhan frontend saat ini.

Hasil akhir:

- Automated regression: `70 passed`, `754 assertions`.
- Black-box smoke test lokal: health `200`, stateless login `419` terkontrol, login SPA berhasil untuk relawan, organizer, dan admin.
- Tidak ada endpoint yang terdeteksi mengembalikan `500` untuk flow auth/session/rate-limit yang diuji.

## Cakupan Endpoint

| Area | Endpoint utama | Status QA |
| --- | --- | --- |
| Auth | `/api/auth/login`, `/api/auth/register`, `/api/auth/me`, `/api/auth/logout` | Pass |
| Public | `/api/health`, `/api/home`, `/api/categories`, `/api/organizers`, `/api/events`, `/api/certificates/verify/{credentialId}` | Pass |
| Volunteer | dashboard, applications, saved events, certificates, apply event | Pass |
| Organizer | dashboard, events, applications, status, check-in, certificates | Pass |
| Admin | dashboard, users, events, organizers | Pass |
| Notification | mark notification read | Pass |

## Temuan yang Diperbaiki

- Login tanpa session SPA sebelumnya bisa jatuh ke server error. Sekarang menghasilkan `419` dengan pesan terkontrol.
- Handler `HttpException` sempat menangkap rate-limit lebih dulu dan mengubah pesan menjadi `Too Many Attempts.`. Urutan handler diperbaiki sehingga `429` kembali memakai pesan API Indonesia yang konsisten.
- QA coverage ditambah untuk:
  - login non-stateful tidak menjadi `500`;
  - endpoint admin hanya bisa diakses admin;
  - response admin sesuai kontrak frontend;
  - notification hanya bisa dibaca oleh pemilik;
  - check-in applicant hanya bisa dilakukan organizer manager dan tetap scoped ke organizer.

## Skenario yang Terverifikasi

- Login satu pintu tanpa `accountType`:
  - `nadira.putri@mail.com` -> volunteer;
  - `bagus.setiawan@mail.com` -> organizer + manageOrganizer;
  - `admin@migunani.id` -> admin.
- Legacy login dengan `accountType` tetap berfungsi dan tetap menolak akun pada area yang salah.
- Protected route mengembalikan `401` jika unauthenticated.
- Wrong role mengembalikan `403`.
- Not found/scoped resource mengembalikan `404`.
- Validation invalid mengembalikan `422`.
- Business conflict mengembalikan `409`.
- Rate-limit mengembalikan `429` dengan kontrak error konsisten.

## Risiko Tersisa

- Frontend masih memiliki beberapa halaman berbasis data lokal; audit ini memverifikasi backend dan kontraknya, bukan wiring penuh semua halaman frontend ke API.
- Endpoint admin saat ini mengembalikan collection penuh tanpa pagination. Ini cukup untuk prototype, tapi perlu pagination/filter server-side sebelum dataset production membesar.
- Notification audit columns ada, tetapi Laravel database notification default tetap mengisi actor sebagai nullable kecuali dibuat custom notification channel.

## Command Verifikasi

```bash
php artisan test
```

Smoke test HTTP dilakukan terhadap backend lokal `http://127.0.0.1:8001` dengan flow:

- `GET /api/health`
- `POST /api/auth/login` tanpa SPA session
- `/sanctum/csrf-cookie` + `POST /api/auth/login` untuk tiga role demo

