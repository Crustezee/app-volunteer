<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat {{ $certificate->credential_id }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            background: #f8fafc;
        }
        .page {
            position: relative;
            width: 100%;
            height: 100%;
            padding: 42px;
            border: 18px solid #0f5132;
        }
        .inner {
            height: 100%;
            padding: 42px 58px;
            text-align: center;
            border: 3px solid #f2c94c;
        }
        .brand {
            margin: 0;
            color: #0f5132;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        h1 {
            margin: 18px 0 8px;
            color: #0f5132;
            font-size: 40px;
        }
        .lead {
            margin: 0;
            color: #475569;
            font-size: 16px;
        }
        .name {
            margin: 26px auto 18px;
            padding-bottom: 10px;
            max-width: 620px;
            font-size: 32px;
            font-weight: bold;
            border-bottom: 2px solid #f2c94c;
        }
        .description {
            margin: 0 auto;
            max-width: 720px;
            font-size: 16px;
            line-height: 1.7;
        }
        .event {
            color: #0f5132;
            font-weight: bold;
        }
        .details {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }
        .details td {
            width: 33.333%;
            padding: 12px;
            font-size: 13px;
            border: 1px solid #dbe3e8;
        }
        .label {
            display: block;
            margin-bottom: 5px;
            color: #64748b;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 26px;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="inner">
            <p class="brand">Migunani</p>
            <h1>Sertifikat Volunteer</h1>
            <p class="lead">Sertifikat ini diberikan kepada</p>

            <div class="name">{{ $certificate->volunteer_name_snapshot }}</div>

            <p class="description">
                atas kontribusinya sebagai <strong>{{ $certificate->role_snapshot }}</strong>
                dalam kegiatan <span class="event">{{ $certificate->event_title_snapshot }}</span>
                yang diselenggarakan oleh {{ $certificate->organizer_name_snapshot }}.
            </p>

            <table class="details">
                <tr>
                    <td>
                        <span class="label">Jam kontribusi</span>
                        {{ $certificate->hours }} jam
                    </td>
                    <td>
                        <span class="label">Tanggal terbit</span>
                        {{ $certificate->issued_at }}
                    </td>
                    <td>
                        <span class="label">Credential ID</span>
                        {{ $certificate->credential_id }}
                    </td>
                </tr>
            </table>

            <p class="footer">
                Verifikasi keaslian: {{ rtrim(config('app.url'), '/') }}/verify/{{ $certificate->credential_id }}.
            </p>
        </div>
    </div>
</body>
</html>
