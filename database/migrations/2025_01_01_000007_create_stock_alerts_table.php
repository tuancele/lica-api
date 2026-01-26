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
        Schema::dropIfExists('stock_alerts');

        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedInteger('variant_id');

            // Alert type
            $table->enum('alert_type', [
                'low_stock',      // Sắp hết hàng
                'out_of_stock',   // Hết hàng
                'overstock',      // Tồn kho quá cao
                'expiring_soon',  // Sắp hết hạn
                'expired',        // Đã hết hạn
                'reorder_point',  // Đạt điểm đặt hàng lại
            ])->comment('Loại cảnh báo');

            // Stock info at alert time
            $table->integer('current_stock')->comment('Tồn kho lúc tạo cảnh báo');
            $table->integer('threshold')->nullable()->comment('Ngưỡng so sánh');

            // Status
            $table->enum('status', ['active', 'acknowledged', 'resolved', 'ignored'])
                ->default('active')
                ->comment('Trạng thái cảnh báo');

            // Actions taken
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            // Notification tracking
            $table->boolean('email_sent')->default(false);
            $table->timestamp('email_sent_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses_v2')
                ->onDelete('cascade');

            // Indexes
            $table->index('status', 'idx_alert_status');
            $table->index('alert_type', 'idx_alert_type');
            $table->index(['warehouse_id', 'status'], 'idx_alert_wh_status');
            $table->index(['variant_id', 'status'], 'idx_alert_variant_status');
            $table->index('created_at', 'idx_alert_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
