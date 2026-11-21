<?php

use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique(); // Contoh: tkt_98a7sd6f87a6sd...
            $table->string('ticket_number', 30)->unique(); // Contoh: G1-R1-001
            $table->foreignId('wave_id')->constrained('waves')->restrictOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->unsignedInteger('sequence_number'); // Nomor urut global per gelombang (1, 2, 3...)
            $table->string('status', 20)->default(TicketStatus::WAITING->value)->index();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Menjamin nomor urut dalam satu gelombang tidak pernah duplikat meski request bersamaan
            $table->unique(['wave_id', 'sequence_number']);
            // Mempercepat query antrian aktif (FIFO) dan riwayat per petugas
            $table->index(['wave_id', 'room_id', 'status', 'sequence_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};