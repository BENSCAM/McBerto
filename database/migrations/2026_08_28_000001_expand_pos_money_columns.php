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
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('total_amount')->change();
            $table->unsignedBigInteger('amount_given')->nullable()->change();
            $table->unsignedBigInteger('change_due')->nullable()->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_price')->change();
            $table->unsignedBigInteger('subtotal')->change();
        });

        Schema::table('cash_register_closings', function (Blueprint $table) {
            $table->unsignedBigInteger('total_cash')->default(0)->change();
            $table->unsignedBigInteger('total_orange_money')->default(0)->change();
            $table->unsignedBigInteger('total_mtn_momo')->default(0)->change();
            $table->unsignedBigInteger('total_other')->default(0)->change();
            $table->unsignedBigInteger('total_amount')->default(0)->change();
            $table->bigInteger('counted_cash')->nullable()->change();
            $table->bigInteger('variance')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('price')->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('total_amount')->change();
            $table->unsignedInteger('amount_given')->nullable()->change();
            $table->unsignedInteger('change_due')->nullable()->change();
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->unsignedInteger('unit_price')->change();
            $table->unsignedInteger('subtotal')->change();
        });

        Schema::table('cash_register_closings', function (Blueprint $table) {
            $table->unsignedInteger('total_cash')->default(0)->change();
            $table->unsignedInteger('total_orange_money')->default(0)->change();
            $table->unsignedInteger('total_mtn_momo')->default(0)->change();
            $table->unsignedInteger('total_other')->default(0)->change();
            $table->unsignedInteger('total_amount')->default(0)->change();
            $table->integer('counted_cash')->nullable()->change();
            $table->integer('variance')->nullable()->change();
        });
    }
};
