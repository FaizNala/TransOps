<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('requester_id')->constrained('employees');
            $table->foreignId('driver_id')->constrained('drivers');
            $table->text('purpose');
            $table->string('destination');
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->dateTime('departure_at')->nullable();
            $table->dateTime('return_at')->nullable();
            $table->decimal('odometer_start', 10, 2)->nullable();
            $table->decimal('odometer_end', 10, 2)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'status', 'start_at']);
            $table->index('requester_id');
            $table->index('created_by');
            $table->index(['status', 'return_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
