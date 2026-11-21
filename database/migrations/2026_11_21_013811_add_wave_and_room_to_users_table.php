<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->foreignId('wave_id')->nullable()->constrained('waves')->nullOnDelete();
                $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
                $table->rememberToken();
                $table->timestamps();
            });
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('wave_id')->nullable()->after('password')->constrained('waves')->nullOnDelete();
                $table->foreignId('room_id')->nullable()->after('wave_id')->constrained('rooms')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['wave_id']);
                $table->dropForeign(['room_id']);
                $table->dropColumn(['wave_id', 'room_id']);
            });
        }
    }
};