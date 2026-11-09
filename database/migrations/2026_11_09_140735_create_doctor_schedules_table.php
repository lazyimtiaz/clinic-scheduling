<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            // 0 = Sunday, 1 = Monday, ..., 6 = Saturday (standard ISO/Carbon representation)
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time'); // e.g., 09:00:00
            $table->time('end_time');   // e.g., 17:00:00
            $table->time('break_start')->nullable(); // e.g., 13:00:00 (Lunch)
            $table->time('break_end')->nullable();   // e.g., 14:00:00
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            // A doctor should only have one schedule entry per day of the week
            $table->unique(['doctor_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
