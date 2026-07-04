<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\Organizer;
use App\Models\OrganizerMember;
use App\Models\SavedEvent;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Models\VolunteerEvent;
use App\Models\VolunteerProfile;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $volunteerUser = User::query()->updateOrCreate(
            ['email' => 'nadira@example.com'],
            ['name' => 'Nadira Putri', 'password' => 'password', 'email_verified_at' => now()]
        );

        $organizerUser = User::query()->updateOrCreate(
            ['email' => 'owner@aksaramuda.test'],
            ['name' => 'Dimas Aksara', 'password' => 'password', 'email_verified_at' => now()]
        );

        Category::query()->upsert([
            ['id' => 'education', 'name' => 'Pendidikan', 'description' => 'Mentoring, kelas inspirasi, dan pendampingan belajar.', 'color' => '#101010', 'bg_color' => '#FEDA00'],
            ['id' => 'environment', 'name' => 'Lingkungan', 'description' => 'Aksi bersih, konservasi, dan kampanye hijau.', 'color' => '#FFFFFF', 'bg_color' => '#00913D'],
            ['id' => 'health', 'name' => 'Kesehatan', 'description' => 'Edukasi kesehatan, posko, dan dukungan lapangan.', 'color' => '#FFFFFF', 'bg_color' => '#0284C7'],
            ['id' => 'social', 'name' => 'Sosial', 'description' => 'Distribusi bantuan, inklusi, dan dukungan komunitas.', 'color' => '#FFFFFF', 'bg_color' => '#F97316'],
            ['id' => 'disaster', 'name' => 'Bencana', 'description' => 'Kesiapsiagaan, logistik, dan pemulihan warga.', 'color' => '#FFFFFF', 'bg_color' => '#DC2626'],
            ['id' => 'literacy', 'name' => 'Literasi', 'description' => 'Perpustakaan, donasi buku, dan kelas menulis.', 'color' => '#101010', 'bg_color' => '#FFF7B8'],
            ['id' => 'community', 'name' => 'Komunitas', 'description' => 'Festival warga, kampanye publik, dan aktivasi lokal.', 'color' => '#FFFFFF', 'bg_color' => '#004225'],
        ], ['id']);

        Organizer::query()->upsert([
            ['id' => 'org-aksara-muda', 'name' => 'Aksara Muda Foundation', 'type' => 'Yayasan Pendidikan', 'city' => 'Yogyakarta', 'verified' => true, 'logo_initial' => 'A', 'rating' => 4.9, 'total_events' => 28, 'response_time' => '< 2 jam'],
            ['id' => 'org-hijau-kota', 'name' => 'Hijau Kota Collective', 'type' => 'Komunitas Lingkungan', 'city' => 'Sleman', 'verified' => true, 'logo_initial' => 'H', 'rating' => 4.8, 'total_events' => 19, 'response_time' => '< 1 jam'],
            ['id' => 'org-sehat-bersama', 'name' => 'Sehat Bersama ID', 'type' => 'Gerakan Kesehatan', 'city' => 'Bantul', 'verified' => true, 'logo_initial' => 'S', 'rating' => 4.7, 'total_events' => 14, 'response_time' => '< 3 jam'],
            ['id' => 'org-dapur-warga', 'name' => 'Dapur Warga', 'type' => 'Komunitas Sosial', 'city' => 'Surakarta', 'verified' => false, 'logo_initial' => 'D', 'rating' => 4.6, 'total_events' => 11, 'response_time' => '< 5 jam'],
            ['id' => 'org-siaga-muda', 'name' => 'Siaga Muda Response', 'type' => 'Relawan Bencana', 'city' => 'Magelang', 'verified' => true, 'logo_initial' => 'S', 'rating' => 4.9, 'total_events' => 23, 'response_time' => '< 30 menit'],
        ], ['id']);

        OrganizerMember::query()
            ->where('organizer_id', 'org-aksara-muda')
            ->where('user_id', $volunteerUser->id)
            ->delete();

        OrganizerMember::query()->updateOrCreate(
            ['organizer_id' => 'org-aksara-muda', 'user_id' => $organizerUser->id],
            ['id' => 'mem-aksara-owner', 'role' => 'Owner']
        );

        $events = [
            ['id' => 'evt-001', 'slug' => 'kelas-inspirasi-anak-kali-code', 'title' => 'Kelas Inspirasi Anak Kali Code', 'category_id' => 'education', 'organizer_id' => 'org-aksara-muda', 'location' => 'Rumah Belajar Code', 'city' => 'Yogyakarta', 'mode' => 'Offline', 'date' => '2026-06-06', 'start_time' => '08:00', 'end_time' => '12:00', 'duration_hours' => 4, 'quota' => 40, 'registered' => 27, 'status' => 'Open', 'image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Mengajar literasi dasar, numerasi, dan creative sharing untuk anak bantaran sungai.', 'description' => 'Program kelas akhir pekan untuk membantu anak-anak bantaran Kali Code belajar dengan cara yang ringan, aktif, dan menyenangkan.', 'benefits' => ['Sertifikat 4 jam', 'Mentoring kit', 'Relasi komunitas'], 'skills' => ['Public speaking', 'Mengajar anak', 'Empati'], 'roles' => ['Education Mentor', 'Content & Documentation'], 'impact_target' => '80 anak mendapat sesi belajar interaktif.', 'tags' => ['Sertifikat', 'Mahasiswa', 'Weekend'], 'featured' => true],
            ['id' => 'evt-002', 'slug' => 'bersih-sungai-gajah-wong', 'title' => 'Bersih Sungai Gajah Wong', 'category_id' => 'environment', 'organizer_id' => 'org-hijau-kota', 'location' => 'Taman Gajah Wong Edupark', 'city' => 'Sleman', 'mode' => 'Offline', 'date' => '2026-06-09', 'start_time' => '06:30', 'end_time' => '10:30', 'duration_hours' => 4, 'quota' => 80, 'registered' => 73, 'status' => 'Nearly Full', 'image' => 'https://images.unsplash.com/photo-1618477461853-cf6ed80faba5?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Aksi bersih sungai, audit sampah, dan edukasi pemilahan untuk warga sekitar.', 'description' => 'Relawan akan dibagi menjadi tim cleanup, dokumentasi, dan edukasi warga.', 'benefits' => ['Sertifikat 4 jam', 'Konsumsi', 'Volunteer starter pack'], 'skills' => ['Kerja tim', 'Dokumentasi', 'Komunikasi warga'], 'roles' => ['Field Volunteer', 'Content & Documentation', 'Logistics Crew'], 'impact_target' => '250 kg sampah terpilah dari area sungai.', 'tags' => ['Outdoor', 'Lingkungan', 'Konsumsi'], 'featured' => true],
            ['id' => 'evt-003', 'slug' => 'posko-edukasi-kesehatan-kampus', 'title' => 'Posko Edukasi Kesehatan Kampus', 'category_id' => 'health', 'organizer_id' => 'org-sehat-bersama', 'location' => 'Lapangan Parkir Timur Kampus Terpadu', 'city' => 'Bantul', 'mode' => 'Hybrid', 'date' => '2026-06-12', 'start_time' => '09:00', 'end_time' => '15:00', 'duration_hours' => 6, 'quota' => 32, 'registered' => 18, 'status' => 'Open', 'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Membantu alur registrasi, edukasi pola hidup sehat, dan publikasi posko kampus.', 'description' => 'Kegiatan ini membuka posko edukasi kesehatan dasar untuk mahasiswa dan warga sekitar kampus.', 'benefits' => ['Sertifikat 6 jam', 'Surat tugas', 'Briefing kesehatan dasar'], 'skills' => ['Administrasi', 'Komunikasi', 'Ketelitian'], 'roles' => ['Health Support', 'Content & Documentation', 'Logistics Crew'], 'impact_target' => '500 peserta menerima edukasi kesehatan preventif.', 'tags' => ['Hybrid', 'Kampus', 'Sertifikat'], 'featured' => false],
            ['id' => 'evt-004', 'slug' => 'dapur-hangat-malam-minggu', 'title' => 'Dapur Hangat Malam Minggu', 'category_id' => 'social', 'organizer_id' => 'org-dapur-warga', 'location' => 'Dapur Komunitas Sriwedari', 'city' => 'Surakarta', 'mode' => 'Offline', 'date' => '2026-06-14', 'start_time' => '16:00', 'end_time' => '21:00', 'duration_hours' => 5, 'quota' => 45, 'registered' => 41, 'status' => 'Nearly Full', 'image' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Menyiapkan dan membagikan paket makan hangat untuk pekerja malam dan warga rentan.', 'description' => 'Relawan membantu dapur produksi, packing makanan, distribusi, dan pencatatan penerima manfaat.', 'benefits' => ['Sertifikat 5 jam', 'Makan bersama', 'Transport lokal'], 'skills' => ['Logistik', 'Kerja cepat', 'Empati'], 'roles' => ['Logistics Crew', 'Field Volunteer'], 'impact_target' => '350 paket makanan terdistribusi tepat sasaran.', 'tags' => ['Malam', 'Sosial', 'Logistik'], 'featured' => false],
            ['id' => 'evt-005', 'slug' => 'kelas-siaga-bencana-remaja', 'title' => 'Kelas Siaga Bencana Remaja', 'category_id' => 'disaster', 'organizer_id' => 'org-siaga-muda', 'location' => 'Balai Desa Candimulyo', 'city' => 'Magelang', 'mode' => 'Offline', 'date' => '2026-06-18', 'start_time' => '08:30', 'end_time' => '14:30', 'duration_hours' => 6, 'quota' => 36, 'registered' => 22, 'status' => 'Open', 'image' => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Workshop simulasi evakuasi, tas siaga, dan komunikasi darurat untuk remaja desa.', 'description' => 'Relawan membantu fasilitator menjalankan simulasi, menjaga pos aktivitas, dan mendampingi kelompok.', 'benefits' => ['Sertifikat 6 jam', 'Basic disaster kit', 'Training fasilitator'], 'skills' => ['Fasilitasi', 'Disiplin', 'Kerja tim'], 'roles' => ['Community Facilitator', 'Logistics Crew'], 'impact_target' => '120 remaja memahami prosedur siaga bencana.', 'tags' => ['Training', 'Desa', 'Impact'], 'featured' => true],
            ['id' => 'evt-006', 'slug' => 'perpustakaan-pop-up-kampung', 'title' => 'Perpustakaan Pop-up Kampung', 'category_id' => 'literacy', 'organizer_id' => 'org-aksara-muda', 'location' => 'Pendopo Kampung Karangkajen', 'city' => 'Yogyakarta', 'mode' => 'Offline', 'date' => '2026-06-21', 'start_time' => '10:00', 'end_time' => '15:00', 'duration_hours' => 5, 'quota' => 30, 'registered' => 15, 'status' => 'Open', 'image' => 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Membuka ruang baca sementara, story telling, dan donasi buku anak.', 'description' => 'Perpustakaan pop-up menghadirkan bacaan anak dan remaja di ruang warga.', 'benefits' => ['Sertifikat 5 jam', 'Merch komunitas', 'Portofolio kegiatan'], 'skills' => ['Storytelling', 'Administrasi', 'Kreativitas'], 'roles' => ['Education Mentor', 'Community Facilitator', 'Content & Documentation'], 'impact_target' => '200 buku tersirkulasi dan 70 anak ikut sesi cerita.', 'tags' => ['Literasi', 'Anak', 'Portofolio'], 'featured' => false],
            ['id' => 'evt-007', 'slug' => 'festival-warga-migunani', 'title' => 'Festival Warga Migunani', 'category_id' => 'community', 'organizer_id' => 'org-hijau-kota', 'location' => 'Alun-alun Utara', 'city' => 'Yogyakarta', 'mode' => 'Offline', 'date' => '2026-06-28', 'start_time' => '07:00', 'end_time' => '17:00', 'duration_hours' => 8, 'quota' => 120, 'registered' => 120, 'status' => 'Closed', 'image' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Aktivasi komunitas lintas isu dengan booth edukasi, panggung warga, dan area relawan.', 'description' => 'Festival warga mempertemukan komunitas sosial, lingkungan, pendidikan, dan kesehatan.', 'benefits' => ['Sertifikat 8 jam', 'Meal voucher', 'Networking komunitas'], 'skills' => ['Event handling', 'Komunikasi', 'Problem solving'], 'roles' => ['Field Volunteer', 'Logistics Crew', 'Content & Documentation'], 'impact_target' => '3.000 pengunjung mengenal peluang kontribusi komunitas.', 'tags' => ['Festival', 'Networking', 'Penuh'], 'featured' => false],
            ['id' => 'evt-008', 'slug' => 'mentoring-beasiswa-untuk-sma', 'title' => 'Mentoring Beasiswa untuk SMA', 'category_id' => 'education', 'organizer_id' => 'org-aksara-muda', 'location' => 'Google Meet', 'city' => 'Online', 'mode' => 'Online', 'date' => '2026-07-03', 'start_time' => '19:00', 'end_time' => '21:00', 'duration_hours' => 2, 'quota' => 25, 'registered' => 9, 'status' => 'Open', 'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80', 'short_description' => 'Mendampingi siswa SMA menyusun target kampus, CV, dan esai beasiswa.', 'description' => 'Sesi mentoring online untuk siswa SMA yang sedang menyiapkan beasiswa dan pilihan kampus.', 'benefits' => ['Sertifikat 2 jam', 'Remote friendly', 'Template mentoring'], 'skills' => ['Mentoring', 'Review dokumen', 'Komunikasi online'], 'roles' => ['Education Mentor'], 'impact_target' => '60 siswa mendapat feedback personal untuk persiapan beasiswa.', 'tags' => ['Online', 'Mahasiswa', 'Beasiswa'], 'featured' => false],
        ];

        foreach ($events as $event) {
            VolunteerEvent::query()->updateOrCreate(['id' => $event['id']], $event);
        }

        VolunteerProfile::query()->updateOrCreate(['id' => 'usr-nadira'], ['user_id' => $volunteerUser->id, 'name' => 'Nadira Putri', 'university' => 'Universitas Gadjah Mada', 'major' => 'Ilmu Komunikasi', 'city' => 'Yogyakarta', 'avatar_initials' => 'NP', 'interests' => ['Pendidikan', 'Lingkungan', 'Sosial']]);

        foreach ([
            ['id' => 'app-001', 'event_id' => 'evt-001', 'volunteer_profile_id' => 'usr-nadira', 'role' => 'Education Mentor', 'status' => 'Accepted', 'submitted_at' => '2026-05-11', 'motivation' => 'Saya ingin membantu anak-anak belajar dengan metode yang ringan dan menyenangkan.', 'availability' => ['Sabtu pagi', 'Briefing online malam hari']],
            ['id' => 'app-002', 'event_id' => 'evt-002', 'volunteer_profile_id' => 'usr-nadira', 'role' => 'Field Volunteer', 'status' => 'Submitted', 'submitted_at' => '2026-05-13', 'motivation' => 'Saya tertarik ikut aksi lingkungan yang punya tindak lanjut audit sampah.', 'availability' => ['Minggu pagi', 'Siap outdoor']],
            ['id' => 'app-003', 'event_id' => 'evt-006', 'volunteer_profile_id' => 'usr-nadira', 'role' => 'Content & Documentation', 'status' => 'Completed', 'submitted_at' => '2026-04-18', 'motivation' => 'Saya ingin membuat dokumentasi kegiatan literasi yang bisa dipakai komunitas.', 'availability' => ['Full day']],
            ['id' => 'app-004', 'event_id' => 'evt-008', 'volunteer_profile_id' => 'usr-nadira', 'role' => 'Education Mentor', 'status' => 'Draft', 'submitted_at' => '2026-05-15', 'motivation' => 'Saya pernah menerima beasiswa dan ingin berbagi prosesnya.', 'availability' => ['Jumat malam']],
            ['id' => 'app-005', 'event_id' => 'evt-003', 'volunteer_profile_id' => 'usr-nadira', 'role' => 'Health Support', 'status' => 'Completed', 'submitted_at' => '2026-03-10', 'motivation' => 'Saya membantu administrasi dan edukasi kesehatan.', 'availability' => ['Full day']],
            ['id' => 'app-006', 'event_id' => 'evt-004', 'volunteer_profile_id' => 'usr-nadira', 'role' => 'Logistics Crew', 'status' => 'Completed', 'submitted_at' => '2026-02-14', 'motivation' => 'Saya membantu persiapan dan distribusi makanan.', 'availability' => ['Full day']],
        ] as $application) {
            VolunteerApplication::query()->updateOrCreate(['id' => $application['id']], $application);
        }

        foreach ([
            ['id' => 'crt-001', 'application_id' => 'app-003', 'issued_at' => '2026-04-24', 'credential_id' => 'MGN-2026-LIT-0424', 'hours' => 5],
            ['id' => 'crt-002', 'application_id' => 'app-005', 'issued_at' => '2026-03-18', 'credential_id' => 'MGN-2026-HLT-0318', 'hours' => 6],
            ['id' => 'crt-003', 'application_id' => 'app-006', 'issued_at' => '2026-02-22', 'credential_id' => 'MGN-2026-SOC-0222', 'hours' => 5],
        ] as $certificate) {
            Certificate::query()->updateOrCreate(['id' => $certificate['id']], $certificate);
        }

        foreach (['evt-001', 'evt-002', 'evt-005', 'evt-008'] as $eventId) {
            SavedEvent::query()->updateOrCreate(
                ['event_id' => $eventId, 'volunteer_profile_id' => 'usr-nadira'],
                ['id' => 'sav-'.$eventId]
            );
        }
    }
}
