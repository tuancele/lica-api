@extends('Layout::layout')
@section('title','Cài đặt giao hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cài đặt giao hàng',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('delivery.update')}}">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <h4>Miễn phí vận chuyển</h4>
                        <div class="row">
                            <div class="col-md-4">
                                 <div class="form-group">
                                    <label class="control-label">Trạng thái</label>
                                    <p><label style="font-weight: normal;"><input class="minimal" type="radio" name="data[free_ship]" value="1" @if(getConfig('free_ship')==1) checked="" @endif> &nbsp;&nbsp;Bật</label>
                                        <label style="margin-left: 10px;font-weight: normal;"><input class="minimal" type="radio" name="data[free_ship]" value="0" @if(getConfig('free_ship')==0) checked="" @endif> &nbsp;&nbsp;Không</label>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-8">
                                 <div class="form-group">
                                    <label class="control-label">Miễn phí cho giá trị đơn hàng từ</label>
                                    <input type="text" name="data[free_order]" class="form-control" value="{{getConfig('free_order')}}">
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <h4>Giao hàng tiết kiệm</h4>
                        <div class="form-group">
                            <label class="control-label">Kích hoạt:</label>
                             <p><label style="font-weight: normal;"><input class="minimal" type="radio" name="data[ghtk_status]" value="1" @if(getConfig('ghtk_status')==1) checked="" @endif> &nbsp;&nbsp;Có</label>
                            <label style="margin-left: 10px;font-weight: normal;"><input class="minimal" type="radio" name="data[ghtk_status]" value="0" @if(getConfig('ghtk_status')==0) checked="" @endif> &nbsp;&nbsp;Không</label></p>
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Token Key <span>*</span></label>
                            <input type="text" name="data[ghtk_token]" class="form-control" value="{{getConfig('ghtk_token')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">URL môi trường:</label>
                            <input type="text" name="data[ghtk_url]" class="form-control" value="{{getConfig('ghtk_url')}}">   
                        </div> 
                        <hr/>
                        <h4>Cài đặt thông báo cho khách hàng khi đăng đơn thành công.</h4>
                        <div class="note" style="margin-bottom: 15px;">
                            Sử dụng {site_title} để hiển thị tiêu đề website<br/>
                            {ship_id} để hiển thị mã vận đơn bên ghtk<br/>
                            {order_id} để hiển thị mã đơn hàng trên web của bạn<br/>
                            {estimated_deliver} để hiển thị ngày dự kiến giao hàng<br/>
                        </div>
                        <div class="form-group">
                            <label class="control-label">Cho phép gửi mail thông báo:</label>
                            <p><label style="font-weight: normal;"><input class="minimal" type="radio" name="data[delivery_mail_status]" value="1" @if(getConfig('delivery_mail_status')==1) checked="" @endif> &nbsp;&nbsp;Có</label>
                            <label style="margin-left: 10px;font-weight: normal;"><input class="minimal" type="radio" name="data[delivery_mail_status]" value="0" @if(getConfig('delivery_mail_status')==0) checked="" @endif> &nbsp;&nbsp;Không</label></p>
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Tiêu đề mail:</label>
                            <input type="text" name="data[delivery_mail_title]" class="form-control" value="{{getConfig('delivery_mail_title')}}">     
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Nội dung email:</label>
                            <textarea class="form-control ckeditor" class="form-control" name="data[delivery_mail_content]">{{getConfig('delivery_mail_content')}}</textarea>
                        </div>    
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu cài đặt</button>
            </div>
        </div>
    </form>
</section>
<link href="/public/admin/plugins/iCheck/all.css" rel="stylesheet" type="text/css" />
<script src="/public/admin/plugins/iCheck/icheck.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $('input[type="radio"].minimal').iCheck({
      checkboxClass: 'icheckbox_minimal-blue',
      radioClass: 'iradio_minimal-blue'
    });
</script>
<style type="text/css">
    .showimage{
        margin-top: 10px;
        width: 200px;
    }
    .show_favicon{
        margin-top: 10px;
        width: 40px;
    }
</style>
@endsection