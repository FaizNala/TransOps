<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->string('service_type');
            $table->date('scheduled_date')->nullable();
            $table->decimal('scheduled_odometer', 10, 2)->nullable();
            $table->unsignedInteger('recurrence_km')->nullable();
            $table->unsignedInteger('recurrence_months')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
