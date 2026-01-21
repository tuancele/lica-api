## V2 Product Edit Upgrade Log

This document records the schema and field mapping for upgrading Product Edit page
to use Admin REST API instead of direct controller injected variables.

All content below uses ASCII only to avoid encoding issues.

---

## 1. Product Detail JSON Schema (GET /admin/api/products/{id})

High level shape:

- root object:
  - id: int
  - name: string
  - slug: string
  - seo_title: string|null
  - seo_description: string|null
  - cbmp: string|null
  - content: string (HTML)
  - description: string|null
  - status: int (0 or 1)
  - feature: string ("0" or "1")
  - best: string ("0" or "1")
  - verified: string ("0" or "1")
  - type: int
  - sort: int
  - video: string|null (URL)
  - image: string|null (main image URL)
  - gallery: string[] (image URLs)
  - stock: string|int (legacy total stock field)
  - warehouse_stock: int (total stock from warehouse service)
  - is_out_of_stock: bool
  - has_variants: int (0 or 1)
  - option1_name: string|null (for example: "Size", "Dung tich")
  - ingredient: string|null (raw ingredient string for textarea)
  - categories: int[] (flatten category ids, leaf at the end)
  - brand_id: int|null
  - origin_id: int|null
  - created_at: string (ISO 8601)
  - updated_at: string (ISO 8601)
  - brand: BrandResource|null
  - origin: OriginResource|null
  - variants: VariantResource[]

### 1.1 BrandResource (embedded)

- id: int
- name: string
- slug: string
- status: int
- image: string|null

### 1.2 OriginResource (embedded)

- id: int
- name: string
- slug: string
- status: int

### 1.3 VariantResource (embedded)

- id: int
- product_id: int
- sku: string
- option1_value: string|null
- image: string|null
- size_id: int|null
- color_id: int|null
- weight: float
- price: float
- stock: int
- warehouse_stock: int
- is_out_of_stock: bool
- position: int
- created_at: string (ISO 8601)
- updated_at: string (ISO 8601)

---

## 2. DOM <-> API Field Mapping (Product Edit Blade)

This section documents how each form field on Product Edit page maps to
the API JSON fields. All selectors are for app/Modules/Product/Views/edit.blade.php.

### 2.1 Core product fields

- name
  - selector: #product-name-input (input[name="name"])
  - api: data.name

- slug
  - selector: #slug-target (input[name="slug"])
  - api: data.slug

- seo_title
  - selector: #seo-title-auto (input[name="seo_title"])
  - api: data.seo_title

- seo_description
  - selector: #seo-desc-auto (textarea[name="seo_description"])
  - api: data.seo_description

- cbmp
  - selector: input[name="cbmp"]
  - api: data.cbmp

- description (short description)
  - selector: textarea[name="description"]
  - api: data.description

- content (long HTML content)
  - selector: textarea[name="content"] (plus WYSIWYG init)
  - api: data.content

- status
  - selector: select[name="status"] or equivalent status control
  - api: data.status

- feature / best / verified
  - selector:
    - input[name="feature"] (checkbox)
    - input[name="best"] (checkbox)
    - input[name="verified"] (checkbox)
  - api:
    - data.feature ("0" or "1")
    - data.best ("0" or "1")
    - data.verified ("0" or "1")

### 2.2 Media: gallery and video

- gallery (imageOther[])
  - wrapper: .image-grid.list_image
  - per item: .image-upload-box.has-img with:
    - img[src]
    - input[type="hidden"][name="imageOther[]"][value=image_url]
  - api: data.gallery[] (array of image URLs)
  - behavior:
    - clear existing dynamic .has-img nodes (keep #trigger-upload)
    - for each url in data.gallery: append item and hidden input

- video
  - selector:
    - hidden: #product-video-url (input[name="video"])
    - trigger: #product-video-trigger
  - api: data.video
  - behavior:
    - if data.video not null:
      - set #product-video-url.value = data.video
      - render <video><source src="data.video"></video> inside box
    - else: show default "add video" state

### 2.3 Taxonomy, brand, origin

- categories (cat_id[])
  - display: #categoryDisplayEdit
  - hidden leaf: #cat_id_hidden_edit (input[name="cat_id[]"])
  - api: data.categories (array of ids)
  - behavior:
    - const catIds = data.categories || []
    - if catIds.length > 0:
      - #cat_id_hidden_edit.value = catIds[catIds.length - 1]
    - update display text of #categoryDisplayEdit from taxonomy API if needed

- brand
  - selector: #brand-selector-edit (select[name="brand_id"])
  - api source:
    - preferred: data.brand_id
    - fallback: data.brand && data.brand.id
  - behavior:
    - after loading brand options via Admin API:
      - select.value = data.brand_id || (data.brand && data.brand.id)

- origin
  - selector: #origin-selector-edit (select[name="origin_id"])
  - api source:
    - preferred: data.origin_id
    - fallback: data.origin && data.origin.id
  - behavior:
    - after loading origin options:
      - select.value = data.origin_id || (data.origin && data.origin.id)

### 2.4 Variants, price, sku, weight, stock

- has_variants
  - selector: #has_variants (input[name="has_variants"])
  - api: data.has_variants (0 or 1)
  - behavior:
    - document.getElementById("has_variants").value = data.has_variants ? 1 : 0
    - toggle visibility between:
      - #single-selling (single mode)
      - variant table block (multi-variant mode)

#### Single mode (has_variants == 0)

- default variant selection
  - rule:
    - defaultVariant = variant with smallest position
    - if no position, fallback to first element in data.variants

- price
  - selector: input[name="price"] inside #single-selling
  - api: defaultVariant.price
  - behavior:
    - priceInput.value = formatted number of defaultVariant.price

- stock (read-only from warehouse)
  - selector: #single_stock_qty (input[name="stock_qty"])
  - api:
    - preferred: defaultVariant.warehouse_stock
    - fallback: defaultVariant.stock

- sku
  - selector: input[name="sku"]
  - api: defaultVariant.sku

- weight
  - selector: input[name="weight"]
  - api: defaultVariant.weight

#### Variant mode (has_variants == 1)

- option1_name
  - selector: #option1_name (input[name="option1_name"])
  - api: data.option1_name

- variants_json (hidden)
  - selector: #variants_json (input[name="variants_json"])
  - api: data.variants (array)
  - behavior:
    - on load:
      - variantsJsonInput.value = JSON.stringify(data.variants || [])
      - call buildVariantTableFromJson(variantsJsonInput.value)
    - on submit:
      - FE posts variants_json string
      - backend parses and syncs via ProductService::syncVariantsFromJson

### 2.5 Ingredient

- ingredient
  - selector: textarea[name="ingredient"]
  - api: data.ingredient
  - behavior:
    - textarea.value = data.ingredient || ""

---

## 3. Notes

- This document is the single source of truth for Product Edit v2:
  - which fields the Admin Product API must provide
  - how the edit.blade.php page maps DOM to JSON fields
- When changing ProductResource or the edit page, update this file to keep
  future debugging and API migration consistent.

