<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFooterBlocksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('footer_blocks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title')->nullable()->comment('Tiêu đề block');
            $table->text('tags')->nullable()->comment('Danh sách tags (JSON)');
            $table->text('links')->nullable()->comment('Danh sách links (JSON)');
            $table->smallInteger('status')->default(1)->comment('Trạng thái: 1=Hiển thị, 0=Ẩn');
            $table->integer('sort')->default(0)->comment('Thứ tự sắp xếp');
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('footer_blocks');
    }
}
