<?php

use App\Enums\ServiceArea;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('service_area')->default(ServiceArea::Standard->value)->after('price');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('service_area')->default(ServiceArea::Standard->value)->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('service_area');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('service_area');
        });
    }
};
