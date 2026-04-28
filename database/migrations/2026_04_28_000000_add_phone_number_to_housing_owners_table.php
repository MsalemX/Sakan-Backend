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
        Schema::table('housing_owners', function (Blueprint $table) {
            if (!Schema::hasColumn('housing_owners', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('id_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('housing_owners', function (Blueprint $table) {
            $table->dropColumn('phone_number');
        });
    }
};
