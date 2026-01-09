<?php

namespace App\Modules\Website\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Website\Models\Website;
use App\Modules\Post\Models\Post;
use App\Modules\Menu\Models\GroupMenu;
use Validator;
use Illuminate\Support\Facades\Auth;
use Session;
use App\Traits\Admin;
class ThemesController extends Controller
{
    use Admin;
    public function header(){
        active('themes','header');
        $data['header'] = Website::where('code','header')->first();
        $data['groups'] = GroupMenu::all();
        return view('Website::header',$data);
    }
    public function postHeader(Request $request){
        $block_0 = array(
            'title' => $request->title,
            'logo' => $request->logo,
            'alt' => $request->alt,
            'menu' => $request->menu
        );
        Website::where('id',$request->id)->update(array(
            'block_0' =>  json_encode($block_0),
            'user_id'=> Auth::id(),
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => ''
        ]);
    }
    // Footer
    public function footer(){
        active('themes','footer');
        $data['footer'] = Website::where('code','footer')->first();
        $data['groups'] = GroupMenu::all();
        return view('Website::footer',$data);
    }
    public function postFooter(Request $request){
        $block_4= json_encode(array(
            'logo' => $request->logo,
            'alt' => $request->alt,
            'facebook' => $request->facebook,
            'instagram' => $request->instagram,
            'tiktok' => $request->tiktok,
            'link' => $request->link
        ));
        Website::where('id',$request->id)->update(array(
            'block_0' =>  $request->block_0,
            'block_1' =>  $request->block_1,
            'block_2' =>  $request->block_2,
            'block_3' =>  $request->block_3,
            'block_4' =>  $block_4,
            'user_id'=> Auth::id(),
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => ''
        ]);
    }
    public function home(){
        active('themes','home');
        $data['home'] = Website::where('code','home')->first();
        return view('Website::home',$data);
    }
    public function postHome(Request $request){
        $block_0 = array(
            'image' => $request->image_0,
            'video' => $request->video_0,
            'title' => $request->title_01,
            'title1' => $request->title_02,
            'content' => $request->content_0,
            'status' => $request->status_0,
        );
        $block_1 = array(
            'category' => $request->category_1,
            'number' => $request->number_1,
            'status' => $request->status_1,
        );
        $block_2 = array(
            'title' => $request->title_2,
            'number' => $request->number_2,
            'status' => $request->status_2,
        );
        $block_3 = array(
            'title' => $request->title_3,
            'number' => $request->number_3,
            'status' => $request->status_3,
        );
        $block_5 = array(
            'title' => $request->title_5,
            'image' => $request->image_5,
            'title1' => $request->title_51,
            'title2' => $request->title_52,
            'title3' => $request->title_53,
            'title4' => $request->title_54,
            'title5' => $request->title_55,
            'title6' => $request->title_56,
            'status' => $request->status_5,
        );
        $block_6 = array(
            'title' => $request->title_6,
            'number' => $request->number_6,
            'status' => $request->status_6,
        );
        $block_7 = array(
            'title' => $request->title_7,
            'number' => $request->number_7,
            'status' => $request->status_7,
        );
        $block_8 = array(
            'title' => $request->title_8,
            'category' => $request->category_8,
            'number' => $request->number_8,
            'status' => $request->status_8,
        );
        $block_9 = array(
            'title' => $request->title_9,
            'number' => $request->number_9,
            'status' => $request->status_9,
        );
        Website::where('id',$request->id)->update(array(
            'block_0' =>  json_encode($block_0),
            'block_1' =>  json_encode($block_1),
            'block_2' =>  json_encode($block_2),
            'block_3' =>  json_encode($block_3),
            'block_5' =>  json_encode($block_5),
            'block_6' =>  json_encode($block_6),
            'block_7' =>  json_encode($block_7),
            'block_8' =>  json_encode($block_8),
            'block_9' =>  json_encode($block_9),
            'user_id'=> Auth::id(),
        ));
        return response()->json([
            'status' => 'success',
            'alert' => 'Cập nhật thành công!',
            'url' => ''
        ]);
    }
}