@extends('Layout::layout')
@section('title','Xuất hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Xuất hàng',
])
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-10"> 
                <div class="row">  
                    <form method="get" action="/admin/export-goods"> 
                    <div class="col-md-6 pr-0">
                        <input type="text" name="keyword" value="{{ request()->get('keyword') }}" class="large_input float_left form-control" placeholder="Từ khóa tìm kiếm">
                    </div>
                    <div class="col-md-4">
                        <button class="button btn btn-default" type="submit"><i class="fa fa-search" aria-hidden="true"></i> Tìm kiếm</button>
                    </div>
                    </form>
                </div>
            </div>
            <div class="col-md-2">
                <a class="button add btn btn-info pull-right" href="/admin/export-goods/create" style="margin-right:5px;"><i class="fa fa-plus" aria-hidden="true"></i> Tạo đơn hàng mới</a>
            </div>
        </div>
    </div><!-- /.box-header -->
    <div class="box-body">
        <form id="tblForm" method="post" delete-url="/admin/export-goods/delete" action-url="/admin/export-goods/action">
            <div class="PageContent">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="3%"><input type="checkbox" id="checkall" class="wgr-checkbox"></th>
                            <th width="10%">Mã đơn hàng</th>
                            <th width="10%">Nội dung xuất </th>  
                            <th width="10%">Ghi chú</th>       
                            <th width="10%">Ngày xuất</th>
                            <th width="10%">Người xuất</th>
                            <th width="10%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list) && !empty($list))
                        @foreach($list as $value)
                        <tr>
                            <td><input type="checkbox" name="checklist[]" class="checkbox wgr-checkbox" value="{{$value->id}}"></td>
                            <td>
                               <a href=""> {{$value->code}} </a>
                            </td>
                            <td>
                               {{$value->subject}}
                            </td>
                            <td>
                                {{$value->content}}
                            </td>
                            <td>
                               {{date('d-m-Y',strtotime($value->created_at))}}
                            </td>
                            <td> 
                                @if($value->user){{$value->user->name}}@endif
                            </td>
                            
                            <td>
                                <a class="btn btn-info btn-xs btnShow" data-id="{{$value->id}}"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                <a class="btn btn-primary btn-xs" href="/admin/export-goods/edit/{{$value->id}}"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                <a class="btn_delete btn btn-danger btn-xs" data-page="{{ app('request')->input('page') }}" data-id="{{$value->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
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
                        <option value="2">Xóa </option>
                    </select>
                    <button class="btn btn-primary btn_action" type="button">Thực hiện</button>
                </div>
                <div class="col-md-8 text-right">
                    {{$list->links()}}
                </div>
            </div>
        </form>
        
    </div>
</div>
</section>
<div class="modal fade" id="showOrder" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-body">
        </div>
    </div>
  </div>
</div>
<script>
    $('body').on('click','.btnShow',function(){
        var id = $(this).attr('data-id');
        $.ajax({
            type: 'post',
            url: '/admin/export-goods/show',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                $('#showOrder').modal('show');
                $('#showOrder .modal-body').html(res);
            },
            error: function (xhr, ajaxOptions, thrownError) {
              if(xhr.status === 403){
                  toastr.error('Bạn không có quyền sử dụng thao tác này', 'Thông báo');
              }else{
                  toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo');
              }
            } 
        })
    });
</script>
@endsection