<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->unique();                                   // RSV-2026-0001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();            // the borrower
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->index();
            $table->string('status', 20)->default('pending');                           // App\Enums\ReservationStatus
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->text('remarks')->nullable();
            $table->dateTime('overdue_notified_at')->nullable();                        // max one reminder per day
            $table->timestamps();

            // Speeds up the conflict check: "active reservations overlapping this time range".
            $table->index(['status', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
