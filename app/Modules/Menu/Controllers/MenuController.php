<?php

declare(strict_types=1);

namespace App\Modules\Menu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Models\GroupMenu;
use App\Modules\Menu\Models\Menu;
use App\Modules\Post\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class MenuController extends Controller
{
    private $model;
    private $view = 'Menu';

    public function __construct(Menu $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        active('menu', 'list');
        $data['groups'] = GroupMenu::all();

        return view($this->view.'::index', $data);
    }

    public function tree(Request $req)
    {
        active('menu', 'list');
        $menu = Menu::where('group_id', $req->id)->orderBy('sort', 'asc')->get();
        $data['menu'] = $this->treeCate($menu, 0);

        return view($this->view.'::tree', $data);
    }

    public function treeCate($array = null, $parent = 0)
    {
        $html = '';
        if (isset($array) && ! empty($array)) {
            $html .= '<ol class="sortable">';
            foreach ($array as $item) {
                if ($item->parent == $parent) {
                    $html .= '<li id="list_'.$item->id.'">';
                    $html .= '<div><i class="fa fa-angle-double-right"></i> '.$item->name.'<a class="btn-delete deleteCate" style="cursor: pointer;" catid="'.$item->id.'" title="Delete">[Xóa]</a><a title="Edit" href="javascript:;" data-id="'.$item->id.'" class="btn-edit btn_edit_link">[Sửa]</a></div>';
                    $html .= $this->treeCate($array, $item->id);
                    $html .= '</li>';
                }
            }
            $html .= '</ol>';
        }

        return $html;
    }

    public function create()
    {
        active('menu', 'top');

        return view($this->view.'::create');
    }

    public function addLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'url' => 'required|min:1|max:250',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'url.required' => 'Bạn chưa nhập đường dẫn',
            'url.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'url.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $id = Menu::insertGetId(
            [
                'name' => $request->name,
                'url' => $request->url,
                'parent' => $request->parent,
                'group_id' => $request->group_id,
                'image' => $request->image,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'errors' => ['alert' => ['0' => 'Thêm không thành công!']],
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        $id = GroupMenu::insertGetId(
            [
                'name' => $request->name,
                'user_id' => Auth::id(),
                'created_at' => date('Y-m-d H:i:s'),
            ]
        );
        if ($id > 0) {
            return response()->json([
                'status' => 'success',
                'alert' => 'Thêm thành công!',
                'url' => '/admin/menu/edit/'.$id,
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
        active('menu', 'top');
        $detail = GroupMenu::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect('admin/menu');
        }
        $data['detail'] = $detail;
        $data['menus'] = Menu::where('group_id', $detail->id)->orderBy('sort', 'asc')->get();

        return view($this->view.'::edit', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        GroupMenu::where('id', $request->id)->update([
            'name' => $request->name,
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $sort = $request->sortable;
        if (isset($sort)) {
            foreach ($sort as $order => $value) {
                $id = (int) $value['item_id'];
                Menu::where('id', $id)->update([
                    'parent' => (int) $value['parent_id'],
                    'sort' => $order,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
        ]);
    }

    public function delete(Request $request)
    {
        $data = GroupMenu::findOrFail($request->id)->delete();
        Menu::where('group_id', $request->id)->delete();

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
            'url' => '/admin/menu',
        ]);
    }

    public function editLink($id)
    {
        $detail = Menu::find($id);
        if (! isset($detail) && empty($detail)) {
            return redirect('admin/menu');
        }
        $data['detail'] = $detail;
        $data['menus'] = Menu::where([['group_id', $detail->group_id], ['id', '!=', $id]])->orderBy('sort', 'asc')->get();

        return view($this->view.'::modal', $data);
    }

    public function postLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:250',
            'url' => 'required|min:1|max:250',
        ], [
            'name.required' => 'Tiêu đề không được bỏ trống.',
            'name.min' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'name.max' => 'Tiêu đề có độ dài từ 1 đến 250 ký tự',
            'url.required' => 'Bạn chưa nhập đường dẫn',
            'url.min' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
            'url.max' => 'Đường dẫn có độ dài từ 1 đến 250 ký tự',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ]);
        }
        Menu::where('id', $request->id)->update([
            'name' => $request->name,
            'url' => $request->url,
            'parent' => $request->parent,
            'image' => $request->image,
            'user_id' => Auth::id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'status' => 'success',
            'alert' => 'Sửa thành công!',
        ]);
    }

    public function deleteLink(Request $request)
    {
        $data = Menu::findOrFail($request->id)->delete();

        return response()->json([
            'status' => 'success',
            'alert' => 'Xóa thành công!',
        ]);
    }

    public function showurl(Request $request)
    {
        if ($request->item == 'contact') {
            echo '<td><label for="inputEmail3" class="control-label">Liên hệ:</label></td>
                    <td><input  type="text" name="url" class="form-control" data-validation="required" value="lien-he" data-validation-error-msg="Không được bỏ trống">
                    </td>';
        } elseif ($request->item == 'post') {
            echo '<td><label for="inputEmail3" class="control-label">Tất cả bài viết:</label></td>
                    <td><input  type="text" name="url" class="form-control" data-validation="required" value="bai-viet" data-validation-error-msg="Không được bỏ trống">
                    </td>';
        } elseif ($request->item == 'product') {
            echo '<td><label for="inputEmail3" class="control-label">Tất cả sản phẩm:</label></td>
                    <td><input  type="text" name="url" class="form-control" data-validation="required" value="san-pham" data-validation-error-msg="Không được bỏ trống">
                    </td>';
        } elseif ($request->item == 'page') {
            $posts = Post::where([['type', 'page'], ['status', '1']])->get();
            echo '<td><label for="inputEmail3" class="control-label">Trang tĩnh:</label></td>';
            if ($posts->count() > 0) {
                echo '<td><select class="form-control" name="url">';
                foreach ($posts as $post) {
                    echo '<option value="'.$post->slug.'">'.$post->name.'</option>';
                }
                echo '</select></td>';
            } else {
                echo '<td><select class="form-control" name="url">';
                echo '<option value="">Không có trang tĩnh</option>';
                echo '</select></td>';
            }
        } elseif ($request->item == 'category') {
            $posts = Post::where([['type', 'category'], ['status', '1']])->get();
            echo '<td><label for="inputEmail3" class="control-label">Chuyên mục bài viết:</label></td>';
            if ($posts->count() > 0) {
                echo '<td><select class="form-control" name="url">';
                echo actionMulti($posts, 0, '');
                echo '</select></td>';
            } else {
                echo '<td><select class="form-control" name="url">';
                echo '<option value="">Không có chuyên mục</option>';
                echo '</select></td>';
            }
        } elseif ($request->item == 'taxonomy') {
            $posts = Post::where([['type', 'taxonomy'], ['status', '1']])->get();
            echo '<td><label for="inputEmail3" class="control-label">Danh mục sản phẩm:</label></td>';
            if ($posts->count() > 0) {
                echo '<td><select class="form-control" name="url">';
                echo actionMulti($posts, 0, '');
                echo '</select></td>';
            } else {
                echo '<td><select class="form-control" name="url">';
                echo '<option value="">Không có chuyên mục</option>';
                echo '</select></td>';
            }
        } else {
            echo '<td><label for="inputEmail3" class="control-label">Đường dẫn:</label></td>
                    <td><input  type="text" name="url" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                    </td>';
        }
    }
}
