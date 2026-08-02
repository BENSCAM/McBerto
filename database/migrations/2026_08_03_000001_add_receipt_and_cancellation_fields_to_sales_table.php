<?php

use App\Enums\SaleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->unique()->after('id');
            $table->string('sale_status')->default(SaleStatus::Completed->value)->after('service_area');
            $table->foreignId('canceled_by')->nullable()->after('cash_register_closing_id')->constrained('users')->nullOnDelete();
            $table->timestamp('canceled_at')->nullable()->after('canceled_by');
            $table->string('cancellation_reason')->nullable()->after('canceled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['canceled_by']);
            $table->dropColumn([
                'receipt_number',
                'sale_status',
                'canceled_by',
                'canceled_at',
                'cancellation_reason',
            ]);
        });
    }
};
