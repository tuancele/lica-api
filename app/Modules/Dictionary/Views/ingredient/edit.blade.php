@extends('Layout::layout')
@section('title','Sửa thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa thành phần',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('dictionary.ingredient.update')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <input type="hidden" value="{{$detail->id}}" name="id">
                                @include('Layout::title',['title' => $detail->name])
                                @include('Layout::description',['description' => $detail->description])
                                <div class="form-group">
                                    <label>Sơ lược</label>
                                    <textarea class="form-control ckeditor" name="glance">{{$detail->glance}}</textarea>
                                </div> 
                                <div class="form-group">
                                    <label style="display: block;width: 100%;overflow: hidden;">Nội dung</label>
                                    <textarea class="form-control ckeditor" name="content">{{$detail->content}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label style="display: block;width: 100%;overflow: hidden;">Shortcode sản phẩm <button class="btn btn-default pull-right" type="button" data-toggle="modal" data-target="#myModal">Chèn shortcode</button></label>
                                    <textarea class="form-control" id="shortcode" name="shortcode">{{$detail->shortcode}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Tài liệu</label>
                                    <textarea class="form-control ckeditor" name="reference">{{$detail->reference}}</textarea>
                                </div>
                                <div class="form-group">
                                    <label>Trách nhiệm</label>
                                    <textarea class="form-control" rows="5" name="disclaimer">{{$detail->disclaimer}}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h3 class="box-title"> Tối ưu seo</h3>
                        <p>Thiết lập các thẻ mô tả giúp khách hàng dễ dàng tìm thấy bài viết trên công cụ tìm kiếm như Google.</p>
                        <hr/>
                        @include('Layout::seo',['title' => $detail->seo_title,'description' => $detail->seo_description])
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Danh mục</label>
                            <div class="box-category box-body">
                                @if($categories->count() > 0)
                                @foreach($categories as $category)
                                <label for="cate{{$category->id}}" style="font-weight: normal;">
                                    <input @if(isset($dcat)) @if(in_array($category->id,$dcat)) checked @endif @endif id="cate{{$category->id}}" type="checkbox" class="wgr-checkbox" name="cat_id[]" value="{{$category->id}}">
                                    <span>{{$category->name}}</span>
                                </label>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Lợi ích</label>
                            <div class="box-category box-body">
                                @if($benefits->count() > 0)
                                @foreach($benefits as $benefit)
                                <label for="cate{{$benefit->id}}" style="font-weight: normal;">
                                    <input @if(isset($dben)) @if(in_array($benefit->id,$dben)) checked @endif @endif id="cate{{$benefit->id}}" type="checkbox" class="wgr-checkbox" name="benefit_id[]" value="{{$benefit->id}}">
                                    <span>{{$benefit->name}}</span>
                                </label>
                                @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Đánh giá</label>
                            <select class="form-control" name="rate_id">
                                @if($rates->count() > 0)
                                @foreach($rates as $rate)
                                <option value="{{$rate->id}}" @if($rate->id == $detail->rate_id) selected="" @endif>{{$rate->name}}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-body">
                        @include('Layout::status',['status' => $detail->status])
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('dictionary.ingredient')])
        </div>
    </form>
</section>
<div class="modal fade" tabindex="-1" id="myModal" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Chọn sản phẩm</h4>
      </div>
      <div class="modal-body" style="height: 300px;overflow-y: scroll;">
        @if($products->count() > 0)
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="5%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                    <th width="15%">Hình ảnh</th>
                    <th width="50%">Tiêu đề</th>
                </tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$product->id}}"></td>
                    <td>
                        <img src="{{$product->image}}" style="width: 70px;">
                    </td>
                    <td>{{$product->name}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
        <button type="button" class="btn btn-primary btn_chose_product">Chọn</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<style>
    .box-category{
        height: 250px;
        overflow-y: scroll;
        width: 100%;
        padding:0px;
    }
    .box-category label{
        display: block;
        overflow: hidden;
    }
    .box-category label.parent{
        font-weight: normal;
        margin-left: 30px;
    }
    .box-category input{
        float: left;
    }
</style>
@endsection
@section('footer')
<script>
    $('.btn_chose_product').click(function(){
        var product = [];
        $("#myModal tr td").each(function () {
            if($(this).find("input").is(':checked')){
                product.push($(this).find("input").val());
            }
        })
        var string = '[title Sản phẩm có chứa thành phần][products slug='+product.join(',')+']';
        // InsertHTML(string);
        $('#shortcode').val(string);
        $('#myModal').modal('hide');
    });
    // function InsertHTML(HTML)
    // {
    //   CKEDITOR.instances.my_editor.insertHtml(HTML);
    // }
</script>
@endsection