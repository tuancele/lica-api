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
    public function up(): void
    {
        Schema::dropIfExists('inventory_stocks');

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedInteger('variant_id');

            // Stock levels
            $table->integer('physical_stock')->default(0)->comment('Tồn kho vật lý thực tế');
            $table->integer('reserved_stock')->default(0)->comment('Đã giữ cho đơn hàng pending');

            // Computed column for available stock
            $table->integer('available_stock')
                ->storedAs('GREATEST(0, physical_stock - reserved_stock)')
                ->comment('Có thể bán = physical - reserved');

            // Promotional holds
            $table->integer('flash_sale_hold')->default(0)->comment('Giữ cho Flash Sale');
            $table->integer('deal_hold')->default(0)->comment('Giữ cho Deal/Combo');

            // Thresholds
            $table->integer('low_stock_threshold')->default(10)->comment('Ngưỡng cảnh báo sắp hết');
            $table->integer('reorder_point')->default(20)->comment('Điểm đặt hàng lại');

            // Cost tracking
            $table->decimal('average_cost', 15, 2)->default(0)->comment('Giá vốn trung bình');
            $table->decimal('last_cost', 15, 2)->default(0)->comment('Giá nhập gần nhất');

            // Location within warehouse
            $table->string('location_code', 50)->nullable()->comment('Vị trí trong kho: A1-B2-C3');

            // Tracking
            $table->timestamp('last_stock_check')->nullable();
            $table->timestamp('last_movement_at')->nullable();

            $table->timestamps();

            // Unique constraint
            $table->unique(['warehouse_id', 'variant_id'], 'unique_warehouse_variant');

            // Foreign keys
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses_v2')
                ->onDelete('cascade');

            // Indexes for common queries
            $table->index('variant_id', 'idx_inv_variant');
            $table->index('available_stock', 'idx_inv_available');
            $table->index(['physical_stock', 'low_stock_threshold'], 'idx_inv_low_stock');
            $table->index('location_code', 'idx_inv_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
