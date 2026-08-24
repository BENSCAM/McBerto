<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('employee_type', 30);
            $table->unsignedBigInteger('employee_id');
            $table->date('work_date');
            $table->time('scheduled_start')->nullable();
            $table->time('actual_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->time('actual_end')->nullable();
            $table->string('status', 30)->default('present');
            $table->unsignedInteger('late_minutes')->default(0);
            $table->boolean('absence_justified')->default(false);
            $table->string('abandoned_post')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('abandonment_severity')->nullable();
            $table->text('abandonment_explanation')->nullable();
            $table->text('comment')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_type', 'employee_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
