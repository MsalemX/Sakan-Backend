<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: إضافة حقول Social Login إلى جدول المستخدمين
 * تدعم هذه الحقول تسجيل الدخول عبر Google وأي مزود آخر مستقبلاً.
 */
return new class extends Migration
{
    /**
     * تشغيل الـ Migration: إضافة الأعمدة الجديدة.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // معرّف المستخدم على Google
            $table->string('google_id')->nullable()->unique()->after('profile_image');

            // رابط صورة الملف الشخصي من Google
            $table->text('avatar')->nullable()->after('google_id');

            // مزود تسجيل الدخول: 'google' | 'apple' | null (تسجيل عادي)
            // يسمح بدعم مزودين متعددين مستقبلاً
            $table->string('provider')->nullable()->after('avatar');

            // كلمة المرور الآن اختيارية (لأن مستخدمي Social لا يملكون كلمة مرور)
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * التراجع: حذف الأعمدة المضافة.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar', 'provider']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
