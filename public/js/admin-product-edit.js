/**
 * Admin Product Edit v2 - Skeleton JS
 *
 * This file is a draft skeleton for migrating Product Edit page to use
 * Admin REST API. It reads mapping defined in V2-PRODUCT.MD conceptually.
 *
 * Notes:
 * - Keep comments in English only.
 * - Real implementation should handle error states, loading states,
 *   and integration with existing upload / WYSIWYG / category picker code.
 */

(function () {
    "use strict";

    /**
     * Basic configuration: API endpoints and selectors.
     */
    var CONFIG = {
        productDetailUrlTemplate: "/admin/api/products/{id}",
        updateUrlTemplate: "/admin/api/products/{id}",
        rootSelector: "#tblForm",
        nameSelector: "#product-name-input",
        slugSelector: "#slug-target",
        seoTitleSelector: "#seo-title-auto",
        seoDescriptionSelector: "#seo-desc-auto",
        cbmpSelector: 'input[name="cbmp"]',
        descriptionSelector: 'textarea[name="description"]',
        contentSelector: 'textarea[name="content"]',
        statusSelector: 'select[name="status"]',
        featureSelector: 'input[name="feature"]',
        bestSelector: 'input[name="best"]',
        verifiedSelector: 'input[name="verified"]',
        galleryWrapperSelector: ".image-grid.list_image",
        galleryTriggerId: "trigger-upload",
        videoHiddenSelector: "#product-video-url",
        videoBoxSelector: "#product-video-trigger",
        categoryHiddenSelector: "#cat_id_hidden_edit",
        categoryDisplaySelector: "#categoryDisplayEdit",
        brandSelector: "#brand-selector-edit",
        originSelector: "#origin-selector-edit",
        hasVariantsSelector: "#has_variants",
        option1NameSelector: "#option1_name",
        variantsJsonSelector: "#variants_json",
        singlePriceSelector: 'input[name="price"]',
        singleStockQtySelector: "#single_stock_qty",
        singleSkuSelector: 'input[name="sku"]',
        singleWeightSelector: 'input[name="weight"]',
        ingredientSelector: 'textarea[name="ingredient"]'
    };

    /**
     * Resolve product ID from wrapper or hidden input.
     */
    function resolveProductId() {
        // Preferred: wrapper #product-edit-app with data-id
        var root = document.getElementById("product-edit-app");
        if (root && root.getAttribute("data-id")) {
            return root.getAttribute("data-id");
        }

        // Fallback: hidden id inside form
        var form = document.querySelector(CONFIG.rootSelector);
        if (!form) return null;

        // Hidden input: <input type="hidden" name="id" value="...">
        var hiddenId = form.querySelector('input[name="id"]');
        if (hiddenId && hiddenId.value) {
            return hiddenId.value;
        }

        // Optional: use data attribute on form
        var dataId = form.getAttribute("data-product-id");
        return dataId || null;
    }

    /**
     * Public function: load product data via Admin API using axios.
     *
     * @param {string|number} productId
     */
    function loadProductData(productId) {
        if (!productId) return;

        var url = CONFIG.productDetailUrlTemplate.replace("{id}", productId);

        // Prefer axios if available
        if (window.axios && typeof window.axios.get === "function") {
            window.axios
                .get(url)
                .then(function (response) {
                    var resp = response && response.data ? response.data : null;
                    if (!resp || !resp.success || !resp.data) {
                        console.warn("Product edit API returned invalid payload", resp);
                        return;
                    }
                    applyProductData(resp.data);
                })
                .catch(function (error) {
                    console.error("Product edit API error (axios)", error);
                });
            return;
        }

        // Fallback: fetch
        fetch(url, {
            method: "GET",
            headers: {
                "Accept": "application/json"
            }
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (resp) {
                if (!resp || !resp.success || !resp.data) {
                    console.warn("Product edit API returned invalid payload", resp);
                    return;
                }
                applyProductData(resp.data);
            })
            .catch(function (err) {
                console.error("Product edit API error (fetch)", err);
            });
    }

    /**
     * Entry: load product detail when DOM is ready (auto bootstrap).
     * Only runs on Product Edit page (has #product-edit-app or URL matches /admin/product/edit/).
     */
    function initProductEdit() {
        // Check if we're on Product Edit page
        var productEditApp = document.getElementById("product-edit-app");
        var isProductEditPage = productEditApp !== null;
        
        // Also check URL path as fallback
        if (!isProductEditPage) {
            var path = window.location.pathname;
            isProductEditPage = path.indexOf("/admin/product/edit/") !== -1;
        }
        
        if (!isProductEditPage) {
            // Not Product Edit page, skip initialization
            return;
        }
        
        var productId = resolveProductId();
        if (!productId) {
            // No ID, probably create page, skip.
            return;
        }
        loadProductData(productId);

        // Attach submit handler to form
        var form = document.querySelector(CONFIG.rootSelector);
        if (form) {
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                handleFormSubmit(form, productId);
            });
        }
    }

    /**
     * Apply JSON data from API to form fields.
     *
     * This function follows mapping defined in V2-PRODUCT.MD.
     */
    function applyProductData(data) {
        if (!data) return;

        // Basic fields
        setValue(CONFIG.nameSelector, data.name);
        setValue(CONFIG.slugSelector, data.slug);
        setValue(CONFIG.seoTitleSelector, data.seo_title);
        setValue(CONFIG.seoDescriptionSelector, data.seo_description);
        setValue(CONFIG.cbmpSelector, data.cbmp);
        setValue(CONFIG.descriptionSelector, data.description);
        setValue(CONFIG.contentSelector, data.content);

        // Status and flags
        setValue(CONFIG.statusSelector, data.status);
        setCheckbox(CONFIG.featureSelector, data.feature == "1" || data.feature === 1);
        setCheckbox(CONFIG.bestSelector, data.best == "1" || data.best === 1);
        setCheckbox(CONFIG.verifiedSelector, data.verified == "1" || data.verified === 1);

        // Media
        applyGallery(data.gallery || []);
        applyVideo(data.video);

        // Taxonomy / brand / origin
        applyCategories(data.categories || []);
        applyBrand(data);
        applyOrigin(data);

        // Variants and selling info
        applyVariantsAndSelling(data);

        // Ingredient
        setValue(CONFIG.ingredientSelector, data.ingredient);
    }

    /**
     * Helper: set value safely.
     */
    function setValue(selector, value) {
        var el = document.querySelector(selector);
        if (!el || typeof value === "undefined" || value === null) return;
        el.value = value;
    }

    /**
     * Helper: set checkbox state.
     */
    function setCheckbox(selector, checked) {
        var el = document.querySelector(selector);
        if (!el) return;
        el.checked = !!checked;
    }

    /**
     * Apply gallery images based on data.gallery.
     */
    function applyGallery(gallery) {
        var wrapper = document.querySelector(CONFIG.galleryWrapperSelector);
        if (!wrapper) return;

        var trigger = document.getElementById(CONFIG.galleryTriggerId);
        // Remove existing dynamic image boxes (keep trigger)
        var boxes = wrapper.querySelectorAll(".image-upload-box.has-img");
        boxes.forEach(function (box) {
            box.parentNode.removeChild(box);
        });

        if (!Array.isArray(gallery)) return;

        gallery.forEach(function (url, idx) {
            if (!url) return;
            var box = document.createElement("div");
            box.className = "image-upload-box has-img" + (idx === 0 ? " is-cover" : "");
            box.setAttribute("data-existing", "true");

            var img = document.createElement("img");
            img.src = url;
            box.appendChild(img);

            var hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "imageOther[]";
            hidden.value = url;
            box.appendChild(hidden);

            // Optional: remove button, same structure as existing blade
            var remove = document.createElement("a");
            remove.href = "javascript:void(0)";
            remove.className = "remove-btn";
            remove.innerHTML = '<i class="fa fa-times"></i>';
            box.appendChild(remove);

            if (trigger && trigger.parentNode) {
                trigger.parentNode.insertBefore(box, trigger);
            } else {
                wrapper.appendChild(box);
            }
        });
    }

    /**
     * Apply video based on data.video.
     */
    function applyVideo(videoUrl) {
        var hidden = document.querySelector(CONFIG.videoHiddenSelector);
        var box = document.querySelector(CONFIG.videoBoxSelector);
        if (!hidden || !box) return;

        if (!videoUrl) {
            // No video, keep default state.
            return;
        }

        hidden.value = videoUrl;

        // Clear existing video if any
        var existingVideo = box.querySelector("video");
        if (existingVideo && existingVideo.parentNode) {
            existingVideo.parentNode.removeChild(existingVideo);
        }

        var inner = box.querySelector(".video-upload-inner");
        if (inner) {
            inner.style.display = "none";
        }

        var video = document.createElement("video");
        video.setAttribute("playsinline", "");
        video.muted = true;
        video.style.width = "100%";
        video.style.height = "100%";
        video.style.objectFit = "cover";
        video.style.borderRadius = "4px";
        video.style.pointerEvents = "none";

        var source = document.createElement("source");
        source.src = videoUrl;
        source.type = "video/mp4";
        video.appendChild(source);

        box.appendChild(video);
    }

    /**
     * Apply categories to hidden field and display.
     */
    function applyCategories(categories) {
        var hidden = document.querySelector(CONFIG.categoryHiddenSelector);
        var display = document.querySelector(CONFIG.categoryDisplaySelector);
        if (!hidden || !display) return;

        if (!Array.isArray(categories) || categories.length === 0) {
            hidden.value = "";
            return;
        }

        // Use leaf id for now.
        var leafId = categories[categories.length - 1];
        hidden.value = leafId;

        // Display text will be updated by existing category picker JS
        // once it loads taxonomy data.
    }

    /**
     * Apply brand selection.
     */
    function applyBrand(data) {
        var select = document.querySelector(CONFIG.brandSelector);
        if (!select) return;

        var id = data.brand_id;
        if (!id && data.brand && data.brand.id) {
            id = data.brand.id;
        }
        if (!id) return;
        // In phase 1 we only set value, options may be already rendered by PHP.
        select.value = String(id);
        // If select2 is used, trigger change for UI update.
        if (window.$ && $.fn && typeof $.fn.select2 === "function") {
            $(select).val(String(id)).trigger("change");
        }
    }

    /**
     * Apply origin selection.
     */
    function applyOrigin(data) {
        var select = document.querySelector(CONFIG.originSelector);
        if (!select) return;

        var id = data.origin_id;
        if (!id && data.origin && data.origin.id) {
            id = data.origin.id;
        }
        if (!id) return;
        select.value = String(id);
        if (window.$ && $.fn && typeof $.fn.select2 === "function") {
            $(select).val(String(id)).trigger("change");
        }
    }

    /**
     * Apply variants and selling information for both single and variant mode.
     */
    function applyVariantsAndSelling(data) {
        var hasVariantsInput = document.querySelector(CONFIG.hasVariantsSelector);
        var variants = Array.isArray(data.variants) ? data.variants : [];
        var hasVariants = data.has_variants ? 1 : 0;

        if (hasVariantsInput) {
            hasVariantsInput.value = hasVariants;
        }

        // Set option1_name
        setValue(CONFIG.option1NameSelector, data.option1_name);

        if (!hasVariants) {
            applySingleVariantMode(variants);
        } else {
            applyMultiVariantMode(variants);
        }
    }

    /**
     * Single variant mode: pick defaultVariant and map price, sku, weight, stock.
     */
    function applySingleVariantMode(variants) {
        if (!variants || variants.length === 0) return;

        var defaultVariant = pickDefaultVariant(variants);
        if (!defaultVariant) return;

        // Price, sku, weight, stock
        if (defaultVariant.price != null) {
            var priceVal = String(defaultVariant.price).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            setValue(CONFIG.singlePriceSelector, priceVal);
        }

        var stockVal = typeof defaultVariant.warehouse_stock !== "undefined"
            ? defaultVariant.warehouse_stock
            : defaultVariant.stock;
        if (typeof stockVal !== "undefined") {
            setValue(CONFIG.singleStockQtySelector, stockVal);
        }

        setValue(CONFIG.singleSkuSelector, defaultVariant.sku);
        if (typeof defaultVariant.weight !== "undefined") {
            setValue(CONFIG.singleWeightSelector, defaultVariant.weight);
        }
    }

    /**
     * Multi variant mode: store variants_json and delegate to existing builder.
     */
    function applyMultiVariantMode(variants) {
        var variantsJsonInput = document.querySelector(CONFIG.variantsJsonSelector);
        if (!variantsJsonInput) return;

        try {
            variantsJsonInput.value = JSON.stringify(variants || []);
        } catch (e) {
            console.error("Failed to stringify variants", e);
            return;
        }

        // Call existing JS hook if available.
        if (typeof window.buildVariantTableFromJson === "function") {
            window.buildVariantTableFromJson(variantsJsonInput.value);
        }
    }

    /**
     * Pick default variant according to position rules.
     */
    function pickDefaultVariant(variants) {
        if (!variants || variants.length === 0) return null;

        var withPosition = variants.filter(function (v) {
            return typeof v.position !== "undefined" && v.position !== null;
        });

        if (withPosition.length > 0) {
            withPosition.sort(function (a, b) {
                var pa = parseInt(a.position, 10) || 0;
                var pb = parseInt(b.position, 10) || 0;
                return pa - pb;
            });
            return withPosition[0];
        }

        return variants[0];
    }

    /**
     * Serialize form into a plain JSON object suitable for API.
     *
     * - imageOther[] -> imageOther: []
     * - cat_id[] -> cat_id: []
     * - other [] fields follow the same rule
     */
    function serializeFormToJson(form) {
        var formData = new FormData(form);
        var data = {};

        formData.forEach(function (value, key) {
            // Normalize key for [] arrays
            if (key.endsWith("[]")) {
                var baseKey = key.slice(0, -2);
                if (!data[baseKey]) {
                    data[baseKey] = [];
                }
                data[baseKey].push(value);
            } else {
                if (data[key] !== undefined) {
                    // Promote to array if duplicate
                    if (!Array.isArray(data[key])) {
                        data[key] = [data[key]];
                    }
                    data[key].push(value);
                } else {
                    data[key] = value;
                }
            }
        });

        return data;
    }

    /**
     * Clear previous validation error states.
     */
    function clearValidationErrors() {
        // Remove error classes
        var invalids = document.querySelectorAll(".is-invalid");
        invalids.forEach(function (el) {
            el.classList.remove("is-invalid");
        });

        // Remove error messages
        var msgs = document.querySelectorAll(".field-error-message");
        msgs.forEach(function (el) {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        });
    }

    /**
     * Show validation errors returned from API (422).
     *
     * @param {object} errors - Laravel style errors object: { field: [messages...] }
     */
    function showValidationErrors(errors) {
        if (!errors || typeof errors !== "object") return;

        Object.keys(errors).forEach(function (field) {
            var messages = errors[field];
            if (!messages || !messages.length) return;

            var fieldName = field;
            // For array fields like imageOther.0, use base name
            if (fieldName.indexOf(".") !== -1) {
                fieldName = fieldName.split(".")[0];
            }

            var selector =
                '[name="' + fieldName + '"]' +
                ',[name="' + fieldName + '[]"]';
            var input = document.querySelector(selector);
            if (!input) {
                console.warn("Cannot find field for validation error:", field);
                return;
            }

            input.classList.add("is-invalid");

            var msgEl = document.createElement("div");
            msgEl.className = "field-error-message";
            msgEl.style.color = "#d93025";
            msgEl.style.fontSize = "12px";
            msgEl.style.marginTop = "4px";
            msgEl.textContent = messages[0];

            if (input.parentNode) {
                input.parentNode.appendChild(msgEl);
            }
        });
    }

    /**
     * Handle form submit: send PUT /admin/api/products/{id}.
     */
    function handleFormSubmit(form, productId) {
        if (!productId) return;

        clearValidationErrors();

        var payload = serializeFormToJson(form);

        var url = CONFIG.updateUrlTemplate.replace("{id}", productId);

        // Extract CSRF token
        var csrfToken = payload._token || null;
        if (!csrfToken) {
            var csrfInput = form.querySelector('input[name="_token"]');
            if (csrfInput && csrfInput.value) {
                csrfToken = csrfInput.value;
            }
        }

        // Normalize numeric flags if needed (optional)
        // Example: has_variants should be int
        if (typeof payload.has_variants !== "undefined") {
            payload.has_variants = parseInt(payload.has_variants, 10) || 0;
        }

        // Prefer axios for PUT
        if (window.axios && typeof window.axios.put === "function") {
            var axiosConfig = {};
            if (csrfToken) {
                axiosConfig.headers = {
                    "X-CSRF-TOKEN": csrfToken
                };
            }

            window.axios
                .put(url, payload, axiosConfig)
                .then(function (response) {
                    var resp = response && response.data ? response.data : null;
                    if (!resp || !resp.success) {
                        console.warn("Product update failed", resp);
                        showToast("Cap nhat san pham that bai", "error");
                        return;
                    }

                    showToast(resp.message || "Cap nhat san pham thanh cong", "success");

                    // Redirect back to edit page (same behavior as old controller)
                    var redirectId =
                        (resp.data && resp.data.id) ? resp.data.id : productId;
                    var redirectUrl = "/admin/product/edit/" + redirectId + "?t=" + Date.now();
                    window.location.href = redirectUrl;
                })
                .catch(function (error) {
                    if (error.response && error.response.status === 422) {
                        var data = error.response.data || {};
                        // Log validation errors to console for debugging
                        console.error("Validation errors:", data);
                        if (data.errors) {
                            console.error("Field errors:", data.errors);
                        }
                        showToast(data.message || "Du lieu khong hop le", "error");
                        showValidationErrors(data.errors || {});
                        return;
                    }

                    console.error("Product update error (axios)", error);
                    showToast("Loi he thong khi cap nhat san pham", "error");
                });

            return;
        }

        // Fallback: fetch PUT
        var headers = {
            "Content-Type": "application/json",
            "Accept": "application/json"
        };
        if (csrfToken) {
            headers["X-CSRF-TOKEN"] = csrfToken;
        }

        fetch(url, {
            method: "PUT",
            headers: headers,
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                if (res.status === 422) {
                    return res.json().then(function (data) {
                        // Log validation errors to console for debugging
                        console.error("Validation errors:", data);
                        if (data.errors) {
                            console.error("Field errors:", data.errors);
                        }
                        showToast((data && data.message) || "Du lieu khong hop le", "error");
                        showValidationErrors((data && data.errors) || {});
                        throw new Error("Validation error");
                    });
                }
                return res.json();
            })
            .then(function (resp) {
                if (!resp || !resp.success) {
                    console.warn("Product update failed", resp);
                    showToast("Cap nhat san pham that bai", "error");
                    return;
                }

                showToast(resp.message || "Cap nhat san pham thanh cong", "success");
                var redirectId =
                    (resp.data && resp.data.id) ? resp.data.id : productId;
                var redirectUrl = "/admin/product/edit/" + redirectId + "?t=" + Date.now();
                window.location.href = redirectUrl;
            })
            .catch(function (err) {
                console.error("Product update error (fetch)", err);
                // Error already handled for 422
                if (String(err && err.message).toLowerCase().indexOf("validation") !== -1) {
                    return;
                }
                showToast("Loi he thong khi cap nhat san pham", "error");
            });
    }

    /**
     * Simple toast helper: use toastr if available, otherwise alert.
     */
    function showToast(message, type) {
        if (window.toastr) {
            if (type === "success" && typeof window.toastr.success === "function") {
                window.toastr.success(message);
                return;
            }
            if (type === "error" && typeof window.toastr.error === "function") {
                window.toastr.error(message);
                return;
            }
            window.toastr.info(message);
            return;
        }

        // Fallback
        alert(message);
    }

    // Expose public function for manual debug if needed.
    window.loadProductData = loadProductData;

    // Init on DOM ready.
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initProductEdit);
    } else {
        initProductEdit();
    }
})();

