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
        // 1. إضافة حقول السكن الناقصة
        Schema::table('housings', function (Blueprint $table) {
            if (!Schema::hasColumn('housings', 'city')) {
                $table->string('city')->nullable()->after('name');
            }
            if (!Schema::hasColumn('housings', 'address')) {
                $table->text('address')->nullable()->after('city');
            }
        });

        // 2. إضافة حقول تواريخ الحجز الناقصة
        Schema::table('booking_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_requests', 'start_date')) {
                $table->date('start_date')->nullable()->after('housing_id');
            }
            if (!Schema::hasColumn('booking_requests', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });

        // 3. إضافة رقم بطاقة المالك
        Schema::table('housing_owners', function (Blueprint $table) {
            if (!Schema::hasColumn('housing_owners', 'id_number')) {
                $table->string('id_number')->nullable()->after('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('housings', function (Blueprint $table) {
            $table->dropColumn(['city', 'address']);
        });

        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('housing_owners', function (Blueprint $table) {
            $table->dropColumn('id_number');
        });
    }
};
