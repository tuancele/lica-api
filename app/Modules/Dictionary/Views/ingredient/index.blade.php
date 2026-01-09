@extends('Layout::layout')
@section('title','Danh sách thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Danh sách thành phần',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-8"> 
                <div class="row">  
                    <form method="get" action="{{route('dictionary.ingredient')}}"> 
                    <div class="col-md-4 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-2 pr-0">
                        @php $status = request()->get('status');@endphp
                        <select class="form-control" name="status">
                            <option value=""  @if($status == "") selected="" @endif>---Trạng thái---</option>
                            <option value="1" @if($status == 1 && $status != "") selected="" @endif>Hiển thị</option>
                            <option value="0" @if($status == 0 && $status != "") selected="" @endif>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="button btn btn-default" type="submit">Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-4">
                <a class="button btn btn-primary pull-right" href="{{route('dictionary.ingredient.crawl')}}"><i class="fa fa-download" aria-hidden="true"></i> Crawl dữ liệu</a>
                <a class="button add btn btn-info pull-right" href="{{route('dictionary.ingredient.create')}}" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Thêm mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="{{route('dictionary.ingredient.delete')}}" action-url="{{route('dictionary.ingredient.action')}}">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="25%">Tiêu đề</th>
                            <th width="15%">Danh mục</th>
                            <th width="15%">Đánh giá</th>
                            <th width="15%">Lợi ích</th>
                            <th width="10%">Trạng thái</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($posts->count() > 0)
                        @foreach($posts as $post)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$post->id}}"></td>
                            <td>
                                <a href="{{asset('ingredient-dictionary/'.$post->slug)}}" target="_blank">{{$post->name}}</a>
                            </td>
                            <td>
                                @php $categories = json_decode($post->cat_id);@endphp
                                @if(isset($categories) && !empty($categories))
                                @foreach($categories as $valcat)
                                @php $category = App\Modules\Dictionary\Models\IngredientCategory::find($valcat) @endphp
                                @if(isset($category) && !empty($category)) <a target="_blank" href="/admin/dictionary/category/edit/{{$category->id}}">{{$category->name}}</a>,@endif
                                @endforeach
                                @endif
                            </td>
                            <td>{{$post->rate->name??'No Rated'}}</td>
                            <td>
                                @php $benefits = json_decode($post->benefit_id);@endphp
                                @if(isset($benefits) && !empty($benefits))
                                @foreach($benefits as $valben)
                                @php $benefit = App\Modules\Dictionary\Models\IngredientBenefit::find($valben) @endphp
                                @if(isset($benefit) && !empty($benefit)) <a target="_blank" href="/admin/dictionary/benefit/edit/{{$benefit->id}}">{{$benefit->name}}</a>,@endif
                                @endforeach
                                @endif
                            </td>
                            <td>
                                <select class="select_status form-control" data-id="{{$post->id}}" data-url="{{route('dictionary.ingredient.status')}}">
                                    <option value="1" @if($post->status == 1) selected="selected" @endif> Hiển thị</option>
                                    <option value="0" @if($post->status == 0) selected="selected" @endif> Ẩn</option>
                                </select>
                            </td>
                            <td>
                                <a class="btn btn-primary btn-xs" href="{{route('dictionary.ingredient.edit',['id' => $post->id])}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$post->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <select class="form-control" name="action" style="width:50%;float:left;margin-right:5px;">
                        <option value="">---Chọn thao tác---</option>
                        <option value="0">Ẩn</option>
                        <option value="1">Hiển thị</option>
                        <option value="2">Xóa</option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right">
                    {{$posts->links()}}
                </div>
            </div>
        </form>
    </div>
</div>
</section>
@endsection