<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot table for the many-to-many link: one reservation has many items, one item appears in many reservations.
 * It also stores the item's condition at hand-over (condition_out) and at return (condition_in).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_reservation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->restrictOnDelete();
            $table->string('condition_out', 20)->nullable();  // good, fair, poor
            $table->string('condition_in', 20)->nullable();   // good, fair, poor, damaged
            $table->timestamps();

            $table->unique(['reservation_id', 'equipment_id']); // same item cannot be added twice
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_reservation');
    }
};
