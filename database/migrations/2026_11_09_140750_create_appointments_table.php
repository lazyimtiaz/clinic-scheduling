<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique(); // Human-friendly ref like APT-20261109-XYZ
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            // Status lifecycle
            $table->enum('status', [
                'pending',
                'confirmed',
                'completed',
                'cancelled',
                'no_show',
            ])->default('pending');

            $table->text('cancellation_reason')->nullable();
            $table->text('patient_notes')->nullable();
            $table->timestamps();

            // Composite index for ultra-fast overlap queries and scheduling checks
            $table->index(['doctor_id', 'start_time', 'end_time']);
            $table->index(['patient_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
