-- =====================================================
-- SQL Script: Reset Stock History và Inventory Stocks
-- Mục đích: Xóa sạch lịch sử kho và reset tất cả số lượng về 0
-- CẢNH BÁO: Script này sẽ XÓA VĨNH VIỄN tất cả dữ liệu lịch sử kho!
-- =====================================================

-- Bước 1: Tắt kiểm tra Foreign Key để có thể TRUNCATE
SET FOREIGN_KEY_CHECKS = 0;

-- Bước 2: TRUNCATE các bảng lịch sử kho (xóa sạch dữ liệu)
TRUNCATE TABLE `stock_receipts`;
TRUNCATE TABLE `stock_receipt_items`;
TRUNCATE TABLE `stock_movements`;
TRUNCATE TABLE `stock_reservations`;

-- Bước 3: UPDATE bảng inventory_stocks - Reset tất cả các cột số lượng về 0
-- Lưu ý: available_stock là GENERATED COLUMN, sẽ tự động tính lại sau khi UPDATE
UPDATE `inventory_stocks`
SET 
    `physical_stock` = 0,
    `reserved_stock` = 0,
    `flash_sale_hold` = 0,
    `deal_hold` = 0,
    `average_cost` = 0.00,
    `last_cost` = 0.00,
    `last_stock_check` = NULL,
    `last_movement_at` = NULL,
    `updated_at` = NOW();

-- Bước 4: Bật lại kiểm tra Foreign Key
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Kiểm tra kết quả (Optional - Uncomment để chạy)
-- =====================================================
-- SELECT 
--     COUNT(*) as total_records,
--     SUM(physical_stock) as total_physical,
--     SUM(reserved_stock) as total_reserved,
--     SUM(flash_sale_hold) as total_flash_sale_hold,
--     SUM(deal_hold) as total_deal_hold
-- FROM inventory_stocks;








