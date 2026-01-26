<?php

declare(strict_types=1);

namespace App\Modules\Compare\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compare\Models\Compare;
use App\Modules\Compare\Models\Draff;
use App\Modules\Compare\Models\Store;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class CompareController extends Controller
{
    private $model;
    private $controller = 'compare';
    private $view = 'Compare';

    public function __construct(Compare $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('compare', 'list');
        $data['list'] = $this->model::join('stores', 'stores.id', '=', 'compares.store_id')->select('compares.*', 'stores.name as storename', 'stores.id as storeid')->where(function ($query) use ($request) {
            if ($request->get('status') != '') {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('keyword') != '') {
                $query->orWhere('compares.name', 'like', '%'.$request->get('keyword').'%')->orWhere('brand', 'like', '%'.$request->get('keyword').'%');
            }
            if ($request->get('store_id') != '') {
                $query->where('store_id', $request->get('store_id'));
            }
        })->orderBy('compares.created_at', 'desc')->paginate(20)->appends(['keyword' => $request->get('keyword'), 'status' => $request->get('status'), 'store_id' => $request->get('store_id')]);
        $data['stores'] = Store::where('status', '1')->get();

        return view($this->view.'::index', $data);
    }

    public function create()
    {
        active('compare', 'list');
        $data['stores'] = Store::where('status', '1')->get();

        return view($this->view.'::create', $data);
    }

    public function store(Request $request)
    {
        // $this->authorize('post-create');
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'link' => 'required|unique:compares,link',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'link.required' => 'Bạn chưa nhập link sản phẩm',
            'link.unique' => 'Link sản phẩm đã tồn tại',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $id = $this->model::insertGetId(
            [
                'store_id' => $request->store_id,
                'name' => $request->name,
                'price' => ($request->price != '') ? str_replace(',', '', $request->price) : 0,
                'link' => $request->link,
                'is_link' => $request->is_link,
                'brand' => strtolower($request->brand),
                'status' => $request->status,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => route('compare'),
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Thêm không thành công!']],
            ]);
        }
    }

    public function edit($id)
    {
        active('compare', 'list');
        $post = $this->model::find($id);
        if (! isset($post) && empty($post)) {
            return redirect()->route('compare');
        }
        $data['detail'] = $post;
        $data['stores'] = Store::where('status', '1')->get();

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'link' => 'required|unique:compares,link,'.$request->id,
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'link.required' => 'Bạn chưa nhập link sản phẩm',
            'link.unique' => 'Link sản phẩm đã tồn tại',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $this->model::where('id', $request->id)->update([
            'store_id' => $request->store_id,
            'name' => $request->name,
            'price' => ($request->price != '') ? str_replace(',', '', $request->price) : 0,
            'link' => $request->link,
            'is_link' => $request->is_link,
            'brand' => strtolower($request->brand),
            'status' => $request->status,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
            'url' => route('compare'),
        ]);
    }

    public function delete(Request $request)
    {
        $data = $this->model::findOrFail($request->id)->delete();
        if ($request->page != '') {
            $url = route('compare').'?page='.$request->page;
        } else {
            $url = route('compare');
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => $url,
        ]);
    }

    public function status(Request $request)
    {
        $this->model::where('id', $request->id)->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Đổi trạng thái thành công!',
            'url' => route('compare'),
        ]);
    }

    public function action(Request $request)
    {
        $check = $request->checklist;
        if (! isset($check) && empty($check)) {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Chưa chọn dữ liệu cần thao tác!']],
            ]);
        }
        $action = $request->action;
        if ($action == 0) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '0',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Ẩn thành công!',
                'url' => route('compare'),
            ]);
        } elseif ($action == 1) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'status' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Hiển thị thành công!',
                'url' => route('compare'),
            ]);
        } elseif ($action == 3) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'is_link' => '1',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật thành công!',
                'url' => route('compare'),
            ]);
        } elseif ($action == 4) {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->update([
                    'is_link' => '0',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Cập nhật thành công!',
                'url' => route('compare'),
            ]);
        } else {
            foreach ($check as $key => $value) {
                $this->model::where('id', $value)->delete();
            }

            return response()->json([
                'status' => 'success',
                'alert' => 'Xóa thành công!',
                'url' => route('compare'),
            ]);
        }
    }

    public function crawl()
    {
        active('compare', 'list');
        active('compare', 'list');
        $data['stores'] = Store::where('status', '1')->get();

        return view($this->view.'::crawl', $data);
    }

    public function getBrand(Request $request)
    {
        $store = $request->id;
        if ($store == 1) {
            // Beautybox
            $link = 'https://beautybox-api.hsv-tech.io/client/config/collections';
            $ch = curl_init($link);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $content = curl_exec($ch);
            curl_close($ch);
            $api = json_decode($content, true);
            $total = 0;
            if (isset($api) && ! empty($api)) {
                foreach ($api as $item) {
                    if ($item['isActive'] == 1 && $item['parentCollectionId'] == 1) {
                        $url = $item['slug'];
                        $id = $item['id'];
                        $brand = strtolower($item['detail']['0']['name']);
                        Draff::updateOrCreate([
                            'link' => $url,
                        ], [
                            'store_id' => $store,
                            'brand_id' => $id,
                            'name' => $brand,
                            'status' => '1',
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                        $total++;
                    }
                }
            }
            $html = '';
            for ($i = 0; $i < ceil($total / 20); $i++) {
                $html .= '<option value="'.($i * 20).'">'.($i * 20).'-'.(($i + 1) * 20).'</option>';
            }

            return response()->json([
                'status' => 'success',
                'total' => 'Tổng thương hiệu: '.$total,
                'html' => $html,
            ]);
        } elseif ($store == 2) {
            // Guardian
            $link = 'https://www.guardian.com.vn/pages/brand-list';
            $dom = HtmlDomParser::file_get_html($link);
            $total = 0;
            foreach ($dom->find('.item-search a') as $element) {
                $url = 'https://www.guardian.com.vn'.$element->href;
                $brand = strtolower($element->innerText());
                Draff::updateOrCreate([
                    'link' => $url,
                ], [
                    'store_id' => $store,
                    'name' => $brand,
                    'status' => '1',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $total++;
            }
            $html = '';
            for ($i = 0; $i < ceil($total / 50); $i++) {
                $html .= '<option value="'.($i * 50).'">'.($i * 50).'-'.(($i + 1) * 50).'</option>';
            }

            return response()->json([
                'status' => 'success',
                'total' => 'Tổng thương hiệu: '.$total,
                'html' => $html,
            ]);
        } elseif ($store == 3) {
            // Nuty
            // $link = 'https://nuty.vn/thuong-hieu';
            // $dom = HtmlDomParser::file_get_html($link);
            // $total = 0;
            // foreach ($dom->find('.brand-item .title a') as $element) {
            //     $url = $element->href;
            //     $brand = strtolower($element->innerText());
            //     Draff::updateOrCreate([
            //         'link' => $url,
            //     ], [
            //         'store_id' => $store,
            //         'name' => $brand,
            //         'status' => '1',
            //         'created_at' => date('Y-m-d H:i:s')
            //     ]);
            //     $total++;
            // }
            // $html = "";
            // for($i = 0;$i < ceil($total/50); $i++){
            // 	$html .='<option value="'.($i*50).'">'.($i*50).'-'.(($i+1)*50).'</option>';
            // }
            // return response()->json([
            //     'status' => 'success',
            //     'total' => 'Tổng thương hiệu: '.$total,
            //     'html' => $html
            // ]);
        } elseif ($store == 4) {
            // Hasaki
            $link = 'https://hasaki.vn/thuong-hieu';
            $dom = HtmlDomParser::file_get_html($link);
            $total = 0;
            foreach ($dom->find('.name_thuonghieu a') as $element) {
                $url = 'https://hasaki.vn'.$element->href;
                $brand = strtolower($element->innerText());
                Draff::updateOrCreate([
                    'link' => $url,
                ], [
                    'store_id' => $store,
                    'name' => $brand,
                    'status' => '1',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $total++;
            }
            $html = '';
            for ($i = 0; $i < ceil($total / 20); $i++) {
                $html .= '<option value="'.($i * 20).'">'.($i * 20).'-'.(($i + 1) * 20).'</option>';
            }

            return response()->json([
                'status' => 'success',
                'total' => 'Tổng thương hiệu: '.$total,
                'html' => $html,
            ]);
        }
    }

    public function getProduct(Request $request)
    {
        $offset = $request->offset;
        $store = $request->store_id;
        switch ($store) {
            case '1':
                $this->productBeautyBox($offset, $store);
                break;
            case '2':
                $this->productGuardian($offset, $store);
                break;
            case '3':
                $this->productNuty($offset, $store);
                break;
            case '4':
                $this->productHasaki($offset, $store);
                break;
            default:

                break;
        }
    }

    public function productBeautyBox($offset, $store)
    {
        try {
            $draffs = Draff::select('name', 'link', 'store_id')->where('store_id', $store)->offset($offset)->limit(50)->get();
            $total = 0;
            if ($draffs->count() > 0) {
                foreach ($draffs as $value) {
                    $link = 'https://beautybox-api.hsv-tech.io/client/products?sortRuleCollectionId='.$value->brand_id.'&collections='.$value->link.'&sort=createdAt%2CDESC&limit=40&page=1';
                    $ch = curl_init($link);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $content = curl_exec($ch);
                    curl_close($ch);
                    $api = json_decode($content, true);
                    $page = ceil($api['total'] / 40);
                    for ($i = 1; $i <= $page; $i++) {
                        $link2 = 'https://beautybox-api.hsv-tech.io/client/products?sortRuleCollectionId='.$value->brand_id.'&collections='.$value->link.'&sort=createdAt%2CDESC&limit=40&page='.$i;
                        $ch2 = curl_init($link2);
                        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                        $content2 = curl_exec($ch2);
                        curl_close($ch2);
                        $api2 = json_decode($content2, true);
                        if (isset($api2['data']) && ! empty($api2['data'])) {
                            foreach ($api2['data'] as $item) {
                                $url = 'https://beautybox.com.vn/products/'.$item['slug'];
                                $price = (isset($item['variants']['0']['price'])) ? $item['variants']['0']['price'] : $item['currentPrice'];
                                $name = $item['detail']['0']['name'];
                                Compare::updateOrCreate([
                                    'link' => $url,
                                ], [
                                    'store_id' => $value->store_id,
                                    'name' => $name,
                                    'is_link' => 1,
                                    'price' => $price,
                                    'brand' => $value->name,
                                    'user_id' => Auth::id(),
                                    'status' => '1',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]);
                                $total++;
                            }
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'total' => 'Tổng sản phẩm đã lấy: '.$total,
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function productGuardian($offset, $store)
    {
        try {
            $draffs = Draff::select('name', 'link', 'store_id')->where('store_id', $store)->offset($offset)->limit(50)->get();
            $total = 0;
            if ($draffs->count() > 0) {
                foreach ($draffs as $value) {
                    for ($p = 1; $p <= 100; $p++) {
                        $link = $value->link.'&page='.$p;
                        $dom = HtmlDomParser::file_get_html($link);
                        $item = $dom->find('.product-block .box-pro-detail');
                        if (isset($item) && ! empty($item)) {
                            foreach ($item as $element) {
                                $url = 'https://www.guardian.com.vn'.$element->find('.pro-name a', 0)->href;
                                $name = $element->find('.pro-name a', 0)->innerText();
                                $price = str_replace([',', '₫'], '', $element->find('.box-pro-prices .p-compare ', 0)->innerText());
                                Compare::updateOrCreate([
                                    'link' => $url,
                                ], [
                                    'store_id' => $value->store_id,
                                    'name' => $name,
                                    'is_link' => 1,
                                    'price' => $price,
                                    'brand' => $value->name,
                                    'user_id' => Auth::id(),
                                    'status' => '1',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]);
                                $total++;
                            }
                        } else {
                            break;
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'total' => 'Tổng sản phẩm đã lấy: '.$total,
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function productNuty($offset, $store) {}

    public function productHasaki($offset, $store)
    {
        try {
            $draffs = Draff::select('name', 'link', 'store_id')->where('store_id', $store)->offset($offset)->limit(50)->get();
            $total = 0;
            if ($draffs->count() > 0) {
                foreach ($draffs as $value) {
                    for ($p = 1; $p <= 100; $p++) {
                        $link = $value->link.'?p='.$p;
                        $dom = HtmlDomParser::file_get_html($link);
                        $item = $dom->find('.item_sp_hasaki .block_info_item_sp');
                        if (isset($item) && ! empty($item)) {
                            foreach ($item as $element) {
                                $url = $element->href;
                                $name = $element->find('.vn_names', 0)->innerText();
                                $price = str_replace(['.', '₫', ' '], '', $element->find('.item_giamoi', 0)->innerText());
                                Compare::updateOrCreate([
                                    'link' => $url,
                                ], [
                                    'store_id' => $value->store_id,
                                    'name' => $name,
                                    'is_link' => 1,
                                    'price' => $price,
                                    'brand' => $value->name,
                                    'user_id' => Auth::id(),
                                    'status' => '1',
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]);
                                $total++;
                            }
                        } else {
                            break;
                        }
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'total' => 'Tổng sản phẩm đã lấy: '.$total,
            ]);
        } catch (Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function postCrawl()
    {
        // $link = 'https://beautybox.com.vn/brands';
        // // $ch = curl_init($link);
        // // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // // $content = curl_exec($ch);
        // // curl_close($ch);
        // $dom = HtmlDomParser::file_get_html($link);
        // $total = 0;
        // foreach ($dom->find('.section-item a') as $element) {
        //     $url = $element->href;
        //     //$brand = strtolower($element->find('span')->innerText());
        //     echo $url.'<br/>';
        //     $total++;
        // }
        // echo $total;
        $link = 'https://beautybox-api.hsv-tech.io/client/config/collections';
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $api = json_decode($content, true);
        $total = 0;
        if (isset($api) && ! empty($api)) {
            foreach ($api as $item) {
                if ($item['isActive'] == 1 && $item['parentCollectionId'] == 1) {
                    echo 'https://beautybox.com.vn/collections/'.$item['slug'].'-';
                    echo strtolower($item['detail']['0']['name']).'<br/>';
                    $total++;
                }
            }
        }
        echo $total;
    }

    public function crawlProduct()
    {
        // $link = 'https://hasaki.vn/thuong-hieu/closeup.html';
        // $ch = curl_init($link);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $content = curl_exec($ch);
        // curl_close($ch);
        // $dom = HtmlDomParser::file_get_html($link);
        // //$item = $dom->find('.product-list-heading');
        // // if(isset($item) && !empty($item)){
        // 	foreach ($dom->find('.item_sp_hasaki .block_info_item_sp') as $element) {
        //        echo $element->href;
        // 	   echo $element->find('.vn_names',0)->innerText();
        // 	   echo $element->find('.item_giamoi',0)->innerText().'<br/>';
        //     }
        // // }
        $link = 'https://beautybox-api.hsv-tech.io/client/products?sortRuleCollectionId=5&collections=ahc&sort=createdAt%2CDESC&limit=40&page=2';
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($ch);
        curl_close($ch);
        $api = json_decode($content, true);
        if (isset($api['data']) && ! empty($api['data'])) {
            foreach ($api['data'] as $item) {
                $link = 'https://beautybox.com.vn/products/'.$item['slug'];
                $price = (isset($item['variants']['0']['price'])) ? $item['variants']['0']['price'] : $item['currentPrice'];
                $name = $item['detail']['0']['name'];
                echo $name.' - '.$price.'<br/>';
            }
        }
    }
}
