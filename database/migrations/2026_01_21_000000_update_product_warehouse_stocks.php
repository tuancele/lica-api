<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductWarehouseStocks extends Migration
{
    public function up(): void
    {
        Schema::table('product_warehouse', function (Blueprint $table) {
            if (!Schema::hasColumn('product_warehouse', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('warehouse_id');
            }

            if (!Schema::hasColumn('product_warehouse', 'physical_stock')) {
                $table->unsignedBigInteger('physical_stock')->default(0)->after('qty');
            }

            if (!Schema::hasColumn('product_warehouse', 'flash_sale_stock')) {
                $table->unsignedBigInteger('flash_sale_stock')->default(0)->after('physical_stock');
            }

            if (!Schema::hasColumn('product_warehouse', 'deal_stock')) {
                $table->unsignedBigInteger('deal_stock')->default(0)->after('flash_sale_stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_warehouse', function (Blueprint $table) {
            if (Schema::hasColumn('product_warehouse', 'deal_stock')) {
                $table->dropColumn('deal_stock');
            }

            if (Schema::hasColumn('product_warehouse', 'flash_sale_stock')) {
                $table->dropColumn('flash_sale_stock');
            }

            if (Schema::hasColumn('product_warehouse', 'physical_stock')) {
                $table->dropColumn('physical_stock');
            }

            if (Schema::hasColumn('product_warehouse', 'variant_id')) {
                $table->dropColumn('variant_id');
            }
        });
    }
}
