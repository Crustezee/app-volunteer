# Migunani Laravel Backend

Backend Laravel untuk aplikasi volunteer Migunani. Database menggunakan SQLite di `database/database.sqlite`.

## Prasyarat PHP

Aktifkan ekstensi PHP berikut sebelum menjalankan migrasi/server:

```txt
pdo_sqlite
sqlite3
dom
xml
xmlwriter
```

Di Ubuntu biasanya:

```bash
sudo apt install php8.3-sqlite3 php8.3-xml
```

## Setup

```bash
cd /home/key/app-volunteer-master/backend
composer install --ignore-platform-reqs
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Health check:

```txt
http://127.0.0.1:8000/api/health
```

## Endpoint

```txt
GET    /api/health
GET    /api/categories
GET    /api/organizers
GET    /api/organizers/{id}
GET    /api/organizers/{id}/events
GET    /api/events
GET    /api/events/{idOrSlug}
POST   /api/events
GET    /api/profile
PATCH  /api/profile/saved-events
GET    /api/applications
POST   /api/applications
PATCH  /api/applications/{id}
GET    /api/certificates
GET    /api/dashboard/volunteer
GET    /api/dashboard/organizer
```
