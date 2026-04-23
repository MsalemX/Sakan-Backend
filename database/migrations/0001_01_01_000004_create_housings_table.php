<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('housings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('housing_owner_id')->constrained('housing_owners')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->text('conditions');
            $table->decimal('base_price', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('features')->nullable(); // Using text to store JSON or comma-separated features
            $table->integer('capacity');
            $table->integer('remaining_capacity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('housings');
    }
};
