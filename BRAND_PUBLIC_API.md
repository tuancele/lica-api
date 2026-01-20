# Brand Public API

## GET /api/v1/brands/options

- Muc tieu: Lay danh sach thuong hieu (id, name) de do vao select brand_id trong trang admin add/edit san pham.
- Auth: Khong yeu cau (public).

### Response 200

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Brand A" },
    { "id": 2, "name": "Brand B" }
  ]
}
```

### Response 500

```json
{
  "success": false,
  "message": "Lay danh sach thuong hieu that bai"
}
```

## GET /api/v1/origins/options

- Muc tieu: Lay danh sach xuat xu (id, name) de do vao select origin_id trong trang admin add/edit san pham.
- Auth: Khong yeu cau (public).

### Response 200

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Han Quoc" },
    { "id": 2, "name": "Nhat Ban" }
  ]
}
```

### Response 500

```json
{
  "success": false,
  "message": "Lay danh sach xuat xu that bai"
}
```


