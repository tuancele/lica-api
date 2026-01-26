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
        Schema::create('stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_code', 50)->unique();

            // Type and status
            $table->enum('type', ['import', 'export', 'transfer', 'adjustment', 'return'])
                ->comment('Loại phiếu: nhập/xuất/chuyển/điều chỉnh/trả hàng');
            $table->enum('status', ['draft', 'pending', 'approved', 'completed', 'cancelled'])
                ->default('draft')
                ->comment('Trạng thái phiếu');

            // Warehouse info
            $table->unsignedBigInteger('from_warehouse_id')->nullable()
                ->comment('Kho xuất (NULL nếu là phiếu nhập)');
            $table->unsignedBigInteger('to_warehouse_id')->nullable()
                ->comment('Kho nhập (NULL nếu là phiếu xuất)');

            // Reference to other entities (order, purchase order, etc.)
            $table->string('reference_type', 50)->nullable()
                ->comment('Loại tham chiếu: order, purchase_order, manual');
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('ID của entity tham chiếu');
            $table->string('reference_code', 100)->nullable()
                ->comment('Mã tham chiếu (order code, PO code)');

            // Supplier/Customer info
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('supplier_name', 255)->nullable();
            $table->string('customer_name', 255)->nullable();

            // Details
            $table->string('subject', 255)->comment('Tiêu đề/Nội dung chính');
            $table->text('content')->nullable()->comment('Ghi chú chi tiết');
            $table->string('vat_invoice', 100)->nullable()->comment('Số hóa đơn VAT');

            // Totals (cached for performance)
            $table->integer('total_items')->default(0)->comment('Số loại sản phẩm');
            $table->integer('total_quantity')->default(0)->comment('Tổng số lượng');
            $table->decimal('total_value', 15, 2)->default(0)->comment('Tổng giá trị');

            // Approval workflow
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('from_warehouse_id')
                ->references('id')
                ->on('warehouses_v2')
                ->onDelete('set null');

            $table->foreign('to_warehouse_id')
                ->references('id')
                ->on('warehouses_v2')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index(['type', 'status'], 'idx_receipt_type_status');
            $table->index('created_at', 'idx_receipt_created');
            $table->index(['reference_type', 'reference_id'], 'idx_receipt_reference');
            $table->index('from_warehouse_id', 'idx_receipt_from_wh');
            $table->index('to_warehouse_id', 'idx_receipt_to_wh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_receipts');
    }
};
