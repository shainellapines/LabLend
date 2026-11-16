<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete: the database refuses to delete a category that still has equipment.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('asset_tag')->unique();                // e.g. PRJ-0004 (also used in the QR URL)
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();               // e.g. "Lab 2 cabinet"
            $table->string('condition', 20)->default('good');     // App\Enums\EquipmentCondition
            $table->string('status', 20)->default('available');   // App\Enums\EquipmentStatus
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();                                // deleted_at: "deleted" items stay for history
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
