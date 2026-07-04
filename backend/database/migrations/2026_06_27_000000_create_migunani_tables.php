<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('name')->unique();
            $table->text('description');
            $table->string('color');
            $table->string('bg_color');
        });

        Schema::create('organizers', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('name');
            $table->string('type');
            $table->string('city');
            $table->boolean('verified')->default(false);
            $table->string('logo_initial', 4);
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedInteger('total_events')->default(0);
            $table->string('response_time');
        });

        Schema::create('volunteer_profiles', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('university');
            $table->string('major');
            $table->string('city');
            $table->string('avatar_initials', 8);
            $table->json('interests');
        });

        Schema::create('organizer_members', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('organizer_id');
            $table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->unique(['organizer_id', 'user_id']);
        });

        Schema::create('volunteer_events', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->string('organizer_id');
            $table->foreign('organizer_id')->references('id')->on('organizers')->cascadeOnDelete();
            $table->string('location');
            $table->string('city');
            $table->string('mode');
            $table->date('date');
            $table->string('start_time');
            $table->string('end_time');
            $table->unsignedInteger('duration_hours');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('registered')->default(0);
            $table->string('status')->default('Open');
            $table->text('image');
            $table->text('short_description');
            $table->text('description');
            $table->json('benefits');
            $table->json('skills');
            $table->json('roles');
            $table->string('impact_target');
            $table->json('tags');
            $table->boolean('featured')->default(false);
        });

        Schema::create('volunteer_applications', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('event_id');
            $table->foreign('event_id')->references('id')->on('volunteer_events')->cascadeOnDelete();
            $table->string('volunteer_profile_id');
            $table->foreign('volunteer_profile_id')->references('id')->on('volunteer_profiles')->cascadeOnDelete();
            $table->string('role');
            $table->string('status')->default('Submitted');
            $table->date('submitted_at');
            $table->text('motivation');
            $table->json('availability');
            $table->unique(['event_id', 'volunteer_profile_id']);
        });

        Schema::create('saved_events', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('event_id');
            $table->foreign('event_id')->references('id')->on('volunteer_events')->cascadeOnDelete();
            $table->string('volunteer_profile_id');
            $table->foreign('volunteer_profile_id')->references('id')->on('volunteer_profiles')->cascadeOnDelete();
            $table->unique(['event_id', 'volunteer_profile_id']);
        });

        Schema::create('certificates', function (Blueprint $table): void {
            $table->string('id')->primary();
            $this->addAuditColumns($table);
            $table->string('application_id')->unique();
            $table->foreign('application_id')->references('id')->on('volunteer_applications')->cascadeOnDelete();
            $table->date('issued_at');
            $table->string('credential_id')->unique();
            $table->unsignedInteger('hours');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('saved_events');
        Schema::dropIfExists('volunteer_applications');
        Schema::dropIfExists('volunteer_events');
        Schema::dropIfExists('organizer_members');
        Schema::dropIfExists('volunteer_profiles');
        Schema::dropIfExists('organizers');
        Schema::dropIfExists('categories');
    }

    private function addAuditColumns(Blueprint $table): void
    {
        $table->timestamp('created_at')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('updated_at')->nullable();
        $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    }
};
