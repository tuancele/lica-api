<?php

namespace App\Modules\Product\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Variant;
use App\Modules\Brand\Models\Brand;
use App\Modules\Color\Models\Color;
use App\Modules\Size\Models\Size;
use App\Modules\Origin\Models\Origin;
use App\Modules\Ingredient\Models\Ingredient;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\OrderDetail;
use Illuminate\Support\Facades\Cache;
use App\Modules\Redirection\Models\Redirection;

class ProductController extends Controller
{
    private $model;
    private $module = 'Product';

    public function __construct(Product $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('product', 'list');
        $query = $this->model::where('type', 'product');

        if ($request->get('status') != "") {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('cat_id') != "") {
            $query->where('cat_id', 'like', '%' . $request->get('cat_id') . '%');
        }
        if ($request->get('keyword') != "") {
            $query->where('name', 'like', '%' . $request->get('keyword') . '%');
        }

        $data['products'] = $query->orderBy('sort', 'desc')
            ->paginate(10)
            ->appends([
                'cat_id' => $request->get('cat_id'),
                'keyword' => $request->get('keyword'),
                'status' => $request->get('status')
            ]);

        $data['categories'] = $this->model::where([['type', 'taxonomy'], ['status', '1']])->orderBy('sort', 'asc')->get();
        return view($this->module . '::index', $data);
    }

    public function create()
    {
        active('product', 'list');
        $data['categories'] = $this->model::where([['type', 'taxonomy'], ['status', '1'], ['cat_id', '0']])->orderBy('sort', 'asc')->get();
        $data['brands'] = Brand::where('status', '1')->orderBy('name', 'asc')->get();
        $data['origins'] = Origin::where('status', '1')->orderBy('sort', 'asc')->get();
        $data['ingredients'] = Ingredient::where('status', '1')->orderBy('name', 'asc')->get();
        return view($this->module . '::create', $data);
    }

    public function edit($id)
    {
        active('product', 'list');
        
        // CRITICAL: Use fresh query to bypass any model cache and get latest data from DB
        // Clear any query cache first
        \Illuminate\Support\Facades\Cache::forget('product_' . $id);
        
        // Use fresh query to ensure we get latest data from DB
        $detail = $this->model::where('id', $id)->first();
        if (!$detail) {
            return redirect()->route('product');
        }
        
        $data['categories'] = $this->model::where([['type', 'taxonomy'], ['status', '1'], ['cat_id', '0']])->orderBy('sort', 'asc')->get();
        $data['detail'] = $detail;
        $data['brands'] = Brand::where('status', '1')->orderBy('name', 'asc')->get();
        $data['origins'] = Origin::where('status', '1')->orderBy('sort', 'asc')->get();
        $data['ingredients'] = Ingredient::where('status', '1')->orderBy('name', 'asc')->get();
        
        // Safely decode gallery JSON - get fresh from DB
        $galleryJson = $detail->gallery ?? '[]';
        $decodedGallery = json_decode($galleryJson, true);
        $data['gallerys'] = is_array($decodedGallery) ? $decodedGallery : [];
        
        \Illuminate\Support\Facades\Log::info("Product Edit: Gallery loaded from DB", [
            'product_id' => $id,
            'gallery_json' => $galleryJson,
            'gallery_count' => count($data['gallerys']),
            'gallery_urls' => $data['gallerys']
        ]);
        
        // Safely decode cat_id JSON
        $catIdJson = $detail->cat_id ?? '[]';
        $decodedCatId = json_decode($catIdJson, true);
        $data['dcat'] = is_array($decodedCatId) ? $decodedCatId : [];
        return view($this->module . '::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug,' . $request->id,
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        // Logic: First image in gallery is the main image (Avatar)
        // Get URLs from request (existing images from form)
        $imageOther = $request->imageOther ?? [];
        
        // Filter out empty, blob, and invalid URLs from form data immediately
        $imageOther = array_filter($imageOther, function($url) {
            return !empty($url) && 
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $imageOther = array_values($imageOther); // Re-index
        
        \Illuminate\Support\Facades\Log::info("Product Update: imageOther from form", [
            'raw_imageOther' => $request->imageOther ?? [],
            'filtered_imageOther' => $imageOther,
            'count' => count($imageOther),
            'values' => $imageOther
        ]);
        
        // Also check session for R2 uploaded URLs (new uploads)
        // Support multiple session keys (comma-separated) from multiple uploads
        $sessionKeyInput = $request->input('r2_session_key');
        $sessionUrls = [];
        $sessionKeysProcessed = [];
        $urlsPerSessionKey = [];
        
        if ($sessionKeyInput) {
            // Split by comma to handle multiple session keys
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys)); // Remove empty values
            
            foreach ($sessionKeys as $sessionKey) {
                $urlsFromKey = \Illuminate\Support\Facades\Session::get($sessionKey, []);
                $sessionKeysProcessed[] = $sessionKey;
                
                if (!empty($urlsFromKey)) {
                    if (is_array($urlsFromKey)) {
                        $urlsPerSessionKey[$sessionKey] = count($urlsFromKey);
                        $sessionUrls = array_merge($sessionUrls, $urlsFromKey);
                    } else {
                        $urlsPerSessionKey[$sessionKey] = 1;
                        $sessionUrls[] = $urlsFromKey;
                    }
                } else {
                    $urlsPerSessionKey[$sessionKey] = 0;
                }
            }
            
            \Illuminate\Support\Facades\Log::info("Product Update: R2 Session URLs retrieval", [
                'session_keys' => $sessionKeysProcessed,
                'session_keys_count' => count($sessionKeysProcessed),
                'urls_per_session_key' => $urlsPerSessionKey,
                'total_session_urls_count' => count($sessionUrls),
                'session_urls' => $sessionUrls
            ]);
        }
        
        // Also check user's general session
        $userSessionKey = 'r2_uploaded_urls_user_' . auth()->id();
        $userSessionUrls = \Illuminate\Support\Facades\Session::get($userSessionKey, []);
        if (!empty($userSessionUrls)) {
            \Illuminate\Support\Facades\Log::info("Product Update: Found R2 URLs in user session", [
                'user_session_urls_count' => count($userSessionUrls),
                'user_session_urls' => $userSessionUrls
            ]);
        }
        
        // Merge: Form URLs (existing images) FIRST, then session URLs (new uploads)
        // This ensures existing images are preserved and new uploads are added
        // Filter session URLs to ensure they are valid
        $sessionUrls = array_filter($sessionUrls, function($url) {
            return !empty($url) && 
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $userSessionUrls = array_filter($userSessionUrls, function($url) {
            return !empty($url) && 
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        
        $allUrls = array_merge($imageOther, $sessionUrls, $userSessionUrls);
        
        \Illuminate\Support\Facades\Log::info("Product Update: URLs merged - VERIFICATION", [
            'from_form_count' => count($imageOther),
            'from_form_urls' => $imageOther,
            'from_session_count' => count($sessionUrls),
            'from_session_urls' => $sessionUrls,
            'from_user_session_count' => count($userSessionUrls),
            'from_user_session_urls' => $userSessionUrls,
            'total_after_merge' => count($allUrls),
            'all_urls' => $allUrls,
            'session_keys_processed' => $sessionKeysProcessed ?? [],
            'urls_per_session_key' => $urlsPerSessionKey ?? []
        ]);
        
        // Remove duplicates while preserving order
        $gallery = [];
        $seen = [];
        foreach ($allUrls as $url) {
            if (!in_array($url, $seen)) {
                $gallery[] = $url;
                $seen[] = $url;
            }
        }
        
        // Final filter to ensure all URLs are valid
        $gallery = array_filter($gallery, function($url) {
            $isValid = !empty($url) && 
                   $url !== asset("public/admin/no-image.png") && 
                   $url !== url("public/admin/no-image.png") &&
                   strpos($url, 'no-image.png') === false &&
                   strpos($url, 'blob:') === false;
            if (!$isValid) {
                \Illuminate\Support\Facades\Log::warning("Product Update: Filtered out invalid image URL", ['url' => $url]);
            }
            return $isValid;
        });
        $gallery = array_values($gallery); // Re-index array
        
        \Illuminate\Support\Facades\Log::info("Product Update: Gallery after filtering - FINAL RESULT", [
            'gallery' => $gallery,
            'count' => count($gallery),
            'gallery_json' => json_encode($gallery),
            'verification' => [
                'form_urls_count' => count($imageOther),
                'session_urls_count' => count($sessionUrls),
                'user_session_urls_count' => count($userSessionUrls),
                'total_merged' => count($allUrls),
                'final_gallery_count' => count($gallery),
                'match' => (count($gallery) >= count($imageOther) + count($sessionUrls)) ? 'OK' : 'MISMATCH'
            ]
        ]);
        
        $image = (count($gallery) > 0) ? $gallery[0] : null;

        // Get old product to check slug change
        $oldProduct = $this->model::find($request->id);
        $oldSlug = $oldProduct->slug;

        \Illuminate\Support\Facades\Log::info("Product Update: Updating product", [
            'product_id' => $request->id,
            'image' => $image,
            'gallery_json' => json_encode($gallery),
            'gallery_count' => count($gallery)
        ]);
        
        \Illuminate\Support\Facades\Log::info("Product Update: Updating product", [
            'product_id' => $request->id,
            'image' => $image,
            'gallery_json' => json_encode($gallery),
            'gallery_count' => count($gallery)
        ]);
        
        $updateResult = $this->model::where('id', $request->id)->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'image' => $image,
            'content' => $request->content,
            'cbmp' => $request->cbmp,
            'description' => $request->description ?? $oldProduct->description,
            'status' => $request->status ?? $oldProduct->status,
            'cat_id' => json_encode($request->cat_id),
            'origin_id' => $request->origin_id,
            'brand_id' => $request->brand_id ?? $oldProduct->brand_id,
            'seo_title' => $request->seo_title ?? $oldProduct->seo_title,
            'seo_description' => $request->seo_description ?? $oldProduct->seo_description,
            'type' => 'product',
            'feature' => $request->feature ?? $oldProduct->feature,
            'stock' => $request->stock ?? $oldProduct->stock,
            'best' => $request->best ?? $oldProduct->best,
            'ingredient' => $this->processIngredients($request->ingredient ?? $oldProduct->ingredient),
            'verified' => $request->verified ?? $oldProduct->verified,
            'gallery' => json_encode($gallery),
            'user_id' => Auth::id(),
        ]);
        
        // Verify gallery was saved correctly - use fresh query to bypass cache
        $savedProduct = $this->model::where('id', $request->id)->first();
        if ($savedProduct) {
            // Force refresh from DB by re-querying
            $savedProduct = $this->model::where('id', $request->id)->first();
        }
        
        $savedGalleryJson = $savedProduct->gallery ?? '[]';
        $savedGallery = json_decode($savedGalleryJson, true);
        $savedGallery = is_array($savedGallery) ? $savedGallery : [];
        
        \Illuminate\Support\Facades\Log::info("Product Update: Product updated successfully - VERIFICATION", [
            'product_id' => $request->id,
            'update_result' => $updateResult,
            'gallery_saved_count' => count($savedGallery),
            'gallery_expected_count' => count($gallery),
            'gallery_match' => (count($savedGallery) === count($gallery)) ? 'OK' : 'MISMATCH',
            'gallery_saved' => $savedGallery,
            'gallery_expected' => $gallery
        ]);

        // Handle Redirection if slug changed
        if ($oldSlug != $request->slug) {
            try {
                // Check if redirection already exists to avoid duplicates
                $exists = Redirection::where('link_from', url($oldSlug))->exists();
                if (!$exists) {
                    Redirection::insert([
                        'link_from' => url($oldSlug),
                        'link_to' => url($request->slug),
                        'type' => 301,
                        'status' => '1',
                        'user_id' => Auth::id(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't stop the process
                \Illuminate\Support\Facades\Log::error("Failed to create redirection: " . $e->getMessage());
            }
        }

        // Clear Cache BEFORE returning response
        Cache::flush();
        
        // Clear session URLs after successful save
        // Support multiple session keys (comma-separated)
        $sessionKeyInput = $request->input('r2_session_key');
        if ($sessionKeyInput) {
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys)); // Remove empty values
            foreach ($sessionKeys as $sessionKey) {
                \Illuminate\Support\Facades\Session::forget($sessionKey);
            }
        }
        $userSessionKey = 'r2_uploaded_urls_user_' . auth()->id();
        \Illuminate\Support\Facades\Session::forget($userSessionKey);

        // Return response with cache busting parameter to ensure fresh page load
        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => '/admin/product/edit/' . $request->id . '?t=' . time(),
            'gallery_count' => count($savedGallery) // Include gallery count for verification
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'slug' => 'required|min:1|max:250|unique:posts,slug',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'slug.required' => 'Bạn chưa nhập đường dẫn',
            'slug.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'slug.unique' => 'Đường dẫn đã tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }

        if ($request->sku) {
            $check = Variant::select('id')->where('sku', $request->sku)->exists();
            if ($check) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sku đã tồn tại'
                ]);
            }
        }

        // Logic: First image in gallery is the main image (Avatar)
        // Get URLs from request (existing images from form)
        $imageOther = $request->imageOther ?? [];
        
        // Filter out empty, blob, and invalid URLs from form data immediately
        $imageOther = array_filter($imageOther, function($url) {
            return !empty($url) && 
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $imageOther = array_values($imageOther); // Re-index
        
        // Note: For store (new product), we don't need to load from DB since it's a new product
        
        \Illuminate\Support\Facades\Log::info("Product Store: imageOther from form", [
            'raw_imageOther' => $request->imageOther ?? [],
            'filtered_imageOther' => $imageOther,
            'count' => count($imageOther),
            'values' => $imageOther
        ]);
        
        // Also check session for R2 uploaded URLs (new uploads)
        // Support multiple session keys (comma-separated) from multiple uploads
        $sessionKeyInput = $request->input('r2_session_key');
        $sessionUrls = [];
        $sessionKeysProcessed = [];
        $urlsPerSessionKey = [];
        
        if ($sessionKeyInput) {
            // Split by comma to handle multiple session keys
            $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
            $sessionKeys = array_filter(array_map('trim', $sessionKeys)); // Remove empty values
            
            foreach ($sessionKeys as $sessionKey) {
                $urlsFromKey = \Illuminate\Support\Facades\Session::get($sessionKey, []);
                $sessionKeysProcessed[] = $sessionKey;
                
                if (!empty($urlsFromKey)) {
                    if (is_array($urlsFromKey)) {
                        $urlsPerSessionKey[$sessionKey] = count($urlsFromKey);
                        $sessionUrls = array_merge($sessionUrls, $urlsFromKey);
                    } else {
                        $urlsPerSessionKey[$sessionKey] = 1;
                        $sessionUrls[] = $urlsFromKey;
                    }
                } else {
                    $urlsPerSessionKey[$sessionKey] = 0;
                }
            }
            
            \Illuminate\Support\Facades\Log::info("Product Store: R2 Session URLs retrieval", [
                'session_keys' => $sessionKeysProcessed,
                'session_keys_count' => count($sessionKeysProcessed),
                'urls_per_session_key' => $urlsPerSessionKey,
                'total_session_urls_count' => count($sessionUrls),
                'session_urls' => $sessionUrls
            ]);
        }
        
        // Also check user's general session
        $userSessionKey = 'r2_uploaded_urls_user_' . auth()->id();
        $userSessionUrls = \Illuminate\Support\Facades\Session::get($userSessionKey, []);
        if (!empty($userSessionUrls)) {
            \Illuminate\Support\Facades\Log::info("Product Store: Found R2 URLs in user session", [
                'user_session_urls_count' => count($userSessionUrls),
                'user_session_urls' => $userSessionUrls
            ]);
        }
        
        // Merge: Form URLs (existing images) FIRST, then session URLs (new uploads)
        // This ensures existing images are preserved and new uploads are added
        // Filter session URLs to ensure they are valid
        $sessionUrls = array_filter($sessionUrls, function($url) {
            return !empty($url) && 
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        $userSessionUrls = array_filter($userSessionUrls, function($url) {
            return !empty($url) && 
                   strpos($url, 'blob:') === false &&
                   strpos($url, 'no-image.png') === false;
        });
        
        $allUrls = array_merge($imageOther, $sessionUrls, $userSessionUrls);
        
        \Illuminate\Support\Facades\Log::info("Product Store: URLs merged - VERIFICATION", [
            'from_form_count' => count($imageOther),
            'from_form_urls' => $imageOther,
            'from_session_count' => count($sessionUrls),
            'from_session_urls' => $sessionUrls,
            'from_user_session_count' => count($userSessionUrls),
            'from_user_session_urls' => $userSessionUrls,
            'total_after_merge' => count($allUrls),
            'all_urls' => $allUrls,
            'session_keys_processed' => $sessionKeysProcessed ?? [],
            'urls_per_session_key' => $urlsPerSessionKey ?? []
        ]);
        
        // Remove duplicates while preserving order
        $gallery = [];
        $seen = [];
        foreach ($allUrls as $url) {
            if (!in_array($url, $seen)) {
                $gallery[] = $url;
                $seen[] = $url;
            }
        }
        
        // Final filter to ensure all URLs are valid
        $gallery = array_filter($gallery, function($url) {
            $isValid = !empty($url) && 
                   $url !== asset("public/admin/no-image.png") && 
                   $url !== url("public/admin/no-image.png") &&
                   strpos($url, 'no-image.png') === false &&
                   strpos($url, 'blob:') === false;
            if (!$isValid) {
                \Illuminate\Support\Facades\Log::warning("Product Store: Filtered out invalid image URL", ['url' => $url]);
            }
            return $isValid;
        });
        $gallery = array_values($gallery); // Re-index array
        
        \Illuminate\Support\Facades\Log::info("Product Store: Gallery after filtering - FINAL RESULT", [
            'gallery' => $gallery,
            'count' => count($gallery),
            'gallery_json' => json_encode($gallery),
            'verification' => [
                'form_urls_count' => count($imageOther),
                'session_urls_count' => count($sessionUrls),
                'user_session_urls_count' => count($userSessionUrls),
                'total_merged' => count($allUrls),
                'final_gallery_count' => count($gallery),
                'match' => (count($gallery) >= count($imageOther) + count($sessionUrls)) ? 'OK' : 'MISMATCH'
            ]
        ]);
        
        $image = (count($gallery) > 0) ? $gallery[0] : null;

        try {
            $id = $this->model::insertGetId([
                'name' => $request->name,
                'slug' => $request->slug,
                'image' => $image,
                'content' => $request->content,
                'cbmp' => $request->cbmp,
                'description' => $request->description ?? '',
                'status' => $request->status ?? '1',
                'cat_id' => json_encode($request->cat_id ?? []),
                'seo_title' => $request->seo_title,
                'seo_description' => $request->seo_description,
                'origin_id' => $request->origin_id,
                'brand_id' => $request->brand_id,
                'type' => 'product',
                'feature' => $request->feature ?? '0',
                'ingredient' => $request->ingredient,
                'best' => $request->best ?? '0',
                'stock' => $request->stock ?? '1',
                'user_id' => Auth::id(),
                'gallery' => json_encode($gallery),
                'verified' => $request->verified ?? '0',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi DB Product: ' . $e->getMessage()
            ]);
        }

        if ($id > 0) {
            try {
                Variant::insertGetId([
                    'sku' => $request->sku ?? 'SKU-'.time().'-'.rand(10,99),
                    'product_id' => $id,
                    'image' => $image, // Use the main image from gallery
                    'weight' => ($request->weight != "") ? $request->weight : 0,
                    'size_id' => '0',
                    'color_id' => '0',
                    'price' => ($request->price != "") ? str_replace(',', '', $request->price) : 0,
                    'sale' => ($request->sale != "") ? str_replace(',', '', $request->sale) : 0,
                    'user_id' => Auth::id(),
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Lỗi DB Variant: ' . $e->getMessage()
                ]);
            }
            
            // Clear Cache
            Cache::flush();
            
            // Clear session URLs after successful save
            // Support multiple session keys (comma-separated)
            $sessionKeyInput = $request->input('r2_session_key');
            if ($sessionKeyInput) {
                $sessionKeys = is_array($sessionKeyInput) ? $sessionKeyInput : explode(',', $sessionKeyInput);
                $sessionKeys = array_filter(array_map('trim', $sessionKeys)); // Remove empty values
                foreach ($sessionKeys as $sessionKey) {
                    \Illuminate\Support\Facades\Session::forget($sessionKey);
                }
            }
            $userSessionKey = 'r2_uploaded_urls_user_' . auth()->id();
            \Illuminate\Support\Facades\Session::forget($userSessionKey);

            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('product')
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Thêm không thành công!']]
            ]);
        }
    }

    public function delete(Request $request)
    {
        $this->model::findOrFail($request->id)->delete();
        $url = route('product');
        if ($request->page != "") {
            $url .= '?page=' . $request->page;
        }
        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url
        ]);
    }

    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update([
            'status' => $request->status
        ]);
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('product')
        ]);
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']]
            ]);
        }
        $action = $request->action;
        if ($action == 0) {
            $this->model::whereIn('id', $check)->update(['status' => '0']);
            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('product')
            ]);
        } elseif ($action == 1) {
            $this->model::whereIn('id', $check)->update(['status' => '1']);
            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('product')
            ]);
        } else {
            $this->model::whereIn('id', $check)->delete();
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('product')
            ]);
        }
    }

    public function postSort(Request $req)
    {
        $sort = $req->sort;
        if (isset($sort) && !empty($sort)) {
            foreach ($sort as $key => $val) {
                $this->model::where('id', $key)->update([
                    'sort' => $val
                ]);
            }
        }
    }

    public function sort(Request $req)
    {
        $this->model::where('id', $req->id)->update([
            'sort' => $req->sort
        ]);
        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi thứ tự thành công!',
            'url' => route('product')
        ]);
    }

    public function variant($id, $code)
    {
        active('product', 'list');
        $product = $this->model::find($id);
        $detail = Variant::find($code);
        if (!$product || !$detail) {
            return redirect()->route('product');
        }
        $data['detail'] = $detail;
        $data['product'] = $product;
        $data['variants'] = $product->variants;
        $data['colors'] = Color::where('status', '1')->get();
        $data['sizes'] = Size::where('status', '1')->orderBy('sort', 'asc')->get();
        return view($this->module . '::variant', $data);
    }

    public function editvariant(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sku' => 'required|unique:variants,sku,' . $req->id,
        ], [
            'sku.required' => 'Bạn chưa nhập Sku',
            'sku.unique' => 'Sku đã tồn tại',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $update = Variant::where('id', $req->id)->update([
            'sku' => $req->sku,
            'size_id' => $req->size_id,
            'color_id' => $req->color_id,
            'image' => $req->image,
            'weight' => $req->weight,
            'price' => str_replace(',', '', $req->price),
            'sale' => str_replace(',', '', $req->sale),
            'user_id' => Auth::id(),
        ]);
        if ($update > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật biến thể thành công!',
                'url' => route('product.variant', ['id' => $req->product_id, 'code' => $req->id])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Cập nhật biến thể không thành công!']]
            ]);
        }
    }

    public function variantnew($id)
    {
        active('product', 'list');
        $product = $this->model::find($id);
        if (!$product) {
            return redirect()->route('product');
        }
        $data['product'] = $product;
        $data['variants'] = $product->variants;
        $data['first'] = Variant::where('product_id', $id)->orderBy('id', 'asc')->first();
        $data['colors'] = Color::where('status', '1')->get();
        $data['sizes'] = Size::where('status', '1')->orderBy('sort', 'asc')->get();
        return view($this->module . '::variantnew', $data);
    }

    public function createvariant(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'sku' => 'required|unique:variants,sku',
        ], [
            'sku.required' => 'Bạn chưa nhập Sku',
            'sku.unique' => 'Sku đã tồn tại',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ]);
        }
        $id = Variant::insertGetId([
            'sku' => $req->sku,
            'product_id' => $req->product_id,
            'image' => $req->image,
            'size_id' => $req->size_id,
            'color_id' => $req->color_id,
            'weight' => $req->weight,
            'price' => str_replace(',', '', $req->price),
            'sale' => str_replace(',', '', $req->sale),
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Tạo biến thể thành công!',
                'url' => route('product.variant', ['id' => $req->product_id, 'code' => $id])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Tạo biến thể không thành công!']]
            ]);
        }
    }

    public function delvariant(Request $req)
    {
        $order = OrderDetail::select('id')->where('product_id', $req->id)->exists();
        if ($order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sản phẩm đã có đơn hàng không thể xóa!'
            ]);
        } else {
            Variant::findOrFail($req->id)->delete();
            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
            ]);
        }
    }

    public function processIngredients($content)
    {
        if (empty($content)) return $content;

        // Strip tags to work with plain text
        $cleanContent = strip_tags($content);
        
        // Split by comma (standard ingredient separator)
        $parts = preg_split('/,\s*/', $cleanContent);
        
        // Filter empty
        $parts = array_filter($parts, function($value) { return trim($value) !== ''; });
        
        if (empty($parts)) return $content;

        // Get unique names to query
        $names = array_map('trim', $parts);
        $names = array_unique($names);

        // Query DB for these names (Case-insensitive)
        $ingredients = Ingredient::whereIn('name', $names)->where('status', '1')->get();
        
        // Map for lookup
        $ingMap = [];
        foreach ($ingredients as $ing) {
            $ingMap[strtolower($ing->name)] = $ing;
        }

        // Rebuild content
        $processedParts = [];
        foreach ($parts as $part) {
            $trimPart = trim($part);
            $lowerPart = strtolower($trimPart);
            
            if (isset($ingMap[$lowerPart])) {
                $ing = $ingMap[$lowerPart];
                // Link using official name from DB
                $processedParts[] = '<a href="javascript:;" class="item_ingredient" data-id="'.$ing->slug.'">'.$ing->name.'</a>';
            } else {
                $processedParts[] = $trimPart;
            }
        }

        return implode(', ', $processedParts);
    }
}
