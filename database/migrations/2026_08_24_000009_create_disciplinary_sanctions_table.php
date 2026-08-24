<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_sanctions', function (Blueprint $table) {
            $table->id();
            $table->string('employee_type', 30);
            $table->unsignedBigInteger('employee_id');
            $table->string('fault_type', 30);
            $table->text('description');
            $table->date('fault_date');
            $table->string('sanction_type', 40);
            $table->unsignedInteger('deduction_amount')->default(0);
            $table->foreignId('responsible_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->text('comment')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['fault_date', 'status']);
            $table->index(['employee_type', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_sanctions');
    }
};
