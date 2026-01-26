<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarketingCampaignsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Tên chương trình');
            $table->timestamp('start_at')->nullable()->comment('Thời gian bắt đầu');
            $table->timestamp('end_at')->nullable()->comment('Thời gian kết thúc');
            $table->tinyInteger('status')->default(0)->comment('0: Tắt, 1: Bật');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Người tạo');
            $table->timestamps();
        });

        Schema::create('marketing_campaign_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('price', 15, 2)->default(0)->comment('Giá khuyến mại trong chương trình');
            $table->integer('limit')->default(0)->comment('Giới hạn số lượng (nếu cần)');
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('marketing_campaigns')->onDelete('cascade');
            // product_id refers to posts.id where type='product'. 
            // Assuming no foreign key constraint strictly needed here to avoid issues if product deleted differently, 
            // but for data integrity it's good. However, legacy system might have soft deletes or different logic.
            // I will add index for faster lookup.
            $table->index(['campaign_id', 'product_id']);
            $table->index('product_id'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_campaign_products');
        Schema::dropIfExists('marketing_campaigns');
    }
}
