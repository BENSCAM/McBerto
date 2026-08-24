<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('planned_working_days_per_month')->default(26);
            $table->unsignedTinyInteger('planned_working_hours_per_day')->default(8);
            $table->unsignedSmallInteger('simple_late_threshold_minutes')->default(15);
            $table->unsignedSmallInteger('sanctionable_late_threshold_minutes')->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_settings');
    }
};
