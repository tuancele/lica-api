<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedFooterBlocksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Nếu bảng chưa tồn tại thì bỏ qua
        if (! Schema::hasTable('footer_blocks')) {
            return;
        }

        // Nếu đã có dữ liệu thì không seed nữa
        if (DB::table('footer_blocks')->count() > 0) {
            return;
        }

        $now = now();

        DB::table('footer_blocks')->insert([
            [
                'title' => 'Mỹ Phẩm High-End',
                'tags' => json_encode([]),
                'links' => json_encode([
                    ['text' => 'Sản phẩm cao cấp', 'url' => '/my-pham-cao-cap'],
                    ['text' => 'Thương hiệu nổi tiếng', 'url' => '/thuong-hieu'],
                    ['text' => 'Bộ sưu tập đặc biệt', 'url' => '/bo-suu-tap'],
                ]),
                'status' => 1,
                'sort' => 1,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Chăm Sóc Cá Nhân',
                'tags' => json_encode([]),
                'links' => json_encode([
                    ['text' => 'Băng Vệ Sinh', 'url' => '/bang-ve-sinh'],
                    ['text' => 'Khăn Giấy / Khăn Ướt', 'url' => '/khan-giay'],
                    ['text' => 'Khử Mùi / Làm Thơm Phòng', 'url' => '/khu-mui'],
                    ['text' => 'Dao Cạo Râu', 'url' => '/dao-cao-rau'],
                    ['text' => 'Bọt Cạo Râu', 'url' => '/bot-cao-rau'],
                    ['text' => 'Miếng Dán Nóng', 'url' => '/mieng-dan-nong'],
                    ['text' => 'Mặt Nạ Xông Hơi', 'url' => '/mat-na-xong-hoi'],
                ]),
                'status' => 1,
                'sort' => 2,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Chăm Sóc Cơ Thể',
                'tags' => json_encode([]),
                'links' => json_encode([
                    ['text' => 'Sữa Tắm', 'url' => '/sua-tam'],
                    ['text' => 'Xà Phòng', 'url' => '/xa-phong'],
                    ['text' => 'Tẩy Tế Bào Chết Body', 'url' => '/tay-te-bao-chet'],
                    ['text' => 'Dưỡng Thể', 'url' => '/duong-the'],
                    ['text' => 'Dưỡng Da Tay / Chân', 'url' => '/duong-da-tay-chan'],
                ]),
                'status' => 1,
                'sort' => 3,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Nước Hoa',
                'tags' => json_encode([]),
                'links' => json_encode([
                    ['text' => 'Nước Hoa Nữ', 'url' => '/nuoc-hoa-nu'],
                    ['text' => 'Nước Hoa Nam', 'url' => '/nuoc-hoa-nam'],
                    ['text' => 'Xịt Thơm Toàn Thân', 'url' => '/xit-thom'],
                    ['text' => 'Nước Hoa Vùng Kín', 'url' => '/nuoc-hoa-vung-kin'],
                ]),
                'status' => 1,
                'sort' => 4,
                'user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Không xoá dữ liệu khi rollback để tránh mất dữ liệu thật
    }
}
