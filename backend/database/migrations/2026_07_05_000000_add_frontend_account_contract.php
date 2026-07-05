<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('volunteer')->after('password');
            $table->string('status')->default('Active')->after('role');
            $table->string('city')->nullable()->after('status');
            $table->string('avatar_initials', 8)->nullable()->after('city');
        });

        Schema::table('volunteer_applications', function (Blueprint $table): void {
            $table->timestamp('checked_in_at')->nullable()->after('availability');
            $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
        });

        DB::table('users')->where('email', 'owner@aksaramuda.test')->update(['role' => 'organizer']);
        DB::table('users')->where('email', 'admin@migunani.id')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('volunteer_applications', function (Blueprint $table): void {
            $table->dropForeign(['checked_in_by']);
            $table->dropColumn(['checked_in_at', 'checked_in_by']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'status', 'city', 'avatar_initials']);
        });
    }
};
