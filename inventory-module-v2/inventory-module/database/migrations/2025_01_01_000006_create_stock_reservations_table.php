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
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('variant_id');

            // Reservation details
            $table->integer('quantity')->comment('Số lượng giữ');

            // Reference (usually order or cart)
            $table->string('reference_type', 50)->comment('Loại: order, cart, flash_sale, deal');
            $table->unsignedBigInteger('reference_id')->comment('ID của order/cart');
            $table->string('reference_code', 100)->nullable()->comment('Mã order/cart');

            // Status
            $table->enum('status', ['active', 'confirmed', 'released', 'expired'])
                ->default('active')
                ->comment('active=đang giữ, confirmed=đã xác nhận (trừ stock), released=đã thả, expired=hết hạn');

            // Timing
            $table->timestamp('expires_at')->nullable()->comment('Thời điểm hết hạn tự động release');
            $table->timestamp('confirmed_at')->nullable()->comment('Thời điểm xác nhận (khi thanh toán)');
            $table->timestamp('released_at')->nullable()->comment('Thời điểm release');

            // Who released (for manual releases)
            $table->unsignedBigInteger('released_by')->nullable();
            $table->text('release_reason')->nullable();

            // Metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Unique constraint - one reservation per reference + variant
            $table->unique(
                ['reference_type', 'reference_id', 'variant_id', 'warehouse_id'],
                'unique_reservation'
            );

            // Foreign keys
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses_v2')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('variants')
                ->onDelete('cascade');

            // Indexes
            $table->index(['warehouse_id', 'variant_id'], 'idx_res_wh_variant');
            $table->index('status', 'idx_res_status');
            $table->index(['status', 'expires_at'], 'idx_res_expires');
            $table->index(['reference_type', 'reference_id'], 'idx_res_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
