<?php

declare(strict_types=1);

namespace App\Themes\website\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rate\Models\Rate;
use Illuminate\Http\Request;
use Validator;

class ReviewController extends Controller
{
    public function getReview($id)
    {
        $data['reviews'] = Rate::where([['product_id', $id], ['status', '1']])->orderBy('created_at', 'desc')->paginate(5);

        return view('Website::review.index', $data);
    }

    public function addReview(Request $request)
    {
        // if(getConfig('recaptcha_status')){
        //     $context = stream_context_create([
        //         'http' => [
        //             'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        //             'method' => 'POST',
        //             'content' => http_build_query([
        //                 'secret' => getConfig('recaptcha_secret_key'),
        //                 'response' => request('recaptcha')
        //             ])
        //         ]
        //     ]);
        //     $result = json_decode(file_get_contents(env('GOOGLEURL'), false, $context));
        //     if($result->success != true){
        //         return response()->json([
        //             'status' => false,
        //             'message' => 'Quá thời gian xử lý! Xin vui lòng thử lại.',
        //         ]);
        //     }
        // }
        $validator = Validator::make($request->all(), [
            'images.*' => 'mimes:jpg,png,webp,gif',
            'name' => 'required',
            'email' => 'required',
        ], [
            'images.mimes' => 'Định dạng hình ảnh không đúng.',
            'name.required' => 'Bạn chưa nhập tên.',
            'email.required' => 'Bạn chưa nhập địa chỉ email.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $images = [];
        if (isset($request->images) && ! empty($request->images)) {
            foreach ($request->images as $key => $image) {
                $imageName = uniqid().'_'.trim($image->getClientOriginalName());
                $image->move('uploads/images/reviews', $imageName);
                array_push($images, 'uploads/images/reviews/'.$imageName);
            }
        }
        $id = Rate::insertGetId([
            'name' => $request->name,
            'rate' => $request->rating,
            'content' => $request->content,
            'is_aff' => $request->is_aff,
            'title' => $request->title,
            'email' => $request->email,
            'product_id' => $request->product_id,
            'images' => json_encode($images),
            'status' => '1',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($id > 0) {
            return response()->json([
                'status' => true,
                'message' => 'Cảm ơn bạn đã tham gia đánh giá cho sản phẩm này!',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra! Xin vui lòng thử lại',
            ]);
        }
    }
}
