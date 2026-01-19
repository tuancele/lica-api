# Kế Hoạch Nâng Cấp Module Quản Lý Kho Hàng Sang RESTful API V1

## 📋 PHÂN TÍCH CHUYÊN SÂU (DEEP DIVE ANALYSIS)

### 1. PHÂN TÍCH LOGIC NGHIỆP VỤ HIỆN TẠI

#### 1.1. Cấu trúc Module Warehouse
Module Warehouse hiện tại bao gồm:

**Controllers:**
- `WarehouseController`: Quản lý tổng quan kho hàng (danh sách sản phẩm, thống kê số lượng, doanh thu)
- `IgoodsController`: Quản lý phiếu nhập hàng (Import Goods)
- `EgoodsController`: Quản lý phiếu xuất hàng (Export Goods)

**Models:**
- `Warehouse`: Bảng `warehouse` - Lưu thông tin phiếu nhập/xuất hàng
- `ProductWarehouse`: Bảng `product_warehouse` - Chi tiết sản phẩm trong phiếu nhập/xuất

**Chức năng chính:**
1. **Quản lý tồn kho:**
   - Xem danh sách sản phẩm trong kho
   - Thống kê số lượng tồn kho theo variant
   - Thống kê doanh thu theo variant

2. **Quản lý phiếu nhập hàng:**
   - Tạo phiếu nhập hàng
   - Sửa phiếu nhập hàng
   - Xem chi tiết phiếu nhập hàng
   - In phiếu nhập hàng
   - Xóa phiếu nhập hàng
   - Tìm kiếm sản phẩm (AJAX)
   - Lấy danh sách phân loại theo sản phẩm
   - Lấy tồn kho của phân loại
   - Quản lý sản phẩm nhập hàng

3. **Quản lý phiếu xuất hàng:**
   - Tạo phiếu xuất hàng
   - Sửa phiếu xuất hàng
   - Xem chi tiết phiếu xuất hàng
   - In phiếu xuất hàng
   - Xóa phiếu xuất hàng
   - Kiểm tra tồn kho trước khi xuất
   - Lấy giá sản phẩm
   - Tìm kiếm sản phẩm (AJAX)
   - Lấy danh sách phân loại theo sản phẩm
   - Lấy tồn kho của phân loại
   - Quản lý sản phẩm xuất hàng

#### 1.2. Database Schema

**Bảng `warehouse`:**
- `id` (int, PK)
- `code` (string, unique) - Mã phiếu nhập/xuất
- `subject` (string) - Tiêu đề/Nội dung
- `content` (text) - Ghi chú (có thể chứa VAT invoice)
- `type` (enum: 'import'|'export') - Loại phiếu
- `user_id` (int, FK -> users.id) - Người tạo
- `created_at` (datetime)
- `updated_at` (datetime)

**Bảng `product_warehouse`:**
- `id` (int, PK)
- `warehouse_id` (int, FK -> warehouse.id)
- `variant_id` (int, FK -> variants.id) - Phân loại sản phẩm
- `price` (decimal) - Giá nhập/xuất
- `qty` (int) - Số lượng
- `type` (enum: 'import'|'export') - Loại phiếu
- `created_at` (datetime)
- `updated_at` (datetime)

**Helper Functions:**
- `countProduct($variantId, $type)`: Tính tổng số lượng nhập/xuất của variant
- `countPrice($variantId, $type)`: Tính tổng giá trị nhập/xuất của variant
- `convertNumberToWords($number)`: Chuyển số thành chữ tiếng Việt
- `getVatInvoiceFromContent($content)`: Lấy số hóa đơn VAT từ content
- `getImportReceiptCode($id, $createdAt)`: Tạo mã phiếu nhập hàng
- `getExportReceiptCode($id, $createdAt)`: Tạo mã phiếu xuất hàng
- `generateQRCode($url, $size)`: Tạo QR code từ URL

#### 1.3. Logic Nghiệp Vụ Quan Trọng

**Nhập hàng:**
- Mã phiếu phải unique
- Phải có ít nhất 1 sản phẩm với variant_id hợp lệ
- Tự động cập nhật tồn kho khi tạo phiếu nhập
- Hỗ trợ VAT invoice (không bắt buộc)
- Tự động tạo mã phiếu nhập hàng (PH-YYYYMMDD-XXXXXX)

**Xuất hàng:**
- Mã phiếu phải unique
- Phải kiểm tra tồn kho trước khi xuất
- Chỉ xuất được số lượng có trong kho
- Tự động cập nhật tồn kho khi tạo phiếu xuất
- Hỗ trợ VAT invoice (không bắt buộc)
- Tự động tạo mã phiếu xuất hàng (PX-YYYYMMDD-XXXXXX)

**Tồn kho:**
- Tồn kho = Tổng nhập - Tổng xuất
- Tính theo variant_id (phân loại sản phẩm)

---

## 🎯 KẾ HOẠCH CHUYỂN ĐỔI SANG RESTful API V1

### 2. CẤU TRÚC API ĐỀ XUẤT

#### 2.1. Namespace và Route Structure

**Namespace:** `App\Modules\ApiAdmin\Controllers`

**Route Prefix:** `admin/api/v1/warehouse`

**Route Groups:**
```
/admin/api/v1/warehouse
├── /inventory              (Tồn kho)
├── /import-receipts        (Phiếu nhập hàng)
├── /export-receipts        (Phiếu xuất hàng)
└── /statistics             (Thống kê)
```

#### 2.2. API Endpoints Chi Tiết

### **A. INVENTORY MANAGEMENT (Quản lý Tồn kho)**

#### 1. GET /admin/api/v1/warehouse/inventory
**Mục tiêu:** Lấy danh sách tồn kho với phân trang và bộ lọc

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): Trang hiện tại, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 10, tối đa 100
- `keyword` (string, optional): Tìm kiếm theo tên sản phẩm hoặc SKU
- `variant_id` (integer, optional): Lọc theo variant ID
- `product_id` (integer, optional): Lọc theo product ID
- `min_stock` (integer, optional): Lọc tồn kho tối thiểu
- `max_stock` (integer, optional): Lọc tồn kho tối đa
- `sort_by` (string, optional): Sắp xếp theo (stock, product_name, variant_name), mặc định 'product_name'
- `sort_order` (string, optional): Thứ tự sắp xếp (asc, desc), mặc định 'asc'

**Validation Logic:**
- `limit`: Phải từ 1-100
- `sort_by`: Chỉ chấp nhận: stock, product_name, variant_name
- `sort_order`: Chỉ chấp nhận: asc, desc

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "variant_id": 1,
      "variant_sku": "SKU-001",
      "variant_option": "500ml",
      "product_id": 10,
      "product_name": "Sản phẩm A",
      "product_image": "https://example.com/image.jpg",
      "import_total": 1000,
      "export_total": 750,
      "current_stock": 250,
      "last_import_date": "2026-01-18T10:30:00.000000Z",
      "last_export_date": "2026-01-19T14:20:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 2. GET /admin/api/v1/warehouse/inventory/{variantId}
**Mục tiêu:** Lấy chi tiết tồn kho của một variant cụ thể

**Tham số đầu vào:**
- `variantId` (integer, required): ID của variant (URL parameter)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "variant_id": 1,
    "variant_sku": "SKU-001",
    "variant_option": "500ml",
    "product_id": 10,
    "product_name": "Sản phẩm A",
    "product_image": "https://example.com/image.jpg",
    "import_total": 1000,
    "export_total": 750,
    "current_stock": 250,
    "import_history": [
      {
        "receipt_id": 100,
        "receipt_code": "PH-20260118-000100",
        "quantity": 500,
        "price": 100000,
        "date": "2026-01-18T10:30:00.000000Z"
      }
    ],
    "export_history": [
      {
        "receipt_id": 200,
        "receipt_code": "PX-20260119-000200",
        "quantity": 250,
        "price": 120000,
        "date": "2026-01-19T14:20:00.000000Z"
      }
    ],
    "last_import_date": "2026-01-18T10:30:00.000000Z",
    "last_export_date": "2026-01-19T14:20:00.000000Z"
  }
}
```

**Trạng thái:** Đang cập nhật

---

### **B. IMPORT RECEIPTS MANAGEMENT (Quản lý Phiếu Nhập hàng)**

#### 3. GET /admin/api/v1/warehouse/import-receipts
**Mục tiêu:** Lấy danh sách phiếu nhập hàng với phân trang và bộ lọc

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): Trang hiện tại, mặc định 1
- `limit` (integer, optional): Số lượng mỗi trang, mặc định 10, tối đa 100
- `keyword` (string, optional): Tìm kiếm theo mã phiếu hoặc nội dung
- `code` (string, optional): Lọc theo mã phiếu chính xác
- `user_id` (integer, optional): Lọc theo người tạo
- `date_from` (date, optional): Lọc từ ngày (format: YYYY-MM-DD)
- `date_to` (date, optional): Lọc đến ngày (format: YYYY-MM-DD)
- `sort_by` (string, optional): Sắp xếp theo (created_at, code, total_value), mặc định 'created_at'
- `sort_order` (string, optional): Thứ tự sắp xếp (asc, desc), mặc định 'desc'

**Validation Logic:**
- `limit`: Phải từ 1-100
- `date_from`, `date_to`: Phải đúng format YYYY-MM-DD
- `sort_by`: Chỉ chấp nhận: created_at, code, total_value

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 100,
      "code": "NH-ORDER001-1705564800",
      "receipt_code": "PH-20260118-000100",
      "subject": "Nhập hàng từ nhà cung cấp ABC",
      "content": "Ghi chú nhập hàng",
      "vat_invoice": "VAT-2026-001",
      "type": "import",
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "total_items": 5,
      "total_quantity": 100,
      "total_value": 10000000,
      "created_at": "2026-01-18T10:30:00.000000Z",
      "updated_at": "2026-01-18T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 50,
    "last_page": 5
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 4. GET /admin/api/v1/warehouse/import-receipts/{id}
**Mục tiêu:** Lấy chi tiết phiếu nhập hàng bao gồm danh sách sản phẩm

**Tham số đầu vào:**
- `id` (integer, required): ID của phiếu nhập hàng (URL parameter)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 100,
    "code": "NH-ORDER001-1705564800",
    "receipt_code": "PH-20260118-000100",
    "subject": "Nhập hàng từ nhà cung cấp ABC",
    "content": "Ghi chú nhập hàng",
    "vat_invoice": "VAT-2026-001",
    "type": "import",
    "user": {
      "id": 1,
      "name": "Admin User"
    },
    "items": [
      {
        "id": 1,
        "variant_id": 10,
        "variant_sku": "SKU-001",
        "variant_option": "500ml",
        "product_id": 5,
        "product_name": "Sản phẩm A",
        "price": 100000,
        "quantity": 20,
        "subtotal": 2000000
      }
    ],
    "total_items": 5,
    "total_quantity": 100,
    "total_value": 10000000,
    "total_value_in_words": "Mười triệu đồng",
    "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=https%3A%2F%2Flica.test%2Fadmin%2Fimport-goods%2Fprint%2F100",
    "view_url": "https://lica.test/admin/import-goods/print/100",
    "created_at": "2026-01-18T10:30:00.000000Z",
    "updated_at": "2026-01-18T10:30:00.000000Z"
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 5. POST /admin/api/v1/warehouse/import-receipts
**Mục tiêu:** Tạo phiếu nhập hàng mới

**Tham số đầu vào (Body - JSON):**
```json
{
  "code": "NH-ORDER001-1705564800",
  "subject": "Nhập hàng từ nhà cung cấp ABC",
  "content": "Ghi chú nhập hàng",
  "vat_invoice": "VAT-2026-001",
  "items": [
    {
      "variant_id": 10,
      "price": 100000,
      "quantity": 20
    }
  ]
}
```

**Validation Logic:**
- `code` (string, required, unique:warehouse,code): Mã phiếu nhập hàng
- `subject` (string, required, max:255): Tiêu đề/Nội dung
- `content` (string, optional): Ghi chú
- `vat_invoice` (string, optional, max:100): Số hóa đơn VAT
- `items` (array, required, min:1): Danh sách sản phẩm
  - `variant_id` (integer, required, exists:variants,id): ID phân loại sản phẩm
  - `price` (numeric, required, min:0): Giá nhập
  - `quantity` (integer, required, min:1): Số lượng

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "Tạo phiếu nhập hàng thành công",
  "data": {
    "id": 100,
    "code": "NH-ORDER001-1705564800",
    "receipt_code": "PH-20260118-000100",
    "subject": "Nhập hàng từ nhà cung cấp ABC",
    "total_items": 1,
    "total_quantity": 20,
    "total_value": 2000000,
    "created_at": "2026-01-18T10:30:00.000000Z"
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 6. PUT /admin/api/v1/warehouse/import-receipts/{id}
**Mục tiêu:** Cập nhật phiếu nhập hàng

**Tham số đầu vào:**
- `id` (integer, required): ID của phiếu nhập hàng (URL parameter)
- Body tương tự như POST, nhưng `code` có thể giữ nguyên hoặc thay đổi (unique:warehouse,code,{id})

**Validation Logic:** Tương tự POST, nhưng:
- `code`: unique:warehouse,code,{id} (cho phép giữ nguyên code cũ)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Cập nhật phiếu nhập hàng thành công",
  "data": {
    "id": 100,
    "code": "NH-ORDER001-1705564800",
    "receipt_code": "PH-20260118-000100",
    "subject": "Nhập hàng từ nhà cung cấp ABC (Đã cập nhật)",
    "total_items": 1,
    "total_quantity": 25,
    "total_value": 2500000,
    "updated_at": "2026-01-18T11:00:00.000000Z"
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 7. DELETE /admin/api/v1/warehouse/import-receipts/{id}
**Mục tiêu:** Xóa phiếu nhập hàng

**Tham số đầu vào:**
- `id` (integer, required): ID của phiếu nhập hàng (URL parameter)

**Validation Logic:**
- Kiểm tra phiếu nhập hàng tồn tại
- Có thể thêm điều kiện: chỉ cho phép xóa nếu chưa có phiếu xuất hàng liên quan

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "message": "Xóa phiếu nhập hàng thành công"
}
```

**Trạng thái:** Đang cập nhật

---

#### 8. GET /admin/api/v1/warehouse/import-receipts/{id}/print
**Mục tiêu:** Lấy thông tin phiếu nhập hàng để in (bao gồm QR code, mã phiếu, tổng bằng chữ)

**Tham số đầu vào:**
- `id` (integer, required): ID của phiếu nhập hàng (URL parameter)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "id": 100,
    "receipt_code": "PH-20260118-000100",
    "qr_code_url": "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=...",
    "view_url": "https://lica.test/admin/import-goods/print/100",
    "print_data": {
      "header": {
        "title": "PHIẾU NHẬP HÀNG",
        "receipt_code": "PH-20260118-000100"
      },
      "info": {
        "code": "NH-ORDER001-1705564800",
        "user_name": "Admin User",
        "subject": "Nhập hàng từ nhà cung cấp ABC",
        "vat_invoice": "VAT-2026-001",
        "date": "10:30:48 18/01/2026"
      },
      "items": [...],
      "total": {
        "value": 10000000,
        "value_in_words": "Mười triệu đồng"
      },
      "signatures": {
        "creator": "Người lập phiếu",
        "receiver": "Người nhận hàng"
      }
    }
  }
}
```

**Trạng thái:** Đang cập nhật

---

### **C. EXPORT RECEIPTS MANAGEMENT (Quản lý Phiếu Xuất hàng)**

#### 9. GET /admin/api/v1/warehouse/export-receipts
**Mục tiêu:** Lấy danh sách phiếu xuất hàng với phân trang và bộ lọc

**Tham số đầu vào:** Tương tự import-receipts

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 200,
      "code": "PX-ORDER001-1705564800",
      "receipt_code": "PX-20260119-000200",
      "subject": "Xuất hàng cho đơn hàng ORDER001",
      "content": "Xuất hàng cho đơn hàng ORDER001",
      "vat_invoice": "",
      "type": "export",
      "user": {
        "id": 1,
        "name": "Admin User"
      },
      "total_items": 3,
      "total_quantity": 50,
      "total_value": 6000000,
      "created_at": "2026-01-19T14:20:00.000000Z",
      "updated_at": "2026-01-19T14:20:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 30,
    "last_page": 3
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 10. GET /admin/api/v1/warehouse/export-receipts/{id}
**Mục tiêu:** Lấy chi tiết phiếu xuất hàng

**Tham số đầu vào:** Tương tự import-receipts/{id}

**Phản hồi mẫu:** Tương tự import-receipts/{id}, nhưng type='export'

**Trạng thái:** Đang cập nhật

---

#### 11. POST /admin/api/v1/warehouse/export-receipts
**Mục tiêu:** Tạo phiếu xuất hàng mới

**Tham số đầu vào (Body - JSON):**
```json
{
  "code": "PX-ORDER001-1705564800",
  "subject": "Xuất hàng cho đơn hàng ORDER001",
  "content": "Ghi chú xuất hàng",
  "vat_invoice": "",
  "items": [
    {
      "variant_id": 10,
      "price": 120000,
      "quantity": 15
    }
  ]
}
```

**Validation Logic:**
- Tương tự import-receipts, nhưng thêm:
  - Kiểm tra tồn kho: `quantity` phải <= tồn kho hiện tại của variant
  - Nếu không đủ tồn kho, trả về lỗi 422 với thông báo chi tiết

**Phản hồi mẫu (201):**
```json
{
  "success": true,
  "message": "Tạo phiếu xuất hàng thành công",
  "data": {
    "id": 200,
    "code": "PX-ORDER001-1705564800",
    "receipt_code": "PX-20260119-000200",
    "subject": "Xuất hàng cho đơn hàng ORDER001",
    "total_items": 1,
    "total_quantity": 15,
    "total_value": 1800000,
    "created_at": "2026-01-19T14:20:00.000000Z"
  }
}
```

**Lỗi khi thiếu tồn kho (422):**
```json
{
  "success": false,
  "message": "Không đủ tồn kho để xuất hàng",
  "errors": {
    "items.0.quantity": [
      "Số lượng vượt quá tồn kho. Tồn kho hiện tại: 10"
    ]
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 12. PUT /admin/api/v1/warehouse/export-receipts/{id}
**Mục tiêu:** Cập nhật phiếu xuất hàng

**Tham số đầu vào:** Tương tự import-receipts/{id}

**Validation Logic:** Tương tự POST export-receipts

**Trạng thái:** Đang cập nhật

---

#### 13. DELETE /admin/api/v1/warehouse/export-receipts/{id}
**Mục tiêu:** Xóa phiếu xuất hàng

**Tham số đầu vào:** Tương tự import-receipts/{id}

**Trạng thái:** Đang cập nhật

---

#### 14. GET /admin/api/v1/warehouse/export-receipts/{id}/print
**Mục tiêu:** Lấy thông tin phiếu xuất hàng để in

**Tham số đầu vào:** Tương tự import-receipts/{id}/print

**Trạng thái:** Đang cập nhật

---

### **D. SUPPORTING ENDPOINTS (Các Endpoint Hỗ trợ)**

#### 15. GET /admin/api/v1/warehouse/products/search
**Mục tiêu:** Tìm kiếm sản phẩm để chọn khi tạo phiếu nhập/xuất

**Tham số đầu vào (Query Params):**
- `q` (string, required, min:2): Từ khóa tìm kiếm (tối thiểu 2 ký tự)
- `limit` (integer, optional): Số lượng kết quả, mặc định 50, tối đa 100

**Validation Logic:**
- `q`: Bắt buộc, tối thiểu 2 ký tự

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Sản phẩm A",
      "slug": "san-pham-a",
      "image": "https://example.com/image.jpg"
    }
  ]
}
```

**Trạng thái:** Đang cập nhật

---

#### 16. GET /admin/api/v1/warehouse/products/{productId}/variants
**Mục tiêu:** Lấy danh sách phân loại của một sản phẩm

**Tham số đầu vào:**
- `productId` (integer, required): ID của sản phẩm (URL parameter)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "sku": "SKU-001",
      "option1_value": "500ml",
      "current_stock": 250
    }
  ]
}
```

**Trạng thái:** Đang cập nhật

---

#### 17. GET /admin/api/v1/warehouse/variants/{variantId}/stock
**Mục tiêu:** Lấy thông tin tồn kho của một variant

**Tham số đầu vào:**
- `variantId` (integer, required): ID của variant (URL parameter)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "variant_id": 10,
    "variant_sku": "SKU-001",
    "variant_option": "500ml",
    "import_total": 1000,
    "export_total": 750,
    "current_stock": 250,
    "price": {
      "import_avg": 100000,
      "export_avg": 120000
    }
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 18. GET /admin/api/v1/warehouse/variants/{variantId}/price
**Mục tiêu:** Lấy giá đề xuất cho variant (giá bán hoặc giá nhập gần nhất)

**Tham số đầu vào:**
- `variantId` (integer, required): ID của variant (URL parameter)
- `type` (string, optional): Loại giá (import|export), mặc định 'export'

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "variant_id": 10,
    "suggested_price": 120000,
    "price_type": "export",
    "last_price": 120000,
    "variant_price": 120000,
    "variant_sale": 0
  }
}
```

**Trạng thái:** Đang cập nhật

---

### **E. STATISTICS (Thống kê)**

#### 19. GET /admin/api/v1/warehouse/statistics/quantity
**Mục tiêu:** Thống kê số lượng tồn kho theo variant

**Tham số đầu vào (Query Params):**
- `page` (integer, optional): Trang hiện tại
- `limit` (integer, optional): Số lượng mỗi trang
- `keyword` (string, optional): Tìm kiếm theo tên sản phẩm hoặc SKU
- `sort_by` (string, optional): Sắp xếp theo (stock, product_name), mặc định 'product_name'
- `sort_order` (string, optional): Thứ tự sắp xếp (asc, desc), mặc định 'asc'

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "variant_id": 10,
      "variant_sku": "SKU-001",
      "variant_option": "500ml",
      "product_id": 5,
      "product_name": "Sản phẩm A",
      "current_stock": 250,
      "import_total": 1000,
      "export_total": 750
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 20. GET /admin/api/v1/warehouse/statistics/revenue
**Mục tiêu:** Thống kê doanh thu theo variant

**Tham số đầu vào:** Tương tự statistics/quantity

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": [
    {
      "variant_id": 10,
      "variant_sku": "SKU-001",
      "variant_option": "500ml",
      "product_id": 5,
      "product_name": "Sản phẩm A",
      "import_value": 100000000,
      "export_value": 90000000,
      "profit": -10000000,
      "import_quantity": 1000,
      "export_quantity": 750
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 150,
    "last_page": 15
  }
}
```

**Trạng thái:** Đang cập nhật

---

#### 21. GET /admin/api/v1/warehouse/statistics/summary
**Mục tiêu:** Tổng hợp thống kê tổng quan kho hàng

**Tham số đầu vào (Query Params):**
- `date_from` (date, optional): Từ ngày (format: YYYY-MM-DD)
- `date_to` (date, optional): Đến ngày (format: YYYY-MM-DD)

**Phản hồi mẫu (200):**
```json
{
  "success": true,
  "data": {
    "total_products": 150,
    "total_variants": 300,
    "total_import_receipts": 50,
    "total_export_receipts": 30,
    "total_import_value": 1000000000,
    "total_export_value": 900000000,
    "total_profit": 100000000,
    "total_import_quantity": 10000,
    "total_export_quantity": 7500,
    "current_stock_value": 250000000,
    "low_stock_items": 15,
    "out_of_stock_items": 5
  }
}
```

**Trạng thái:** Đang cập nhật

---

## 🏗️ KIẾN TRÚC IMPLEMENTATION

### 3. CẤU TRÚC THƯ MỤC

```
app/Modules/ApiAdmin/
├── Controllers/
│   └── WarehouseController.php          (Main controller)
├── Requests/
│   ├── Warehouse/
│   │   ├── StoreImportReceiptRequest.php
│   │   ├── UpdateImportReceiptRequest.php
│   │   ├── StoreExportReceiptRequest.php
│   │   └── UpdateExportReceiptRequest.php
│   └── ...
├── Resources/
│   ├── Warehouse/
│   │   ├── InventoryResource.php
│   │   ├── ImportReceiptResource.php
│   │   ├── ImportReceiptCollection.php
│   │   ├── ExportReceiptResource.php
│   │   ├── ExportReceiptCollection.php
│   │   ├── ReceiptItemResource.php
│   │   └── StatisticsResource.php
│   └── ...
├── Services/
│   └── Warehouse/
│       └── WarehouseService.php        (Business logic layer)
└── routes.php
```

### 4. RESPONSE FORMAT CHUẨN

**Success Response:**
```json
{
  "success": true,
  "message": "Thông báo thành công (optional)",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Thông báo lỗi",
  "errors": {
    "field_name": ["Lỗi validation"]
  }
}
```

**HTTP Status Codes:**
- `200`: Success (GET, PUT, DELETE)
- `201`: Created (POST)
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

---

## 📝 GHI CHÚ QUAN TRỌNG

### 5. BẢO MẬT

- Tất cả API endpoints yêu cầu authentication (`auth:api` middleware)
- Kiểm tra quyền admin trước khi cho phép thao tác
- Validate tất cả input từ client
- Sanitize dữ liệu trước khi lưu database

### 6. PERFORMANCE

- Sử dụng Eloquent eager loading để tránh N+1 query
- Cache các query thống kê phức tạp
- Pagination cho tất cả danh sách
- Index database cho các cột thường xuyên query

### 7. TƯƠNG THÍCH NGƯỢC

- Giữ nguyên logic nghiệp vụ hiện tại
- Không thay đổi database schema
- API mới không ảnh hưởng đến frontend hiện tại
- Có thể chạy song song với hệ thống cũ

---

## ✅ CHECKLIST TRIỂN KHAI

- [ ] Tạo WarehouseController trong ApiAdmin
- [ ] Tạo Request classes cho validation
- [ ] Tạo Resource classes cho response formatting
- [ ] Tạo WarehouseService cho business logic
- [ ] Đăng ký routes trong ApiAdmin/routes.php
- [ ] Implement các endpoints theo thứ tự ưu tiên
- [ ] Viết unit tests cho các API endpoints
- [ ] Cập nhật API_ADMIN_DOCS.md sau mỗi endpoint
- [ ] Test integration với frontend
- [ ] Review code và optimize performance

---

**Trạng thái tổng thể:** Đang cập nhật

**Ngày tạo:** 2026-01-20

**Phiên bản:** 1.0
