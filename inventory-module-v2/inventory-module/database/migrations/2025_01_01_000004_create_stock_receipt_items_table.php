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
        Schema::create('stock_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('variant_id');
            
            // Quantity and price
            $table->integer('quantity')->comment('Số lượng');
            $table->decimal('unit_price', 15, 2)->default(0)->comment('Đơn giá');
            $table->decimal('total_price', 15, 2)
                ->storedAs('quantity * unit_price')
                ->comment('Thành tiền');
            
            // Stock snapshot (for audit trail)
            $table->integer('stock_before')->nullable()->comment('Tồn kho trước khi thay đổi');
            $table->integer('stock_after')->nullable()->comment('Tồn kho sau khi thay đổi');
            
            // Batch/Serial tracking (optional for products that need it)
            $table->string('batch_number', 100)->nullable()->comment('Số lô');
            $table->date('manufacturing_date')->nullable()->comment('Ngày sản xuất');
            $table->date('expiry_date')->nullable()->comment('Ngày hết hạn');
            $table->json('serial_numbers')->nullable()->comment('Danh sách serial numbers');
            
            // Quality info
            $table->enum('condition', ['new', 'used', 'damaged', 'refurbished'])
                ->default('new')
                ->comment('Tình trạng hàng');
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('receipt_id')
                ->references('id')
                ->on('stock_receipts')
                ->onDelete('cascade');
            
            $table->foreign('variant_id')
                ->references('id')
                ->on('variants')
                ->onDelete('cascade');

            // Indexes
            $table->index('receipt_id', 'idx_item_receipt');
            $table->index('variant_id', 'idx_item_variant');
            $table->index('batch_number', 'idx_item_batch');
            $table->index('expiry_date', 'idx_item_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_items');
    }
};
