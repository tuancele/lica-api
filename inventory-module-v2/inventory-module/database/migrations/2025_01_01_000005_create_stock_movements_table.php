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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('variant_id');

            // Movement type
            $table->enum('movement_type', [
                'import',           // Nhập kho từ NCC
                'export',           // Xuất kho thủ công
                'sale',             // Bán hàng (đơn hoàn thành)
                'sale_cancel',      // Hủy đơn hàng
                'return',           // Trả hàng
                'transfer_out',     // Chuyển kho đi
                'transfer_in',      // Chuyển kho đến
                'adjustment_plus',  // Điều chỉnh tăng (kiểm kê)
                'adjustment_minus', // Điều chỉnh giảm (kiểm kê)
                'reserve',          // Giữ hàng cho order
                'release',          // Thả hàng đã giữ
                'flash_sale_hold',  // Giữ cho flash sale
                'flash_sale_release', // Thả flash sale
                'deal_hold',        // Giữ cho deal
                'deal_release',     // Thả deal
                'damage',           // Hàng hỏng
                'lost',             // Mất mát
                'found',            // Tìm lại được
                'initial',          // Nhập tồn đầu kỳ
            ])->comment('Loại biến động');

            // Quantity (positive for increase, negative for decrease)
            $table->integer('quantity')->comment('Số lượng thay đổi (+ tăng, - giảm)');

            // Stock snapshot BEFORE and AFTER
            $table->integer('physical_before')->comment('Tồn vật lý TRƯỚC');
            $table->integer('physical_after')->comment('Tồn vật lý SAU');
            $table->integer('reserved_before')->default(0)->comment('Đang giữ TRƯỚC');
            $table->integer('reserved_after')->default(0)->comment('Đang giữ SAU');
            $table->integer('available_before')->comment('Có thể bán TRƯỚC');
            $table->integer('available_after')->comment('Có thể bán SAU');

            // Reference to source entity
            $table->string('reference_type', 50)->nullable()
                ->comment('Loại nguồn: receipt, order, reservation, adjustment');
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID của entity nguồn');
            $table->string('reference_code', 100)->nullable()
                ->comment('Mã của entity nguồn');

            // Additional info
            $table->text('reason')->nullable()->comment('Lý do/Ghi chú');
            $table->json('metadata')->nullable()->comment('Dữ liệu bổ sung');

            // Cost tracking
            $table->decimal('unit_cost', 15, 2)->nullable()->comment('Giá vốn đơn vị');
            $table->decimal('total_cost', 15, 2)->nullable()->comment('Tổng giá vốn');

            // Tracking who did what
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Foreign keys
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses_v2')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('variants')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for common queries
            $table->index(['warehouse_id', 'variant_id'], 'idx_mov_wh_variant');
            $table->index('movement_type', 'idx_mov_type');
            $table->index(['reference_type', 'reference_id'], 'idx_mov_reference');
            $table->index('created_at', 'idx_mov_created');
            $table->index(['variant_id', 'created_at'], 'idx_mov_variant_date');
            $table->index('created_by', 'idx_mov_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
