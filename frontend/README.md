# Migunani

Migunani adalah prototype frontend marketplace event volunteer. Saya membuat project ini sebagai tampilan web interaktif untuk membantu relawan menemukan event, mendaftar kegiatan, menyimpan riwayat kontribusi, dan melihat sertifikat dummy. Di sisi organizer, Migunani menyediakan tampilan untuk membuat event, memantau applicant, dan melihat performa kegiatan.

Project ini bersifat frontend-only, jadi tidak memakai backend, database, API, atau autentikasi sungguhan.

## Fitur

- Halaman public untuk discover dan explore event volunteer.
- Search, filter, sort, dan toggle grid/list event.
- Detail event dengan informasi jadwal, lokasi, benefit, skill, role, kuota, dan organizer.
- Simulasi role public, volunteer, dan organizer.
- Apply flow multi-step untuk volunteer.
- Dashboard volunteer berisi status pendaftaran, jam kontribusi, impact summary, dan sertifikat dummy.
- Dashboard organizer berisi event yang dikelola, statistik applicant, tabel applicant, dan form create event.
- Desain responsive untuk desktop dan mobile.

## Tech Stack

- React + Vite
- TypeScript
- Tailwind CSS
- React Router
- Lucide React
- Framer Motion
- Static dummy data

## Cara Menjalankan

Kelompok kami menggunakan Node.js 22 untuk project ini.

```bash
nvm use 22
npm install
npm run dev
```

Untuk cek build dan lint:

```bash
npm run build
npm run lint
```

## Struktur Singkat

```txt
src/
  components/   komponen UI reusable
  data/         dummy data event, kategori, organizer, dan profil
  layouts/      layout public dan dashboard
  pages/        halaman utama aplikasi
  routes/       konfigurasi routing
  types/        tipe data TypeScript
```

## Aset

Logo Migunani menggunakan SVG custom. Gambar event menggunakan placeholder dari Unsplash untuk kebutuhan prototype.
