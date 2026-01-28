<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (! Schema::hasTable('orderdetail')) {
            return;
        }

        Schema::table('orderdetail', function (Blueprint $table) {
            if (! Schema::hasColumn('orderdetail', 'productsale_id')) {
                $table->unsignedBigInteger('productsale_id')->nullable()->after('dealsale_id')->comment('ID of ProductSale record for Flash Sale tracking');
                $table->index('productsale_id', 'orderdetail_productsale_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (! Schema::hasTable('orderdetail')) {
            return;
        }

        Schema::table('orderdetail', function (Blueprint $table) {
            if (Schema::hasColumn('orderdetail', 'productsale_id')) {
                $table->dropIndex('orderdetail_productsale_id_index');
                $table->dropColumn('productsale_id');
            }
        });
    }
};
