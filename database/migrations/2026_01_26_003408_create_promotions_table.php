<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->string('code', 100)->unique();
                $table->decimal('value', 15, 2)->default(0);
                $table->string('unit', 20)->nullable()->comment('percent or amount');
                $table->integer('number')->default(0)->comment('Number of uses');
                $table->dateTime('start')->nullable();
                $table->dateTime('end')->nullable();
                $table->decimal('order_sale', 15, 2)->nullable()->comment('Minimum order amount');
                $table->text('endow')->nullable();
                $table->text('content')->nullable();
                $table->string('payment', 50)->nullable();
                $table->smallInteger('status')->default(1);
                $table->integer('sort')->default(0);
                $table->integer('user_id')->nullable();
                $table->timestamps();
                
                $table->index('code');
                $table->index('status');
                $table->index('sort');
            });
        } else {
            // Add missing columns if table exists
            Schema::table('promotions', function (Blueprint $table) {
                if (!Schema::hasColumn('promotions', 'sort')) {
                    $table->integer('sort')->default(0)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
