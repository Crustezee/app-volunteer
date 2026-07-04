<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropUnique(['application_id']);
            $table->string('status')->default('Issued')->after('hours');
            $table->unsignedInteger('revision_number')->default(1)->after('status');
            $table->string('supersedes_certificate_id')->nullable()->after('revision_number');
            $table->timestamp('revoked_at')->nullable()->after('supersedes_certificate_id');
            $table->foreignId('revoked_by')->nullable()->after('revoked_at')->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable()->after('revoked_by');
            $table->string('volunteer_name_snapshot')->nullable()->after('revocation_reason');
            $table->string('event_title_snapshot')->nullable()->after('volunteer_name_snapshot');
            $table->string('organizer_name_snapshot')->nullable()->after('event_title_snapshot');
            $table->string('role_snapshot')->nullable()->after('organizer_name_snapshot');
            $table->date('event_date_snapshot')->nullable()->after('role_snapshot');
            $table->foreign('supersedes_certificate_id')
                ->references('id')
                ->on('certificates')
                ->nullOnDelete();
            $table->unique(['application_id', 'revision_number']);
            $table->index(['status', 'issued_at']);
        });

        DB::table('certificates')
            ->join('volunteer_applications', 'volunteer_applications.id', '=', 'certificates.application_id')
            ->join('volunteer_profiles', 'volunteer_profiles.id', '=', 'volunteer_applications.volunteer_profile_id')
            ->join('volunteer_events', 'volunteer_events.id', '=', 'volunteer_applications.event_id')
            ->join('organizers', 'organizers.id', '=', 'volunteer_events.organizer_id')
            ->select([
                'certificates.id',
                'volunteer_profiles.name as volunteer_name',
                'volunteer_events.title as event_title',
                'volunteer_events.date as event_date',
                'organizers.name as organizer_name',
                'volunteer_applications.role',
            ])
            ->orderBy('certificates.id')
            ->each(function (object $row): void {
                DB::table('certificates')->where('id', $row->id)->update([
                    'volunteer_name_snapshot' => $row->volunteer_name,
                    'event_title_snapshot' => $row->event_title,
                    'organizer_name_snapshot' => $row->organizer_name,
                    'role_snapshot' => $row->role,
                    'event_date_snapshot' => $row->event_date,
                ]);
            });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('certificates', function (Blueprint $table): void {
            $table->dropForeign(['supersedes_certificate_id']);
            $table->dropForeign(['revoked_by']);
            $table->dropUnique(['application_id', 'revision_number']);
            $table->dropIndex(['status', 'issued_at']);
            $table->dropColumn([
                'status',
                'revision_number',
                'supersedes_certificate_id',
                'revoked_at',
                'revoked_by',
                'revocation_reason',
                'volunteer_name_snapshot',
                'event_title_snapshot',
                'organizer_name_snapshot',
                'role_snapshot',
                'event_date_snapshot',
            ]);
            $table->unique('application_id');
        });
    }
};
