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
        Schema::create('cash_register_closings', function (Blueprint $table) {
            $table->id();
            $table->date('closing_date')->unique();
            $table->foreignId('closed_by')->constrained('users');
            $table->unsignedInteger('total_cash')->default(0);
            $table->unsignedInteger('total_orange_money')->default(0);
            $table->unsignedInteger('total_mtn_momo')->default(0);
            $table->unsignedInteger('total_other')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('total_orders_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_closings');
    }
};
