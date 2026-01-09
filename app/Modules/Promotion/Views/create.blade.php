@extends('Layout::layout')
@section('title','Thêm mã khuyến mại')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm mã khuyến mại',
])
<section class="content">
   <form role="form" id="tblForm" method="post" ajax="{{route('promotion.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tiêu đề</label>
                                    <input type="text" name="name" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">             
                                </div>
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Mã giảm giá</label>
                                    <input type="text" name="code" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">             
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Số lượt sử dụng <span style="font-weight: normal;font-style: italic;">(Mặc định 0 là không giới hạn)</span></label>
                                            <input type="number" name="number" class="form-control" value="0">
                                        </div> 
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Giá trị giảm</label>
                                            <input type="text" name="value" class="form-control" data-validation="required" data-validation-error-msg="Không được bỏ trống">
                                        </div> 
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label class="control-label">Đơn vị</label>
                                            <select class="form-control" name="unit">
                                                <option value="0">%</option>
                                                <option value="1">đ</option>
                                            </select>
                                        </div> 
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                         <div class="form-group">
                                            <label class="control-label">Giá trị đơn tối thiểu (đ)</label>
                                            <input type="text" name="order_sale" class="form-control" min="0">
                                        </div> 
                                    </div>
                                    <div class="col-md-4">
                                         <div class="form-group">
                                            <label class="control-label">Ngày bắt đầu</label>
                                            <input type="date" name="start" class="form-control" min="{{date('Y-m-d')}}">
                                        </div> 
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Ngày kết thúc</label>
                                            <input type="date" name="end" class="form-control" min="{{date('Y-m-d')}}">
                                        </div> 
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label">Ưu đãi</label>
                                    <textarea class="form-control" name="endow" rows="4"></textarea>
                                </div> 
                                <div class="form-group">
                                    <label class="control-label">Thanh toán</label>
                                    <textarea class="form-control" name="payment" rows="4"></textarea>
                                </div> 
                                <div class="form-group">
                                    <label class="control-label">Chi tiết</label>
                                    <textarea class="form-control" name="content" rows="4"></textarea>
                                </div> 
                                <div class="row">
                                    <div class="col-md-4">
                                        @include('Layout::status')
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('promotion')])
        </div>
    </form>
</section>
<script type="text/javascript">
    $('input[name="star"]').change(function(){
        var star = $(this).val();
        $('input[name="end"]').attr('min',star);
    });
</script>
@endsection