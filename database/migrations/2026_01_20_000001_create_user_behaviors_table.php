<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserBehaviorsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_behaviors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('session_id', 100)->index();
            $table->integer('user_id')->nullable()->index();
            $table->integer('product_id')->index();
            $table->string('behavior_type', 50)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->integer('duration')->nullable()->default(0);
            // 地理位置
            $table->string('country', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('city', 100)->nullable();
            // 设备信息
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            // 页面信息
            $table->string('page_url', 500)->nullable();
            $table->string('page_title', 255)->nullable();
            // 产品相关信息
            $table->text('product_categories')->nullable();
            $table->integer('product_brand_id')->nullable();
            $table->text('product_ingredients')->nullable();
            $table->text('product_features')->nullable();
            // 用户行为
            $table->integer('scroll_depth')->nullable()->default(0);
            $table->boolean('clicked_product')->default(false);
            $table->boolean('viewed_gallery')->default(false);
            $table->boolean('read_description')->default(false);
            // 会话信息
            $table->integer('session_page_views')->nullable()->default(1);
            $table->timestamp('session_start_time')->nullable();
            $table->timestamps();

            // 索引
            $table->index(['session_id', 'product_id', 'behavior_type']);
            $table->index(['user_id', 'product_id', 'behavior_type']);
            $table->index(['created_at']);
            $table->index(['country', 'region']);
            $table->index(['device_type']);
            $table->index(['product_brand_id']);
            $table->index(['session_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('user_behaviors');
    }
}
