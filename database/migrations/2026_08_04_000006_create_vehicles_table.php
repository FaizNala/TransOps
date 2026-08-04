<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('category');
            $table->decimal('capacity', 8, 2)->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('ownership_type');
            $table->foreignId('rental_partner_id')->nullable()->constrained('rental_partners')->nullOnDelete();
            $table->date('rental_start_date')->nullable();
            $table->date('rental_end_date')->nullable();
            $table->string('fuel_type');
            $table->decimal('tank_capacity', 6, 2)->nullable();
            $table->decimal('current_odometer', 10, 2)->default(0);
            $table->string('status')->default('available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
