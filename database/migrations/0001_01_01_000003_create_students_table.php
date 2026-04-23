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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->longText('personal_id_image')->nullable();
            $table->longText('father_id_image')->nullable();
            $table->string('university_name');
            $table->string('major');
            $table->longText('university_card_image')->nullable();
            $table->string('academic_level');
            $table->longText('image')->nullable(); // user profile image?
            $table->string('phone_number');
            $table->string('address');
            $table->string('nationality');
            $table->longText('proof_of_enrollment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
