<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles');
            $table->foreignId('service_schedule_id')->nullable()->constrained('service_schedules')->nullOnDelete();
            $table->string('service_type');
            $table->date('serviced_date');
            $table->decimal('odometer', 10, 2);
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('vendor')->nullable();
            $table->string('mechanic')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['vehicle_id', 'serviced_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_logs');
    }
};
