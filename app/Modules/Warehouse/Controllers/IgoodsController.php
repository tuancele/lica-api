<?php

namespace App\Modules\Warehouse\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Warehouse\WarehouseServiceInterface;
use Illuminate\Support\Facades\Log;

/**
 * IgoodsController (Web)
 * 
 * Quản lý giao diện nhập hàng, đã nâng cấp sử dụng WarehouseService (API V1)
 */
class IgoodsController extends Controller
{
    private WarehouseServiceInterface $warehouseService;

    public function __construct(WarehouseServiceInterface $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Danh sách phiếu nhập (Giao diện mới tải bằng AJAX)
     */
	public function index(Request $request)
    {
        active('warehouse','importgoods');
        return view('Warehouse::import.index');
    }

    /**
     * Form tạo phiếu nhập (Giao diện mới sử dụng API POST)
     */
    public function create()
    {
    	active('warehouse','importgoods');
        return view('Warehouse::import.create');
    }

    /**
     * In phiếu nhập hàng
     */
    public function print($id)
    {
        try {
            $detail = $this->warehouseService->getImportReceipt((int)$id);
            
            $data['detail'] = $detail;
            $data['products'] = $detail->items; // Loaded by service
            $data['receipt_code'] = getImportReceiptCode($detail->id, $detail->created_at);
            $data['vat_invoice'] = getVatInvoiceFromContent($detail->content);
            
            $viewUrl = url('/admin/import-goods/print/' . $detail->id);
            $data['view_url'] = $viewUrl;
            $data['qr_code'] = generateQRCode($viewUrl, 120);

            return view('Warehouse::import.print', $data);
        } catch (\Exception $e) {
            Log::error('Print import receipt failed: ' . $e->getMessage());
            return redirect('/admin/import-goods')->with('error', 'Không tìm thấy phiếu nhập');
        }
    }

    /**
     * Giao diện chỉnh sửa phiếu (Cần bổ sung nếu muốn dùng Web Edit)
     */
    public function edit($id)
    {
        // Hiện tại ưu tiên dùng API, nếu cần Web Edit sẽ build bổ sung
        // Tạm thời redirect sang list hoặc thông báo
        return redirect('/admin/import-goods')->with('info', 'Chức năng sửa đang được cập nhật sang giao diện mới');
    }
}
