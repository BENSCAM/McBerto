<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->string('employee_type');
            $table->unsignedBigInteger('employee_id');
            $table->date('payroll_month');
            $table->unsignedBigInteger('gross_salary');
            $table->unsignedBigInteger('deduction_amount')->default(0);
            $table->unsignedBigInteger('net_salary');
            $table->timestamp('paid_at');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['employee_type', 'employee_id', 'payroll_month'],
                'salary_payments_employee_month_unique'
            );
            $table->index('payroll_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
