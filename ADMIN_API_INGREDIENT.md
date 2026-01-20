## Kế hoạch nâng cấp Admin Ingredient lên API

- **Mục tiêu:** Thay thế giao diện `/admin/dictionary/ingredient` bằng REST API tại tiền tố `admin/api`, giữ nguyên logic business (CRUD, bulk action, status toggle, crawl Paula’s Choice, clear cache `ingredient_paulas_active_list`) và quan hệ với Category/Benefit/Rate. **Hiện trạng:** Blade giao diện đã chuyển hoàn toàn sang gọi Admin API.
- **Phạm vi:** IngredientPaulas + IngredientCategory + IngredientBenefit + IngredientRate.
- **Yêu cầu bắt buộc:**
  - Kiểm tra sâu logic cũ `IngredientController` (đã đọc) và giữ nguyên hành vi: bulk action `0=hide,1=show,2=delete`.
  - Slug duy nhất khi tạo/cập nhật.
  - Cache clear sau mọi thao tác ghi.
  - Crawl phải `set_time_limit(0)` và bắt timeout Guzzle, trả thông điệp cụ thể.
  - JSON response chuẩn `{success, message?, data?, pagination?}`.

### Endpoint dự kiến
- `GET /admin/api/ingredients` – list + filter `keyword,status,cat_id[],benefit_id[],rate_id,limit`.
- `GET /admin/api/ingredients/{id}` – chi tiết.
- `POST /admin/api/ingredients` – tạo mới (validate slug unique, cat/benefit/rate tồn tại).
- `PUT /admin/api/ingredients/{id}` – cập nhật.
- `DELETE /admin/api/ingredients/{id}` – xóa.
- `PATCH /admin/api/ingredients/{id}/status` – đổi trạng thái.
- `POST /admin/api/ingredients/bulk-action` – action=0/1/2.
- `GET /admin/api/ingredients/crawl/summary` – trả total/page từ Paula’s Choice.
- `POST /admin/api/ingredients/crawl/run` – chạy crawl theo offset, an toàn timeout.
- Category/Benefit/Rate:
  - `GET /admin/api/ingredient-categories|benefits|rates`
  - `POST /...` (create), `PUT /.../{id}` (update), `DELETE /.../{id}`, `PATCH /.../{id}/status`, `POST /.../bulk-action` (reuse 0/1/2).

### Thiết kế lớp
- **Requests:** `StoreIngredientRequest`, `UpdateIngredientRequest`, `IngredientStatusRequest`, `IngredientBulkActionRequest`, `IngredientCrawlRunRequest`; tương tự cho Category/Benefit/Rate (`DictionaryItemRequest`, `DictionaryStatusRequest`, `DictionaryBulkActionRequest`).
- **Resources:** `IngredientResource` (kèm rate obj, categories/benefits array), `IngredientCollection`; `DictionaryItemResource` dùng chung.
- **Service:** `IngredientAdminService` (index, show, create, update, delete, status, bulk, crawlSummary, crawlRun, helper parse detail, map categories/benefits/rate).
- **Controller:** `App\Modules\ApiAdmin\Controllers\IngredientController` gọi service + resources, try/catch trả JSON lỗi.
- **Routes:** đăng ký tại `app/Modules/ApiAdmin/routes.php` với prefix `admin/api`.

### Validation chính
- name required 1-250, slug required unique `ingredient_paulas,slug,{id}`, status in 0/1.
- rate_id exists:ingredient_rate,id; cat_id[] exists:ingredient_category,id; benefit_id[] exists:ingredient_benefit,id.
- bulk checklist array exists; action in 0/1/2.
- crawl offset integer >=0.

### Crawl lưu ý
- `@set_time_limit(0);`
- Dùng `GuzzleHttp\Client` timeout cấu hình (vd 20s), bắt `ConnectException|RequestException` và trả message rõ.
- List URL: `https://www.paulaschoice.com/ingredient-dictionary?start={offset}&sz=2000&ajax=true`
- Detail URL: `https://www.paulaschoice.com{url}&ajax=true`
- Parse giống logic cũ: description texts cuối phần, references, disclaimer, glance (keyPoints).

### Cache & logging
- Sau create/update/delete/status/bulk/crawl: `Cache::forget('ingredient_paulas_active_list');`
- Log lỗi với method + payload khi catch exception.

### Tài liệu
- Cập nhật `API_ADMIN_DOCS.md` thêm mục Ingredient Admin API với mô tả, params, response mẫu, trạng thái Hoàn thành.

### Kiểm thử nhanh
- Unit/Feature: CRUD thành công, slug unique, bulk action 0/1/2, status patch, filters, crawl summary (mock Guzzle), crawl run timeout.
- Thủ công: gọi list/create/update/delete/status/bulk, confirm cache clear (log/Cache::has).
