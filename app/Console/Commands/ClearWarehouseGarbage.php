<?php

declare(strict_types=1);
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearWarehouseGarbage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warehouse:purge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Xóa sạch toàn bộ dữ liệu trong bảng product_warehouse (Warehouse V2)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('⚠️  CẢNH BÁO: Lệnh này sẽ XÓA SẠCH toàn bộ dữ liệu trong bảng product_warehouse!');
        
        if (!$this->confirm('Bạn có chắc chắn muốn tiếp tục?', false)) {
            $this->info('Đã hủy lệnh.');
            return 0;
        }

        try {
            $countBefore = DB::table('product_warehouse')->count();
            
            DB::table('product_warehouse')->truncate();
            
            $this->info("✅ Đã xóa sạch {$countBefore} dòng dữ liệu trong bảng product_warehouse.");
            $this->info('📝 Bây giờ bạn có thể import lại dữ liệu hàng mới.');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Lỗi khi xóa dữ liệu: ' . $e->getMessage());
            return 1;
        }
    }
}
