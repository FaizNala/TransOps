<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->dateTime('refueled_at');
            $table->decimal('odometer', 10, 2);
            $table->decimal('liters', 10, 2);
            $table->decimal('price_per_liter', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->string('station')->nullable();
            $table->boolean('is_full_tank')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'refueled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};
